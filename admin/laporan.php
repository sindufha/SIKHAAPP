<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

$kelas_id = getString('kelas_id', 36);
[$bulan, $tahun, $startDate, $endDate] = reportPeriod(getString('bulan', 2), getString('tahun', 4));
$status = getString('status', 20);
$export = getString('export', 20);
if (!in_array($status, ['', 'HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALFA'], true)) $status = '';

$where = ["p.tanggal BETWEEN ? AND ?"];
$params = [$startDate, $endDate];
if ($kelas_id) { $where[] = "s.kelas_id = ?"; $params[] = $kelas_id; }
if ($status) { $where[] = "p.status = ?"; $params[] = $status; }
$whereClause = implode(" AND ", $where);

if ($export === 'excel') {
    function excelCell(mixed $value): string {
        $value = (string)$value;
        if (preg_match('/^[=+\-@]/', $value)) $value = "'" . $value;
        return escape($value);
    }
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan_presensi_' . $bulan . '_' . $tahun . '.xls"');
    echo "\xEF\xBB\xBF<meta charset='UTF-8'><table border='1'>
        <tr><th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Jam Datang</th><th>Jam Pulang</th><th>Keterangan</th></tr>";
    $stmt = $pdo->prepare("SELECT p.*, s.nama, s.nis, k.nama as nama_kelas FROM presensi p JOIN siswa s ON p.siswa_id = s.id JOIN kelas k ON s.kelas_id = k.id WHERE $whereClause ORDER BY p.tanggal DESC, s.nama");
    $stmt->execute($params);
    while ($r = $stmt->fetch()) {
        echo '<tr><td>' . excelCell($r['tanggal']) . '</td><td>' . excelCell($r['nis']) . '</td><td>' . excelCell($r['nama'])
            . '</td><td>' . excelCell($r['nama_kelas']) . '</td><td>' . excelCell($r['status']) . '</td><td>'
            . excelCell($r['jam_datang'] ? date('H:i', strtotime($r['jam_datang'])) : '') . '</td><td>'
            . excelCell($r['jam_pulang'] ? date('H:i', strtotime($r['jam_pulang'])) : '') . '</td><td>'
            . excelCell($r['keterangan'] ?? '') . '</td></tr>';
    }
    echo "</table>";
    exit;
}

$kelasList = $pdo->query("SELECT id, nama as nama_kelas FROM kelas ORDER BY nama")->fetchAll();
$stmt = $pdo->prepare("SELECT p.*, s.nama, s.nis, k.nama as nama_kelas FROM presensi p JOIN siswa s ON p.siswa_id = s.id JOIN kelas k ON s.kelas_id = k.id WHERE $whereClause ORDER BY p.tanggal DESC, s.nama LIMIT 500");
$stmt->execute($params);
$presensiList = $stmt->fetchAll();
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Presensi</h1>
        <p class="page-desc"><?= count($presensiList) ?> data presensi | <?= monthNameIndonesian((int)$bulan) ?> <?= escape($tahun) ?></p>
    </div>
    <?php if (count($presensiList) > 0): ?>
    <a href="?export=excel&amp;bulan=<?= rawurlencode($bulan) ?>&amp;tahun=<?= rawurlencode($tahun) ?>&amp;kelas_id=<?= rawurlencode($kelas_id) ?>&amp;status=<?= rawurlencode($status) ?>" class="btn btn-success h-10 px-4 text-sm rounded-lg gap-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Export Excel
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card animate-in">
    <div class="card-body">
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="label">Kelas</label>
                <select name="kelas_id" class="input">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= escape($k['id']) ?>" <?= $kelas_id === $k['id'] ? 'selected' : '' ?>><?= escape($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Bulan</label>
                <select name="bulan" class="input">
                    <?php for ($i=1; $i<=12; $i++): $b = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $b ?>" <?= $bulan === $b ? 'selected' : '' ?>><?= monthNameIndonesian($i) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="label">Tahun</label>
                <input type="number" name="tahun" class="input" value="<?= escape($tahun) ?>" min="2020" max="2099">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">Semua</option>
                    <option value="HADIR" <?= $status === 'HADIR' ? 'selected' : '' ?>>Hadir</option>
                    <option value="TERLAMBAT" <?= $status === 'TERLAMBAT' ? 'selected' : '' ?>>Terlambat</option>
                    <option value="IZIN" <?= $status === 'IZIN' ? 'selected' : '' ?>>Izin</option>
                    <option value="SAKIT" <?= $status === 'SAKIT' ? 'selected' : '' ?>>Sakit</option>
                    <option value="ALFA" <?= $status === 'ALFA' ? 'selected' : '' ?>>Alfa</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary w-full h-10 px-4 text-sm rounded-lg">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
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
                        <td class="font-semibold"><?= escape($p['nama']) ?></td>
                        <td><?= escape($p['nama_kelas']) ?></td>
                        <td>
                            <?php $bc = match($p['status']) { 'HADIR' => 'badge-success', 'IZIN' => 'badge-info', 'SAKIT' => 'badge-warning', 'ALFA' => 'badge-error', 'TERLAMBAT' => 'badge-warning', default => 'badge-secondary' }; ?>
                            <span class="badge <?= $bc ?>"><?= escape($p['status']) ?></span>
                        </td>
                        <td><?= $p['jam_datang'] ? date('H:i', strtotime($p['jam_datang'])) : '-' ?></td>
                        <td><?= $p['jam_pulang'] ? date('H:i', strtotime($p['jam_pulang'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data presensi pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
