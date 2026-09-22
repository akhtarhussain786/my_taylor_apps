<?php
$pageTitle = "Quality Control (QC) Master Inspection";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['qc_staff', 'admin'])) {
    $demo = $pdo->query("SELECT * FROM `users` WHERE `role` = 'qc_staff' LIMIT 1")->fetch();
    if ($demo) loginUser($demo);
    $currentUser = getCurrentUser();
}

$qcId = $currentUser['id'];
$successMsg = null;

// Handle QC Result Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $result = $_POST['result'] ?? 'PASS';
    $reworkReason = trim($_POST['rework_reason'] ?? '');
    $reworkTarget = $_POST['rework_target'] ?? 'STITCHING';
    $comments = trim($_POST['comments'] ?? '');

    $checklist = [
        'measurement_accuracy' => isset($_POST['chk_meas']),
        'stitch_quality'       => isset($_POST['chk_stitch']),
        'fabric_condition'     => isset($_POST['chk_fabric']),
        'thread_finishing'     => isset($_POST['chk_thread']),
        'button_zip'           => isset($_POST['chk_button']),
        'symmetry'             => isset($_POST['chk_symm']),
        'collar_alignment'     => isset($_POST['chk_collar']),
        'sleeve_alignment'     => isset($_POST['chk_sleeve']),
        'ironing_press'        => isset($_POST['chk_iron']),
        'design_match'         => isset($_POST['chk_design'])
    ];

    $insQC = $pdo->prepare("
      INSERT INTO `qc_logs` (`order_id`, `inspector_id`, `checklist_json`, `result`, `rework_reason`, `rework_target_stage`, `comments`)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insQC->execute([$orderId, $qcId, json_encode($checklist), $result, $reworkReason, $reworkTarget, $comments]);

    if ($result === 'PASS') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'PACKING', `assigned_packing_id` = 6 WHERE `id` = ?")->execute([$orderId]);
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`, `completed_at`) VALUES (?, 'QC', ?, 'COMPLETED', '10-Point Master QC Passed.', NOW(), NOW())")->execute([$orderId, $qcId]);
        logAudit($orderId, null, $qcId, 'QC_PASSED', 'QUALITY_CHECK', 'PACKING', 'Garment passed 10-point QC. Transferred to Packing.');
        $successMsg = "Order #{$orderId} PASSED 10-Point QC! Sent to Packaging department.";
    } else {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'REWORK' WHERE `id` = ?")->execute([$orderId]);
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`) VALUES (?, 'QC', ?, 'REWORK', ?, NOW())")->execute([$orderId, $qcId, "Rework: {$reworkReason}"]);
        logAudit($orderId, null, $qcId, 'QC_REWORK', 'QUALITY_CHECK', 'REWORK', "QC Failed: {$reworkReason}");
        $successMsg = "Order #{$orderId} sent back for REWORK ({$reworkReason}).";
    }
}

