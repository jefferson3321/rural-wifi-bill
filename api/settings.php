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
    echo json_encode($r); exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
} catch (Throwable $e) {
    out(false, 'DB config error: ' . $e->getMessage());
}

if (empty($_SESSION['user_id'])) {
    out(false, 'Unauthorized');
}

$isAdmin = $_SESSION['role'] === 'admin';

try {
    $db = getDB();
} catch (Throwable $e) {
    out(false, 'Database connection failed: ' . $e->getMessage());
}

$b   = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $b['action'] ?? 'get';

if ($act === 'get') {
    try {
        $rows = $db->query("SELECT setting_key, setting_value FROM settings")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
        out(true, 'OK', [
            'gcash_number'    => $rows['gcash_number']    ?? '',
            'gcash_name'      => $rows['gcash_name']      ?? '',
            'smtp_user'       => $rows['smtp_username']   ?? '',
            'smtp_from_name'  => $rows['from_name']       ?? 'Rural WiFi',
            'smtp_pass_set'   => !empty($rows['smtp_password']),
            'smtp_host'       => $rows['smtp_host']       ?? 'smtp.gmail.com',
            'smtp_port'       => $rows['smtp_port']       ?? '587',
            'smtp_encryption' => $rows['smtp_encryption'] ?? 'tls',
            'from_email'      => $rows['from_email']      ?? '',
            'app_url'         => $rows['app_url']         ?? '',
        ]);
    } catch (Throwable $e) {
        out(false, 'Error loading settings: ' . $e->getMessage());
    }
}

if ($act === 'save_gcash' && $isAdmin) {
    $no   = trim($b['gcash_number'] ?? $b['gcash_no']  ?? '');
    $name = trim($b['gcash_name']   ?? $b['acct_name'] ?? '');
    if (!$no || !$name) {
        out(false, 'GCash number and name required.');
    }
    try {
        $upsertSql = "INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?";
        $db->prepare($upsertSql)->execute(['gcash_number', $no,   $no]);
        $db->prepare($upsertSql)->execute(['gcash_name',   $name, $name]);
        out(true, 'GCash settings saved.');
    } catch (Throwable $e) {
        out(false, 'Save failed: ' . $e->getMessage());
    }
}

if ($act === 'save_email' && $isAdmin) {
    $user     = trim($b['smtp_user']      ?? '');
    $fromName = trim($b['smtp_from_name'] ?? 'Rural WiFi');
    $pass     = trim($b['smtp_pass']      ?? '');

    if (!$user) {
        out(false, 'Gmail address required.');
    }

    try {
        $upsertSql = "INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?";
        $db->prepare($upsertSql)->execute(['smtp_username',   $user,             $user]);
        $db->prepare($upsertSql)->execute(['from_email',      $user,             $user]);
        $db->prepare($upsertSql)->execute(['from_name',       $fromName,         $fromName]);
        $db->prepare($upsertSql)->execute(['smtp_host',       'smtp.gmail.com',  'smtp.gmail.com']);
        $db->prepare($upsertSql)->execute(['smtp_port',       '587',             '587']);
        $db->prepare($upsertSql)->execute(['smtp_encryption', 'tls',             'tls']);
        if ($pass !== '') {
            $db->prepare($upsertSql)->execute(['smtp_password', $pass, $pass]);
        }
        out(true, 'Email settings saved.');
    } catch (Throwable $e) {
        out(false, 'Save failed: ' . $e->getMessage());
    }
}

if ($act === 'test_email' && $isAdmin) {
    try {
        require_once __DIR__ . '/mailer.php';
        $smtp = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $to   = $smtp['smtp_username'] ?? '';
        if (!$to) {
            out(false, 'No email configured yet. Save your Gmail settings first.');
        }
        $result = sendMail(
            $to, 'Admin',
            '✅ Test Email — Rural WiFi BillFlow',
            "<div style='font-family:sans-serif;padding:20px;'>
                <h2 style='color:#2e7d52;'>✅ Email is working!</h2>
                <p>This test email was sent from <strong>Rural WiFi BillFlow</strong>.</p>
                <p>Your Gmail SMTP settings are configured correctly.<br>
                Customer invoice emails will be sent automatically.</p>
            </div>"
        );
        out($result['success'], $result['message']);
    } catch (Throwable $e) {
        out(false, 'Test failed: ' . $e->getMessage());
    }
}

if ($act === 'save_app_url' && $isAdmin) {
    $url = rtrim(trim($b['app_url'] ?? ''), '/');
    try {
        $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
           ->execute(['app_url', $url, $url]);
        out(true, 'Portal URL saved.');
    } catch (Throwable $e) {
        out(false, 'Save failed: ' . $e->getMessage());
    }
}

if ($act === 'save_smtp' && $isAdmin) {
    try {
        $fields = ['smtp_host','smtp_port','smtp_username','smtp_password','smtp_encryption','from_email','from_name'];
        foreach ($fields as $f) {
            if (isset($b[$f])) {
                $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
                   ->execute([$f, $b[$f], $b[$f]]);
            }
        }
        out(true, 'SMTP settings saved.');
    } catch (Throwable $e) {
        out(false, 'Save failed: ' . $e->getMessage());
    }
}

out(false, 'Unknown action');
