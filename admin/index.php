<?php
$pageTitle = "Command Center";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../includes/mail.php';

$successMsg = null;
$errorMsg = null;

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add New Staff (Delivery Boy or Measurement Executive)
    if ($action === 'add_staff') {
        $name = trim($_POST['staff_name'] ?? '');
        $mobile = trim($_POST['staff_mobile'] ?? '');
        $email = trim($_POST['staff_email'] ?? '');
        $role = $_POST['staff_role'] ?? 'delivery_executive';
        $password = $_POST['staff_password'] ?? 'password123';

        if (empty($name) || empty($mobile)) {
            $errorMsg = "Please enter staff name and mobile number.";
        } else {
            if (empty($email)) {
                $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . rand(100, 999) . '@mytaylor.local';
            }

            $chk = $pdo->prepare("SELECT `id` FROM `users` WHERE `mobile` = ? OR `email` = ?");
            $chk->execute([$mobile, $email]);
            if ($chk->fetch()) {
                $errorMsg = "A staff member with this mobile number or email already exists.";
            } else {
                $hash = password_hash($password ?: 'password123', PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `mobile`, `password_hash`, `role`, `status`) VALUES (?, ?, ?, ?, ?, 'active')");
                $ins->execute([$name, $email, $mobile, $hash, $role]);
                $roleLabel = ($role === 'delivery_executive') ? 'Delivery Boy' : 'Measurement Executive';
                logAudit(null, null, $currentUser['id'], 'STAFF_CREATED', null, 'ACTIVE', "Admin added new {$roleLabel}: {$name} ({$mobile})");
                $successMsg = "New {$roleLabel} '{$name}' successfully added to the system!";
            }
        }
    }

    // 2. Assign Measurement Executive to Appointment
    if ($action === 'assign_meas_exec') {
        $aptId = (int)$_POST['appointment_id'];
        $execId = (int)$_POST['measurement_exec_id'];
        $pdo->prepare("UPDATE `appointments` SET `executive_id` = ?, `status` = 'EXECUTIVE_ASSIGNED' WHERE `id` = ?")->execute([$execId, $aptId]);
        logAudit(null, $aptId, $currentUser['id'], 'MEAS_EXEC_ASSIGNED', null, 'EXECUTIVE_ASSIGNED', "Admin assigned measurement executive #{$execId} to appointment #{$aptId}");
        $successMsg = "Measurement Executive assigned to Appointment!";
    }

    // 3. Update Order Status & Assign Delivery Boy
    if ($action === 'update_order_status') {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['new_status'];
        $deliveryBoyId = !empty($_POST['delivery_boy_id']) ? (int)$_POST['delivery_boy_id'] : null;

        $prevStatus = $pdo->query("SELECT `order_status` FROM `orders` WHERE `id` = {$orderId}")->fetchColumn();

        if ($deliveryBoyId) {
            $pdo->prepare("UPDATE `orders` SET `order_status` = ?, `assigned_delivery_id` = ? WHERE `id` = ?")->execute([$newStatus, $deliveryBoyId, $orderId]);
        } else {
            $pdo->prepare("UPDATE `orders` SET `order_status` = ? WHERE `id` = ?")->execute([$newStatus, $orderId]);
        }

        if ($newStatus === 'DELIVERED') {
            $pdo->prepare("UPDATE `orders` SET `delivered_at` = NOW() WHERE `id` = ?")->execute([$orderId]);
        }

        logAudit($orderId, null, $currentUser['id'], 'ADMIN_STATUS_UPDATE', $prevStatus, $newStatus, "Admin updated order status to {$newStatus}");
        
        // Trigger Automated Status Email to Customer
        sendOrderStatusEmail($pdo, $orderId, $newStatus);

        $successMsg = "Order status updated to: " . str_replace('_', ' ', $newStatus);
    }

    // 4. Convert Appointment to Active Order with Measurements
    if ($action === 'convert_apt_to_order') {
        $aptId = (int)$_POST['appointment_id'];
        $fitPref = $_POST['fit_preference'] ?? 'Regular Fit';
        $garmentType = $_POST['garment_type'] ?? 'Shirt';
        $notes = trim($_POST['notes'] ?? '');

        $mArray = [
            'neck'          => (float)($_POST['m_neck'] ?? 16.0),
            'shoulder'      => (float)($_POST['m_shoulder'] ?? 18.5),
            'chest'         => (float)($_POST['m_chest'] ?? 40.0),
            'waist'         => (float)($_POST['m_waist'] ?? 34.0),
            'hip'           => (float)($_POST['m_hip'] ?? 41.0),
            'sleeve_length' => (float)($_POST['m_sleeve_length'] ?? 25.5),
            'length'        => (float)($_POST['m_length'] ?? 30.0)
        ];

        try {
            $pdo->beginTransaction();

            $aptStmt = $pdo->prepare("SELECT a.*, s.base_price, s.express_price, s.measurement_fee FROM `appointments` a JOIN `services` s ON a.service_id = s.id WHERE a.id = ?");
            $aptStmt->execute([$aptId]);
            $apt = $aptStmt->fetch();

            if (!$apt) throw new Exception("Appointment not found");

            $mCode = generateMeasurementCode($pdo);
            $insM = $pdo->prepare("
              INSERT INTO `measurements` (`measurement_code`, `customer_id`, `garment_category`, `measurements_json`, `fit_preference`, `notes`, `created_by_user_id`)
              VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insM->execute([$mCode, $apt['customer_id'], $garmentType, json_encode($mArray), $fitPref, $notes, $currentUser['id']]);
            $measurementId = $pdo->lastInsertId();

            $pdo->prepare("UPDATE `appointments` SET `status` = 'MEASUREMENT_COMPLETED', `completed_at` = NOW() WHERE `id` = ?")->execute([$aptId]);

            $bookingId = generateBookingId($pdo);
            $slaStart = date('Y-m-d H:i:s');
            $slaDeadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $totalAmount = $apt['base_price'] + $apt['express_price'] + $apt['measurement_fee'];

            $firstRider = $pdo->query("SELECT `id` FROM `users` WHERE `role` = 'delivery_executive' AND `status` = 'active' LIMIT 1")->fetchColumn() ?: 7;

            $insOrder = $pdo->prepare("
              INSERT INTO `orders` (
                `booking_id`, `customer_id`, `appointment_id`, `measurement_id`, `service_id`,
                `fabric_source`, `delivery_address_id`, `priority`, `is_24h_delivery`,
                `tailoring_charge`, `measurement_fee`, `express_fee`, `total_amount`,
                `payment_method`, `payment_status`, `order_status`, `sla_start_time`, `sla_deadline`,
                `assigned_delivery_id`, `special_instructions`
              ) VALUES (?, ?, ?, ?, ?, 'MY_TAYLOR_FABRIC', ?, 'EXPRESS', 1, ?, ?, ?, ?, 'UPI', 'PAID', 'STITCHING_IN_PROGRESS', ?, ?, ?, ?)
            ");
            $insOrder->execute([
                $bookingId, $apt['customer_id'], $aptId, $measurementId, $apt['service_id'],
                $apt['address_id'], $apt['base_price'], $apt['measurement_fee'], $apt['express_price'], $totalAmount,
                $slaStart, $slaDeadline, $firstRider, "Doorstep measurement recorded. Fit: {$fitPref}."
            ]);
            $newOrderId = $pdo->lastInsertId();

            logAudit($newOrderId, $aptId, $currentUser['id'], 'ORDER_CREATED_BY_ADMIN', 'BOOKED', 'STITCHING_IN_PROGRESS', "Admin recorded measurement and created Order {$bookingId}");

            $pdo->commit();
            
            // Dispatch Order Confirmed Email to Customer
            sendOrderStatusEmail($pdo, $newOrderId, 'STITCHING_IN_PROGRESS', "Measurement recorded. Garment moved to artisanal tailoring.");

            $successMsg = "Appointment converted! Order {$bookingId} created and moved to Stitching!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Active Delivery Boys and Measurement Executives
$deliveryBoys = $pdo->query("SELECT * FROM `users` WHERE `role` = 'delivery_executive' AND `status` = 'active' ORDER BY `id` DESC")->fetchAll();
$measExecutives = $pdo->query("SELECT * FROM `users` WHERE `role` = 'measurement_executive' AND `status` = 'active' ORDER BY `id` DESC")->fetchAll();
$totalServices = (int)$pdo->query("SELECT COUNT(*) FROM `services` WHERE `status` = 'active'")->fetchColumn();

// Fetch All Appointments
$appointments = $pdo->query("
  SELECT a.*, s.name as service_name,
         u.name as customer_name, u.mobile as customer_mobile,
         exec_u.name as exec_name,
         addr.house_no, addr.street, addr.area, addr.city, addr.pincode
  FROM `appointments` a
  JOIN `services` s ON a.service_id = s.id
  JOIN `users` u ON a.customer_id = u.id
  JOIN `addresses` addr ON a.address_id = addr.id
  LEFT JOIN `users` exec_u ON a.executive_id = exec_u.id
  ORDER BY a.id DESC
")->fetchAll();

// Fetch All Orders
$orders = $pdo->query("
  SELECT o.*, s.name as service_name,
         u.name as customer_name, u.mobile as customer_mobile,
         m.measurement_code, m.fit_preference,
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

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `orders` WHERE `payment_status` = 'PAID'")->fetchColumn();
$totalOrders = count($orders);
$totalApts = count($appointments);
$activeProduction = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` NOT IN ('DELIVERED', 'CANCELLED')")->fetchColumn();
$deliveredCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'DELIVERED'")->fetchColumn();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Operations Command Center</h1>
    <p>Live 24-Hour Express Tailoring, Doorstep Appointments & Dispatch Hub</p>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <button type="button" class="btn btn-sm" onclick="document.getElementById('addStaffModal').style.display='flex';" style="background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:8px;">
      <i class="fa-solid fa-user-plus"></i> + Add Staff Member
    </button>
    <a href="<?= APP_URL ?>/admin/services.php" class="btn btn-sm" style="background:#0F172A; color:#FFFFFF; font-weight:700; border-radius:8px;">
      <i class="fa-solid fa-scissors text-gold"></i> Edit Garment Pricing
    </a>
    <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-sm" style="background:#FFFFFF; border:1px solid var(--admin-border); color:#0F172A; font-weight:700; border-radius:8px;">
      <i class="fa-solid fa-sliders text-gold"></i> Cashfree & Settings
    </a>
  </div>
</div>

<?php if ($successMsg): ?>
  <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-circle-check"></i> <?= e($successMsg) ?>
  </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div class="card" style="background:#FEF2F2; border:1px solid #F87171; color:#991B1B; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMsg) ?>
  </div>
<?php endif; ?>

<!-- Executive KPI Stat Cards -->
<div class="admin-kpi-grid">
  <div class="admin-kpi-card gold">
    <div class="admin-kpi-data">
      <span>Total Revenue</span>
      <h3><?= formatPrice($totalRevenue) ?></h3>
    </div>
    <div class="admin-kpi-icon icon-gold">
      <i class="fa-solid fa-indian-rupee-sign"></i>
    </div>
  </div>

  <div class="admin-kpi-card blue">
    <div class="admin-kpi-data">
      <span>Active 24H Orders</span>
      <h3><?= $activeProduction ?></h3>
    </div>
    <div class="admin-kpi-icon icon-blue">
      <i class="fa-solid fa-bolt"></i>
    </div>
  </div>

  <div class="admin-kpi-card emerald">
    <div class="admin-kpi-data">
      <span>Doorstep Appointments</span>
      <h3><?= $totalApts ?></h3>
    </div>
    <div class="admin-kpi-icon icon-emerald">
      <i class="fa-solid fa-calendar-check"></i>
    </div>
  </div>

  <div class="admin-kpi-card purple">
    <div class="admin-kpi-data">
      <span>On-Duty Staff</span>
      <h3><?= count($deliveryBoys) + count($measExecutives) ?></h3>
    </div>
    <div class="admin-kpi-icon icon-purple">
      <i class="fa-solid fa-users-gear"></i>
    </div>
  </div>
</div>

<!-- 1. Live Orders & 24H SLA Dispatch Control (Full Width Card for Maximum Column Visibility) -->
<div class="admin-card" style="border-top: 4px solid var(--admin-gold); margin-bottom: 28px;">
  <div class="admin-card-header">
    <h3>
      <i class="fa-solid fa-box-open text-gold"></i> 
      Active 24-Hour Express Orders & Live Status Control
    </h3>
    <a href="<?= APP_URL ?>/admin/orders.php" style="font-size:12.5px; color:var(--admin-gold); text-decoration:none; font-weight:700;">
      View All Orders (<?= $totalOrders ?>) <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="min-width:140px;">Booking ID</th>
          <th style="min-width:180px;">Customer Details</th>
          <th style="min-width:150px;">Garment Service</th>
          <th style="min-width:160px;">Assigned Delivery Boy</th>
          <th style="min-width:210px;">Update Status</th>
          <th style="min-width:90px; text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="6" style="text-align:center; padding:36px; color:#64748B;">No active production orders yet.</td></tr>
        <?php else: ?>
          <?php foreach (array_slice($orders, 0, 8) as $o): ?>
            <tr>
              <td>
                <strong style="color:#0F172A; font-family:'Outfit'; font-size:14px;"><?= e($o['booking_id']) ?></strong>
                <div style="font-size:11.5px; color:#64748B; margin-top:2px;">
                  <i class="fa-solid fa-clock text-gold"></i> <?= date('d M, h:i A', strtotime($o['created_at'])) ?>
                </div>
              </td>
              <td>
                <strong style="font-size:13.5px; color:#0F172A;"><?= e($o['customer_name']) ?></strong>
                <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-phone"></i> <?= e($o['customer_mobile']) ?></div>
                <div style="font-size:11.5px; color:#94A3B8;"><i class="fa-solid fa-location-dot"></i> <?= e($o['area']) ?>, <?= e($o['pincode']) ?></div>
              </td>
              <td>
                <strong style="font-weight:700; font-size:13.5px; color:#0F172A;"><?= e($o['service_name']) ?></strong>
                <div style="font-size:12px; color:var(--accent-emerald); font-weight:800; margin-top:2px;"><?= formatPrice($o['total_amount']) ?> (Paid)</div>
              </td>
              <td>
                <form action="<?= APP_URL ?>/admin/index.php" method="POST" style="margin:0;">
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <input type="hidden" name="new_status" value="<?= $o['order_status'] ?>">
                  <select name="delivery_boy_id" onchange="this.form.submit()" style="font-size:12.5px; font-weight:600; padding:6px 10px; border-radius:6px; border:1px solid #CBD5E1; background:#F8FAFC; width:100%; max-width:160px;">
                    <option value="">-- Assign Rider --</option>
                    <?php foreach ($deliveryBoys as $db): ?>
                      <option value="<?= $db['id'] ?>" <?= $o['assigned_delivery_id'] == $db['id'] ? 'selected' : '' ?>>
                        🚴 <?= e($db['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td>
                <form action="<?= APP_URL ?>/admin/index.php" method="POST" style="margin:0;">
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <input type="hidden" name="delivery_boy_id" value="<?= $o['assigned_delivery_id'] ?>">
                  <select name="new_status" onchange="this.form.submit()" style="font-size:12.5px; font-weight:700; padding:6px 12px; border-radius:6px; border:1px solid #CBD5E1; background:<?= $o['order_status'] === 'DELIVERED' ? '#DCFCE7' : ($o['order_status'] === 'OUT_FOR_DELIVERY' ? '#FEF3C7' : '#EFF6FF') ?>; color:#0F172A; width:100%; max-width:210px;">
                    <option value="ORDER_CONFIRMED" <?= $o['order_status'] === 'ORDER_CONFIRMED' ? 'selected' : '' ?>>1. Order Confirmed</option>
                    <option value="MEASUREMENT_COMPLETED" <?= $o['order_status'] === 'MEASUREMENT_COMPLETED' ? 'selected' : '' ?>>2. Measurement Done</option>
                    <option value="CUTTING_IN_PROGRESS" <?= $o['order_status'] === 'CUTTING_IN_PROGRESS' ? 'selected' : '' ?>>3. Fabric Cutting</option>
                    <option value="STITCHING_IN_PROGRESS" <?= $o['order_status'] === 'STITCHING_IN_PROGRESS' ? 'selected' : '' ?>>4. Stitching</option>
                    <option value="QUALITY_CHECK_PASSED" <?= $o['order_status'] === 'QUALITY_CHECK_PASSED' ? 'selected' : '' ?>>5. QC Passed</option>
                    <option value="READY_FOR_DISPATCH" <?= $o['order_status'] === 'READY_FOR_DISPATCH' ? 'selected' : '' ?>>6. Packed & Ready</option>
                    <option value="OUT_FOR_DELIVERY" <?= $o['order_status'] === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>7. Out For Delivery</option>
                    <option value="DELIVERED" <?= $o['order_status'] === 'DELIVERED' ? 'selected' : '' ?>>8. Delivered (Signed)</option>
                  </select>
                </form>
              </td>
              <td style="text-align:right;">
                <a href="<?= APP_URL ?>/track.php?booking_id=<?= urlencode($o['booking_id']) ?>" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-size:11.5px; padding:6px 12px; border-radius:6px; font-weight:700;" title="Live Customer Tracking">
                  <i class="fa-solid fa-location-crosshairs"></i> Track
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 2. Dual Panel: Doorstep Appointments & Field Personnel Grid -->
<div class="admin-grid-responsive">
  
  <!-- Left: Doorstep Appointments & Measurement Converter -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-calendar-check text-gold"></i>
        Doorstep Appointments (<?= count($appointments) ?>)
      </h3>
      <a href="<?= APP_URL ?>/admin/appointments.php" style="font-size:12.5px; color:var(--admin-gold); text-decoration:none; font-weight:700;">
        View All <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <div class="admin-card-body" style="padding:18px;">
      <?php if (empty($appointments)): ?>
        <p style="text-align:center; color:#64748B; padding:24px 0;">No appointments booked yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:14px;">
          <?php foreach (array_slice($appointments, 0, 4) as $apt): ?>
            <div style="border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:16px; background:#F8FAFC;">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                <div>
                  <strong style="color:var(--admin-gold); font-size:13px; font-family:'Outfit';"><?= e($apt['appointment_code']) ?></strong>
                  <h4 style="font-size:14.5px; margin:2px 0 0; color:#0F172A;"><?= e($apt['customer_name']) ?> (<?= e($apt['customer_mobile']) ?>)</h4>
                </div>
                <span class="badge-status <?= strtolower($apt['status']) === 'completed' ? 'delivered' : 'booked' ?>" style="font-size:10.5px;">
                  <?= str_replace('_', ' ', $apt['status']) ?>
                </span>
              </div>

              <div style="font-size:12.5px; color:#475569; margin-bottom:8px;">
                <i class="fa-solid fa-scissors"></i> <strong><?= e($apt['service_name']) ?></strong> | <i class="fa-solid fa-clock text-gold"></i> <?= date('d M', strtotime($apt['appointment_date'])) ?> (<?= e($apt['time_slot']) ?>)
              </div>

              <div style="font-size:12px; color:#64748B; margin-bottom:12px;">
                <i class="fa-solid fa-location-dot" style="color:#EF4444;"></i> <?= e($apt['house_no']) ?>, <?= e($apt['street']) ?>, <?= e($apt['area']) ?>
              </div>

              <?php if ($apt['status'] !== 'MEASUREMENT_COMPLETED'): ?>
                <!-- Quick Measurement & Convert Accordion Trigger -->
                <details style="background:#FFFFFF; border:1px solid #CBD5E1; border-radius:6px; padding:10px 12px; font-size:12.5px;">
                  <summary style="cursor:pointer; font-weight:700; color:#0F172A;">
                    <i class="fa-solid fa-ruler-combined text-gold"></i> Input Measurements & Convert to 24H Order
                  </summary>
                  <form action="<?= APP_URL ?>/admin/index.php" method="POST" style="margin-top:14px;">
                    <input type="hidden" name="action" value="convert_apt_to_order">
                    <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                    <input type="hidden" name="garment_type" value="<?= e($apt['service_name']) ?>">

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                      <div>
                        <label style="font-size:11px; font-weight:700; color:#475569;">Chest (in):</label>
                        <input type="number" step="0.1" name="m_chest" value="40.0" style="width:100%; padding:6px; font-size:12.5px; border:1px solid #CBD5E1; border-radius:4px;" required>
                      </div>
                      <div>
                        <label style="font-size:11px; font-weight:700; color:#475569;">Waist (in):</label>
                        <input type="number" step="0.1" name="m_waist" value="34.0" style="width:100%; padding:6px; font-size:12.5px; border:1px solid #CBD5E1; border-radius:4px;" required>
                      </div>
                      <div>
                        <label style="font-size:11px; font-weight:700; color:#475569;">Length (in):</label>
                        <input type="number" step="0.1" name="m_length" value="30.0" style="width:100%; padding:6px; font-size:12.5px; border:1px solid #CBD5E1; border-radius:4px;" required>
                      </div>
                      <div>
                        <label style="font-size:11px; font-weight:700; color:#475569;">Fit:</label>
                        <select name="fit_preference" style="width:100%; padding:6px; font-size:12.5px; border:1px solid #CBD5E1; border-radius:4px;">
                          <option value="Slim Fit">Slim Fit</option>
                          <option value="Regular Fit" selected>Regular Fit</option>
                          <option value="Comfort / Loose Fit">Comfort Fit</option>
                        </select>
                      </div>
                    </div>

                    <button type="submit" class="btn btn-sm" style="width:100%; background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:6px; font-size:12.5px; padding:8px 0;">
                      <i class="fa-solid fa-bolt"></i> Create Order & Start 24H SLA
                    </button>
                  </form>
                </details>
              <?php else: ?>
                <div style="font-size:12px; color:var(--accent-emerald); font-weight:700;">
                  <i class="fa-solid fa-check-double"></i> Measurements Recorded & Moved to Production
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Active Staff On Duty & Quick Actions -->
  <div>
    <div class="admin-card" style="margin-bottom:24px;">
      <div class="admin-card-header">
        <h3><i class="fa-solid fa-motorcycle text-gold"></i> Active Delivery Boys</h3>
        <a href="<?= APP_URL ?>/admin/staff.php" style="font-size:12.5px; color:var(--admin-gold); text-decoration:none; font-weight:700;">Manage Staff</a>
      </div>
      <div class="admin-card-body" style="padding:18px;">
        <div style="display:flex; flex-direction:column; gap:12px;">
          <?php foreach ($deliveryBoys as $db): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#F8FAFC; border-radius:var(--admin-radius-sm); border:1px solid var(--admin-border);">
              <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#0F172A; color:var(--admin-gold); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800;">
                  <?= strtoupper(substr($db['name'], 0, 1)) ?>
                </div>
                <div>
                  <strong style="font-size:13.5px; color:#0F172A;"><?= e($db['name']) ?></strong>
                  <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-phone"></i> <?= e($db['mobile']) ?></div>
                </div>
              </div>
              <span class="badge-status delivered" style="font-size:10.5px;">On Duty</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Quick Shortcuts Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3><i class="fa-solid fa-sliders text-gold"></i> Quick Management Hub</h3>
      </div>
      <div class="admin-card-body" style="padding:18px; display:flex; flex-direction:column; gap:10px;">
        <a href="<?= APP_URL ?>/admin/website-content.php" class="btn btn-sm" style="background:#0F172A; color:#FFFFFF; font-weight:700; text-align:left; justify-content:flex-start; padding:10px 14px;">
          <i class="fa-solid fa-desktop text-gold" style="margin-right:8px;"></i> Edit Website Headlines & CMS Content
        </a>
        <a href="<?= APP_URL ?>/admin/testimonials.php" class="btn btn-sm" style="background:#F8FAFC; border:1px solid #CBD5E1; color:#0F172A; font-weight:700; text-align:left; justify-content:flex-start; padding:10px 14px;">
          <i class="fa-solid fa-comments text-gold" style="margin-right:8px;"></i> Upload Customer Reviews & Photos
        </a>
        <a href="<?= APP_URL ?>/admin/services.php" class="btn btn-sm" style="background:#F8FAFC; border:1px solid #CBD5E1; color:#0F172A; font-weight:700; text-align:left; justify-content:flex-start; padding:10px 14px;">
          <i class="fa-solid fa-scissors text-gold" style="margin-right:8px;"></i> Add / Edit Garments & Pricing
        </a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-sm" style="background:#F8FAFC; border:1px solid #CBD5E1; color:#0F172A; font-weight:700; text-align:left; justify-content:flex-start; padding:10px 14px;">
          <i class="fa-solid fa-credit-card text-gold" style="margin-right:8px;"></i> Cashfree PG Keys & COD Option
        </a>
      </div>
    </div>
  </div>

</div>

<!-- Modal: Add New Staff Member -->
<div id="addStaffModal" style="display:none; position:fixed; inset:0; background:rgba(10,17,40,0.7); backdrop-filter:blur(4px); z-index:999; align-items:center; justify-content:center; padding:16px;">
  <div class="card" style="background:#FFFFFF; border-radius:var(--admin-radius); max-width:480px; width:100%; max-height:90vh; overflow-y:auto; padding:24px 20px; box-shadow:var(--shadow-lg); border: 2px solid var(--admin-gold);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <h3 style="font-family:'Outfit'; font-size:20px; font-weight:800; color:#0F172A; margin:0;">
        <i class="fa-solid fa-user-plus text-gold"></i> Add New Staff Member
      </h3>
      <button type="button" onclick="document.getElementById('addStaffModal').style.display='none';" style="background:none; border:none; font-size:20px; color:#64748B; cursor:pointer;">&times;</button>
    </div>

    <form action="<?= APP_URL ?>/admin/index.php" method="POST">
      <input type="hidden" name="action" value="add_staff">
      
      <div style="margin-bottom:14px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Full Name *</label>
        <input type="text" name="staff_name" class="form-control" placeholder="e.g. Vikram Verma" required>
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Mobile Number *</label>
        <input type="tel" name="staff_mobile" class="form-control" placeholder="e.g. 9811223344" required>
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Staff Role *</label>
        <select name="staff_role" class="form-control form-select" required>
          <option value="delivery_executive">🚴 Delivery Boy (Rider & Handover)</option>
          <option value="measurement_executive">📏 Measurement Executive (Doorstep Visit)</option>
        </select>
      </div>

      <div style="margin-bottom:20px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Login Password</label>
        <input type="text" name="staff_password" class="form-control" value="password123">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="document.getElementById('addStaffModal').style.display='none';" class="btn btn-sm" style="background:#F1F5F9; color:#475569; font-weight:700;">Cancel</button>
        <button type="submit" class="btn btn-sm" style="background:var(--admin-gold); color:#0F172A; font-weight:800;"><i class="fa-solid fa-plus"></i> Save Staff Member</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
