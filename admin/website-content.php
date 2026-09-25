<?php
$pageTitle = "Website Content & CMS Control";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
$error = null;

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../uploads/cms/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Helper to handle image uploads
function handleCmsImageUpload($fileInputName, $currentValue, $uploadDir) {
    if (!empty($_FILES[$fileInputName]['name']) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES[$fileInputName]['tmp_name'];
        $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        
        if (in_array($ext, $allowedExts)) {
            $fileName = 'cms_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                return 'uploads/cms/' . $fileName;
            }
        }
    }
    return $currentValue;
}

// Handle Content Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'site_announcement',
        'hero_badge',
        'hero_headline',
        'hero_tagline',
        'hero_image_url',
        'hero_stat_1_num',
        'hero_stat_1_label',
        'hero_stat_1_desc',
        'hero_stat_2_num',
        'hero_stat_2_label',
        'hero_stat_2_desc',
        'hero_stat_3_num',
        'hero_stat_3_label',
        'hero_stat_3_desc',
        'cat_section_tag',
        'cat_section_title',
        'cat_section_desc',
        'cat_1_tag',
        'cat_1_title',
        'cat_1_desc',
        'cat_1_price',
        'cat_1_image',
        'cat_1_btn_text',
        'cat_1_btn_link',
        'cat_2_tag',
        'cat_2_title',
        'cat_2_desc',
        'cat_2_price',
        'cat_2_image',
        'cat_2_btn_text',
        'cat_2_btn_link',
        'cat_3_tag',
        'cat_3_title',
        'cat_3_desc',
        'cat_3_price',
        'cat_3_image',
        'cat_3_btn_text',
        'cat_3_btn_link',
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

    // Process File Uploads
    $settingsToSave['hero_image_url'] = handleCmsImageUpload('hero_image_file', $settingsToSave['hero_image_url'], $uploadDir);
    $settingsToSave['cat_1_image'] = handleCmsImageUpload('cat_1_image_file', $settingsToSave['cat_1_image'], $uploadDir);
    $settingsToSave['cat_2_image'] = handleCmsImageUpload('cat_2_image_file', $settingsToSave['cat_2_image'], $uploadDir);
    $settingsToSave['cat_3_image'] = handleCmsImageUpload('cat_3_image_file', $settingsToSave['cat_3_image'], $uploadDir);

    saveSettings($settingsToSave);
    logAudit(null, null, $currentUser['id'], 'CMS_CONTENT_UPDATED', null, 'SAVED', "Admin updated main website content, hero & category cards");
    $msg = "Website content, hero image, and category cards updated successfully! Changes are live on the homepage.";
}

// Current Values with defaults
$announcement = getSetting('site_announcement', '⚡ 24-Hour Express Tailoring Guaranteed Across Prime Hubs | Free Doorstep Measurement');
$heroBadge = getSetting('hero_badge', "India's #1 Doorstep Tailoring Atelier");
$heroHeadline = getSetting('hero_headline', 'Bespoke Luxury Tailoring. Delivered in 24 Hours.');
$heroTagline = getSetting('hero_tagline', 'No physical tailor visits. Master measurement specialist visits your doorstep, takes 15 anatomical points, and your bespoke handcrafted garment arrives within 24 hours.');
$heroImageUrl = getSetting('hero_image_url', 'assets/images/hero_tailor.jpg');

$stat1Num = getSetting('hero_stat_1_num', '24');
$stat1Label = getSetting('hero_stat_1_label', 'Hours');
$stat1Desc = getSetting('hero_stat_1_desc', 'Guaranteed Handover SLA');

$stat2Num = getSetting('hero_stat_2_num', '15');
$stat2Label = getSetting('hero_stat_2_label', 'Points');
$stat2Desc = getSetting('hero_stat_2_desc', 'Doorstep Body Measurements');

$stat3Num = getSetting('hero_stat_3_num', '100');
$stat3Label = getSetting('hero_stat_3_label', '%');
$stat3Desc = getSetting('hero_stat_3_desc', 'Perfect Fit Guarantee');

// Category Section
$catSectionTag = getSetting('cat_section_tag', 'Master Atelier Collections');
$catSectionTitle = getSetting('cat_section_title', 'Bespoke Tailoring Across Categories');
$catSectionDesc = getSetting('cat_section_desc', 'Handcrafted for men and women with Savile Row craftsmanship and Italian finishing standards.');

