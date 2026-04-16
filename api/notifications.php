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

$db   = getDB();
$b    = json_decode(file_get_contents('php://input'), true) ?? [];
$act  = $b['action'] ?? 'list';
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

if ($act === 'list') {
    if ($role === 'admin') {
        $rows = $db->query("SELECT * FROM notifications WHERE recipient_type='admin' AND is_dismissed=0 ORDER BY created_at DESC LIMIT 50")->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE recipient_type='customer' AND recipient_id=? AND is_dismissed=0 ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([$uid]); $rows = $stmt->fetchAll();
    }
    out(true, 'OK', $rows);
} elseif ($act === 'mark_read') {
    $nid = (int)($b['notif_id'] ?? 0);
    if (!$nid) { out(false, 'Missing notif_id.'); }
    $db->prepare("UPDATE notifications SET is_read=1 WHERE notif_id=?")->execute([$nid]);
    out(true, 'OK');
} elseif ($act === 'dismiss') {
    $nid = (int)($b['notif_id'] ?? 0);
    if (!$nid) { out(false, 'Missing notif_id.'); }
    $db->prepare("UPDATE notifications SET is_dismissed=1 WHERE notif_id=?")->execute([$nid]);
    out(true, 'OK');
} else {
    out(false, 'Unknown action');
}

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}
