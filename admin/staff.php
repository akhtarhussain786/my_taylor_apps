<?php
$pageTitle = "Staff & Delivery Boys Management";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
$error = null;

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE NEW STAFF
    if ($action === 'create_staff') {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'delivery_executive';
        $password = $_POST['password'] ?? 'password123';

        if (empty($name) || empty($mobile)) {
            $error = "Name and Mobile number are mandatory.";
        } else {
            if (empty($email)) {
                $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . rand(100, 999) . '@mytaylor.local';
            }

            $chk = $pdo->prepare("SELECT `id` FROM `users` WHERE `mobile` = ? OR `email` = ?");
            $chk->execute([$mobile, $email]);
            if ($chk->fetch()) {
                $error = "A staff member with this mobile number or email already exists.";
            } else {
                $hash = password_hash($password ?: 'password123', PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `mobile`, `password_hash`, `role`, `status`) VALUES (?, ?, ?, ?, ?, 'active')");
                $ins->execute([$name, $email, $mobile, $hash, $role]);
                $msg = "New staff member '{$name}' created successfully!";
            }
        }
    }

    // 2. UPDATE / EDIT STAFF
    if ($action === 'update_staff') {
        $staffId = (int)$_POST['staff_id'];
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'delivery_executive';
        $status = $_POST['status'] ?? 'active';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($name) || empty($mobile)) {
            $error = "Name and Mobile number cannot be empty.";
        } else {
            $chk = $pdo->prepare("SELECT `id` FROM `users` WHERE (`mobile` = ? OR `email` = ?) AND `id` != ?");
            $chk->execute([$mobile, $email, $staffId]);
            if ($chk->fetch()) {
                $error = "Another staff member already has this mobile or email.";
            } else {
                if (!empty($newPassword)) {
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE `users` SET `name` = ?, `mobile` = ?, `email` = ?, `role` = ?, `status` = ?, `password_hash` = ? WHERE `id` = ? AND `role` != 'admin'");
                    $upd->execute([$name, $mobile, $email, $role, $status, $hash, $staffId]);
                } else {
                    $upd = $pdo->prepare("UPDATE `users` SET `name` = ?, `mobile` = ?, `email` = ?, `role` = ?, `status` = ? WHERE `id` = ? AND `role` != 'admin'");
                    $upd->execute([$name, $mobile, $email, $role, $status, $staffId]);
                }
                $msg = "Staff member '{$name}' updated successfully!";
            }
        }
    }

    // 3. DELETE STAFF
    if ($action === 'delete_staff') {
        $staffId = (int)$_POST['staff_id'];
        if ($staffId === $currentUser['id']) {
            $error = "You cannot delete your own admin account.";
        } else {
            $pdo->prepare("UPDATE `orders` SET `assigned_delivery_id` = NULL WHERE `assigned_delivery_id` = ?")->execute([$staffId]);
            $pdo->prepare("UPDATE `appointments` SET `executive_id` = NULL WHERE `executive_id` = ?")->execute([$staffId]);
            $del = $pdo->prepare("DELETE FROM `users` WHERE `id` = ? AND `role` != 'admin'");
            $del->execute([$staffId]);
            $msg = "Staff member deleted from the system.";
        }
    }

    // 4. TOGGLE STATUS
    if ($action === 'toggle_status') {
        $staffId = (int)$_POST['staff_id'];
        $pdo->prepare("UPDATE `users` SET `status` = IF(`status` = 'active', 'inactive', 'active') WHERE `id` = ? AND `role` != 'admin'")->execute([$staffId]);
        $msg = "Staff active status toggled!";
    }
}

// Fetch Staff to Edit
$editStaff = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? AND `role` != 'admin'");
    $stmt->execute([$editId]);
    $editStaff = $stmt->fetch();
}

