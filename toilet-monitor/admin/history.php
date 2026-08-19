<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$toilets = $pdo->query("SELECT * FROM toilets ORDER BY code")->fetchAll();
$students = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY full_name")->fetchAll();

$filterToilet = (int)($_GET['toilet_id'] ?? 0);
$filterUser   = (int)($_GET['user_id'] ?? 0);
$filterFrom   = trim($_GET['from'] ?? '');
$filterTo     = trim($_GET['to'] ?? '');
$filterStatus = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($filterToilet) { $where[] = 'ts.toilet_id = ?'; $params[] = $filterToilet; }
if ($filterUser)   { $where[] = 'ts.user_id = ?';   $params[] = $filterUser; }
if ($filterFrom)   { $where[] = 'DATE(ts.checkin_time) >= ?'; $params[] = $filterFrom; }
if ($filterTo)     { $where[] = 'DATE(ts.checkin_time) <= ?'; $params[] = $filterTo; }
if ($filterStatus === 'active' || $filterStatus === 'completed') {
    $where[] = 'ts.status = ?'; $params[] = $filterStatus;
}

$sql = "SELECT ts.*, t.code, t.name AS toilet_name, u.full_name AS user_name
        FROM toilet_sessions ts
        JOIN toilets t ON t.id = ts.toilet_id
        JOIN users u ON u.id = ts.user_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ts.checkin_time DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$pageTitle = 'Full History';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-clock-history"></i> Complete Usage & Cleanliness History</h4>

<div class="card p-3 mb-3">
  <form class="row g-2" method="get">
    <div class="col-md-3">
      <label class="form-label small">Toilet</label>
      <select class="form-select" name="toilet_id">
        <option value="0">All Toilets</option>
        <?php foreach ($toilets as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $filterToilet==$t['id']?'selected':'' ?>><?= e($t['code']) ?> — <?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Student</label>
      <select class="form-select" name="user_id">
        <option value="0">All Students</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $filterUser==$s['id']?'selected':'' ?>><?= e($s['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">From</label>
      <input type="date" class="form-control" name="from" value="<?= e($filterFrom) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">To</label>
      <input type="date" class="form-control" name="to" value="<?= e($filterTo) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Status</label>
      <select class="form-select" name="status">
        <option value="">All</option>
        <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>In Progress</option>
        <option value="completed" <?= $filterStatus==='completed'?'selected':'' ?>>Completed</option>
      </select>
    </div>
    <div class="col-12">
      <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
      <a href="history.php" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
  </form>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table align-middle table-hover">
      <thead>
        <tr><th>Toilet</th><th>Student</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (!$sessions): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No records match these filters.</td></tr>
      <?php endif; ?>
      <?php foreach ($sessions as $s): ?>
        <tr>
          <td><span class="badge bg-info text-dark"><?= e($s['code']) ?></span> <?= e($s['toilet_name']) ?></td>
          <td><?= e($s['user_name']) ?></td>
          <td><?= format_dt($s['checkin_time']) ?></td>
          <td><?= format_dt($s['checkout_time']) ?></td>
          <td><?= $s['status']==='active' ? '<span class="badge status-badge-active">In Progress</span>' : '<span class="badge status-badge-completed">Completed</span>' ?></td>
          <td><a href="session_detail.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary">View Details</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-muted small mb-0">Showing up to 300 most recent matching records.</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
