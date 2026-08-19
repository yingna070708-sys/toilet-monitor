<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalToilets = $pdo->query("SELECT COUNT(*) FROM toilets")->fetchColumn();
$activeNow    = $pdo->query("SELECT COUNT(*) FROM toilet_sessions WHERE status='active'")->fetchColumn();
$totalSessions= $pdo->query("SELECT COUNT(*) FROM toilet_sessions")->fetchColumn();

$recent = $pdo->query(
    "SELECT ts.*, t.code, t.name AS toilet_name, u.full_name AS user_name
     FROM toilet_sessions ts
     JOIN toilets t ON t.id = ts.toilet_id
     JOIN users u ON u.id = ts.user_id
     ORDER BY ts.id DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h4>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Students / Users</div>
      <div class="fs-3 fw-bold"><?= (int)$totalUsers ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Toilets</div>
      <div class="fs-3 fw-bold"><?= (int)$totalToilets ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Currently In-Session</div>
      <div class="fs-3 fw-bold text-warning"><?= (int)$activeNow ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Total Records</div>
      <div class="fs-3 fw-bold"><?= (int)$totalSessions ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="users.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-people"></i><br>Manage Users</a></div>
  <div class="col-md-3"><a href="toilets.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-building"></i><br>Manage Toilets</a></div>
  <div class="col-md-3"><a href="assignments.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-link-45deg"></i><br>Assign Toilets</a></div>
  <div class="col-md-3"><a href="history.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-clock-history"></i><br>Full History</a></div>
</div>

<div class="card p-3">
  <h6 class="mb-3">Recent Activity</h6>
  <?php if (!$recent): ?>
    <p class="text-muted mb-0">No check-in/check-out activity yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead><tr><th>Toilet</th><th>Student</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['code']) ?> — <?= e($r['toilet_name']) ?></td>
          <td><?= e($r['user_name']) ?></td>
          <td><?= format_dt($r['checkin_time']) ?></td>
          <td><?= format_dt($r['checkout_time']) ?></td>
          <td>
            <?php if ($r['status'] === 'active'): ?>
              <span class="badge status-badge-active">In Progress</span>
            <?php else: ?>
              <span class="badge status-badge-completed">Completed</span>
            <?php endif; ?>
          </td>
          <td><a href="session_detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
