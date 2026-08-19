<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

/** Returns the logged-in user's session array, or null. */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/** Redirects to login if not authenticated. */
function require_login(): void {
    if (!current_user()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Redirects to login / blocks access unless the user is an admin. */
function require_admin(): void {
    require_login();
    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        die('Access denied: administrator privileges are required.');
    }
}

/** Redirects to login / blocks access unless the user is a student. */
function require_student(): void {
    require_login();
    if (current_user()['role'] !== 'student') {
        http_response_code(403);
        die('Access denied: this area is for student/user accounts only.');
    }
}

/** Logs a user in by their DB row (after password verification). */
function log_in_user(array $userRow): void {
    $_SESSION['user'] = [
        'id'                   => (int)$userRow['id'],
        'username'             => $userRow['username'],
        'full_name'            => $userRow['full_name'],
        'role'                 => $userRow['role'],
        'must_change_password' => (bool)$userRow['must_change_password'],
    ];
}

function log_out_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
