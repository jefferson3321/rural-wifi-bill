<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$clientId     = getenv('GMAIL_CLIENT_ID');
$clientSecret = getenv('GMAIL_CLIENT_SECRET');
$redirectUri  = 'https://rural-wifi-bill-production.up.railway.app/api/oauth_callback.php';

if (isset($_GET['code'])) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['refresh_token'])) {
        $db = getDB();
        $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('gmail_refresh_token',?) ON DUPLICATE KEY UPDATE setting_value=?")
           ->execute([$response['refresh_token'], $response['refresh_token']]);
        die('<h2>✅ Gmail OAuth connected! You can close this tab.</h2>');
    }
    die('<h2>❌ Error: ' . ($response['error'] ?? 'Unknown') . '</h2>');
}

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'https://mail.google.com/',
    'access_type'   => 'offline',
    'prompt'        => 'consent',
]);
header('Location: ' . $url);
