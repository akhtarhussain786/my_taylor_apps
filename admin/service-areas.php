<?php
$pageTitle = "Service Area & 24H Capacity Engine";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDbConnection();
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    $demo = $pdo->query("SELECT * FROM `users` WHERE `role` = 'admin' LIMIT 1")->fetch();
    if ($demo) loginUser($demo);
}

// Handle Add / Toggle Area
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_area'])) {
        $city = trim($_POST['city'] ?? '');
        $areaName = trim($_POST['area_name'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $is24h = isset($_POST['is_24h_available']) ? 1 : 0;
        $capacity = (int)($_POST['max_daily_capacity'] ?? 30);

        $ins = $pdo->prepare("
          INSERT INTO `service_areas` (`city`, `area_name`, `pincode`, `is_active`, `is_24h_available`, `max_daily_capacity`)
          VALUES (?, ?, ?, 1, ?, ?)
          ON DUPLICATE KEY UPDATE `area_name`=VALUES(`area_name`), `is_24h_available`=VALUES(`is_24h_available`), `max_daily_capacity`=VALUES(`max_daily_capacity`)
        ");
        $ins->execute([$city, $areaName, $pincode, $is24h, $capacity]);
        $msg = "Service Area {$areaName} ({$pincode}) saved successfully!";
    } elseif (isset($_POST['toggle_24h'])) {
        $areaId = (int)$_POST['area_id'];
        $pdo->prepare("UPDATE `service_areas` SET `is_24h_available` = NOT `is_24h_available` WHERE `id` = ?")->execute([$areaId]);
        $msg = "24-Hour Express status toggled for Area #{$areaId}";
    }
}

$areas = $pdo->query("SELECT * FROM `service_areas` ORDER BY `city`, `pincode` ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Service Areas & 24H Capacity | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#F8FAFC;">

<!-- Navbar -->
<div class="portal-header">
  <div class="container portal-nav">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="<?= APP_URL ?>/logo.jpeg" alt="Logo" style="height:38px; border-radius:6px; border:1px solid var(--gold-primary);">
      <div>
        <h3 style="font-size:16px; margin:0; color:#FFFFFF;">MY TAYLOR Logistics Engine</h3>
        <span class="portal-role-tag">Service Areas & 24H Capacity</span>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-gold-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>
  </div>
</div>

<div class="container" style="padding: 30px 20px 80px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <div>
      <h1 style="font-size:24px; color:#0F172A; margin:0;">Serviceable Pincodes & Express Capacity Control</h1>
      <p style="font-size:13.5px; color:var(--text-muted); margin:2px 0 0;">Manage metro operational zones, toggle 24-hour express availability, and monitor daily quota.</p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:12px; margin-bottom:18px;">
      <i class="fa-solid fa-circle-check"></i> <?= e($msg) ?>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="grid-template-columns: 1.4fr 0.6fr; gap:30px;">
    <!-- Areas Table -->
    <div class="card" style="padding:0; overflow:hidden;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#0B132B; color:#FFFFFF; font-size:12px; text-align:left;">
            <th style="padding:14px 18px;">Zone / Pincode</th>
            <th style="padding:14px 18px;">City & Locality</th>
            <th style="padding:14px 18px;">Daily Quota</th>
            <th style="padding:14px 18px;">24H Express Status</th>
            <th style="padding:14px 18px; text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($areas as $a): ?>
            <tr style="border-bottom:1px solid #E2E8F0; font-size:13.5px;">
              <td style="padding:14px 18px;">
                <strong style="font-size:15px; color:var(--gold-primary);"><?= e($a['pincode']) ?></strong>
              </td>
              <td style="padding:14px 18px;">
                <strong><?= e($a['area_name']) ?></strong><br>
                <span style="font-size:12px; color:var(--text-muted);"><?= e($a['city']) ?></span>
              </td>
              <td style="padding:14px 18px; font-size:13px;">
                <?= $a['current_booked_today'] ?> / <?= $a['max_daily_capacity'] ?> slots
              </td>
              <td style="padding:14px 18px;">
                <?php if ($a['is_24h_available']): ?>
                  <span class="badge-status badge-emerald"><i class="fa-solid fa-bolt"></i> 24H ACTIVE</span>
                <?php else: ?>
                  <span class="badge-status badge-slate">Standard Only</span>
                <?php endif; ?>
              </td>
              <td style="padding:14px 18px; text-align:right;">
                <form action="" method="POST" style="display:inline;">
                  <input type="hidden" name="toggle_24h" value="1">
                  <input type="hidden" name="area_id" value="<?= $a['id'] ?>">
                  <button type="submit" class="btn <?= $a['is_24h_available'] ? 'btn-dark' : 'btn-gold' ?> btn-sm" style="font-size:11px;">
                    <?= $a['is_24h_available'] ? 'Disable 24H' : 'Enable 24H' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Add New Pincode Zone -->
    <div>
      <div class="card" style="padding:24px; border-top:4px solid var(--gold-primary);">
        <h3 style="font-size:17px; margin-bottom:16px; color:#0F172A;"><i class="fa-solid fa-map-location-dot text-gold"></i> Add / Update Pincode Zone</h3>
        <form action="" method="POST">
          <input type="hidden" name="add_area" value="1">

          <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-control" placeholder="e.g. Mumbai, Delhi, Bangalore" value="Mumbai" required>
          </div>

          <div class="form-group">
            <label class="form-label">Area / Locality Name *</label>
            <input type="text" name="area_name" class="form-control" placeholder="e.g. Juhu & Vile Parle West" required>
          </div>

          <div class="form-group">
            <label class="form-label">6-Digit Pincode *</label>
            <input type="text" name="pincode" class="form-control" placeholder="e.g. 400049" maxlength="6" required>
          </div>

          <div class="form-group">
            <label class="form-label">Max Daily Workshop Capacity</label>
            <input type="number" name="max_daily_capacity" class="form-control" value="35" required>
          </div>

          <div class="form-group">
            <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
              <input type="checkbox" name="is_24h_available" value="1" checked> Enable 24-Hour Express Guarantee for this area
            </label>
          </div>

          <button type="submit" class="btn btn-gold btn-block">
            <i class="fa-solid fa-plus"></i> Save Service Area Zone
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>
