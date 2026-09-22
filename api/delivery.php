<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $sigData = $_POST['signature'] ?? '';
    $recipient = trim($_POST['recipient'] ?? 'Self');
    $riderId = $currentUser ? $currentUser['id'] : 7;

    if ($orderId > 0) {
        $ins = $pdo->prepare("
          INSERT INTO `delivery_proofs` (`order_id`, `delivery_executive_id`, `verification_type`, `customer_signature_svg`, `recipient_name`, `delivered_timestamp`)
          VALUES (?, ?, 'SIGNATURE_AND_PHOTO', ?, ?, NOW())
          ON DUPLICATE KEY UPDATE `customer_signature_svg`=VALUES(`customer_signature_svg`), `recipient_name`=VALUES(`recipient_name`), `delivered_timestamp`=NOW()
        ");
        $ins->execute([$orderId, $riderId, $sigData, $recipient]);

        $pdo->prepare("UPDATE `orders` SET `order_status` = 'DELIVERED', `delivered_at` = NOW() WHERE `id` = ?")->execute([$orderId]);
        logAudit($orderId, null, $riderId, 'DELIVERY_COMPLETED', 'OUT_FOR_DELIVERY', 'DELIVERED', "Delivered with digital signature to {$recipient}");

        echo json_encode(['status' => 'success', 'order_id' => $orderId, 'message' => 'Delivered successfully']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
