<?php
$pageTitle = "Security & Audit Trail";
require_once __DIR__ . '/includes/admin_header.php';

$logs = $pdo->query("
  SELECT l.*, u.name as user_name, u.role as user_role, o.booking_id
  FROM `audit_logs` l
  LEFT JOIN `users` u ON l.user_id = u.id
  LEFT JOIN `orders` o ON l.order_id = o.id
  ORDER BY l.id DESC LIMIT 100
")->fetchAll();
?>

<!-- Title & Action Header -->
<div class="admin-header-row">
  <div class="admin-title-area">
    <h1>Security Audit Trail & Action Logs</h1>
    <p>Immutable forensic ledger of status changes, price updates, rider dispatches, and gateway events.</p>
  </div>
  <div>
    <span class="admin-badge-count admin-badge-gold" style="font-size:14px; padding:6px 14px;">Logged Events: <?= count($logs) ?></span>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h3><i class="fa-solid fa-shield-halved text-gold"></i> Immutable Action Log</h3>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Action / Event</th>
          <th>Docket Reference</th>
          <th>Triggered By</th>
          <th>State Transition</th>
          <th>Description & IP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td style="white-space:nowrap; color:#64748B; font-size:12px;">
              <i class="fa-solid fa-clock text-gold"></i> <?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?>
            </td>
            <td>
              <span class="badge-status booked"><?= e($l['action']) ?></span>
            </td>
            <td>
              <strong style="color:var(--admin-gold); font-family:'Outfit'; font-size:13px;">
                <?= e($l['booking_id'] ?? ($l['appointment_id'] ? "APT #{$l['appointment_id']}" : 'Global Settings')) ?>
              </strong>
            </td>
            <td>
              <strong style="font-size:13px; color:#0F172A;"><?= e($l['user_name'] ?? 'System Process') ?></strong>
              <div style="font-size:11px; color:#64748B;"><?= strtoupper(str_replace('_', ' ', $l['user_role'] ?? 'SYSTEM')) ?></div>
            </td>
            <td>
              <?php if ($l['previous_state'] || $l['new_state']): ?>
                <span style="color:#64748B; font-size:11.5px;"><?= e($l['previous_state'] ?: 'NONE') ?></span>
                <i class="fa-solid fa-arrow-right" style="font-size:10px; color:#94A3B8; margin:0 4px;"></i>
                <strong style="color:var(--accent-emerald); font-size:11.5px;"><?= e($l['new_state']) ?></strong>
              <?php else: ?>
                <span style="color:#94A3B8;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size:12.5px; color:#334155;"><?= e($l['description']) ?></div>
              <div style="font-size:10.5px; color:#94A3B8;">IP: <?= e($l['ip_address'] ?? '127.0.0.1') ?></div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
