<?php
/**
 * ONE-TIME SETUP SCRIPT.
 * Run this once in your browser (e.g. http://localhost/toilet-monitor/install.php)
 * after creating an empty MySQL database and setting credentials in config/db.php.
 *
 * It will:
 *   1. Run schema.sql to create all tables.
 *   2. Create the default admin account (username: admin) with a securely
 *      generated password hash.
 *
 * DELETE THIS FILE (or move it out of the web root) once setup is complete.
 */

require_once __DIR__ . '/config/config.php';

$DB_HOST = 'localhost';
$DB_NAME = 'synergy1_khoryingna_schoolnotes';
$DB_USER = 'synergy1_yenping';
$DB_PASS = 'R.zb0ZwEuGZ}*fW2';
$DB_PORT = 3306;

$messages = [];
$errorMsg = null;
$done = false;
$adminPassword = $_POST['admin_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strlen($adminPassword) < 6) {
        $errorMsg = 'Please choose an admin password of at least 6 characters.';
    } else {
        try {
            // Connect without a database first, in case it doesn't exist yet.
            $pdoRoot = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4", $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            $sql = str_replace("\r\n", "\n", $sql);
            // Strip full-line comments first so they can never swallow the statement that follows them.
            $lines = array_filter(explode("\n", $sql), fn($line) => !str_starts_with(trim($line), '--'));
            $sql = implode("\n", $lines);
            // Now split into individual statements on semicolons (schema.sql has no semicolons inside string data).
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt === '') continue;
                $pdoRoot->exec($stmt);
            }
            $messages[] = 'Database and tables created (or already existed).';

            $pdo = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute(['admin']);
            if ($check->fetch()) {
                $messages[] = 'An admin account already exists — leaving it unchanged.';
            } else {
                $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $ins = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, full_name, role, must_change_password, status)
                     VALUES ('admin', ?, 'System Administrator', 'admin', 0, 'active')"
                );
                $ins->execute([$hash]);
                $messages[] = 'Admin account created successfully. Username: admin';
            }
            $done = true;
        } catch (Throwable $e) {
            $errorMsg = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Install · <?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:560px;">
  <h3 class="mb-3">Install · <?= htmlspecialchars(APP_NAME) ?></h3>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <?php foreach ($messages as $m): ?>
    <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
  <?php endforeach; ?>

  <?php if ($done): ?>
    <div class="card p-4">
      <p>Setup is complete. You can now log in as <strong>admin</strong>.</p>
      <p class="text-danger small">For security, please delete <code>install.php</code> from the server now.</p>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Go to Login</a>
    </div>
  <?php else: ?>
    <div class="card p-4">
      <p class="text-muted">Before continuing, make sure:</p>
      <ul class="text-muted small">
        <li>You created an empty MySQL database, OR will let this script create it.</li>
        <li>You updated <code>config/db.php</code> with your MySQL host/user/password.</li>
      </ul>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Choose an admin password</label>
          <input type="password" name="admin_password" class="form-control" required minlength="6">
        </div>
        <button class="btn btn-primary w-100">Run Setup</button>
      </form>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
