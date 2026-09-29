<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $nama = postString('nama', 100);
        $wali_kelas_id = postString('wali_kelas_id', 36) ?: null;
        if ($nama === '') abortRequest('Nama kelas wajib diisi.');
        $duplicate = $pdo->prepare('SELECT id FROM kelas WHERE nama = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$nama, postString('id', 36)]);
        if ($duplicate->fetchColumn()) abortRequest('Nama kelas sudah digunakan.');
        if ($wali_kelas_id) {
            $guru = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'GURU' AND is_active = 1");
            $guru->execute([$wali_kelas_id]);
            if (!$guru->fetchColumn()) abortRequest('Wali kelas tidak valid.');
            $kelasLain = $pdo->prepare('SELECT id FROM kelas WHERE wali_kelas_id = ? AND id <> ? LIMIT 1');
            $kelasLain->execute([$wali_kelas_id, postString('id', 36)]);
            if ($kelasLain->fetchColumn()) abortRequest('Guru tersebut sudah menjadi wali kelas lain.');
        }

        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO kelas (id, nama, wali_kelas_id) VALUES (UUID(), ?, ?)");
            $stmt->execute([$nama, $wali_kelas_id]);
            logAudit($pdo, 'CREATE_KELAS', "Menambah kelas $nama");
        } else {
            $id = postString('id', 36);
            $stmt = $pdo->prepare("UPDATE kelas SET nama = ?, wali_kelas_id = ? WHERE id = ?");
            $stmt->execute([$nama, $wali_kelas_id, $id]);
            logAudit($pdo, 'UPDATE_KELAS', "Mengubah kelas $nama");
        }
        redirect(appUrl('admin/kelas.php'));
    }
    if ($_POST['action'] === 'delete') {
        $id = postString('id', 36);
        $count = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE kelas_id = ?');
        $count->execute([$id]);
        if ((int)$count->fetchColumn() > 0) abortRequest('Kelas yang masih memiliki siswa tidak dapat dihapus.');
        $pdo->prepare("DELETE FROM kelas WHERE id = ?")->execute([$id]);
        logAudit($pdo, 'DELETE_KELAS', 'Menghapus kelas');
        redirect(appUrl('admin/kelas.php'));
    }
}

$kelasList = $pdo->query("SELECT k.*, k.nama as nama, k.wali_kelas_id, u.nama as wali_kelas_nama, (SELECT COUNT(*) FROM siswa WHERE kelas_id = k.id) as jumlah_siswa FROM kelas k LEFT JOIN users u ON k.wali_kelas_id = u.id ORDER BY k.nama")->fetchAll();
$guruList = $pdo->query("SELECT id, nama FROM users WHERE role = 'GURU' AND is_active = 1 ORDER BY nama")->fetchAll();
$totalKelas = count($kelasList);
include '../includes/header.php';
?>

<div class="space-y-5">
    <div class="page-header">
        <div>
            <h1 class="page-title">Data Kelas</h1>
            <p class="page-desc"><?= $totalKelas ?> kelas terdaftar</p>
        </div>
        <button class="btn btn-primary h-10 px-4 text-sm rounded-lg gap-2" onclick="openKelasModal(false)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Kelas
        </button>
    </div>

    <!-- Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php $delay = 0; foreach ($kelasList as $k): ?>
        <div class="card animate-in" style="animation-delay: <?= $delay ?>ms;">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-primary-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>
                    </div>
                    <h3 class="font-bold text-sm"><?= escape($k['nama']) ?></h3>
                </div>
                <div class="flex gap-0.5">
                    <div class="dot-menu">
                        <button class="dot-menu-trigger" onclick="toggleDotMenu(this, 'menuKelas<?= $k['id'] ?>')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </button>
                        <div class="dot-menu-items" id="menuKelas<?= $k['id'] ?>">
                            <button class="dot-menu-item" onclick="openKelasModal(<?= jsonForHtml(['id'=>$k['id'],'nama'=>$k['nama'],'wali_kelas_id'=>$k['wali_kelas_id']]) ?>); closeAllDotMenus();">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </button>
                            <form method="POST" onsubmit="return confirm('Yakin hapus kelas ini?')" style="margin:0;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="dot-menu-item text-error">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-1 text-xs text-text-secondary">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
                    <span><?= number_format($k['jumlah_siswa']) ?> siswa</span>
                </div>
                <p>Wali Kelas: <?= escape($k['wali_kelas_nama'] ?? '-') ?></p>
            </div>
        </div>
        <?php $delay += 50; endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalKelas">
    <div class="modal-content">
        <form method="POST">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 id="modalTitle">Tambah Kelas</h5>
                <button type="button" class="modal-close" onclick="closeModal('modalKelas')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="mb-3">
                    <label class="label">Nama Kelas <span class="required">*</span></label>
                    <input type="text" class="input" name="nama" id="formNama" required>
                </div>
                <div class="mb-3">
                    <label class="label">Wali Kelas</label>
                    <select class="input" name="wali_kelas_id" id="formWali">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= escape($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalKelas')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openKelasModal(data) {
    var modal = document.getElementById('modalKelas');
    document.getElementById('modalTitle').innerText = data ? 'Edit Kelas' : 'Tambah Kelas';
    document.getElementById('formAction').value = data ? 'edit' : 'add';
    document.getElementById('formId').value = data ? data.id : '';
    document.getElementById('formNama').value = data ? data.nama : '';
    document.getElementById('formWali').value = data ? (data.wali_kelas_id || '') : '';
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
</script>

<?php include '../includes/footer.php'; ?>
