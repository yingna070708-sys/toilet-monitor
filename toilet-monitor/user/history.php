<?php
require_once __DIR__ . '/../includes/functions.php';
require_student();

$user = current_user();

$stmt = $pdo->prepare(
    "SELECT ts.*, t.code, t.name AS toilet_name
     FROM toilet_sessions ts
     JOIN toilets t ON t.id = ts.toilet_id
     WHERE ts.user_id = ?
     ORDER BY ts.checkin_time DESC
     LIMIT 100"
);
$stmt->execute([$user['id']]);
$sessions = $stmt->fetchAll();

$pageTitle = 'My History';
include __DIR__ . '/../includes/header.php';
?>
<a href="index.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> My Toilets</a>
<h4 class="mb-3"><i class="bi bi-clock-history"></i> My Check-In / Check-Out History</h4>

<div class="card p-3">
  <?php if (!$sessions): ?>
    <p class="text-muted mb-0">You have no check-in/check-out records yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Toilet</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($sessions as $s): ?>
        <tr>
          <td><span class="badge bg-info text-dark"><?= e($s['code']) ?></span> <?= e($s['toilet_name']) ?></td>
          <td><?= format_dt($s['checkin_time']) ?></td>
          <td><?= format_dt($s['checkout_time']) ?></td>
          <td><?= $s['status']==='active' ? '<span class="badge status-badge-active">In Progress</span>' : '<span class="badge status-badge-completed">Completed</span>' ?></td>
          <td><a href="toilet.php?id=<?= (int)$s['toilet_id'] ?>" class="btn btn-sm btn-outline-secondary">Go to Toilet</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
