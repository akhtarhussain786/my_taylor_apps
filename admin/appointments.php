<?php
$pageTitle = "Doorstep Appointments";
require_once __DIR__ . '/includes/admin_header.php';

$executives = $pdo->query("SELECT * FROM `users` WHERE `role` = 'measurement_executive' AND `status` = 'active'")->fetchAll();

// Handle Reassignment and Deletion
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_exec'])) {
        $aptId = (int)$_POST['appointment_id'];
        $newExecId = (int)$_POST['executive_id'];
        $pdo->prepare("UPDATE `appointments` SET `executive_id` = ?, `status` = 'EXECUTIVE_ASSIGNED' WHERE `id` = ?")->execute([$newExecId, $aptId]);
        logAudit(null, $aptId, $currentUser['id'], 'APPOINTMENT_DISPATCHED', null, 'EXECUTIVE_ASSIGNED', "Admin assigned executive #{$newExecId} to appointment #{$aptId}");
        $msg = "Executive assigned successfully!";
    }

    if (isset($_POST['delete_appointment'])) {
        $aptId = (int)$_POST['appointment_id'];
        $code = $pdo->query("SELECT `appointment_code` FROM `appointments` WHERE `id` = {$aptId}")->fetchColumn();
        
        $pdo->prepare("UPDATE `orders` SET `appointment_id` = NULL WHERE `appointment_id` = ?")->execute([$aptId]);
        $pdo->prepare("DELETE FROM `appointments` WHERE `id` = ?")->execute([$aptId]);
        
        logAudit(null, $aptId, $currentUser['id'], 'APPOINTMENT_DELETED', null, 'DELETED', "Admin deleted appointment {$code} (#{$aptId})");
        $msg = "Appointment '{$code}' successfully deleted!";
    }
}

$apts = $pdo->query("
  SELECT a.*, s.name as service_name,
         u.name as customer_name, u.mobile as customer_mobile,
         exec_u.name as exec_name,
         addr.house_no, addr.street, addr.area, addr.city, addr.pincode
  FROM `appointments` a
  JOIN `services` s ON a.service_id = s.id
  JOIN `users` u ON a.customer_id = u.id
  JOIN `addresses` addr ON a.address_id = addr.id
  LEFT JOIN `users` exec_u ON a.executive_id = exec_u.id
  ORDER BY a.appointment_date DESC, a.id DESC
")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Doorstep Measurement Appointments</h1>
    <p>Manage customer appointments, auto/manual assign specialist executives, and track field visits.</p>
  </div>
  <div>
    <span class="admin-badge-count admin-badge-gold" style="font-size:14px; padding:6px 14px;">Total Bookings: <?= count($apts) ?></span>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-circle-check"></i> <?= e($msg) ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3><i class="fa-solid fa-calendar-check text-gold"></i> Appointments Registry</h3>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Code & Slot</th>
          <th>Customer Details</th>
          <th>Garment Service</th>
          <th>Doorstep Address</th>
          <th>Assigned Executive</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($apts as $a): ?>
          <tr>
            <td>
              <strong style="color:var(--admin-gold); font-family:'Outfit'; font-size:14px;"><?= e($a['appointment_code']) ?></strong>
              <div style="font-size:11.5px; color:#64748B;">
                <i class="fa-solid fa-calendar"></i> <?= date('d M Y', strtotime($a['appointment_date'])) ?><br>
                <i class="fa-solid fa-clock text-gold"></i> <?= e($a['time_slot']) ?>
              </div>
            </td>
            <td>
              <strong style="font-size:13.5px; color:#0F172A;"><?= e($a['customer_name']) ?></strong>
              <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-phone"></i> <?= e($a['customer_mobile']) ?></div>
            </td>
            <td>
              <strong style="font-size:13.5px; color:#0F172A;"><?= e($a['service_name']) ?></strong>
              <div style="font-size:11px; color:#B45309; font-weight:700;"><i class="fa-solid fa-bolt text-gold"></i> <?= e($a['delivery_preference']) ?></div>
            </td>
            <td>
              <div style="font-size:12.5px; color:#334155; line-height:1.4;">
                <i class="fa-solid fa-location-dot" style="color:#EF4444;"></i> <?= e($a['house_no']) ?>, <?= e($a['street']) ?><br>
                <span style="color:#64748B;"><?= e($a['area']) ?>, <?= e($a['city']) ?> - <?= e($a['pincode']) ?></span>
              </div>
            </td>
            <td>
              <form action="<?= APP_URL ?>/admin/appointments.php" method="POST" style="margin:0;">
                <input type="hidden" name="assign_exec" value="1">
                <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                <select name="executive_id" onchange="this.form.submit()" style="font-size:12px; padding:6px 10px; border-radius:6px; border:1px solid #CBD5E1; background:#F8FAFC;">
                  <option value="">-- Assign Specialist --</option>
                  <?php foreach ($executives as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= $a['executive_id'] == $ex['id'] ? 'selected' : '' ?>>
                      📏 <?= e($ex['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <span class="badge-status <?= strtolower($a['status']) === 'measurement_completed' ? 'delivered' : 'booked' ?>">
                <?= str_replace('_', ' ', $a['status']) ?>
              </span>
            </td>
            <td style="text-align:right; white-space:nowrap;">
              <div style="display:inline-flex; gap:6px; align-items:center;">
                <a href="<?= APP_URL ?>/portal/executive.php" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-size:11.5px; padding:6px 10px; border-radius:6px; text-decoration:none;">
                  <i class="fa-solid fa-ruler"></i> Measurement Pad
                </a>
                <form action="<?= APP_URL ?>/admin/appointments.php" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Kya aap sach me appointment <?= e($a['appointment_code']) ?> ko delete karna chahte hain?');">
                  <input type="hidden" name="delete_appointment" value="1">
                  <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background:#FEE2E2; color:#DC2626; border:1px solid #FCA5A5; font-size:11.5px; padding:6px 10px; border-radius:6px; cursor:pointer;" title="Delete Appointment">
                    <i class="fa-solid fa-trash-can"></i> Delete
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
