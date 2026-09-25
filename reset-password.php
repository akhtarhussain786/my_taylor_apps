<?php
$pageTitle = "Set New Password";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$isValidToken = false;
$emailAssociated = null;
$errorMessage = null;
$success = false;

$pdo = getDbConnection();

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT `email`, `expires_at` FROM `password_resets` WHERE `token` = ? AND `expires_at` > NOW() ORDER BY `id` DESC LIMIT 1");
        $stmt->execute([$token]);
        $resetRecord = $stmt->fetch();

        if ($resetRecord) {
            $isValidToken = true;
            $emailAssociated = $resetRecord['email'];
        }
    } catch (Exception $e) {
        $errorMessage = "Database connection error. Please try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || strlen($newPassword) < 6) {
        $errorMessage = "Password must be at least 6 characters in length.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "The two passwords entered do not match. Please verify.";
    } else {
        try {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update user password
            $upd = $pdo->prepare("UPDATE `users` SET `password_hash` = ?, `updated_at` = NOW() WHERE `email` = ?");
            $upd->execute([$newHash, $emailAssociated]);

            // Clear reset tokens for this email
            $del = $pdo->prepare("DELETE FROM `password_resets` WHERE `email` = ?");
            $del->execute([$emailAssociated]);

            $success = true;
            $isValidToken = false; // Prevent resubmission
        } catch (Exception $e) {
            $errorMessage = "Failed to update password: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 20px 90px;">
  <div class="container-sm" style="max-width:490px;">
    <div class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); background:#FFFFFF; border:1px solid #E2E8F0;">
      
      <?php if ($success): ?>
        <div style="text-align:center; padding:10px 0;">
          <div style="width:68px; height:68px; background:#DCFCE7; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:18px;">
            <i class="fa-solid fa-circle-check" style="font-size:36px; color:#16A34A;"></i>
          </div>
          <h1 style="font-size:24px; font-weight:700; color:#0F172A; margin-bottom:10px;">Password Reset Successful!</h1>
          <p style="font-size:14px; color:var(--text-muted); line-height:1.6; margin-bottom:24px;">
            Your account password has been updated securely. You can now log in using your new credentials.
          </p>

          <a href="<?= APP_URL ?>/login.php" class="btn btn-gold btn-block btn-lg" style="text-decoration:none; font-weight:700;">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Account
          </a>
        </div>

      <?php elseif (!$isValidToken && empty($success)): ?>
        <div style="text-align:center; padding:10px 0;">
          <div style="width:68px; height:68px; background:#FEE2E2; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:18px;">
            <i class="fa-solid fa-link-slash" style="font-size:32px; color:#DC2626;"></i>
          </div>
          <h1 style="font-size:22px; font-weight:700; color:#0F172A; margin-bottom:10px;">Invalid or Expired Link</h1>
          <p style="font-size:13.5px; color:var(--text-muted); line-height:1.6; margin-bottom:24px;">
            This password recovery link is either invalid, already used, or has expired (links expire after 60 minutes).
          </p>

          <a href="<?= APP_URL ?>/forgot-password.php" class="btn btn-gold btn-block" style="text-decoration:none; font-weight:700; margin-bottom:12px;">
            <i class="fa-solid fa-rotate-right"></i> Request New Reset Link
          </a>

          <a href="<?= APP_URL ?>/login.php" style="color:var(--text-muted); font-size:13px; text-decoration:none;">
            Back to Sign In
          </a>
        </div>

      <?php else: ?>
        <div style="text-align:center; margin-bottom:24px;">
          <div style="width:60px; height:60px; background:linear-gradient(135deg, rgba(212,175,55,0.15) 0%, rgba(170,124,17,0.15) 100%); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
            <i class="fa-solid fa-lock" style="font-size:24px; color:var(--gold-primary);"></i>
          </div>
          <h1 style="font-size:24px; font-weight:700; color:#0F172A; margin-bottom:8px;">Set New Password</h1>
          <p style="font-size:13px; color:var(--text-muted); margin:0;">
            Resetting password for: <strong style="color:#0F172A;"><?= e($emailAssociated) ?></strong>
          </p>
        </div>

        <?php if ($errorMessage): ?>
          <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:12px 16px; margin-bottom:20px; font-size:13.5px; border-radius:8px;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMessage) ?>
          </div>
        <?php endif; ?>

        <form action="<?= APP_URL ?>/reset-password.php" method="POST">
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="form-group" style="margin-bottom:18px;">
            <label class="form-label" style="font-weight:600; font-size:13px; color:#334155; margin-bottom:6px; display:block;">New Password *</label>
            <div style="position:relative;">
              <input type="password" name="password" id="newPass" class="form-control" placeholder="Minimum 6 characters" required minlength="6" style="padding-right:40px;">
              <i class="fa-solid fa-eye" id="togglePass" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#94A3B8; cursor:pointer;" onclick="toggleVisibility('newPass', 'togglePass')"></i>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:24px;">
            <label class="form-label" style="font-weight:600; font-size:13px; color:#334155; margin-bottom:6px; display:block;">Confirm New Password *</label>
            <div style="position:relative;">
              <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Re-enter your new password" required minlength="6" style="padding-right:40px;">
              <i class="fa-solid fa-eye" id="toggleConfirm" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#94A3B8; cursor:pointer;" onclick="toggleVisibility('confirmPass', 'toggleConfirm')"></i>
            </div>
          </div>

          <button type="submit" class="btn btn-gold btn-block btn-lg" style="font-weight:700;">
            <i class="fa-solid fa-shield-check"></i> Update & Save Password
          </button>
        </form>

        <script>
          function toggleVisibility(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            if (input.type === "password") {
              input.type = "text";
              icon.classList.remove('fa-eye');
              icon.classList.add('fa-eye-slash');
            } else {
              input.type = "password";
              icon.classList.remove('fa-eye-slash');
              icon.classList.add('fa-eye');
            }
          }
        </script>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
