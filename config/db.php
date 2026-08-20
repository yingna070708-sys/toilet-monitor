<?php
/**
 * Database connection (PDO / MySQL).
 * Update the credentials below to match your MySQL server.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'synergy1_khoryingna_toliet_new';
$DB_USER = 'synergy1_yenping';
$DB_PASS = 'R.zb0ZwEuGZ}*fW2';
$DB_PORT = 3306;

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Please check config/db.php. (' . $e->getMessage() . ')');
}
