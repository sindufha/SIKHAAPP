<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if (in_array($_POST['action'], ['add', 'edit'])) {
        $nis = postString('nis', 50); $nama = postString('nama', 150); $kelas_id = postString('kelas_id', 36);
        $jk = postString('jenis_kelamin', 20);
        $tempat_lahir = postString('tempat_lahir', 100) ?: null;
        $tanggal_lahir = postString('tanggal_lahir', 10) ?: null;
        $alamat = postString('alamat', 5000) ?: null;
        if ($nis === '' || $nama === '' || $kelas_id === '' || !in_array($jk, ['LAKI_LAKI', 'PEREMPUAN'], true)) {
            abortRequest('Data siswa tidak valid.');
        }
        $duplicate = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$nis, postString('id', 36)]);
        if ($duplicate->fetchColumn()) abortRequest('NIS sudah digunakan.');
        if ($tanggal_lahir && !isValidDate($tanggal_lahir)) abortRequest('Tanggal lahir tidak valid.');
        $kelasCheck = $pdo->prepare('SELECT id FROM kelas WHERE id = ?');
        $kelasCheck->execute([$kelas_id]);
        if (!$kelasCheck->fetchColumn()) abortRequest('Kelas tidak ditemukan.');

        if ($_POST['action'] === 'add') {
            $qr = bin2hex(random_bytes(10));
            $stmt = $pdo->prepare("INSERT INTO siswa (id, nis, nama, kelas_id, qr_code, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nis, $nama, $kelas_id, $qr, $jk, $tempat_lahir, $tanggal_lahir, $alamat]);
            logAudit($pdo, 'CREATE_SISWA', "Menambah siswa $nama");
        } else {
            $stmt = $pdo->prepare("UPDATE siswa SET nis=?, nama=?, kelas_id=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, alamat=? WHERE id=?");
            $stmt->execute([$nis, $nama, $kelas_id, $jk, $tempat_lahir, $tanggal_lahir, $alamat, postString('id', 36)]);
            logAudit($pdo, 'UPDATE_SISWA', "Mengubah siswa $nama");
        }
        redirect(appUrl('admin/siswa.php'));
    }
    if ($_POST['action'] === 'delete') {
        $pdo->prepare("UPDATE siswa SET is_active = 0 WHERE id = ?")->execute([postString('id', 36)]);
        logAudit($pdo, 'NONAKTIFKAN_SISWA', 'Menonaktifkan siswa');
        redirect(appUrl('admin/siswa.php'));
    }
    if ($_POST['action'] === 'reset_qr') {
        $qr = bin2hex(random_bytes(10));
        $pdo->prepare("UPDATE siswa SET qr_code = ? WHERE id = ?")->execute([$qr, postString('id', 36)]);
        logAudit($pdo, 'RESET_QR_SISWA', 'Reset QR siswa');
        redirect(appUrl('admin/siswa.php'));
    }
    if ($_POST['action'] === 'activate') {
        $pdo->prepare("UPDATE siswa SET is_active = 1 WHERE id = ?")->execute([postString('id', 36)]);
        logAudit($pdo, 'AKTIFKAN_SISWA', 'Mengaktifkan kembali siswa');
        redirect(appUrl('admin/siswa.php'));
    }
}

// Pencarian & filter
$search = getString('search', 100);
$filterKelas = getString('kelas_id', 36);
$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (s.nama LIKE ? OR s.nis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterKelas) {
    $where .= " AND s.kelas_id = ?";
    $params[] = $filterKelas;
}

$siswaList = $pdo->prepare("SELECT s.*, k.nama as nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE $where ORDER BY s.nama");
$siswaList->execute($params);
$siswaList = $siswaList->fetchAll();

$kelasList = $pdo->query("SELECT id, nama as nama_kelas FROM kelas ORDER BY nama")->fetchAll();
$totalSiswa = count($siswaList);
include '../includes/header.php';
?>

