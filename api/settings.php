<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$db = getDB();
$b = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'get';

if ($act === 'get') {
    $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode(['success' => true, 'data' => $rows]);
} elseif ($act === 'save_gcash' || $act === 'save_smtp') {
    foreach ($b as $key => $value) {
        if ($key === 'action') continue;
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                             ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
        $stmt->execute([$key, $value]);
    }
    echo json_encode(['success' => true, 'message' => 'Settings saved']);
}
