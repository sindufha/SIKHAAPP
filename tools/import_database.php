<?php
declare(strict_types=1);

$url = getenv('RAILWAY_IMPORT_URL');
if ($url === false || $url === '') {
    fwrite(STDERR, "RAILWAY_IMPORT_URL tidak tersedia.\n");
    exit(1);
}

$parts = parse_url($url);
if (!is_array($parts) || !isset($parts['host'], $parts['user'], $parts['path'])) {
    fwrite(STDERR, "Format URL database tidak valid.\n");
    exit(1);
}

$host = $parts['host'];
$port = (int)($parts['port'] ?? 3306);
$user = rawurldecode($parts['user']);
$password = rawurldecode((string)($parts['pass'] ?? ''));
$database = rawurldecode(ltrim($parts['path'], '/'));
$schema = file_get_contents(dirname(__DIR__) . '/database.sql');

if ($schema === false) {
    fwrite(STDERR, "database.sql tidak dapat dibaca.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    );
    $pdo->exec($schema);
    $adminPassword = getenv('ADMIN_BOOTSTRAP_PASSWORD');
    $passwordRotated = false;
    if ($adminPassword !== false && $adminPassword !== '') {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([password_hash($adminPassword, PASSWORD_DEFAULT)]);
        $pdo->exec('DELETE FROM sessions');
        $passwordRotated = $stmt->rowCount() === 1;
    }
    $tableCount = (int)$pdo->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchColumn();
    $adminCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE username = 'admin'"
    )->fetchColumn();
    $sessionCount = (int)$pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();
    echo json_encode([
        'import' => 'success',
        'tables' => $tableCount,
        'adminAccounts' => $adminCount,
        'activeSessions' => $sessionCount,
        'adminPasswordRotated' => $passwordRotated,
    ], JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, "Impor database gagal: {$error->getMessage()}\n");
    exit(1);
}
