<?php
$pageTitle = "Doorstep Tailoring & 24-Hour Express Delivery";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/settings.php';

$pdo = getDbConnection();

// Dynamic CMS Texts from Database (Admin Editable)
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

// Fetch active services safely
$services = [];
$testimonials = [];
try {
    $services = $pdo->query("SELECT * FROM `services` WHERE `status` = 'active' ORDER BY `id` ASC LIMIT 6")->fetchAll();
    $testimonials = $pdo->query("SELECT * FROM `testimonials` WHERE `status` = 'active' ORDER BY `display_order` ASC, `id` DESC")->fetchAll();
} catch (Exception $e) {
    // Database tables might not be migrated yet
    $dbErrorNotice = true;
}
?>

<!-- 1. Ultra-Luxury Hero Section with GSAP Animations -->
<section class="hero-section" style="position:relative; overflow:hidden; background: linear-gradient(135deg, rgba(7,13,30,0.92) 0%, rgba(10,17,40,0.85) 60%, rgba(7,13,30,0.95) 100%), url('<?= APP_URL ?>/assets/images/hero_tailor.jpg') center/cover no-repeat; padding: 75px 0 95px;">
  <div class="container hero-grid" style="position:relative; z-index:2;">
    <div>
      <div class="gsap-hero-badge" style="display:inline-flex; align-items:center; gap:8px; margin-bottom:18px;">
        <span class="badge-24h pulse"><i class="fa-solid fa-bolt"></i> 24-Hour Express Delivery</span>
        <span style="font-size:12px; color:var(--text-light-muted); letter-spacing:1px; text-transform:uppercase;">• <?= e($heroBadge) ?></span>
      </div>
      
      <h1 class="hero-title gsap-hero-title" style="font-size:clamp(32px, 4.5vw, 54px); line-height:1.15; margin-bottom:18px;">
        <?= nl2br(e($heroHeadline)) ?>
      </h1>
      
      <p class="hero-subtitle gsap-hero-sub" style="font-size:16.5px; line-height:1.7; color:#CBD5E1; max-width:580px; margin-bottom:28px;">
        <?= e($heroTagline) ?>
      </p>

      <div class="gsap-hero-cta" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:36px;">
        <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-lg"><i class="fa-solid fa-tape"></i> Book Doorstep Measurement</a>
        <a href="<?= APP_URL ?>/track.php" class="btn btn-gold-outline btn-lg"><i class="fa-solid fa-location-crosshairs"></i> Track Order Live</a>
        <a href="<?= APP_URL ?>/services.php" class="btn btn-dark btn-lg"><i class="fa-solid fa-scissors text-gold"></i> View Catalog</a>
      </div>

      <!-- Hero Value Counters with GSAP Increment -->
      <div class="hero-stats gsap-hero-stats" style="border-top:1px solid rgba(212,175,55,0.25); padding-top:24px;">
        <div class="stat-item">
          <h4 class="counter-num" data-target="24" style="color:var(--gold-primary); font-size:32px; margin:0; font-weight:800;">24</h4>
          <span style="font-size:14px; font-weight:700; color:var(--gold-primary);">Hours</span>
          <p style="color:#94A3B8; font-size:12px; margin:2px 0 0;">Guaranteed Handover SLA</p>
        </div>
        <div class="stat-item">
          <h4 class="counter-num" data-target="15" style="color:#FFFFFF; font-size:32px; margin:0; font-weight:800;">15</h4>
          <span style="font-size:14px; font-weight:700; color:#FFFFFF;">Points</span>
          <p style="color:#94A3B8; font-size:12px; margin:2px 0 0;">Doorstep Body Measurements</p>
        </div>
        <div class="stat-item">
          <h4 class="counter-num" data-target="100" style="color:var(--accent-emerald); font-size:32px; margin:0; font-weight:800;">100</h4>
          <span style="font-size:14px; font-weight:700; color:var(--accent-emerald);">%</span>
          <p style="color:#94A3B8; font-size:12px; margin:2px 0 0;">Perfect Fit Guarantee</p>
        </div>
      </div>
    </div>

    <!-- Right Quick Track & Serviceability Widget -->
    <div>
      <div class="hero-card gsap-hero-card" style="background:rgba(14,23,47,0.9); backdrop-filter:blur(20px); border:1px solid rgba(212,175,55,0.35); border-radius:var(--radius-lg); padding:32px; box-shadow:0 25px 50px rgba(0,0,0,0.6);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <h3 style="font-size:20px; font-weight:700; color:#FFFFFF; margin:0;"><i class="fa-solid fa-bolt text-gold"></i> Live Order Tracker</h3>
          <span class="badge-status badge-gold">24H Live SLA</span>
        </div>
        <p style="color:#94A3B8; font-size:13.5px; margin-bottom:18px;">Track your garment live from doorstep measurement to master cutting, stitching, QC, and express delivery.</p>

        <form action="<?= APP_URL ?>/track.php" method="GET" style="margin-bottom:24px;">
          <div style="display:flex; gap:8px;">
            <input type="text" name="booking_id" class="form-control" placeholder="e.g. MYT-20260920-001245" value="MYT-20260920-001245" required style="background:rgba(255,255,255,0.08); border-color:var(--border-dark); color:#FFFFFF;">
            <button type="submit" class="btn btn-gold" style="white-space:nowrap; font-weight:800;"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
          </div>
        </form>

        <hr style="border-color:rgba(255,255,255,0.08); margin:20px 0;">

        <!-- Doorstep Booking Quick Feature -->
        <div style="display:flex; align-items:center; gap:14px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-sm); padding:14px;">
          <div style="width:42px; height:42px; border-radius:50%; background:rgba(212,175,55,0.15); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
            <i class="fa-solid fa-house-user"></i>
          </div>
          <div>
            <strong style="color:#FFFFFF; font-size:14px;">Doorstep Fitting Specialist</strong>
            <p style="color:#94A3B8; font-size:12px; margin:2px 0 0;">Arrives at your home with fabric swatches & measurement docket.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 2. Trust Bar & Press Accolades -->
