<?php
require_once __DIR__ . '/../config/config.php';
?>
<!-- Luxury Footer -->
<footer class="footer-custom">
  <div class="container">
    <div class="footer-grid">
      <!-- Brand & Mission -->
      <div class="footer-col">
        <div style="display:flex; flex-direction:column; align-items:flex-start; gap:8px; margin-bottom:18px;">
          <img src="<?= APP_URL ?>/logo-white.png" alt="MY TAYLOR Logo" style="height:48px; width:auto; max-width:220px; object-fit:contain;" onerror="this.src='<?= APP_URL ?>/logo-tight.png'">
          <span style="color:var(--gold-primary); font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Tailored for You. Delivered in 24 Hours.</span>
        </div>
        <p style="color:var(--text-light-muted); font-size:14px; line-height:1.7; margin-bottom:20px;">
          India's pioneering doorstep tailoring ecosystem. Experience bespoke craftsmanship, live production tracking, and 24-hour express delivery.
        </p>
        <div style="display:flex; gap:12px;">
          <a href="#" class="btn btn-dark btn-sm" style="width:36px; height:36px; padding:0; border-radius:50%;"><i class="fa-brands fa-instagram text-gold"></i></a>
          <a href="#" class="btn btn-dark btn-sm" style="width:36px; height:36px; padding:0; border-radius:50%;"><i class="fa-brands fa-whatsapp text-gold"></i></a>
        </div>
      </div>

      <!-- Quick Services -->
      <div class="footer-col">
        <h4>Tailoring Services</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/services.php?cat=men"><i class="fa-solid fa-angle-right text-gold"></i> Men's Bespoke Shirts</a></li>
          <li><a href="<?= APP_URL ?>/services.php?cat=men"><i class="fa-solid fa-angle-right text-gold"></i> Tailored Formal Trousers</a></li>
          <li><a href="<?= APP_URL ?>/services.php?cat=men"><i class="fa-solid fa-angle-right text-gold"></i> Bespoke Blazers & Suits</a></li>
          <li><a href="<?= APP_URL ?>/services.php?cat=women"><i class="fa-solid fa-angle-right text-gold"></i> Designer Saree Blouses</a></li>
          <li><a href="<?= APP_URL ?>/services.php?cat=alteration"><i class="fa-solid fa-angle-right text-gold"></i> Express Fit Alterations</a></li>
        </ul>
      </div>

      <!-- Customer Hub -->
      <div class="footer-col">
        <h4>Customer Concierge</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/book.php"><i class="fa-solid fa-angle-right text-gold"></i> Book Doorstep Appointment</a></li>
          <li><a href="<?= APP_URL ?>/track.php"><i class="fa-solid fa-angle-right text-gold"></i> Live Order Tracking</a></li>
          <li><a href="<?= APP_URL ?>/dashboard.php"><i class="fa-solid fa-angle-right text-gold"></i> Digital Measurement Profile</a></li>
          <li><a href="<?= APP_URL ?>/login.php"><i class="fa-solid fa-angle-right text-gold"></i> Customer Sign In</a></li>
        </ul>
      </div>

      <!-- Atelier & Support Contact -->
      <div class="footer-col">
        <h4>Atelier Concierge</h4>
        <p style="font-size:13.5px; color:var(--text-light-muted); line-height:1.8; margin-bottom:14px;">
          <i class="fa-solid fa-phone text-gold"></i> <?= e(getSetting('company_phone', '+91 98000 00000')) ?><br>
          <i class="fa-solid fa-envelope text-gold"></i> <?= e(getSetting('company_email', 'concierge@mytaylor.com')) ?><br>
          <i class="fa-solid fa-location-dot text-gold"></i> <?= e(getSetting('company_address', 'Prime Bespoke Atelier, Bandra West, Mumbai 400050')) ?>
        </p>
        <div style="display:flex; gap:10px; margin-top:12px;">
          <a href="https://wa.me/<?= preg_replace('/\D/', '', getSetting('company_whatsapp', '9800000000')) ?>" target="_blank" class="btn btn-gold btn-sm" style="flex:1; font-size:12px;">
            <i class="fa-brands fa-whatsapp"></i> Chat with Us
          </a>
          <a href="<?= APP_URL ?>/book.php" class="btn btn-dark btn-sm" style="flex:1; font-size:12px; border-color:rgba(212,175,55,0.3);">
            <i class="fa-solid fa-tape text-gold"></i> Book Fit
          </a>
        </div>
      </div>
    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <p>© <?= date('Y') ?> <strong>MY TAYLOR</strong>. All rights reserved. 24-Hour Bespoke Tailoring Ecosystem.</p>
        <div style="display:flex; gap:18px; font-size:12px;">
          <a href="#" style="color:#64748B; text-decoration:none;">Privacy Policy</a>
          <a href="#" style="color:#64748B; text-decoration:none;">Terms of Service</a>
          <a href="#" style="color:#64748B; text-decoration:none;">24H SLA Guarantee</a>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
