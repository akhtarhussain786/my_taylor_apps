<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';

$pincode = trim($_GET['pincode'] ?? '');

if (empty($pincode)) {
    echo json_encode(['status' => 'error', 'message' => 'Pincode is required']);
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM `service_areas` WHERE `pincode` = ? AND `is_active` = 1");
$stmt->execute([$pincode]);
$area = $stmt->fetch();

if ($area) {
    echo json_encode([
        'status'           => 'success',
        'pincode'          => $area['pincode'],
        'city'             => $area['city'],
        'area_name'        => $area['area_name'],
        'is_24h_available' => (bool)$area['is_24h_available'],
        'delivery_charge'  => (float)$area['delivery_charge'],
        'measurement_fee'  => (float)$area['measurement_fee'],
        'message'          => $area['is_24h_available'] ? '24-Hour Delivery Available' : 'Standard Delivery Available'
    ]);
} else {
    echo json_encode([
        'status'  => 'unavailable',
        'pincode' => $pincode,
        'message' => 'Service Currently Expanding To Your Area'
    ]);
}