<div style="background:#050914; border-bottom:1px solid rgba(212,175,55,0.15); padding:18px 0;">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; opacity:0.85;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; color:var(--gold-primary); font-weight:800;">Featured In & Recognized By:</span>
    <div style="display:flex; align-items:center; gap:32px; flex-wrap:wrap; color:#94A3B8; font-size:14px; font-weight:700; letter-spacing:1.5px;">
      <span><i class="fa-solid fa-gem text-gold"></i> VOGUE LUXURY</span>
      <span><i class="fa-solid fa-award text-gold"></i> GQ SARTORIAL</span>
      <span><i class="fa-solid fa-crown text-gold"></i> FORBES INDIA</span>
      <span><i class="fa-solid fa-certificate text-gold"></i> THE ECONOMIC TIMES</span>
    </div>
  </div>
</div>

<!-- 3. Interactive 4-Step Doorstep Journey (GSAP ScrollTrigger) -->
<section class="section gsap-reveal-section" style="padding: 80px 0;">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Seamless Atelier Flow</span>
      <h2 class="section-title">How 24-Hour Doorstep Tailoring Works</h2>
      <p class="section-desc">Experience frictionless bespoke luxury tailored and delivered to your doorstep in 4 simple steps.</p>
    </div>

    <div class="journey-grid" style="display:grid; grid-template-columns: 1fr 1.3fr; gap:36px; align-items:center; margin-top:30px;">
      <!-- Step Imagery -->
      <div class="gsap-journey-img">
        <div style="position:relative; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-lg); border:1px solid var(--border-light);">
          <img src="<?= APP_URL ?>/assets/images/doorstep_service.jpg" alt="Doorstep Measurement Specialist" style="width:100%; height:auto; display:block;">
          <div style="position:absolute; bottom:0; left:0; right:0; background:linear-gradient(0deg, rgba(7,13,30,0.95) 0%, rgba(7,13,30,0) 100%); padding:24px 20px; color:#FFFFFF;">
            <span class="badge-24h" style="margin-bottom:6px;"><i class="fa-solid fa-certificate"></i> Certified Fitting Specialists</span>
            <h4 style="font-size:18px; margin:0;">Doorstep 15-Point Anatomical Scan</h4>
          </div>
        </div>
      </div>

      <!-- 4 Stepper Cards -->
      <div class="gsap-journey-cards" style="display:flex; flex-direction:column; gap:16px;">
        <div class="card gsap-step-card" style="display:flex; align-items:flex-start; gap:16px; padding:20px; transition:transform 0.3s;">
          <div style="width:40px; height:40px; border-radius:50%; background:#070D1E; color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0;">
            1
          </div>
          <div>
            <h4 style="font-size:17px; margin:0 0 4px; color:#0F172A;">Schedule Doorstep Appointment</h4>
            <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Pick your garment (Shirt, Suit, Kurta, Blouse), date, and preferred 1-hour time slot on our responsive web portal.</p>
          </div>
        </div>

        <div class="card gsap-step-card" style="display:flex; align-items:flex-start; gap:16px; padding:20px; border-left:4px solid var(--gold-primary); transition:transform 0.3s;">
          <div style="width:40px; height:40px; border-radius:50%; background:var(--gold-primary); color:#070D1E; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0;">
            2
          </div>
          <div>
            <h4 style="font-size:17px; margin:0 0 4px; color:#0F172A;">Master Specialist Measures at Home</h4>
            <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Our executive visits your home or office with digital calipers, takes your exact fit preferences, and confirms fabric selections.</p>
          </div>
        </div>

        <div class="card gsap-step-card" style="display:flex; align-items:flex-start; gap:16px; padding:20px; transition:transform 0.3s;">
          <div style="width:40px; height:40px; border-radius:50%; background:#070D1E; color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0;">
            3
          </div>
          <div>
            <h4 style="font-size:17px; margin:0 0 4px; color:#0F172A;">Master Tailor Cutting & 10-Point QC</h4>
            <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Precision laser cutting, hand-stitching with German Gutermann threads, and strict 10-point quality inspection.</p>
          </div>
        </div>

        <div class="card gsap-step-card" style="display:flex; align-items:flex-start; gap:16px; padding:20px; border-left:4px solid var(--accent-emerald); transition:transform 0.3s;">
          <div style="width:40px; height:40px; border-radius:50%; background:var(--accent-emerald); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0;">
            4
          </div>
          <div>
            <h4 style="font-size:17px; margin:0 0 4px; color:#0F172A;"><i class="fa-solid fa-bolt text-gold"></i> 24-Hour Express Handover</h4>
            <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Our rider arrives at your doorstep with branded garment dockets. Verify fit and sign digitally on screen.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Featured Bespoke Collections Grid -->
