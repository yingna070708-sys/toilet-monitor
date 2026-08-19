<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            $error = 'Invalid username or password.';
        } elseif ($row['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact the administrator.';
        } else {
            log_in_user($row);
            if ($row['must_change_password']) {
                header('Location: ' . BASE_URL . '/change_password.php?first=1');
            } elseif ($row['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/user/index.php');
            }
            exit;
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4 mt-4">
      <div class="text-center mb-3">
        <i class="bi bi-droplet-half display-4 text-primary"></i>
        <h4 class="mt-2">Sign in</h4>
        <p class="text-muted small">Toilet Cleanliness Monitoring System</p>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
      <p class="text-muted small mt-3 mb-0">Accounts are created by the Administrator. First-time users will be asked to set their own password after logging in.</p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
