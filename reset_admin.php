<?php
// ============================================================
//  Rural WiFi BillFlow — Admin Password Reset
//
//  PAANO GAMITIN:
//  1. Buksan sa browser: http://localhost/bill/reset_admin.php
//  2. I-type ang bagong password
//  3. I-delete o i-rename ang file na ito pagkatapos!
//
//  ⚠️  I-DELETE ITO AFTER USE — SECURITY RISK!
//
//  NOTE: Gumagamit na ng merged `users` table (role='admin').
// ============================================================

// Auto-disable if not localhost
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
            || ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';
if (!$isLocalhost) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>This page is only accessible from localhost.</p>');
}

require_once __DIR__ . '/config/db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass  = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';
    $username = $_POST['username']  ?? 'admin';
    if (strlen($newPass) < 8) {
        $msg = '<p style="color:red">❌ Password must be at least 8 characters.</p>';
    } elseif ($newPass !== $confirm) {
        $msg = '<p style="color:red">❌ Passwords do not match.</p>';
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $db   = getDB();
        // Updated: queries `users` table filtered by role='admin'
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = ? AND role = 'admin'");
        $stmt->execute([$hash, $username]);
        if ($stmt->rowCount() > 0) {
            $msg = '<p style="color:green">✅ Password updated! <strong>Please delete this file now.</strong></p>';
        } else {
            $msg = '<p style="color:red">❌ Admin username not found.</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Reset Admin Password</title>
<style>body{font-family:sans-serif;max-width:400px;margin:60px auto;padding:20px;}
input{width:100%;padding:10px;margin:8px 0 16px;border:1px solid #ccc;border-radius:6px;font-size:14px;box-sizing:border-box;}
button{background:#1a1208;color:#fff;padding:12px 24px;border:none;border-radius:8px;cursor:pointer;font-size:15px;width:100%;}
.warn{background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;font-size:13px;color:#7a5c00;margin-bottom:20px;}
.info{background:#f0f7ff;border:1px solid #cce0ff;border-radius:8px;padding:12px;font-size:12px;color:#004a99;margin-bottom:16px;}
</style></head><body>
<h2>🔑 Reset Admin Password</h2>
<div class="warn">⚠️ <strong>Delete this file after use!</strong> It should not be accessible in production.</div>
<div class="info">ℹ️ This resets the password of a user with <strong>role = 'admin'</strong> in the <code>users</code> table.</div>
<?= $msg ?>
<form method="POST">
  <label>Admin Username</label>
  <input type="text" name="username" value="admin">
  <label>New Password (min 8 characters)</label>
  <input type="password" name="password" placeholder="Enter new password">
  <label>Confirm Password</label>
  <input type="password" name="confirm" placeholder="Confirm new password">
  <button type="submit">Update Password</button>
</form>
</body></html>
