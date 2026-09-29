<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('GURU');

$stmt = $pdo->prepare("SELECT id, nama FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$kelas = $stmt->fetch();

if (!$kelas) {
    die("<div style='padding:2rem;text-align:center;color:var(--color-error);font-weight:500;'>Anda belum terdaftar sebagai wali kelas.</div>");
}

[$bulan, $tahun, $startDate, $endDate] = reportPeriod(getString('bulan', 2), getString('tahun', 4));

$presensiList = $pdo->prepare("
    SELECT p.*, s.nama as nama_siswa, s.nis
    FROM presensi p
    JOIN siswa s ON p.siswa_id = s.id
    WHERE s.kelas_id = ? AND p.tanggal BETWEEN ? AND ?
    ORDER BY p.tanggal DESC, s.nama ASC
");
$presensiList->execute([$kelas['id'], $startDate, $endDate]);
$presensiList = $presensiList->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Presensi</h1>
        <p class="page-desc">Kelas <?= escape($kelas['nama']) ?> — <?= count($presensiList) ?> data</p>
    </div>
</div>

<div class="card animate-in mb-4">
    <div class="card-body">
        <form method="GET" action="" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="label">Bulan</label>
                <select name="bulan" class="input">
                    <?php for($i=1; $i<=12; $i++): $b = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $b ?>" <?= $bulan === $b ? 'selected' : '' ?>><?= monthNameIndonesian($i) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="label">Tahun</label>
                <input type="number" name="tahun" class="input" value="<?= escape($tahun) ?>" min="2020" max="2099">
            </div>
            <div>
                <button type="submit" class="btn btn-primary h-10 px-4 text-sm rounded-lg">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Jam Datang</th>
                    <th>Jam Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($presensiList) > 0): ?>
                    <?php foreach ($presensiList as $p): ?>
                    <tr class="animate-in" style="animation-delay: 0ms;">
                        <td class="text-muted"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                        <td class="font-mono text-xs font-medium"><?= escape($p['nis']) ?></td>
                        <td class="font-semibold"><?= escape($p['nama_siswa']) ?></td>
                        <td>
                            <span class="badge badge-<?= match($p['status']) { 'HADIR' => 'success', 'IZIN' => 'info', 'SAKIT' => 'warning', 'ALFA' => 'error', 'TERLAMBAT' => 'warning', default => 'secondary' } ?>">
                                <?= escape($p['status']) ?>
                            </span>
                        </td>
                        <td><?= $p['jam_datang'] ? date('H:i', strtotime($p['jam_datang'])) : '-' ?></td>
                        <td><?= $p['jam_pulang'] ? date('H:i', strtotime($p['jam_pulang'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data presensi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
