<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mailer.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$db  = getDB();
$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'list';

if ($act === 'list') {
    listCustomers($db);
} elseif ($act === 'add') {
    addCustomer($db, $b);
} elseif ($act === 'edit_plan') {
    editPlan($db, $b);
} elseif ($act === 'toggle_status') {
    toggleStatus($db, $b);
} else {
    out(false, 'Unknown action');
}

function listCustomers(PDO $db): void {
    $rows = $db->query("
        SELECT u.user_id AS customer_id, u.full_name, u.username,
               u.phone, u.address, u.email,
               u.plan_id, u.billing_day, u.status, u.created_at,
               p.plan_name, p.monthly_fee
        FROM   users u
        JOIN   plans p ON p.plan_id = u.plan_id
        WHERE  u.role = 'customer'
        ORDER  BY u.full_name
    ")->fetchAll();
    out(true, 'OK', $rows);
}

function addCustomer(PDO $db, array $b): void {
    $name  = trim($b['full_name']    ?? '');
    $uname = trim($b['username']     ?? '');
    $pass  = $b['password']          ?? '';
    $email = trim($b['email']        ?? '');
    $phone = trim($b['phone']        ?? '');
    $addr  = trim($b['address']      ?? '');
    $pid   = (int)($b['plan_id']     ?? 0);
    $bday  = (int)($b['billing_day'] ?? 1);

    if (!$name || !$uname || !$pass || !$pid) { out(false, 'Name, username, password and plan required.'); return; }
    if ($bday < 1 || $bday > 28) $bday = 1;

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        out(false, 'Invalid email address.'); return;
    }

    $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
    $chk->execute([$uname]);
    if ($chk->fetchColumn() > 0) { out(false, 'Username already taken.'); return; }

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare("
        INSERT INTO users (role, full_name, username, password_hash, email, phone, address, plan_id, billing_day, status)
        VALUES ('customer', ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ")->execute([$name, $uname, $hash, $email ?: null, $phone, $addr, $pid, $bday]);
    $custId = (int)$db->lastInsertId();

    $plan = $db->prepare("SELECT plan_name, monthly_fee FROM plans WHERE plan_id=?");
    $plan->execute([$pid]);
    $planRow = $plan->fetch();

    $today   = new DateTime();
    $dueDate = (clone $today)->setDate((int)$today->format('Y'), (int)$today->format('m'), $bday);
    if ($dueDate <= $today) $dueDate->modify('+1 month');
    $db->prepare("INSERT INTO invoices (customer_id, plan_id, billing_month, due_date) VALUES (?,?,?,?)")
       ->execute([$custId, $pid, $dueDate->format('F Y'), $dueDate->format('Y-m-d')]);

    if ($email) {
        $loginUrl = getLoginUrl();
        sendMail($email, $name, 'Welcome to Rural WiFi — Your Account Details',
            buildWelcomeEmail([
                'full_name'      => $name,
                'username'       => $uname,
                'plain_password' => $pass,
                'plan_name'      => $planRow['plan_name'],
                'monthly_fee'    => $planRow['monthly_fee'],
                'billing_day'    => $bday,
                'login_url'      => $loginUrl,
            ])
        );
    }
    out(true, 'Customer added.', ['customer_id' => $custId]);
}

function editPlan(PDO $db, array $b): void {
    $cid  = (int)($b['customer_id'] ?? 0);
    $pid  = (int)($b['plan_id']     ?? 0);
    $bday = (int)($b['billing_day'] ?? 1);
    if (!$cid || !$pid) { out(false, 'Missing fields.'); return; }
    if ($bday < 1 || $bday > 28) $bday = 1;
    $db->prepare("UPDATE users SET plan_id=?, billing_day=? WHERE user_id=? AND role='customer'")
       ->execute([$pid, $bday, $cid]);
    out(true, 'Plan updated.');
}

function toggleStatus(PDO $db, array $b): void {
    $cid = (int)($b['customer_id'] ?? 0);
    if (!$cid) { out(false, 'Missing customer_id.'); return; }
    $cur = $db->prepare("SELECT status FROM users WHERE user_id=? AND role='customer'");
    $cur->execute([$cid]);
    $status = $cur->fetchColumn();
    $new    = $status === 'active' ? 'suspended' : 'active';
    $db->prepare("UPDATE users SET status=? WHERE user_id=? AND role='customer'")->execute([$new, $cid]);
    out(true, "Status changed to {$new}.", ['new_status' => $new]);
}

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}
