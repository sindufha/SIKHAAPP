<?php
declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = $requestPath === '/' ? '/index.php' : $requestPath;
$relativePath = ltrim($requestPath, '/');

$allowed = preg_match(
    '#^(?:(?:index|login|logout|profil)\.php|(?:admin|guru|siswa)/[a-z0-9_]+\.php|api/presensi\.php)$#D',
    $relativePath
) === 1;

$projectRoot = realpath(dirname(__DIR__));
$target = $allowed && $projectRoot !== false
    ? realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath))
    : false;

if ($target === false || !is_file($target) || !str_starts_with($target, $projectRoot . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Halaman tidak ditemukan.';
    exit;
}

$_SERVER['SCRIPT_NAME'] = $requestPath;
$_SERVER['PHP_SELF'] = $requestPath;
chdir(dirname($target));
require $target;
