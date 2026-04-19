<?php
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getSmtpConfig(): array {
    try {
        $db   = getDB();
        $rows = $db->query("SELECT setting_key, setting_value FROM settings")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'host'       => $rows['smtp_host']       ?? 'smtp.gmail.com',
            'port'       => (int)($rows['smtp_port'] ?? 587),
            'encryption' => $rows['smtp_encryption'] ?? 'tls',
            'username'   => $rows['smtp_username']   ?? '',
            'password'   => $rows['smtp_password']   ?? '',
            'from_email' => $rows['from_email']      ?? ($rows['smtp_username'] ?? ''),
            'from_name'  => $rows['from_name']       ?? 'Rural WiFi',
        ];
    } catch (Throwable $e) {
        return [
            'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
            'username' => '', 'password' => '', 'from_email' => '', 'from_name' => 'Rural WiFi',
        ];
    }
}

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    $cfg = getSmtpConfig();
    if (empty($cfg['username']) || empty($cfg['password'])) {
        return ['success' => false, 'message' => 'SMTP not configured. Go to Settings → Email Settings.'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = $cfg['encryption'];
        $mail->Port       = $cfg['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $fromEmail = $cfg['from_email'] ?: $cfg['username'];
        $mail->setFrom($fromEmail, $cfg['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return ['success' => true, 'message' => 'Email sent.'];
    } catch (Exception $e) {
        error_log('[Mailer] ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function emailWrapper(string $content): string {
    return '
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e0d5;">
      <div style="background:#1a1208;padding:24px;text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#c8973a;letter-spacing:1px;">Rural WiFi</div>
        <div style="font-size:11px;color:rgba(255,255,255,.45);letter-spacing:3px;margin-top:4px;">INTERNET SERVICES</div>
      </div>
      <div style="padding:28px 28px 20px;">' . $content . '</div>
      <div style="background:#f5f2eb;padding:14px;text-align:center;border-top:1px solid #e5e0d5;">
        <p style="font-size:11px;color:#999;margin:0;">Rural WiFi Internet Services · Automated notification · Do not reply</p>
      </div>
    </div>';
}

function buildWelcomeEmail(array $d): string {
    $fee  = number_format((float)($d['monthly_fee'] ?? 0), 2);
    $bday = (int)($d['billing_day'] ?? 1);
    $suffix = $bday === 1 ? 'st' : ($bday === 2 ? 'nd' : ($bday === 3 ? 'rd' : 'th'));

    $content = '
      <h2 style="color:#1a1208;margin-top:0;">Welcome to Rural WiFi! 🎉</h2>
      <p style="color:#444;line-height:1.7;">Hi <strong>' . htmlspecialchars($d['full_name']) . '</strong>,<br>
      Your internet account has been created. Here are your login details:</p>

      <div style="background:#f5f2eb;border-radius:8px;padding:16px 20px;margin:16px 0;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:4px 0;color:#666;width:140px;">Username</td><td style="color:#1a1208;font-weight:700;">' . htmlspecialchars($d['username']) . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Password</td><td style="color:#1a1208;font-weight:700;">' . htmlspecialchars($d['plain_password']) . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Plan</td><td style="color:#1a1208;">' . htmlspecialchars($d['plan_name']) . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Monthly Fee</td><td style="color:#c8973a;font-weight:700;">₱' . $fee . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Billing Day</td><td style="color:#1a1208;">Every ' . $bday . $suffix . ' of the month</td></tr>
        </table>
      </div>

      <p style="color:#444;line-height:1.7;">Log in to your customer portal to view your invoices and submit payments:</p>
      <div style="text-align:center;margin:20px 0;">
        <a href="' . htmlspecialchars($d['login_url']) . '" style="background:#c8973a;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:15px;display:inline-block;">
          Login to Portal →
        </a>
      </div>
      <p style="color:#888;font-size:12px;">Keep your password safe. Contact us if you need help.</p>';

    return emailWrapper($content);
}

function buildDueReminderEmail(array $d): string {
    $amount   = number_format((float)($d['amount'] ?? 0), 2);
    $daysLeft = (int)($d['days_left'] ?? 0);
    $due      = $d['due_date'] ?? '';
    $month    = $d['billing_month'] ?? '';

    if ($daysLeft > 0) {
        $statusBadge = '<span style="background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">Due in ' . $daysLeft . ' day(s)</span>';
    } elseif ($daysLeft === 0) {
        $statusBadge = '<span style="background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">⚠️ Due TODAY</span>';
    } else {
        $statusBadge = '<span style="background:#f8d7da;color:#721c24;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">🔴 OVERDUE</span>';
    }

    $content = '
      <h2 style="color:#1a1208;margin-top:0;">📋 Invoice for ' . htmlspecialchars($month) . '</h2>
      <p style="color:#444;">Hi <strong>' . htmlspecialchars($d['full_name']) . '</strong>, ' .
      ($daysLeft > 0 ? 'your bill is coming up.' : ($daysLeft === 0 ? 'your bill is due <strong>today</strong>.' : 'your bill is <strong>overdue</strong>. Please pay as soon as possible.')) .
      '</p>

      <div style="background:#f5f2eb;border-radius:8px;padding:16px 20px;margin:16px 0;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:4px 0;color:#666;width:140px;">Plan</td><td style="color:#1a1208;">' . htmlspecialchars($d['plan_name'] ?? '') . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Amount Due</td><td style="color:#c8973a;font-weight:700;font-size:18px;">₱' . $amount . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Due Date</td><td style="color:#1a1208;">' . htmlspecialchars($due) . '  ' . $statusBadge . '</td></tr>
        </table>
      </div>

      <p style="color:#444;line-height:1.7;"><strong>How to Pay (GCash):</strong><br>
      Send ₱' . $amount . ' to GCash number <strong>' . htmlspecialchars($d['gcash_no'] ?? '') . '</strong><br>
      Account Name: <strong>' . htmlspecialchars($d['gcash_name'] ?? '') . '</strong></p>

      <p style="color:#444;line-height:1.7;">After payment, log in to your portal and upload your GCash screenshot as proof.</p>

      <div style="text-align:center;margin:20px 0;">
        <a href="' . htmlspecialchars($d['login_url'] ?? '#') . '" style="background:#c8973a;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:15px;display:inline-block;">
          Submit Payment Proof →
        </a>
      </div>';

    return emailWrapper($content);
}

function buildReceiptEmail(array $d): string {
    $amount  = number_format((float)($d['amount'] ?? 0), 2);
    $method  = htmlspecialchars($d['payment_method'] ?? 'GCash');
    $ref     = htmlspecialchars($d['gcash_ref'] ?? '');
    $paid    = htmlspecialchars($d['paid_date'] ?? date('Y-m-d'));
    $month   = htmlspecialchars($d['billing_month'] ?? '');
    $plan    = htmlspecialchars($d['plan_name'] ?? '');

    $content = '
      <h2 style="color:#1a1208;margin-top:0;">✅ Payment Confirmed!</h2>
      <p style="color:#444;">Hi <strong>' . htmlspecialchars($d['full_name']) . '</strong>,<br>
      Your payment has been verified and your account is active. Thank you!</p>

      <div style="background:#f5f2eb;border-radius:8px;padding:16px 20px;margin:16px 0;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:4px 0;color:#666;width:160px;">Billing Month</td><td style="color:#1a1208;">' . $month . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Plan</td><td style="color:#1a1208;">' . $plan . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Amount Paid</td><td style="color:#2e7d52;font-weight:700;font-size:18px;">₱' . $amount . '</td></tr>
          <tr><td style="padding:4px 0;color:#666;">Payment Method</td><td style="color:#1a1208;">' . $method . '</td></tr>
          ' . ($ref ? '<tr><td style="padding:4px 0;color:#666;">GCash Ref #</td><td style="color:#1a1208;">' . $ref . '</td></tr>' : '') . '
          <tr><td style="padding:4px 0;color:#666;">Date Paid</td><td style="color:#1a1208;">' . $paid . '</td></tr>
        </table>
      </div>

      <p style="color:#444;line-height:1.7;">You can view your receipt and payment history anytime in your customer portal.</p>
      <p style="color:#888;font-size:12px;">Keep enjoying fast internet! 🚀</p>';

    return emailWrapper($content);
}
