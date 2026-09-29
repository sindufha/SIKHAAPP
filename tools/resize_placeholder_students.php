<?php
declare(strict_types=1);

$url = getenv('RAILWAY_IMPORT_URL');
$perClass = 7;
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

$firstNames = [
    'Aditya', 'Aisyah', 'Alif', 'Amira', 'Andika', 'Annisa', 'Arif', 'Aulia',
    'Bagas', 'Bima', 'Citra', 'Daffa', 'Dania', 'Dimas', 'Fahmi', 'Farah',
    'Fikri', 'Gita', 'Hana', 'Hendra', 'Intan', 'Iqbal', 'Jihan', 'Khalid',
    'Laras', 'Lutfi', 'Maya', 'Nabila', 'Naufal', 'Naya', 'Putra', 'Raka',
    'Rani', 'Rasya', 'Rizki', 'Salsa', 'Satria', 'Tiara', 'Vino', 'Zahra',
];
$lastNames = [
    'Anggara', 'Anwar', 'Bakri', 'Cahyono', 'Darmawan', 'Fauzan', 'Haryanto',
    'Irawan', 'Jatmiko', 'Kurniawan', 'Lesmana', 'Maulana', 'Nugraha', 'Pratama',
    'Ramadhan', 'Saputra', 'Setiawan', 'Siregar', 'Suhendra', 'Wijaya',
];

$classes = $pdo->query("SELECT id, nama FROM kelas WHERE nama REGEXP '^Kelas [1-6][AB]$' ORDER BY nama")->fetchAll();
if (count($classes) !== 12) {
    throw new RuntimeException('Dibutuhkan tepat 12 kelas SD (Kelas 1A sampai Kelas 6B).');
}

$pdo->beginTransaction();
$updated = 0;
$removed = 0;
$usedNames = [];
try {
    $find = $pdo->prepare(
        "SELECT id, jenis_kelamin FROM siswa
         WHERE kelas_id = ? AND alamat LIKE 'Alamat placeholder%'
         ORDER BY id"
    );
    $update = $pdo->prepare(
        'UPDATE siswa SET nis = ?, nama = ?, qr_code = ?, jenis_kelamin = ? WHERE id = ?'
    );
    $delete = $pdo->prepare('DELETE FROM siswa WHERE id = ?');

    foreach ($classes as $classIndex => $class) {
        $find->execute([$class['id']]);
        $students = $find->fetchAll();
        foreach (array_slice($students, $perClass) as $extra) {
            $delete->execute([$extra['id']]);
            $removed += $delete->rowCount();
        }
        foreach (array_slice($students, 0, $perClass) as $number => $student) {
            do {
                $name = $firstNames[random_int(0, count($firstNames) - 1)] . ' '
                    . $lastNames[random_int(0, count($lastNames) - 1)];
            } while (isset($usedNames[$name]));
            $usedNames[$name] = true;
            $nis = sprintf('%06d', 100000 + ($classIndex * 1000) + $number + 1);
            $update->execute([
                $nis,
                $name,
                sha1('sikha-placeholder:' . $nis),
                $student['jenis_kelamin'],
                $student['id'],
            ]);
            $updated += $update->rowCount();
        }
    }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}

$summary = $pdo->query(
    "SELECT COUNT(*) AS total, COUNT(DISTINCT kelas_id) AS kelas_terisi,
            SUM(nis REGEXP '^[0-9]{6}$') AS nis_enam_digit
     FROM siswa WHERE alamat LIKE 'Alamat placeholder%' AND is_active = 1"
)->fetch();
echo json_encode([
    'targetPerClass' => $perClass,
    'updated' => $updated,
    'removed' => $removed,
    'placeholderStudents' => (int)$summary['total'],
    'classesWithStudents' => (int)$summary['kelas_terisi'],
    'sixDigitNis' => (int)$summary['nis_enam_digit'],
], JSON_THROW_ON_ERROR), PHP_EOL;
