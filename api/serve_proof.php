<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403); ob_end_clean(); exit;
}

$file = basename($_GET['file'] ?? '');
if (!$file) { http_response_code(400); ob_end_clean(); exit; }

$path = __DIR__ . '/../uploads/proofs/' . $file;
if (!file_exists($path) || !is_file($path)) {
    http_response_code(404); ob_end_clean(); exit;
}

require_once __DIR__ . '/../config/db.php';
if ($_SESSION['role'] === 'customer') {
    $uid  = (int)$_SESSION['user_id'];
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM payment_proofs WHERE proof_image=? AND customer_id=?");
    $stmt->execute(['uploads/proofs/' . $file, $uid]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(403); ob_end_clean(); exit;
    }
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if ($ext === 'jpg' || $ext === 'jpeg') {
    $mime = 'image/jpeg';
} elseif ($ext === 'png') {
    $mime = 'image/png';
} elseif ($ext === 'gif') {
    $mime = 'image/gif';
} elseif ($ext === 'webp') {
    $mime = 'image/webp';
} else {
    $mime = null;
}

if (!$mime) { http_response_code(400); ob_end_clean(); exit; }

ob_end_clean();
header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
readfile($path);
