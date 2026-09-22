<?php
$pageTitle = "Reset Your Password";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        $message = "If an account exists with {$email}, a secure password reset link has been dispatched to your inbox.";
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 20px 90px;">
  <div class="container-sm" style="max-width:480px;">
    <div class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
      <div style="text-align:center; margin-bottom:24px;">
        <h1 style="font-size:24px; color:#0F172A; margin-bottom:8px;">Recover Password</h1>
        <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Enter your registered email address to receive a secure reset link.</p>
      </div>

      <?php if ($message): ?>
        <div class="card" style="background:#F0FDF4; border-color:#86EFAC; color:#166534; padding:14px; margin-bottom:20px; font-size:13.5px;">
          <i class="fa-solid fa-circle-check"></i> <?= e($message) ?>
        </div>
      <?php endif; ?>

      <form action="<?= APP_URL ?>/forgot-password.php" method="POST">
        <div class="form-group">
          <label class="form-label">Registered Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="e.g. rahul.sharma@example.com" required>
        </div>

        <button type="submit" class="btn btn-gold btn-block">
          <i class="fa-solid fa-paper-plane"></i> Send Password Reset Link
        </button>
      </form>

      <div style="text-align:center; margin-top:20px; font-size:13px;">
        <a href="<?= APP_URL ?>/login.php" style="color:var(--gold-primary); text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Sign In</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
