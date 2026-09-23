<?php
/**
 * MY TAYLOR - Modern Dedicated Admin Header & Sidebar Layout
 */
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/auth.php';

$pdo = getDbConnection();
$currentUser = getCurrentUser();

if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

$activeAdminPage = basename($_SERVER['PHP_SELF'], '.php');

// Quick counts for sidebar badges
$pendingAptCount = (int)$pdo->query("SELECT COUNT(*) FROM `appointments` WHERE `status` IN ('BOOKED', 'EXECUTIVE_ASSIGNED')")->fetchColumn();
$activeOrderCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` NOT IN ('DELIVERED', 'CANCELLED')")->fetchColumn();
$activeStaffCount = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` IN ('delivery_executive', 'measurement_executive') AND `status` = 'active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | Admin Command Hub' : 'MY TAYLOR — Admin Command Center' ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  
  <!-- FontAwesome 6 & Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=<?= time() ?>">
</head>
<body class="admin-body">

  <!-- Overlay backdrop for mobile drawer -->
  <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

  <!-- Left Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <!-- Sidebar Header & Brand -->
    <div class="admin-sidebar-header">
      <a href="<?= APP_URL ?>/admin/index.php" class="admin-sidebar-brand">
        <img src="<?= APP_URL ?>/logo-white.png" alt="MY TAYLOR Logo" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
        <div class="admin-brand-info">
          <span><i class="fa-solid fa-crown"></i> Master Admin Hub</span>
        </div>
      </a>
      <button type="button" class="admin-sidebar-close" id="sidebarCloseBtn" aria-label="Close Sidebar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Scrollable Navigation Area -->
    <div class="admin-sidebar-nav">
      <div class="admin-nav-section-label">Operations Core</div>
      <ul class="admin-nav-list">
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-link <?= $activeAdminPage === 'index' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Command Center</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/appointments.php" class="admin-nav-link <?= $activeAdminPage === 'appointments' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Appointments</span>
            <?php if ($pendingAptCount > 0): ?>
              <span class="admin-badge-count admin-badge-gold"><?= $pendingAptCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/orders.php" class="admin-nav-link <?= $activeAdminPage === 'orders' ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i>
            <span>24H Express Orders</span>
            <?php if ($activeOrderCount > 0): ?>
              <span class="admin-badge-count" style="background:#2563EB; color:#fff;"><?= $activeOrderCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/production.php" class="admin-nav-link <?= $activeAdminPage === 'production' ? 'active' : '' ?>">
            <i class="fa-solid fa-industry"></i>
            <span>Tailoring & Workshop</span>
          </a>
        </li>
      </ul>

      <div class="admin-nav-section-label">Management & Catalog</div>
      <ul class="admin-nav-list">
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/services.php" class="admin-nav-link <?= $activeAdminPage === 'services' ? 'active' : '' ?>">
            <i class="fa-solid fa-scissors"></i>
            <span>Garments & Pricing</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/staff.php" class="admin-nav-link <?= $activeAdminPage === 'staff' ? 'active' : '' ?>">
            <i class="fa-solid fa-users-gear"></i>
            <span>Staff & Delivery Boys</span>
            <span class="admin-badge-count"><?= $activeStaffCount ?></span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/customers.php" class="admin-nav-link <?= $activeAdminPage === 'customers' ? 'active' : '' ?>">
            <i class="fa-solid fa-address-book"></i>
            <span>Customers & Fit Data</span>
          </a>
        </li>
      </ul>

      <div class="admin-nav-section-label">Website & Gateway</div>
      <ul class="admin-nav-list">
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/website-content.php" class="admin-nav-link <?= $activeAdminPage === 'website-content' ? 'active' : '' ?>">
            <i class="fa-solid fa-desktop"></i>
            <span>Website CMS & Texts</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/testimonials.php" class="admin-nav-link <?= $activeAdminPage === 'testimonials' ? 'active' : '' ?>">
            <i class="fa-solid fa-comments"></i>
            <span>Testimonials & Reviews</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/settings.php" class="admin-nav-link <?= $activeAdminPage === 'settings' ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders"></i>
            <span>Cashfree & Controls</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/admin/audit-logs.php" class="admin-nav-link <?= $activeAdminPage === 'audit-logs' ? 'active' : '' ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Security Audit Trail</span>
          </a>
        </li>
        <li class="admin-nav-item">
          <a href="<?= APP_URL ?>/index.php" target="_blank" class="admin-nav-link" style="color:var(--admin-gold);">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>View Public Store</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Sidebar Admin Profile (Fixed Bottom) -->
    <div class="admin-sidebar-footer">
      <div class="admin-user-card">
        <div class="admin-avatar">
          <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="admin-user-details">
          <div class="admin-user-name"><?= e($currentUser['name'] ?? 'Super Admin') ?></div>
          <div class="admin-user-role"><i class="fa-solid fa-circle" style="color:#10B981; font-size:8px;"></i> Master Admin</div>
        </div>
        <a href="<?= APP_URL ?>/logout.php" title="Logout" style="color:#94A3B8; font-size:14px; text-decoration:none;">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Content Container -->
  <div class="admin-main">
    
    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="admin-topbar-left">
        <button type="button" id="sidebarToggle" class="admin-sidebar-toggle-btn" aria-label="Toggle Navigation">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="admin-search-wrapper">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Order ID, Mobile, Customer..." id="adminGlobalSearch" autocomplete="off">
        </div>
      </div>

      <div class="admin-topbar-actions">
        <div class="admin-live-sla-badge" title="24H SLA Dispatch Engine is Active">
          <span class="admin-live-pulse"></span>
          <span class="sla-text-desktop">24H SLA Engine: <strong>LIVE</strong></span>
          <span class="sla-text-mobile">24H <strong>LIVE</strong></span>
        </div>
        
        <a href="<?= APP_URL ?>/admin/settings.php" class="admin-topbar-btn" title="Payment Gateway Settings">
          <i class="fa-solid fa-credit-card"></i>
          <span class="btn-text">Cashfree PG</span>
        </a>

        <a href="<?= APP_URL ?>/portal/delivery.php" target="_blank" class="admin-topbar-btn admin-topbar-btn-primary" title="Rider Dispatch Portal">
          <i class="fa-solid fa-motorcycle"></i>
          <span class="btn-text">Rider Portal</span>
        </a>
      </div>
    </header>

    <!-- Main Dynamic Body Container -->
    <main class="admin-content">