<section class="section gsap-reveal-section" style="background:#F1F5F9; padding:80px 0;">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Master Atelier Collections</span>
      <h2 class="section-title">Bespoke Tailoring Across Categories</h2>
      <p class="section-desc">Handcrafted for men and women with Savile Row craftsmanship and Italian finishing standards.</p>
    </div>

    <div class="category-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:24px; margin-top:30px;">
      
      <!-- Men's Bespoke Card -->
      <div class="card gsap-cat-card" style="padding:0; overflow:hidden; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); transition:transform 0.3s, box-shadow 0.3s;">
        <div style="height:240px; overflow:hidden; position:relative;">
          <img src="<?= APP_URL ?>/assets/images/men_bespoke.jpg" alt="Men's Bespoke Tailoring" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s;">
          <span style="position:absolute; top:16px; left:16px; background:#070D1E; color:var(--gold-primary); font-size:11px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase;">Men's Atelier</span>
        </div>
        <div style="padding:24px;">
          <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;">Men's Bespoke Suits & Shirts</h3>
          <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:16px;">Formal Shirts, 2-Piece & 3-Piece Tuxedos, Tailored Trousers, Bandhgalas, and Kurta Pajama sets.</p>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong style="color:var(--gold-primary); font-size:18px;">From ₹599</strong>
            <a href="<?= APP_URL ?>/book.php" class="btn btn-dark btn-sm"><i class="fa-solid fa-tape"></i> Book Measurement</a>
          </div>
        </div>
      </div>

      <!-- Women's Designer Card -->
      <div class="card gsap-cat-card" style="padding:0; overflow:hidden; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); transition:transform 0.3s, box-shadow 0.3s;">
        <div style="height:240px; overflow:hidden; position:relative;">
          <img src="<?= APP_URL ?>/assets/images/women_atelier.jpg" alt="Women's Designer Atelier" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s;">
          <span style="position:absolute; top:16px; left:16px; background:#070D1E; color:var(--gold-primary); font-size:11px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase;">Women's Atelier</span>
        </div>
        <div style="padding:24px;">
          <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;">Designer Blouses & Lehengas</h3>
          <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:16px;">Saree Blouses (Princess cut, Padded, Backless), Anarkalis, Salwar Kameez, and Bridal Lehengas.</p>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong style="color:var(--gold-primary); font-size:18px;">From ₹699</strong>
            <a href="<?= APP_URL ?>/book.php" class="btn btn-dark btn-sm"><i class="fa-solid fa-tape"></i> Book Measurement</a>
          </div>
        </div>
      </div>

      <!-- Custom & 24H Express Alterations -->
      <div class="card gsap-cat-card" style="padding:0; overflow:hidden; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); transition:transform 0.3s, box-shadow 0.3s;">
        <div style="height:240px; overflow:hidden; position:relative; background:#0A1128;">
          <img src="<?= APP_URL ?>/assets/images/hero_tailor.jpg" alt="Express Alterations" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s;">
          <span style="position:absolute; top:16px; left:16px; background:#B45309; color:#FFFFFF; font-size:11px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase;"><i class="fa-solid fa-bolt"></i> 24H Express</span>
        </div>
        <div style="padding:24px;">
          <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;">Custom Fitting & Alterations</h3>
          <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:16px;">Emergency suit alterations, waist tapering, sleeve adjustments, and hem reshaping in record time.</p>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong style="color:var(--gold-primary); font-size:18px;">From ₹299</strong>
            <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-scissors"></i> Book Alteration</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 5. Premium Fabric Swatch & Master Wool / Silk Library -->
