<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'save_assignments' && $userId) {
        $toiletIds = array_map('intval', $_POST['toilet_ids'] ?? []);

        // Replace the user's assignments with the submitted set (many-to-many).
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM user_toilets WHERE user_id = ?')->execute([$userId]);
        if ($toiletIds) {
            $ins = $pdo->prepare('INSERT INTO user_toilets (user_id, toilet_id) VALUES (?, ?)');
            foreach (array_unique($toiletIds) as $tid) {
                $ins->execute([$userId, $tid]);
            }
        }
        $pdo->commit();
        flash_set('success', 'Toilet assignments updated.');
        header('Location: assignments.php?user_id=' . $userId);
        exit;
    }
}

$students = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY full_name")->fetchAll();
$toilets  = $pdo->query("SELECT * FROM toilets WHERE status='active' ORDER BY code")->fetchAll();

$selectedUserId = (int)($_GET['user_id'] ?? ($students[0]['id'] ?? 0));
$assignedIds = [];
if ($selectedUserId) {
    $stmt = $pdo->prepare('SELECT toilet_id FROM user_toilets WHERE user_id = ?');
    $stmt->execute([$selectedUserId]);
    $assignedIds = array_column($stmt->fetchAll(), 'toilet_id');
}

// Also build a quick summary: toilet -> list of assigned student names
$summaryStmt = $pdo->query(
    "SELECT t.code, t.name, u.full_name
     FROM user_toilets ut
     JOIN toilets t ON t.id = ut.toilet_id
     JOIN users u ON u.id = ut.user_id
     ORDER BY t.code, u.full_name"
);
$summary = [];
foreach ($summaryStmt as $row) {
    $summary[$row['code'] . ' — ' . $row['name']][] = $row['full_name'];
}

$pageTitle = 'Toilet Assignments';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-link-45deg"></i> Assign Toilets to Users</h4>
<p class="text-muted">A user can be assigned to one or several toilets, and a toilet can have several assigned users (many-to-many).</p>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="mb-3">Select Student</h6>
      <select class="form-select mb-3" onchange="window.location='assignments.php?user_id='+this.value">
        <?php foreach ($students as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $s['id']==$selectedUserId?'selected':'' ?>><?= e($s['full_name']) ?> (<?= e($s['username']) ?>)</option>
        <?php endforeach; ?>
      </select>

      <?php if (!$students): ?>
        <p class="text-muted">No student accounts yet — add one on the Users page first.</p>
      <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_assignments">
        <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
        <div class="list-group mb-3">
          <?php foreach ($toilets as $t): ?>
            <label class="list-group-item">
              <input class="form-check-input me-2" type="checkbox" name="toilet_ids[]" value="<?= (int)$t['id'] ?>"
                <?= in_array($t['id'], $assignedIds) ? 'checked' : '' ?>>
              <span class="badge bg-info text-dark"><?= e($t['code']) ?></span> <?= e($t['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <button class="btn btn-primary w-100">Save Assignments</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card p-3">
      <h6 class="mb-3">Current Assignments Overview</h6>
      <?php if (!$summary): ?>
        <p class="text-muted mb-0">No assignments have been made yet.</p>
      <?php else: ?>
        <?php foreach ($summary as $toiletLabel => $names): ?>
          <div class="mb-2">
            <div class="fw-semibold small"><?= e($toiletLabel) ?></div>
            <div class="text-muted small"><?= e(implode(', ', $names)) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
