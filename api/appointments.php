<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';

$pdo = getDbConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? 'slots';

if ($action === 'slots') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $standardSlots = [
        "08:00 AM – 09:00 AM",
        "09:00 AM – 10:00 AM",
        "10:00 AM – 11:00 AM",
        "11:00 AM – 12:00 PM",
        "12:00 PM – 01:00 PM",
        "02:00 PM – 03:00 PM",
        "03:00 PM – 04:00 PM",
        "04:00 PM – 05:00 PM",
        "05:00 PM – 06:00 PM",
        "06:00 PM – 07:00 PM"
    ];

    $stmt = $pdo->prepare("SELECT `time_slot`, COUNT(*) as booked FROM `appointments` WHERE `appointment_date` = ? AND `status` != 'CANCELLED' GROUP BY `time_slot`");
    $stmt->execute([$date]);
    $bookedMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $result = [];
    foreach ($standardSlots as $s) {
        $count = $bookedMap[$s] ?? 0;
        $result[] = [
            'slot'      => $s,
            'available' => ($count < 3),
            'booked'    => (int)$count
        ];
    }

    echo json_encode(['status' => 'success', 'date' => $date, 'slots' => $result]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
