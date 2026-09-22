<?php
$pageTitle = "Master Cutting Workshop Module";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['cutting_staff', 'admin'])) {
    header("Location: " . APP_URL . "/portal/login.php");
    exit;
}

$staffId = $currentUser['id'];
$successMsg = null;

// Handle Start / Complete Cutting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'start_cutting') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'CUTTING_IN_PROGRESS', `assigned_cutting_id` = ? WHERE `id` = ?")->execute([$staffId, $orderId]);
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`) VALUES (?, 'CUTTING', ?, 'IN_PROGRESS', 'Pattern shears in progress.', NOW())")->execute([$orderId, $staffId]);
        logAudit($orderId, null, $staffId, 'CUTTING_STARTED', 'FABRIC_READY', 'CUTTING_IN_PROGRESS', 'Master cutter started pattern shearing');
        $successMsg = "Order #{$orderId} marked: CUTTING IN PROGRESS";
    } elseif ($action === 'complete_cutting') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'CUTTING_COMPLETED', `assigned_tailor_id` = 4 WHERE `id` = ?")->execute([$orderId]);
        $pdo->prepare("UPDATE `production_tasks` SET `status` = 'COMPLETED', `completed_at` = NOW() WHERE `order_id` = ? AND `stage` = 'CUTTING'")->execute([$orderId]);
        logAudit($orderId, null, $staffId, 'CUTTING_COMPLETED', 'CUTTING_IN_PROGRESS', 'CUTTING_COMPLETED', 'Pattern cutting finished. Handed to Tailor Master Anwar');
        $successMsg = "Order #{$orderId} Cutting Completed! Transferred to Tailoring Workshop.";
    }
}

// Fetch Cutting Orders
$orders = $pdo->query("
  SELECT o.*, s.name as service_name, s.category as service_category,
         f.name as fabric_name, f.color as fabric_color,
         m.measurement_code, m.measurements_json, m.fit_preference, m.design_specs_json, m.notes as measurement_notes,
         u.name as customer_name, u.mobile as customer_mobile
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  LEFT JOIN `fabrics` f ON o.fabric_id = f.id
  LEFT JOIN `measurements` m ON o.measurement_id = m.id
  WHERE o.order_status IN ('ORDER_CONFIRMED', 'FABRIC_READY', 'CUTTING_ASSIGNED', 'CUTTING_IN_PROGRESS', 'CUTTING_COMPLETED', 'REWORK')
  ORDER BY o.priority = 'EXPRESS' DESC, o.sla_deadline ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Master Cutting Module | MY TAYLOR</title>
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
        <h3 style="font-size:16px; margin:0; color:#FFFFFF;">MY TAYLOR Production Hub</h3>
        <span class="portal-role-tag">Master Cutting Workshop</span>
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
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Pattern Cutting Queue</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Inspect anatomical measurements, align grain lines, and execute precision cuts.</p>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-scissors"></i> Active Queue: <?= count($orders) ?></span>
  </div>

  <?php if ($successMsg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:20px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="gap:24px;">
    <?php foreach ($orders as $o): ?>
      <?php 
        $mJson = json_decode($o['measurements_json'], true) ?: []; 
        $dJson = json_decode($o['design_specs_json'], true) ?: [];
      ?>
      <div class="card" style="border-top:4px solid var(--gold-primary);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
          <div>
            <div style="display:flex; gap:8px; align-items:center;">
              <span class="badge-status badge-gold"><?= e($o['booking_id']) ?></span>
              <span class="badge-24h pulse" style="font-size:10px;"><i class="fa-solid fa-bolt"></i> 24H EXPRESS</span>
            </div>
            <h3 style="font-size:18px; color:#0F172A; margin:6px 0 2px;"><?= e($o['service_name']) ?></h3>
            <span style="font-size:12px; color:var(--text-muted);">Patron: <?= e($o['customer_name']) ?> (<?= e($o['customer_mobile']) ?>)</span>
          </div>
          <span class="badge-status badge-blue"><?= str_replace('_', ' ', $o['order_status']) ?></span>
        </div>

        <!-- Fabric & Sourcing -->
        <div style="background:#F1F5F9; border:1px solid #E2E8F0; padding:10px 12px; border-radius:6px; font-size:13px; margin-bottom:14px;">
          <strong style="color:#0F172A;"><i class="fa-solid fa-layer-group text-gold"></i> Fabric Specification:</strong><br>
          <?= e($o['fabric_name'] ? $o['fabric_name'] . ' • ' . $o['fabric_color'] : 'Customer Provided Fabric (Verified)') ?><br>
          Fit Style: <strong style="color:var(--accent-emerald);"><?= e($o['fit_preference'] ?: 'Regular Fit') ?></strong>
        </div>

        <!-- Measurement Specs -->
        <div style="margin-bottom:14px;">
          <strong style="font-size:13px; color:#475569;">Pattern Dimensions (Inches):</strong>
          <div class="measurement-spec-grid" style="margin:8px 0;">
            <?php foreach ($mJson as $mk => $mv): ?>
              <div class="spec-chip" style="padding:4px 8px;">
                <div class="name" style="font-size:10px;"><?= str_replace('_', ' ', $mk) ?></div>
                <div class="val" style="font-size:14px;"><?= e($mv) ?>"</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Design Specs -->
        <?php if (!empty($dJson)): ?>
          <div style="font-size:12px; color:#475569; background:#F8FAFC; padding:8px 10px; border-radius:6px; margin-bottom:16px; border:1px solid #E2E8F0;">
            <strong>Pattern Features:</strong>
            <?php foreach ($dJson as $dk => $dv): ?>
              <span style="margin-right:8px;"><?= ucfirst($dk) ?>: <em><?= e($dv) ?></em></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:14px;">
          <span style="font-size:12px; color:#64748B;">SLA Deadline: <?= date('d M, h:i A', strtotime($o['sla_deadline'] ?? '+24 hours')) ?></span>
          
          <div style="display:flex; gap:8px;">
            <?php if ($o['order_status'] !== 'CUTTING_IN_PROGRESS' && $o['order_status'] !== 'CUTTING_COMPLETED'): ?>
              <form action="" method="POST">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <input type="hidden" name="action" value="start_cutting">
                <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-scissors"></i> Start Shearing</button>
              </form>
            <?php elseif ($o['order_status'] === 'CUTTING_IN_PROGRESS'): ?>
              <form action="" method="POST">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <input type="hidden" name="action" value="complete_cutting">
                <button type="submit" class="btn btn-emerald btn-sm"><i class="fa-solid fa-check"></i> Complete & Handover to Tailor</button>
              </form>
            <?php else: ?>
              <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Handed to Stitching</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
