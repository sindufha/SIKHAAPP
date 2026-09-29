<?php
declare(strict_types=1);

$url = getenv('RAILWAY_IMPORT_URL');
$perClass = max(1, min(100, (int)(getenv('STUDENTS_PER_CLASS') ?: 7)));
if ($url === false || $url === '') {
    fwrite(STDERR, "RAILWAY_IMPORT_URL wajib diisi.\n");
    exit(1);
}

$parts = parse_url($url);
if (!is_array($parts) || !isset($parts['host'], $parts['user'], $parts['path'])) {
    fwrite(STDERR, "Format URL database tidak valid.\n");
    exit(1);
}
$credentials = explode(':', $parts['user'] . ':' . ($parts['pass'] ?? ''), 2);
$pdo = new PDO(
    'mysql:host=' . $parts['host'] . ';port=' . (int)($parts['port'] ?? 3306)
        . ';dbname=' . rawurldecode(ltrim($parts['path'], '/')) . ';charset=utf8mb4',
    rawurldecode($credentials[0]),
    rawurldecode($credentials[1]),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$classes = $pdo->query("SELECT id, nama FROM kelas WHERE nama REGEXP '^Kelas [1-6][AB]$' ORDER BY nama")->fetchAll();
if (count($classes) !== 12) {
    throw new RuntimeException('Dibutuhkan tepat 12 kelas SD (Kelas 1A sampai Kelas 6B).');
}

$insert = $pdo->prepare(
    'INSERT IGNORE INTO siswa
        (id, nis, nama, kelas_id, qr_code, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat)
     VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)'
);
$pdo->beginTransaction();
$inserted = 0;
try {
    foreach ($classes as $class) {
        preg_match('/^Kelas ([1-6])([AB])$/', $class['nama'], $matches);
        $grade = (int)$matches[1];
        $section = $matches[2];
        for ($number = 1; $number <= $perClass; $number++) {
            $nis = sprintf('PH-%d%s-%03d', $grade, $section, $number);
            $gender = $number % 2 === 0 ? 'PEREMPUAN' : 'LAKI_LAKI';
            $birthYear = 2026 - (7 + $grade);
            $birthDate = sprintf('%04d-%02d-%02d', $birthYear, (($number - 1) % 12) + 1, (($number - 1) % 25) + 1);
            $insert->execute([
                $nis,
                sprintf('Siswa Placeholder %d%s %02d', $grade, $section, $number),
                $class['id'],
                sha1('sikha-placeholder:' . $nis),
                $gender,
                'Sukorejo',
                $birthDate,
                sprintf('Alamat placeholder %s nomor %d', $class['nama'], $number),
            ]);
            $inserted += $insert->rowCount();
        }
    }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}

$summary = $pdo->query(
    "SELECT COUNT(*) AS total, COUNT(DISTINCT kelas_id) AS kelas_terisi
     FROM siswa WHERE nis LIKE 'PH-%' AND is_active = 1"
)->fetch();
echo json_encode([
    'perClass' => $perClass,
    'inserted' => $inserted,
    'placeholderStudents' => (int)$summary['total'],
    'classesWithStudents' => (int)$summary['kelas_terisi'],
], JSON_THROW_ON_ERROR), PHP_EOL;
