<?php
$pageTitle = "My Profile & Customer Dashboard";
require_once __DIR__ . '/includes/header.php';
requireRole(['customer', 'admin'], 'login.php');

$currentUser = getCurrentUser();
$pdo = getDbConnection();
$userId = $currentUser['id'];

// Fetch Active Orders
$ordersStmt = $pdo->prepare("
  SELECT o.*, s.name as service_name, s.category as service_category,
         f.name as fabric_name, a.street, a.city, a.pincode
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  LEFT JOIN `fabrics` f ON o.fabric_id = f.id
  JOIN `addresses` a ON o.delivery_address_id = a.id
  WHERE o.customer_id = ?
  ORDER BY o.id DESC
");
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

// Fetch Appointments
$aptStmt = $pdo->prepare("
  SELECT a.*, s.name as service_name, u.name as exec_name
  FROM `appointments` a
  JOIN `services` s ON a.service_id = s.id
  LEFT JOIN `users` u ON a.executive_id = u.id
  WHERE a.customer_id = ?
  ORDER BY a.id DESC
");
$aptStmt->execute([$userId]);
$appointments = $aptStmt->fetchAll();

// Fetch Saved Measurements
$measStmt = $pdo->prepare("
  SELECT * FROM `measurements` WHERE `customer_id` = ? ORDER BY `id` DESC
");
$measStmt->execute([$userId]);
$measurements = $measStmt->fetchAll();

// Fetch Addresses
$addrStmt = $pdo->prepare("
  SELECT * FROM `addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC, `id` DESC
");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

// Handle 1-Click Reorder Action
$reorderSuccess = null;
if (isset($_GET['action']) && $_GET['action'] === 'reorder' && isset($_GET['order_id'])) {
    $origOrderId = (int)$_GET['order_id'];
    $origStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = ? AND `customer_id` = ?");
    $origStmt->execute([$origOrderId, $userId]);
    $origOrder = $origStmt->fetch();

    if ($origOrder) {
        $newBookingId = generateBookingId($pdo);
        $slaStart = date('Y-m-d H:i:s');
        $slaDeadline = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $insOrder = $pdo->prepare("
          INSERT INTO `orders` (
            `booking_id`, `customer_id`, `measurement_id`, `service_id`, `fabric_source`, `fabric_id`,
            `delivery_address_id`, `priority`, `is_24h_delivery`, `tailoring_charge`, `fabric_charge`,
            `measurement_fee`, `express_fee`, `total_amount`, `payment_method`, `payment_status`,
            `order_status`, `sla_start_time`, `sla_deadline`, `assigned_cutting_id`, `assigned_tailor_id`,
            `assigned_qc_id`, `assigned_packing_id`, `assigned_delivery_id`, `special_instructions`
          ) VALUES (?, ?, ?, ?, ?, ?, ?, 'EXPRESS', 1, ?, ?, 0.00, ?, ?, 'UPI', 'PAID', 'CUTTING_IN_PROGRESS', ?, ?, 3, 4, 5, 6, 7, ?)
        ");
        $insOrder->execute([
            $newBookingId, $userId, $origOrder['measurement_id'], $origOrder['service_id'], $origOrder['fabric_source'],
            $origOrder['fabric_id'], $origOrder['delivery_address_id'], $origOrder['tailoring_charge'], $origOrder['fabric_charge'],
            $origOrder['express_fee'], $origOrder['total_amount'] - $origOrder['measurement_fee'],
            $slaStart, $slaDeadline, "1-Click Instant Reorder using Saved Digital Profile MT-M."
        ]);
        $newId = $pdo->lastInsertId();

        // Create tasks
        $pdo->prepare("INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`) VALUES (?, 'CUTTING', 3, 'IN_PROGRESS', 'Re-order pattern cutting initiated with saved measurements.', ?)")->execute([$newId, $slaStart]);

        logAudit($newId, null, $userId, 'REORDER_PLACED', null, 'CUTTING_IN_PROGRESS', "Customer placed 1-Click Reorder {$newBookingId}");

        header("Location: " . APP_URL . "/track.php?booking_id=" . urlencode($newBookingId));
        exit;
    }
}
?>

<div class="container" style="padding: 40px 20px 80px;">
  <!-- Welcome Topbar -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; flex-wrap:wrap; gap:16px;">
    <div>
      <span class="section-tag">Patron Concierge</span>
      <h1 style="font-size:32px; color:#0F172A; margin:0;">Welcome back, <?= e($currentUser['name']) ?></h1>
      <p style="color:var(--text-muted); font-size:14px; margin:4px 0 0;">
        <i class="fa-solid fa-phone text-gold"></i> <?= e($currentUser['mobile']) ?> • <i class="fa-solid fa-envelope text-gold"></i> <?= e($currentUser['email']) ?>
      </p>
    </div>
    <div style="display:flex; gap:12px;">
      <a href="<?= APP_URL ?>/book.php" class="btn btn-gold"><i class="fa-solid fa-tape"></i> Book New Doorstep Appointment</a>
    </div>
  </div>

  <!-- Dashboard Grid -->
  <div class="grid-2" style="grid-template-columns: 1.35fr 0.65fr; gap:36px;">
    
    <!-- Left Column: Active Orders & Appointments -->
    <div>
      <!-- Section: Orders -->
      <div style="margin-bottom:40px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
          <h2 style="font-size:22px; color:#0F172A;"><i class="fa-solid fa-shirt text-gold"></i> My Orders (<?= count($orders) ?>)</h2>
          <span style="font-size:12px; color:var(--text-muted);">Real-time production status</span>
        </div>

        <?php if (empty($orders)): ?>
          <div class="card" style="text-align:center; padding:32px;">
            <p style="color:var(--text-muted);">You have not placed any tailoring orders yet.</p>
            <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-sm" style="margin-top:10px;">Book First Appointment</a>
          </div>
        <?php endif; ?>

        <?php foreach ($orders as $o): ?>
          <div class="card" style="margin-bottom:18px; border-left:4px solid var(--gold-primary);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap; gap:10px;">
              <div>
                <span class="badge-status badge-gold">Booking ID: <?= e($o['booking_id']) ?></span>
                <h3 style="font-size:18px; margin:6px 0 2px; color:#0F172A;"><?= e($o['service_name']) ?></h3>
                <span style="font-size:12px; color:var(--text-muted);">
                  Ordered on <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                </span>
              </div>
              <div style="text-align:right;">
                <span style="font-size:18px; font-weight:800; color:#0F172A;"><?= formatPrice($o['total_amount']) ?></span>
                <div>
                  <span class="badge-status <?= $o['order_status'] === 'DELIVERED' ? 'badge-emerald' : 'badge-blue' ?>">
                    <?= str_replace('_', ' ', $o['order_status']) ?>
                  </span>
                </div>
              </div>
            </div>

            <p style="font-size:13px; color:#475569; margin-bottom:14px;">
              <i class="fa-solid fa-location-dot text-gold"></i> Delivery Address: <?= e($o['street']) ?>, <?= e($o['city']) ?> - <?= e($o['pincode']) ?>
              <?php if ($o['fabric_name']): ?>
                • Fabric: <strong><?= e($o['fabric_name']) ?></strong>
              <?php endif; ?>
            </p>

            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:12px; flex-wrap:wrap; gap:10px;">
              <div style="display:flex; gap:10px;">
                <a href="<?= APP_URL ?>/track.php?booking_id=<?= urlencode($o['booking_id']) ?>" class="btn btn-gold btn-sm">
                  <i class="fa-solid fa-location-crosshairs"></i> Track Live
                </a>
                <a href="<?= APP_URL ?>/invoice.php?booking_id=<?= urlencode($o['booking_id']) ?>" target="_blank" class="btn btn-dark btn-sm">
                  <i class="fa-solid fa-file-invoice"></i> Invoice
                </a>
              </div>
              <div>
                <a href="<?= APP_URL ?>/dashboard.php?action=reorder&order_id=<?= $o['id'] ?>" class="btn btn-gold-outline btn-sm" onclick="return confirm('Place instant 1-Click Re-order for <?= e($o['service_name']) ?> with your saved measurements?');">
                  <i class="fa-solid fa-repeat"></i> 1-Click Reorder
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Section: Doorstep Appointments -->
      <div>
        <h2 style="font-size:22px; color:#0F172A; margin-bottom:18px;"><i class="fa-solid fa-calendar-days text-gold"></i> Doorstep Appointments</h2>
        <?php if (empty($appointments)): ?>
          <div class="card" style="text-align:center; padding:24px;">
            <p style="color:var(--text-muted); margin:0;">No appointments booked currently.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($appointments as $apt): ?>
          <div class="card" style="margin-bottom:14px; background:#F8FAFC;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <strong style="font-size:15px; color:#0F172A;"><?= e($apt['appointment_code']) ?> — <?= e($apt['service_name']) ?></strong>
                <p style="font-size:13px; color:var(--text-muted); margin:2px 0 0;">
                  <i class="fa-regular fa-clock text-gold"></i> <?= date('d M Y', strtotime($apt['appointment_date'])) ?> (<?= e($apt['time_slot']) ?>)
                </p>
                <p style="font-size:12px; color:#059669; margin:2px 0 0;">
                  <i class="fa-solid fa-user-tie"></i> Specialist: <?= e($apt['exec_name'] ?? 'Vikram Singh (Assigned)') ?>
                </p>
              </div>
              <div>
                <span class="badge-status <?= $apt['status'] === 'MEASUREMENT_COMPLETED' ? 'badge-emerald' : 'badge-amber' ?>">
                  <?= str_replace('_', ' ', $apt['status']) ?>
                </span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Column: Digital Measurement Profile & Addresses -->
    <div>
      <!-- Digital Measurement Profiles -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <h3 style="font-size:18px; color:#0F172A;"><i class="fa-solid fa-ruler-combined text-gold"></i> Digital Measurement IDs</h3>
        </div>

        <?php if (empty($measurements)): ?>
          <div class="card" style="text-align:center; padding:20px; font-size:13px; color:var(--text-muted);">
            No measurements recorded yet. Book an executive to generate your permanent profile.
          </div>
        <?php endif; ?>

        <?php foreach ($measurements as $m): ?>
          <?php 
            $mJson = json_decode($m['measurements_json'], true) ?: []; 
            $dJson = json_decode($m['design_specs_json'], true) ?: [];
          ?>
          <div class="measurement-badge-card" style="margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <div>
                <span class="badge-status badge-gold"><?= e($m['measurement_code']) ?></span>
                <h4 style="font-size:16px; margin:6px 0 2px; color:#0F172A;"><?= e($m['garment_category']) ?> Profile</h4>
                <span style="font-size:12px; color:var(--accent-emerald); font-weight:700;"><i class="fa-solid fa-check"></i> <?= e($m['fit_preference']) ?></span>
              </div>
              <span style="font-size:11px; color:#94A3B8;">Verified</span>
            </div>

            <!-- Anatomical Specs Grid -->
            <div class="measurement-spec-grid">
              <?php foreach ($mJson as $k => $v): ?>
                <div class="spec-chip">
                  <div class="name"><?= str_replace('_', ' ', $k) ?></div>
                  <div class="val"><?= e($v) ?>"</div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (!empty($dJson)): ?>
              <div style="font-size:12px; color:#64748B; background:#F1F5F9; padding:8px 10px; border-radius:6px; margin-top:8px;">
                <strong>Styling Defaults:</strong>
                <?php foreach ($dJson as $dk => $dv): ?>
                  <span style="display:inline-block; margin-right:8px;"><?= ucfirst($dk) ?>: <em><?= e($dv) ?></em></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($m['notes'])): ?>
              <p style="font-size:12px; color:#475569; margin-top:8px; font-style:italic;">
                "<?= e($m['notes']) ?>"
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Saved Addresses -->
      <div>
        <h3 style="font-size:18px; color:#0F172A; margin-bottom:14px;"><i class="fa-solid fa-map-location-dot text-gold"></i> Saved Addresses</h3>
        <?php foreach ($addresses as $a): ?>
          <div class="card" style="margin-bottom:10px; padding:14px; background:#F8FAFC;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
              <strong style="font-size:14px; color:#0F172A;"><?= e($a['title']) ?></strong>
              <?php if ($a['is_default']): ?>
                <span class="badge-status badge-emerald" style="font-size:10px;">Default</span>
              <?php endif; ?>
            </div>
            <p style="font-size:13px; color:#475569; margin:0; line-height:1.4;">
              <?= e($a['house_no']) ?>, <?= e($a['building']) ?><br>
              <?= e($a['street']) ?>, <?= e($a['area']) ?><br>
              <?= e($a['city']) ?> - <?= e($a['pincode']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