$cat1Tag = getSetting('cat_1_tag', "Men's Atelier");
$cat1Title = getSetting('cat_1_title', "Men's Bespoke Suits & Shirts");
$cat1Desc = getSetting('cat_1_desc', "Formal Shirts, 2-Piece & 3-Piece Tuxedos, Tailored Trousers, Bandhgalas, and Kurta Pajama sets.");
$cat1Price = getSetting('cat_1_price', 'From ₹599');
$cat1Image = getSetting('cat_1_image', 'assets/images/men_bespoke.jpg');
$cat1BtnText = getSetting('cat_1_btn_text', 'Book Measurement');
$cat1BtnLink = getSetting('cat_1_btn_link', 'book.php');

$cat2Tag = getSetting('cat_2_tag', "Women's Atelier");
$cat2Title = getSetting('cat_2_title', "Designer Blouses & Lehengas");
$cat2Desc = getSetting('cat_2_desc', "Saree Blouses (Princess cut, Padded, Backless), Anarkalis, Salwar Kameez, and Bridal Lehengas.");
$cat2Price = getSetting('cat_2_price', 'From ₹699');
$cat2Image = getSetting('cat_2_image', 'assets/images/women_atelier.jpg');
$cat2BtnText = getSetting('cat_2_btn_text', 'Book Measurement');
$cat2BtnLink = getSetting('cat_2_btn_link', 'book.php');

$cat3Tag = getSetting('cat_3_tag', "24H Express");
$cat3Title = getSetting('cat_3_title', "Custom Fitting & Alterations");
$cat3Desc = getSetting('cat_3_desc', "Emergency suit alterations, waist tapering, sleeve adjustments, and hem reshaping in record time.");
$cat3Price = getSetting('cat_3_price', 'From ₹299');
$cat3Image = getSetting('cat_3_image', 'assets/images/hero_tailor.jpg');
$cat3BtnText = getSetting('cat_3_btn_text', 'Book Alteration');
$cat3BtnLink = getSetting('cat_3_btn_link', 'book.php');

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
    <p>Admin control over public website hero banners, images, category cards, headlines, features, and contact details without editing code.</p>
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

