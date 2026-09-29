<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(dashboardUrl());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = postString('username', 100);
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'since' => time()];
    if ((time() - (int)$attempts['since']) > 300) $attempts = ['count' => 0, 'since' => time()];
    if ((int)$attempts['count'] >= 5) {
        $error = 'Terlalu banyak percobaan login. Coba lagi beberapa menit lagi.';
    }

    if ($error) {
        // Pesan pembatasan sudah disiapkan di atas.
    } elseif (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, nama, is_active FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                session_regenerate_id(true);
                unset($_SESSION['login_attempts']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama'];

                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $user['id']]);
                }

                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                logAudit($pdo, 'LOGIN', 'User login berhasil');

                redirect(dashboardUrl());
            } else {
                $error = 'Akun Anda tidak aktif.';
            }
        } else {
            $attempts['count'] = (int)$attempts['count'] + 1;
            $_SESSION['login_attempts'] = $attempts;
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sikha</title>
    <link rel="icon" type="image/svg+xml" href="<?= appUrl('assets/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= appUrl('assets/css/style.css?v=3.0') ?>" rel="stylesheet">
</head>
<body>
    <div id="root">
        <div class="min-h-screen bg-background flex flex-col items-center justify-center p-4">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8 animate-in">
                    <div class="w-16 h-16 rounded-2xl bg-white flex items-center justify-center mx-auto mb-4 shadow-sm p-1">
                        <img src="<?= appUrl('assets/logo.png') ?>" alt="SIKHA Logo" style="width:100%;height:100%;object-fit:contain;">
                    </div>
                    <h1 class="page-title" style="font-size:1.5rem;">SDI Khadijah Sukorejo</h1>
                    <p class="text-sm text-muted mt-1">Sistem Kehadiran Siswa (SIKHA)</p>
                </div>

                <div class="card animate-in" style="animation-delay:0.1s;">
                    <h2 class="text-base font-bold text-center mb-6 font-heading">Masuk ke Akun Anda</h2>

                    <?php if ($error): ?>
                    <div class="alert alert-danger mb-4"><?= escape($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="loginForm">
                        <?= csrfField() ?>
                        <div class="mb-4">
                            <label class="label">Username<span class="required">*</span></label>
                            <input type="text" class="input" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <label class="label">Password<span class="required">*</span></label>
                            <div class="relative">
                                <input type="password" class="input pr-10" name="password" id="passwordInput" placeholder="Masukkan password" required autocomplete="current-password">
                                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-muted" id="togglePassword" aria-label="Tampilkan password">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="eyeIcon"><path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full h-10 px-4 text-sm rounded-lg gap-2" id="btnSubmit">
                            <span class="spinner d-none" id="spinner">
                                <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/><path d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"/></svg>
                            </span>
                            <span class="inline-flex items-center gap-2 leading-none">Masuk</span>
                        </button>
                    </form>

                    <div class="mt-5 pt-4 border-t text-center">
                        <a href="<?= appUrl('siswa/qr.php') ?>" class="text-sm font-medium text-primary">Siswa? Lihat QR Code di sini</a>
                    </div>
                </div>

                <p class="text-center mt-6 text-xs text-muted">&copy; <?= date('Y') ?> SDI Khadijah Sukorejo</p>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/>';
        }
    });

    document.getElementById('loginForm').addEventListener('submit', function() {
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('spinner').classList.remove('d-none');
    });
    </script>
</body>
</html>
