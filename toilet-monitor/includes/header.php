<?php
/**
 * Shared header. Include AFTER setting $pageTitle, and after
 * includes/functions.php has already been required by the caller.
 */
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?><?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  :root { --brand: #0d6efd; }
  body { background: #f4f6f9; padding-bottom: 3rem; }
  .navbar-brand i { color: #9fd3ff; }
  .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: .75rem; }
  .status-badge-active { background:#fff3cd; color:#664d03; }
  .status-badge-completed { background:#d1e7dd; color:#0f5132; }
  .photo-thumb { width: 90px; height: 90px; object-fit: cover; border-radius: .5rem; cursor: pointer; border: 1px solid #dee2e6; }
  .timeline-item { border-left: 3px solid #0d6efd; padding-left: 1rem; margin-bottom: 1.5rem; }
  .toilet-card:hover { transform: translateY(-2px); transition: .15s; box-shadow: 0 4px 10px rgba(0,0,0,.12); }
  footer.app-footer { color:#94a1b2; font-size:.85rem; text-align:center; margin-top:3rem; }
</style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark navbar-expand-lg mb-4">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/<?= $user && $user['role']==='admin' ? 'admin/index.php' : ($user ? 'user/index.php' : 'login.php') ?>">
      <i class="bi bi-droplet-half"></i> <?= e(APP_NAME) ?>
    </a>
    <?php if ($user): ?>
    <div class="d-flex align-items-center gap-3">
      <?php if ($user['role'] === 'admin'): ?>
        <div class="btn-group">
          <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-sm btn-outline-light">Dashboard</a>
          <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-sm btn-outline-light">Users</a>
          <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-sm btn-outline-light">Toilets</a>
          <a href="<?= BASE_URL ?>/admin/assignments.php" class="btn btn-sm btn-outline-light">Assignments</a>
          <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-sm btn-outline-light">History</a>
        </div>
      <?php elseif ($user['role'] === 'student'): ?>
        <div class="btn-group">
          <a href="<?= BASE_URL ?>/user/index.php" class="btn btn-sm btn-outline-light">My Toilets</a>
          <a href="<?= BASE_URL ?>/user/history.php" class="btn btn-sm btn-outline-light">My History</a>
        </div>
      <?php endif; ?>
      <span class="text-light small"><i class="bi bi-person-circle"></i> <?= e($user['full_name']) ?></span>
      <a href="<?= BASE_URL ?>/change_password.php" class="btn btn-sm btn-outline-light">Change Password</a>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
    <?php endif; ?>
  </div>
</nav>
<div class="container">
  <?php $flash = flash_get(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show" role="alert">
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
</div>
<div class="container">
