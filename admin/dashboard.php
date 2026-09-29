<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

// Statistik
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM siswa WHERE is_active = 1")->fetchColumn();
$totalKelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$totalUser = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE tanggal = ?");
$stmt->execute([$today]);
$totalPresensiHariIni = $stmt->fetchColumn();

// Hitung gender distribution
$stmt = $pdo->query("SELECT jenis_kelamin, COUNT(*) as count FROM siswa WHERE is_active = 1 GROUP BY jenis_kelamin");
$genderData = ['LAKI_LAKI' => 0, 'PEREMPUAN' => 0];
while ($row = $stmt->fetch()) {
    $genderData[$row['jenis_kelamin']] = (int)$row['count'];
}
$totalGender = $genderData['LAKI_LAKI'] + $genderData['PEREMPUAN'];
$pctLaki = $totalGender > 0 ? round($genderData['LAKI_LAKI'] / $totalGender * 100) : 0;
$pctPerempuan = $totalGender > 0 ? round($genderData['PEREMPUAN'] / $totalGender * 100) : 0;

// Hitung metode presensi (scan QR vs manual)
$stmt = $pdo->query("SELECT metode, COUNT(*) as count FROM presensi WHERE metode IS NOT NULL GROUP BY metode");
$metodeData = ['scan' => 0, 'manual' => 0];
while ($row = $stmt->fetch()) {
    $metode = strtolower($row['metode']);
    if (strpos($metode, 'scan') !== false || strpos($metode, 'qr') !== false) {
        $metodeData['scan'] += (int)$row['count'];
    } else {
        $metodeData['manual'] += (int)$row['count'];
    }
}
$totalMetode = $metodeData['scan'] + $metodeData['manual'];
$pctScan = $totalMetode > 0 ? round($metodeData['scan'] / $totalMetode * 100) : 0;
$pctManual = $totalMetode > 0 ? round($metodeData['manual'] / $totalMetode * 100) : 0;