// Fetch Orders Pending QC
$orders = $pdo->query("
  SELECT o.*, s.name as service_name, s.category as service_category,
         m.measurement_code, m.measurements_json, m.fit_preference,
         u.name as customer_name, u.mobile as customer_mobile
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  LEFT JOIN `measurements` m ON o.measurement_id = m.id
  WHERE o.order_status IN ('QUALITY_CHECK', 'STITCHING_COMPLETED', 'FINISHING')
  ORDER BY o.priority = 'EXPRESS' DESC, o.sla_deadline ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quality Control (QC) Master Inspection | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#F8FAFC;">

<!-- Portal Nav -->
<div class="portal-header">
  <div class="container portal-nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="<?= APP_URL ?>/logo.jpeg" alt="Logo" style="height:38px; border-radius:6px; border:1px solid var(--gold-primary);">
      <div>
        <h3 style="font-size:16px; margin:0; color:#FFFFFF;">MY TAYLOR QC Atelier</h3>
        <span class="portal-role-tag">10-Point Master QC Lead</span>
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
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Quality Control Inspection Station</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Inspect finished garments against the 10-Point Quality Checklist before dispatch clearance.</p>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-award"></i> Pending Inspection: <?= count($orders) ?></span>
  </div>

  <?php if ($successMsg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:20px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <div class="card" style="text-align:center; padding:40px;">
      <i class="fa-solid fa-circle-check" style="font-size:40px; color:var(--accent-emerald); margin-bottom:12px;"></i>
      <h3 style="font-size:18px;">All Finished Garments Inspected!</h3>
      <p style="color:var(--text-muted);">No garments currently waiting in the QC inspection queue.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($orders as $o): ?>
    <div class="card" style="margin-bottom:28px; border-top:4px solid var(--gold-primary);">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; border-bottom:1px solid #E2E8F0; padding-bottom:12px;">
        <div>
          <span class="badge-status badge-gold"><?= e($o['booking_id']) ?></span>
          <h3 style="font-size:20px; color:#0F172A; margin:6px 0 2px;"><?= e($o['service_name']) ?></h3>
          <span style="font-size:13px; color:var(--text-muted);">Patron: <?= e($o['customer_name']) ?> (<?= e($o['customer_mobile']) ?>)</span>
        </div>
        <div style="text-align:right;">
          <span class="badge-24h pulse"><i class="fa-solid fa-bolt"></i> 24H EXPRESS SLA</span>
          <div style="font-size:12px; color:#B45309; font-weight:700; margin-top:4px;">
            Target Delivery: <?= date('d M, h:i A', strtotime($o['sla_deadline'] ?? '+24 hours')) ?>
          </div>
        </div>
      </div>

      <!-- 10-Point Checklist Form -->
      <form action="" method="POST">
        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">

        <h4 style="font-size:15px; color:#0F172A; margin-bottom:12px;"><i class="fa-solid fa-list-check text-gold"></i> 10-Point Master Checklist Verification:</h4>

        <div class="grid-2" style="background:#F8FAFC; padding:18px; border-radius:8px; border:1px solid #E2E8F0; margin-bottom:18px;">
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_meas" checked> 1. Measurement accuracy matches Digital Profile
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_stitch" checked> 2. Seam strength & stitch density (18 SPI)
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_fabric" checked> 3. Fabric pristine & zero surface defects
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_thread" checked> 4. Thread finishing & loose end trimmed
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_button" checked> 5. Button reinforcement & buttonhole check
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_symm" checked> 6. Overall symmetry and posture balance
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_collar" checked> 7. Collar crispness & roll alignment
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_sleeve" checked> 8. Sleeve cuff symmetry & pleat finish
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_iron" checked> 9. Bespoke steam press & shape retention
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
            <input type="checkbox" name="chk_design" checked> 10. Design exact match with customer instructions
          </label>
        </div>

        <div class="grid-2" style="margin-bottom:18px;">
          <div class="form-group">
            <label class="form-label">Inspection Verdict</label>
            <select name="result" class="form-control form-select" id="qc-verdict-<?= $o['id'] ?>" onchange="toggleReworkFields(<?= $o['id'] ?>)">
              <option value="PASS">PASS — Verified & Ready for Packaging</option>
              <option value="REWORK_REQUIRED">REWORK REQUIRED — Send Back to Workshop</option>
            </select>
          </div>
          <div class="form-group" id="rework-fields-<?= $o['id'] ?>" style="display:none;">
            <label class="form-label">Rework Reason & Target Stage</label>
            <input type="text" name="rework_reason" class="form-control" placeholder="e.g. Collar alignment off by 0.5 inch, re-stitch left sleeve...">
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
          <button type="submit" class="btn btn-emerald btn-lg">
            <i class="fa-solid fa-stamp"></i> Submit Master QC Verdict
          </button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<script>
function toggleReworkFields(orderId) {
  const select = document.getElementById(`qc-verdict-${orderId}`);
  const reworkDiv = document.getElementById(`rework-fields-${orderId}`);
  if (select.value === 'REWORK_REQUIRED') {
    reworkDiv.style.display = 'block';
  } else {
    reworkDiv.style.display = 'none';
  }
}
</script>

</body>
</html>
