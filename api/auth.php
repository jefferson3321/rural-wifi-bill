<?php
ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

session_start();

header('Content-Type: application/json');

function out(bool $ok, string $msg, $data = null): void {
    ob_end_clean();
    $r = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
    getDB();
} catch (Throwable $e) {
    out(false, 'DB Error: ' . $e->getMessage());
}

$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? ($_GET['action'] ?? '');

if ($act === 'login')       { doLogin($b);  }
elseif ($act === 'logout')  { doLogout();   }
elseif ($act === 'me')      { doMe();       }
else                        { out(false, 'Unknown action'); }

function doLogin(array $b): void {
    $uname = trim($b['username'] ?? '');
    $pass  = $b['password']      ?? '';
    $role  = $b['role']          ?? 'customer';
    if (!$uname || !$pass) { out(false, 'Username and password required.'); return; }

    try {
        $db = getDB();
    } catch (Throwable $e) {
        out(false, 'DB Error: ' . $e->getMessage()); return;
    }

    $ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip       = trim(explode(',', $ip)[0]);
    $cacheKey = 'login_attempts_' . md5($ip);
    $attStmt  = $db->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
    $attStmt->execute([$cacheKey]);
    $attempts = (int)($attStmt->fetchColumn() ?: 0);
    if ($attempts >= 10) {
        out(false, 'Too many login attempts. Please wait 15 minutes before trying again.'); return;
    }
    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value+1, updated_at=NOW()")
       ->execute([$cacheKey, 1]);

    $stmt = $db->prepare("SELECT user_id AS id, username, full_name AS name, role, status, password_hash FROM users WHERE username=? AND role=? LIMIT 1");
    $stmt->execute([$uname, $role]);
    $user = $stmt->fetch();

    if (!$user) { out(false, 'Invalid username or password.'); return; }

    if ($role === 'customer' && $user['status'] === 'suspended') {
        if (!password_verify($pass, $user['password_hash'])) {
            out(false, 'Invalid username or password.'); return;
        }
        out(false, 'Your account is suspended.', ['suspended' => true]);
        return;
    }

    if (!password_verify($pass, $user['password_hash'])) {
        out(false, 'Invalid username or password.'); return;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];

    try {
        $db->prepare("DELETE FROM settings WHERE setting_key=?")->execute([$cacheKey]);
    } catch (Throwable $e) {}

    out(true, 'Login successful.', ['id' => $user['id'], 'role' => $user['role'], 'name' => $user['name']]);
}

function doLogout(): void {
    $role = $_SESSION['role'] ?? null;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    out(true, 'Logged out.', ['role' => $role]);
}

function doMe(): void {
    if (empty($_SESSION['user_id'])) { out(false, 'Not authenticated.'); return; }
    $base = ['id' => $_SESSION['user_id'], 'role' => $_SESSION['role'], 'name' => $_SESSION['name']];
    if ($_SESSION['role'] === 'customer') {
        try {
            $db   = getDB();
            $stmt = $db->prepare("
                SELECT u.user_id, u.full_name AS name, u.username, u.email,
                       u.phone, u.address, u.billing_day, u.status,
                       p.plan_name, p.monthly_fee
                FROM   users u
                JOIN   plans p ON p.plan_id = u.plan_id
                WHERE  u.user_id = ? AND u.role = 'customer'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $cust = $stmt->fetch();
            if ($cust) $base = array_merge($base, $cust);
        } catch (Throwable $e) {}
    }
    out(true, 'OK', $base);
}
