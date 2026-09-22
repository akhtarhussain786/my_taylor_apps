<?php
$pageTitle = "Production Kanban Board";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch all active orders
$orders = $pdo->query("
  SELECT o.*, s.name as service_name, u.name as customer_name, u.mobile as customer_mobile,
         tailor_u.name as tailor_name, deliv_u.name as deliv_name
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  LEFT JOIN `users` tailor_u ON o.assigned_tailor_id = tailor_u.id
  LEFT JOIN `users` deliv_u ON o.assigned_delivery_id = deliv_u.id
  ORDER BY o.priority = 'EXPRESS' DESC, o.id DESC
")->fetchAll();

// Group orders by Kanban Lane
$lanes = [
    'NEW'       => ['title' => '1. Confirmed Orders', 'badge' => 'booked', 'items' => []],
    'CUTTING'   => ['title' => '2. Cutting Workshop', 'badge' => 'cutting', 'items' => []],
    'STITCHING' => ['title' => '3. Tailor Stitching', 'badge' => 'stitching', 'items' => []],
    'QC'        => ['title' => '4. 10-Point QC', 'badge' => 'qc', 'items' => []],
    'PACKING'   => ['title' => '5. Packaging & QR', 'badge' => 'ready', 'items' => []],
    'DISPATCH'  => ['title' => '6. Out for Delivery', 'badge' => 'out', 'items' => []],
    'DELIVERED' => ['title' => '7. Delivered (24H SLA)', 'badge' => 'delivered', 'items' => []],
];

foreach ($orders as $o) {
    $st = $o['order_status'];
    if (in_array($st, ['ORDER_CREATED', 'ORDER_CONFIRMED', 'FABRIC_READY'])) {
        $lanes['NEW']['items'][] = $o;
    } elseif (in_array($st, ['CUTTING_ASSIGNED', 'CUTTING_IN_PROGRESS', 'CUTTING_COMPLETED'])) {
        $lanes['CUTTING']['items'][] = $o;
    } elseif (in_array($st, ['STITCHING_ASSIGNED', 'STITCHING_IN_PROGRESS', 'STITCHING_COMPLETED', 'FINISHING', 'REWORK'])) {
        $lanes['STITCHING']['items'][] = $o;
    } elseif (in_array($st, ['QUALITY_CHECK', 'QC_PASSED', 'QUALITY_CHECK_PASSED'])) {
        $lanes['QC']['items'][] = $o;
    } elseif (in_array($st, ['PACKING', 'READY_FOR_DISPATCH'])) {
        $lanes['PACKING']['items'][] = $o;
    } elseif ($st === 'OUT_FOR_DELIVERY') {
        $lanes['DISPATCH']['items'][] = $o;
    } elseif ($st === 'DELIVERED') {
        $lanes['DELIVERED']['items'][] = $o;
    }
}
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Live Atelier Production Kanban</h1>
    <p>Real-time visual tracking of garments moving through Cutting, Stitching, 10-Point QC, Packaging, and Express Dispatch.</p>
  </div>
</div>

<!-- Horizontal Kanban Board -->
<div class="admin-kanban-board">
  <?php foreach ($lanes as $laneKey => $lane): ?>
    <div class="admin-card" style="margin-bottom:0; background:#F8FAFC; border:1px solid var(--admin-border);">
      <div class="admin-card-header" style="background:#FFFFFF; padding:12px 16px;">
        <h4 style="font-size:13px; font-weight:700; color:#0F172A; margin:0;">
          <?= e($lane['title']) ?>
        </h4>
        <span class="badge-status <?= $lane['badge'] ?>" style="font-size:11px;"><?= count($lane['items']) ?></span>
      </div>

      <div style="padding:12px; display:flex; flex-direction:column; gap:10px; min-height:160px;">
        <?php if (empty($lane['items'])): ?>
          <div style="text-align:center; color:#94A3B8; font-size:12px; padding:24px 0;">No active items</div>
        <?php else: ?>
          <?php foreach ($lane['items'] as $item): ?>
            <div style="background:#FFFFFF; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:12px; box-shadow:var(--admin-shadow);">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <strong style="color:var(--admin-gold); font-size:12.5px; font-family:'Outfit';"><?= e($item['booking_id']) ?></strong>
                <span style="font-size:10.5px; font-weight:700; color:#B45309;"><i class="fa-solid fa-bolt text-gold"></i> 24H</span>
              </div>
              <h5 style="font-size:13px; color:#0F172A; margin:0 0 4px;"><?= e($item['service_name']) ?></h5>
              <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-user"></i> <?= e($item['customer_name']) ?></div>
              
              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; padding-top:8px; border-top:1px dashed #E2E8F0; font-size:11px;">
                <span style="color:var(--accent-emerald); font-weight:700;"><?= formatPrice($item['total_amount']) ?></span>
                <a href="<?= APP_URL ?>/track.php?booking_id=<?= urlencode($item['booking_id']) ?>" target="_blank" style="color:#2563EB; text-decoration:none; font-weight:700;">
                  Track <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
