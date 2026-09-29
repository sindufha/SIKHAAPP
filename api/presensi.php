<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jsonResponse(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Sesi login telah berakhir.'], 401);
}

$userStmt = $pdo->prepare('SELECT role, is_active FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch();
if (!$currentUser || !(int)$currentUser['is_active']) {
    destroySession();
    jsonResponse(['success' => false, 'message' => 'Akun tidak aktif.'], 401);
}
if (!in_array($currentUser['role'], ['ADMIN', 'GURU'], true)) {
    jsonResponse(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
}

if (!isCsrfValid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Sesi telah kedaluwarsa. Muat ulang halaman.'], 419);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(['success' => false, 'message' => 'Data permintaan tidak valid.'], 400);
}

$qrCode = is_string($input['qr_code'] ?? null) ? trim($input['qr_code']) : '';
if ($qrCode === '' || !preg_match('/^[a-f0-9]{40}$/i', $qrCode)) {
    jsonResponse(['success' => false, 'message' => 'QR Code tidak valid.'], 400);
}

$stmt = $pdo->prepare(
    'SELECT s.id, s.nama, s.nis, s.jenis_kelamin, s.kelas_id, k.nama AS nama_kelas, k.wali_kelas_id
     FROM siswa s
     JOIN kelas k ON s.kelas_id = k.id
     WHERE s.qr_code = ? AND s.is_active = 1
     LIMIT 1'
);
$stmt->execute([$qrCode]);
$siswa = $stmt->fetch();

if (!$siswa) {
    jsonResponse(['success' => false, 'message' => 'Siswa tidak ditemukan atau tidak aktif.'], 404);
}
if ($currentUser['role'] === 'GURU' && $siswa['wali_kelas_id'] !== $_SESSION['user_id']) {
    jsonResponse(['success' => false, 'message' => 'QR ini bukan milik siswa kelas Anda.'], 403);
}

$tahunAjaran = $pdo->query('SELECT id FROM tahun_ajaran WHERE is_active = 1 LIMIT 1')->fetch();
if (!$tahunAjaran) {
    jsonResponse(['success' => false, 'message' => 'Tahun ajaran aktif belum diatur.'], 409);
}

$today = date('Y-m-d');
$nowTime = date('H:i:s');
$jamSetting = $pdo->query('SELECT jam_masuk, toleransi_menit FROM jam_presensi WHERE is_active = 1 LIMIT 1')->fetch();
$status = 'HADIR';
if ($jamSetting) {
    $deadline = strtotime($today . ' ' . $jamSetting['jam_masuk']) + ((int)$jamSetting['toleransi_menit'] * 60);
    if (time() > $deadline) $status = 'TERLAMBAT';
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "INSERT INTO presensi (id, siswa_id, kelas_id, tahun_ajaran_id, tanggal, status, metode, jam_datang)
         VALUES (UUID(), ?, ?, ?, ?, ?, 'QR', ?)"
    );
    $stmt->execute([$siswa['id'], $siswa['kelas_id'], $tahunAjaran['id'], $today, $status, $nowTime]);
    logAudit($pdo, 'PRESENSI_QR', 'Mencatat presensi QR', [
        'siswa_id' => $siswa['id'],
        'status' => $status,
    ]);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') {
        jsonResponse(['success' => false, 'message' => $siswa['nama'] . ' sudah melakukan presensi hari ini.'], 409);
    }
    error_log('Gagal menyimpan presensi QR: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Presensi gagal disimpan. Silakan coba lagi.'], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Presensi datang berhasil dicatat untuk ' . $siswa['nama'],
    'data' => [
        'nama' => $siswa['nama'],
        'nis' => $siswa['nis'],
        'nama_kelas' => $siswa['nama_kelas'],
        'jenis_kelamin' => $siswa['jenis_kelamin'],
        'type' => 'DATANG',
        'status' => $status,
    ],
]);
