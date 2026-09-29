<?php
declare(strict_types=1);

$url = getenv('RAILWAY_IMPORT_URL');
$guru1Password = getenv('GURU1_PASSWORD');
$guru2Password = getenv('GURU2_PASSWORD');
if ($url === false || $guru1Password === false || $guru2Password === false || $guru1Password === '' || $guru2Password === '') {
    fwrite(STDERR, "RAILWAY_IMPORT_URL, GURU1_PASSWORD, dan GURU2_PASSWORD wajib diisi.\n");
    exit(1);
}

$parts = parse_url($url);
if (!is_array($parts) || !isset($parts['host'], $parts['user'], $parts['path'])) {
    fwrite(STDERR, "Format URL database tidak valid.\n");
    exit(1);
}

$userInfo = explode(':', $parts['user'] . ':' . ($parts['pass'] ?? ''), 2);
$pdo = new PDO(
    'mysql:host=' . $parts['host'] . ';port=' . (int)($parts['port'] ?? 3306)
        . ';dbname=' . rawurldecode(ltrim($parts['path'], '/')) . ';charset=utf8mb4',
    rawurldecode($userInfo[0]),
    rawurldecode($userInfo[1]),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$pdo->beginTransaction();
try {
    $userStmt = $pdo->prepare(
        "INSERT INTO users (id, username, password, nama, role, is_active)
         VALUES (UUID(), ?, ?, ?, 'GURU', 1)
         ON DUPLICATE KEY UPDATE nama = VALUES(nama), role = 'GURU', is_active = 1"
    );
    $userStmt->execute(['guru1', password_hash($guru1Password, PASSWORD_DEFAULT), 'Guru Kelas 1']);
    $userStmt->execute(['guru2', password_hash($guru2Password, PASSWORD_DEFAULT), 'Guru Kelas 2']);

    $ids = $pdo->query("SELECT username, id FROM users WHERE username IN ('guru1', 'guru2')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $classStmt = $pdo->prepare(
        'INSERT INTO kelas (id, nama, wali_kelas_id) VALUES (UUID(), ?, ?)
         ON DUPLICATE KEY UPDATE wali_kelas_id = COALESCE(kelas.wali_kelas_id, VALUES(wali_kelas_id))'
    );
    $created = 0;
    foreach (range(1, 6) as $grade) {
        foreach (['A', 'B'] as $section) {
            $name = "Kelas {$grade}{$section}";
            $waliId = $name === 'Kelas 1A' ? ($ids['guru1'] ?? null) : ($name === 'Kelas 1B' ? ($ids['guru2'] ?? null) : null);
            $classStmt->execute([$name, $waliId]);
            $created += $classStmt->rowCount() > 0 ? 1 : 0;
        }
    }
    $pdo->commit();

    $summary = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM kelas WHERE nama REGEXP '^Kelas [1-6][AB]$') AS kelas_sd,
            (SELECT COUNT(*) FROM users WHERE username IN ('guru1', 'guru2') AND role = 'GURU' AND is_active = 1) AS akun_guru,
            (SELECT COUNT(*) FROM kelas WHERE wali_kelas_id IS NOT NULL AND nama REGEXP '^Kelas [1-6][AB]$') AS kelas_berwali"
    )->fetch();
    echo json_encode([
        'kelasInsertedOrExisting' => $created,
        'kelasSD' => (int)$summary['kelas_sd'],
        'akunGuru' => (int)$summary['akun_guru'],
        'kelasDenganWali' => (int)$summary['kelas_berwali'],
    ], JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Seed data gagal: {$error->getMessage()}\n");
    exit(1);
}
