<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$db = getDB();
$b = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'list';

if ($act === 'list') {
    $rows = $db->query("SELECT i.*, u.full_name FROM invoices i JOIN users u ON i.customer_id = u.user_id ORDER BY i.created_at DESC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $rows]);
} elseif ($act === 'generate') {
    $month = date('F Y');
    $dueDate = date('Y-m-d', strtotime('+7 days'));
    
    $sql = "INSERT INTO invoices (customer_id, plan_id, billing_month, due_date, amount, status)
            SELECT u.user_id, u.plan_id, ?, ?, p.monthly_fee, 'unpaid'
            FROM users u
            JOIN plans p ON u.plan_id = p.plan_id
            WHERE u.role = 'customer' AND u.status = 'active'
            AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.customer_id = u.user_id AND i.billing_month = ?)";
    $db->prepare($sql)->execute([$month, $dueDate, $month]);
    echo json_encode(['success' => true, 'message' => 'Invoices generated']);
}
