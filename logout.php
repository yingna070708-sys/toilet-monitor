<?php
require_once __DIR__ . '/includes/functions.php';
log_out_user();
header('Location: ' . BASE_URL . '/login.php');
exit;
