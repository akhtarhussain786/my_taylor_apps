<?php
$pageTitle = "Garments & Pricing Catalog";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
$error = null;

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE NEW SERVICE
    if ($action === 'create_service') {
        $category = $_POST['category'] ?? 'men';
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $basePrice = (float)($_POST['base_price'] ?? 599.00);
        $expressPrice = (float)($_POST['express_price'] ?? 199.00);
        $measFee = (float)($_POST['measurement_fee'] ?? 99.00);
        $hours = (int)($_POST['estimated_hours'] ?? 24);

        if (empty($name)) {
            $error = "Garment service name is required.";
        } else {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . rand(10, 99);
            $ins = $pdo->prepare("
              INSERT INTO `services` (`category`, `name`, `slug`, `description`, `base_price`, `express_price`, `measurement_fee`, `is_24h_eligible`, `estimated_hours`, `status`)
              VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'active')
            ");
            $ins->execute([$category, $name, $slug, $description, $basePrice, $expressPrice, $measFee, $hours]);
            logAudit(null, null, $currentUser['id'], 'SERVICE_CREATED', null, 'ACTIVE', "Admin added new service: {$name} (₹{$basePrice})");
            $msg = "New garment service '{$name}' created successfully!";
        }
    }

    // 2. UPDATE / EDIT SERVICE
    if ($action === 'update_service') {
        $serviceId = (int)$_POST['service_id'];
        $category = $_POST['category'] ?? 'men';
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $basePrice = (float)($_POST['base_price'] ?? 599.00);
        $expressPrice = (float)($_POST['express_price'] ?? 199.00);
        $measFee = (float)($_POST['measurement_fee'] ?? 99.00);
        $hours = (int)($_POST['estimated_hours'] ?? 24);
        $status = $_POST['status'] ?? 'active';

        if (empty($name)) {
            $error = "Service name cannot be empty.";
        } else {
            $upd = $pdo->prepare("
              UPDATE `services` 
              SET `category` = ?, `name` = ?, `description` = ?, `base_price` = ?, `express_price` = ?, `measurement_fee` = ?, `estimated_hours` = ?, `status` = ?
              WHERE `id` = ?
            ");
            $upd->execute([$category, $name, $description, $basePrice, $expressPrice, $measFee, $hours, $status, $serviceId]);
            logAudit(null, null, $currentUser['id'], 'SERVICE_UPDATED', null, $status, "Admin updated service #{$serviceId}: {$name}");
            $msg = "Garment service '{$name}' updated successfully!";
        }
    }

    // 3. DELETE SERVICE
    if ($action === 'delete_service') {
        $serviceId = (int)$_POST['service_id'];
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `service_id` = ?");
        $chk->execute([$serviceId]);
        $orderCount = (int)$chk->fetchColumn();

        if ($orderCount > 0) {
            $pdo->prepare("UPDATE `services` SET `status` = 'inactive' WHERE `id` = ?")->execute([$serviceId]);
            $msg = "Service has existing order history, so it was set to Inactive (hidden from booking dropdown).";
        } else {
            $pdo->prepare("DELETE FROM `services` WHERE `id` = ?")->execute([$serviceId]);
            $msg = "Garment service deleted permanently.";
        }
    }

    // 4. TOGGLE STATUS
    if ($action === 'toggle_status') {
        $serviceId = (int)$_POST['service_id'];
        $pdo->prepare("UPDATE `services` SET `status` = IF(`status` = 'active', 'inactive', 'active') WHERE `id` = ?")->execute([$serviceId]);
        $msg = "Service status toggled!";
    }
}

// Fetch Service to Edit if requested
$editService = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM `services` WHERE `id` = ?");
    $stmt->execute([$editId]);
    $editService = $stmt->fetch();
}

$services = $pdo->query("SELECT * FROM `services` ORDER BY `category`, `id` ASC")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Garments & Pricing Catalog</h1>
    <p>Manage all apparel items, tailoring charges, and 24H express fees synced with the booking dropdown.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="<?= APP_URL ?>/book.php" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-weight:700; border-radius:8px;">
      <i class="fa-solid fa-eye"></i> View Live Dropdown
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

