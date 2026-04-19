function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    $clientId     = getenv('GMAIL_CLIENT_ID');
    $clientSecret = getenv('GMAIL_CLIENT_SECRET');

    try {
        $db           = getDB();
        $rows         = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $refreshToken = $rows['gmail_refresh_token'] ?? '';
        $fromName     = $rows['from_name'] ?? getenv('FROM_NAME') ?? 'Rural WiFi';
        $fromEmail    = $rows['from_email'] ?? getenv('FROM_EMAIL') ?? '';
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
    }

    if (!$refreshToken) {
        return ['success' => false, 'message' => 'Gmail OAuth not connected yet.'];
    }

    // Get access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type'    => 'refresh_token',
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tokenData = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($tokenData['access_token'])) {
        return ['success' => false, 'message' => 'Failed to get Gmail access token.'];
    }

    $accessToken = $tokenData['access_token'];

    // Build email
    $emailContent  = "From: {$fromName} <{$fromEmail}>\r\n";
    $emailContent .= "To: {$toName} <{$toEmail}>\r\n";
    $emailContent .= "Subject: {$subject}\r\n";
    $emailContent .= "MIME-Version: 1.0\r\n";
    $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $emailContent .= $htmlBody;

    $encoded = rtrim(strtr(base64_encode($emailContent), '+/', '-_'), '=');

    // Send via Gmail API
    $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['raw' => $encoded]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        return ['success' => true, 'message' => 'Email sent via Gmail!'];
    }

    return ['success' => false, 'message' => $response['error']['message'] ?? 'Gmail send failed.'];
}
