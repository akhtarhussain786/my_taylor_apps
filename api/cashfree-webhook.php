<?php
/**
 * MY TAYLOR - Cashfree Webhook Listener
 * Receives Real-time asynchronous payment events from Cashfree PG
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data) {
    echo json_encode(['status' => 'ignored', 'message' => 'Empty or non-JSON payload']);
    exit;
}

$pdo = getDbConnection();

// Extract Cashfree webhook event type & payload
$eventType = $data['type'] ?? 'PAYMENT_SUCCESS_WEBHOOK';
$orderId = $data['data']['order']['order_id'] ?? null;
$paymentStatus = $data['data']['payment']['payment_status'] ?? 'SUCCESS';
$amount = (float)($data['data']['payment']['payment_amount'] ?? 0);

if ($orderId && $paymentStatus === 'SUCCESS') {
    // Look up appointment
    $stmt = $pdo->prepare("SELECT * FROM `appointments` WHERE `appointment_code` = ?");
    $stmt->execute([$orderId]);
    $apt = $stmt->fetch();

    if ($apt) {
        // Record payment transaction
        $pdo->prepare("
            INSERT INTO `payment_transactions` (`user_id`, `amount`, `currency`, `method`, `gateway`, `gateway_order_id`, `status`, `notes`)
            VALUES (?, ?, 'INR', 'UPI/ONLINE', 'CASHFREE', ?, 'SUCCESS', 'Cashfree Webhook Confirmation')
        ")->execute([$apt['customer_id'], $amount, $orderId]);

        logAudit(null, $apt['id'], $apt['customer_id'], 'WEBHOOK_PAYMENT_SUCCESS', null, 'PAID', "Cashfree webhook processed for order {$orderId}");
    }
}

echo json_encode(['status' => 'success', 'processed' => true]);
