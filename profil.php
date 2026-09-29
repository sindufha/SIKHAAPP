<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $password_lama = is_string($_POST['password_lama'] ?? null) ? $_POST['password_lama'] : '';
    $password_baru = is_string($_POST['password_baru'] ?? null) ? $_POST['password_baru'] : '';
    $password_konfirmasi = is_string($_POST['password_konfirmasi'] ?? null) ? $_POST['password_konfirmasi'] : '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($password_lama, $user['password'])) {
        $error = 'Password lama tidak sesuai.';
    } elseif (strlen($password_baru) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
        session_regenerate_id(true);
        logAudit($pdo, 'GANTI_PASSWORD', 'User mengganti password');
        $success = 'Password berhasil diubah.';
    }
}

include 'includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Profil</h1>
        <p class="page-desc">Informasi akun dan pengaturan password</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-5">
        <div class="card">
            <div class="card-body" style="text-align:center;padding:2rem;">
                <div style="width:4rem;height:4rem;border-radius:9999px;background:var(--color-primary-100);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;margin:0 auto 1rem;">
                    <?= strtoupper(substr(escape($_SESSION['nama'] ?? 'U'), 0, 1)) ?>
                </div>
                <h5 class="font-bold" style="margin-bottom:0.25rem;"><?= escape($_SESSION['nama'] ?? 'User') ?></h5>
                <p class="text-muted text-sm">@<?= escape($_SESSION['username'] ?? '') ?></p>
                <span class="badge badge-<?= $_SESSION['role'] === 'ADMIN' ? 'error' : 'info' ?>" style="margin-top:0.5rem;display:inline-flex;"><?= escape($_SESSION['role']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Ganti Password</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= escape($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= escape($success) ?></div><?php endif; ?>
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" class="input" name="password_lama" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="input" name="password_baru" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="input" name="password_konfirmasi" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
