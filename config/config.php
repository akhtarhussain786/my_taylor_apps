<?php
/**
 * MY TAYLOR - Global Application Configuration & Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// App Constants
define('APP_NAME', 'MY TAYLOR');
define('APP_TAGLINE', 'Tailored for You. Delivered in 24 Hours.');
define('APP_SUB_PROMISE', 'Doorstep Measurement • Premium Tailoring • Live Tracking • 24H Delivery');
// Dynamic App URL detection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    define('APP_URL', $protocol . $host . '/my%20talor');
} else {
    // Live Server (e.g. https://mytaylor.in)
    define('APP_URL', $protocol . $host);
}
define('CURRENCY_SYMBOL', '₹');

// PHP 7/8 Backward Compatibility Polyfills
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/database.php';

// Helper: Format Currency
function formatPrice($amount) {
    return '₹' . number_format((float)$amount, 2);
}

// Helper: Sanitize string output
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Helper: Generate Unique Booking ID (e.g. MYT-20260920-001245)
function generateBookingId($pdo) {
    $datePart = date('Ymd');
    $prefix = "MYT-{$datePart}-";
    $stmt = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `booking_id` LIKE '{$prefix}%'");
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf("MYT-%s-%06d", $datePart, $count + 1245);
}

// Helper: Generate Unique Measurement ID (e.g. MT-M-0001028)
function generateMeasurementCode($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `measurements`");
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf("MT-M-%07d", $count + 1030);
}

// Helper: Generate Unique Appointment ID (e.g. APT-20260920-001)
function generateAppointmentCode($pdo) {
    $datePart = date('Ymd');
    $prefix = "APT-{$datePart}-";
    $stmt = $pdo->query("SELECT COUNT(*) FROM `appointments` WHERE `appointment_code` LIKE '{$prefix}%'");
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf("APT-%s-%03d", $datePart, $count);
}

// Helper: Log Action in System Audit Trail
function logAudit($orderId, $appointmentId, $userId, $action, $prevState, $newState, $description) {
    try {
        $pdo = getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`order_id`, `appointment_id`, `user_id`, `action`, `previous_state`, `new_state`, `description`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $appointmentId, $userId, $action, $prevState, $newState, $description, $ip]);
    } catch (Exception $e) {
        // Silent catch for audit
    }
}

// Order Status Pipeline Order and Pretty Labels
function getOrderStages() {
    return [
        'ORDER_CREATED'         => ['label' => 'Order Placed', 'step' => 1, 'badge' => 'badge-slate'],
        'MEASUREMENT_COMPLETED' => ['label' => 'Measurement Taken', 'step' => 2, 'badge' => 'badge-blue'],
        'ORDER_CONFIRMED'       => ['label' => 'Order Confirmed', 'step' => 3, 'badge' => 'badge-blue'],
        'FABRIC_READY'          => ['label' => 'Fabric Prepared', 'step' => 4, 'badge' => 'badge-indigo'],
        'CUTTING_IN_PROGRESS'   => ['label' => 'Cutting In Progress', 'step' => 5, 'badge' => 'badge-amber'],
        'CUTTING_COMPLETED'     => ['label' => 'Cutting Completed', 'step' => 5, 'badge' => 'badge-amber'],
        'STITCHING_IN_PROGRESS' => ['label' => 'Stitching In Progress', 'step' => 6, 'badge' => 'badge-amber'],
        'STITCHING_COMPLETED'   => ['label' => 'Stitching Completed', 'step' => 6, 'badge' => 'badge-teal'],
        'FINISHING'             => ['label' => 'Finishing & Pressing', 'step' => 7, 'badge' => 'badge-teal'],
        'QUALITY_CHECK'         => ['label' => 'Quality Inspection', 'step' => 8, 'badge' => 'badge-purple'],
        'QC_PASSED'             => ['label' => 'QC Passed', 'step' => 8, 'badge' => 'badge-emerald'],
        'PACKING'               => ['label' => 'Packaging & QR Label', 'step' => 9, 'badge' => 'badge-cyan'],
        'READY_FOR_DISPATCH'    => ['label' => 'Ready for Dispatch', 'step' => 9, 'badge' => 'badge-cyan'],
        'OUT_FOR_DELIVERY'      => ['label' => 'Out for Delivery', 'step' => 10, 'badge' => 'badge-gold'],
        'DELIVERED'             => ['label' => 'Delivered', 'step' => 11, 'badge' => 'badge-emerald'],
        'REWORK'                => ['label' => 'QC Rework', 'step' => 6, 'badge' => 'badge-rose'],
        'CANCELLED'             => ['label' => 'Cancelled', 'step' => 0, 'badge' => 'badge-rose'],
    ];
}