<div class="space-y-5">
    <div class="page-header">
        <div>
            <h1 class="page-title">Data Siswa</h1>
            <p class="page-desc"><?= $totalSiswa ?> siswa terdaftar</p>
        </div>
        <button class="btn btn-primary h-10 px-4 text-sm rounded-lg gap-2" onclick="openModalSiswa(false)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Siswa
        </button>
    </div>

    <!-- Toolbar: Search + Filter -->
    <div class="toolbar-row">
        <div class="relative flex-1 max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.34-4.34"/></svg>
            <input class="input pl-9" placeholder="Cari nama atau NIS..." type="text" id="searchInput" value="<?= escape($search) ?>" onkeyup="filterTable()">
        </div>
        <select class="input max-w-[180px]" id="filterKelas" onchange="filterTable()">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelasList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $filterKelas === $k['id'] ? 'selected' : '' ?>><?= escape($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Table -->
    <div class="card p-0">
        <div class="table-container border-0 rounded-none">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>NIS</th>
                        <th>Nama</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th class="w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="siswaTableBody">
                    <?php if (count($siswaList) === 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data siswa</td></tr>
                    <?php endif; ?>
                    <?php $i = 1; foreach ($siswaList as $s): ?>
                    <tr class="animate-in" style="animation-delay: <?= min(($i-1)*30, 300) ?>ms;">
                        <td class="text-muted text-center"><?= $i ?></td>
                        <td class="font-mono text-xs font-medium"><?= escape($s['nis']) ?></td>
                        <td class="font-semibold"><?= escape($s['nama']) ?></td>
                        <td class="text-text-secondary"><?= escape($s['nama_kelas']) ?></td>
                        <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="text-center">
                            <div class="dot-menu">
                                <button class="dot-menu-trigger" onclick="toggleDotMenu(this, 'menuSiswa<?= $s['id'] ?>')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                </button>
                                <div class="dot-menu-items" id="menuSiswa<?= $s['id'] ?>">
                                    <button class="dot-menu-item" onclick="openModalSiswa(<?= jsonForHtml($s) ?>); closeAllDotMenus();">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Edit
                                    </button>
                                    <?php if ($s['is_active']): ?>
                                    <form method="POST" onsubmit="return confirm('Yakin nonaktifkan siswa ini? Riwayat presensi tetap tersimpan.')" style="margin:0;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="dot-menu-item text-error">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            Nonaktifkan
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="margin:0;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="id" value="<?= escape($s['id']) ?>">
                                        <button type="submit" class="dot-menu-item" style="color:var(--color-success);">Aktifkan kembali</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalSiswa">
    <div class="modal-content" style="max-height:90vh;display:flex;flex-direction:column;">
        <form method="POST" style="display:flex;flex-direction:column;min-height:0;">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 id="modalTitle">Tambah Siswa</h5>
                <button type="button" class="modal-close" onclick="closeModal('modalSiswa')">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y:auto;flex:1;min-height:0;">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="form-row">
                    <div>
                        <label class="label">NIS <span class="required">*</span></label>
                        <input type="text" class="input" name="nis" id="formNis" required>
                    </div>
                    <div>
                        <label class="label">Kelas <span class="required">*</span></label>
                        <select class="input" name="kelas_id" id="formKelas" required>
                            <option value="">Pilih...</option>
                            <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= escape($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="input" name="nama" id="formNama" required>
                </div>
                <div class="form-row">
                    <div>
                        <label class="label">Jenis Kelamin</label>
                        <select class="input" name="jenis_kelamin" id="formJk" required>
                            <option value="LAKI_LAKI">Laki-Laki</option>
                            <option value="PEREMPUAN">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tempat Lahir</label>
                        <input type="text" class="input" name="tempat_lahir" id="formTempat">
                    </div>
                    <div>
                        <label class="label">Tanggal Lahir</label>
                        <input type="date" class="input" name="tanggal_lahir" id="formTgl">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="label">Alamat</label>
                    <textarea class="input" name="alamat" id="formAlamat" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalSiswa')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalSiswa(data) {
    var modal = document.getElementById('modalSiswa');
    document.getElementById('modalTitle').innerText = data ? 'Edit Siswa' : 'Tambah Siswa';
    document.getElementById('formAction').value = data ? 'edit' : 'add';
    document.getElementById('formId').value = data ? data.id : '';
    document.getElementById('formNis').value = data ? data.nis : '';
    document.getElementById('formNama').value = data ? data.nama : '';
    document.getElementById('formKelas').value = data ? data.kelas_id : '';
    document.getElementById('formJk').value = data ? data.jenis_kelamin : 'LAKI_LAKI';
    document.getElementById('formTempat').value = data ? (data.tempat_lahir || '') : '';
    document.getElementById('formTgl').value = data ? (data.tanggal_lahir || '') : '';
    document.getElementById('formAlamat').value = data ? (data.alamat || '') : '';
    modal.classList.add('show');
}
function closeAllDotMenus() {
    document.querySelectorAll('.dot-menu-items.show').forEach(function(m) {
        m.classList.remove('show');
        m.style.position = '';
        m.style.top = '';
        m.style.left = '';
        m.style.right = '';
        m.style.zIndex = '';
        if (m.dataset.originalParent) {
            document.getElementById(m.dataset.originalParent).appendChild(m);
            delete m.dataset.originalParent;
        }
    });
}
function filterTable() {
    var search = document.getElementById('searchInput').value;
    var kelas = document.getElementById('filterKelas').value;
    var url = new URL(window.location.href);
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    if (kelas) url.searchParams.set('kelas_id', kelas);
    else url.searchParams.delete('kelas_id');
    window.location.href = url.toString();
}
// Enter key triggers search
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') filterTable();
});
</script>

<?php include '../includes/footer.php'; ?>
