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
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$b        = json_decode(file_get_contents('php://input'), true) ?? [];
$act      = $b['action'] ?? $_GET['action'] ?? 'list';
$cidParam = (int)($b['customer_id'] ?? $_GET['customer_id'] ?? 0);

if ($act === 'list') {
    if ($role === 'admin') {
        if ($cidParam > 0) {
            $stmt = $db->prepare("SELECT * FROM messages WHERE customer_id=? ORDER BY sent_at ASC");
            $stmt->execute([$cidParam]);
            $db->prepare("UPDATE messages SET is_read=1 WHERE customer_id=? AND sender_type='customer'")->execute([$cidParam]);
            out(true, 'OK', $stmt->fetchAll());
        }
        $rows = $db->query("
            SELECT u.user_id AS customer_id, u.full_name, p.plan_name, u.status,
                   (SELECT message_text FROM messages m WHERE m.customer_id = u.user_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                   (SELECT COUNT(*) FROM messages m WHERE m.customer_id = u.user_id AND m.sender_type='customer' AND m.is_read=0) AS unread_count,
                   (SELECT m2.sent_at FROM messages m2 WHERE m2.customer_id = u.user_id ORDER BY m2.sent_at DESC LIMIT 1) AS last_sent_at
            FROM   users u
            JOIN   plans p ON p.plan_id = u.plan_id
            WHERE  u.role = 'customer'
            ORDER  BY last_sent_at DESC, u.full_name ASC
        ")->fetchAll();
        out(true, 'OK', $rows);
    } else {
        $stmt = $db->prepare("SELECT * FROM messages WHERE customer_id=? ORDER BY sent_at ASC");
        $stmt->execute([$uid]);
        $msgs = $stmt->fetchAll();
        $db->prepare("UPDATE messages SET is_read=1 WHERE customer_id=? AND sender_type='admin'")->execute([$uid]);
        out(true, 'OK', $msgs);
    }
}

if ($act === 'get_conv') {
    if ($role !== 'admin') { out(false, 'Unauthorized'); }
    $cid  = $cidParam;
    if (!$cid) { out(false, 'customer_id required.'); }
    $stmt = $db->prepare("SELECT * FROM messages WHERE customer_id=? ORDER BY sent_at ASC");
    $stmt->execute([$cid]);
    $db->prepare("UPDATE messages SET is_read=1 WHERE customer_id=? AND sender_type='customer'")->execute([$cid]);
    out(true, 'OK', $stmt->fetchAll());
}

if ($act === 'mark_read') {
    $cid = (int)($b['customer_id'] ?? $cidParam);
    if ($role === 'admin' && $cid) {
        $db->prepare("UPDATE messages SET is_read=1 WHERE customer_id=? AND sender_type='customer'")->execute([$cid]);
    } elseif ($role === 'customer') {
        $db->prepare("UPDATE messages SET is_read=1 WHERE customer_id=? AND sender_type='admin'")->execute([$uid]);
    }
    out(true, 'OK');
}

if ($act === 'send') {
    $text = trim($b['message_text'] ?? '');
    if (!$text) { out(false, 'Empty message.'); }
    if (mb_strlen($text) > 2000) { out(false, 'Message too long (max 2000 characters).'); }

    if ($role === 'admin') {
        $cid = (int)($b['customer_id'] ?? 0);
        if (!$cid) { out(false, 'customer_id required.'); }
        $db->prepare("INSERT INTO messages (customer_id,sender_type,sender_id,message_text) VALUES (?,?,?,?)")
           ->execute([$cid, 'admin', $uid, $text]);
        $db->prepare("INSERT INTO notifications (recipient_type,recipient_id,type,title,message)
                      VALUES ('customer',?,'new_message','New Message from Admin',?)")
           ->execute([$cid, mb_substr($text, 0, 100)]);
    } else {
        $db->prepare("INSERT INTO messages (customer_id,sender_type,sender_id,message_text) VALUES (?,?,?,?)")
           ->execute([$uid, 'customer', $uid, $text]);
    }
    out(true, 'Sent.');
}

if ($act === 'unread_count') {
    if ($role === 'admin') {
        $n = $db->query("SELECT COUNT(*) FROM messages WHERE sender_type='customer' AND is_read=0")->fetchColumn();
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE customer_id=? AND sender_type='admin' AND is_read=0");
        $stmt->execute([$uid]);
        $n = $stmt->fetchColumn();
    }
    out(true, 'OK', ['count' => (int)$n]);
}

out(false, 'Unknown action');

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r); exit;
}
