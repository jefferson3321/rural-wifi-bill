<?php
@ini_set('display_errors', '0');
error_reporting(0);

define('DB_HOST', getenv('DB_HOST') ?: 'akhjsacrifkojrclvrex.supabase.co');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]));
    }
}

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

function getLoginUrl(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
        return 'http://localhost/bill/customer_login.php';
    }
    return 'https://' . $host . '/customer_login.php';
}