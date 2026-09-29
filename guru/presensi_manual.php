<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('GURU');

// Guru hanya boleh mengelola kelas yang menjadi tanggung jawabnya.
$kelasStmt = $pdo->prepare("SELECT id, nama FROM kelas WHERE wali_kelas_id = ? ORDER BY nama");
$kelasStmt->execute([$_SESSION['user_id']]);
$allKelas = $kelasStmt->fetchAll();

// Cek apakah guru ini adalah wali kelas
$waliKelasStmt = $pdo->prepare("SELECT id, nama FROM kelas WHERE wali_kelas_id = ? LIMIT 1");
$waliKelasStmt->execute([$_SESSION['user_id']]);
$waliKelas = $waliKelasStmt->fetch();
$waliKelasId = $waliKelas ? $waliKelas['id'] : null;
$waliKelasNama = $waliKelas ? $waliKelas['nama'] : null;

// Ambil kelas yang dipilih (default: kelas wali jika ada)
$selectedKelasId = getString('kelas_id', 36) ?: ($waliKelasId ?? ($allKelas[0]['id'] ?? ''));

// Ambil data kelas yang dipilih
$kelas = null;
foreach ($allKelas as $k) {
    if ($k['id'] === $selectedKelasId) {
        $kelas = $k;
        break;
    }
}

if (!$kelas) {
    die("<div style='padding:2rem;text-align:center;color:var(--color-error);font-weight:500;'>Tidak ada kelas tersedia.</div>");
}

$today = date('Y-m-d');
$nowTime = date('H:i:s');

// Handle bulk save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['presensi_data'])) {
    verifyCsrf();
    $rawPresensi = is_string($_POST['presensi_data'] ?? null) ? $_POST['presensi_data'] : '';
    $presensiData = json_decode($rawPresensi, true);

    $stmt = $pdo->query("SELECT id FROM tahun_ajaran WHERE is_active = 1 LIMIT 1");
    $tahun_ajaran = $stmt->fetch();
    $tahun_ajaran_id = $tahun_ajaran ? $tahun_ajaran['id'] : null;
    if (!$tahun_ajaran_id) abortRequest('Tahun ajaran aktif belum diatur.');

    $allowedStatuses = ['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALFA'];
    $allowedStudents = $pdo->prepare('SELECT id FROM siswa WHERE id = ? AND kelas_id = ? AND is_active = 1');

    $pdo->beginTransaction();
    try {
        if (is_array($presensiData)) foreach ($presensiData as $item) {
            if (!is_array($item)) continue;
            $siswa_id = $item['siswa_id'] ?? null;
            $status = $item['status'] ?? 'HADIR';
            $rawKeterangan = $item['keterangan'] ?? '';
            $keterangan = is_string($rawKeterangan) ? trim($rawKeterangan) : '';
            $keterangan = function_exists('mb_substr') ? mb_substr($keterangan, 0, 500) : substr($keterangan, 0, 500);

            if (!is_string($siswa_id) || !in_array($status, $allowedStatuses, true)) continue;
            $allowedStudents->execute([$siswa_id, $kelas['id']]);
            if (!$allowedStudents->fetchColumn()) continue;

            $stmt = $pdo->prepare("SELECT id FROM presensi WHERE siswa_id = ? AND tanggal = ?");
            $stmt->execute([$siswa_id, $today]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE presensi SET status = ?, keterangan = ?, metode = 'manual' WHERE id = ?");
                $stmt->execute([$status, $keterangan ?: null, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO presensi (id, siswa_id, kelas_id, tahun_ajaran_id, tanggal, status, keterangan, jam_datang, metode) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, 'manual')");
                $stmt->execute([$siswa_id, $kelas['id'], $tahun_ajaran_id, $today, $status, $keterangan ?: null, $nowTime]);
            }
        }

    // Set semua siswa yang belum ada presensi ke HADIR
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO presensi (id, siswa_id, kelas_id, tahun_ajaran_id, tanggal, status, metode, jam_datang)
        SELECT UUID(), s.id, s.kelas_id, ?, ?, 'HADIR', 'manual', ?
        FROM siswa s
        LEFT JOIN presensi p ON s.id = p.siswa_id AND p.tanggal = ?
        WHERE s.kelas_id = ? AND s.is_active = 1 AND p.id IS NULL
    ");
        $stmt->execute([$tahun_ajaran_id, $today, $nowTime, $today, $kelas['id']]);
        logAudit($pdo, 'PRESENSI_MANUAL', 'Menyimpan presensi manual', ['kelas_id' => $kelas['id'], 'tanggal' => $today]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Gagal menyimpan presensi manual: ' . $e->getMessage());
        http_response_code(500);
        exit('Presensi gagal disimpan. Silakan coba lagi.');
    }

    redirect(appUrl('guru/presensi_manual.php?kelas_id=' . rawurlencode($kelas['id'])));
}

// Ambil data siswa dengan status presensi hari ini
$siswaList = $pdo->prepare("
    SELECT s.id, s.nama, s.nis, p.status, p.keterangan
    FROM siswa s
    LEFT JOIN presensi p ON s.id = p.siswa_id AND p.tanggal = ?
    WHERE s.kelas_id = ? AND s.is_active = 1
    ORDER BY s.nama
");
$siswaList->execute([$today, $kelas['id']]);
$siswaList = $siswaList->fetchAll();

$totalSiswa = count($siswaList);

include '../includes/header.php';
?>

<style>
/* Filter Card */
.filter-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.filter-card .label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
    display: block;
}

.filter-card .select {
    width: 100%;
    max-width: 300px;
    padding: 0.625rem 1rem;
    border: 1.5px solid var(--color-border);
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    color: var(--color-text);
    background: white;
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.filter-card .select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Simpan Button */
.btn-simpan {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.btn-simpan:hover {
    background: #1D4ED8;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
    transform: translateY(-1px);
}

.btn-simpan:active {
    transform: translateY(0);
}

.btn-simpan .badge-count {
    background: white;
    color: var(--color-primary);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    min-width: 1.5rem;
    text-align: center;
}

/* Table Styles */
.presensi-table {
    width: 100%;
    border-collapse: collapse;
}

.presensi-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #F8FAFC;
    border-bottom: 1px solid var(--color-border);
}

.presensi-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--color-border);
    vertical-align: middle;
}

