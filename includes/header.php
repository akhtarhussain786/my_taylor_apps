<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/auth.php';
$currentUser = getCurrentUser();
$activePage = basename($_SERVER['PHP_SELF'], '.php');

$topAnnouncement = getSetting('site_announcement', '⚡ 24-Hour Express Tailoring Guaranteed Across Prime Hubs | Free Doorstep Measurement');
$supportPhone = getSetting('company_phone', '+91 98000 00000');
$whatsappNo = getSetting('company_whatsapp', '9800000000');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' — ' . APP_TAGLINE ?></title>
  <meta name="description" content="On-demand doorstep tailoring, bespoke measurements, live production tracking, and guaranteed 24-hour delivery.">
  
  <!-- Favicon / Logo -->
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  
  <!-- FontAwesome & Custom Sartorial CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">

  <!-- GSAP & ScrollTrigger Animation Engine -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</head>
<body>

<!-- Luxury Consumer Top Ribbon -->
<div style="background:#070D1E; border-bottom:1px solid rgba(212,175,55,0.15); padding:7px 0; font-size:12.5px; color:var(--text-light-muted);">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; align-items:center; gap:12px;">
      <span style="color:#F1F5F9;"><i class="fa-solid fa-bolt text-gold"></i> <?= e($topAnnouncement) ?></span>
    </div>
    <div style="display:flex; align-items:center; gap:16px;">
      <a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsappNo) ?>" target="_blank" style="color:var(--gold-primary); text-decoration:none; font-weight:700; font-size:12px;">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp Concierge
      </a>
      <span style="opacity:0.4;">|</span>
      <a href="tel:<?= preg_replace('/\s+/', '', $supportPhone) ?>" style="color:var(--text-light-muted); text-decoration:none; font-weight:600; font-size:12px;">
        <i class="fa-solid fa-phone text-gold"></i> <?= e($supportPhone) ?>
      </a>
    </div>
  </div>
</div>

<!-- Mobile Backdrop Overlay (Under Navbar) -->
<div id="mobileBackdrop" class="mobile-backdrop"></div>

