<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
            || ($_SERVER['HTTP_HOST'] ?? '') === 'localhost'
            || str_contains($_SERVER['HTTP_HOST'] ?? '', 'ngrok');

$key = $_GET['key'] ?? '';
if ($key !== 'demo2024' || !$isLocalhost) {
    http_response_code(403);
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Demo endpoint disabled in production.']); exit;
}
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

require_once __DIR__ . '/../config/db.php';
$db  = getDB();
$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? '';

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success'=>$ok,'message'=>$msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}

if ($act === 'list_invoices') {
    $rows = $db->query("
        SELECT i.invoice_id, i.billing_month, p.monthly_fee AS amount, i.status,
               u.full_name AS customer_name
        FROM   invoices i
        JOIN   plans    p ON p.plan_id = i.plan_id
        JOIN   users    u ON u.user_id = i.customer_id AND u.role = 'customer'
        ORDER  BY i.created_at DESC
        LIMIT  200
    ")->fetchAll();
    out(true, 'OK', $rows);
}

if ($act === 'generate_for_month') {
    $month = trim($b['month'] ?? '');
    if (!$month) { out(false, 'Month required.'); }

    $custs = $db->query("
        SELECT u.user_id AS customer_id, u.billing_day, u.plan_id, p.monthly_fee
        FROM   users u
        JOIN   plans p ON p.plan_id = u.plan_id
        WHERE  u.role = 'customer' AND u.status = 'active'
    ")->fetchAll();

    $dt = DateTime::createFromFormat('F Y', $month);
    if (!$dt) { out(false, 'Invalid month format. Use e.g. "March 2026".'); }

    $generated = 0;
    foreach ($custs as $c) {
        $bday = min((int)$c['billing_day'], 28);
        $dueDate = clone $dt;
        $dueDate->setDate((int)$dt->format('Y'), (int)$dt->format('m'), $bday);

        $ex = $db->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id=? AND billing_month=?");
        $ex->execute([$c['customer_id'], $month]);
        if ($ex->fetchColumn() > 0) continue;

        $db->prepare("INSERT INTO invoices (customer_id,plan_id,billing_month,due_date) VALUES (?,?,?,?)")
           ->execute([$c['customer_id'], $c['plan_id'], $month, $dueDate->format('Y-m-d')]);
        $generated++;
    }
    out(true, "Generated {$generated} invoice(s) for {$month}.", ['generated' => $generated]);
}

if ($act === 'delete_all_invoices') {
    $month = trim($b['month'] ?? '');
    if ($month) {
        $stmt = $db->prepare("DELETE FROM invoices WHERE billing_month=?");
        $stmt->execute([$month]);
        $n = $stmt->rowCount();
        out(true, "Deleted {$n} invoice(s) for {$month}.");
    } else {
        $db->exec("DELETE FROM payment_proofs");
        $db->exec("DELETE FROM notifications WHERE invoice_id IS NOT NULL");
        $db->exec("DELETE FROM invoices");
        out(true, "All invoices deleted.");
    }
}

if ($act === 'delete_invoice') {
    $iid = (int)($b['invoice_id'] ?? 0);
    if (!$iid) { out(false, 'Missing invoice_id.'); }
    $db->prepare("DELETE FROM payment_proofs WHERE invoice_id=?")->execute([$iid]);
    $db->prepare("DELETE FROM invoices WHERE invoice_id=?")->execute([$iid]);
    out(true, 'Invoice deleted.');
}

if ($act === 'reset_customers') {
    $db->exec("UPDATE users SET status='active' WHERE role='customer'");
    out(true, 'All customers reactivated.');
}

out(false, 'Unknown action');