.presensi-table tr:last-child td {
    border-bottom: none;
}

.presensi-table tr:hover {
    background: #F8FAFC;
}

/* Presensi Pills */
.presensi-pills {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.presensi-pill {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 2px solid;
    background: white;
}

.presensi-pill:active {
    transform: scale(0.95);
}

.pill-hadir {
    border-color: #22C55E;
    color: #16A34A;
}

.pill-hadir.active,
.pill-hadir:hover {
    background: #22C55E;
    color: white;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
}

.pill-izin {
    border-color: #3B82F6;
    color: #2563EB;
}

.pill-izin.active,
.pill-izin:hover {
    background: #3B82F6;
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.pill-sakit {
    border-color: #F97316;
    color: #EA580C;
}

.pill-sakit.active,
.pill-sakit:hover {
    background: #F97316;
    color: white;
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
}

.pill-telat {
    border-color: #EAB308;
    color: #CA8A04;
}

.pill-telat.active,
.pill-telat:hover {
    background: #EAB308;
    color: white;
    box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3);
}

.pill-alfa {
    border-color: #EF4444;
    color: #DC2626;
}

.pill-alfa.active,
.pill-alfa:hover {
    background: #EF4444;
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

/* Keterangan Wrapper */
.ket-wrapper {
    margin-top: 0.5rem;
    display: none;
}

.ket-wrapper.show {
    display: block;
}

.ket-label {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    margin-bottom: 0.25rem;
    display: block;
}

.ket-label .ket-type {
    font-weight: 600;
    text-transform: lowercase;
}

.ket-input {
    width: 100%;
    max-width: 250px;
    padding: 0.5rem 0.75rem;
    border: 1.5px solid var(--color-border);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-text);
    transition: border-color 0.15s ease;
}

.ket-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.ket-input::placeholder {
    color: #9CA3AF;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-hadir {
    background: #DCFCE7;
    color: #16A34A;
}

.status-terlambat {
    background: #FEF9C3;
    color: #CA8A04;
}

.status-izin {
    background: #DBEAFE;
    color: #2563EB;
}

.status-sakit {
    background: #FFEDD5;
    color: #EA580C;
}

.status-alfa {
    background: #FEE2E2;
    color: #DC2626;
}

/* Animasi */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-in {
    animation: fadeIn 0.3s ease forwards;
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Presensi Manual</h1>
        <p class="page-desc">Isi kehadiran siswa secara manual</p>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card animate-in">
    <form method="POST" id="presensiForm">
        <?= csrfField() ?>
        <input type="hidden" name="presensi_data" id="presensiData" value="">
        <div style="display:flex;align-items:flex-end;gap:1rem;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label class="label">Pilih Kelas</label>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <select class="select" id="kelasSelect" style="max-width:200px;" onchange="window.location.href='?kelas_id='+this.value">
                        <?php foreach ($allKelas as $k): ?>
                        <option value="<?= escape($k['id']) ?>" <?= $k['id'] === $selectedKelasId ? 'selected' : '' ?>><?= escape($k['nama']) ?></option>
                        <?php endforeach; ?>
                </select>
                    <?php if ($waliKelasNama): ?>
                    <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;background:var(--color-primary-50);color:var(--color-primary-dark);border-radius:9999px;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Anda wali kelas <?= escape($waliKelasNama) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="btn-simpan" id="btnSimpan">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Simpan Presensi
                <span class="badge-count"><?= $totalSiswa ?></span>
            </button>
        </div>
    </form>
</div>

<!-- Tabel Presensi -->
<div class="card animate-in" style="animation-delay: 0.1s;">
    <div class="card-body p-0">
        <table class="presensi-table">
            <thead>
                <tr>
                    <th style="width:50px;">NO</th>
                    <th style="width:100px;">NIS</th>
                    <th>NAMA</th>
                    <th style="width:120px;">STATUS HARI INI</th>
                    <th>PILIH KEHADIRAN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siswaList as $i => $s): ?>
                <?php
                    $stat = $s['status'] ?? 'HADIR';
                    $showKet = in_array($stat, ['IZIN', 'SAKIT']);
                    $statusBadgeClass = match($stat) {
                        'HADIR' => 'status-hadir',
                        'TERLAMBAT' => 'status-terlambat',
                        'IZIN' => 'status-izin',
                        'SAKIT' => 'status-sakit',
                        'ALFA' => 'status-alfa',
                        default => 'status-hadir'
                    };
                    $statusLabel = match($stat) {
                        'HADIR' => 'Hadir',
                        'TERLAMBAT' => 'Telat',
                        'IZIN' => 'Izin',
                        'SAKIT' => 'Sakit',
                        'ALFA' => 'Alfa',
                        default => 'Hadir'
                    };
                ?>
                <tr data-siswa-id="<?= escape($s['id']) ?>" style="animation: fadeIn 0.3s ease forwards; animation-delay: <?= min($i * 30, 300) ?>ms; opacity: 0;">
                    <td style="font-size:0.875rem;color:var(--color-text-muted);"><?= $i + 1 ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8125rem;font-weight:500;color:var(--color-text);"><?= escape($s['nis']) ?></td>
                    <td style="font-weight:600;color:var(--color-text);"><?= escape($s['nama']) ?></td>
                    <td>
                        <span class="status-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                    </td>
                    <td>
                        <div class="presensi-pills">
                            <button type="button" class="presensi-pill pill-hadir <?= $stat === 'HADIR' ? 'active' : '' ?>" data-status="HADIR">Hadir</button>
                            <button type="button" class="presensi-pill pill-izin <?= $stat === 'IZIN' ? 'active' : '' ?>" data-status="IZIN">Izin</button>
                            <button type="button" class="presensi-pill pill-sakit <?= $stat === 'SAKIT' ? 'active' : '' ?>" data-status="SAKIT">Sakit</button>
                            <button type="button" class="presensi-pill pill-telat <?= $stat === 'TERLAMBAT' ? 'active' : '' ?>" data-status="TERLAMBAT">Telat</button>
                            <button type="button" class="presensi-pill pill-alfa <?= $stat === 'ALFA' ? 'active' : '' ?>" data-status="ALFA">Alfa</button>
                        </div>
                        <div class="ket-wrapper <?= $showKet ? 'show' : '' ?>" id="ket-<?= $s['id'] ?>">
                            <label class="ket-label">Keterangan <span class="ket-type"><?= $stat === 'IZIN' ? 'izin' : 'sakit' ?></span>:</label>
                            <input type="text" class="ket-input" placeholder="Contoh: demam" value="<?= escape($s['keterangan'] ?? '') ?>">
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Handle pill button clicks
document.querySelectorAll('.presensi-pill').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const siswaId = row.dataset.siswaId;
        const status = this.dataset.status;

        // Remove active from siblings
        row.querySelectorAll('.presensi-pill').forEach(b => b.classList.remove('active'));

        // Add active to clicked
        this.classList.add('active');

        // Show/hide keterangan
        const ketWrapper = document.getElementById('ket-' + siswaId);
        const ketType = ketWrapper.querySelector('.ket-type');

        if (status === 'IZIN' || status === 'SAKIT') {
            ketType.textContent = status === 'IZIN' ? 'izin' : 'sakit';
            ketWrapper.classList.add('show');
            ketWrapper.querySelector('.ket-input').focus();
        } else {
            ketWrapper.classList.remove('show');
            ketWrapper.querySelector('.ket-input').value = '';
        }

        updateCounter();
    });
});

// Update counter
function updateCounter() {
    const total = document.querySelectorAll('tr[data-siswa-id]').length;
    document.querySelector('.badge-count').textContent = total;
}

// Handle save button
document.getElementById('btnSimpan').addEventListener('click', function() {
    const presensiData = [];

    document.querySelectorAll('tr[data-siswa-id]').forEach(row => {
        const siswaId = row.dataset.siswaId;
        const activePill = row.querySelector('.presensi-pill.active');
        const status = activePill ? activePill.dataset.status : 'HADIR';
        const ketInput = row.querySelector('.ket-input');
        const keterangan = ketInput ? ketInput.value : '';

        presensiData.push({
            siswa_id: siswaId,
            status: status,
            keterangan: keterangan
        });
    });

    document.getElementById('presensiData').value = JSON.stringify(presensiData);
    document.getElementById('presensiForm').submit();
});

// Initialize - set default active state for HADIR if no status
document.querySelectorAll('tr[data-siswa-id]').forEach(row => {
    const hasActive = row.querySelector('.presensi-pill.active');
    if (!hasActive) {
        row.querySelector('.pill-hadir').classList.add('active');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
