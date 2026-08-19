<?php
/**
 * Global application configuration.
 *
 * If you deploy this app inside a sub-folder of your web root
 * (e.g. http://yourcollege.edu/toilet-monitor/), set BASE_URL to
 * that sub-path, e.g. '/toilet-monitor'. If it lives at the web
 * root, leave it as an empty string.
 */
define('BASE_URL', 'https://khoryingna.kolejsynergy.com/l/toilet');

define('APP_NAME', 'Toilet Cleanliness Monitoring System');

// Absolute filesystem path to the uploads folder, and its public URL path.
define('UPLOAD_DIR', __DIR__ . '/../uploads/sessions/');
define('UPLOAD_URL', BASE_URL . '/uploads/sessions/');

// Upload constraints
define('MAX_PHOTO_SIZE', 8 * 1024 * 1024); // 8 MB per photo
define('MAX_PHOTOS_PER_SUBMIT', 10);
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
