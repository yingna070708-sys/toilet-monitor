<?php
require_once __DIR__ . '/../includes/functions.php';
require_student();

$user = current_user();
$toiletId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM toilets WHERE id = ? AND status = 'active'");
$stmt->execute([$toiletId]);
$toilet = $stmt->fetch();

if (!$toilet || !user_is_assigned_to_toilet($pdo, $user['id'], $toiletId)) {
    flash_set('error', 'That toilet is not available or is not assigned to you.');
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}

$activeSession = get_active_session($pdo, $user['id'], $toiletId);

// Shared history for this toilet — visible to any student assigned to it.
$historyStmt = $pdo->prepare(
    "SELECT ts.*, u.full_name AS user_name
     FROM toilet_sessions ts
     JOIN users u ON u.id = ts.user_id
     WHERE ts.toilet_id = ?
     ORDER BY ts.checkin_time DESC
     LIMIT 50"
);
$historyStmt->execute([$toiletId]);
$history = $historyStmt->fetchAll();

// Preload photos for the visible history in one go.
$historyPhotos = [];
if ($history) {
    $ids = array_column($history, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $ph = $pdo->prepare("SELECT * FROM session_photos WHERE session_id IN ($in) ORDER BY id");
    $ph->execute($ids);
    foreach ($ph->fetchAll() as $p) {
        $historyPhotos[$p['session_id']][$p['type']][] = $p;
    }
}

$pageTitle = $toilet['code'] . ' · ' . $toilet['name'];
include __DIR__ . '/../includes/header.php';
?>
<a href="index.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> My Toilets</a>

<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><span class="badge bg-info text-dark"><?= e($toilet['code']) ?></span> <?= e($toilet['name']) ?></h4>
      <div class="text-muted"><?= e($toilet['location']) ?></div>
    </div>
    <?php if ($activeSession): ?>
      <span class="badge status-badge-active fs-6">You are checked in since <?= format_dt($activeSession['checkin_time'], 'h:i A') ?></span>
    <?php else: ?>
      <span class="badge bg-light text-dark fs-6 border">Not checked in</span>
    <?php endif; ?>
  </div>
</div>

<?php if (!$activeSession): ?>
  <!-- CHECK-IN FORM -->
  <div class="card p-4 mb-4">
    <h5 class="mb-3 text-primary"><i class="bi bi-box-arrow-in-right"></i> Check In</h5>
    <p class="text-muted small">Take photos of the toilet condition now, and add a comment if needed.</p>
    <form method="post" action="checkin.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="toilet_id" value="<?= (int)$toilet['id'] ?>">
      <div class="mb-3">
        <label class="form-label">Photos (before)</label>
        <input type="file" name="photos[]" class="form-control" accept="image/*" capture="environment" multiple>
      </div>
      <div class="mb-3">
        <label class="form-label">Comment</label>
        <textarea name="comment" class="form-control" rows="3" placeholder="e.g. Floor wet and rubbish bin full."></textarea>
      </div>
      <button class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Submit Check-In</button>
    </form>
  </div>
<?php else: ?>
  <!-- CHECK-OUT FORM -->
  <div class="card p-4 mb-4">
    <h5 class="mb-3 text-success"><i class="bi bi-box-arrow-left"></i> Check Out</h5>
    <p class="text-muted small">
      Checked in at <?= format_dt($activeSession['checkin_time']) ?><?= $activeSession['checkin_comment'] ? ' — "' . e($activeSession['checkin_comment']) . '"' : '' ?>.
      Take photos of the toilet condition now before you leave.
    </p>
    <form method="post" action="checkout.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="toilet_id" value="<?= (int)$toilet['id'] ?>">
      <div class="mb-3">
        <label class="form-label">Photos (after)</label>
        <input type="file" name="photos[]" class="form-control" accept="image/*" capture="environment" multiple>
      </div>
      <div class="mb-3">
        <label class="form-label">Comment</label>
        <textarea name="comment" class="form-control" rows="3" placeholder="e.g. Floor cleaned and rubbish removed."></textarea>
      </div>
      <button class="btn btn-success"><i class="bi bi-box-arrow-left"></i> Submit Check-Out</button>
    </form>
  </div>
<?php endif; ?>

<div class="card p-4">
  <h5 class="mb-3"><i class="bi bi-clock-history"></i> Shared History for <?= e($toilet['code']) ?></h5>
  <p class="text-muted small">Visible to everyone assigned to this toilet.</p>

  <?php if (!$history): ?>
    <p class="text-muted">No check-in/check-out records yet for this toilet.</p>
  <?php endif; ?>

  <?php foreach ($history as $h): ?>
    <div class="timeline-item">
      <div class="d-flex justify-content-between flex-wrap">
        <div class="fw-semibold"><?= format_dt($h['checkin_time'], 'd M Y') ?> — <?= e($toilet['code']) ?></div>
        <?= $h['status']==='active' ? '<span class="badge status-badge-active">In Progress</span>' : '<span class="badge status-badge-completed">Completed</span>' ?>
      </div>
      <div class="text-muted small mb-2">User: <?= e($h['user_name']) ?></div>

      <div class="row">
        <div class="col-md-6">
          <div class="small text-muted">Check In: <?= format_dt($h['checkin_time'], 'h:i A') ?></div>
          <?php if ($h['checkin_comment']): ?><div class="small">Comment: "<?= e($h['checkin_comment']) ?>"</div><?php endif; ?>
          <div class="d-flex flex-wrap gap-2 mt-1">
            <?php foreach (($historyPhotos[$h['id']]['checkin'] ?? []) as $p): ?>
              <img src="<?= BASE_URL ?>/<?= e($p['photo_path']) ?>" class="photo-thumb">
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-md-6">
          <?php if ($h['status'] === 'completed'): ?>
            <div class="small text-muted">Check Out: <?= format_dt($h['checkout_time'], 'h:i A') ?></div>
            <?php if ($h['checkout_comment']): ?><div class="small">Comment: "<?= e($h['checkout_comment']) ?>"</div><?php endif; ?>
            <div class="d-flex flex-wrap gap-2 mt-1">
              <?php foreach (($historyPhotos[$h['id']]['checkout'] ?? []) as $p): ?>
                <img src="<?= BASE_URL ?>/<?= e($p['photo_path']) ?>" class="photo-thumb">
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="small text-muted fst-italic">Not checked out yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
