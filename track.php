<?php
$pageTitle = "Live Order Tracking & Production Timeline";
require_once __DIR__ . '/includes/header.php';
$pdo = getDbConnection();

$bookingId = trim($_GET['booking_id'] ?? '');

$order = null;
$tasks = [];
$proof = null;
$errorMsg = null;

if (!empty($bookingId)) {
    $stmt = $pdo->prepare("
      SELECT o.*, 
             s.name as service_name, s.category as service_category,
             f.name as fabric_name, f.color as fabric_color,
             u.name as customer_name, u.mobile as customer_mobile,
             a.house_no, a.building, a.street, a.area, a.city, a.pincode,
             exec_u.name as exec_name,
             tailor_u.name as tailor_name,
             deliv_u.name as deliv_name, deliv_u.mobile as deliv_mobile
      FROM `orders` o
      JOIN `services` s ON o.service_id = s.id
      JOIN `users` u ON o.customer_id = u.id
      LEFT JOIN `fabrics` f ON o.fabric_id = f.id
      JOIN `addresses` a ON o.delivery_address_id = a.id
      LEFT JOIN `users` exec_u ON o.appointment_id IS NOT NULL AND exec_u.role = 'measurement_executive'
      LEFT JOIN `users` tailor_u ON o.assigned_tailor_id = tailor_u.id
      LEFT JOIN `users` deliv_u ON o.assigned_delivery_id = deliv_u.id
      WHERE o.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $order = $stmt->fetch();

    if ($order) {
        $taskStmt = $pdo->prepare("SELECT * FROM `production_tasks` WHERE `order_id` = ? ORDER BY `id` ASC");
        $taskStmt->execute([$order['id']]);
        $tasks = $taskStmt->fetchAll();

        // Check delivery proof if delivered
        $pStmt = $pdo->prepare("SELECT * FROM `delivery_proofs` WHERE `order_id` = ?");
        $pStmt->execute([$order['id']]);
        $proof = $pStmt->fetch();
    } else {
        $errorMsg = "No order found with Booking ID '{$bookingId}'. Please verify the ID and try again.";
    }
}
?>

<div class="container" style="padding: 40px 20px 80px;">
  <!-- Search Header -->
  <div class="section-header">
    <span class="section-tag">Flipkart-Style Live Tracking</span>
    <h1 class="section-title">Track Your Bespoke Order</h1>
    <p class="section-desc">Real-time status updates from doorstep measurement to cutting, stitching, QC, and 24-hour express delivery.</p>

    <!-- Search Bar -->
    <form action="<?= APP_URL ?>/track.php" method="GET" style="max-width:560px; margin:24px auto 0;">
      <div style="display:flex; gap:10px;">
        <input type="text" name="booking_id" class="form-control" placeholder="Enter Booking ID (e.g. MYT-20260920-001245)" value="<?= e($bookingId) ?>" required>
        <button type="submit" class="btn btn-gold" style="white-space:nowrap;"><i class="fa-solid fa-magnifying-glass"></i> Track Live</button>
      </div>
    </form>
  </div>

  <?php if ($errorMsg): ?>
    <div class="container-sm">
      <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:16px 20px;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMsg) ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($order): ?>
    <?php
      // Determine stage progress level
      $status = $order['order_status'];
      $stages = [
        'ORDER_CONFIRMED'       => ['step' => 1, 'name' => 'Order Confirmed', 'desc' => 'Order confirmed & digital docket created.'],
        'MEASUREMENT_COMPLETED' => ['step' => 2, 'name' => 'Measurement Taken', 'desc' => 'Certified executive recorded exact measurements.'],
        'CUTTING_IN_PROGRESS'   => ['step' => 3, 'name' => 'Pattern Cutting', 'desc' => 'Master cutter shaping fabric patterns.'],
        'CUTTING_COMPLETED'     => ['step' => 3, 'name' => 'Pattern Cutting', 'desc' => 'Master cutter shaping fabric patterns.'],
        'STITCHING_IN_PROGRESS' => ['step' => 4, 'name' => 'Tailoring & Stitching', 'desc' => 'Artisanal tailoring and seam reinforcement in progress.'],
        'STITCHING_COMPLETED'   => ['step' => 4, 'name' => 'Tailoring & Stitching', 'desc' => 'Artisanal tailoring and seam reinforcement in progress.'],
        'FINISHING'             => ['step' => 5, 'name' => 'Finishing & Steam Press', 'desc' => 'Buttoning, trimming, and bespoke iron pressing.'],
        'QUALITY_CHECK'         => ['step' => 6, 'name' => 'Quality Control (QC)', 'desc' => '10-point measurement and stitch inspection.'],
        'QC_PASSED'             => ['step' => 6, 'name' => 'Quality Control (QC)', 'desc' => '10-point measurement and stitch inspection.'],
        'PACKING'               => ['step' => 7, 'name' => 'Bespoke Packaging', 'desc' => 'Garment sealed with QR Code and dispatched.'],
        'READY_FOR_DISPATCH'    => ['step' => 7, 'name' => 'Bespoke Packaging', 'desc' => 'Garment sealed with QR Code and dispatched.'],
        'OUT_FOR_DELIVERY'      => ['step' => 8, 'name' => 'Out for Delivery', 'desc' => 'Express rider en route to your doorstep.'],
        'DELIVERED'             => ['step' => 9, 'name' => 'Delivered', 'desc' => 'Handed over at your address. Verified with signature.'],
      ];

      $currentStepNum = $stages[$status]['step'] ?? 1;

      // Define standard milestone steps for timeline
      $milestones = [
        1 => ['title' => 'Order Confirmed', 'desc' => 'Booking ID assigned and fabric reserved.', 'icon' => 'fa-clipboard-check', 'time' => $order['created_at']],
        2 => ['title' => 'Measurement Completed', 'desc' => 'Measurements captured and saved to digital profile.', 'icon' => 'fa-ruler-combined', 'time' => $order['sla_start_time'] ?? $order['created_at']],
        3 => ['title' => 'Pattern Cutting Completed', 'desc' => 'Fabric laser/sheared according to custom fit.', 'icon' => 'fa-scissors', 'time' => 'Est: 2-3 hours'],
        4 => ['title' => 'Stitching & Tailoring', 'desc' => 'Handcrafted by senior tailor.', 'icon' => 'fa-shirt', 'time' => 'In Progress'],
        5 => ['title' => 'Finishing & Steam Press', 'desc' => 'Buttonhole threading, lining & pressing.', 'icon' => 'fa-wand-magic-sparkles', 'time' => 'Pending'],
        6 => ['title' => '10-Point Master QC', 'desc' => 'Passed inspection for symmetry and seam strength.', 'icon' => 'fa-award', 'time' => 'Pending'],
        7 => ['title' => 'Garment Packaging', 'desc' => 'Packaged with QR Dispatch label.', 'icon' => 'fa-box-open', 'time' => 'Pending'],
        8 => ['title' => 'Out for Delivery', 'desc' => 'Express rider assigned for last-mile delivery.', 'icon' => 'fa-motorcycle', 'time' => 'Pending'],
        9 => ['title' => 'Delivered to Doorstep', 'desc' => 'Delivered with signature & photo confirmation.', 'icon' => 'fa-house-circle-check', 'time' => $order['delivered_at'] ?? 'Guaranteed in 24 Hours']
      ];
    ?>

    <div class="tracking-wrapper">
      <!-- Tracking Header -->
      <div class="tracking-header">
        <div>
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
            <span class="badge-24h pulse"><i class="fa-solid fa-bolt"></i> <?= $order['priority'] === 'EXPRESS' ? '24-Hour Express Order' : 'Standard Delivery' ?></span>
            <span class="badge-status badge-gold">Status: <?= str_replace('_', ' ', $order['order_status']) ?></span>
          </div>
          <h2 style="font-size:26px; color:#0F172A; margin:0;">Booking ID: <span class="text-gold"><?= e($order['booking_id']) ?></span></h2>
          <p style="font-size:14px; color:var(--text-muted); margin:4px 0 0;">
            Product: <strong><?= e($order['service_name']) ?></strong> • Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
          </p>
        </div>

        <!-- 24-Hour Countdown Box -->
        <?php if ($order['order_status'] !== 'DELIVERED' && !empty($order['sla_deadline'])): ?>
          <div class="countdown-box">
            <span style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--gold-primary); font-weight:700;">24-Hour Delivery Countdown</span>
            <div class="time" id="sla-countdown" data-deadline="<?= date('c', strtotime($order['sla_deadline'])) ?>">
              Calculating...
            </div>
            <span style="font-size:11px; color:#CBD5E1;">Deadline: <?= date('d M, h:i A', strtotime($order['sla_deadline'])) ?></span>
          </div>
        <?php elseif ($order['order_status'] === 'DELIVERED'): ?>
          <div class="countdown-box" style="background:#065F46; border-color:#34D399;">
            <span style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#A7F3D0; font-weight:700;">Delivered Successfully</span>
            <div class="time" style="color:#FFFFFF; font-size:20px;">✓ Within 24-Hour SLA</div>
            <span style="font-size:11px; color:#D1FAE5;"><?= date('d M Y, h:i A', strtotime($order['delivered_at'] ?? $order['updated_at'])) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Main Grid: Timeline + Order Details -->
      <div class="grid-2" style="grid-template-columns: 1.3fr 0.7fr; gap:40px;">
        
        <!-- Left: Flipkart / Amazon Vertical Timeline -->
        <div>
          <h3 style="font-size:18px; margin-bottom:20px; color:#0F172A;"><i class="fa-solid fa-timeline text-gold"></i> Live Production Journey</h3>

          <div class="timeline-stepper">
            <?php foreach ($milestones as $stepKey => $m): ?>
              <?php
                $isCompleted = $stepKey < $currentStepNum || $order['order_status'] === 'DELIVERED';
                $isActive = ($stepKey === $currentStepNum) && $order['order_status'] !== 'DELIVERED';
                $class = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
              ?>
              <div class="timeline-step <?= $class ?>">
                <div class="step-marker">
                  <?php if ($isCompleted): ?>
                    <i class="fa-solid fa-check"></i>
                  <?php elseif ($isActive): ?>
                    <i class="fa-solid fa-arrows-spin fa-spin"></i>
                  <?php else: ?>
                    <i class="fa-solid <?= $m['icon'] ?>"></i>
                  <?php endif; ?>
                </div>
                <div class="step-content">
                  <h4><?= e($m['title']) ?></h4>
                  <p><?= e($m['desc']) ?></p>
                  <div class="step-time">
                    <?php if ($isCompleted): ?>
                      <span style="color:var(--accent-emerald);"><i class="fa-solid fa-circle-check"></i> Completed</span>
                    <?php elseif ($isActive): ?>
                      <span style="color:var(--gold-primary); font-weight:700;"><i class="fa-solid fa-bolt"></i> Currently In Progress</span>
                    <?php else: ?>
                      <span style="color:#94A3B8;">Upcoming Stage</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right: Summary & Operations Card -->
        <div>
          <!-- Garment & Pricing Summary -->
          <div class="card" style="margin-bottom:24px; background:#F8FAFC;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #E2E8F0; padding-bottom:8px;">
              <h4 style="font-size:16px; margin:0;">Garment Summary</h4>
              <a href="<?= APP_URL ?>/invoice.php?booking_id=<?= e($order['booking_id']) ?>" target="_blank" class="btn btn-dark btn-sm" style="font-size:11px;">
                <i class="fa-solid fa-file-invoice"></i> Invoice
              </a>
            </div>

            <p style="font-size:13.5px; margin-bottom:8px;"><strong>Garment:</strong> <?= e($order['service_name']) ?></p>
            <p style="font-size:13.5px; margin-bottom:8px;"><strong>Fabric:</strong> <?= e($order['fabric_name'] ? $order['fabric_name'] . ' (' . $order['fabric_color'] . ')' : 'Customer Provided Fabric') ?></p>
            <?php if (!empty($order['special_instructions'])): ?>
              <p style="font-size:13px; color:#475569; margin-bottom:12px; background:#FFFFFF; padding:8px 10px; border-radius:6px; border:1px solid #E2E8F0;">
                <i class="fa-solid fa-pen-nib text-gold"></i> <em>"<?= e($order['special_instructions']) ?>"</em>
              </p>
            <?php endif; ?>

            <div style="border-top:1px dashed #CBD5E1; padding-top:10px; margin-top:10px; font-size:13px;">
              <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span>Tailoring Charge:</span>
                <span><?= formatPrice($order['tailoring_charge']) ?></span>
              </div>
              <?php if ($order['fabric_charge'] > 0): ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                  <span>Fabric Charge:</span>
                  <span><?= formatPrice($order['fabric_charge']) ?></span>
                </div>
              <?php endif; ?>
              <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span>Doorstep Measurement:</span>
                <span><?= formatPrice($order['measurement_fee']) ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span>24H Express Fee:</span>
                <span><?= formatPrice($order['express_fee']) ?></span>
              </div>
              <?php if ($order['discount_amount'] > 0): ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px; color:var(--accent-emerald);">
                  <span>Discount Applied:</span>
                  <span>-<?= formatPrice($order['discount_amount']) ?></span>
                </div>
              <?php endif; ?>
              <div style="display:flex; justify-content:space-between; font-weight:800; font-size:16px; margin-top:8px; border-top:1px solid #CBD5E1; padding-top:8px; color:#0F172A;">
                <span>Total Amount:</span>
                <span style="color:var(--gold-primary);"><?= formatPrice($order['total_amount']) ?></span>
              </div>
            </div>
          </div>

          <!-- Delivery Address Card -->
          <div class="card" style="margin-bottom:24px; background:#F8FAFC;">
            <h4 style="font-size:16px; margin-bottom:10px;"><i class="fa-solid fa-location-dot text-gold"></i> Delivery Destination</h4>
            <p style="font-size:13.5px; color:#334155; line-height:1.5;">
              <strong><?= e($order['customer_name']) ?></strong> (<?= e($order['customer_mobile']) ?>)<br>
              <?= e($order['house_no']) ?>, <?= e($order['building']) ?><br>
              <?= e($order['street']) ?>, <?= e($order['area']) ?><br>
              <?= e($order['city']) ?> - <strong><?= e($order['pincode']) ?></strong>
            </p>
        </div>

      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
