<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $loc  = trim($_POST['location'] ?? '');
        if ($code === '' || $name === '') {
            $error = 'Toilet code and name are required.';
        } else {
            $check = $pdo->prepare('SELECT id FROM toilets WHERE code = ?');
            $check->execute([$code]);
            if ($check->fetch()) {
                $error = 'That toilet code is already in use.';
            } else {
                $pdo->prepare('INSERT INTO toilets (code, name, location) VALUES (?, ?, ?)')
                    ->execute([$code, $name, $loc ?: null]);
                flash_set('success', 'Toilet "' . $code . '" added.');
                header('Location: toilets.php'); exit;
            }
        }
    }

    if ($action === 'edit') {
        $id   = (int)$_POST['id'];
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $loc  = trim($_POST['location'] ?? '');
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        if ($code === '' || $name === '') {
            $error = 'Toilet code and name are required.';
        } else {
            $pdo->prepare('UPDATE toilets SET code=?, name=?, location=?, status=? WHERE id=?')
                ->execute([$code, $name, $loc ?: null, $status, $id]);
            flash_set('success', 'Toilet updated.');
            header('Location: toilets.php'); exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $hasHistory = $pdo->prepare('SELECT COUNT(*) FROM toilet_sessions WHERE toilet_id = ?');
        $hasHistory->execute([$id]);
        if ($hasHistory->fetchColumn() > 0) {
            $pdo->prepare("UPDATE toilets SET status='inactive' WHERE id=?")->execute([$id]);
            flash_set('success', 'This toilet has existing history, so it was deactivated instead of deleted (to preserve records).');
        } else {
            $pdo->prepare('DELETE FROM toilets WHERE id=?')->execute([$id]);
            flash_set('success', 'Toilet deleted.');
        }
        header('Location: toilets.php'); exit;
    }
}

$toilets = $pdo->query(
    "SELECT t.*, (SELECT COUNT(*) FROM user_toilets ut WHERE ut.toilet_id = t.id) AS assigned_count
     FROM toilets t ORDER BY t.code"
)->fetchAll();

$pageTitle = 'Manage Toilets';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-building"></i> Manage Toilets</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addToiletModal"><i class="bi bi-plus-lg"></i> Add Toilet</button>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Assigned Users</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($toilets as $t): ?>
        <tr>
          <td><span class="badge bg-info text-dark"><?= e($t['code']) ?></span></td>
          <td><?= e($t['name']) ?></td>
          <td><?= e($t['location']) ?: '<span class="text-muted">—</span>' ?></td>
          <td><?= (int)$t['assigned_count'] ?></td>
          <td><?= $t['status']==='active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editToiletModal<?= (int)$t['id'] ?>">Edit</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete/deactivate this toilet?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <div class="modal fade" id="editToiletModal<?= (int)$t['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <div class="modal-header"><h5 class="modal-title">Edit Toilet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <div class="mb-3"><label class="form-label">Code</label><input class="form-control" name="code" value="<?= e($t['code']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($t['name']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Location</label><input class="form-control" name="location" value="<?= e($t['location']) ?>"></div>
                <div class="mb-1"><label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="active" <?= $t['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $t['status']==='inactive'?'selected':'' ?>>Inactive</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addToiletModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Add New Toilet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Code</label><input class="form-control" name="code" placeholder="e.g. T04" required></div>
        <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" placeholder="e.g. Block C - Level 1 - Male" required></div>
        <div class="mb-1"><label class="form-label">Location <span class="text-muted small">(optional)</span></label><input class="form-control" name="location"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Add Toilet</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