<!-- Sticky Dark Floating Header Bar -->
<div class="header-floating-wrapper">
  <div class="container" style="padding:0 20px;">
    <!-- Main Sticky Pill Navbar -->
    <header class="navbar-custom">
      <div class="nav-wrapper">
        <!-- Brand Logo -->
        <a href="<?= APP_URL ?>/index.php" class="brand-logo" title="MY TAYLOR — Bespoke Luxury 24H Tailoring">
          <img src="<?= APP_URL ?>/logo-white.png" alt="MY TAYLOR Logo" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
          <div class="brand-text">
            <span class="brand-tagline" style="font-size:10px; letter-spacing:1.5px; font-weight:800; color:var(--gold-primary); text-transform:uppercase;">Tailored in 24 Hours</span>
          </div>
        </a>

        <!-- Navigation Links & Sliding Mobile Drawer -->
        <div class="nav-links" id="mainNavLinks">
          <!-- Drawer Header (Visible on Mobile) -->
          <div class="drawer-header">
            <div style="display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-crown text-gold" style="font-size:16px;"></i>
              <span style="color:#FFFFFF; font-weight:800; font-size:15px; letter-spacing:1px;">MY TAYLOR</span>
            </div>
            <button type="button" id="drawerCloseBtn" class="drawer-close-btn" aria-label="Close Menu">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Nav Items -->
          <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $activePage === 'index' ? 'active' : '' ?>"><i class="fa-solid fa-house text-gold"></i> Home</a>
          <a href="<?= APP_URL ?>/services.php" class="nav-link <?= $activePage === 'services' ? 'active' : '' ?>"><i class="fa-solid fa-scissors text-gold"></i> Services</a>
          <a href="<?= APP_URL ?>/book.php" class="nav-link <?= $activePage === 'book' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-check text-gold"></i> Book Appointment</a>
          <a href="<?= APP_URL ?>/track.php" class="nav-link <?= $activePage === 'track' ? 'active' : '' ?>"><i class="fa-solid fa-location-crosshairs text-gold"></i> Track Order</a>
          <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
            <a href="<?= APP_URL ?>/dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-ruler-combined text-gold"></i> My Profile & Orders</a>
          <?php endif; ?>
          
          <!-- Mobile-only CTA & Concierge inside drawer -->
          <div class="mobile-only-nav">
            <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
              <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-gold btn-sm" style="width:100%; justify-content:center; margin-bottom:10px;"><i class="fa-solid fa-user"></i> My Account (<?= e($currentUser['name']) ?>)</a>
              <a href="<?= APP_URL ?>/logout.php" class="btn btn-dark btn-sm" style="width:100%; justify-content:center;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            <?php else: ?>
              <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-sm" style="width:100%; justify-content:center; margin-bottom:10px; font-weight:800;"><i class="fa-solid fa-tape"></i> Book Appointment</a>
              <a href="<?= APP_URL ?>/login.php" class="btn btn-gold-outline btn-sm" style="width:100%; justify-content:center;"><i class="fa-solid fa-user"></i> Customer Login</a>
            <?php endif; ?>

            <!-- Quick Concierge Contacts in Drawer -->
            <div class="drawer-concierge-box">
              <span style="font-size:11px; text-transform:uppercase; color:var(--gold-primary); font-weight:700; letter-spacing:0.5px;">Direct Concierge</span>
              <div style="display:flex; gap:8px; margin-top:8px;">
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsappNo) ?>" target="_blank" class="btn btn-dark btn-sm" style="flex:1; font-size:12px; border-color:rgba(212,175,55,0.3); color:#10B981;">
                  <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
                <a href="tel:<?= preg_replace('/\s+/', '', $supportPhone) ?>" class="btn btn-dark btn-sm" style="flex:1; font-size:12px; border-color:rgba(212,175,55,0.3);">
                  <i class="fa-solid fa-phone text-gold"></i> Call
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons (Purely Customer-Facing on Desktop) -->
        <div class="nav-actions">
          <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
            <div style="display:flex; align-items:center; gap:10px;">
              <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-user-check text-gold"></i> <?= e($currentUser['name']) ?>
              </a>
              <a href="<?= APP_URL ?>/logout.php" class="btn btn-gold-outline btn-sm" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
          <?php else: ?>
            <a href="<?= APP_URL ?>/login.php" class="btn btn-gold-outline btn-sm"><i class="fa-solid fa-user"></i> Login</a>
            <a href="<?= APP_URL ?>/book.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-tape"></i> Book Appointment</a>
          <?php endif; ?>
        </div>

        <!-- Mobile Hamburger Toggle Button -->
        <button type="button" id="mobileMenuToggle" class="mobile-menu-btn" aria-label="Toggle Navigation Menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>
    </header>
  </div>
</div>

<script>
  // Mobile Navbar Drawer & Backdrop
  document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const closeBtn = document.getElementById('drawerCloseBtn');
    const navLinks = document.getElementById('mainNavLinks');
    const backdrop = document.getElementById('mobileBackdrop');
    
    function toggleMenu(open) {
      const isOpen = open !== undefined ? open : !navLinks.classList.contains('active');
      if (isOpen) {
        navLinks.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
        const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
        if (icon) { icon.classList.remove('fa-bars'); icon.classList.add('fa-xmark'); }
        document.body.style.overflow = 'hidden';
      } else {
        navLinks.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
        const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
        if (icon) { icon.classList.remove('fa-xmark'); icon.classList.add('fa-bars'); }
        document.body.style.overflow = '';
      }
    }

    if (toggleBtn) {
      toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu(false);
      });
    }

    if (backdrop) {
      backdrop.addEventListener('click', function() {
        toggleMenu(false);
      });
    }

    // Close when clicking any nav link
    if (navLinks) {
      navLinks.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
          toggleMenu(false);
        });
      });
    }
  });
</script>
