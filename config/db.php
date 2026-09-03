<?php
/**
 * Database Configuration
 * Update these values to match your XAMPP / cPanel MySQL credentials
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'synergy1_raymondtanzijian_it_ticketing_system');
define('DB_USER', 'synergy1_yenping');
define('DB_PASS', 'R.zb0ZwEuGZ}*fW2');
define('DB_PORT', '3306');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ":" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;color:#b02a37;">
        <h2>Database Connection Error</h2>
        <p>Please make sure MySQL is running and the database "it_ticketing_system" has been imported.</p>
        <p style="color:#666;font-size:13px;">' . htmlspecialchars($e->getMessage()) . '</p></div>');
}
