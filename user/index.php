<?php
require_once __DIR__ . '/../includes/functions.php';
require_student();

$user = current_user();
$toilets = get_assigned_toilets($pdo, $user['id']);

// If exactly one toilet is assigned, jump straight to it.
if (count($toilets) === 1) {
    header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toilets[0]['id']);
    exit;
}

$pageTitle = 'My Toilets';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-house-door"></i> Welcome, <?= e($user['full_name']) ?></h4>

<?php if (!$toilets): ?>
  <div class="alert alert-warning">You have not been assigned to any toilet yet. Please contact the administrator.</div>
<?php else: ?>
  <p class="text-muted">You are assigned to <?= count($toilets) ?> toilets. Select one to check in or check out.</p>
  <div class="row g-3">
    <?php foreach ($toilets as $t): ?>
      <div class="col-md-4">
        <a href="toilet.php?id=<?= (int)$t['id'] ?>" class="text-decoration-none">
          <div class="card p-3 toilet-card h-100">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-info text-dark fs-6"><?= e($t['code']) ?></span>
            </div>
            <div class="fw-semibold text-dark"><?= e($t['name']) ?></div>
            <div class="text-muted small"><?= e($t['location']) ?></div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
