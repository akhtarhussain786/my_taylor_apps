<?php
/**
 * MY TAYLOR - Enterprise SMTP Mailer & Automated Notification Engine
 * Supports Gmail SMTP (App Passwords), Hostinger Webmail, Custom SMTP with TLS/SSL.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/settings.php';

/**
 * Send email using configured SMTP settings
 *
 * @param string $toEmail Recipient email address
 * @param string $toName  Recipient full name
 * @param string $subject Email subject line
 * @param string $htmlBody HTML content
 * @param string $plainText Fallback text content
 * @return array ['success' => bool, 'message' => string]
 */
function sendSmtpEmail($toEmail, $toName, $subject, $htmlBody, $plainText = '') {
    $smtpEnabled = getSetting('smtp_enabled', '0') === '1';
    if (!$smtpEnabled) {
        // Fallback to PHP native mail() if SMTP is disabled
        $fromEmail = getSetting('smtp_from_email', getSetting('company_email', 'concierge@mytaylor.in'));
        $fromName = getSetting('smtp_from_name', APP_NAME);

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $sent = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        return [
            'success' => $sent,
            'message' => $sent ? 'Email sent via default server mail' : 'SMTP is disabled and mail() failed'
        ];
    }

    $host = getSetting('smtp_host', 'smtp.gmail.com');
    $port = (int)getSetting('smtp_port', 587);
    $encryption = strtolower(getSetting('smtp_encryption', 'tls')); // 'tls', 'ssl', or 'none'
    $username = trim(getSetting('smtp_username', ''));
    $password = trim(getSetting('smtp_password', '')); // Gmail App Password
    $fromEmail = trim(getSetting('smtp_from_email', $username ?: 'concierge@mytaylor.in'));
    $fromName = getSetting('smtp_from_name', APP_NAME . ' Concierge');

    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'SMTP Username or Password is not configured in Admin Settings.'];
    }

    $timeout = 15;
    $connectHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;

    $socket = @fsockopen($connectHost, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return ['success' => false, 'message' => "Could not connect to SMTP server ({$host}:{$port}): {$errstr} ({$errno})"];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 512)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCommand = function($cmd) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        return $readResponse();
    };

    // Initial greeting from server
    $greeting = $readResponse();
    if (substr($greeting, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'message' => "SMTP greeting error: {$greeting}"];
    }

    // EHLO
    $clientHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $ehlo = $sendCommand("EHLO {$clientHost}");

    // STARTTLS negotiation if TLS
    if ($encryption === 'tls') {
        $starttls = $sendCommand("STARTTLS");
        if (substr($starttls, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "STARTTLS failed: {$starttls}"];
        }

        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            fclose($socket);
            return ['success' => false, 'message' => 'TLS encryption handshake negotiation failed.'];
        }

        // Re-send EHLO after TLS handshake
        $ehlo = $sendCommand("EHLO {$clientHost}");
    }

    // AUTH LOGIN
    $auth = $sendCommand("AUTH LOGIN");
    if (substr($auth, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => "AUTH LOGIN not accepted: {$auth}"];
    }

    // Send Username (Base64)
    $userRes = $sendCommand(base64_encode($username));
    if (substr($userRes, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => "SMTP Username rejected: {$userRes}"];
    }

    // Send Password (Base64)
    $passRes = $sendCommand(base64_encode($password));
    if (substr($passRes, 0, 3) !== '235') {
        fclose($socket);
        return ['success' => false, 'message' => "SMTP Password/App Password authentication failed. Verify Gmail App Password."];
    }

    // MAIL FROM
    $mailFrom = $sendCommand("MAIL FROM: <{$fromEmail}>");
    if (substr($mailFrom, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => "MAIL FROM failed: {$mailFrom}"];
    }

    // RCPT TO
    $rcptTo = $sendCommand("RCPT TO: <{$toEmail}>");
    if (substr($rcptTo, 0, 3) !== '250' && substr($rcptTo, 0, 3) !== '251') {
        fclose($socket);
        return ['success' => false, 'message' => "Recipient <{$toEmail}> rejected: {$rcptTo}"];
    }

    // DATA
    $dataCmd = $sendCommand("DATA");
    if (substr($dataCmd, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'message' => "DATA command rejected: {$dataCmd}"];
    }

    // Construct MIME Email Message
    $boundary = "====_MYTAYLOR_" . md5(uniqid(time()));
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
    $encodedToName = !empty($toName) ? "=?UTF-8?B?" . base64_encode($toName) . "?=" : '';

    $headers = [
        "From: {$encodedFromName} <{$fromEmail}>",
        "To: {$encodedToName} <{$toEmail}>",
        "Subject: {$encodedSubject}",
        "Date: " . date('r'),
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        "X-Mailer: MY TAYLOR Automated Dispatch Engine"
    ];

    $body = implode("\r\n", $headers) . "\r\n\r\n";

    // Plaintext Part
    if (!empty($plainText)) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainText)) . "\r\n";
    }

    // HTML Part
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $body .= "--{$boundary}--\r\n";
    $body .= ".";

    $sendBody = $sendCommand($body);
    $sendCommand("QUIT");
    fclose($socket);

    if (substr($sendBody, 0, 3) === '250') {
        return ['success' => true, 'message' => 'Email sent successfully via SMTP!'];
    }

    return ['success' => false, 'message' => "Message body rejected: {$sendBody}"];
}

