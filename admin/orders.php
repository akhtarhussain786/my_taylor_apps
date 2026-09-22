<?php
$pageTitle = "24H Express Orders";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)$_POST['order_id'];
    $bookingId = $pdo->query("SELECT `booking_id` FROM `orders` WHERE `id` = {$orderId}")->fetchColumn();
    
    $pdo->prepare("DELETE FROM `production_tasks` WHERE `order_id` = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM `delivery_proofs` WHERE `order_id` = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM `reviews` WHERE `order_id` = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM `order_items` WHERE `order_id` = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM `orders` WHERE `id` = ?")->execute([$orderId]);
    
    logAudit($orderId, null, $currentUser['id'], 'ORDER_DELETED', null, 'DELETED', "Admin deleted order {$bookingId} (#{$orderId})");
    $msg = "Order '{$bookingId}' successfully deleted!";
}

// Fetch all orders
$orders = $pdo->query("
  SELECT o.*, s.name as service_name, s.category as service_category,
         u.name as customer_name, u.mobile as customer_mobile,
         m.measurement_code,
         deliv_u.name as delivery_boy_name,
         a.house_no, a.building, a.street, a.area, a.city, a.pincode
  FROM `orders` o
  JOIN `services` s ON o.service_id = s.id
  JOIN `users` u ON o.customer_id = u.id
  JOIN `addresses` a ON o.delivery_address_id = a.id
  LEFT JOIN `measurements` m ON o.measurement_id = m.id
  LEFT JOIN `users` deliv_u ON o.assigned_delivery_id = deliv_u.id
  ORDER BY o.id DESC
")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>24-Hour Express Orders & Tracking</h1>
    <p>Monitor real-time tailoring pipeline, customer SLA timers, assigned riders, and invoice generation.</p>
  </div>
  <div>
    <span class="admin-badge-count admin-badge-gold" style="font-size:14px; padding:6px 14px;">Total Orders: <?= count($orders) ?></span>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-circle-check"></i> <?= e($msg) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3><i class="fa-solid fa-box-open text-gold"></i> Orders Registry</h3>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Customer Details</th>
          <th>Garment & Profile</th>
          <th>Assigned Rider</th>
          <th>24H SLA Deadline</th>
          <th>Total</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td>
              <strong style="color:#0F172A; font-family:'Outfit'; font-size:14px;"><?= e($o['booking_id']) ?></strong>
              <div style="font-size:11px; color:#64748B;"><i class="fa-solid fa-clock text-gold"></i> <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></div>
            </td>
            <td>
              <strong style="font-size:13.5px; color:#0F172A;"><?= e($o['customer_name']) ?></strong>
              <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-phone"></i> <?= e($o['customer_mobile']) ?></div>
              <div style="font-size:11.5px; color:#94A3B8;"><i class="fa-solid fa-location-dot"></i> <?= e($o['area']) ?>, <?= e($o['city']) ?></div>
            </td>
            <td>
              <strong style="color:#0F172A; font-size:13.5px;"><?= e($o['service_name']) ?></strong>
              <div style="font-size:11px; color:#64748B;">Profile: <?= e($o['measurement_code'] ?? 'Standard') ?></div>
            </td>
            <td>
              <?php if (!empty($o['delivery_boy_name'])): ?>
                <strong style="font-size:13px; color:#0F172A;"><i class="fa-solid fa-motorcycle text-gold"></i> <?= e($o['delivery_boy_name']) ?></strong>
              <?php else: ?>
                <span style="color:#94A3B8; font-size:12px;">Unassigned</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($o['order_status'] === 'DELIVERED'): ?>
                <span style="color:var(--accent-emerald); font-weight:700; font-size:12px;"><i class="fa-solid fa-circle-check"></i> Delivered</span>
              <?php else: ?>
                <span class="badge-status qc" style="font-size:11px;"><i class="fa-solid fa-bolt"></i> <?= date('d M, h:i A', strtotime($o['sla_deadline'] ?? '+24h')) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color:var(--admin-gold); font-size:14px;"><?= formatPrice($o['total_amount']) ?></strong>
              <div style="font-size:10.5px; color:var(--accent-emerald); font-weight:700;">PAID</div>
            </td>
            <td>
              <span class="badge-status <?= strtolower($o['order_status']) === 'delivered' ? 'delivered' : 'booked' ?>">
                <?= str_replace('_', ' ', $o['order_status']) ?>
              </span>
            </td>
            <td style="text-align:right; white-space:nowrap;">
              <div style="display:inline-flex; gap:6px; align-items:center;">
                <a href="<?= APP_URL ?>/track.php?booking_id=<?= urlencode($o['booking_id']) ?>" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-size:11.5px; padding:5px 9px; border-radius:6px; text-decoration:none;" title="Live Customer Tracking">
                  <i class="fa-solid fa-location-crosshairs"></i> Track
                </a>
                <a href="<?= APP_URL ?>/invoice.php?booking_id=<?= urlencode($o['booking_id']) ?>" target="_blank" class="btn btn-sm" style="background:#F1F5F9; color:#0F172A; font-size:11.5px; padding:5px 9px; border-radius:6px; border:1px solid #CBD5E1; text-decoration:none;" title="Print Invoice">
                  <i class="fa-solid fa-print"></i>
                </a>
                <form action="<?= APP_URL ?>/admin/orders.php" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Kya aap sach me order <?= e($o['booking_id']) ?> ko delete karna chahte hain?');">
                  <input type="hidden" name="delete_order" value="1">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background:#FEE2E2; color:#DC2626; border:1px solid #FCA5A5; font-size:11.5px; padding:5px 8px; border-radius:6px; cursor:pointer;" title="Delete Order">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
