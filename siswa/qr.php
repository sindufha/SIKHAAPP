<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$siswa = null;
$error = '';

if (isset($_GET['nis'])) {
    $nis = getString('nis', 50);
    $stmt = $pdo->prepare("SELECT s.*, k.nama as nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.nis = ? AND s.is_active = 1 LIMIT 1");
    $stmt->execute([$nis]);
    $siswa = $stmt->fetch();

    if (!$siswa) {
        $error = 'Siswa dengan NIS tersebut tidak ditemukan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Siswa - Sikha</title>
    <link rel="icon" type="image/svg+xml" href="<?= appUrl('assets/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= appUrl('assets/css/style.css') ?>" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { background: var(--color-background); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    </style>
</head>
<body>
    <div style="width:100%;max-width:420px;padding:1rem;">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <h1 style="font-size:1.25rem;font-weight:800;color:var(--color-text);margin-bottom:0.25rem;">Sikha</h1>
            <p style="color:var(--color-text-muted);font-size:0.85rem;">Cari QR Code Siswa</p>
        </div>

        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body">
                <form method="GET" action="">
                    <div style="display:flex;gap:0.5rem;">
                        <input type="text" class="input" name="nis" placeholder="Masukkan NIS" value="<?= escape($_GET['nis'] ?? '') ?>" maxlength="50" autocomplete="off" required style="flex:1;">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>

        <?php if ($siswa): ?>
        <div class="card">
            <div class="card-body text-center" style="padding:2rem;">
                <h5 style="font-weight:700;margin-bottom:0.25rem;"><?= escape($siswa['nama']) ?></h5>
                <p class="text-muted" style="margin-bottom:1.5rem;"><?= escape($siswa['nis']) ?> — <?= escape($siswa['nama_kelas']) ?></p>

                <div id="qrcode" style="display:flex;justify-content:center;margin-bottom:1.5rem;"></div>

                <p style="font-size:0.85rem;color:var(--color-text-muted);margin:0;">Tunjukkan QR Code ini ke petugas atau guru piket saat melakukan presensi.</p>
            </div>
        </div>
        <script>
            new QRCode(document.getElementById("qrcode"), {
                text: <?= jsonForHtml($siswa['qr_code']) ?>,
                width: 250, height: 250,
                colorDark: "#000000", colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        </script>
        <?php endif; ?>

        <div style="text-align:center;margin-top:1.5rem;">
            <a href="<?= appUrl('login.php') ?>" style="color:var(--color-text-muted);font-size:0.85rem;text-decoration:none;">
                &larr; Kembali ke Login
            </a>
        </div>
    </div>
</body>
</html>
