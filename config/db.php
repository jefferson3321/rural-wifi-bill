<?php
// ============================================================
//  Rural WiFi BillFlow — config/db.php
//  Updated for Railway MySQL hosting
// ============================================================

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(0);
@ini_set('log_errors', '1');
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_httponly', '1');
@ini_set('session.cookie_samesite', 'Lax');

// ============================================================
//  DATABASE CONFIG — Kumuha sa Railway Environment Variables
// ============================================================
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'rural_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
//  APP URL
// ============================================================
define('APP_URL', getenv('APP_URL') ?: '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException('Cannot connect to database: ' . $e->getMessage());
    }
}

function detectPublicHost(): string {
    $fwdHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? '';
    $fwdProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https';

    if ($fwdHost && $fwdHost !== 'localhost') {
        return $fwdProto . '://' . $fwdHost;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function autoSaveNgrokUrl(): void {
    $fwdHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
    if (!$fwdHost || strpos($fwdHost, 'ngrok') === false) return;

    $proto   = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '/bill/api/auth.php';
    $parts   = explode('/', trim($script, '/'));
    $base    = '/' . ($parts[0] ?? 'bill');
    $appUrl  = $proto . '://' . $fwdHost . $base;

    try {
        $db  = getDB();
        $cur = $db->query("SELECT setting_value FROM settings WHERE setting_key='app_url' LIMIT 1")->fetchColumn();
        if ($cur !== $appUrl) {
            $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('app_url',?) ON DUPLICATE KEY UPDATE setting_value=?")
               ->execute([$appUrl, $appUrl]);
        }
    } catch (Throwable $e) {}
}

function getLoginUrl(): string {
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/') . '/customer_login.php';
    }
    try {
        $db  = getDB();
        $row = $db->query("SELECT setting_value FROM settings WHERE setting_key='app_url' LIMIT 1")->fetch();
        if ($row && !empty($row['setting_value'])) {
            return rtrim($row['setting_value'], '/') . '/customer_login.php';
        }
    } catch (Throwable $e) {}

    $base   = detectPublicHost();
    $script = $_SERVER['SCRIPT_NAME'] ?? '/bill/api/auth.php';
    $parts  = explode('/', trim($script, '/'));
    $dir    = '/' . ($parts[0] ?? 'bill');
    return $base . $dir . '/customer_login.php';
}

// ── Helper: get gcash settings from the unified settings table ──
function getGcashSettings(): array {
    try {
        $db   = getDB();
        $rows = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('gcash_number','gcash_name')")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'gcash_no'  => $rows['gcash_number'] ?? '',
            'acct_name' => $rows['gcash_name']   ?? '',
        ];
    } catch (Throwable $e) {
        return ['gcash_no' => '', 'acct_name' => ''];
    }
}

autoSaveNgrokUrl();