<section id="fabricSection" class="section gsap-reveal-section" style="padding: 80px 0; background:#070D1E; color:#FFFFFF; position:relative; overflow:hidden;">
  <div class="container" style="position:relative; z-index:2;">
    <div class="section-header">
      <span class="section-tag" style="color:var(--gold-primary); background:rgba(212,175,55,0.15); border:1px solid rgba(212,175,55,0.3);">Sartorial Materials</span>
      <h2 class="section-title" style="color:#FFFFFF;">World-Class Fabric Mill Partners</h2>
      <p class="section-desc" style="color:#94A3B8;">Our measurement executives bring genuine swatch booklets directly to your home. Choose from Italy, England & India's finest mills.</p>
    </div>

    <div class="fabric-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:22px; margin-top:36px;">
      
      <!-- Fabric Card 1 -->
      <div class="card gsap-fabric-card" style="background:#0E172F; border:1px solid rgba(212,175,55,0.35); color:#FFFFFF; padding:24px; border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(0,0,0,0.4); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.3s ease, border-color 0.3s ease;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:11px; background:rgba(212,175,55,0.15); color:var(--gold-primary); padding:4px 10px; border-radius:20px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">Super 150s Merino</span>
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(212,175,55,0.12); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:16px; border:1px solid rgba(212,175,55,0.25);">
              <i class="fa-solid fa-layer-group"></i>
            </div>
          </div>
          <h4 style="font-size:19px; margin:0 0 8px; color:#FFFFFF; font-weight:700;">Italian Wool & Cashmere</h4>
          <p style="font-size:13.5px; color:#94A3B8; line-height:1.6; margin-bottom:18px;">Lightweight, breathable natural stretch drape sourced from Biella mills. Perfect for year-round bespoke tuxedos and executive suits.</p>
          
          <!-- Visual Swatch Samples -->
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <span style="font-size:11px; color:#64748B;">Sample Swatches:</span>
            <span style="width:18px; height:18px; border-radius:50%; background:#1E293B; border:1px solid #475569; display:inline-block;" title="Midnight Navy"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#334155; border:1px solid #64748B; display:inline-block;" title="Charcoal Grey"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#000000; border:1px solid #475569; display:inline-block;" title="Jet Black"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#5C4033; border:1px solid #8D6E63; display:inline-block;" title="Mocha Tweed"></span>
          </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:14px; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-size:12px; color:var(--gold-primary); font-weight:700;"><i class="fa-solid fa-circle-check"></i> 100+ Italian Weaves</span>
          <a href="<?= APP_URL ?>/book.php" class="btn btn-gold-outline btn-sm" style="font-size:11.5px; padding:6px 14px; border-radius:20px;">Book Swatch</a>
        </div>
      </div>

      <!-- Fabric Card 2 -->
      <div class="card gsap-fabric-card" style="background:#0E172F; border:1px solid rgba(212,175,55,0.35); color:#FFFFFF; padding:24px; border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(0,0,0,0.4); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.3s ease, border-color 0.3s ease;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:11px; background:rgba(212,175,55,0.15); color:var(--gold-primary); padding:4px 10px; border-radius:20px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">120s 2-Ply Cotton</span>
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(212,175,55,0.12); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:16px; border:1px solid rgba(212,175,55,0.25);">
              <i class="fa-solid fa-shirt"></i>
            </div>
          </div>
          <h4 style="font-size:19px; margin:0 0 8px; color:#FFFFFF; font-weight:700;">Egyptian Giza Cotton</h4>
          <p style="font-size:13.5px; color:#94A3B8; line-height:1.6; margin-bottom:18px;">Extra-long staple fibers offering silk-like softness, crisp structured collars, high breathability, and wrinkle-resistant longevity.</p>
          
          <!-- Visual Swatch Samples -->
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <span style="font-size:11px; color:#64748B;">Sample Swatches:</span>
            <span style="width:18px; height:18px; border-radius:50%; background:#F8FAFC; border:1px solid #CBD5E1; display:inline-block;" title="Crisp White"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#BAE6FD; border:1px solid #38BDF8; display:inline-block;" title="Sky Blue"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#FDE68A; border:1px solid #F59E0B; display:inline-block;" title="Champagne Cream"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#E2E8F0; border:1px solid #94A3B8; display:inline-block;" title="Fine Pin-Stripe"></span>
          </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:14px; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-size:12px; color:var(--gold-primary); font-weight:700;"><i class="fa-solid fa-circle-check"></i> 50+ Colors & Stripes</span>
          <a href="<?= APP_URL ?>/book.php" class="btn btn-gold-outline btn-sm" style="font-size:11.5px; padding:6px 14px; border-radius:20px;">Book Swatch</a>
        </div>
      </div>

      <!-- Fabric Card 3 -->
      <div class="card gsap-fabric-card" style="background:#0E172F; border:1px solid rgba(212,175,55,0.35); color:#FFFFFF; padding:24px; border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(0,0,0,0.4); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.3s ease, border-color 0.3s ease;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:11px; background:rgba(212,175,55,0.15); color:var(--gold-primary); padding:4px 10px; border-radius:20px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">Pure Mulberry</span>
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(212,175,55,0.12); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:16px; border:1px solid rgba(212,175,55,0.25);">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
          </div>
          <h4 style="font-size:19px; margin:0 0 8px; color:#FFFFFF; font-weight:700;">Raw Silk & Chanderi</h4>
          <p style="font-size:13.5px; color:#94A3B8; line-height:1.6; margin-bottom:18px;">Authentic royal weaves with rich natural luster for bespoke bridal blouses, festive kurtas, bandhgalas, and ceremonial lehengas.</p>
          
          <!-- Visual Swatch Samples -->
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <span style="font-size:11px; color:#64748B;">Sample Swatches:</span>
            <span style="width:18px; height:18px; border-radius:50%; background:#831843; border:1px solid #BE185D; display:inline-block;" title="Royal Maroon"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#D97706; border:1px solid #F59E0B; display:inline-block;" title="Amber Gold"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#065F46; border:1px solid #10B981; display:inline-block;" title="Emerald Green"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#4C1D95; border:1px solid #7C3AED; display:inline-block;" title="Imperial Plum"></span>
          </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:14px; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-size:12px; color:var(--gold-primary); font-weight:700;"><i class="fa-solid fa-circle-check"></i> Certified Pure Silk</span>
          <a href="<?= APP_URL ?>/book.php" class="btn btn-gold-outline btn-sm" style="font-size:11.5px; padding:6px 14px; border-radius:20px;">Book Swatch</a>
        </div>
      </div>

      <!-- Fabric Card 4 -->
      <div class="card gsap-fabric-card" style="background:#0E172F; border:1px solid rgba(212,175,55,0.35); color:#FFFFFF; padding:24px; border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(0,0,0,0.4); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.3s ease, border-color 0.3s ease;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:11px; background:rgba(212,175,55,0.15); color:var(--gold-primary); padding:4px 10px; border-radius:20px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">Irish Herringbone</span>
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(212,175,55,0.12); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:16px; border:1px solid rgba(212,175,55,0.25);">
              <i class="fa-solid fa-feather"></i>
            </div>
          </div>
          <h4 style="font-size:19px; margin:0 0 8px; color:#FFFFFF; font-weight:700;">European Pure Linen</h4>
          <p style="font-size:13.5px; color:#94A3B8; line-height:1.6; margin-bottom:18px;">Ultra-cool, relaxed luxury textures crafted for summer blazers, safari jackets, casual trousers, and destination wedding wear.</p>
          
          <!-- Visual Swatch Samples -->
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <span style="font-size:11px; color:#64748B;">Sample Swatches:</span>
            <span style="width:18px; height:18px; border-radius:50%; background:#D1D5DB; border:1px solid #9CA3AF; display:inline-block;" title="Natural Beige"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#CBD5E1; border:1px solid #94A3B8; display:inline-block;" title="Sage Mist"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#E5E7EB; border:1px solid #D1D5DB; display:inline-block;" title="Bleached Flax"></span>
            <span style="width:18px; height:18px; border-radius:50%; background:#92400E; border:1px solid #B45309; display:inline-block;" title="Terracotta Rust"></span>
          </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:14px; display:flex; justify-content:space-between; align-items:center;">
          <span style="font-size:12px; color:var(--gold-primary); font-weight:700;"><i class="fa-solid fa-circle-check"></i> 100% Organic Flax</span>
          <a href="<?= APP_URL ?>/book.php" class="btn btn-gold-outline btn-sm" style="font-size:11.5px; padding:6px 14px; border-radius:20px;">Book Swatch</a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 6. 15-Point Digital Doorstep Measurement Quality -->
