<?php
$pageTitle = "Cashfree Gateway & System Settings";
require_once __DIR__ . '/includes/admin_header.php';

$msg = null;
$error = null;

    // Handle Settings Save
    if (isset($_POST['save_settings'])) {
        $cashfreeAppId = trim($_POST['cashfree_app_id'] ?? '');
        $cashfreeSecret = trim($_POST['cashfree_secret_key'] ?? '');
        $cashfreeMode = $_POST['cashfree_mode'] ?? 'TEST';
        $cashfreeEnabled = isset($_POST['cashfree_enabled']) ? '1' : '0';
        $codEnabled = isset($_POST['cod_enabled']) ? '1' : '0';
        
        $measFee = (float)($_POST['default_measurement_fee'] ?? 99.00);
        $expressFee = (float)($_POST['default_express_fee'] ?? 199.00);
        $phone = trim($_POST['company_phone'] ?? '+91 98000 00000');
        $email = trim($_POST['company_email'] ?? 'concierge@mytaylor.com');
        $slaHours = (int)($_POST['sla_guarantee_hours'] ?? 24);

        saveSettings([
            'cashfree_app_id'        => $cashfreeAppId,
            'cashfree_secret_key'    => $cashfreeSecret,
            'cashfree_mode'          => $cashfreeMode,
            'cashfree_enabled'       => $cashfreeEnabled,
            'cod_enabled'            => $codEnabled,
            'default_measurement_fee'=> $measFee,
            'default_express_fee'    => $expressFee,
            'company_phone'          => $phone,
            'company_email'          => $email,
            'sla_guarantee_hours'    => $slaHours
        ]);

        logAudit(null, null, $currentUser['id'], 'SETTINGS_UPDATED', null, 'SAVED', "Admin updated Cashfree API keys and platform fees");
        $msg = "Settings & Cashfree Gateway configuration saved successfully!";
    }

    // Handle Reset / Clear Demo Orders & Revenue
    if (isset($_POST['reset_demo_orders'])) {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE `delivery_proofs`;");
            $pdo->exec("TRUNCATE TABLE `production_tasks`;");
            $pdo->exec("TRUNCATE TABLE `order_items`;");
            $pdo->exec("TRUNCATE TABLE `orders`;");
            $pdo->exec("TRUNCATE TABLE `appointments`;");
            $pdo->exec("TRUNCATE TABLE `measurements`;");
            $pdo->exec("TRUNCATE TABLE `reviews`;");
            $pdo->exec("TRUNCATE TABLE `audit_logs`;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            logAudit(null, null, $currentUser['id'], 'PRODUCTION_RESET', null, 'CLEAN', "Admin reset demo orders and cleared sample revenue");
            $msg = "All demo orders, appointments, and fake revenue have been wiped! Total Revenue is now reset to ₹0.00 (Ready for real customers).";
        } catch (Exception $e) {
            $error = "Reset failed: " . $e->getMessage();
        }
    }
}

// Current Values
$appId = getSetting('cashfree_app_id', '');
$secretKey = getSetting('cashfree_secret_key', '');
$mode = getSetting('cashfree_mode', 'TEST');
$cfEnabled = getSetting('cashfree_enabled', '1') === '1';
$codEnabled = getSetting('cod_enabled', '1') === '1';
$measFee = getSetting('default_measurement_fee', '99.00');
$expressFee = getSetting('default_express_fee', '199.00');
$phone = getSetting('company_phone', '+91 98000 00000');
$email = getSetting('company_email', 'concierge@mytaylor.com');
$slaHours = getSetting('sla_guarantee_hours', '24');
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Platform & Cashfree Settings</h1>
    <p>Configure Cashfree Payment Gateway credentials, Cash on Delivery (COD), and platform service fees.</p>
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