<!-- ADD / EDIT SERVICE FORM -->
<div class="admin-card" style="border-top: 4px solid <?= $editService ? 'var(--accent-amber)' : 'var(--admin-gold)' ?>;">
  <div class="admin-card-header">
    <h3>
      <?php if ($editService): ?>
        <i class="fa-solid fa-pen-to-square text-gold"></i> Edit Garment: <span style="color:var(--admin-gold);"><?= e($editService['name']) ?></span>
      <?php else: ?>
        <i class="fa-solid fa-plus-circle text-gold"></i> Add New Garment to Booking Dropdown
      <?php endif; ?>
    </h3>
    <?php if ($editService): ?>
      <a href="<?= APP_URL ?>/admin/services.php" class="btn btn-sm" style="background:#F1F5F9; color:#475569; font-weight:700; border-radius:6px;">
        <i class="fa-solid fa-xmark"></i> Cancel Edit Mode
      </a>
    <?php endif; ?>
  </div>

  <div class="admin-card-body">
    <form action="<?= APP_URL ?>/admin/services.php" method="POST">
      <input type="hidden" name="action" value="<?= $editService ? 'update_service' : 'create_service' ?>">
      <?php if ($editService): ?>
        <input type="hidden" name="service_id" value="<?= $editService['id'] ?>">
      <?php endif; ?>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:16px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Garment Category *</label>
          <select name="category" class="form-control form-select" required>
            <option value="men" <?= ($editService && $editService['category'] === 'men') ? 'selected' : '' ?>>👔 MEN'S WEAR</option>
            <option value="women" <?= ($editService && $editService['category'] === 'women') ? 'selected' : '' ?>>👗 WOMEN'S ATELIER</option>
            <option value="custom" <?= ($editService && $editService['category'] === 'custom') ? 'selected' : '' ?>>✨ CUSTOM TAILORING</option>
            <option value="alteration" <?= ($editService && $editService['category'] === 'alteration') ? 'selected' : '' ?>>✂️ ALTERATION</option>
          </select>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Garment Item Name *</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Bespoke Tuxedo / Formal Shirt" value="<?= e($editService['name'] ?? '') ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Base Tailoring Charge (₹) *</label>
          <input type="number" step="1" name="base_price" class="form-control" placeholder="e.g. 599" value="<?= e($editService['base_price'] ?? '599') ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">24H Express Fee (₹) *</label>
          <input type="number" step="1" name="express_price" class="form-control" placeholder="e.g. 199" value="<?= e($editService['express_price'] ?? '199') ?>" required>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Doorstep Measurement Fee (₹)</label>
          <input type="number" step="1" name="measurement_fee" class="form-control" value="<?= e($editService['measurement_fee'] ?? '99') ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Turnaround (Hours)</label>
          <input type="number" name="estimated_hours" class="form-control" value="<?= e($editService['estimated_hours'] ?? '24') ?>" required>
        </div>

        <?php if ($editService): ?>
          <div>
            <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Visibility Status</label>
            <select name="status" class="form-control form-select">
              <option value="active" <?= $editService['status'] === 'active' ? 'selected' : '' ?>>Active (Visible in Dropdown)</option>
              <option value="inactive" <?= $editService['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
            </select>
          </div>
        <?php else: ?>
          <div>
            <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Short Description</label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Handcrafted tailored fit with luxury finishing.">
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:8px; padding:10px 24px;">
          <i class="fa-solid fa-floppy-disk"></i> <?= $editService ? 'Update Garment Service' : 'Save & Publish to Dropdown' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- SERVICES TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>
      <i class="fa-solid fa-list-check text-gold"></i>
      Active Garments in Customer Dropdown (<?= count($services) ?>)
    </h3>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Category</th>
          <th>Garment Name</th>
          <th>Base Price</th>
          <th>Express Fee</th>
          <th>Turnaround</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td>
              <span class="badge-status <?= $s['category'] === 'men' ? 'booked' : ($s['category'] === 'women' ? 'stitching' : 'confirmed') ?>">
                <?= strtoupper($s['category']) ?>
              </span>
            </td>
            <td>
              <strong style="color:#0F172A; font-size:14px;"><?= e($s['name']) ?></strong>
              <?php if (!empty($s['description'])): ?>
                <div style="font-size:11.5px; color:#64748B;"><?= e($s['description']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color:var(--admin-gold); font-size:14px;"><?= formatPrice($s['base_price']) ?></strong>
            </td>
            <td>
              <span style="color:#B45309; font-weight:700; font-size:13px;">+<?= formatPrice($s['express_price']) ?></span>
            </td>
            <td>
              <span style="font-size:12.5px; font-weight:600;"><i class="fa-solid fa-bolt text-gold"></i> <?= (int)$s['estimated_hours'] ?>h</span>
            </td>
            <td>
              <form action="<?= APP_URL ?>/admin/services.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                <button type="submit" class="badge-status <?= $s['status'] === 'active' ? 'delivered' : 'qc' ?>" style="border:none; cursor:pointer;" title="Click to toggle active/inactive">
                  <?= strtoupper($s['status']) ?>
                </button>
              </form>
            </td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:8px;">
                <a href="<?= APP_URL ?>/admin/services.php?edit_id=<?= $s['id'] ?>" class="btn btn-sm" style="background:#0F172A; color:#FFFFFF; font-size:12px; padding:6px 12px; border-radius:6px;">
                  <i class="fa-solid fa-pen-to-square text-gold"></i> Edit
                </a>

                <form action="<?= APP_URL ?>/admin/services.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this garment?');" style="margin:0;">
                  <input type="hidden" name="action" value="delete_service">
                  <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
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
