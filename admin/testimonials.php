<?php
$pageTitle = "Manage Customer Testimonials & Reviews";
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDbConnection();
$message = null;
$error = null;

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../uploads/testimonials/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Actions: Add, Edit, Delete, Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['customer_name'] ?? '');
        $role = trim($_POST['customer_role'] ?? '');
        $location = trim($_POST['location'] ?? 'Mumbai');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $review = trim($_POST['review_text'] ?? '');
        $garment = trim($_POST['garment_type'] ?? 'Bespoke Suit');
        $order = (int)($_POST['display_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Handle Image Upload
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image_file']['name']);
            $targetPath = $uploadDir . $fileName;

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $mime = mime_content_type($fileTmp);

            if (in_array($mime, $allowedMimes)) {
                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $imageUrl = 'uploads/testimonials/' . $fileName;
                } else {
                    $error = "Failed to upload image file.";
                }
            } else {
                $error = "Invalid image type. Please upload JPG, PNG, or WebP.";
            }
        }

        if (empty($name) || empty($review)) {
            $error = "Please provide both Customer Name and Review Text.";
        } else if (!$error) {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO `testimonials` (`customer_name`, `customer_role`, `location`, `rating`, `review_text`, `image_url`, `garment_type`, `display_order`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $location, $rating, $review, $imageUrl, $garment, $order, $status]);
                $message = "Testimonial successfully created and published!";
            } else {
                $id = (int)$_POST['id'];
                if ($imageUrl) {
                    $stmt = $pdo->prepare("UPDATE `testimonials` SET `customer_name` = ?, `customer_role` = ?, `location` = ?, `rating` = ?, `review_text` = ?, `image_url` = ?, `garment_type` = ?, `display_order` = ?, `status` = ? WHERE `id` = ?");
                    $stmt->execute([$name, $role, $location, $rating, $review, $imageUrl, $garment, $order, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE `testimonials` SET `customer_name` = ?, `customer_role` = ?, `location` = ?, `rating` = ?, `review_text` = ?, `garment_type` = ?, `display_order` = ?, `status` = ? WHERE `id` = ?");
                    $stmt->execute([$name, $role, $location, $rating, $review, $garment, $order, $status, $id]);
                }
                $message = "Testimonial updated successfully!";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM `testimonials` WHERE `id` = ?")->execute([$id]);
        $message = "Testimonial removed successfully.";
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $curr = $_POST['current_status'] ?? 'active';
        $newStatus = ($curr === 'active') ? 'inactive' : 'active';
        $pdo->prepare("UPDATE `testimonials` SET `status` = ? WHERE `id` = ?")->execute([$newStatus, $id]);
        $message = "Status updated to " . strtoupper($newStatus);
    }
}

// Fetch edit item if requested
$editItem = null;
if (isset($_GET['edit_id'])) {
    $eStmt = $pdo->prepare("SELECT * FROM `testimonials` WHERE `id` = ?");
    $eStmt->execute([(int)$_GET['edit_id']]);
    $editItem = $eStmt->fetch();
}

// Fetch all testimonials
$testimonials = $pdo->query("SELECT * FROM `testimonials` ORDER BY `display_order` ASC, `id` DESC")->fetchAll();
?>

<?php if ($message): ?>
  <div style="background:#ECFDF5; border:1px solid #10B981; color:#065F46; padding:14px 18px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
    <i class="fa-solid fa-circle-check text-emerald" style="font-size:18px;"></i>
    <strong><?= e($message) ?></strong>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:#FEF2F2; border:1px solid #EF4444; color:#991B1B; padding:14px 18px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
    <i class="fa-solid fa-triangle-exclamation text-rose" style="font-size:18px;"></i>
    <strong><?= e($error) ?></strong>
  </div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
  <div>
    <h2 style="font-size:24px; font-weight:800; color:#0F172A; margin:0;">Customer Testimonials & Reviews</h2>
    <p style="color:#64748B; font-size:13.5px; margin:4px 0 0;">Manage luxury customer feedback, upload portraits, and control website testimonial carousel.</p>
  </div>
  <a href="<?= APP_URL ?>/index.php#testimonials" target="_blank" class="btn btn-gold-outline btn-sm">
    <i class="fa-solid fa-arrow-up-right-from-square"></i> Preview on Website
  </a>
</div>

<div style="display:grid; grid-template-columns: 1.1fr 1.9fr; gap:24px; align-items:start;">
  
  <!-- Form: Add or Edit Testimonial -->
  <div class="admin-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid #E2E8F0; padding-bottom:12px;">
      <h3 style="font-size:17px; font-weight:800; color:#0F172A; margin:0;">
        <?= $editItem ? '<i class="fa-solid fa-pen-to-square text-gold"></i> Edit Testimonial' : '<i class="fa-solid fa-plus-circle text-gold"></i> Add New Testimonial' ?>
      </h3>
      <?php if ($editItem): ?>
        <a href="<?= APP_URL ?>/admin/testimonials.php" class="btn btn-sm" style="background:#F1F5F9; color:#475569; font-weight:700; border-radius:6px;">Cancel</a>
      <?php endif; ?>
    </div>

    <form action="<?= APP_URL ?>/admin/testimonials.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
      <?php if ($editItem): ?>
        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Customer Name *</label>
        <input type="text" name="customer_name" class="form-control" required placeholder="e.g. Vikram Singhania" value="<?= e($editItem['customer_name'] ?? '') ?>">
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label">Role / Title</label>
          <input type="text" name="customer_role" class="form-control" placeholder="e.g. Managing Director" value="<?= e($editItem['customer_role'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">City / Location</label>
          <input type="text" name="location" class="form-control" placeholder="e.g. South Mumbai" value="<?= e($editItem['location'] ?? 'Mumbai') ?>">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label">Garment Type Tailored</label>
          <input type="text" name="garment_type" class="form-control" placeholder="e.g. 3-Piece Tuxedo" value="<?= e($editItem['garment_type'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Rating</label>
          <select name="rating" class="form-control form-select">
            <option value="5" <?= ($editItem['rating'] ?? 5) == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5 Stars - Exceptional)</option>
            <option value="4" <?= ($editItem['rating'] ?? 5) == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4 Stars - Great)</option>
            <option value="3" <?= ($editItem['rating'] ?? 5) == 3 ? 'selected' : '' ?>>⭐⭐⭐ (3 Stars)</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Review / Experience Text *</label>
        <textarea name="review_text" class="form-control" rows="4" required placeholder="Describe the doorstep fitting, 24-hour delivery, and fabric craftsmanship..."><?= e($editItem['review_text'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Customer Photo (Upload or Enter URL)</label>
        <?php if (!empty($editItem['image_url'])): ?>
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
            <img src="<?= APP_URL ?>/<?= ltrim($editItem['image_url'], '/') ?>" alt="Photo" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid var(--gold-primary);">
            <span style="font-size:12px; color:#64748B;">Current Image: <?= e($editItem['image_url']) ?></span>
          </div>
        <?php endif; ?>
        <input type="file" name="image_file" class="form-control" accept="image/*" style="margin-bottom:8px;">
        <input type="text" name="image_url" class="form-control" placeholder="Or enter image URL / assets path e.g. assets/images/testimonial_1.jpg" value="<?= e($editItem['image_url'] ?? '') ?>">
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label class="form-label">Display Order</label>
          <input type="number" name="display_order" class="form-control" value="<?= e($editItem['display_order'] ?? 0) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control form-select">
            <option value="active" <?= ($editItem['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible on Website)</option>
            <option value="inactive" <?= ($editItem['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-gold btn-block" style="font-weight:800; padding:12px; justify-content:center; width:100%;">
        <i class="fa-solid fa-floppy-disk"></i> <?= $editItem ? 'Save Changes' : 'Publish Testimonial' ?>
      </button>
    </form>
  </div>

  <!-- Table: List of Testimonials -->
  <div class="admin-card" style="padding:0; overflow:hidden;">
    <div style="padding:18px 24px; border-bottom:1px solid #E2E8F0; background:#FAFAFA; display:flex; justify-content:space-between; align-items:center;">
      <h3 style="font-size:16px; font-weight:800; color:#0F172A; margin:0;">
        <i class="fa-solid fa-comments text-gold"></i> Live Website Testimonials (<?= count($testimonials) ?>)
      </h3>
      <span class="badge-status badge-gold">Dynamic Website Feed</span>
    </div>

    <div style="overflow-x:auto;">
      <table class="admin-table" style="width:100%;">
        <thead>
          <tr>
            <th>Client</th>
            <th>Garment & Role</th>
            <th>Review Snippet</th>
            <th>Rating</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($testimonials as $t): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <?php if (!empty($t['image_url'])): ?>
                    <img src="<?= APP_URL ?>/<?= ltrim($t['image_url'], '/') ?>" alt="<?= e($t['customer_name']) ?>" style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid var(--gold-primary);" onerror="this.src='<?= APP_URL ?>/assets/images/hero_tailor.jpg'">
                  <?php else: ?>
                    <div style="width:42px; height:42px; border-radius:50%; background:#0B132B; color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;">
                      <?= strtoupper(substr($t['customer_name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <strong style="color:#0F172A; font-size:14px;"><?= e($t['customer_name']) ?></strong>
                    <div style="color:#64748B; font-size:12px;"><i class="fa-solid fa-location-dot text-gold"></i> <?= e($t['location']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <strong style="font-size:13px; color:#0F172A;"><?= e($t['garment_type']) ?></strong>
                <div style="font-size:12px; color:#64748B;"><?= e($t['customer_role']) ?></div>
              </td>
              <td>
                <p style="font-size:13px; color:#334155; margin:0; max-width:280px; line-height:1.5;">
                  "<?= e(substr($t['review_text'], 0, 95)) ?><?= strlen($t['review_text']) > 95 ? '...' : '' ?>"
                </p>
              </td>
              <td>
                <span style="color:#F59E0B; font-size:13px;">
                  <?= str_repeat('★', (int)$t['rating']) ?>
                </span>
              </td>
              <td>
                <form action="<?= APP_URL ?>/admin/testimonials.php" method="POST" style="margin:0;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $t['status'] ?>">
                  <button type="submit" class="badge-status <?= $t['status'] === 'active' ? 'badge-emerald' : 'badge-gold' ?>" style="cursor:pointer; border:none;">
                    <?= strtoupper($t['status']) ?>
                  </button>
                </form>
              </td>
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:6px;">
                  <a href="<?= APP_URL ?>/admin/testimonials.php?edit_id=<?= $t['id'] ?>" class="btn btn-sm" style="background:#0F172A; color:#FFFFFF; font-size:12px; padding:6px 12px; border-radius:6px;">
                    <i class="fa-solid fa-pen"></i> Edit
                  </a>
                  <form action="<?= APP_URL ?>/admin/testimonials.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this testimonial?');" style="margin:0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background:#FEE2E2; color:#DC2626; font-size:12px; padding:6px 10px; border-radius:6px; border:none; cursor:pointer;">
                      <i class="fa-solid fa-trash"></i>
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

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
