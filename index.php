<?php
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
if (!$user) {
    header('Location: ' . BASE_URL . '/login.php');
} elseif ($user['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/admin/index.php');
} else {
    header('Location: ' . BASE_URL . '/user/index.php');
}
exit;
