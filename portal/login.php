<?php
$pageTitle = "Staff & Operations Portal Login";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter staff mobile or email and password.";
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE (`email` = ? OR `mobile` = ?) AND `role` != 'customer'");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            // Route to appropriate portal by role
            switch ($user['role']) {
                case 'measurement_executive':
                    header("Location: " . APP_URL . "/portal/executive.php");
                    break;
                case 'cutting_staff':
                    header("Location: " . APP_URL . "/portal/cutting.php");
                    break;
                case 'tailor':
                    header("Location: " . APP_URL . "/portal/tailor.php");
                    break;
                case 'qc_staff':
                    header("Location: " . APP_URL . "/portal/qc.php");
                    break;
                case 'packing_staff':
                    header("Location: " . APP_URL . "/portal/packing.php");
                    break;
                case 'delivery_executive':
                    header("Location: " . APP_URL . "/portal/delivery.php");
                    break;
                case 'admin':
                    header("Location: " . APP_URL . "/admin/index.php");
                    break;
                default:
                    header("Location: " . APP_URL . "/portal/executive.php");
            }
            exit;
        } else {
            $error = "Invalid staff credentials or unauthorized role access.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Operations Login | MY TAYLOR</title>
  <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/logo.jpeg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:#070D1E; color:#FFFFFF; min-height:100vh; display:flex; flex-direction:column; justify-content:center;">

<div class="container" style="max-width:540px; padding:40px 20px;">
  <div class="card card-dark" style="padding:36px; border:1px solid var(--border-dark); border-radius:var(--radius-lg); box-shadow:0 25px 50px rgba(0,0,0,0.6);">
    <div style="text-align:center; margin-bottom:28px;">
      <img src="<?= APP_URL ?>/logo-white.png" alt="Logo" style="height:56px; width:auto; max-width:240px; object-fit:contain; margin-bottom:14px;" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
      <h1 style="font-size:24px; color:#FFFFFF; margin-bottom:4px;">MY TAYLOR Operations Portal</h1>
      <span style="font-size:12px; color:var(--gold-primary); font-weight:700; text-transform:uppercase;">Role-Based Workshop & Field Access</span>
    </div>

    <?php if ($error): ?>
      <div class="card" style="background:#450A0A; border-color:#EF4444; color:#FCA5A5; padding:12px 16px; margin-bottom:20px; font-size:13.5px;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/portal/login.php" method="POST">
      <div class="form-group">
        <label class="form-label" style="color:#CBD5E1;">Staff Email or Mobile *</label>
        <input type="text" name="identifier" class="form-control" placeholder="e.g. exec.vikram@mytaylor.com" value="exec.vikram@mytaylor.com" required style="background:rgba(255,255,255,0.06); border-color:var(--border-dark); color:#FFFFFF;">
      </div>

      <div class="form-group">
        <label class="form-label" style="color:#CBD5E1;">Password *</label>
        <input type="password" name="password" class="form-control" value="password123" required style="background:rgba(255,255,255,0.06); border-color:var(--border-dark); color:#FFFFFF;">
      </div>

      <button type="submit" class="btn btn-gold btn-block btn-lg" style="margin-top:10px;">
        <i class="fa-solid fa-lock"></i> Access Operations Terminal
      </button>
    </form>

    <!-- Quick Role Switcher for Pair Programming / Review -->
    <div style="margin-top:30px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1); font-size:12px;">
      <span style="display:block; font-weight:700; color:var(--gold-primary); margin-bottom:10px; text-transform:uppercase;">
        <i class="fa-solid fa-bolt"></i> 1-Click Operations Role Direct Portals:
      </span>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
        <a href="<?= APP_URL ?>/portal/executive.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-ruler-combined text-gold"></i> Measurement Exec</a>
        <a href="<?= APP_URL ?>/portal/cutting.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-scissors text-gold"></i> Master Cutter</a>
        <a href="<?= APP_URL ?>/portal/tailor.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-shirt text-gold"></i> Tailor Workshop</a>
        <a href="<?= APP_URL ?>/portal/qc.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-award text-gold"></i> QC Lead Inspector</a>
        <a href="<?= APP_URL ?>/portal/packing.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-box-open text-gold"></i> Packaging & QR</a>
        <a href="<?= APP_URL ?>/portal/delivery.php" class="btn btn-dark btn-sm" style="font-size:11px; justify-content:flex-start;"><i class="fa-solid fa-truck-fast text-gold"></i> Delivery Rider</a>
      </div>
      <div style="margin-top:10px; text-align:center;">
        <a href="<?= APP_URL ?>/admin/index.php" style="color:var(--text-light-muted); text-decoration:none; font-size:12px;"><i class="fa-solid fa-shield-halved text-gold"></i> Master Admin Control Hub</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
