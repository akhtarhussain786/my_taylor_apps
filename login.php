<?php
$pageTitle = "Sign In to Your Account";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter your registered mobile/email and password.";
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? OR `mobile` = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            if ($user['role'] === 'customer') {
                header("Location: " . APP_URL . "/dashboard.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: " . APP_URL . "/admin/index.php");
            } else {
                header("Location: " . APP_URL . "/portal/login.php");
            }
            exit;
        } else {
            $error = "Invalid mobile/email or password. Please try again.";
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 20px 90px;">
  <div class="container-sm" style="max-width:480px;">
    <div class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
      <div style="text-align:center; margin-bottom:28px;">
        <img src="<?= APP_URL ?>/logo-tight.png" alt="MY TAYLOR Logo" style="height:56px; width:auto; max-width:240px; object-fit:contain; margin-bottom:14px;" onerror="this.src='<?= APP_URL ?>/logo.jpeg'">
        <h1 style="font-size:26px; color:#0F172A; margin-bottom:6px;">Sign In to MY TAYLOR</h1>
        <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Access your saved measurements, active 24h orders & reorders.</p>
      </div>

      <?php if ($error): ?>
        <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:12px 16px; margin-bottom:20px; font-size:13.5px;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form action="<?= APP_URL ?>/login.php" method="POST">
        <div class="form-group">
          <label class="form-label">Mobile Number or Email *</label>
          <input type="text" name="identifier" class="form-control" placeholder="e.g. 9876543210 or rahul.sharma@example.com" value="9876543210" required autofocus>
        </div>

        <div class="form-group">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <label class="form-label" style="margin:0;">Password *</label>
            <a href="<?= APP_URL ?>/forgot-password.php" style="font-size:12px; color:var(--gold-primary); text-decoration:none;">Forgot password?</a>
          </div>
          <input type="password" name="password" class="form-control" placeholder="••••••••" value="password123" required>
        </div>

        <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:10px;">
          <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
        </button>
      </form>

      <div style="text-align:center; margin-top:24px; font-size:13.5px; color:var(--text-muted);">
        Don't have an account? <a href="<?= APP_URL ?>/register.php" style="color:var(--gold-primary); font-weight:700; text-decoration:none;">Register Now</a>
      </div>

      <!-- Quick 1-Click Demo Login Assist -->
      <div style="margin-top:28px; padding-top:20px; border-top:1px solid #E2E8F0; font-size:12px;">
        <span style="display:block; font-weight:700; color:#64748B; margin-bottom:8px; text-transform:uppercase;">Demo Customer Account:</span>
        <div style="background:#F8FAFC; padding:10px 12px; border-radius:6px; border:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
          <span>Mobile: <strong>9876543210</strong><br>Pass: <strong>password123</strong></span>
          <span class="badge-status badge-gold">Patron: Rahul</span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
