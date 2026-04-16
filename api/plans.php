<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$db  = getDB();
$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'list';

if ($act === 'list') {
    $rows = $db->query("SELECT * FROM plans WHERE is_active=1 ORDER BY monthly_fee")->fetchAll();
    out(true, 'OK', $rows);
} elseif ($act === 'add' && $_SESSION['role'] === 'admin') {
    $name = trim($b['plan_name']   ?? '');
    $fee  = (float)($b['monthly_fee'] ?? 0);
    $spd  = (int)($b['speed_mbps']   ?? 0) ?: null;
    if (!$name || $fee <= 0) { out(false, 'Plan name and a valid fee are required.'); }
    $chk = $db->prepare("SELECT COUNT(*) FROM plans WHERE plan_name=?");
    $chk->execute([$name]);
    if ($chk->fetchColumn() > 0) { out(false, 'A plan with that name already exists.'); }
    $db->prepare("INSERT INTO plans (plan_name,monthly_fee,speed_mbps) VALUES (?,?,?)")->execute([$name,$fee,$spd]);
    out(true, 'Plan added.');
} elseif ($act === 'edit' && $_SESSION['role'] === 'admin') {
    $pid  = (int)($b['plan_id']    ?? 0);
    $name = trim($b['plan_name']   ?? '');
    $fee  = (float)($b['monthly_fee'] ?? 0);
    $spd  = (int)($b['speed_mbps']   ?? 0) ?: null;
    if (!$pid || !$name || $fee <= 0) { out(false, 'Plan ID, name and valid fee required.'); }
    $db->prepare("UPDATE plans SET plan_name=?,monthly_fee=?,speed_mbps=? WHERE plan_id=?")->execute([$name,$fee,$spd,$pid]);
    out(true, 'Plan updated.');
} else {
    out(false, 'Unknown action');
}

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}
