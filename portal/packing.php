<?php
$pageTitle = "Packaging & QR Dispatch Center";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['role'], ['packing_staff', 'admin'])) {
    header("Location: " . APP_URL . "/portal/login.php");
    exit;
}

$packId = $currentUser['id'];
$successMsg = null;

// Handle Mark Ready for Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_dispatched') {
        $pdo->prepare("UPDATE `orders` SET `order_status` = 'READY_FOR_DISPATCH', `assigned_delivery_id` = 7 WHERE `id` = ?")->execute([$orderId]);
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`, `completed_at`) VALUES (?, 'PACKING', ?, 'COMPLETED', 'Garment folded, packaged in suit cover & QR tag applied.', NOW(), NOW())")->execute([$orderId, $packId]);
        logAudit($orderId, null, $packId, 'PACKED_AND_LABELED', 'PACKING', 'READY_FOR_DISPATCH', 'Garment packaged with QR label. Ready for courier pickup.');
        $successMsg = "Order #{$orderId} PACKED & SEALED! Transferred to Express Delivery Rider.";
    }
}

// Fetch Packing Queue
$orders = $pdo->query("
  SELECT o.*, s.name as service_name,
         u.name as customer_name, u.mobile as customer_mobile,
         a.house_no, a.building, a.street, a.area, a.city, a.pincode
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  JOIN `addresses` a ON o.delivery_address_id = a.id
  WHERE o.order_status IN ('PACKING', 'QC_PASSED', 'READY_FOR_DISPATCH')
  ORDER BY o.priority = 'EXPRESS' DESC, o.sla_deadline ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Packaging & Dispatch Center | MY TAYLOR</title>
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
        <h3 style="font-size:16px; margin:0; color:#FFFFFF;">MY TAYLOR Dispatch Hub</h3>
        <span class="portal-role-tag">Packaging & Labeling Specialist</span>
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
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Bespoke Packaging & Docket Dispatch</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Fold finished garments into luxury suit covers, generate QR Barcode shipping tags, and seal.</p>
    </div>
    <span class="badge-status badge-gold"><i class="fa-solid fa-box-open"></i> Packing Station</span>
  </div>

  <?php if ($successMsg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:20px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="gap:24px;">
    <?php foreach ($orders as $o): ?>
      <div class="card" style="border-top:4px solid var(--gold-primary);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
          <div>
            <span class="badge-status badge-gold"><?= e($o['booking_id']) ?></span>
            <h3 style="font-size:18px; color:#0F172A; margin:6px 0 2px;"><?= e($o['service_name']) ?></h3>
            <span style="font-size:13px; color:var(--text-muted);"><?= e($o['customer_name']) ?> (<?= e($o['customer_mobile']) ?>)</span>
          </div>
          <span class="badge-status badge-cyan"><?= str_replace('_', ' ', $o['order_status']) ?></span>
        </div>

        <!-- Packaging Shipping Label Preview -->
        <div style="background:#FFFFFF; border:2px dashed #0B132B; padding:16px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
              <img src="<?= APP_URL ?>/logo.jpeg" alt="Logo" style="height:24px; border-radius:4px;">
              <strong style="font-size:13px; color:#0B132B;">MY TAYLOR EXPRESS</strong>
            </div>
            <div style="font-size:12px; color:#334155; line-height:1.4;">
              <strong>DOCKET: <?= e($o['booking_id']) ?></strong><br>
              To: <?= e($o['customer_name']) ?><br>
              <?= e($o['house_no']) ?>, <?= e($o['street']) ?>, <?= e($o['area']) ?><br>
              <?= e($o['city']) ?> - <strong><?= e($o['pincode']) ?></strong>
            </div>
          </div>
          <div style="text-align:center; padding:8px; background:#F8FAFC; border:1px solid #CBD5E1; border-radius:6px;">
            <i class="fa-solid fa-qrcode" style="font-size:42px; color:#0B132B;"></i>
            <div style="font-size:9px; font-weight:700; margin-top:2px;">EXPRESS SCAN</div>
          </div>
        </div>

        <!-- Action -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:14px;">
          <span style="font-size:12px; color:#B45309; font-weight:700;"><i class="fa-solid fa-bolt text-gold"></i> 24-Hour Express Queue</span>

          <?php if ($o['order_status'] !== 'READY_FOR_DISPATCH'): ?>
            <form action="" method="POST">
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <input type="hidden" name="action" value="mark_dispatched">
              <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-truck-ramp-box"></i> Apply Label & Mark Ready for Dispatch</button>
            </form>
          <?php else: ?>
            <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Ready for Delivery Pickup</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