<section class="section gsap-reveal-section" style="padding: 80px 0; background:#FFFFFF;">
  <div class="container">
    <div style="display:grid; grid-template-columns:1.1fr 1fr; gap:40px; align-items:center;">
      <div>
        <span class="section-tag">Anatomical Precision</span>
        <h2 class="section-title">15 Anatomical Measurement Points</h2>
        <p class="section-desc">Why do off-the-rack garments never fit like bespoke? Because our certified measurement specialists record 15 distinct biometric coordinates right in your living room.</p>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:24px;">
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">1. Collar Circumference & Posture</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">Zero collar gap</p>
          </div>
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">2. Shoulder Slope Angle</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">Anatomical shoulder pads</p>
          </div>
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">3. Chest & Full Bust Arc</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">Princess seam contour</p>
          </div>
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">4. Bicep, Forearm & Wrist</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">Tapered modern sleeves</p>
          </div>
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">5. Waist & Hip Drop Ratio</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">Clean silhouette lines</p>
          </div>
          <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 16px; border-radius:8px;">
            <strong style="color:var(--gold-primary); font-size:13px;">6. Inseam, Outseam & Rise</strong>
            <p style="font-size:12px; color:#64748B; margin:2px 0 0;">No-break trouser hem</p>
          </div>
        </div>

        <div style="margin-top:24px;">
          <a href="<?= APP_URL ?>/book.php" class="btn btn-gold"><i class="fa-solid fa-tape"></i> Experience Doorstep Measurement</a>
        </div>
      </div>

      <!-- Feature Card -->
      <div style="background:#070D1E; border-radius:var(--radius-lg); padding:36px; color:#FFFFFF; border:1px solid rgba(212,175,55,0.3); box-shadow:var(--shadow-lg);">
        <div style="width:50px; height:50px; border-radius:50%; background:rgba(212,175,55,0.2); color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:18px;">
          <i class="fa-solid fa-fingerprint"></i>
        </div>
        <h3 style="font-size:22px; color:#FFFFFF; margin-bottom:12px;">Saved to Your Digital Cloud Docket</h3>
        <p style="color:#94A3B8; font-size:14px; line-height:1.7; margin-bottom:20px;">
          Once measured, your 15 anatomical points are digitally archived into your <strong>MY TAYLOR Profile</strong>. Reorder suits, shirts, and festive kurtas in 1 click anytime without remeasuring!
        </p>
        <div style="display:flex; align-items:center; gap:12px; background:rgba(255,255,255,0.05); padding:12px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
          <i class="fa-solid fa-shield-check text-emerald" style="font-size:20px;"></i>
          <span style="font-size:13px; color:#E2E8F0;">Zero store visits. Lifetime fit record on your mobile.</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 7. Customer Testimonials & Reviews Section (Admin Controlled with Photos) -->
