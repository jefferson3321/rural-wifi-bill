<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

$key = $_GET['key'] ?? ($argv[1] ?? '');
if ($key !== 'ruralwifi_cron') {
    http_response_code(403);
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Forbidden']));
}
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mailer.php';

$db           = getDB();
$loginUrl     = getLoginUrl();
$gcash        = getGcashSettings();
$today        = new DateTime();
$todayDay     = (int)$today->format('d');
$billingMonth = $today->format('F Y');
$generated    = 0;
$sent         = 0;
$reminded     = 0;
$errors       = [];

$smtpReady = !empty(getenv('RESEND_API_KEY'));

function sentToday(PDO $db, int $custId, int $invId, string $type): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE  recipient_id  = ?
          AND  invoice_id    = ?
          AND  type          = ?
          AND  DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$custId, $invId, $type]);
    return $stmt->fetchColumn() > 0;
}

function logNotif(PDO $db, int $custId, int $invId, string $type, string $title, string $msg): void {
    $db->prepare("
        INSERT INTO notifications (recipient_type, recipient_id, type, title, message, invoice_id)
        VALUES ('customer', ?, ?, ?, ?, ?)
    ")->execute([$custId, $type, $title, $msg, $invId]);
}

// STEP 1 — Mark overdue
$db->exec("UPDATE invoices SET status='overdue' WHERE status='unpaid' AND due_date < CURDATE()");

// STEP 2 — Auto-generate invoice on billing_day
$needInvoice = $db->query("
    SELECT u.user_id AS customer_id, u.full_name, u.email, u.billing_day,
           u.plan_id, p.monthly_fee, p.plan_name
    FROM   users u
    JOIN   plans p ON p.plan_id = u.plan_id
    WHERE  u.role        = 'customer'
      AND  u.status      = 'active'
      AND  u.billing_day <= {$todayDay}
      AND  NOT EXISTS (
               SELECT 1 FROM invoices i
               WHERE  i.customer_id   = u.user_id
                 AND  i.billing_month = '{$billingMonth}'
           )
")->fetchAll();

foreach ($needInvoice as $c) {
    $bday = min((int)$c['billing_day'], 28);
    if ($bday > $todayDay) continue;

    try {
        $due = new DateTime($today->format('Y-m-') . str_pad($bday, 2, '0', STR_PAD_LEFT));
    } catch (Exception $e) {
        $due = new DateTime($today->format('Y-m-28'));
    }

    $db->prepare("
        INSERT INTO invoices (customer_id, plan_id, billing_month, due_date)
        VALUES (?, ?, ?, ?)
    ")->execute([
        $c['customer_id'], $c['plan_id'],
        $billingMonth,
        $due->format('Y-m-d'),
    ]);
    $invoiceId = (int)$db->lastInsertId();
    $generated++;

    logNotif($db, $c['customer_id'], $invoiceId,
        'invoice_sent', 'Invoice Ready',
        'Your invoice for ' . $billingMonth . ' is ready. Please log in to view and pay.'
    );
    $db->prepare("UPDATE invoices SET sent_to_customer=1, sent_date=CURDATE() WHERE invoice_id=?")
       ->execute([$invoiceId]);

   if (empty($c['email'])) {
    $sent++;
    continue;
}
if (!$smtpReady) {
    $errors[] = $c['full_name'] . ': Resend API key not configured.';
    continue;
}

    $daysLeft = max(0, (int)$today->diff($due)->days * ($due >= $today ? 1 : -1));

    $result = sendMail(
        $c['email'], $c['full_name'],
        '📋 Invoice for ' . $billingMonth . ' — ₱' . number_format((float)$c['monthly_fee'], 2) . ' — Rural WiFi',
        buildDueReminderEmail([
            'full_name'     => $c['full_name'],
            'amount'        => $c['monthly_fee'],
            'due_date'      => $due->format('Y-m-d'),
            'billing_month' => $billingMonth,
            'plan_name'     => $c['plan_name'],
            'gcash_no'      => $gcash['gcash_no'],
            'gcash_name'    => $gcash['acct_name'],
            'login_url'     => $loginUrl,
            'days_left'     => $daysLeft,
        ])
    );

    if ($result['success']) { $sent++; }
    else { $errors[] = $c['full_name'] . ' (invoice email): ' . $result['message']; }
}

// STEP 3 — Daily reminder for unpaid/overdue invoices
if ($smtpReady) {
    $unpaidInvoices = $db->query("
        SELECT i.invoice_id, i.customer_id, p.monthly_fee AS amount, i.due_date,
               i.billing_month, i.status,
               u.full_name, u.email,
               p.plan_name
        FROM   invoices  i
        JOIN   users     u ON u.user_id  = i.customer_id AND u.role = 'customer'
        JOIN   plans     p ON p.plan_id  = i.plan_id
        WHERE  i.status       IN ('unpaid', 'overdue')
          AND  i.due_date     <= CURDATE()
          AND  u.status        = 'active'
          AND  u.email        IS NOT NULL
          AND  u.email         <> ''
    ")->fetchAll();

    foreach ($unpaidInvoices as $inv) {
        if (sentToday($db, (int)$inv['customer_id'], (int)$inv['invoice_id'], 'daily_reminder')) continue;

        $dueObj   = new DateTime($inv['due_date']);
        $daysOver = (int)$dueObj->diff($today)->days;

        if ($daysOver === 0) {
            $subject = '⚠️ Bill Due TODAY — ₱' . number_format((float)$inv['amount'], 2) . ' — Rural WiFi';
        } else {
            $subject = '🔴 Bill OVERDUE ' . $daysOver . ' day(s) — ₱' . number_format((float)$inv['amount'], 2) . ' — Rural WiFi';
        }

        $result = sendMail(
            $inv['email'], $inv['full_name'],
            $subject,
            buildDueReminderEmail([
                'full_name'     => $inv['full_name'],
                'amount'        => $inv['amount'],
                'due_date'      => $inv['due_date'],
                'billing_month' => $inv['billing_month'],
                'plan_name'     => $inv['plan_name'],
                'gcash_no'      => $gcash['gcash_no'],
                'gcash_name'    => $gcash['acct_name'],
                'login_url'     => $loginUrl,
                'days_left'     => 0,
            ])
        );

        logNotif($db, (int)$inv['customer_id'], (int)$inv['invoice_id'],
            'daily_reminder',
            $daysOver === 0 ? 'Due Today Reminder' : "Overdue Reminder (Day {$daysOver})",
            $result['success']
                ? 'Reminder sent to ' . $inv['email'] . ($daysOver > 0 ? " ({$daysOver} day(s) overdue)" : '')
                : 'Failed: ' . $result['message']
        );

        if ($result['success']) $reminded++;
        else $errors[] = $inv['full_name'] . ' (reminder): ' . $result['message'];
    }
}

ob_end_clean();
echo json_encode([
    'success'   => true,
    'generated' => $generated,
    'sent'      => $sent,
    'reminded'  => $reminded,
    'errors'    => $errors,
    'timestamp' => date('Y-m-d H:i:s'),
]);
