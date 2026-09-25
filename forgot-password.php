<?php
$pageTitle = "Recover Your Password";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';

$successMessage = null;
$errorMessage = null;
$resetLinkDemo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid registered email address.";
    } else {
        try {
            $pdo = getDbConnection();

            // Look up user
            $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM `users` WHERE `email` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['status'] !== 'suspended') {
                // Generate secure 64-char token
                $token = bin2hex(random_bytes(32));

                // Clean up previous tokens for this email
                $del = $pdo->prepare("DELETE FROM `password_resets` WHERE `email` = ?");
                $del->execute([$email]);

                // Store new token with 1 hour expiry
                $ins = $pdo->prepare("INSERT INTO `password_resets` (`email`, `token`, `expires_at`) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $ins->execute([$email, $token]);

                // Dispatch Email
                $mailResult = sendPasswordResetEmail($pdo, $email, $token);
                
                $successMessage = "If an account exists with <strong>" . e($email) . "</strong>, a secure password reset link has been dispatched to your inbox.";

                // If SMTP is not enabled or in local test environment, provide link helper
                $smtpEnabled = getSetting('smtp_enabled', '0') === '1';
                if (!$smtpEnabled || (isset($mailResult['success']) && !$mailResult['success'])) {
                    $resetLinkDemo = APP_URL . '/reset-password.php?token=' . urlencode($token);
                }
            } else {
                // Security best practice: show identical message even if email not found to avoid user enumeration
                $successMessage = "If an account exists with <strong>" . e($email) . "</strong>, a secure password reset link has been dispatched to your inbox.";
            }
        } catch (Exception $e) {
            $errorMessage = "An error occurred while processing your request. Please try again or contact concierge support.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 20px 90px;">
  <div class="container-sm" style="max-width:490px;">
    <div class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); background:#FFFFFF; border:1px solid #E2E8F0;">
      <div style="text-align:center; margin-bottom:24px;">
        <div style="width:60px; height:60px; background:linear-gradient(135deg, rgba(212,175,55,0.15) 0%, rgba(170,124,17,0.15) 100%); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
          <i class="fa-solid fa-key" style="font-size:24px; color:var(--gold-primary);"></i>
        </div>
        <h1 style="font-size:24px; font-weight:700; color:#0F172A; margin-bottom:8px;">Recover Password</h1>
        <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Enter your registered email address to receive a secure reset link.</p>
      </div>

      <?php if ($errorMessage): ?>
        <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:13px 16px; margin-bottom:20px; font-size:13.5px; border-radius:8px;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMessage) ?>
        </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
        <div class="card" style="background:#F0FDF4; border:1px solid #86EFAC; color:#166534; padding:15px 18px; margin-bottom:20px; font-size:13.5px; border-radius:8px; line-height:1.6;">
          <div style="display:flex; align-items:flex-start; gap:10px;">
            <i class="fa-solid fa-circle-check" style="font-size:16px; margin-top:2px; color:#16A34A;"></i>
            <div><?= $successMessage ?></div>
          </div>
        </div>

        <?php if ($resetLinkDemo): ?>
          <div style="background:#F8FAFC; border:1px solid #CBD5E1; border-radius:8px; padding:16px; margin-bottom:20px; font-size:12.5px; color:#334155;">
            <div style="font-weight:700; color:#0F172A; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
              <i class="fa-solid fa-shield-halved" style="color:var(--gold-primary);"></i> Quick Reset Link (Direct Access):
            </div>
            <p style="margin:0 0 10px; color:#64748B; font-size:11.5px;">Click below to open the reset password screen:</p>
            <a href="<?= $resetLinkDemo ?>" class="btn btn-gold btn-block" style="font-size:13px; padding:10px; text-decoration:none; text-align:center;">
              <i class="fa-solid fa-lock-open"></i> Proceed to Reset Password &rarr;
            </a>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <form action="<?= APP_URL ?>/forgot-password.php" method="POST">
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label" style="font-weight:600; font-size:13px; color:#334155; margin-bottom:6px; display:block;">Registered Email Address</label>
          <div style="position:relative;">
            <input type="email" name="email" class="form-control" placeholder="e.g. mytaylor302@gmail.com" required style="padding-left:38px;" autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <i class="fa-solid fa-envelope" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94A3B8;"></i>
          </div>
        </div>

        <button type="submit" class="btn btn-gold btn-block" style="padding:12px; font-size:14px; font-weight:700;">
          <i class="fa-solid fa-paper-plane"></i> Send Password Reset Link
        </button>
      </form>

      <div style="text-align:center; margin-top:24px; font-size:13px; padding-top:18px; border-top:1px solid #F1F5F9;">
        <a href="<?= APP_URL ?>/login.php" style="color:var(--gold-primary); font-weight:600; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Sign In</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
