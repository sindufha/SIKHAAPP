<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <img src="<?= appUrl('assets/logo.png') ?>" alt="SIKHA Logo" style="width:100%;height:100%;object-fit:contain;">
        </div>
        <div class="sidebar-brand-text">
            <h3>SIKHA</h3>
            <small>SDI Khadijah Sukorejo</small>
        </div>
    </div>

    <nav class="sidebar-nav custom-scrollbar">
        <?php if(hasRole('ADMIN')): ?>
        <div class="sidebar-section">Utama</div>
        <a href="<?= appUrl('admin/dashboard.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="<?= appUrl('admin/siswa.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'siswa.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
            <span>Data Siswa</span>
        </a>
        <a href="<?= appUrl('admin/kelas.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'kelas.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>
            <span>Data Kelas</span>
        </a>
        <a href="<?= appUrl('admin/users.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            <span>Data Pengguna</span>
        </a>

        <div class="sidebar-section">Presensi</div>
        <a href="<?= appUrl('admin/generate_qr.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'generate_qr.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
            <span>Generate QR</span>
        </a>
        <a href="<?= appUrl('admin/presensi_qr.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'presensi_qr.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>
            <span>Scan QR</span>
        </a>
        <a href="<?= appUrl('admin/jam_presensi.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'jam_presensi.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <span>Jam Presensi</span>
        </a>
        <a href="<?= appUrl('admin/laporan.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'laporan.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            <span>Laporan</span>
        </a>
        <a href="<?= appUrl('admin/tahun_ajaran.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'tahun_ajaran.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Tahun Ajaran</span>
        </a>
        <a href="<?= appUrl('admin/audit_log.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'audit_log.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
            <span>Audit Log</span>
        </a>

        <?php elseif(hasRole('GURU')): ?>
        <div class="sidebar-section">Utama</div>
        <a href="<?= appUrl('guru/dashboard.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section">Presensi</div>
        <a href="<?= appUrl('guru/presensi_qr.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'presensi_qr.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>
            <span>Scan QR</span>
        </a>
        <a href="<?= appUrl('guru/presensi_manual.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'presensi_manual.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <span>Presensi Manual</span>
        </a>
        <a href="<?= appUrl('guru/laporan.php') ?>" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'laporan.php' ? 'sidebar-link-active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            <span>Laporan</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <span><?= strtoupper(substr(escape($_SESSION['nama'] ?? 'U'), 0, 1)) ?></span>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= escape($_SESSION['nama'] ?? 'User') ?></div>
                <div class="sidebar-user-role"><?= escape($_SESSION['role'] ?? '') ?></div>
            </div>
        </div>
    </div>
</div>
