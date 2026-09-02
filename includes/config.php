<?php
/**
 * School ID Registration, Printing & Gate Monitoring System
 * Configuration
 */

// --- Display errors in development ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- .env loader (mirrors the PPT / Laravel approach) ---
// Reads a key=value .env file so DB settings (and app settings) come
// from one central place. Real env vars always win over .env values.
$__env = [];
$__envFile = __DIR__ . '/../.env';
if (is_file($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#' || strpos($__line, '=') === false) {
            continue;
        }
        [$__k, $__v] = array_map('trim', explode('=', $__line, 2));
        $__env[$__k] = trim($__v, '"\'');
    }
}
$__env = function (string $key, $default = null) use ($__env) {
    $real = getenv($key);
    return ($real !== false && $real !== '') ? $real : ($__env[$key] ?? $default);
};

// --- App constants ---
defined('APP_NAME') || define('APP_NAME', $__env('APP_NAME', 'School ID System'));
defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));
defined('APP_ENV')  || define('APP_ENV', $__env('APP_ENV', 'local'));
defined('APP_DEBUG')|| define('APP_DEBUG', filter_var($__env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN));
defined('BASE_URL') || define('BASE_URL', $__env('APP_BASE_URL', '/'));

// --- Database settings (from .env, overridable by env vars) ---
define('DB_CONNECTION', $__env('DB_CONNECTION', 'mysql'));
define('DB_HOST', $__env('DB_HOST', '127.0.0.1'));
define('DB_PORT', $__env('DB_PORT', '3307'));
define('DB_NAME', $__env('DB_DATABASE', 'school_id_system'));
define('DB_USER', $__env('DB_USERNAME', 'school'));
define('DB_PASS', $__env('DB_PASSWORD', 'school123'));

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
