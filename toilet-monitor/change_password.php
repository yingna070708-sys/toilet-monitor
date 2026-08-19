<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$isFirstTime = $user['must_change_password'];
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (!$isFirstTime && !password_verify($current, $row['password_hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
        $upd->execute([$hash, $user['id']]);

        // refresh session flag
        $_SESSION['user']['must_change_password'] = false;

        flash_set('success', 'Password updated successfully.');
        header('Location: ' . BASE_URL . '/' . ($user['role'] === 'admin' ? 'admin/index.php' : 'user/index.php'));
        exit;
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4 mt-2">
      <h4 class="mb-3"><i class="bi bi-key"></i> Change Password</h4>
      <?php if ($isFirstTime): ?>
        <div class="alert alert-info">This is your first login. Please set your own password to continue.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <?php if (!$isFirstTime): ?>
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" minlength="6" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" minlength="6" required>
        </div>
        <button class="btn btn-primary w-100">Save Password</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
