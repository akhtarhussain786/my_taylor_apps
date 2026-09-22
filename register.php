<?php
$pageTitle = "Create New Account";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? 'male';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($mobile) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match. Please check and retype.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $pdo = getDbConnection();
        // Check duplicate
        $chk = $pdo->prepare("SELECT `id` FROM `users` WHERE `mobile` = ? OR `email` = ?");
        $chk->execute([$mobile, $email]);
        if ($chk->fetch()) {
            $error = "An account with this mobile number or email already exists. Please log in.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `mobile`, `password_hash`, `role`, `status`, `gender`) VALUES (?, ?, ?, ?, 'customer', 'active', ?)");
            $ins->execute([$name, $email, $mobile, $hash, $gender]);
            $userId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO `customer_profiles` (`user_id`, `preferred_language`, `notes`) VALUES (?, 'English', 'New Registered Patron')")->execute([$userId]);

            $uStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch();

            loginUser($user);
            header("Location: " . APP_URL . "/dashboard.php");
            exit;
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 20px 90px;">
  <div class="container-sm" style="max-width:520px;">
    <div class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
      <div style="text-align:center; margin-bottom:28px;">
        <h1 style="font-size:26px; color:#0F172A; margin-bottom:6px;">Create Your Patron Account</h1>
        <p style="font-size:13.5px; color:var(--text-muted); margin:0;">Save your measurements, track live garment creation, and enjoy 24h delivery.</p>
      </div>

      <?php if ($error): ?>
        <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:12px 16px; margin-bottom:20px; font-size:13.5px;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form action="<?= APP_URL ?>/register.php" method="POST">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Mobile Number *</label>
            <input type="tel" name="mobile" class="form-control" placeholder="Enter 10-digit mobile number" maxlength="15" required>
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control form-select">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password (min 6 characters)" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:10px;">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
      </form>

      <div style="text-align:center; margin-top:24px; font-size:13.5px; color:var(--text-muted);">
        Already have an account? <a href="<?= APP_URL ?>/login.php" style="color:var(--gold-primary); font-weight:700; text-decoration:none;">Sign In</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
