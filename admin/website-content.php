<?php
$pageTitle = "Website Content & CMS Control";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
$error = null;

// Handle Content Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'site_announcement',
        'hero_badge',
        'hero_headline',
        'hero_tagline',
        'about_title',
        'about_text',
        'promise_1_title',
        'promise_1_desc',
        'promise_2_title',
        'promise_2_desc',
        'promise_3_title',
        'promise_3_desc',
        'company_whatsapp',
        'company_phone',
        'company_email',
        'company_address'
    ];

    $settingsToSave = [];
    foreach ($keys as $k) {
        $settingsToSave[$k] = trim($_POST[$k] ?? '');
    }

    saveSettings($settingsToSave);
    logAudit(null, null, $currentUser['id'], 'CMS_CONTENT_UPDATED', null, 'SAVED', "Admin updated main website content & banners");
    $msg = "Main website content & banners updated successfully! Changes are live on the homepage.";
}

// Current Values with defaults
$announcement = getSetting('site_announcement', '⚡ 24-Hour Express Tailoring Guaranteed Across Prime Hubs | Free Doorstep Measurement');
$heroBadge = getSetting('hero_badge', "India's #1 Doorstep Tailoring Atelier");
$heroHeadline = getSetting('hero_headline', 'Bespoke Luxury Tailoring. Delivered in 24 Hours.');
$heroTagline = getSetting('hero_tagline', 'No physical tailor visits. Master measurement specialist visits your doorstep, takes 15 anatomical points, and your bespoke handcrafted garment arrives within 24 hours.');
$aboutTitle = getSetting('about_title', 'Crafted by Master Artisans. Delivered at Express Speed.');
$aboutText = getSetting('about_text', 'MY TAYLOR redefines modern sartorial luxury. We combine bespoke Italian & Savile Row construction standards with precision digital workflows and guaranteed 24-hour express doorstep delivery.');

$p1Title = getSetting('promise_1_title', '100% Perfect Fit Guarantee');
$p1Desc = getSetting('promise_1_desc', '15-point anatomical measurement recorded at your doorstep by certified measurement executives.');

$p2Title = getSetting('promise_2_title', 'Strict 24-Hour Turnaround');
$p2Desc = getSetting('promise_2_desc', 'Real-time production tracking through cutting, bespoke stitching, 10-point QC, and rapid courier dispatch.');

$p3Title = getSetting('promise_3_title', 'Zero Store Visits');
$p3Desc = getSetting('promise_3_desc', 'Complete luxury concierge at your home or office. Cash on Doorstep or instant online UPI payment.');

$whatsapp = getSetting('company_whatsapp', '9800000000');
$phone = getSetting('company_phone', '+91 98000 00000');
$email = getSetting('company_email', 'concierge@mytaylor.com');
$address = getSetting('company_address', 'MY TAYLOR Atelier, Bandra West, Mumbai, Maharashtra 400050');
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Website Content & Live CMS Control</h1>
    <p>Admin control over all public website banners, headlines, features, about story, and contact details without editing code.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="<?= APP_URL ?>/index.php" target="_blank" class="btn btn-sm" style="background:#0F172A; color:#D4AF37; font-weight:700; border-radius:8px;">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> Preview Live Homepage
    </a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:14px 18px; margin-bottom:20px; border-radius:var(--admin-radius-sm); font-weight:600;">
    <i class="fa-solid fa-circle-check"></i> <?= e($msg) ?>
  </div>
<?php endif; ?>

