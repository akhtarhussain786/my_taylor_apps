<?php
$pageTitle = "Customer Intelligence & Fit Profiles";
require_once __DIR__ . '/includes/admin_header.php';

$customers = $pdo->query("
  SELECT u.*, cp.notes as patron_notes, cp.total_orders,
         COUNT(DISTINCT m.id) as measurement_count
  FROM `users` u
  LEFT JOIN `customer_profiles` cp ON u.id = cp.user_id
  LEFT JOIN `measurements` m ON u.id = m.customer_id
  WHERE u.role = 'customer'
  GROUP BY u.id
  ORDER BY u.id DESC
")->fetchAll();

$measurements = $pdo->query("
  SELECT m.*, u.name as customer_name, u.mobile as customer_mobile
  FROM `measurements` m
  JOIN `users` u ON m.customer_id = u.id
  ORDER BY m.id DESC
")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Customer Directory & Anatomical Fit Data</h1>
    <p>View registered patrons and their reusable digital measurement profiles for 1-click reordering.</p>
  </div>
  <div>
    <span class="admin-badge-count admin-badge-gold" style="font-size:14px; padding:6px 14px;">Patrons: <?= count($customers) ?></span>
  </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; align-items:start;">
  
  <!-- Customers Registry -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3><i class="fa-solid fa-users text-gold"></i> Registered Customers</h3>
    </div>
    <div class="admin-table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Saved Profiles</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:10px;">
                  <div style="width:34px; height:34px; border-radius:50%; background:#0F172A; color:var(--admin-gold); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">
                    <?= strtoupper(substr($c['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <strong style="color:#0F172A; font-size:13.5px;"><?= e($c['name']) ?></strong>
                    <div style="font-size:11px; color:#64748B;">ID #<?= $c['id'] ?></div>
                  </div>
                </div>
              </td>
              <td>
                <strong style="font-size:12.5px; color:#0F172A;"><i class="fa-solid fa-phone"></i> <?= e($c['mobile']) ?></strong>
                <div style="font-size:11.5px; color:#64748B;"><?= e($c['email']) ?></div>
              </td>
              <td>
                <span class="badge-status booked" style="font-size:11px;"><?= $c['measurement_count'] ?> Profiles</span>
              </td>
              <td>
                <span class="badge-status delivered" style="font-size:10.5px;"><?= strtoupper($c['status']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Measurement Profiles -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3><i class="fa-solid fa-tape text-gold"></i> Digital Measurement Dockets</h3>
    </div>
    <div class="admin-table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Docket Code</th>
            <th>Customer & Garment</th>
            <th>Body Metrics</th>
            <th>Fit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($measurements as $m): ?>
            <?php $mJson = json_decode($m['measurements_json'], true) ?: []; ?>
            <tr>
              <td>
                <strong style="color:var(--admin-gold); font-family:'Outfit'; font-size:13.5px;"><?= e($m['measurement_code']) ?></strong>
                <div style="font-size:11px; color:#64748B;"><?= date('d M Y', strtotime($m['created_at'])) ?></div>
              </td>
              <td>
                <strong style="font-size:13px; color:#0F172A;"><?= e($m['customer_name']) ?></strong>
                <div style="font-size:11.5px; color:#64748B;"><?= e($m['garment_category']) ?></div>
              </td>
              <td>
                <div style="font-size:11.5px; line-height:1.4; color:#334155;">
                  <?php foreach ($mJson as $param => $val): ?>
                    <span style="display:inline-block; margin-right:6px;"><strong><?= ucfirst($param) ?>:</strong> <?= $val ?>"</span>
                  <?php endforeach; ?>
                </div>
              </td>
              <td>
                <span class="badge-status qc" style="font-size:11px;"><?= e($m['fit_preference']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
