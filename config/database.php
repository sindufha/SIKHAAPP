<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(self)');
    header('Cache-Control: no-store, max-age=0');
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'sikha_db';
$user = getenv('DB_USER') ?: 'root';
$passwordEnv = getenv('DB_PASSWORD');
$pass = $passwordEnv === false ? '' : $passwordEnv;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('Koneksi database SIKHA gagal: ' . $e->getMessage());
    throw new \RuntimeException('Koneksi database gagal. Periksa konfigurasi database.');
}
?>