/**
 * Generate Master HTML Email Container with Luxury Gold & Navy Sartorial Theme
 */
function getEmailLayoutTemplate($contentHtml, $preheader = '') {
    $logoUrl = APP_URL . '/logo-white.png';
    $year = date('Y');
    $supportPhone = getSetting('company_phone', '+91 98000 00000');
    $supportEmail = getSetting('company_email', 'concierge@mytaylor.in');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MY TAYLOR</title>
  <style>
    body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    body { margin: 0; padding: 0; background-color: #070D1E; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    .email-container { max-width: 600px; margin: 0 auto; background: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .header { background: #070D1E; padding: 30px 24px; text-align: center; border-bottom: 2px solid #D4AF37; }
    .logo { height: 48px; width: auto; max-width: 220px; }
    .tagline { color: #D4AF37; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; margin-top: 8px; }
    .content { padding: 32px 28px; color: #1E293B; line-height: 1.6; font-size: 14.5px; }
    .badge-status { display: inline-block; padding: 6px 14px; background: #FEF3C7; color: #92400E; font-weight: 700; font-size: 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .btn-gold { display: inline-block; padding: 13px 28px; background: linear-gradient(135deg, #D4AF37 0%, #AA7C11 100%); color: #070D1E !important; font-weight: 800; font-size: 14px; text-decoration: none; border-radius: 8px; text-align: center; letter-spacing: 0.5px; }
    .info-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 18px; margin: 20px 0; }
    .footer { background: #0F172A; padding: 24px; text-align: center; color: #94A3B8; font-size: 12px; line-height: 1.6; }
    .footer a { color: #D4AF37; text-decoration: none; }
  </style>
</head>
<body style="margin:0; padding:24px 10px; background-color:#070D1E;">
  <div style="display:none; font-size:1px; color:#070D1E; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;">
    {$preheader}
  </div>

  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center">
        <div class="email-container">
          <!-- Header -->
          <div class="header">
            <img src="{$logoUrl}" alt="MY TAYLOR" class="logo" style="display:inline-block; border:0;">
            <div class="tagline">Tailored for You. Delivered in 24 Hours.</div>
          </div>

          <!-- Body Content -->
          <div class="content">
            {$contentHtml}
          </div>

          <!-- Footer -->
          <div class="footer">
            <p style="margin:0 0 10px; color:#FFFFFF; font-weight:700; font-size:13px;">MY TAYLOR ATELIER</p>
            <p style="margin:0 0 10px;">Doorstep Measurements • Master Tailoring • 24-Hour Express Delivery</p>
            <p style="margin:0 0 10px;">Concierge Support: <a href="tel:{$supportPhone}">{$supportPhone}</a> | <a href="mailto:{$supportEmail}">{$supportEmail}</a></p>
            <p style="margin:0; font-size:11px; color:#64748B;">© {$year} MY TAYLOR. All rights reserved.</p>
          </div>
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Trigger: Automated Appointment Booking Confirmation Email
 */
function sendAppointmentConfirmationEmail($pdo, $appointmentId) {
    try {
        $stmt = $pdo->prepare("
          SELECT a.*, s.name as service_name, s.base_price, s.measurement_fee,
                 u.name as customer_name, u.email as customer_email, u.mobile as customer_mobile,
                 addr.house_no, addr.building, addr.street, addr.area, addr.city, addr.pincode
          FROM `appointments` a
          JOIN `services` s ON a.service_id = s.id
          JOIN `users` u ON a.customer_id = u.id
          JOIN `addresses` addr ON a.address_id = addr.id
          WHERE a.id = ?
        ");
        $stmt->execute([$appointmentId]);
        $apt = $stmt->fetch();

        if (!$apt || empty($apt['customer_email'])) {
            return false;
        }

        $custName = htmlspecialchars($apt['customer_name']);
        $aptCode = htmlspecialchars($apt['appointment_code']);
        $aptDate = date('D, d M Y', strtotime($apt['appointment_date']));
        $timeSlot = htmlspecialchars($apt['time_slot']);
        $serviceName = htmlspecialchars($apt['service_name']);
        $address = htmlspecialchars("{$apt['house_no']}, {$apt['building']}, {$apt['street']}, {$apt['area']}, {$apt['city']} - {$apt['pincode']}");
        $portalUrl = APP_URL . '/dashboard.php';

        $bodyHtml = <<<HTML
<h2 style="color:#0F172A; margin-top:0; font-size:22px;">Doorstep Appointment Confirmed! ✨</h2>
<p>Dear <strong>{$custName}</strong>,</p>
<p>Thank you for choosing <strong>MY TAYLOR</strong>. Your doorstep measurement visit has been scheduled. Our master measurement specialist will arrive at your address with laser & anatomical measurement dockets.</p>

<div class="info-box">
  <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13.5px;">
    <tr>
      <td width="40%" style="color:#64748B;">Booking Code:</td>
      <td><strong style="color:#D4AF37; font-size:15px;">{$aptCode}</strong></td>
    </tr>
    <tr>
      <td style="color:#64748B;">Service Requested:</td>
      <td><strong>{$serviceName}</strong></td>
    </tr>
    <tr>
      <td style="color:#64748B;">Appointment Date:</td>
      <td><strong>{$aptDate}</strong></td>
    </tr>
    <tr>
      <td style="color:#64748B;">Time Window:</td>
      <td><strong>{$timeSlot}</strong></td>
    </tr>
    <tr>
      <td style="color:#64748B;">Doorstep Address:</td>
      <td>{$address}</td>
    </tr>
    <tr>
      <td style="color:#64748B;">Delivery Promise:</td>
      <td><span style="color:#059669; font-weight:700;">⚡ Guaranteed 24-Hour Turnaround</span></td>
    </tr>
  </table>
</div>

<p style="margin:24px 0 16px; text-align:center;">
  <a href="{$portalUrl}" class="btn-gold">View Your Measurement Docket</a>
</p>

<p style="font-size:13px; color:#64748B; margin-top:20px;">
  <em>Need to reschedule or update address? Reply directly to this email or contact our concierge helpline.</em>
</p>
HTML;

        $fullHtml = getEmailLayoutTemplate($bodyHtml, "Doorstep measurement confirmed for {$aptCode}");
        return sendSmtpEmail($apt['customer_email'], $apt['customer_name'], "Appointment Confirmed: {$aptCode} | MY TAYLOR Doorstep Service", $fullHtml);
    } catch (Exception $e) {
        error_log("Failed sending appointment confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Trigger: Automated Order Status Update Email (Live Tracking Alert)
 */
function sendOrderStatusEmail($pdo, $orderId, $newStatus, $remarks = '') {
    try {
        $stmt = $pdo->prepare("
          SELECT o.*, s.name as service_name,
                 u.name as customer_name, u.email as customer_email, u.mobile as customer_mobile,
                 a.house_no, a.street, a.area, a.city, a.pincode
          FROM `orders` o
          JOIN `services` s ON o.service_id = s.id
          JOIN `users` u ON o.customer_id = u.id
          JOIN `addresses` a ON o.delivery_address_id = a.id
          WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order || empty($order['customer_email'])) {
            return false;
        }

        $custName = htmlspecialchars($order['customer_name']);
        $bookingId = htmlspecialchars($order['booking_id']);
        $serviceName = htmlspecialchars($order['service_name']);
        $statusFormatted = ucwords(str_replace('_', ' ', strtolower($newStatus)));
        $trackUrl = APP_URL . '/track.php?booking_id=' . urlencode($order['booking_id']);

        $statusDescriptions = [
            'ORDER_CONFIRMED'       => 'Your order has been validated and digital docket issued to master artisans.',
            'MEASUREMENT_COMPLETED' => 'Your 15-point anatomical measurements have been recorded and saved to profile.',
            'FABRIC_READY'          => 'Premium fabric has been selected, pre-conditioned, and issued to the cutting bay.',
            'CUTTING_IN_PROGRESS'   => 'Master cutter is crafting your precision garment pattern.',
            'CUTTING_COMPLETED'     => 'Pattern cutting completed with French seam tolerances.',
            'STITCHING_IN_PROGRESS' => 'Senior tailor has commenced artisanal handcrafting and seam alignment.',
            'STITCHING_COMPLETED'   => 'Stitching assembly completed. Moving to buttoning and finishings.',
            'FINISHING'             => 'Steam iron pressing, buttonhole threading, and detail trimming in progress.',
            'QUALITY_CHECK'         => 'Lead QC inspector is executing the 10-point fit & seam inspection.',
            'QC_PASSED'             => 'Garment passed 10-point quality audit with 100% precision.',
            'PACKING'               => 'Sealed in luxury garment casing with tamper-proof QR code dispatch tag.',
            'READY_FOR_DISPATCH'    => 'Garment handed to express delivery logistics hub.',
            'OUT_FOR_DELIVERY'      => 'Express delivery rider is en route to your doorstep!',
            'DELIVERED'             => 'Garment successfully delivered to your doorstep. Enjoy the perfect bespoke fit!',
            'CANCELLED'             => 'Your order has been cancelled. Any applicable refunds have been queued.'
        ];

        $desc = $statusDescriptions[$newStatus] ?? "Your order has progressed to {$statusFormatted}.";
        if (!empty($remarks)) {
            $desc .= " <br><strong>Artisan Note:</strong> " . htmlspecialchars($remarks);
        }

        $bodyHtml = <<<HTML
<h2 style="color:#0F172A; margin-top:0; font-size:22px;">Order Status Update ⚡</h2>
<p>Dear <strong>{$custName}</strong>,</p>
<p>We are delighted to update you on your bespoke garment order <strong>#{$bookingId}</strong> ({$serviceName}):</p>

<div style="text-align:center; margin:24px 0;">
  <span class="badge-status" style="font-size:14px; padding:8px 20px;">{$statusFormatted}</span>
</div>

<div class="info-box">
  <p style="margin:0; font-size:14px; line-height:1.6; color:#334155;">
    {$desc}
  </p>
  <div style="margin-top:14px; padding-top:12px; border-top:1px dashed #CBD5E1; font-size:12.5px; color:#64748B;">
    <strong>Booking ID:</strong> {$bookingId} | <strong>24H SLA Promise:</strong> Express Delivery
  </div>
</div>

<p style="margin:28px 0 16px; text-align:center;">
  <a href="{$trackUrl}" class="btn-gold">Track Live Production Timeline</a>
</p>

<p style="font-size:12.5px; color:#64748B; text-align:center;">
  Click above to watch real-time milestone progress or download your digital invoice.
</p>
HTML;

        $fullHtml = getEmailLayoutTemplate($bodyHtml, "Update on Order {$bookingId}: {$statusFormatted}");
        return sendSmtpEmail($order['customer_email'], $order['customer_name'], "Order Update [{$bookingId}]: {$statusFormatted} | MY TAYLOR 24H SLA", $fullHtml);
    } catch (Exception $e) {
        error_log("Failed sending order status email: " . $e->getMessage());
        return false;
    }
}
