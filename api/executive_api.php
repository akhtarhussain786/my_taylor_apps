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
    case 'get_appointments':
        $execId = (int)($_GET['executive_id'] ?? $inputData['executive_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? $inputData['status'] ?? 'all';
        $dateFilter = $_GET['date'] ?? $inputData['date'] ?? '';

        $sql = "
          SELECT a.*, 
                 s.name AS service_name, s.category AS service_category, s.base_price, s.express_price, s.measurement_fee,
                 u.name AS customer_name, u.mobile AS customer_mobile, u.email AS customer_email,
                 addr.house_no, addr.building, addr.street, addr.area, addr.landmark, addr.city, addr.pincode, addr.latitude, addr.longitude
          FROM `appointments` a
          JOIN `services` s ON a.service_id = s.id
          JOIN `users` u ON a.customer_id = u.id
          JOIN `addresses` addr ON a.address_id = addr.id
          WHERE 1=1
        ";

        $params = [];
        if ($execId > 0) {
            $sql .= " AND (a.executive_id = ? OR a.executive_id IS NULL)";
            $params[] = $execId;
        }

        if (!empty($dateFilter)) {
            $sql .= " AND a.appointment_date = ?";
            $params[] = $dateFilter;
        }

        if ($statusFilter !== 'all' && !empty($statusFilter)) {
            $sql .= " AND a.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY a.appointment_date ASC, a.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'count' => count($appointments),
            'appointments' => $appointments
        ]);
        break;

    case 'update_status':
        $aptId = (int)($inputData['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
        $execId = (int)($inputData['executive_id'] ?? $_POST['executive_id'] ?? 2);
        $newStatus = trim($inputData['new_status'] ?? $_POST['new_status'] ?? 'EXECUTIVE_ON_THE_WAY');

        if ($aptId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Appointment ID is required']);
            exit;
        }

        $pdo->prepare("UPDATE `appointments` SET `status` = ?, `executive_id` = ? WHERE `id` = ?")
            ->execute([$newStatus, $execId, $aptId]);

        logAudit(null, $aptId, $execId, 'APPOINTMENT_STATUS_UPDATE', null, $newStatus, "Mobile App: Executive updated status to {$newStatus}");

        echo json_encode([
            'status' => 'success',
            'message' => "Appointment status updated to {$newStatus}",
            'appointment_id' => $aptId,
            'new_status' => $newStatus
        ]);
        break;

    case 'submit_measurement':
        $aptId = (int)($inputData['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
        $execId = (int)($inputData['executive_id'] ?? $_POST['executive_id'] ?? 2);
        $garmentType = trim($inputData['garment_type'] ?? $_POST['garment_type'] ?? 'Shirt');
        $fitPref = trim($inputData['fit_preference'] ?? $_POST['fit_preference'] ?? 'Regular Fit');
        $notes = trim($inputData['notes'] ?? $_POST['notes'] ?? '');
        $fabricSource = trim($inputData['fabric_source'] ?? $_POST['fabric_source'] ?? 'MY_TAYLOR_FABRIC');
        $fabricId = !empty($inputData['fabric_id']) ? (int)$inputData['fabric_id'] : (!empty($_POST['fabric_id']) ? (int)$_POST['fabric_id'] : 1);
        $measurements = $inputData['measurements'] ?? $_POST['measurements'] ?? [];
        $designSpecs = $inputData['design_specs'] ?? $_POST['design_specs'] ?? [];
        $referenceImage = $inputData['reference_image'] ?? $_POST['reference_image'] ?? null;

        if (is_string($measurements)) {
            $measurements = json_decode($measurements, true) ?? [];
        }
        if (is_string($designSpecs)) {
            $designSpecs = json_decode($designSpecs, true) ?? [];
        }

        if ($aptId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Appointment ID is required']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $aptStmt = $pdo->prepare("SELECT a.*, s.base_price, s.express_price, s.measurement_fee FROM `appointments` a JOIN `services` s ON a.service_id = s.id WHERE a.id = ?");
            $aptStmt->execute([$aptId]);
            $apt = $aptStmt->fetch();

            if (!$apt) {
                throw new Exception("Appointment not found");
            }

            // 1. Create Measurement Profile
            $mCode = generateMeasurementCode($pdo);
            $insM = $pdo->prepare("
              INSERT INTO `measurements` (
                `measurement_code`, `customer_id`, `garment_category`, `measurements_json`,
                `fit_preference`, `design_specs_json`, `reference_image`, `notes`, `created_by_user_id`
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insM->execute([
                $mCode,
                $apt['customer_id'],
                $garmentType,
                json_encode($measurements),
                $fitPref,
                json_encode($designSpecs),
                $referenceImage,
                $notes,
                $execId
            ]);
            $measurementId = $pdo->lastInsertId();

            // 2. Mark Appointment Completed
            $pdo->prepare("UPDATE `appointments` SET `status` = 'MEASUREMENT_COMPLETED', `completed_at` = NOW() WHERE `id` = ?")
                ->execute([$aptId]);

            // 3. Create Confirmed Order with 24H SLA countdown
            $bookingId = generateBookingId($pdo);
            $slaStart = date('Y-m-d H:i:s');
            $slaDeadline = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $tailoringCharge = (float)$apt['base_price'];
            $expressFee = ($apt['delivery_preference'] === '24H_EXPRESS') ? (float)$apt['express_price'] : 0.00;
            $measFee = (float)$apt['measurement_fee'];
            $fabCharge = ($fabricSource === 'MY_TAYLOR_FABRIC') ? 899.00 : 0.00;
            $totalAmount = $tailoringCharge + $expressFee + $measFee + $fabCharge;

            $insOrder = $pdo->prepare("
              INSERT INTO `orders` (
                `booking_id`, `customer_id`, `appointment_id`, `measurement_id`, `service_id`,
                `fabric_source`, `fabric_id`, `delivery_address_id`, `priority`, `is_24h_delivery`,
                `tailoring_charge`, `fabric_charge`, `measurement_fee`, `express_fee`, `total_amount`,
                `payment_method`, `payment_status`, `order_status`, `sla_start_time`, `sla_deadline`,
                `assigned_cutting_id`, `assigned_tailor_id`, `assigned_qc_id`, `assigned_packing_id`,
                `assigned_delivery_id`, `special_instructions`
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'EXPRESS', 1, ?, ?, ?, ?, ?, 'UPI', 'PAID', 'FABRIC_READY', ?, ?, 3, 4, 5, 6, 7, ?)
            ");

            $insOrder->execute([
                $bookingId,
                $apt['customer_id'],
                $aptId,
                $measurementId,
                $apt['service_id'],
                $fabricSource,
                ($fabricSource === 'MY_TAYLOR_FABRIC' ? $fabricId : null),
                $apt['address_id'],
                $tailoringCharge,
                $fabCharge,
                $measFee,
                $expressFee,
                $totalAmount,
                $slaStart,
                $slaDeadline,
                $notes
            ]);
            $orderId = $pdo->lastInsertId();

            // Insert initial production task
            $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`, `completed_at`) VALUES (?, 'MEASUREMENT', ?, 'COMPLETED', 'Doorstep measurement taken via Mobile Partner App', NOW(), NOW())")
                ->execute([$orderId, $execId]);

            logAudit($orderId, $aptId, $execId, 'MEASUREMENT_SUBMITTED_MOBILE', 'MEASUREMENT_STARTED', 'MEASUREMENT_COMPLETED', "Doorstep measurement submitted: {$mCode}, Order created: {$bookingId}");

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Measurement recorded and Order created successfully!',
                'measurement_code' => $mCode,
                'measurement_id' => $measurementId,
                'booking_id' => $bookingId,
                'order_id' => $orderId,
                'sla_deadline' => $slaDeadline,
                'total_amount' => $totalAmount
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Failed to save measurement: ' . $e->getMessage()]);
        }
        break;

    case 'get_fabrics':
        $stmt = $pdo->query("SELECT * FROM `fabrics` WHERE `status` != 'out_of_stock' ORDER BY id ASC");
        echo json_encode([
            'status' => 'success',
            'fabrics' => $stmt->fetchAll()
        ]);
        break;

    case 'get_history':
        $execId = (int)($_GET['executive_id'] ?? $inputData['executive_id'] ?? 0);
        $stmt = $pdo->prepare("
          SELECT m.*, u.name as customer_name, u.mobile as customer_mobile, o.booking_id, o.order_status
          FROM `measurements` m
          JOIN `users` u ON m.customer_id = u.id
          LEFT JOIN `orders` o ON o.measurement_id = m.id
          WHERE (? = 0 OR m.created_by_user_id = ?)
          ORDER BY m.id DESC
          LIMIT 50
        ");
        $stmt->execute([$execId, $execId]);
        echo json_encode([
            'status' => 'success',
            'measurements' => $stmt->fetchAll()
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
