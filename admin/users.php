<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$error = null;

// ---------------------------------------------------------------
// Handle form submissions (add / edit / delete) via POST actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] === 'admin' ? 'admin' : 'student';
        $password = $_POST['password'] ?? '';

        if ($username === '' || $fullName === '' || strlen($password) < 6) {
            $error = 'Username, full name are required and password must be at least 6 characters.';
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'That username is already taken.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, full_name, email, role, must_change_password, status)
                     VALUES (?, ?, ?, ?, ?, 1, "active")'
                );
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $email ?: null, $role]);
                flash_set('success', 'User "' . $fullName . '" created. They will be asked to set a new password on first login.');
                header('Location: users.php'); exit;
            }
        }
    }

    if ($action === 'edit') {
        $id       = (int)$_POST['id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] === 'admin' ? 'admin' : 'student';
        $status   = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($fullName === '') {
            $error = 'Full name is required.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name=?, email=?, role=?, status=? WHERE id=?');
            $stmt->execute([$fullName, $email ?: null, $role, $status, $id]);

            if (!empty($_POST['reset_password'])) {
                $newPass = $_POST['reset_password'];
                if (strlen($newPass) >= 6) {
                    $upd = $pdo->prepare('UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?');
                    $upd->execute([password_hash($newPass, PASSWORD_DEFAULT), $id]);
                }
            }
            flash_set('success', 'User updated.');
            header('Location: users.php'); exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === (int)current_user()['id']) {
            $error = 'You cannot delete your own account while logged in.';
        } else {
            // Prevent deleting a user who has session history — deactivate instead, to preserve audit trail.
            $hasHistory = $pdo->prepare('SELECT COUNT(*) FROM toilet_sessions WHERE user_id = ?');
            $hasHistory->execute([$id]);
            if ($hasHistory->fetchColumn() > 0) {
                $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$id]);
                flash_set('success', 'This user has existing check-in/out history, so it was deactivated instead of deleted (to preserve records).');
            } else {
                $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
                flash_set('success', 'User deleted.');
            }
            header('Location: users.php'); exit;
        }
    }
}

$users = $pdo->query(
    "SELECT u.*, (SELECT COUNT(*) FROM user_toilets ut WHERE ut.user_id = u.id) AS toilet_count
     FROM users u ORDER BY u.role DESC, u.full_name"
)->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-people"></i> Manage Users</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i> Add User</button>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Assigned Toilets</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><code><?= e($u['username']) ?></code></td>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['email']) ?: '<span class="text-muted">—</span>' ?></td>
          <td><span class="badge <?= $u['role']==='admin' ? 'bg-dark' : 'bg-primary' ?>"><?= e($u['role']) ?></span></td>
          <td><?= (int)$u['toilet_count'] ?></td>
          <td>
            <?php if ($u['status'] === 'active'): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= (int)$u['id'] ?>">Edit</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete/deactivate this user?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>

        <!-- Edit Modal -->
        <div class="modal fade" id="editUserModal<?= (int)$u['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <div class="modal-header"><h5 class="modal-title">Edit User: <?= e($u['username']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="full_name" value="<?= e($u['full_name']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($u['email']) ?>"></div>
                <div class="mb-3"><label class="form-label">Role</label>
                  <select class="form-select" name="role">
                    <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Student / User</option>
                    <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="active" <?= $u['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $u['status']==='inactive'?'selected':'' ?>>Inactive</option>
                  </select>
                </div>
                <div class="mb-1"><label class="form-label">Reset Password <span class="text-muted small">(leave blank to keep current)</span></label>
                  <input type="password" class="form-control" name="reset_password" minlength="6" placeholder="New password (optional)">
                  <div class="form-text">User will be required to change it on next login.</div>
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

<!-- Add Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Add New User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
        <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
        <div class="mb-3"><label class="form-label">Email <span class="text-muted small">(optional)</span></label><input type="email" class="form-control" name="email"></div>
        <div class="mb-3"><label class="form-label">Role</label>
          <select class="form-select" name="role">
            <option value="student" selected>Student / User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="mb-1"><label class="form-label">Initial Password</label><input type="password" class="form-control" name="password" minlength="6" required>
          <div class="form-text">The student will be asked to set a new password on first login.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
