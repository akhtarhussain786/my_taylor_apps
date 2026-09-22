<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $userId = $currentUser ? $currentUser['id'] : null;

    if ($orderId > 0 && !empty($newStatus)) {
        $prevStatus = $pdo->query("SELECT `order_status` FROM `orders` WHERE `id` = {$orderId}")->fetchColumn();
        
        $pdo->prepare("UPDATE `orders` SET `order_status` = ? WHERE `id` = ?")->execute([$newStatus, $orderId]);
        logAudit($orderId, null, $userId, 'STATUS_CHANGE', $prevStatus, $newStatus, $notes ?: "Status changed to {$newStatus}");

        echo json_encode(['status' => 'success', 'order_id' => $orderId, 'new_status' => $newStatus]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
