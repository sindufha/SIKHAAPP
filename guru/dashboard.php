<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('GURU');

$stmt = $pdo->prepare("SELECT id FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$kelas = $stmt->fetch();
$kelasId = $kelas ? $kelas['id'] : null;

$totalSiswaWali = 0;
$totalPresensiHariIni = 0;
$kelasNama = '-';

if ($kelasId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ? AND is_active = 1");
    $stmt->execute([$kelasId]);
    $totalSiswaWali = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM presensi p JOIN siswa s ON p.siswa_id = s.id WHERE s.kelas_id = ? AND p.tanggal = ?");
    $stmt->execute([$kelasId, date('Y-m-d')]);
    $totalPresensiHariIni = $stmt->fetchColumn();

    $st = $pdo->prepare("SELECT nama FROM kelas WHERE id = ?");
    $st->execute([$kelasId]);
    $kelasNama = $st->fetchColumn() ?: '-';
}

include '../includes/header.php';
?>

<div class="flex justify-between items-center shrink-0 animate-in">
    <div>
        <h2 class="text-base font-bold">Dashboard Guru</h2>
        <p class="text-xs text-muted"><?= escape(dateIndonesian()) ?> — Wali Kelas: <strong><?= escape($kelasNama) ?></strong></p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-4 animate-in animate-in-delay-1">
    <div class="card stat-card p-2.5 shadow-none">
        <div class="stat-icon bg-primary-50 text-primary-dark">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="min-w-0">
            <div class="stat-label">Siswa Wali Kelas</div>
            <div class="stat-value"><?= number_format($totalSiswaWali) ?></div>
        </div>
    </div>
    <div class="card stat-card p-2.5 shadow-none">
        <div class="stat-icon bg-success-bg text-success-text">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="min-w-0">
            <div class="stat-label">Presensi Hari Ini</div>
            <div class="stat-value"><?= number_format($totalPresensiHariIni) ?></div>
        </div>
    </div>
</div>

<div class="card animate-in animate-in-delay-2">
    <div class="card-header">
        <h5 class="card-title">Akses Cepat</h5>
    </div>
    <div class="card-body" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a href="<?= appUrl('guru/presensi_manual.php') ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Presensi Manual
        </a>
        <a href="<?= appUrl('guru/presensi_qr.php') ?>" class="btn btn-ghost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="12" r="1"/></svg>
            Scan QR
        </a>
    </div>
</div>

<!-- Recent presences -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 animate-in animate-in-delay-3">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Presensi Hari Ini</h5>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Status</th>
                        <th>Jam</th>
                    </tr>
                </thead>
                <tbody>
                        <?php if ($kelasId): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT p.*, s.nama FROM presensi p JOIN siswa s ON p.siswa_id = s.id WHERE s.kelas_id = ? AND p.tanggal = ? ORDER BY s.nama LIMIT 10");
                        $stmt->execute([$kelasId, date('Y-m-d')]);
                        $hariIni = $stmt->fetchAll();
                        ?>
                        <?php foreach ($hariIni as $h): ?>
                        <tr>
                            <td><?= escape($h['nama']) ?></td>
                            <td><span class="badge badge-success"><?= escape($h['status']) ?></span></td>
                            <td><?= $h['jam_datang'] ? date('H:i', strtotime($h['jam_datang'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($hariIni)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Belum ada presensi hari ini</td></tr>
                        <?php endif; ?>
                        <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Anda belum menjadi wali kelas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