// Tren kehadiran 6 pekan terakhir
$trenData = [];
for ($i = 5; $i >= 0; $i--) {
    $weekStartDate = (new DateTimeImmutable('monday this week'))->modify("-$i weeks");
    $weekStart = $weekStartDate->format('Y-m-d');
    $weekEnd = $weekStartDate->modify('+6 days')->format('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status IN ('HADIR', 'TERLAMBAT')) AS hadir FROM presensi WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$weekStart, $weekEnd]);
    $weekStats = $stmt->fetch();
    $pctHadir = (int)$weekStats['total'] > 0 ? round((int)$weekStats['hadir'] / (int)$weekStats['total'] * 100) : 0;
    $trenData[] = [
        'week' => $weekStartDate->format('d/m'),
        'value' => min(100, $pctHadir)
    ];
}

include '../includes/header.php';
?>

<div class="space-y-3 p-1 sm:p-2 max-w-5xl mx-auto w-full h-full overflow-hidden">
    <div class="flex justify-between items-center shrink-0 animate-in">
        <div>
            <h2 class="text-base font-bold">Dashboard</h2>
            <p class="text-xs text-muted"><?= escape(dateIndonesian()) ?></p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 shrink-0 animate-in animate-in-delay-1">
        <div class="card stat-card p-2.5 shadow-none" style="border-color:var(--color-border);">
            <div class="stat-icon bg-primary-50 text-primary-dark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="min-w-0">
                <div class="stat-label">Total Siswa</div>
                <div class="stat-value"><?= number_format($totalSiswa) ?></div>
            </div>
        </div>
        <div class="card stat-card p-2.5 shadow-none" style="border-color:var(--color-border);">
            <div class="stat-icon bg-success-bg text-success-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M18 4.933V21"/><path d="m4 6 7.106-3.79a2 2 0 0 1 1.788 0L20 6"/><path d="m6 11-3.52 2.147a1 1 0 0 0-.48.854V19a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a1 1 0 0 0-.48-.853L18 11"/><path d="M6 4.933V21"/><circle cx="12" cy="9" r="2"/></svg>
            </div>
            <div class="min-w-0">
                <div class="stat-label">Total Kelas</div>
                <div class="stat-value"><?= number_format($totalKelas) ?></div>
            </div>
        </div>
        <div class="card stat-card p-2.5 shadow-none" style="border-color:var(--color-border);">
            <div class="stat-icon bg-success-bg text-success-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div class="min-w-0">
                <div class="stat-label">Hadir Hari Ini</div>
                <div class="stat-value"><?= number_format($totalPresensiHariIni) ?></div>
            </div>
        </div>
        <div class="card stat-card p-2.5 shadow-none" style="border-color:var(--color-border);">
            <div class="stat-icon bg-info-bg text-info-text">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            </div>
            <div class="min-w-0">
                <div class="stat-label">Total Guru</div>
                <div class="stat-value"><?= number_format($totalUser) ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 shrink-0 animate-in animate-in-delay-2">
        <!-- Line Chart: Tren Kehadiran Mingguan -->
        <div class="card p-4 flex flex-col justify-between" style="height:260px;">
            <div class="flex items-center justify-between shrink-0 mb-2">
                <div>
                    <h3 class="text-xs font-bold">Tren Kehadiran Mingguan</h3>
                    <p class="text-xs text-muted">Persentase kehadiran 6 pekan terakhir</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <span>Pekan Ini</span>
                    </div>
                </div>
            </div>
            <?php
            // Generate SVG line chart points
            $points = [];
            $labels = [];
            $maxVal = 100;
            $chartW = 500; $chartH = 150;
            $paddingTop = 15; $paddingBottom = 25; $paddingLeft = 30; $paddingRight = 10;
            $plotW = $chartW - $paddingLeft - $paddingRight;
            $plotH = $chartH - $paddingTop - $paddingBottom;
            $count = count($trenData);
            foreach ($trenData as $i => $d) {
                $x = $paddingLeft + ($i / max($count - 1, 1)) * $plotW;
                $y = $paddingTop + ((100 - $d['value']) / 100) * $plotH;
                $points[] = "$x,$y";
                $labels[] = ['x' => $x, 'label' => $d['week']];
            }
            $pathD = 'M ' . implode(' L ', $points);
            ?>
            <div class="relative w-full flex-1">
                <svg viewBox="0 0 <?= $chartW ?> <?= $chartH ?>" class="w-full h-full">
                    <!-- Grid lines -->
                    <?php foreach ([20, 50, 80] as $pct): $gy = $paddingTop + ((100 - $pct) / 100) * $plotH; ?>
                    <line x1="<?= $paddingLeft ?>" y1="<?= $gy ?>" x2="<?= $chartW - $paddingRight ?>" y2="<?= $gy ?>" stroke="#E7E7EE" stroke-width="1" stroke-dasharray="3 3"/>
                    <text x="<?= $paddingLeft - 5 ?>" y="<?= $gy + 3 ?>" text-anchor="end" class="text-xs fill-text-muted font-medium"><?= $pct ?>%</text>
                    <?php endforeach; ?>
                    <!-- Line -->
                    <path d="<?= $pathD ?>" fill="none" stroke="#007AFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <!-- Dots -->
                    <?php foreach ($points as $i => $pt): list($x, $y) = explode(',', $pt); ?>
                    <g>
                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="#007AFF" stroke="#fff" stroke-width="1.5"/>
                    </g>
                    <?php endforeach; ?>
                    <!-- Labels -->
                    <?php foreach ($labels as $lb): ?>
                    <text x="<?= $lb['x'] ?>" y="<?= $chartH - 5 ?>" text-anchor="middle" class="text-xs fill-text-muted font-medium"><?= $lb['label'] ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>

        <!-- Metode & Distribusi -->
        <div class="card p-4 flex flex-col justify-between" style="height:260px;">
            <div class="flex-1 flex flex-col justify-center">
                <h3 class="text-xs font-bold mb-3">Metode &amp; Distribusi</h3>
                <div class="space-y-3">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-muted">Metode: Scan Kartu QR</span>
                            <span class="font-bold tabular-nums"><?= $pctScan ?>%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-primary" style="width:<?= $pctScan ?>%;"></div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-muted">Metode: Presensi Manual</span>
                            <span class="font-bold tabular-nums"><?= $pctManual ?>%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-secondary" style="width:<?= $pctManual ?>%;"></div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-muted">Laki-laki</span>
                            <span class="font-bold tabular-nums"><?= $pctLaki ?>%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-success" style="width:<?= $pctLaki ?>%;"></div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-muted">Perempuan</span>
                            <span class="font-bold tabular-nums"><?= $pctPerempuan ?>%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-primary-light" style="width:<?= $pctPerempuan ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
