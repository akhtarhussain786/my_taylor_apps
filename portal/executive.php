<?php
$pageTitle = "Measurement Executive Field App";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['measurement_executive', 'admin'])) {
    header("Location: " . APP_URL . "/portal/login.php");
    exit;
}

$execId = $currentUser['id'];
$successMsg = null;
$errorMsg = null;

// Handle Appointment Status Change or Measurement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $aptId = (int)($_POST['appointment_id'] ?? 0);

    if ($action === 'update_status') {
        $newStatus = $_POST['new_status'] ?? 'EXECUTIVE_ON_THE_WAY';
        $pdo->prepare("UPDATE `appointments` SET `status` = ?, `executive_id` = ? WHERE `id` = ?")->execute([$newStatus, $execId, $aptId]);
        logAudit(null, $aptId, $execId, 'APPOINTMENT_STATUS_UPDATE', null, $newStatus, "Executive updated status to {$newStatus}");
        $successMsg = "Appointment status updated to {$newStatus}";
    } elseif ($action === 'submit_measurement') {
        $garmentType = $_POST['garment_type'] ?? 'Shirt';
        $fitPref = $_POST['fit_preference'] ?? 'Regular Fit';
        $notes = trim($_POST['measurement_notes'] ?? '');
        $fabricSource = $_POST['fabric_source'] ?? 'MY_TAYLOR_FABRIC';
        $fabricId = !empty($_POST['fabric_id']) ? (int)$_POST['fabric_id'] : 1;

        // Collect measurements as JSON
        $mArray = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'm_') === 0) {
                $mKey = str_replace('m_', '', $k);
                $mArray[$mKey] = (float)$v;
            }
        }

        // Collect design specs as JSON
        $dArray = [
            'collar'  => $_POST['design_collar'] ?? 'Standard Spread',
            'cuff'    => $_POST['design_cuff'] ?? 'Single Button Barrel',
            'pocket'  => $_POST['design_pocket'] ?? 'Single Left Pocket',
            'placket' => $_POST['design_placket'] ?? 'Standard Front'
        ];

        try {
            $pdo->beginTransaction();

            // 1. Get Appointment
            $aptStmt = $pdo->prepare("SELECT a.*, s.base_price, s.express_price, s.measurement_fee FROM `appointments` a JOIN `services` s ON a.service_id = s.id WHERE a.id = ?");
            $aptStmt->execute([$aptId]);
            $apt = $aptStmt->fetch();

            if (!$apt) throw new Exception("Appointment not found");

            // 2. Create Digital Measurement Profile
            $mCode = generateMeasurementCode($pdo);
            $insM = $pdo->prepare("
              INSERT INTO `measurements` (`measurement_code`, `customer_id`, `garment_category`, `measurements_json`, `fit_preference`, `design_specs_json`, `notes`, `created_by_user_id`)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insM->execute([$mCode, $apt['customer_id'], $garmentType, json_encode($mArray), $fitPref, json_encode($dArray), $notes, $execId]);
            $measurementId = $pdo->lastInsertId();

            // 3. Mark Appointment Completed
            $pdo->prepare("UPDATE `appointments` SET `status` = 'MEASUREMENT_COMPLETED', `completed_at` = NOW() WHERE `id` = ?")->execute([$aptId]);

            // 4. Create Confirmed Order with 24H SLA countdown
            $bookingId = generateBookingId($pdo);
            $slaStart = date('Y-m-d H:i:s');
            $slaDeadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $tailoringCharge = $apt['base_price'];
            $expressFee = $apt['delivery_preference'] === '24H_EXPRESS' ? $apt['express_price'] : 0.00;
            $measFee = $apt['measurement_fee'];
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
                $bookingId, $apt['customer_id'], $aptId, $measurementId, $apt['service_id'],
                $fabricSource, $fabricId, $apt['address_id'],
                $tailoringCharge, $fabCharge, $measFee, $expressFee, $totalAmount,
                $slaStart, $slaDeadline, "Doorstep measurement recorded by executive. Fit: {$fitPref}."
            ]);
            $newOrderId = $pdo->lastInsertId();

            // Create Production Tasks
            $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`, `completed_at`) VALUES (?, 'MEASUREMENT', ?, 'COMPLETED', 'Doorstep measurements captured.', NOW(), NOW())")->execute([$newOrderId, $execId]);
            $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`) VALUES (?, 'FABRIC_PREP', 3, 'IN_PROGRESS', 'Fabric issue from mill inventory.', NOW())")->execute([$newOrderId]);

            logAudit($newOrderId, $aptId, $execId, 'MEASUREMENT_RECORDED', 'MEASUREMENT_STARTED', 'FABRIC_READY', "Executive created Order {$bookingId} with Measurement {$mCode}");

            $pdo->commit();
            $successMsg = "Measurement profile {$mCode} created! Order {$bookingId} initialized with 24H SLA countdown!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Failed to record measurement: " . $e->getMessage();
        }
    }
}