<form action="<?= APP_URL ?>/admin/website-content.php" method="POST" enctype="multipart/form-data">

  <!-- 1. Top Ribbon & Hero Section Content -->
  <div class="admin-card" style="border-top: 4px solid var(--admin-gold);">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-bullhorn text-gold"></i>
        1. Top Announcement Bar & Hero Section
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

      <div style="margin-bottom:18px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Hero Subtitle / Description *</label>
        <textarea name="hero_tagline" rows="3" class="form-control" required><?= e($heroTagline) ?></textarea>
      </div>

      <!-- Hero Section Background Image Upload -->
      <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:18px; margin-bottom:18px;">
        <h4 style="font-size:14px; margin:0 0 10px; color:#0F172A;"><i class="fa-solid fa-image text-gold"></i> Hero Section Background Image</h4>
        
        <div style="display:grid; grid-template-columns: 180px 1fr; gap:18px; align-items:center;">
          <div>
            <?php 
              $displayHeroImg = (str_starts_with($heroImageUrl, 'http') ? $heroImageUrl : APP_URL . '/' . ltrim($heroImageUrl, '/'));
            ?>
            <img src="<?= $displayHeroImg ?>" alt="Hero Preview" style="width:100%; height:100px; object-fit:cover; border-radius:8px; border:1px solid #CBD5E1;">
          </div>
          <div>
            <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Upload New Hero Image (JPG, PNG, WebP)</label>
            <input type="file" name="hero_image_file" class="form-control" accept="image/*" style="margin-bottom:8px;">
            <label style="font-size:11.5px; font-weight:600; color:#64748B; display:block; margin-bottom:2px;">Or Image Path / URL</label>
            <input type="text" name="hero_image_url" class="form-control" value="<?= e($heroImageUrl) ?>">
          </div>
        </div>
      </div>

      <!-- Hero Stats Numbers -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
        <div style="background:#FFFFFF; border:1px solid #E2E8F0; padding:12px; border-radius:8px;">
          <label style="font-size:12px; font-weight:700; color:#0F172A;">Stat 1 (Hours)</label>
          <div style="display:flex; gap:6px; margin:6px 0;">
            <input type="text" name="hero_stat_1_num" class="form-control" value="<?= e($stat1Num) ?>" placeholder="24" style="width:70px;">
            <input type="text" name="hero_stat_1_label" class="form-control" value="<?= e($stat1Label) ?>" placeholder="Hours">
          </div>
          <input type="text" name="hero_stat_1_desc" class="form-control" value="<?= e($stat1Desc) ?>" placeholder="Description">
        </div>

        <div style="background:#FFFFFF; border:1px solid #E2E8F0; padding:12px; border-radius:8px;">
          <label style="font-size:12px; font-weight:700; color:#0F172A;">Stat 2 (Points)</label>
          <div style="display:flex; gap:6px; margin:6px 0;">
            <input type="text" name="hero_stat_2_num" class="form-control" value="<?= e($stat2Num) ?>" placeholder="15" style="width:70px;">
            <input type="text" name="hero_stat_2_label" class="form-control" value="<?= e($stat2Label) ?>" placeholder="Points">
          </div>
          <input type="text" name="hero_stat_2_desc" class="form-control" value="<?= e($stat2Desc) ?>" placeholder="Description">
        </div>

        <div style="background:#FFFFFF; border:1px solid #E2E8F0; padding:12px; border-radius:8px;">
          <label style="font-size:12px; font-weight:700; color:#0F172A;">Stat 3 (Guarantee)</label>
          <div style="display:flex; gap:6px; margin:6px 0;">
            <input type="text" name="hero_stat_3_num" class="form-control" value="<?= e($stat3Num) ?>" placeholder="100" style="width:70px;">
            <input type="text" name="hero_stat_3_label" class="form-control" value="<?= e($stat3Label) ?>" placeholder="%">
          </div>
          <input type="text" name="hero_stat_3_desc" class="form-control" value="<?= e($stat3Desc) ?>" placeholder="Description">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Category Cards & Featured Bespoke Collections -->
  <div class="admin-card" style="border-top: 4px solid var(--admin-gold);">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-layer-group text-gold"></i>
        2. Featured Bespoke Collections (Category Cards)
      </h3>
    </div>

    <div class="admin-card-body">
      <!-- Section Header Info -->
      <div style="display:grid; grid-template-columns: 1fr 2fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Section Tag</label>
          <input type="text" name="cat_section_tag" class="form-control" value="<?= e($catSectionTag) ?>">
        </div>
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Section Main Title</label>
          <input type="text" name="cat_section_title" class="form-control" value="<?= e($catSectionTitle) ?>">
        </div>
      </div>
      <div style="margin-bottom:24px;">
        <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Section Subtitle</label>
        <input type="text" name="cat_section_desc" class="form-control" value="<?= e($catSectionDesc) ?>">
      </div>

      <!-- 3 Category Cards Grid -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:24px;">
        
        <!-- Card 1 (Men's Atelier) -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:20px;">
          <h4 style="font-size:15px; margin:0 0 12px; color:#0F172A; font-weight:700;">
            <i class="fa-solid fa-user-tie text-gold"></i> Card 1: Men's Atelier
          </h4>
          
          <div style="margin-bottom:12px; text-align:center;">
            <?php $c1Img = (str_starts_with($cat1Image, 'http') ? $cat1Image : APP_URL . '/' . ltrim($cat1Image, '/')); ?>
            <img src="<?= $c1Img ?>" alt="Card 1" style="width:100%; height:130px; object-fit:cover; border-radius:8px; border:1px solid #CBD5E1; margin-bottom:8px;">
            <input type="file" name="cat_1_image_file" class="form-control form-control-sm" accept="image/*">
            <input type="hidden" name="cat_1_image" value="<?= e($cat1Image) ?>">
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Badge Tag</label>
              <input type="text" name="cat_1_tag" class="form-control form-control-sm" value="<?= e($cat1Tag) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Starting Price</label>
              <input type="text" name="cat_1_price" class="form-control form-control-sm" value="<?= e($cat1Price) ?>">
            </div>
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Title</label>
            <input type="text" name="cat_1_title" class="form-control form-control-sm" value="<?= e($cat1Title) ?>">
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Description</label>
            <textarea name="cat_1_desc" rows="2" class="form-control form-control-sm"><?= e($cat1Desc) ?></textarea>
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Text</label>
              <input type="text" name="cat_1_btn_text" class="form-control form-control-sm" value="<?= e($cat1BtnText) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Link</label>
              <input type="text" name="cat_1_btn_link" class="form-control form-control-sm" value="<?= e($cat1BtnLink) ?>">
            </div>
          </div>
        </div>

        <!-- Card 2 (Women's Atelier) -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:20px;">
          <h4 style="font-size:15px; margin:0 0 12px; color:#0F172A; font-weight:700;">
            <i class="fa-solid fa-person-dress text-gold"></i> Card 2: Women's Atelier
          </h4>
          
          <div style="margin-bottom:12px; text-align:center;">
            <?php $c2Img = (str_starts_with($cat2Image, 'http') ? $cat2Image : APP_URL . '/' . ltrim($cat2Image, '/')); ?>
            <img src="<?= $c2Img ?>" alt="Card 2" style="width:100%; height:130px; object-fit:cover; border-radius:8px; border:1px solid #CBD5E1; margin-bottom:8px;">
            <input type="file" name="cat_2_image_file" class="form-control form-control-sm" accept="image/*">
            <input type="hidden" name="cat_2_image" value="<?= e($cat2Image) ?>">
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Badge Tag</label>
              <input type="text" name="cat_2_tag" class="form-control form-control-sm" value="<?= e($cat2Tag) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Starting Price</label>
              <input type="text" name="cat_2_price" class="form-control form-control-sm" value="<?= e($cat2Price) ?>">
            </div>
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Title</label>
            <input type="text" name="cat_2_title" class="form-control form-control-sm" value="<?= e($cat2Title) ?>">
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Description</label>
            <textarea name="cat_2_desc" rows="2" class="form-control form-control-sm"><?= e($cat2Desc) ?></textarea>
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Text</label>
              <input type="text" name="cat_2_btn_text" class="form-control form-control-sm" value="<?= e($cat2BtnText) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Link</label>
              <input type="text" name="cat_2_btn_link" class="form-control form-control-sm" value="<?= e($cat2BtnLink) ?>">
            </div>
          </div>
        </div>

        <!-- Card 3 (Custom Alterations) -->
        <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:20px;">
          <h4 style="font-size:15px; margin:0 0 12px; color:#0F172A; font-weight:700;">
            <i class="fa-solid fa-bolt text-gold"></i> Card 3: 24H Alterations
          </h4>
          
          <div style="margin-bottom:12px; text-align:center;">
            <?php $c3Img = (str_starts_with($cat3Image, 'http') ? $cat3Image : APP_URL . '/' . ltrim($cat3Image, '/')); ?>
            <img src="<?= $c3Img ?>" alt="Card 3" style="width:100%; height:130px; object-fit:cover; border-radius:8px; border:1px solid #CBD5E1; margin-bottom:8px;">
            <input type="file" name="cat_3_image_file" class="form-control form-control-sm" accept="image/*">
            <input type="hidden" name="cat_3_image" value="<?= e($cat3Image) ?>">
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Badge Tag</label>
              <input type="text" name="cat_3_tag" class="form-control form-control-sm" value="<?= e($cat3Tag) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Starting Price</label>
              <input type="text" name="cat_3_price" class="form-control form-control-sm" value="<?= e($cat3Price) ?>">
            </div>
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Title</label>
            <input type="text" name="cat_3_title" class="form-control form-control-sm" value="<?= e($cat3Title) ?>">
          </div>

          <div style="margin-bottom:10px;">
            <label style="font-size:11.5px; font-weight:700; color:#64748B;">Card Description</label>
            <textarea name="cat_3_desc" rows="2" class="form-control form-control-sm"><?= e($cat3Desc) ?></textarea>
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Text</label>
              <input type="text" name="cat_3_btn_text" class="form-control form-control-sm" value="<?= e($cat3BtnText) ?>">
            </div>
            <div>
              <label style="font-size:11.5px; font-weight:700; color:#64748B;">Button Link</label>
              <input type="text" name="cat_3_btn_link" class="form-control form-control-sm" value="<?= e($cat3BtnLink) ?>">
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- 3. Brand Value Props (Why Choose Us) -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-award text-gold"></i>
        3. Three Pillars / Value Propositions
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

  <!-- 4. About Atelier Story -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-scissors text-gold"></i>
        4. About the Atelier & Craftsmanship Story
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

  <!-- 5. Contact & Support Details -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-headset text-gold"></i>
        5. Customer Concierge & Support Details
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
