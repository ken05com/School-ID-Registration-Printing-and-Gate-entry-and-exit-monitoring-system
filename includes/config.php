<?php
/**
 * School ID Registration, Printing & Gate Monitoring System
 * Configuration
 */

// --- Display errors in development ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- App constants ---
define('APP_NAME', 'School ID System');
define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', '/');

// --- Database settings ---
define('DB_HOST', getenv('SCHOOL_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SCHOOL_DB_PORT') ?: '3307');
define('DB_NAME', getenv('SCHOOL_DB_NAME') ?: 'school_id_system');
define('DB_USER', getenv('SCHOOL_DB_USER') ?: 'school');
define('DB_PASS', getenv('SCHOOL_DB_PASS') ?: 'school123');

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Establish a MySQL database connection.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
