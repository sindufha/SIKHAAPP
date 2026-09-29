<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

// Ambil data presensi hari ini untuk sidebar
$today = date('Y-m-d');
$scanHariIni = $pdo->prepare("
    SELECT p.*, s.nama, s.nis, s.jenis_kelamin, k.nama as nama_kelas
    FROM presensi p
    JOIN siswa s ON p.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE p.tanggal = ?
    ORDER BY p.created_at DESC
");
$scanHariIni->execute([$today]);
$scanList = $scanHariIni->fetchAll();
$totalScan = count($scanList);

include '../includes/header.php';
?>

<style>
    .scan-page { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; align-items: start; }
    @media (max-width: 991.98px) { .scan-page { grid-template-columns: 1fr; } }

    .scanner-frame {
        width: 100%;
        max-width: 340px;
        aspect-ratio: 1;
        margin: 0 auto 1.5rem;
        position: relative;
        border: 3px dashed var(--color-primary);
        border-radius: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: var(--color-primary-50);
    }
    .scanner-frame #reader {
        width: 100% !important;
        height: 100% !important;
    }
    .scanner-frame #reader video {
        object-fit: cover;
        border-radius: 1rem;
    }
    .scanner-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        color: var(--color-text-muted);
    }
    .scanner-placeholder svg { opacity: 0.4; }
    .scanner-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
    .scanner-desc { font-size: 0.85rem; color: var(--color-text-muted); max-width: 280px; margin: 0 auto 1.25rem; }
    .scan-count-badge {
        background: var(--color-primary-50);
        color: var(--color-primary);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }
    .scan-list { max-height: 520px; overflow-y: auto; }
    .scan-list::-webkit-scrollbar { width: 0; }
    .scan-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--color-border);
        transition: background 0.1s;
    }
    .scan-item:last-child { border-bottom: none; }
    .scan-item:hover { background: #F8FAFC; }
    .scan-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .scan-avatar.laki { background: #DBEAFE; color: #1D4ED8; }
    .scan-avatar.perempuan { background: #FCE7F3; color: #BE185D; }
    .scan-info { flex: 1; min-width: 0; }
    .scan-name { font-size: 0.875rem; font-weight: 600; color: var(--color-text); }
    .scan-detail { font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.125rem; }
    .scan-right { text-align: right; flex-shrink: 0; }
    .scan-time { font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem; }

    .scan-toast {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        z-index: 200;
        min-width: 320px;
        max-width: 400px;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        transform: translateX(120%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .scan-toast.show { transform: translateX(0); }
    .scan-toast.success { background: #fff; border-left: 4px solid var(--color-success); }
    .scan-toast.error { background: #fff; border-left: 4px solid var(--color-error); }
    .scan-toast-icon { width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .scan-toast.success .scan-toast-icon { background: var(--color-success-bg); color: var(--color-success); }
    .scan-toast.error .scan-toast-icon { background: var(--color-error-bg); color: var(--color-error); }
    .scan-toast-text { flex: 1; }
    .scan-toast-name { font-size: 0.875rem; font-weight: 700; }
    .scan-toast-msg { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.125rem; }

    #reader { border: none !important; width: 100% !important; height: 100% !important; }
    #reader video { border-radius: 1rem !important; object-fit: cover !important; width: 100% !important; height: 100% !important; }
    #reader img[alt="Info icon"] { display: none !important; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Presensi QR Code</h1>
        <p class="page-desc">Scan kartu QR siswa untuk presensi cepat</p>
    </div>
</div>

<!-- Toast Notifikasi -->
<div class="scan-toast" id="scanToast">
    <div class="scan-toast-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="scan-toast-text">
        <div class="scan-toast-name" id="toastName">Nama Siswa</div>
        <div class="scan-toast-msg" id="toastMsg">Presensi berhasil</div>
    </div>
</div>

<div class="scan-page">
    <!-- Scanner -->
    <div class="card" style="padding:2rem;text-align:center;">
        <div class="scanner-frame" id="scannerFrame">
            <div class="scanner-placeholder" id="scannerPlaceholder">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                    <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                    <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                    <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                    <rect x="7" y="7" width="3" height="3" rx="0.5"/>
                    <rect x="14" y="7" width="3" height="3" rx="0.5"/>
                    <rect x="7" y="14" width="3" height="3" rx="0.5"/>
                    <rect x="14" y="14" width="3" height="3" rx="0.5"/>
                </svg>
            </div>
            <div id="reader" style="display:none;"></div>
        </div>
        <div id="scannerInfo">
            <div class="scanner-title">Mulai Scan QR</div>
            <div class="scanner-desc">Arahkan kamera ke kartu QR Code siswa untuk presensi cepat</div>
            <button class="btn btn-primary btn-lg h-11 px-4 text-sm rounded-lg gap-2" id="btnScan" onclick="startScanner()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Buka Kamera
            </button>
        </div>
        <button class="btn btn-danger btn-lg h-11 px-4 text-sm rounded-lg gap-2" id="btnStop" onclick="stopScanner()" style="display:none;margin-top:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            Stop Kamera
        </button>
    </div>

    <!-- Scan History -->
    <div class="scan-history-card">
        <div class="scan-history-header">
            <h5>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Scan Hari Ini
            </h5>
            <span class="scan-count-badge"><?= $totalScan ?> siswa</span>
        </div>
        <div class="scan-list" id="scanList">
            <?php if ($totalScan > 0): ?>
                <?php foreach ($scanList as $s): ?>
                <div class="scan-item">
                    <div class="scan-avatar <?= $s['jenis_kelamin'] === 'LAKI_LAKI' ? 'laki' : 'perempuan' ?>">
                        <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                    </div>
                    <div class="scan-info">
                        <div class="scan-name"><?= escape($s['nama']) ?></div>
                        <div class="scan-detail"><?= escape($s['nama_kelas']) ?> — <?= escape($s['nis']) ?></div>
                    </div>
                    <div class="scan-right">
                        <?php
                        $badgeClass = match($s['status']) {
                            'HADIR' => 'badge-success',
                            'TERLAMBAT' => 'badge-warning',
                            'IZIN' => 'badge-info',
                            'SAKIT' => 'badge-warning',
                            'ALFA' => 'badge-error',
                            default => 'badge-secondary'
                        };
                        $badgeLabel = match($s['status']) {
                            'HADIR' => 'Hadir',
                            'TERLAMBAT' => 'Terlambat',
                            'IZIN' => 'Izin',
                            'SAKIT' => 'Sakit',
                            'ALFA' => 'Alfa',
                            default => $s['status']
                        };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                        <div class="scan-time"><?= $s['jam_datang'] ? date('H:i', strtotime($s['jam_datang'])) : '' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:3rem 1.25rem;text-align:center;color:var(--color-text-muted);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;margin-bottom:0.75rem;"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/></svg>
                    <p style="font-size:0.85rem;">Belum ada scan hari ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let isProcessing = false;
let cameraRunning = false;

function startScanner() {
    document.getElementById('scannerPlaceholder').style.display = 'none';
    document.getElementById('scannerInfo').style.display = 'none';
    document.getElementById('btnStop').style.display = 'inline-flex';
    const readerEl = document.getElementById('reader');
    readerEl.style.display = 'block';

    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 220, height: 220 }, aspectRatio: 1.0 };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanFailure
    ).then(() => {
        cameraRunning = true;
    }).catch(err => {
        console.error("Camera start error:", err);
        cameraRunning = false;
        resetScannerUI();
        alert('Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.');
    });
}

function stopScanner() {
    if (html5QrCode && cameraRunning) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            cameraRunning = false;
            resetScannerUI();
        }).catch(() => {
            cameraRunning = false;
            resetScannerUI();
        });
    } else {
        resetScannerUI();
    }
}

function resetScannerUI() {
    document.getElementById('reader').style.display = 'none';
    document.getElementById('reader').innerHTML = '';
    document.getElementById('scannerPlaceholder').style.display = 'flex';
    document.getElementById('scannerInfo').style.display = 'block';
    document.getElementById('btnStop').style.display = 'none';
}

function onScanSuccess(decodedText) {
    if (isProcessing) return;
    isProcessing = true;
    html5QrCode.pause(true);

    fetch(<?= jsonForHtml(appUrl('api/presensi.php')) ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': <?= jsonForHtml(csrfToken()) ?> },
        body: JSON.stringify({ qr_code: decodedText })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data);
        if (data.success) {
            addScanItem(data.data);
        }
        setTimeout(() => {
            html5QrCode.resume();
            isProcessing = false;
        }, 2500);
    })
    .catch(() => {
        showToast({ success: false, message: 'Gagal mengirim data' });
        setTimeout(() => {
            html5QrCode.resume();
            isProcessing = false;
        }, 2500);
    });
}

