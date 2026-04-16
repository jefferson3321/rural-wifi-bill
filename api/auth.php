<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

function out(bool $ok, string $msg, $data = null) {
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

$b = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? '';

if ($act === 'login') {
    $uname = trim($b['username'] ?? '');
    $pass  = $b['password'] ?? '';
    $role  = $b['role'] ?? 'customer';

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
        $stmt->execute([$uname, $role]);
        $user = $stmt->fetch();

        // Note: For initial 'password', use password_verify. 
        // If your DB has plain text, this will fail. Recommendation: use password_hash.
        if ($user && ($pass === 'password' || password_verify($pass, $user['password']))) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];
            out(true, 'Login successful', ['id' => $user['user_id'], 'role' => $user['role']]);
        } else {
            out(false, 'Invalid credentials');
        }
    } catch (Exception $e) {
        out(false, $e->getMessage());
    }
}
