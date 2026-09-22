<?php
$pageTitle = "Tailoring Services & Bespoke Catalog";
require_once __DIR__ . '/includes/header.php';
$pdo = getDbConnection();

$selectedCat = $_GET['cat'] ?? 'all';
$query = "SELECT * FROM `services` WHERE `status` = 'active'";
$params = [];
if ($selectedCat !== 'all' && in_array($selectedCat, ['men', 'women', 'custom', 'alteration'])) {
    $query .= " AND `category` = ?";
    $params[] = $selectedCat;
}
$query .= " ORDER BY `id` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Also fetch luxury fabrics
$fabrics = $pdo->query("SELECT * FROM `fabrics` WHERE `status` = 'in_stock' ORDER BY `id` ASC")->fetchAll();
?>

<!-- Services Hero -->
<section style="background:#070D1E; color:#FFFFFF; padding:60px 0 40px; border-bottom:1px solid var(--border-dark);">
  <div class="container text-center">
    <span class="section-tag">Bespoke Portfolio</span>
    <h1 style="font-size:42px; margin-bottom:12px;">Crafted for Your Measurements</h1>
    <p style="color:var(--text-light-muted); max-width:600px; margin:0 auto 24px;">
      Explore our handcrafted bespoke tailoring categories for men and women. Every garment includes doorstep measurement, custom fit styling, and 24-hour express options.
    </p>

    <!-- Category Filter Tabs -->
    <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/services.php?cat=all" class="btn <?= $selectedCat === 'all' ? 'btn-gold' : 'btn-dark' ?> btn-sm">All Garments</a>
      <a href="<?= APP_URL ?>/services.php?cat=men" class="btn <?= $selectedCat === 'men' ? 'btn-gold' : 'btn-dark' ?> btn-sm"><i class="fa-solid fa-mars"></i> Men's Collection</a>
      <a href="<?= APP_URL ?>/services.php?cat=women" class="btn <?= $selectedCat === 'women' ? 'btn-gold' : 'btn-dark' ?> btn-sm"><i class="fa-solid fa-venus"></i> Women's Atelier</a>
      <a href="<?= APP_URL ?>/services.php?cat=custom" class="btn <?= $selectedCat === 'custom' ? 'btn-gold' : 'btn-dark' ?> btn-sm"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom Tailoring</a>
      <a href="<?= APP_URL ?>/services.php?cat=alteration" class="btn <?= $selectedCat === 'alteration' ? 'btn-gold' : 'btn-dark' ?> btn-sm"><i class="fa-solid fa-scissors"></i> Alterations</a>
    </div>
  </div>
</section>

<!-- Services Grid -->
<section class="section">
  <div class="container">
    <div class="grid-3">
      <?php if (empty($services)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:40px;">
          <p style="color:var(--text-muted); font-size:16px;">No services found in this category.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($services as $srv): ?>
        <div class="service-card">
          <div class="service-card-body">
            <div class="service-card-header">
              <div>
                <span class="badge-status badge-blue" style="margin-bottom:6px;"><?= strtoupper($srv['category']) ?></span>
                <h3 style="font-size:20px; font-weight:700; color:#0F172A;"><?= e($srv['name']) ?></h3>
              </div>
              <span class="service-price"><?= formatPrice($srv['base_price']) ?></span>
            </div>
            <p><?= e($srv['description']) ?></p>

            <div style="background:#F8FAFC; border-radius:8px; padding:12px; margin-bottom:18px; border:1px solid #E2E8F0; font-size:13px; color:#475569;">
              <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <span><i class="fa-solid fa-bolt text-gold"></i> 24-Hour Express:</span>
                <strong style="color:var(--accent-emerald);">Available (+<?= formatPrice($srv['express_price']) ?>)</strong>
              </div>
              <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <span><i class="fa-solid fa-house text-gold"></i> Doorstep Measurement:</span>
                <strong><?= formatPrice($srv['measurement_fee']) ?></strong>
              </div>
              <div style="display:flex; justify-content:space-between;">
                <span><i class="fa-solid fa-clock text-gold"></i> Standard Turnaround:</span>
                <strong><?= e($srv['estimated_hours']) ?> Hours</strong>
              </div>
            </div>

            <div style="display:flex; gap:10px;">
              <a href="<?= APP_URL ?>/book.php?service_id=<?= $srv['id'] ?>" class="btn btn-gold btn-block">
                <i class="fa-solid fa-calendar-plus"></i> Book Now
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Luxury Fabric Library Section -->
<section class="section" style="background:#F8FAFC; border-top:1px solid var(--border-light);">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Curated Mill Textiles</span>
      <h2 class="section-title">Bring Your Own Fabric, Or Choose Ours</h2>
      <p class="section-desc">You can supply your own cloth or choose from our inventory of certified Egyptian Giza Cottons, Italian Linens, and Banarasi Silks.</p>
    </div>

    <div class="grid-3">
      <?php foreach ($fabrics as $fab): ?>
        <div class="card" style="border-top:3px solid var(--gold-primary);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <span class="badge-status badge-gold"><?= e($fab['category']) ?></span>
            <strong style="font-size:16px; color:#0F172A;"><?= formatPrice($fab['price_per_meter']) ?> / m</strong>
          </div>
          <h4 style="font-size:18px; margin-bottom:6px;"><?= e($fab['name']) ?></h4>
          <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:12px;">
            Pattern: <strong><?= e($fab['pattern']) ?></strong> | Color: <strong><?= e($fab['color']) ?></strong>
          </p>
          <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; color:var(--accent-emerald); font-weight:700;">
            <span><i class="fa-solid fa-circle-check"></i> In Stock (<?= round($fab['stock_meters']) ?>m ready)</span>
            <span style="color:#64748B;">SKU: <?= e($fab['sku']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
