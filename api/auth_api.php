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
    case 'login':
        $identifier = trim($inputData['mobile'] ?? $inputData['identifier'] ?? $_POST['identifier'] ?? '');
        $password = trim($inputData['password'] ?? $_POST['password'] ?? '');
        $roleFilter = trim($inputData['role'] ?? $_POST['role'] ?? '');

        if (empty($identifier) && empty($roleFilter)) {
            echo json_encode(['status' => 'error', 'message' => 'Mobile number or role is required.']);
            exit;
        }

        $user = null;
        if (!empty($identifier)) {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `mobile` = ? OR `email` = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();
        }

        if (!$user && !empty($roleFilter)) {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `role` = ? LIMIT 1");
            $stmt->execute([$roleFilter]);
            $user = $stmt->fetch();
        }

        if ($user) {
            // Check allowed roles
            $allowedRoles = ['measurement_executive', 'delivery_executive', 'admin'];
            if (!in_array($user['role'], $allowedRoles)) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied: Only Measurement Executives and Delivery Partners can log in here.']);
                exit;
            }

            unset($user['password_hash']);
            $token = base64_encode($user['id'] . ':' . time() . ':' . md5($user['email']));

            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'mobile' => $user['mobile'],
                    'role' => $user['role'],
                    'status' => $user['status'],
                    'gender' => $user['gender'] ?? 'male',
                    'profile_image' => $user['profile_image'] ?? ''
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number or user not found.']);
        }
        break;

    case 'profile':
        $userId = (int)($_GET['user_id'] ?? $inputData['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name, email, mobile, role, status, gender, profile_image, created_at FROM `users` WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            $stats = [];
            if ($user['role'] === 'measurement_executive') {
                $s1 = $pdo->prepare("SELECT COUNT(*) FROM `appointments` WHERE `executive_id` = ? AND `status` != 'CANCELLED'");
                $s1->execute([$userId]);
                $stats['total_assigned'] = (int)$s1->fetchColumn();

                $s2 = $pdo->prepare("SELECT COUNT(*) FROM `appointments` WHERE `executive_id` = ? AND `status` = 'MEASUREMENT_COMPLETED'");
                $s2->execute([$userId]);
                $stats['completed_today'] = (int)$s2->fetchColumn();

                $s3 = $pdo->prepare("SELECT COUNT(*) FROM `appointments` WHERE `executive_id` = ? AND `status` IN ('BOOKED', 'EXECUTIVE_ASSIGNED', 'EXECUTIVE_ON_THE_WAY', 'EXECUTIVE_ARRIVED')");
                $s3->execute([$userId]);
                $stats['pending_today'] = (int)$s3->fetchColumn();
            } elseif ($user['role'] === 'delivery_executive') {
                $d1 = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `assigned_delivery_id` = ? AND `order_status` = 'DELIVERED'");
                $d1->execute([$userId]);
                $stats['delivered_count'] = (int)$d1->fetchColumn();

                $d2 = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `order_status` IN ('READY_FOR_DISPATCH', 'OUT_FOR_DELIVERY')");
                $d2->execute();
                $stats['pending_deliveries'] = (int)$d2->fetchColumn();
            }

            echo json_encode([
                'status' => 'success',
                'user' => $user,
                'stats' => $stats
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