function onScanFailure() {}

function showToast(data) {
    const toast = document.getElementById('scanToast');
    const icon = toast.querySelector('.scan-toast-icon');

    toast.className = 'scan-toast ' + (data.success ? 'success' : 'error');
    document.getElementById('toastName').innerText = data.success ? data.data.nama : 'Gagal';
    document.getElementById('toastMsg').innerText = data.message;

    if (data.success) {
        icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    } else {
        icon.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
    }

    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function addScanItem(data) {
    const list = document.getElementById('scanList');
    const now = new Date();
    const time = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    const isLaki = data.jenis_kelamin === 'LAKI_LAKI';
    const statusClass = data.status === 'HADIR' ? 'badge-success' : (data.status === 'TERLAMBAT' ? 'badge-warning' : 'badge-info');
    const statusLabel = data.status === 'HADIR' ? 'Hadir' : (data.status === 'TERLAMBAT' ? 'Terlambat' : data.status);

    // Hapus placeholder kosong jika ada
    const emptyState = list.querySelector('[style*="text-align:center"]');
    if (emptyState) emptyState.closest('.scan-item, div')?.remove();

    const item = document.createElement('div');
    item.className = 'scan-item';
    item.style.cssText = 'background:var(--color-primary-50);animation:fadeHighlight 2s ease forwards;';
    const avatar = document.createElement('div');
    avatar.className = 'scan-avatar ' + (isLaki ? 'laki' : 'perempuan');
    avatar.textContent = String(data.nama || '?').charAt(0).toUpperCase();
    const info = document.createElement('div');
    info.className = 'scan-info';
    const name = document.createElement('div');
    name.className = 'scan-name';
    name.textContent = data.nama || '';
    const detail = document.createElement('div');
    detail.className = 'scan-detail';
    detail.textContent = (data.nama_kelas || '') + ' — ' + (data.nis || '');
    info.append(name, detail);
    const right = document.createElement('div');
    right.className = 'scan-right';
    const status = document.createElement('span');
    status.className = 'badge ' + statusClass;
    status.textContent = statusLabel;
    const clock = document.createElement('div');
    clock.className = 'scan-time';
    clock.textContent = time;
    right.append(status, clock);
    item.append(avatar, info, right);
    list.prepend(item);

    // Update counter
    const badge = document.querySelector('.scan-count-badge');
    const current = parseInt(badge.textContent) || 0;
    badge.textContent = (current + 1) + ' siswa';
}
</script>

<style>
@keyframes fadeHighlight {
    0% { background: var(--color-primary-100); }
    100% { background: transparent; }
}
</style>

<?php include '../includes/footer.php'; ?>
