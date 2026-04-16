<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/mailer.php';
} catch (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Server init error: ' . $e->getMessage()]);
    exit;
}

if (empty($_SESSION['user_id'])) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
} catch (Throwable $e) {
    out(false, 'DB Error: ' . $e->getMessage());
}

$rawBody = file_get_contents('php://input');
$b       = json_decode($rawBody, true) ?? [];
if (empty($b) && !empty($_POST)) $b = $_POST;
$act = $b['action'] ?? 'list';

if ($act === 'list') {
    listProofs($db);
} elseif ($act === 'submit') {
    submitProof($db, $b);
} elseif ($act === 'review') {
    reviewProof($db, $b);
} else {
    out(false, 'Unknown action');
}

function listProofs(PDO $db): void {
    try {
        $role = $_SESSION['role'];
        if ($role === 'admin') {
            $rows = $db->query("
                SELECT pp.*, i.billing_month, p.monthly_fee AS amount, i.due_date,
                       u.full_name AS customer_name
                FROM   payment_proofs pp
                JOIN   invoices i ON i.invoice_id  = pp.invoice_id
                JOIN   plans    p ON p.plan_id     = i.plan_id
                JOIN   users    u ON u.user_id     = pp.customer_id AND u.role = 'customer'
                ORDER  BY pp.submitted_at DESC
            ")->fetchAll();
        } else {
            $uid  = (int)$_SESSION['user_id'];
            $stmt = $db->prepare("
                SELECT pp.*, i.billing_month, p.monthly_fee AS amount, i.due_date
                FROM   payment_proofs pp
                JOIN   invoices i ON i.invoice_id = pp.invoice_id
                JOIN   plans    p ON p.plan_id    = i.plan_id
                WHERE  pp.customer_id = ?
                ORDER  BY pp.submitted_at DESC
            ");
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
        }
        out(true, 'OK', $rows);
    } catch (Throwable $e) {
        out(false, 'Error loading proofs: ' . $e->getMessage());
    }
}

function submitProof(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'customer') { out(false, 'Unauthorized'); return; }

    $uid = (int)$_SESSION['user_id'];
    $iid = (int)($b['invoice_id'] ?? $_POST['invoice_id'] ?? 0);
    $ref = trim($b['gcash_ref']   ?? $_POST['gcash_ref']  ?? '');
    if (!$iid || !$ref) { out(false, 'Invoice ID and GCash ref required.'); return; }

    try {
        $inv = $db->prepare("SELECT * FROM invoices WHERE invoice_id=? AND customer_id=? AND status IN ('unpaid','overdue')");
        $inv->execute([$iid, $uid]);
        $inv = $inv->fetch();
        if (!$inv) { out(false, 'Invoice not found or already paid.'); return; }

        $pend = $db->prepare("SELECT COUNT(*) FROM payment_proofs WHERE invoice_id=? AND proof_status='pending'");
        $pend->execute([$iid]);
        if ($pend->fetchColumn() > 0) {
            out(false, 'You already have a pending proof for this invoice. Please wait for admin review.');
            return;
        }

        $imagePath = null;
        if (!empty($_FILES['proof_image']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) { out(false, 'Invalid image type.'); return; }

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['proof_image']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    out(false, 'Invalid image content.'); return;
                }
            }

            if ($_FILES['proof_image']['size'] > 5 * 1024 * 1024) {
                out(false, 'Image too large. Maximum 5MB.'); return;
            }

            $filename = 'proof_' . $uid . '_' . time() . '.' . $ext;
            $destDir  = __DIR__ . '/../uploads/proofs/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $destDir . $filename)) {
                $imagePath = 'uploads/proofs/' . $filename;
            }
        }

        $db->prepare("
            INSERT INTO payment_proofs (invoice_id, customer_id, gcash_ref, proof_image, proof_status)
            VALUES (?, ?, ?, ?, 'pending')
        ")->execute([$iid, $uid, $ref, $imagePath]);

        try {
            $db->prepare("
                INSERT INTO notifications (recipient_type, recipient_id, type, title, message, invoice_id)
                VALUES ('admin', 1, 'new_proof', 'New Payment Proof', ?, ?)
            ")->execute(["Customer submitted a GCash proof. Ref: {$ref} — Please review.", $iid]);
        } catch (Throwable $e) {
            error_log('[payments] admin notif error: ' . $e->getMessage());
        }

        try {
            $db->prepare("
                INSERT INTO notifications (recipient_type, recipient_id, type, title, message, invoice_id)
                VALUES ('customer', ?, 'proof_submitted', 'Proof Submitted',
                        'Your payment proof has been submitted. Waiting for admin verification.', ?)
            ")->execute([$uid, $iid]);
        } catch (Throwable $e) {
            error_log('[payments] customer notif error: ' . $e->getMessage());
        }

        out(true, '📤 Proof submitted! Waiting for admin verification.');

    } catch (Throwable $e) {
        out(false, 'Submission error: ' . $e->getMessage());
    }
}

function reviewProof(PDO $db, array $b): void {
    if ($_SESSION['role'] !== 'admin') { out(false, 'Unauthorized'); return; }

    $pid      = (int)($b['proof_id'] ?? 0);
    $decision = $b['decision'] ?? '';
    $reason   = trim($b['reason'] ?? '');
    if (!$pid || !in_array($decision, ['accepted', 'rejected'])) {
        out(false, 'Invalid request.'); return;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM payment_proofs WHERE proof_id=?");
        $stmt->execute([$pid]);
        $proof = $stmt->fetch();
        if (!$proof) { out(false, 'Proof not found.'); return; }
        if ($proof['proof_status'] !== 'pending') { out(false, 'This proof has already been reviewed.'); return; }

        $db->prepare("
            UPDATE payment_proofs
            SET proof_status=?, rejection_reason=?, reviewed_at=NOW(), reviewed_by=?
            WHERE proof_id=?
        ")->execute([$decision, $reason ?: null, (int)$_SESSION['user_id'], $pid]);

        if ($decision === 'accepted') {
            acceptPayment($db, (int)$proof['invoice_id'], (int)$proof['customer_id'], 'gcash', $proof['gcash_ref']);
        } else {
            $rejectMsg = 'Your GCash payment proof was rejected.' .
                ($reason ? ' Reason: ' . $reason : ' Please resubmit with a valid screenshot.');
            try {
                $db->prepare("
                    INSERT INTO notifications (recipient_type, recipient_id, type, title, message, invoice_id)
                    VALUES ('customer', ?, 'payment_rejected', 'Payment Rejected', ?, ?)
                ")->execute([$proof['customer_id'], $rejectMsg, $proof['invoice_id']]);
            } catch (Throwable $e) {
                error_log('[payments] reject notif error: ' . $e->getMessage());
            }
        }

        out(true, $decision === 'accepted' ? '✅ Payment accepted!' : '❌ Payment rejected — customer notified.');
    } catch (Throwable $e) {
        out(false, 'Review error: ' . $e->getMessage());
    }
}

function acceptPayment(PDO $db, int $invoiceId, int $customerId, string $method, string $gcashRef = ''): void {
    try {
        $today    = new DateTime();
        $paidDate = $today->format('Y-m-d');

        $invQ = $db->prepare("
            SELECT i.*, p.monthly_fee AS amount
            FROM   invoices i
            JOIN   plans    p ON p.plan_id = i.plan_id
            WHERE  i.invoice_id = ?
        ");
        $invQ->execute([$invoiceId]);
        $inv = $invQ->fetch();
        if (!$inv) return;

        $db->prepare("UPDATE invoices SET status='paid', paid_date=?, payment_method=? WHERE invoice_id=?")
           ->execute([$paidDate, $method, $invoiceId]);

        $custInfoQ = $db->prepare("
            SELECT u.full_name, u.email, u.billing_day, p.plan_name
            FROM   users u
            JOIN   plans p ON p.plan_id = u.plan_id
            WHERE  u.user_id = ? AND u.role = 'customer'
        ");
        $custInfoQ->execute([$customerId]);
        $cust = $custInfoQ->fetch();

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
                    )->execute([$newBillingDay, $customerId]);
                    $billingDayChanged = true;
                }
            }
        }

        $notifMsg = 'Your GCash payment has been verified and accepted. Receipt is available in your portal.';
        if ($billingDayChanged) {
            $suffix = $newBillingDay === 1 ? 'st' : ($newBillingDay === 2 ? 'nd' : ($newBillingDay === 3 ? 'rd' : 'th'));
            $notifMsg .= ' Your next billing day has been updated to the ' . $newBillingDay . $suffix . ' of the month.';
        }

        try {
            $db->prepare("
                INSERT INTO notifications (recipient_type, recipient_id, type, title, message, invoice_id)
                VALUES ('customer', ?, 'payment_accepted', 'Payment Accepted ✅', ?, ?)
            ")->execute([$customerId, $notifMsg, $invoiceId]);
        } catch (Throwable $e) {
            error_log('[payments] accept notif error: ' . $e->getMessage());
        }

        if (!empty($cust['email'])) {
            $smtpCfg = getSmtpConfig();
            if (!empty($smtpCfg['username']) && !empty($smtpCfg['password'])) {
                sendMail(
                    $cust['email'],
                    $cust['full_name'],
                    '✅ Payment Confirmed — ' . $inv['billing_month'] . ' — Rural WiFi',
                    buildReceiptEmail([
                        'full_name'      => $cust['full_name'],
                        'amount'         => $inv['amount'],
                        'billing_month'  => $inv['billing_month'],
                        'plan_name'      => $cust['plan_name'],
                        'payment_method' => 'GCash',
                        'paid_date'      => $paidDate,
                        'gcash_ref'      => $gcashRef,
                        'next_due_date'  => '',
                    ])
                );
            }
        }
    } catch (Throwable $e) {
        error_log('[payments] acceptPayment error: ' . $e->getMessage());
    }
}

function out(bool $ok, string $msg, $data = null): void {
    while (ob_get_level() > 0) ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r);
    exit;
}
