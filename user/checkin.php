<?php
require_once __DIR__ . '/../includes/functions.php';
require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
verify_csrf();

$user = current_user();
$toiletId = (int)($_POST['toilet_id'] ?? 0);
$comment  = trim($_POST['comment'] ?? '');

// Defence in depth: re-confirm the toilet is active and assigned to this user.
$check = $pdo->prepare("SELECT id FROM toilets WHERE id = ? AND status = 'active'");
$check->execute([$toiletId]);
if (!$check->fetch() || !user_is_assigned_to_toilet($pdo, $user['id'], $toiletId)) {
    flash_set('error', 'That toilet is not available or is not assigned to you.');
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}

$result = do_checkin($pdo, $user['id'], $toiletId, $comment);

if (!$result['ok']) {
    flash_set('error', $result['error']);
} else {
    $errors = save_session_photos($result['session_id'], 'checkin', $_FILES['photos'] ?? [], $pdo);
    if ($errors) {
        flash_set('error', 'Checked in, but some photos could not be saved: ' . implode(' ', $errors));
    } else {
        flash_set('success', 'Checked in successfully at ' . date('h:i A') . '.');
    }
}

header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toiletId);
exit;
