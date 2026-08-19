<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT ts.*, t.code, t.name AS toilet_name, u.full_name AS user_name, u.username
     FROM toilet_sessions ts
     JOIN toilets t ON t.id = ts.toilet_id
     JOIN users u ON u.id = ts.user_id
     WHERE ts.id = ?"
);
$stmt->execute([$id]);
$session = $stmt->fetch();

if (!$session) {
    flash_set('error', 'Record not found.');
    header('Location: history.php');
    exit;
}

$photos = get_session_photos($pdo, $id);

$pageTitle = 'Session Detail';
include __DIR__ . '/../includes/header.php';
?>
<a href="history.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to History</a>

<div class="card p-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1"><span class="badge bg-info text-dark"><?= e($session['code']) ?></span> <?= e($session['toilet_name']) ?></h5>
      <div class="text-muted">Student: <strong><?= e($session['user_name']) ?></strong> (<?= e($session['username']) ?>)</div>
    </div>
    <div><?= $session['status']==='active' ? '<span class="badge status-badge-active fs-6">In Progress</span>' : '<span class="badge status-badge-completed fs-6">Completed</span>' ?></div>
  </div>
  <hr>

  <div class="row">
    <div class="col-md-6">
      <h6 class="text-primary"><i class="bi bi-box-arrow-in-right"></i> Check-In</h6>
      <p class="mb-1 text-muted small">Date/Time</p>
      <p><?= format_dt($session['checkin_time']) ?></p>
      <p class="mb-1 text-muted small">Comment</p>
      <p><?= $session['checkin_comment'] ? nl2br(e($session['checkin_comment'])) : '<span class="text-muted">No comment</span>' ?></p>
      <p class="mb-1 text-muted small">Photos (before)</p>
      <div class="d-flex flex-wrap gap-2">
        <?php if (!$photos['checkin']): ?><span class="text-muted">No photos uploaded</span><?php endif; ?>
        <?php foreach ($photos['checkin'] as $p): ?>
          <img src="<?= BASE_URL ?>/<?= e($p['photo_path']) ?>" class="photo-thumb">
        <?php endforeach; ?>
      </div>
    </div>

    <div class="col-md-6">
      <h6 class="text-success"><i class="bi bi-box-arrow-left"></i> Check-Out</h6>
      <?php if ($session['status'] === 'active'): ?>
        <p class="text-muted">Not checked out yet.</p>
      <?php else: ?>
        <p class="mb-1 text-muted small">Date/Time</p>
        <p><?= format_dt($session['checkout_time']) ?></p>
        <p class="mb-1 text-muted small">Comment</p>
        <p><?= $session['checkout_comment'] ? nl2br(e($session['checkout_comment'])) : '<span class="text-muted">No comment</span>' ?></p>
        <p class="mb-1 text-muted small">Photos (after)</p>
        <div class="d-flex flex-wrap gap-2">
          <?php if (!$photos['checkout']): ?><span class="text-muted">No photos uploaded</span><?php endif; ?>
          <?php foreach ($photos['checkout'] as $p): ?>
            <img src="<?= BASE_URL ?>/<?= e($p['photo_path']) ?>" class="photo-thumb">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
