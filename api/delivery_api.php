<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$inputData = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($inputData['action'])) {
    $action = $inputData['action'];
}

switch ($action) {
    case 'get_deliveries':
        $riderId = (int)($_GET['rider_id'] ?? $inputData['rider_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? $inputData['status'] ?? 'active';

        $sql = "
          SELECT o.*, 
                 s.name AS service_name, s.category AS service_category,
                 u.name AS customer_name, u.mobile AS customer_mobile, u.email AS customer_email,
                 addr.house_no, addr.building, addr.street, addr.area, addr.landmark, addr.city, addr.pincode, addr.latitude, addr.longitude,
                 dp.customer_signature_svg, dp.recipient_name, dp.recipient_relation, dp.delivered_timestamp
          FROM `orders` o
          JOIN `services` s ON o.service_id = s.id
          JOIN `users` u ON o.customer_id = u.id
          JOIN `addresses` addr ON o.delivery_address_id = addr.id
          LEFT JOIN `delivery_proofs` dp ON dp.order_id = o.id
          WHERE 1=1
        ";

        $params = [];
        if ($statusFilter === 'active') {
            $sql .= " AND o.order_status IN ('READY_FOR_DISPATCH', 'OUT_FOR_DELIVERY')";
        } elseif ($statusFilter === 'delivered') {
            $sql .= " AND o.order_status = 'DELIVERED'";
        } elseif ($statusFilter !== 'all' && !empty($statusFilter)) {
            $sql .= " AND o.order_status = ?";
            $params[] = $statusFilter;
        }

        if ($riderId > 0 && $statusFilter === 'delivered') {
            $sql .= " AND o.assigned_delivery_id = ?";
            $params[] = $riderId;
        }

        $sql .= " ORDER BY (o.order_status = 'OUT_FOR_DELIVERY') DESC, (o.order_status = 'READY_FOR_DISPATCH') DESC, o.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Calculate metrics
        $deliveredToday = 0;
        $cashCollected = 0.0;
        $pendingCount = 0;
        foreach ($orders as $ord) {
            if ($ord['order_status'] === 'DELIVERED') {
                $deliveredToday++;
            } else {
                $pendingCount++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($orders),
            'metrics' => [
                'pending_deliveries' => $pendingCount,
                'delivered_today' => $deliveredToday,
                'cash_collected' => $cashCollected
            ],
            'orders' => $orders
        ]);
        break;

    case 'out_for_delivery':
        $orderId = (int)($inputData['order_id'] ?? $_POST['order_id'] ?? 0);
        $riderId = (int)($inputData['rider_id'] ?? $_POST['rider_id'] ?? 7);

        if ($orderId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
            exit;
        }

        $pdo->prepare("UPDATE `orders` SET `order_status` = 'OUT_FOR_DELIVERY', `assigned_delivery_id` = ? WHERE `id` = ?")
            ->execute([$riderId, $orderId]);

        logAudit($orderId, null, $riderId, 'OUT_FOR_DELIVERY_MOBILE', 'READY_FOR_DISPATCH', 'OUT_FOR_DELIVERY', 'Delivery partner started last-mile route via Mobile App');

        echo json_encode([
            'status' => 'success',
            'message' => "Order #{$orderId} marked OUT FOR DELIVERY!",
            'order_id' => $orderId,
            'new_status' => 'OUT_FOR_DELIVERY'
        ]);
        break;

    case 'complete_delivery':
        $orderId = (int)($inputData['order_id'] ?? $_POST['order_id'] ?? 0);
        $riderId = (int)($inputData['rider_id'] ?? $_POST['rider_id'] ?? 7);
        $recipientName = trim($inputData['recipient_name'] ?? $_POST['recipient_name'] ?? 'Customer');
        $recipientRelation = trim($inputData['recipient_relation'] ?? $_POST['recipient_relation'] ?? 'Self');
        $signatureSvg = trim($inputData['signature_data'] ?? $_POST['signature_data'] ?? '');
        $lat = !empty($inputData['latitude']) ? (float)$inputData['latitude'] : null;
        $lng = !empty($inputData['longitude']) ? (float)$inputData['longitude'] : null;
        $photoUrl = $inputData['photo_url'] ?? $_POST['photo_url'] ?? null;

        if ($orderId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $insProof = $pdo->prepare("
              INSERT INTO `delivery_proofs` (
                `order_id`, `delivery_executive_id`, `verification_type`,
                `customer_signature_svg`, `delivery_photo_url`, `recipient_name`, `recipient_relation`,
                `delivery_lat`, `delivery_lng`, `delivered_timestamp`
              ) VALUES (?, ?, 'SIGNATURE_AND_PHOTO', ?, ?, ?, ?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE 
                `customer_signature_svg`=VALUES(`customer_signature_svg`),
                `delivery_photo_url`=VALUES(`delivery_photo_url`),
                `recipient_name`=VALUES(`recipient_name`),
                `recipient_relation`=VALUES(`recipient_relation`),
                `delivery_lat`=VALUES(`delivery_lat`),
                `delivery_lng`=VALUES(`delivery_lng`),
                `delivered_timestamp`=NOW()
            ");
            $insProof->execute([$orderId, $riderId, $signatureSvg, $photoUrl, $recipientName, $recipientRelation, $lat, $lng]);

            $pdo->prepare("UPDATE `orders` SET `order_status` = 'DELIVERED', `delivered_at` = NOW() WHERE `id` = ?")
                ->execute([$orderId]);

            // Mark delivery production task completed
            $pdo->prepare("UPDATE `production_tasks` SET `status` = 'COMPLETED', `completed_at` = NOW() WHERE `order_id` = ? AND `stage` = 'DELIVERY'")
                ->execute([$orderId]);

            logAudit($orderId, null, $riderId, 'DELIVERED_MOBILE', 'OUT_FOR_DELIVERY', 'DELIVERED', "Delivered by Mobile App to {$recipientName} ({$recipientRelation}) with signature proof.");

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => "Order #{$orderId} DELIVERED SUCCESSFULLY! Verified with Customer Signature.",
                'order_id' => $orderId,
                'delivered_at' => date('Y-m-d H:i:s'),
                'recipient' => "{$recipientName} ({$recipientRelation})"
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Delivery update failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
