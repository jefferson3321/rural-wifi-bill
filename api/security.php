<?php
// ============================================================
//  Rural WiFi BillFlow — Security Bootstrap
// ============================================================

// Must be called BEFORE session_start() and BEFORE any output

// ── Detect HTTPS ──────────────────────────────────────────
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// ── Error handling — log to file, never to browser ───────
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);
@ini_set('log_errors', 1);

// Safe log path — use system temp if can't write to project folder
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
if (is_writable($logDir)) {
    @ini_set('error_log', $logDir . '/error.log');
} else {
    @ini_set('error_log', sys_get_temp_dir() . '/ruralwifi_error.log');
}

// ── Secure session cookie settings ───────────────────────
// Must be set BEFORE session_start()
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_httponly', '1');
// SameSite=Lax works on both http (localhost) and https (production)
// Strict breaks on some redirects — Lax is the safe default
@ini_set('session.cookie_samesite', 'Lax');
if ($isHttps) {
    @ini_set('session.cookie_secure', '1');
}

// ── Security response headers ─────────────────────────────
// Only send if headers not yet sent (ob_start handles this)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