<form action="<?= APP_URL ?>/admin/settings.php" method="POST">

  <!-- 1. Cashfree Payment Gateway Box -->
  <div class="admin-card" style="border-top: 4px solid var(--admin-gold);">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-credit-card text-gold"></i>
        Cashfree Payment Gateway Integration
      </h3>
      <span class="badge-status <?= $mode === 'PROD' ? 'delivered' : 'qc' ?>">
        <?= $mode === 'PROD' ? '⚡ LIVE PRODUCTION MODE' : '🧪 SANDBOX TEST MODE' ?>
      </span>
    </div>

    <div class="admin-card-body">
      <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:var(--admin-radius-sm); padding:14px; margin-bottom:20px; color:#92400E; font-size:13px;">
        <i class="fa-solid fa-circle-info"></i> <strong>Zero-Code Setup:</strong> Enter your credentials from the Cashfree Merchant Dashboard (<a href="https://merchant.cashfree.com/" target="_blank" style="color:#B45309; font-weight:700;">merchant.cashfree.com</a>). All transactions, webhooks, and checkouts will use these settings automatically.
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:18px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Cashfree App ID (Client ID) *</label>
          <input type="text" name="cashfree_app_id" class="form-control" placeholder="e.g. TEST103829..." value="<?= e($appId) ?>">
          <small style="color:#64748B; font-size:11.5px;">Found in Cashfree Dashboard &gt; Developers &gt; API Keys</small>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Cashfree Secret Key *</label>
          <input type="password" name="cashfree_secret_key" class="form-control" placeholder="••••••••••••••••••••••••••••••" value="<?= e($secretKey) ?>">
          <small style="color:#64748B; font-size:11.5px;">Secret key used for secure server-to-server signing</small>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:18px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Gateway Environment Mode *</label>
          <select name="cashfree_mode" class="form-control form-select">
            <option value="TEST" <?= $mode === 'TEST' ? 'selected' : '' ?>>🧪 Sandbox / Testing Mode (test.cashfree.com)</option>
            <option value="PROD" <?= $mode === 'PROD' ? 'selected' : '' ?>>⚡ Production / Live Mode (api.cashfree.com)</option>
          </select>
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Webhook Endpoint URL</label>
          <input type="text" class="form-control" value="<?= APP_URL ?>/api/cashfree-webhook.php" readonly style="background:#F1F5F9; color:#475569; font-family:monospace; font-size:12px;">
          <small style="color:#64748B; font-size:11.5px;">Paste this URL in your Cashfree Webhooks dashboard</small>
        </div>
      </div>

      <!-- Payment Method Toggles -->
      <div style="background:#F8FAFC; border:1px solid var(--admin-border); border-radius:var(--admin-radius-sm); padding:16px; margin-top:16px;">
        <h4 style="font-size:14px; margin:0 0 12px; color:#0F172A;">Allowed Customer Payment Methods on Booking:</h4>
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13.5px;">
            <input type="checkbox" name="cashfree_enabled" value="1" <?= $cfEnabled ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-gold);">
            <span><i class="fa-solid fa-credit-card text-gold"></i> Enable Cashfree Online Payments (UPI, Cards, NetBanking)</span>
          </label>

          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13.5px;">
            <input type="checkbox" name="cod_enabled" value="1" <?= $codEnabled ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent-emerald);">
            <span><i class="fa-solid fa-money-bill-wave" style="color:var(--accent-emerald);"></i> Enable Cash on Doorstep / COD Option</span>
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Platform SLA & Fees Box -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>
        <i class="fa-solid fa-sliders text-gold"></i>
        Platform Default Fees & Contact Info
      </h3>
    </div>

    <div class="admin-card-body">
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:18px;">
        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Default Measurement Fee (₹)</label>
          <input type="number" name="default_measurement_fee" class="form-control" value="<?= e($measFee) ?>">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Default 24H Express Fee (₹)</label>
          <input type="number" name="default_express_fee" class="form-control" value="<?= e($expressFee) ?>">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Customer Care Phone</label>
          <input type="text" name="company_phone" class="form-control" value="<?= e($phone) ?>">
        </div>

        <div>
          <label style="font-size:12.5px; font-weight:700; color:#0F172A; display:block; margin-bottom:4px;">Concierge Email</label>
          <input type="email" name="company_email" class="form-control" value="<?= e($email) ?>">
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:24px;">
        <button type="submit" name="save_settings" value="1" class="btn btn-sm" style="background:var(--admin-gold); color:#0F172A; font-weight:800; border-radius:8px; padding:12px 30px; font-size:14px;">
          <i class="fa-solid fa-floppy-disk"></i> Save & Apply Configuration
        </button>
      </div>
    </div>
  </div>

</form>

<!-- Database Production Clean / Reset Card -->
<div class="admin-card" style="border: 1px solid #FECACA; background: #FFF5F5; margin-top: 30px;">
  <div class="admin-card-header" style="border-bottom: 1px solid #FEE2E2;">
    <h3 style="color: #991B1B;">
      <i class="fa-solid fa-broom text-gold"></i>
      Production Database Cleanup (Wipe Demo Orders & Revenue)
    </h3>
  </div>
  <div class="admin-card-body">
    <p style="font-size: 13.5px; color: #7F1D1D; margin-bottom: 16px;">
      Agar aap demo/test ke dauran bane hue dummy orders aur fake revenue ko hatana chahte hain, toh neeche diye gaye button par click karein. Isse <strong>Services (Kapde/Rates)</strong> aur <strong>Staff Accounts</strong> safe rahenge, lekin fake orders aur fake revenue delete hokar strictly <strong>₹0.00</strong> par reset ho jayenge.
    </p>
    <form method="POST" onsubmit="return confirm('Kya aap sach me saare dummy orders aur sample revenue ko reset karna chahte hain?');">
      <button type="submit" name="reset_demo_orders" value="1" class="btn btn-sm" style="background: #DC2626; color: #FFFFFF; font-weight: 700; border-radius: 8px; padding: 10px 20px;">
        <i class="fa-solid fa-trash-can"></i> Reset Demo Orders & Set Revenue to ₹0.00
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
