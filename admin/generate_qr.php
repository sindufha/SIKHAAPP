<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

$kelasList = $pdo->query("SELECT * FROM kelas ORDER BY nama")->fetchAll();

$siswaList = [];
$selectedKelas = getString('kelas_id', 36);

if ($selectedKelas) {
    $stmt = $pdo->prepare("SELECT s.*, k.nama as nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.kelas_id = ? AND s.is_active = 1 ORDER BY s.nama");
    $stmt->execute([$selectedKelas]);
    $siswaList = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div class="page-header d-print-none">
    <div>
        <h1 class="page-title">Generate QR Code</h1>
        <p class="page-desc">Cetak QR Code untuk presensi siswa</p>
    </div>
    <?php if(count($siswaList) > 0): ?>
    <button class="btn btn-success" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Cetak QR
    </button>
    <?php endif; ?>
</div>

<div class="card animate-in mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1" style="min-width:200px;">
                <label class="label">Pilih Kelas</label>
                <select name="kelas_id" class="input" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= escape($k['id']) ?>" <?= $selectedKelas === $k['id'] ? 'selected' : '' ?>><?= escape($k['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary h-10 px-4 text-sm rounded-lg">Tampilkan Siswa</button>
            </div>
        </form>
    </div>
</div>

<?php if(count($siswaList) > 0): ?>
<style>
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>
<div id="print-area">
    <div class="row g-2">
        <?php foreach ($siswaList as $idx => $s): ?>
        <div class="col-6 col-md-4 col-lg-3" style="margin-bottom:1rem;">
            <div class="card" style="text-align:center;page-break-inside:avoid;">
                <div class="card-body p-3">
                    <div id="qr_<?= $idx ?>" style="display:flex;justify-content:center;margin-bottom:0.5rem;"></div>
                    <h6 style="font-size:0.85rem;font-weight:700;margin:0.5rem 0 0.125rem;"><?= escape($s['nama']) ?></h6>
                    <small class="text-muted"><?= escape($s['nis']) ?></small>
                </div>
            </div>
        </div>
        <script>setTimeout(function(){new QRCode(document.getElementById("qr_<?= $idx ?>"),{text:<?= jsonForHtml($s['qr_code']) ?>,width:130,height:130,colorDark:"#000000",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.H})},100);</script>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
