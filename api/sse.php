<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
require_once __DIR__ . '/../config/db.php';

$db = getDB();
while (true) {
    $adminNotifs = $db->query("SELECT COUNT(*) FROM notifications WHERE recipient_type='admin' AND is_read=0")->fetchColumn();
    echo "event: notifications\n";
    echo 'data: ' . json_encode(['count' => (int)$adminNotifs]) . "\n\n";
    
    if (ob_get_level()) ob_flush();
    flush();
    sleep(10);
}
