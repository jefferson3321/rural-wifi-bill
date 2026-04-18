<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mailer.php';

if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$db  = getDB();
$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'list';

if ($act === 'list') {
    listInvoices($db);
} elseif ($act === 'generate') {
    generateInvoices($db, $b);
} elseif ($act === 'generate_for_customer') {
    generateForCustomer($db, $b);
} elseif ($act === 'mark_overdue') {
    markOverdue($db);
} elseif ($act === 'send') {
    sendInvoice($db, $b);
} elseif ($act === 'edit_due_date') {
    editDueDate($db, $b);
} elseif ($act === 'mark_paid_cash') {
    markPaidCash($db, $b);
} elseif ($act === 'delete') {
    deleteInvoice($db, $b);
} else {
    out(false, 'Unknown action');
}

function listInvoices(PDO $db): void {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        $rows = $db->query("
            SELECT i.*, u.full_name AS customer_name, u.billing_day, u.email AS customer_email,
                   p.plan_name, p.monthly_fee, p.monthly_fee AS amount
            FROM   invoices i
            JOIN   users u ON u.user_id  = i.customer_id AND u.role = 'customer'
            JOIN   plans p ON p.plan_id  = i.plan_id
            ORDER  BY i.due_date DESC
        ")->fetchAll();
    } else {
        $uid  = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("
            SELECT i.*, p.plan_name, p.monthly_fee, p.monthly_fee AS amount
            FROM   invoices i
            JOIN   plans p ON p.plan_id = i.plan_id
            WHERE  i.customer_id = ?
            ORDER  BY i.due_date DESC
        ");
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();

        $invIds = array_column($rows, 'invoice_id');
        if ($invIds) {
            $placeholders = implode(',', array_fill(0, count($invIds), '?'));
            $proofStmt = $db->prepare("
                SELECT pp.*
                FROM payment_proofs pp
                INNER JOIN (
                    SELECT invoice_id, MAX(proof_id) AS max_id
                    FROM payment_proofs
                    WHERE invoice_id IN ({$placeholders})
                    GROUP BY invoice_id
                ) latest ON pp.proof_id = latest.max_id
            ");
            $proofStmt->execute($invIds);
            $proofs = [];
            foreach ($proofStmt->fetchAll() as $p) {
                $proofs[$p['invoice_id']] = $p;
            }
            foreach ($rows as &$row) {
                $pr = $proofs[$row['invoice_id']] ?? null;
                $row['proof_status']     = $pr['proof_status']     ?? null;
                $row['rejection_reason'] = $pr['rejection_reason'] ?? null;
                $row['gcash_ref']        = $pr['gcash_ref']        ?? null;
            }
            unset($row);
        }
    }
    $db->exec("UPDATE invoices SET status='overdue' WHERE status='unpaid' AND due_date < CURDATE()");
    out(true, 'OK', $rows);
}

function generateInvoices(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }

    $today = new DateTime();

    $custs = $db->query("
        SELECT u.user_id AS customer_id, u.billing_day, u.plan_id, p.monthly_fee
        FROM   users u
        JOIN   plans p ON p.plan_id = u.plan_id
        WHERE  u.role = 'customer' AND u.status = 'active'
    ")->fetchAll();

    $generated = 0;
    foreach ($custs as $c) {
        $bday = min((int)$c['billing_day'], 28);
        try {
            $due = new DateTime($today->format('Y-m-') . str_pad($bday, 2, '0', STR_PAD_LEFT));
        } catch (Exception $e) {
            $due = new DateTime($today->format('Y-m-28'));
        }
        if ($due < $today) $due->modify('+1 month');
        $billingMonth = $due->format('F Y');

        $ex = $db->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id=? AND billing_month=?");
        $ex->execute([$c['customer_id'], $billingMonth]);
        if ($ex->fetchColumn() > 0) continue;

        $db->prepare("INSERT INTO invoices (customer_id,plan_id,billing_month,due_date) VALUES (?,?,?,?)")
           ->execute([$c['customer_id'], $c['plan_id'], $billingMonth, $due->format('Y-m-d')]);
        $generated++;
    }
    out(true, $generated > 0 ? "Generated {$generated} invoice(s)." : 'All invoices already exist.', ['generated' => $generated]);
}

function generateForCustomer(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }
    $cid = (int)($b['customer_id'] ?? 0);
    if (!$cid) { out(false, 'Missing customer_id.'); return; }

    $cust = $db->prepare("
        SELECT u.user_id AS customer_id, u.billing_day, u.plan_id, u.status, p.monthly_fee
        FROM   users u
        JOIN   plans p ON p.plan_id = u.plan_id
        WHERE  u.user_id = ? AND u.role = 'customer'
    ");
    $cust->execute([$cid]);
    $c = $cust->fetch();
    if (!$c) { out(false, 'Customer not found.'); return; }
    if ($c['status'] === 'suspended') { out(false, 'Customer is suspended — no invoice generated.'); return; }

    $today = new DateTime();
    $bday  = min((int)$c['billing_day'], 28);
    try {
        $due = new DateTime($today->format('Y-m-') . str_pad($bday, 2, '0', STR_PAD_LEFT));
    } catch (Exception $e) {
        $due = new DateTime($today->format('Y-m-28'));
    }
    if ($due < $today) $due->modify('+1 month');
    $billingMonth = $due->format('F Y');

    $ex = $db->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id=? AND billing_month=?");
    $ex->execute([$cid, $billingMonth]);
    if ($ex->fetchColumn() > 0) { out(true, 'Invoice already exists.', ['generated' => 0]); return; }

    $db->prepare("INSERT INTO invoices (customer_id,plan_id,billing_month,due_date) VALUES (?,?,?,?)")
       ->execute([$cid, $c['plan_id'], $billingMonth, $due->format('Y-m-d')]);
    out(true, 'Invoice generated.', ['generated' => 1]);
}

function markOverdue(PDO $db): void {
    $stmt = $db->prepare("UPDATE invoices SET status='overdue' WHERE status='unpaid' AND due_date < CURDATE()");
    $stmt->execute();
    out(true, 'Overdue invoices flagged.', ['updated' => $stmt->rowCount()]);
}

function sendInvoice(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }
    $iid = (int)($b['invoice_id'] ?? 0);
    if (!$iid) { out(false, 'Missing invoice_id.'); return; }

    $stmt = $db->prepare("
        SELECT i.invoice_id, p.monthly_fee AS amount, i.due_date, i.billing_month,
               u.user_id AS customer_id, u.full_name, u.email
        FROM   invoices i
        JOIN   users    u ON u.user_id = i.customer_id AND u.role = 'customer'
        JOIN   plans    p ON p.plan_id = i.plan_id
        WHERE  i.invoice_id = ?
    ");
    $stmt->execute([$iid]);
    $inv = $stmt->fetch();
    if (!$inv) { out(false, 'Invoice not found.'); return; }

    $db->prepare("UPDATE invoices SET sent_to_customer=1, sent_date=CURDATE() WHERE invoice_id=?")
       ->execute([$iid]);

    $portalMsg = 'Your invoice for ' . $inv['billing_month'] . ' is ready. Please log in to view and pay.';
    $db->prepare("INSERT INTO notifications (recipient_type,recipient_id,type,title,message,invoice_id)
                  VALUES ('customer',?,'invoice_sent','Invoice Ready',?,?)")
       ->execute([$inv['customer_id'], $portalMsg, $iid]);

    if (empty($inv['email'])) {
        out(true, 'Invoice sent via portal notification (no email on file).');
        return;
    }

    $smtpCfg = getSmtpConfig();
    if (empty($smtpCfg['username']) || empty($smtpCfg['password'])) {
        $db->prepare("UPDATE invoices SET sent_to_customer=0, sent_date=NULL WHERE invoice_id=?")->execute([$iid]);
        out(false, 'Email not configured. Go to Settings → Email Settings and enter your Gmail + App Password. Customer email: ' . $inv['email']);
        return;
    }

    $loginUrl = getLoginUrl();
    $gcash    = getGcashSettings();

    $planRow = $db->prepare("SELECT p.plan_name FROM invoices i JOIN plans p ON p.plan_id=i.plan_id WHERE i.invoice_id=?");
    $planRow->execute([$iid]);
    $plan = $planRow->fetchColumn() ?: '';

    $today    = new DateTime();
    $dueObj   = new DateTime($inv['due_date']);
    $daysLeft = (int)$today->diff($dueObj)->days;
    if ($dueObj < $today) $daysLeft = 0;

    $result = sendMail(
        $inv['email'],
        $inv['full_name'],
        '📋 Invoice for ' . $inv['billing_month'] . ' — ₱' . number_format((float)$inv['amount'], 2) . ' — Rural WiFi',
        buildDueReminderEmail([
            'full_name'     => $inv['full_name'],
            'amount'        => $inv['amount'],
            'due_date'      => $inv['due_date'],
            'billing_month' => $inv['billing_month'],
            'plan_name'     => $plan,
            'gcash_no'      => $gcash['gcash_no'],
            'gcash_name'    => $gcash['acct_name'],
            'login_url'     => $loginUrl,
            'days_left'     => $daysLeft,
        ])
    );

    if (!$result['success']) {
        $db->prepare("UPDATE invoices SET sent_to_customer=0, sent_date=NULL WHERE invoice_id=?")->execute([$iid]);
        out(false, '❌ Email failed: ' . $result['message']);
        return;
    }

    $db->prepare("INSERT INTO notifications (recipient_type,recipient_id,type,title,message,invoice_id)
                  VALUES ('customer',?,'email_sent','Invoice Emailed',?,?)")
       ->execute([$inv['customer_id'], 'Invoice emailed to ' . $inv['email'], $iid]);

    out(true, '✅ Invoice emailed to ' . $inv['email']);
}

function editDueDate(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }
    $iid  = (int)($b['invoice_id'] ?? 0);
    $date = $b['due_date'] ?? '';
    if (!$iid || !$date) { out(false, 'Missing fields.'); return; }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) { out(false, 'Invalid date format. Use YYYY-MM-DD.'); return; }
    $db->prepare("UPDATE invoices SET due_date=? WHERE invoice_id=?")->execute([$dt->format('Y-m-d'), $iid]);
    out(true, 'Due date updated.');
}

function markPaidCash(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }
    $iid  = (int)($b['invoice_id'] ?? 0);
    $note = trim($b['note'] ?? '');
    if (!$iid) { out(false, 'Missing invoice_id.'); return; }

    $invStmt = $db->prepare("SELECT * FROM invoices WHERE invoice_id=? AND status IN ('unpaid','overdue')");
    $invStmt->execute([$iid]);
    $inv = $invStmt->fetch();
    if (!$inv) { out(false, 'Invoice not found or already paid.'); return; }

    $today    = new DateTime();
    $paidDate = $today->format('Y-m-d');

    $db->prepare("UPDATE invoices SET status='paid', paid_date=?, payment_method='cash' WHERE invoice_id=?")
       ->execute([$paidDate, $iid]);

    $custQ = $db->prepare("
        SELECT u.full_name, u.email, u.billing_day, p.plan_name, p.monthly_fee
        FROM   users u
        JOIN   plans p ON p.plan_id = u.plan_id
        WHERE  u.user_id = ? AND u.role = 'customer'
    ");
    $custQ->execute([$inv['customer_id']]);
    $cust = $custQ->fetch();

    $billingDayChanged = false;
    $newBillingDay     = null;

    if ($cust) {
        $originalBillingDay = (int)$cust['billing_day'];
        $paidDayOfMonth     = (int)$today->format('j');

        if ($paidDayOfMonth > $originalBillingDay) {
            $newBillingDay = max(1, min(28, $paidDayOfMonth));

            if ($newBillingDay !== $originalBillingDay) {
                $db->prepare(
                    "UPDATE users SET billing_day=? WHERE user_id=? AND role='customer'"
                )->execute([$newBillingDay, $inv['customer_id']]);
                $billingDayChanged = true;
            }
        }
    }

    $notifMsg = 'Your cash payment has been recorded. Receipt is available in your portal.';
    if ($billingDayChanged) {
        $suffix = $newBillingDay === 1 ? 'st' : ($newBillingDay === 2 ? 'nd' : ($newBillingDay === 3 ? 'rd' : 'th'));
        $notifMsg .= ' Your next billing day has been updated to the ' . $newBillingDay . $suffix . ' of the month.';
    }

    $db->prepare("INSERT INTO notifications (recipient_type,recipient_id,type,title,message,invoice_id)
                  VALUES ('customer',?,'payment_accepted','Cash Payment Recorded',?,?)")
       ->execute([$inv['customer_id'], $notifMsg, $iid]);

    if (!empty($cust['email'])) {
        sendMail(
            $cust['email'],
            $cust['full_name'],
            '✅ Payment Confirmed — ' . $inv['billing_month'] . ' — Rural WiFi',
            buildReceiptEmail([
                'full_name'      => $cust['full_name'],
                'amount'         => $cust['monthly_fee'],
                'billing_month'  => $inv['billing_month'],
                'plan_name'      => $cust['plan_name'],
                'payment_method' => 'Cash',
                'paid_date'      => $paidDate,
                'gcash_ref'      => '',
                'next_due_date'  => '',
            ])
        );
    }

    $statusMsg = 'Marked as paid (cash). Receipt sent.';
    if ($billingDayChanged) {
        $suffix = $newBillingDay === 1 ? 'st' : ($newBillingDay === 2 ? 'nd' : ($newBillingDay === 3 ? 'rd' : 'th'));
        $statusMsg .= ' Billing day updated to ' . $newBillingDay . $suffix . '.';
    }

    out(true, $statusMsg);
}

function deleteInvoice(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }
    $iid = (int)($b['invoice_id'] ?? 0);
    if (!$iid) { out(false, 'Missing invoice_id.'); return; }

    $inv = $db->prepare("SELECT status FROM invoices WHERE invoice_id=?");
    $inv->execute([$iid]);
    $inv = $inv->fetch();
    if (!$inv) { out(false, 'Invoice not found.'); return; }
    if ($inv['status'] === 'paid') { out(false, 'Cannot delete a paid invoice.'); return; }

    $db->prepare("DELETE FROM payment_proofs WHERE invoice_id=?")->execute([$iid]);
    $db->prepare("DELETE FROM notifications WHERE invoice_id=?")->execute([$iid]);
    $db->prepare("DELETE FROM invoices WHERE invoice_id=?")->execute([$iid]);

    out(true, 'Invoice deleted.');
}

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}