// Fetch Appointments for this Executive
$apts = $pdo->query("
  SELECT a.*, s.name as service_name, s.category as service_category,
         u.name as customer_name, u.mobile as customer_mobile,
         addr.house_no, addr.building, addr.street, addr.area, addr.landmark, addr.city, addr.pincode, addr.latitude, addr.longitude
  FROM `appointments` a
  JOIN `services` s ON a.service_id = s.id
  JOIN `users` u ON a.customer_id = u.id
  JOIN `addresses` addr ON a.address_id = addr.id
  ORDER BY a.appointment_date DESC, a.id DESC
")->fetchAll();

$fabrics = $pdo->query("SELECT * FROM `fabrics` WHERE `status` = 'in_stock'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Measurement Executive Portal | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#F8FAFC;">

<!-- Portal Nav -->
<div class="portal-header">
  <div class="container portal-nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="<?= APP_URL ?>/logo-white.png" alt="MY TAYLOR Logo" style="height:42px; width:auto; max-width:160px; object-fit:contain;" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
      <div>
        <span class="portal-role-tag" style="margin-top:2px;">Measurement Specialist</span>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
      <span style="font-size:13px; color:var(--text-light-muted);"><i class="fa-solid fa-user"></i> <?= e($currentUser['name']) ?></span>
      <a href="<?= APP_URL ?>/portal/login.php" class="btn btn-dark btn-sm">Switch Role</a>
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-gold-outline btn-sm"><i class="fa-solid fa-power-off"></i></a>
    </div>
  </div>
</div>

<div class="container" style="padding: 30px 20px 80px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <div>
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Today's Doorstep Appointments</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Navigate to patron destinations, record precision body specs, and dispatch to production.</p>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-bolt"></i> 24H SLA Active</span>
  </div>

  <?php if ($successMsg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:20px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:14px; margin-bottom:20px;">
      <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <!-- Appointments Queue -->
  <div class="grid-2" style="grid-template-columns: 1.1fr 0.9fr; gap:30px;">
    
    <!-- Left: Assigned Appointments List -->
    <div>
      <h3 style="font-size:17px; margin-bottom:14px; color:#0F172A;">Assigned Queue (<?= count($apts) ?>)</h3>
      
      <?php foreach ($apts as $apt): ?>
        <div class="card" style="margin-bottom:18px; border-left:4px solid <?= $apt['status'] === 'MEASUREMENT_COMPLETED' ? 'var(--accent-emerald)' : 'var(--gold-primary)' ?>;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div>
              <span class="badge-status badge-gold"><?= e($apt['appointment_code']) ?></span>
              <h4 style="font-size:17px; color:#0F172A; margin:6px 0 2px;"><?= e($apt['customer_name']) ?></h4>
              <p style="font-size:13px; color:var(--text-muted); margin:0;">
                <i class="fa-solid fa-phone text-gold"></i> <?= e($apt['customer_mobile']) ?>
              </p>
            </div>
            <div style="text-align:right;">
              <span class="badge-status <?= $apt['status'] === 'MEASUREMENT_COMPLETED' ? 'badge-emerald' : 'badge-amber' ?>">
                <?= str_replace('_', ' ', $apt['status']) ?>
              </span>
              <div style="font-size:12px; font-weight:700; color:#0F172A; margin-top:4px;">
                <?= date('d M', strtotime($apt['appointment_date'])) ?> | <?= e($apt['time_slot']) ?>
              </div>
            </div>
          </div>

          <!-- Location & Navigation Details -->
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:10px 12px; border-radius:6px; font-size:13px; margin-bottom:12px;">
            <strong><i class="fa-solid fa-location-dot text-gold"></i> Destination:</strong><br>
            <?= e($apt['house_no']) ?>, <?= e($apt['building']) ?>, <?= e($apt['street']) ?>, <?= e($apt['area']) ?><br>
            Landmark: <?= e($apt['landmark'] ?: 'N/A') ?> • <strong><?= e($apt['city']) ?> (<?= e($apt['pincode']) ?>)</strong>
          </div>

          <!-- Actions Toolbar -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="display:flex; gap:6px;">
              <a href="https://maps.google.com/?q=<?= urlencode($apt['street'] . ', ' . $apt['area'] . ', ' . $apt['city']) ?>" target="_blank" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-diamond-turn-right text-gold"></i> Navigate
              </a>

              <?php if ($apt['status'] === 'BOOKED'): ?>
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                  <input type="hidden" name="new_status" value="EXECUTIVE_ON_THE_WAY">
                  <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-motorcycle"></i> Start Journey</button>
                </form>
              <?php elseif ($apt['status'] === 'EXECUTIVE_ON_THE_WAY'): ?>
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                  <input type="hidden" name="new_status" value="EXECUTIVE_ARRIVED">
                  <button type="submit" class="btn btn-emerald btn-sm"><i class="fa-solid fa-house-chimney"></i> Mark Arrived</button>
                </form>
              <?php elseif ($apt['status'] === 'EXECUTIVE_ARRIVED'): ?>
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                  <input type="hidden" name="new_status" value="MEASUREMENT_STARTED">
                  <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-tape"></i> Start Measurement</button>
                </form>
              <?php endif; ?>
            </div>

            <?php if ($apt['status'] !== 'MEASUREMENT_COMPLETED'): ?>
              <button type="button" class="btn btn-gold-outline btn-sm" onclick="selectForMeasurement(<?= $apt['id'] ?>, '<?= e($apt['customer_name']) ?>', '<?= e($apt['service_name']) ?>')">
                <i class="fa-solid fa-pen-ruler"></i> Enter Specs & Complete
              </button>
            <?php else: ?>
              <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Completed & Dispatched</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Precision Measurement & Style Capture Form -->
    <div>
      <div class="card" style="padding:28px; border-top:4px solid var(--gold-primary); position:sticky; top:80px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="font-size:18px; margin:0; color:#0F172A;"><i class="fa-solid fa-ruler text-gold"></i> Digital Measurement Capture</h3>
          <span class="badge-status badge-gold" id="selected-patron-badge">Selected: APT #1</span>
        </div>

        <form action="" method="POST">
          <input type="hidden" name="action" value="submit_measurement">
          <input type="hidden" name="appointment_id" id="form-apt-id" value="1">

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Garment Category</label>
              <select name="garment_type" class="form-control form-select" id="garment-type-select">
                <option value="Shirt">Bespoke Shirt</option>
                <option value="Trouser">Tailored Trouser</option>
                <option value="Kurta">Ethnic Kurta</option>
                <option value="Blouse">Designer Blouse</option>
                <option value="Suit">Bespoke Suit</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Fit Preference</label>
              <select name="fit_preference" class="form-control form-select">
                <option value="Slim Fit">Slim Fit (Contoured)</option>
                <option value="Regular Fit" selected>Regular Fit (Standard)</option>
                <option value="Comfort Fit">Comfort Fit (Relaxed)</option>
                <option value="Loose Fit">Loose Fit</option>
              </select>
            </div>
          </div>

          <!-- Anatomical Inputs (Inches) -->
          <label class="form-label" style="font-weight:700; color:#0F172A;">Body Measurements (Inches):</label>
          <div class="grid-3" style="margin-bottom:16px;">
            <div>
              <label style="font-size:11px; color:#64748B;">Neck</label>
              <input type="number" step="0.25" name="m_neck" class="form-control" value="16.0" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Shoulder</label>
              <input type="number" step="0.25" name="m_shoulder" class="form-control" value="18.5" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Chest / Bust</label>
              <input type="number" step="0.25" name="m_chest" class="form-control" value="40.0" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Waist</label>
              <input type="number" step="0.25" name="m_waist" class="form-control" value="34.0" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Hip</label>
              <input type="number" step="0.25" name="m_hip" class="form-control" value="41.0" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Sleeve Length</label>
              <input type="number" step="0.25" name="m_sleeve_length" class="form-control" value="25.5" required>
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Bicep</label>
              <input type="number" step="0.25" name="m_bicep" class="form-control" value="14.5">
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Wrist</label>
              <input type="number" step="0.25" name="m_wrist" class="form-control" value="7.5">
            </div>
            <div>
              <label style="font-size:11px; color:#64748B;">Total Length</label>
              <input type="number" step="0.25" name="m_shirt_length" class="form-control" value="30.0" required>
            </div>
          </div>

          <!-- Styling & Fabric Options -->
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Collar / Neck Style</label>
              <select name="design_collar" class="form-control form-select">
                <option value="Semi-Cutaway">Semi-Cutaway</option>
                <option value="Classic Spread">Classic Spread</option>
                <option value="Mandarin / Band Collar">Mandarin / Band</option>
                <option value="Button-Down">Button-Down</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Cuff Style</label>
              <select name="design_cuff" class="form-control form-select">
                <option value="French Cuff (Double)">French Cuff (Double)</option>
                <option value="Single Button Barrel">Single Button Barrel</option>
                <option value="Convertible Dual Cuff">Convertible Dual Cuff</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Fabric Sourcing</label>
            <select name="fabric_source" class="form-control form-select">
              <option value="MY_TAYLOR_FABRIC">My Taylor Mill Inventory (Egyptian Giza 100s)</option>
              <option value="CUSTOMER_PROVIDED">Customer Provided Fabric (Collected On-Spot)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Special Tailoring Notes</label>
            <textarea name="measurement_notes" rows="2" class="form-control" placeholder="e.g. Extra watch room on left wrist, high armhole..."></textarea>
          </div>

          <button type="submit" class="btn btn-gold btn-block btn-lg">
            <i class="fa-solid fa-clipboard-check"></i> Save Specs & Start 24H Production
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function selectForMeasurement(aptId, customerName, serviceName) {
  document.getElementById('form-apt-id').value = aptId;
  document.getElementById('selected-patron-badge').innerText = `APT #${aptId} - ${customerName}`;
  window.scrollTo({ top: 100, behavior: 'smooth' });
}
</script>

</body>
</html>