<section id="testimonials" class="section gsap-reveal-section" style="background:#F8FAFC; padding:80px 0; border-top:1px solid #E2E8F0;">
  <div class="container">
    <div class="section-header">
      <span class="section-tag"><i class="fa-solid fa-star text-gold"></i> Client Testimonials</span>
      <h2 class="section-title">Loved by 10,000+ Bespoke Patrons</h2>
      <p class="section-desc">Real stories from corporate executives, brides, and style connoisseurs across Mumbai & prime hubs.</p>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:24px; margin-top:36px;">
      <?php foreach ($testimonials as $t): ?>
        <div class="card gsap-testi-card" style="padding:28px; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; border-top:4px solid var(--gold-primary); transition:transform 0.3s, box-shadow 0.3s;">
          <div>
            <!-- Star Ratings & Garment Tag -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
              <div style="color:#F59E0B; font-size:15px; letter-spacing:2px;">
                <?= str_repeat('★', (int)$t['rating']) ?>
              </div>
              <span style="background:rgba(212,175,55,0.12); color:#B45309; font-size:11px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase;">
                <?= e($t['garment_type'] ?: 'Bespoke Fit') ?>
              </span>
            </div>

            <!-- Review Text -->
            <p style="color:#334155; font-size:14.5px; line-height:1.7; font-style:italic; margin-bottom:20px;">
              "<?= e($t['review_text']) ?>"
            </p>
          </div>

          <!-- Customer Identity Strip -->
          <div style="display:flex; align-items:center; gap:14px; border-top:1px solid #E2E8F0; padding-top:16px;">
            <?php if (!empty($t['image_url'])): ?>
              <img src="<?= APP_URL ?>/<?= ltrim($t['image_url'], '/') ?>" alt="<?= e($t['customer_name']) ?>" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid var(--gold-primary);" onerror="this.src='<?= APP_URL ?>/assets/images/hero_tailor.jpg'">
            <?php else: ?>
              <div style="width:48px; height:48px; border-radius:50%; background:#0B132B; color:var(--gold-primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px;">
                <?= strtoupper(substr($t['customer_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div>
              <strong style="color:#0F172A; font-size:15px; display:flex; align-items:center; gap:6px;">
                <?= e($t['customer_name']) ?> <i class="fa-solid fa-circle-check text-emerald" style="font-size:13px;" title="Verified Customer"></i>
              </strong>
              <div style="color:#64748B; font-size:12px;">
                <?= e($t['customer_role']) ?> • <span style="color:var(--gold-primary);"><?= e($t['location']) ?></span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Live Customer Trust Stats -->
    <div style="display:flex; justify-content:center; align-items:center; gap:30px; margin-top:40px; flex-wrap:wrap; text-align:center;">
      <div>
        <strong style="font-size:24px; color:#0F172A;">4.9 / 5.0</strong>
        <p style="font-size:12px; color:#64748B; margin:0;">Average Google & Concierge Rating</p>
      </div>
      <div style="width:1px; height:30px; background:#CBD5E1;"></div>
      <div>
        <strong style="font-size:24px; color:var(--accent-emerald);">99.4%</strong>
        <p style="font-size:12px; color:#64748B; margin:0;">First-Time Perfect Fit Rate</p>
      </div>
      <div style="width:1px; height:30px; background:#CBD5E1;"></div>
      <div>
        <strong style="font-size:24px; color:#B45309;">24 Hours</strong>
        <p style="font-size:12px; color:#64748B; margin:0;">Guaranteed Doorstep Delivery SLA</p>
      </div>
    </div>
  </div>
</section>

<!-- 8. Three Pillars & Guarantees (Admin CMS Controlled) -->
<section class="section gsap-reveal-section" style="padding: 80px 0;">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">The MY TAYLOR Standard</span>
      <h2 class="section-title"><?= e($aboutTitle) ?></h2>
      <p class="section-desc"><?= e($aboutText) ?></p>
    </div>

    <div class="grid-3" style="margin-top:30px;">
      <div class="card gsap-pillar-card" style="padding:32px; border-top:4px solid var(--gold-primary);">
        <div class="card-icon" style="margin-bottom:18px;"><i class="fa-solid fa-ruler-combined"></i></div>
        <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;"><?= e($p1Title) ?></h3>
        <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin:0;"><?= e($p1Desc) ?></p>
      </div>

      <div class="card gsap-pillar-card" style="padding:32px; border-top:4px solid #B45309;">
        <div class="card-icon" style="margin-bottom:18px;"><i class="fa-solid fa-bolt text-gold"></i></div>
        <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;"><?= e($p2Title) ?></h3>
        <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin:0;"><?= e($p2Desc) ?></p>
      </div>

      <div class="card gsap-pillar-card" style="padding:32px; border-top:4px solid var(--accent-emerald);">
        <div class="card-icon" style="margin-bottom:18px;"><i class="fa-solid fa-house-chimney"></i></div>
        <h3 style="font-size:20px; margin-bottom:8px; color:#0F172A;"><?= e($p3Title) ?></h3>
        <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin:0;"><?= e($p3Desc) ?></p>
      </div>
    </div>
  </div>
</section>

<!-- 9. Frequently Asked Questions (Interactive Accordion) -->
<section class="section gsap-reveal-section" style="background:#F1F5F9; padding:80px 0;">
  <div class="container-sm">
    <div class="section-header">
      <span class="section-tag">Clarity & Confidence</span>
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-desc">Everything you need to know about our doorstep measurement, 24H SLA, and guarantees.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px; margin-top:30px;">
      
      <div class="faq-item" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; overflow:hidden;">
        <button type="button" class="faq-toggle" style="width:100%; text-align:left; padding:18px 20px; background:none; border:none; display:flex; justify-content:space-between; align-items:center; cursor:pointer; font-size:16px; font-weight:700; color:#0F172A;">
          <span>How does the 24-Hour Express Tailoring SLA work?</span>
          <i class="fa-solid fa-chevron-down text-gold faq-icon" style="transition:transform 0.3s;"></i>
        </button>
        <div class="faq-body" style="padding:0 20px 18px; color:#64748B; font-size:14px; line-height:1.7; display:none;">
          Once our certified measurement executive records your 15 points at your doorstep, your garment docket enters our master cutting workshop immediately. It passes precision laser cutting, master stitching with German Gutermann threads, 10-point QC, and is handed over to our express dispatch rider within 24 hours.
        </div>
      </div>

      <div class="faq-item" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; overflow:hidden;">
        <button type="button" class="faq-toggle" style="width:100%; text-align:left; padding:18px 20px; background:none; border:none; display:flex; justify-content:space-between; align-items:center; cursor:pointer; font-size:16px; font-weight:700; color:#0F172A;">
          <span>Can I give my own fabric, or do you bring swatches?</span>
          <i class="fa-solid fa-chevron-down text-gold faq-icon" style="transition:transform 0.3s;"></i>
        </button>
        <div class="faq-body" style="padding:0 20px 18px; color:#64748B; font-size:14px; line-height:1.7; display:none;">
          Both! You can provide your own fabric directly to our measurement specialist during the doorstep visit, or choose from our luxury Italian Merino wool, Egyptian Giza cotton, and pure silk swatch books.
        </div>
      </div>

      <div class="faq-item" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; overflow:hidden;">
        <button type="button" class="faq-toggle" style="width:100%; text-align:left; padding:18px 20px; background:none; border:none; display:flex; justify-content:space-between; align-items:center; cursor:pointer; font-size:16px; font-weight:700; color:#0F172A;">
          <span>What if my garment needs minor fit adjustments?</span>
          <i class="fa-solid fa-chevron-down text-gold faq-icon" style="transition:transform 0.3s;"></i>
        </button>
        <div class="faq-body" style="padding:0 20px 18px; color:#64748B; font-size:14px; line-height:1.7; display:none;">
          We back every single order with our <strong>100% Perfect Fit Guarantee</strong>. If you feel any adjustment is needed during trial, our rider picks it up for complimentary alteration within 24 hours.
        </div>
      </div>

      <div class="faq-item" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; overflow:hidden;">
        <button type="button" class="faq-toggle" style="width:100%; text-align:left; padding:18px 20px; background:none; border:none; display:flex; justify-content:space-between; align-items:center; cursor:pointer; font-size:16px; font-weight:700; color:#0F172A;">
          <span>What payment methods are supported?</span>
          <i class="fa-solid fa-chevron-down text-gold faq-icon" style="transition:transform 0.3s;"></i>
        </button>
        <div class="faq-body" style="padding:0 20px 18px; color:#64748B; font-size:14px; line-height:1.7; display:none;">
          We accept instant online payments via Cashfree (UPI, GPay, PhonePe, Cards, NetBanking) or convenient <strong>Cash on Doorstep (COD)</strong> upon measurement or garment delivery.
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 10. Bottom Call To Action Banner -->
<section class="section" style="background:#070D1E; color:#FFFFFF; padding:70px 0; text-align:center; position:relative; overflow:hidden;">
  <div class="container" style="max-width:800px; position:relative; z-index:2;">
    <span class="badge-24h pulse" style="margin-bottom:14px;"><i class="fa-solid fa-bolt"></i> 24-Hour Express Guarantee</span>
    <h2 style="font-size:clamp(28px, 4vw, 44px); margin-bottom:14px; color:#FFFFFF;">Ready for Bespoke Luxury Tailoring?</h2>
    <p style="color:#CBD5E1; font-size:16px; margin-bottom:28px;">Book your appointment now. Our master executive will visit your doorstep at your preferred 1-hour slot.</p>
    <div style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-lg"><i class="fa-solid fa-calendar-check"></i> Book Doorstep Appointment</a>
      <a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsapp) ?>" target="_blank" class="btn btn-dark btn-lg" style="border:1px solid var(--gold-primary);"><i class="fa-brands fa-whatsapp text-gold"></i> Chat on WhatsApp</a>
    </div>
  </div>
</section>

<!-- GSAP & ScrollTrigger Initialization Script -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Check if GSAP is available
    if (typeof gsap !== 'undefined') {
      gsap.registerPlugin(ScrollTrigger);

      // 1. Hero Entrance Animations
      const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
      tl.from('.gsap-hero-badge', { y: 25, opacity: 0, duration: 0.6 })
        .from('.gsap-hero-title', { y: 35, opacity: 0, duration: 0.8 }, '-=0.3')
        .from('.gsap-hero-sub', { y: 25, opacity: 0, duration: 0.7 }, '-=0.4')
        .from('.gsap-hero-cta .btn', { y: 20, opacity: 0, duration: 0.6, stagger: 0.15 }, '-=0.4')
        .from('.gsap-hero-card', { x: 40, opacity: 0, duration: 0.8 }, '-=0.6')
        .from('.gsap-hero-stats .stat-item', { y: 20, opacity: 0, duration: 0.5, stagger: 0.15 }, '-=0.4');

      // 2. Animated Number Counters
      document.querySelectorAll('.counter-num').forEach(function(counter) {
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        gsap.fromTo(counter, 
          { textContent: 0 },
          { 
            textContent: target, 
            duration: 2, 
            ease: 'power2.out',
            snap: { textContent: 1 },
            scrollTrigger: {
              trigger: counter,
              start: 'top 90%'
            }
          }
        );
      });

      // 3. ScrollTrigger Section Reveals
      gsap.utils.toArray('.gsap-reveal-section').forEach(function(section) {
        const header = section.querySelector('.section-header');
        if (header) {
          gsap.from(header, {
            scrollTrigger: {
              trigger: section,
              start: 'top 85%'
            },
            y: 30,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out'
          });
        }
      });

      // 4. Staggered Journey Cards
      if (document.querySelector('.gsap-journey-cards')) {
        gsap.fromTo('.gsap-step-card', 
          { x: 35, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.gsap-journey-cards',
              start: 'top 85%'
            },
            x: 0,
            opacity: 1,
            duration: 0.7,
            stagger: 0.15,
            ease: 'power2.out'
          }
        );
      }

      // 5. Category Atelier Cards
      if (document.querySelector('.category-grid')) {
        gsap.fromTo('.gsap-cat-card',
          { y: 40, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.category-grid',
              start: 'top 85%'
            },
            y: 0,
            opacity: 1,
            duration: 0.7,
            stagger: 0.15,
            ease: 'power2.out'
          }
        );
      }

      // 6. Fabric Swatch Cards
      if (document.querySelector('#fabricSection')) {
        gsap.fromTo('.gsap-fabric-card',
          { y: 30, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#fabricSection',
              start: 'top 85%'
            },
            y: 0,
            opacity: 1,
            duration: 0.6,
            stagger: 0.12,
            ease: 'power2.out'
          }
        );
      }

      // 7. Testimonial Reviews Cards
      if (document.querySelector('#testimonials')) {
        gsap.fromTo('.gsap-testi-card',
          { y: 35, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#testimonials',
              start: 'top 85%'
            },
            y: 0,
            opacity: 1,
            duration: 0.7,
            stagger: 0.15,
            ease: 'power2.out'
          }
        );
      }
    }

    // Interactive FAQ Accordion Logic
    document.querySelectorAll('.faq-toggle').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const parent = btn.closest('.faq-item');
        const body = parent.querySelector('.faq-body');
        const icon = btn.querySelector('.faq-icon');
        
        const isHidden = (window.getComputedStyle(body).display === 'none');
        
        // Close all other FAQs
        document.querySelectorAll('.faq-body').forEach(b => b.style.display = 'none');
        document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = 'rotate(0deg)');

        if (isHidden) {
          body.style.display = 'block';
          if (icon) icon.style.transform = 'rotate(180deg)';
          if (typeof gsap !== 'undefined') {
            gsap.from(body, { opacity: 0, y: -8, duration: 0.3 });
          }
        }
      });
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
