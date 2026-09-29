<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikha - Sistem Kehadiran Siswa</title>
    <link rel="icon" type="image/svg+xml" href="<?= appUrl('assets/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= appUrl('assets/css/style.css?v=3.0') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div id="root">
    <?php if(isLoggedIn()): ?>
    <div class="layout-wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <div class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle d-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                    </button>
                    <h1 class="topbar-title" id="pageTitle">
                        <?php
                        $pageTitles = [
                            'dashboard.php' => 'Dashboard',
                            'siswa.php' => 'Data Siswa',
                            'kelas.php' => 'Data Kelas',
                            'tahun_ajaran.php' => 'Tahun Ajaran',
                            'jam_presensi.php' => 'Jam Presensi',
                            'presensi_qr.php' => 'Scan QR',
                            'generate_qr.php' => 'Generate QR',
                            'laporan.php' => 'Laporan',
                            'users.php' => 'Data Pengguna',
                            'audit_log.php' => 'Audit Log',
                            'profil.php' => 'Profil',
                            'presensi_manual.php' => 'Presensi Manual',
                        ];
                        $currentFile = basename($_SERVER['PHP_SELF']);
                        echo $pageTitles[$currentFile] ?? 'SIKHA';
                        ?>
                    </h1>
                </div>
                <div class="topbar-right">
                    <!-- Live Clock -->
                    <div class="live-clock" id="liveClock">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div class="live-clock-text">
                            <span class="live-clock-time" id="liveTime">--:--:--</span>
                            <span class="live-clock-date" id="liveDate">---</span>
                        </div>
                    </div>

                    <div class="user-dropdown" id="userDropdown">
                        <button class="user-dropdown-trigger" onclick="toggleUserMenu(event)" aria-haspopup="true" aria-expanded="false">
                            <div class="user-dropdown-avatar">
                                <span><?= strtoupper(substr(escape($_SESSION['nama'] ?? 'U'), 0, 1)) ?></span>
                            </div>
                            <span class="user-dropdown-name"><?= escape($_SESSION['nama'] ?? 'User') ?></span>
                            <svg class="user-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="<?= appUrl('profil.php') ?>" class="user-dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Profil
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <form method="POST" action="<?= appUrl('logout.php') ?>">
                                <?= csrfField() ?>
                                <button type="submit" class="user-dropdown-item text-error" style="width:100%;border:0;background:none;cursor:pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content-area custom-scrollbar">
    <?php endif; ?>