<form action="<?= APP_URL ?>/admin/website-content.php" method="POST">

  <!-- 1. Top Ribbon & Hero Section Content -->
  <div class="admin-card" style="border-top: 4px solid var(--admin-gold);">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-bullhorn text-gold"></i>
        1. Top Announcement Bar & Hero Header
      </h3>
    </div>

    <div class="admin-card-body">
      <div style="margin-bottom:18px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Top Ribbon Announcement Bar *</label>
        <input type="text" name="site_announcement" class="form-control" value="<?= e($announcement) ?>" required>
        <small style="color:#64748B; font-size:11.5px;">Visible at the top ribbon on all consumer pages.</small>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 2fr; gap:18px; margin-bottom:18px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Hero Floating Badge Tag</label>
          <input type="text" name="hero_badge" class="form-control" value="<?= e($heroBadge) ?>" required>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Main Hero Headline *</label>
          <input type="text" name="hero_headline" class="form-control" value="<?= e($heroHeadline) ?>" required>
        </div>
      </div>

      <div>
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Hero Subtitle / Description *</label>
        <textarea name="hero_tagline" rows="3" class="form-control" required><?= e($heroTagline) ?></textarea>
      </div>
    </div>
  </div>

  <!-- 2. Brand Value Props (Why Choose Us) -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-award text-gold"></i>
        2. Three Pillars / Value Propositions
      </h3>
    </div>

    <div class="admin-card-body">
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
        <!-- Pillar 1 -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:18px;">
          <h4 style="font-size:14px; margin:0 0 10px; color:#0F172A;"><i class="fa-solid fa-ruler-combined text-gold"></i> Pillar 1: Fit Guarantee</h4>
          <input type="text" name="promise_1_title" class="form-control" value="<?= e($p1Title) ?>" style="margin-bottom:8px;" placeholder="Title">
          <textarea name="promise_1_desc" rows="3" class="form-control" placeholder="Description"><?= e($p1Desc) ?></textarea>
        </div>

        <!-- Pillar 2 -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:18px;">
          <h4 style="font-size:14px; margin:0 0 10px; color:#0F172A;"><i class="fa-solid fa-bolt text-gold"></i> Pillar 2: 24H SLA</h4>
          <input type="text" name="promise_2_title" class="form-control" value="<?= e($p2Title) ?>" style="margin-bottom:8px;" placeholder="Title">
          <textarea name="promise_2_desc" rows="3" class="form-control" placeholder="Description"><?= e($p2Desc) ?></textarea>
        </div>

        <!-- Pillar 3 -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:18px;">
          <h4 style="font-size:14px; margin:0 0 10px; color:#0F172A;"><i class="fa-solid fa-house-chimney text-gold"></i> Pillar 3: Doorstep Concierge</h4>
          <input type="text" name="promise_3_title" class="form-control" value="<?= e($p3Title) ?>" style="margin-bottom:8px;" placeholder="Title">
          <textarea name="promise_3_desc" rows="3" class="form-control" placeholder="Description"><?= e($p3Desc) ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. About Atelier Story -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-scissors text-gold"></i>
        3. About the Atelier & Craftsmanship Story
      </h3>
    </div>

    <div class="admin-card-body">
      <div style="margin-bottom:14px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">About Section Title</label>
        <input type="text" name="about_title" class="form-control" value="<?= e($aboutTitle) ?>">
      </div>

      <div>
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Atelier Story Paragraph</label>
        <textarea name="about_text" rows="3" class="form-control"><?= e($aboutText) ?></textarea>
      </div>
    </div>
  </div>

  <!-- 4. Contact & Support Details -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-headset text-gold"></i>
        4. Customer Concierge & Support Details
      </h3>
    </div>

    <div class="admin-card-body">
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:16px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">WhatsApp Concierge Mobile *</label>
          <input type="text" name="company_whatsapp" class="form-control" value="<?= e($whatsapp) ?>" placeholder="e.g. 9800000000">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Customer Helpline Phone</label>
          <input type="text" name="company_phone" class="form-control" value="<?= e($phone) ?>">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Support Email Address</label>
          <input type="email" name="company_email" class="form-control" value="<?= e($email) ?>">
        </div>
      </div>

      <div>
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Studio Flagship Address</label>
        <input type="text" name="company_address" class="form-control" value="<?= e($address) ?>">
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:24px;">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:8px; padding:12px 32px; font-size:14px;">
          <i class="fa-solid fa-floppy-disk"></i> Save & Publish Changes
        </button>
      </div>
    </div>
  </div>

</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
