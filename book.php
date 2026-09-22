<?php
$pageTitle = "Book Doorstep Measurement Appointment";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/includes/cashfree.php';

$pdo = getDbConnection();

$services = $pdo->query("SELECT * FROM `services` WHERE `status` = 'active' ORDER BY `category`, `id`")->fetchAll();
$selectedServiceId = (int)($_GET['service_id'] ?? 1);
$currentUser = getCurrentUser();

$cfEnabled = getSetting('cashfree_enabled', '1') === '1';
$codEnabled = getSetting('cod_enabled', '1') === '1';

$successAppointment = null;
$errorMessage = null;

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int)($_POST['service_id'] ?? 1);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerMobile = trim($_POST['customer_mobile'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPassword = $_POST['customer_password'] ?? 'password123';

    $houseNo = trim($_POST['house_no'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $city = trim($_POST['city'] ?? 'Mumbai');
    $state = trim($_POST['state'] ?? 'Maharashtra');
    $pincode = trim($_POST['pincode'] ?? '400050');
    $lat = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $lng = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    $appointmentDate = $_POST['appointment_date'] ?? date('Y-m-d');
    $timeSlot = $_POST['time_slot'] ?? '10:00 AM – 11:00 AM';
    $deliveryPref = $_POST['delivery_preference'] ?? '24H_EXPRESS';
    $paymentChoice = $_POST['payment_method'] ?? 'COD'; // COD or CASHFREE_ONLINE
    $specialNotes = trim($_POST['special_notes'] ?? '');

    if (empty($customerName) || empty($customerMobile) || empty($houseNo) || empty($pincode)) {
        $errorMessage = "Please fill in all mandatory details (Name, Mobile, House/Street & Pincode).";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Identify or Create User
            $userId = null;
            if ($currentUser) {
                $userId = $currentUser['id'];
            } else {
                $chk = $pdo->prepare("SELECT `id`, `name`, `email`, `mobile`, `role` FROM `users` WHERE `mobile` = ? OR `email` = ?");
                $chk->execute([$customerMobile, $customerEmail ?: $customerMobile . '@mytaylor.local']);
                $existingUser = $chk->fetch();

                if ($existingUser) {
                    $userId = $existingUser['id'];
                } else {
                    $hash = password_hash($customerPassword ?: 'password123', PASSWORD_BCRYPT);
                    $emailToUse = !empty($customerEmail) ? $customerEmail : "user_" . preg_replace('/\D/', '', $customerMobile) . "@mytaylor.local";
                    $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `mobile`, `password_hash`, `role`, `status`) VALUES (?, ?, ?, ?, 'customer', 'active')");
                    $ins->execute([$customerName, $emailToUse, $customerMobile, $hash]);
                    $userId = $pdo->lastInsertId();

                    $pdo->prepare("INSERT INTO `customer_profiles` (`user_id`, `notes`) VALUES (?, 'Created via Doorstep Booking Flow')")->execute([$userId]);
                }

                $uStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
                $uStmt->execute([$userId]);
                $loggedUser = $uStmt->fetch();
                if ($loggedUser) {
                    loginUser($loggedUser);
                }
            }

            // 2. Save Address
            $addrStmt = $pdo->prepare("INSERT INTO `addresses` (`user_id`, `title`, `house_no`, `building`, `street`, `area`, `landmark`, `city`, `state`, `pincode`, `latitude`, `longitude`, `is_default`) VALUES (?, 'Home', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $addrStmt->execute([$userId, $houseNo, $building, $street, $area, $landmark, $city, $state, $pincode, $lat, $lng]);
            $addressId = $pdo->lastInsertId();

            // 3. Assign Available Measurement Executive (or first active)
            $exec = $pdo->query("SELECT `id` FROM `users` WHERE `role` = 'measurement_executive' AND `status` = 'active' LIMIT 1")->fetch();
            $executiveId = $exec ? $exec['id'] : 2;

            // 4. Generate Appointment Code & Insert
            $aptCode = generateAppointmentCode($pdo);
            $aptStmt = $pdo->prepare("INSERT INTO `appointments` (`appointment_code`, `customer_id`, `service_id`, `address_id`, `executive_id`, `appointment_date`, `time_slot`, `status`, `notes`, `delivery_preference`) VALUES (?, ?, ?, ?, ?, ?, ?, 'BOOKED', ?, ?)");
            $aptStmt->execute([$aptCode, $userId, $serviceId, $addressId, $executiveId, $appointmentDate, $timeSlot, $specialNotes, $deliveryPref]);
            $appointmentId = $pdo->lastInsertId();

            // 5. If Cashfree Online Payment selected, handle gateway session
            $payMethodLabel = ($paymentChoice === 'COD') ? 'Cash on Delivery (COD)' : 'Cashfree Online Gateway';
            $paymentStatus = ($paymentChoice === 'COD') ? 'PENDING' : 'PAID';

            logAudit(null, $appointmentId, $userId, 'APPOINTMENT_BOOKED', null, 'BOOKED', "Appointment {$aptCode} booked by {$customerName}. Payment: {$payMethodLabel}");

            $pdo->commit();

            // Cashfree PG Order Generation
            $cashfreePaymentUrl = null;
            if ($paymentChoice === 'CASHFREE_ONLINE') {
                $cf = new CashfreeGateway();
                $svc = $pdo->query("SELECT * FROM `services` WHERE `id` = {$serviceId}")->fetch();
                $totalAmount = (float)($svc['base_price'] ?? 1500) + (($deliveryPref === '24H_EXPRESS') ? (float)($svc['express_price'] ?? 499) : 0);
                
                $returnUrl = APP_URL . '/payment-verify.php';
                $cfResp = $cf->createOrder($aptCode, $totalAmount, $customerName, $customerMobile, $customerEmail, $returnUrl);
                
                // If Cashfree returns payment session or sandbox test URL
                if (!empty($cfResp['payment_session_id'])) {
                    // Stored session ID for client-side Cashfree JS SDK
                    $cashfreeSessionId = $cfResp['payment_session_id'];
                }
            }

            // Fetch confirmation details
            $successStmt = $pdo->prepare("
              SELECT a.*, s.name as service_name, s.base_price, s.express_price, s.measurement_fee, u.name as exec_name
              FROM `appointments` a
              JOIN `services` s ON a.service_id = s.id
              LEFT JOIN `users` u ON a.executive_id = u.id
              WHERE a.id = ?
            ");
            $successStmt->execute([$appointmentId]);
            $successAppointment = $successStmt->fetch();
            $successAppointment['payment_choice'] = $paymentChoice;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Booking error: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="padding: 40px 20px 80px;">
  <?php if ($successAppointment): ?>
    <!-- Booking Confirmation Screen -->
    <div class="container-sm">
      <div class="card" style="text-align:center; padding:40px; border-top:6px solid var(--accent-emerald);">
        <div style="width:70px; height:70px; border-radius:50%; background:#D1FAE5; color:var(--accent-emerald); font-size:32px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
          <i class="fa-solid fa-check"></i>
        </div>
        <span class="badge-24h pulse" style="margin-bottom:10px;">Appointment Confirmed</span>
        <h2 style="font-size:30px; margin-bottom:8px;">Doorstep Measurement Scheduled!</h2>
        <p style="color:var(--text-muted); font-size:15px; margin-bottom:24px;">
          Your appointment details have reached the Admin Master Hub. An executive is assigned to your slot.
        </p>

        <div style="background:#F8FAFC; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:24px; text-align:left; margin-bottom:28px;">
          <div style="display:flex; justify-content:space-between; margin-bottom:12px; border-bottom:1px solid #E2E8F0; padding-bottom:8px;">
            <span style="color:#64748B; font-size:13px;">Appointment Code:</span>
            <strong style="font-size:16px; color:var(--gold-primary);"><?= e($successAppointment['appointment_code']) ?></strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span style="color:#64748B; font-size:13px;">Garment Service:</span>
            <strong><?= e($successAppointment['service_name']) ?></strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span style="color:#64748B; font-size:13px;">Scheduled Slot:</span>
            <strong><?= date('d M Y', strtotime($successAppointment['appointment_date'])) ?> | <?= e($successAppointment['time_slot']) ?></strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span style="color:#64748B; font-size:13px;">Payment Method:</span>
            <strong style="color:var(--accent-emerald);">
              <?= ($successAppointment['payment_choice'] === 'COD') ? '💵 Cash on Doorstep (COD)' : '⚡ Paid Online via Cashfree' ?>
            </strong>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span style="color:#64748B; font-size:13px;">Delivery SLA:</span>
            <strong style="color:#B45309;"><i class="fa-solid fa-bolt text-gold"></i> 24-Hour Express Guarantee</strong>
          </div>
        </div>

        <div style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
          <a href="<?= APP_URL ?>/track.php?booking_id=<?= urlencode($successAppointment['appointment_code']) ?>" class="btn btn-gold btn-lg"><i class="fa-solid fa-location-crosshairs"></i> Track Order Live</a>
          <a href="<?= APP_URL ?>/index.php" class="btn btn-dark btn-lg"><i class="fa-solid fa-house text-gold"></i> Back to Home</a>
        </div>
      </div>
    </div>

  <?php else: ?>

    <div class="section-header">
      <span class="section-tag">Frictionless Experience</span>
      <h1 class="section-title">Schedule Doorstep Measurement</h1>
      <p class="section-desc">Zero shop visits. Pick your garment, specify your address, and choose Cashfree Online or Cash on Doorstep.</p>
    </div>

    <?php if ($errorMessage): ?>
      <div class="card" style="background:#FEF2F2; border-color:#F87171; color:#991B1B; padding:14px 20px; margin-bottom:24px;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= e($errorMessage) ?>
      </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/book.php" method="POST" class="card" style="padding:36px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
      
      <!-- Step 1: Service Selection -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <div style="display:flex; align-items:center; gap:10px;">
            <span style="width:28px; height:28px; border-radius:50%; background:#0B132B; color:#D4AF37; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">1</span>
            <h3 style="font-size:20px; font-weight:700;">Select Garment Service</h3>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Garment Category & Tailoring Type</label>
          <select name="service_id" class="form-control form-select" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $s['id'] == $selectedServiceId ? 'selected' : '' ?>>
                [<?= strtoupper($s['category']) ?>] <?= e($s['name']) ?> — <?= formatPrice($s['base_price']) ?> (Express: +<?= formatPrice($s['express_price']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <hr style="border:none; border-top:1px solid var(--border-light); margin:28px 0;">

      <!-- Step 2: Location & GPS -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <div style="display:flex; align-items:center; gap:10px;">
            <span style="width:28px; height:28px; border-radius:50%; background:#0B132B; color:#D4AF37; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">2</span>
            <h3 style="font-size:20px; font-weight:700;">Doorstep Location & Address</h3>
          </div>
          <button type="button" id="btn-detect-location" class="btn btn-gold-outline btn-sm">
            <i class="fa-solid fa-crosshairs text-gold"></i> Use My Current GPS Location
          </button>
        </div>
        <div id="geo-status" style="font-size:12.5px; margin-bottom:12px; font-weight:600;"></div>

        <input type="hidden" name="latitude" id="input-lat" value="19.0600">
        <input type="hidden" name="longitude" id="input-lng" value="72.8258">

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">House / Flat / Unit No. *</label>
            <input type="text" name="house_no" class="form-control" placeholder="e.g. Flat 402" value="Flat 402" required>
          </div>
          <div class="form-group">
            <label class="form-label">Building / Apartment / Complex</label>
            <input type="text" name="building" class="form-control" placeholder="e.g. Imperial Heights" value="Imperial Heights">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Street & Area *</label>
            <input type="text" name="street" class="form-control" placeholder="e.g. Pali Hill Road, Bandra West" value="Pali Hill Road" required>
          </div>
          <div class="form-group">
            <label class="form-label">Prominent Landmark</label>
            <input type="text" name="landmark" class="form-control" placeholder="e.g. Near Cafe Basilico" value="Near Cafe Basilico">
          </div>
        </div>

        <div class="grid-3">
          <div class="form-group">
            <label class="form-label">Area / Locality *</label>
            <input type="text" name="area" class="form-control" placeholder="e.g. Bandra West" value="Bandra West" required>
          </div>
          <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-control" value="Mumbai" required>
          </div>
          <div class="form-group">
            <label class="form-label">Pincode (6-Digits) *</label>
            <input type="text" name="pincode" class="form-control" value="400050" maxlength="6" required>
          </div>
        </div>
      </div>

      <hr style="border:none; border-top:1px solid var(--border-light); margin:28px 0;">

      <!-- Step 3: Date & Slot Picker -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
          <span style="width:28px; height:28px; border-radius:50%; background:#0B132B; color:#D4AF37; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">3</span>
          <h3 style="font-size:20px; font-weight:700;">Choose Measurement Date & Time Slot</h3>
        </div>

        <div class="form-group">
          <label class="form-label">Preferred Date *</label>
          <input type="date" name="appointment_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Select 1-Hour Arrival Slot *</label>
          <input type="hidden" name="time_slot" id="selected-time-slot" value="10:00 AM – 11:00 AM">
          
          <div class="slot-grid">
            <div class="slot-item" data-slot="08:00 AM – 09:00 AM">08:00 AM – 09:00 AM</div>
            <div class="slot-item" data-slot="09:00 AM – 10:00 AM">09:00 AM – 10:00 AM</div>
            <div class="slot-item selected" data-slot="10:00 AM – 11:00 AM">10:00 AM – 11:00 AM</div>
            <div class="slot-item" data-slot="11:00 AM – 12:00 PM">11:00 AM – 12:00 PM</div>
            <div class="slot-item" data-slot="12:00 PM – 01:00 PM">12:00 PM – 01:00 PM</div>
            <div class="slot-item" data-slot="02:00 PM – 03:00 PM">02:00 PM – 03:00 PM</div>
            <div class="slot-item" data-slot="03:00 PM – 04:00 PM">03:00 PM – 04:00 PM</div>
            <div class="slot-item" data-slot="04:00 PM – 05:00 PM">04:00 PM – 05:00 PM</div>
            <div class="slot-item" data-slot="05:00 PM – 06:00 PM">05:00 PM – 06:00 PM</div>
            <div class="slot-item" data-slot="06:00 PM – 07:00 PM">06:00 PM – 07:00 PM</div>
          </div>
        </div>

        <div class="form-group" style="margin-top:20px;">
          <label class="form-label">Delivery SLA Option</label>
          <div class="grid-2">
            <label style="display:flex; align-items:center; gap:12px; background:#F8FAFC; border:1px solid #CBD5E1; padding:16px; border-radius:var(--radius-sm); cursor:pointer;">
              <input type="radio" name="delivery_preference" value="24H_EXPRESS" checked>
              <div>
                <strong style="color:#0F172A;"><i class="fa-solid fa-bolt text-gold"></i> 24-Hour Express Delivery</strong>
                <p style="font-size:12.5px; color:var(--text-muted); margin:0;">Delivered strictly within 24 hours of measurement completion.</p>
              </div>
            </label>

            <label style="display:flex; align-items:center; gap:12px; background:#F8FAFC; border:1px solid #CBD5E1; padding:16px; border-radius:var(--radius-sm); cursor:pointer;">
              <input type="radio" name="delivery_preference" value="STANDARD">
              <div>
                <strong style="color:#0F172A;"><i class="fa-solid fa-clock"></i> Standard Tailoring (36-48h)</strong>
                <p style="font-size:12.5px; color:var(--text-muted); margin:0;">Standard turnaround time with regular dispatch.</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <hr style="border:none; border-top:1px solid var(--border-light); margin:28px 0;">

      <!-- Step 4: Payment Gateway & Method Selection -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <div style="display:flex; align-items:center; gap:10px;">
            <span style="width:28px; height:28px; border-radius:50%; background:#0B132B; color:#D4AF37; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">4</span>
            <h3 style="font-size:20px; font-weight:700;">Select Payment Method</h3>
          </div>
        </div>

        <div class="grid-2" style="gap:16px;">
          <?php if ($codEnabled): ?>
            <label style="display:flex; align-items:center; gap:14px; background:#F8FAFC; border:2px solid #CBD5E1; padding:18px; border-radius:var(--radius-md); cursor:pointer;">
              <input type="radio" name="payment_method" value="COD" checked>
              <div>
                <strong style="font-size:16px; color:#0F172A;"><i class="fa-solid fa-money-bill-wave" style="color:var(--accent-emerald);"></i> Cash on Doorstep / COD</strong>
                <p style="font-size:13px; color:var(--text-muted); margin:2px 0 0;">Pay when the measurement executive visits your home or on garment handover.</p>
              </div>
            </label>
          <?php endif; ?>

          <?php if ($cfEnabled): ?>
            <label style="display:flex; align-items:center; gap:14px; background:#F8FAFC; border:2px solid #CBD5E1; padding:18px; border-radius:var(--radius-md); cursor:pointer;">
              <input type="radio" name="payment_method" value="CASHFREE_ONLINE" <?= !$codEnabled ? 'checked' : '' ?>>
              <div>
                <strong style="font-size:16px; color:#0F172A;"><i class="fa-solid fa-credit-card text-gold"></i> Pay Online via Cashfree</strong>
                <p style="font-size:13px; color:var(--text-muted); margin:2px 0 0;">Instant UPI (GPay/PhonePe), Credit/Debit Card & Net Banking via Cashfree Gateway.</p>
              </div>
            </label>
          <?php endif; ?>
        </div>
      </div>

      <hr style="border:none; border-top:1px solid var(--border-light); margin:28px 0;">

      <!-- Step 5: Contact Details -->
      <div style="margin-bottom:32px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
          <span style="width:28px; height:28px; border-radius:50%; background:#0B132B; color:#D4AF37; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">5</span>
          <h3 style="font-size:20px; font-weight:700;">Customer Contact Information</h3>
        </div>

        <div class="grid-3">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="customer_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($currentUser['name'] ?? 'Rahul Sharma') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number *</label>
            <input type="tel" name="customer_mobile" class="form-control" placeholder="e.g. 9876543210" value="<?= e($currentUser['mobile'] ?? '9876543210') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="customer_email" class="form-control" placeholder="e.g. rahul@example.com" value="<?= e($currentUser['email'] ?? 'rahul.sharma@example.com') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Fitting / Style Instructions (Optional)</label>
          <textarea name="special_notes" rows="2" class="form-control" placeholder="e.g. Sleeve thoda loose rakhna, preference for cutaway collars..."></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-gold btn-lg btn-block">
        <i class="fa-solid fa-calendar-check"></i> Confirm Doorstep Measurement Appointment
      </button>
    </form>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
