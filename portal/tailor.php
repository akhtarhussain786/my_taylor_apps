<?php
$pageTitle = "Master Tailor Workshop Module";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['tailor', 'admin'])) {
    $demo = $pdo->query("SELECT * FROM `users` WHERE `role` = 'tailor' LIMIT 1")->fetch();
    if ($demo) loginUser($demo);
    $currentUser = getCurrentUser();
}

$tailorId = $currentUser['id'];
$successMsg = null;

// Handle Start / Complete Stitching
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'start_stitching') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'STITCHING_IN_PROGRESS', `assigned_tailor_id` = ? WHERE `id` = ?")->execute([$tailorId, $orderId]);
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`) VALUES (?, 'STITCHING', ?, 'IN_PROGRESS', 'Garment assembly in progress.', NOW())")->execute([$orderId, $tailorId]);
        logAudit($orderId, null, $tailorId, 'STITCHING_STARTED', 'CUTTING_COMPLETED', 'STITCHING_IN_PROGRESS', 'Master tailor started stitching assembly');
        $successMsg = "Order #{$orderId} marked: STITCHING IN PROGRESS";
    } elseif ($action === 'complete_stitching') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'QUALITY_CHECK', `assigned_qc_id` = 5 WHERE `id` = ?")->execute([$orderId]);
        $pdo->prepare("UPDATE `production_tasks` SET `status` = 'COMPLETED', `completed_at` = NOW() WHERE `order_id` = ? AND `stage` = 'STITCHING'")->execute([$orderId]);
        logAudit($orderId, null, $tailorId, 'STITCHING_COMPLETED', 'STITCHING_IN_PROGRESS', 'QUALITY_CHECK', 'Tailoring completed. Handed to QC Inspector Meera');
        $successMsg = "Order #{$orderId} Stitching Completed! Handed over to QC Inspection.";
    }
}

// Fetch Tailoring Queue
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
  WHERE o.order_status IN ('CUTTING_COMPLETED', 'STITCHING_ASSIGNED', 'STITCHING_IN_PROGRESS', 'STITCHING_COMPLETED', 'FINISHING', 'REWORK')
  ORDER BY o.priority = 'EXPRESS' DESC, o.sla_deadline ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Master Tailor Workshop | MY TAYLOR</title>
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
        <h3 style="font-size:16px; margin:0; color:#FFFFFF;">MY TAYLOR Workshop</h3>
        <span class="portal-role-tag">Master Tailoring Atelier</span>
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
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Stitching & Assembly Queue</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Artisanal tailoring, seam reinforcement, and luxury styling according to digital specs.</p>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-shirt"></i> Tailoring Queue: <?= count($orders) ?></span>
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
            <span style="font-size:12px; color:var(--text-muted);">Patron: <?= e($o['customer_name']) ?> • Fit: <strong><?= e($o['fit_preference']) ?></strong></span>
          </div>
          <span class="badge-status <?= $o['order_status'] === 'STITCHING_IN_PROGRESS' ? 'badge-amber' : 'badge-teal' ?>">
            <?= str_replace('_', ' ', $o['order_status']) ?>
          </span>
        </div>

        <!-- Custom Styling Specifications -->
        <div style="background:#0B132B; color:#FFFFFF; padding:12px 14px; border-radius:6px; font-size:13px; margin-bottom:14px; border:1px solid var(--border-dark);">
          <strong style="color:var(--gold-primary);"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom Tailor Specifications:</strong>
          <div style="margin-top:6px; display:grid; grid-template-columns:1fr 1fr; gap:6px; font-size:12px;">
            <?php foreach ($dJson as $dk => $dv): ?>
              <div><?= ucfirst($dk) ?>: <strong style="color:#FFFFFF;"><?= e($dv) ?></strong></div>
            <?php endforeach; ?>
          </div>
          <?php if (!empty($o['special_instructions'])): ?>
            <div style="margin-top:8px; padding-top:6px; border-top:1px solid rgba(255,255,255,0.1); color:var(--gold-light); font-style:italic;">
              "<?= e($o['special_instructions']) ?>"
            </div>
          <?php endif; ?>
        </div>

        <!-- Anatomical Measurements -->
        <div style="margin-bottom:14px;">
          <strong style="font-size:13px; color:#475569;">Precision Stitch Guide (Inches):</strong>
          <div class="measurement-spec-grid" style="margin:8px 0;">
            <?php foreach ($mJson as $mk => $mv): ?>
              <div class="spec-chip" style="padding:4px 8px;">
                <div class="name" style="font-size:10px;"><?= str_replace('_', ' ', $mk) ?></div>
                <div class="val" style="font-size:14px; color:#0F172A;"><?= e($mv) ?>"</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:14px;">
          <span style="font-size:12px; color:#B45309; font-weight:700;">
            <i class="fa-solid fa-clock text-gold"></i> Deadline: <?= date('d M, h:i A', strtotime($o['sla_deadline'] ?? '+24 hours')) ?>
          </span>

          <div style="display:flex; gap:8px;">
            <?php if ($o['order_status'] !== 'STITCHING_IN_PROGRESS' && $o['order_status'] !== 'QUALITY_CHECK'): ?>
              <form action="" method="POST">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <input type="hidden" name="action" value="start_stitching">
                <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-play"></i> Start Stitching</button>
              </form>
            <?php elseif ($o['order_status'] === 'STITCHING_IN_PROGRESS'): ?>
              <form action="" method="POST">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <input type="hidden" name="action" value="complete_stitching">
                <button type="submit" class="btn btn-emerald btn-sm"><i class="fa-solid fa-check-double"></i> Complete & Send to QC</button>
              </form>
            <?php else: ?>
              <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Handed to QC</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
