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

$check = $pdo->prepare("SELECT id FROM toilets WHERE id = ?");
$check->execute([$toiletId]);
if (!$check->fetch() || !user_is_assigned_to_toilet($pdo, $user['id'], $toiletId)) {
    flash_set('error', 'That toilet is not available or is not assigned to you.');
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}

// do_checkout() enforces the core rule: no checkout without an active check-in for this toilet.
$result = do_checkout($pdo, $user['id'], $toiletId, $comment);

if (!$result['ok']) {
    flash_set('error', $result['error']);
} else {
    $errors = save_session_photos($result['session_id'], 'checkout', $_FILES['photos'] ?? [], $pdo);
    if ($errors) {
        flash_set('error', 'Checked out, but some photos could not be saved: ' . implode(' ', $errors));
    } else {
        flash_set('success', 'Checked out successfully at ' . date('h:i A') . '. Thank you!');
    }
}

header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toiletId);
exit;
