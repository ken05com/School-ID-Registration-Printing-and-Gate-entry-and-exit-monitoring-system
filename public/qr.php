<?php
/**
 * Renders a QR code image for a given value.
 * Usage: /qr.php?value=SID-2023-0001-A1B2C3
 */
require_once __DIR__ . '/../includes/auth.php';

// Suppress the old library's PHP 8.x deprecation notices so they don't
// corrupt the raw PNG output.
error_reporting(error_reporting() & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../qr-lib/phpqrcode.php';

require_login();

$value = $_GET['value'] ?? '';
if ($value === '') {
    http_response_code(400);
    exit('Missing value');
}
if (strlen($value) > 200) {
    $value = substr($value, 0, 200);
}

// Cache so repeated scans/renders are fast.
$cacheDir = sys_get_temp_dir() . '/school_qr_cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
$cacheFile = $cacheDir . '/' . md5($value) . '.png';

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

if (is_file($cacheFile)) {
    readfile($cacheFile);
    exit;
}

// Use the bundled phpqrcode library.
QRcode::png($value, $cacheFile, QR_ECLEVEL_M, 8, 2);
if (is_file($cacheFile)) {
    readfile($cacheFile);
}
