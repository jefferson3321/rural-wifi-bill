<?php
@ini_set('display_errors', 0);
error_reporting(0);

session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die('data: {"error":"Unauthorized"}' . "\n\n");
}

require_once __DIR__ . '/../config/db.php';

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

session_write_close();

if (ob_get_level()) ob_end_clean();
set_time_limit(0);

function sendEvent(string $event, $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

sendEvent('connected', ['status' => 'ok', 'role' => $role]);

$lastCheck = [
    'notifications' => 0,
    'messages'      => 0,
    'invoices'      => 0,
    'proofs'        => 0,
];

$iteration     = 0;
$maxIterations = 300;

while ($iteration < $maxIterations) {
    if (connection_aborted()) break;

    try {
        $db = getDB();

        if ($role === 'admin') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_type='admin' AND is_read=0 AND is_dismissed=0");
            $stmt->execute();
            $notifCount = (int)$stmt->fetchColumn();
            if ($notifCount !== $lastCheck['notifications']) {
                $lastCheck['notifications'] = $notifCount;
                sendEvent('notifications', ['count' => $notifCount]);
            }

            $stmt = $db->query("SELECT COUNT(*) FROM payment_proofs WHERE proof_status='pending'");
            $pendingProofs = (int)$stmt->fetchColumn();
            if ($pendingProofs !== $lastCheck['proofs']) {
                $lastCheck['proofs'] = $pendingProofs;
                sendEvent('pending_proofs', ['count' => $pendingProofs]);
            }

            $stmt = $db->query("SELECT COUNT(*) FROM messages WHERE sender_type='customer' AND is_read=0");
            $unreadMsgs = (int)$stmt->fetchColumn();
            if ($unreadMsgs !== $lastCheck['messages']) {
                $lastCheck['messages'] = $unreadMsgs;
                sendEvent('unread_messages', ['count' => $unreadMsgs]);
            }

            $stmt = $db->query("SELECT SUM(status='overdue') AS overdue, SUM(status='unpaid') AS unpaid, SUM(status='paid') AS paid FROM invoices");
            $counts = $stmt->fetch();
            $hash = md5(json_encode($counts));
            if ($hash !== $lastCheck['invoices']) {
                $lastCheck['invoices'] = $hash;
                sendEvent('invoice_counts', $counts);
            }

        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_type='customer' AND recipient_id=? AND is_read=0 AND is_dismissed=0");
            $stmt->execute([$uid]);
            $notifCount = (int)$stmt->fetchColumn();
            if ($notifCount !== $lastCheck['notifications']) {
                $lastCheck['notifications'] = $notifCount;
                sendEvent('notifications', ['count' => $notifCount]);
            }

            $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE customer_id=? AND sender_type='admin' AND is_read=0");
            $stmt->execute([$uid]);
            $unreadMsgs = (int)$stmt->fetchColumn();
            if ($unreadMsgs !== $lastCheck['messages']) {
                $lastCheck['messages'] = $unreadMsgs;
                sendEvent('unread_messages', ['count' => $unreadMsgs]);
            }

            $stmt = $db->prepare("SELECT COUNT(*), MAX(status) FROM invoices WHERE customer_id=?");
            $stmt->execute([$uid]);
            $invData = $stmt->fetch(PDO::FETCH_NUM);
            $hash = md5(json_encode($invData));
            if ($hash !== $lastCheck['invoices']) {
                $lastCheck['invoices'] = $hash;
                sendEvent('invoice_update', ['reload' => true]);
            }
        }

    } catch (Throwable $e) {}

    if ($iteration % 30 === 0) {
        sendEvent('heartbeat', ['ts' => time()]);
    }

    sleep(1);
    $iteration++;
}

sendEvent('reconnect', ['delay' => 1000]);