$staffMembers = $pdo->query("
  SELECT u.*,
         (SELECT COUNT(*) FROM `orders` WHERE `assigned_delivery_id` = u.id) as total_deliveries,
         (SELECT COUNT(*) FROM `appointments` WHERE `executive_id` = u.id) as total_appointments
  FROM `users` u
  WHERE u.role IN ('delivery_executive', 'measurement_executive', 'master_tailor', 'qc_inspector')
  ORDER BY u.id DESC
")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Staff Personnel & Field Force</h1>
    <p>Manage Delivery Boys (Riders), Doorstep Measurement Executives, and Workshop Tailors.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="<?= APP_URL ?>/portal/delivery.php" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-weight:700; border-radius:8px;">
      <i class="fa-solid fa-motorcycle"></i> Open Rider App View
    </a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-circle-check"></i> <?= e($msg) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="card" style="background:#FEF2F2; border:1px solid #F87171; color:#991B1B; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
  </div>
<?php endif; ?>

<!-- ADD / EDIT STAFF FORM -->
<div class="admin-card" style="border-top: 4px solid <?= $editStaff ? 'var(--accent-amber)' : 'var(--admin-gold)' ?>;">
  <div class="admin-card-header">
    <h3>
      <?php if ($editStaff): ?>
        <i class="fa-solid fa-user-pen text-gold"></i> Edit Personnel: <span style="color:var(--admin-gold);"><?= e($editStaff['name']) ?></span>
      <?php else: ?>
        <i class="fa-solid fa-user-plus text-gold"></i> Add New Delivery Boy / Measurement Executive
      <?php endif; ?>
    </h3>
    <?php if ($editStaff): ?>
      <a href="<?= APP_URL ?>/admin/staff.php" class="btn btn-sm" style="background:#F1F5F9; color:#475569; font-weight:700; border-radius:6px;">
        <i class="fa-solid fa-xmark"></i> Cancel Edit Mode
      </a>
    <?php endif; ?>
  </div>

  <div class="admin-card-body">
    <form action="<?= APP_URL ?>/admin/staff.php" method="POST">
      <input type="hidden" name="action" value="<?= $editStaff ? 'update_staff' : 'create_staff' ?>">
      <?php if ($editStaff): ?>
        <input type="hidden" name="staff_id" value="<?= $editStaff['id'] ?>">
      <?php endif; ?>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:16px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Full Name *</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Rohit Kumar" value="<?= e($editStaff['name'] ?? '') ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Mobile Number *</label>
          <input type="tel" name="mobile" class="form-control" placeholder="e.g. 9800000006" value="<?= e($editStaff['mobile'] ?? '') ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="e.g. rohit@mytaylor.local" value="<?= e($editStaff['email'] ?? '') ?>">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Operational Role *</label>
          <select name="role" class="form-control form-select" required>
            <option value="delivery_executive" <?= ($editStaff && $editStaff['role'] === 'delivery_executive') ? 'selected' : '' ?>>🚴 Delivery Boy (Express Courier)</option>
            <option value="measurement_executive" <?= ($editStaff && $editStaff['role'] === 'measurement_executive') ? 'selected' : '' ?>>📏 Doorstep Measurement Executive</option>
            <option value="master_tailor" <?= ($editStaff && $editStaff['role'] === 'master_tailor') ? 'selected' : '' ?>>✂️ Master Tailor (Cutting/Stitching)</option>
            <option value="qc_inspector" <?= ($editStaff && $editStaff['role'] === 'qc_inspector') ? 'selected' : '' ?>>🔍 QC Inspector</option>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;"><?= $editStaff ? 'Change Password (Optional)' : 'Login Password' ?></label>
          <input type="text" name="<?= $editStaff ? 'new_password' : 'password' ?>" class="form-control" placeholder="<?= $editStaff ? 'Leave blank to keep same' : 'password123' ?>" value="<?= $editStaff ? '' : 'password123' ?>">
        </div>

        <?php if ($editStaff): ?>
          <div>
            <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Duty Status</label>
            <select name="status" class="form-control form-select">
              <option value="active" <?= $editStaff['status'] === 'active' ? 'selected' : '' ?>>Active (On-Duty)</option>
              <option value="inactive" <?= $editStaff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Off-Duty)</option>
            </select>
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:8px; padding:10px 24px;">
          <i class="fa-solid fa-user-check"></i> <?= $editStaff ? 'Update Staff Member' : 'Create & Onboard Staff' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- STAFF TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>
      <i class="fa-solid fa-users text-gold"></i>
      Active Personnel Directory (<?= count($staffMembers) ?>)
    </h3>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Personnel</th>
          <th>Role</th>
          <th>Contact Info</th>
          <th>Workload / Deliveries</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staffMembers as $u): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#0F172A; color:var(--admin-gold); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;">
                  <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                  <strong style="color:#0F172A; font-size:14px;"><?= e($u['name']) ?></strong>
                  <div style="font-size:11px; color:#64748B;">Staff ID #<?= $u['id'] ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role'] === 'delivery_executive'): ?>
                <span class="badge-status out"><i class="fa-solid fa-motorcycle"></i> Delivery Boy</span>
              <?php elseif ($u['role'] === 'measurement_executive'): ?>
                <span class="badge-status booked"><i class="fa-solid fa-ruler-combined"></i> Measurement Exec</span>
              <?php else: ?>
                <span class="badge-status stitching"><i class="fa-solid fa-scissors"></i> <?= e($u['role']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="font-size:13px; color:#0F172A;"><i class="fa-solid fa-phone"></i> <?= e($u['mobile']) ?></strong>
              <div style="font-size:11.5px; color:#64748B;"><i class="fa-solid fa-envelope"></i> <?= e($u['email']) ?></div>
            </td>
            <td>
              <?php if ($u['role'] === 'delivery_executive'): ?>
                <strong style="color:#0F172A; font-size:13px;"><?= (int)$u['total_deliveries'] ?> Deliveries</strong>
              <?php else: ?>
                <strong style="color:#0F172A; font-size:13px;"><?= (int)$u['total_appointments'] ?> Appointments</strong>
              <?php endif; ?>
            </td>
            <td>
              <form action="<?= APP_URL ?>/admin/staff.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                <button type="submit" class="badge-status <?= $u['status'] === 'active' ? 'delivered' : 'qc' ?>" style="border:none; cursor:pointer;" title="Click to toggle status">
                  <?= strtoupper($u['status']) ?>
                </button>
              </form>
            </td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:8px;">
                <a href="<?= APP_URL ?>/admin/staff.php?edit_id=<?= $u['id'] ?>" class="btn btn-sm" style="background:#0F172A; color:#FFFFFF; font-size:12px; padding:6px 12px; border-radius:6px;">
                  <i class="fa-solid fa-pen text-gold"></i> Edit
                </a>

                <form action="<?= APP_URL ?>/admin/staff.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member?');" style="margin:0;">
                  <input type="hidden" name="action" value="delete_staff">
                  <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background:#FEE2E2; color:#DC2626; font-size:12px; padding:6px 12px; border-radius:6px; border:none; cursor:pointer;">
                    <i class="fa-solid fa-trash"></i> Delete
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
