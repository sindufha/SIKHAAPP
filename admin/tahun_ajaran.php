<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $tahun = postString('tahun', 20);
        $semester = postString('semester', 10);
        $tanggal_mulai = postString('tanggal_mulai', 10);
        $tanggal_selesai = postString('tanggal_selesai', 10);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (!preg_match('/^\d{4}\/\d{4}$/', $tahun) || !in_array($semester, ['GANJIL', 'GENAP'], true)
            || !isValidDate($tanggal_mulai) || !isValidDate($tanggal_selesai) || $tanggal_mulai > $tanggal_selesai) {
            abortRequest('Data tahun ajaran tidak valid.');
        }
        $duplicate = $pdo->prepare('SELECT id FROM tahun_ajaran WHERE tahun = ? AND semester = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$tahun, $semester, postString('id', 36)]);
        if ($duplicate->fetchColumn()) abortRequest('Tahun ajaran dan semester tersebut sudah ada.');
        $pdo->beginTransaction();
        try {
            if ($is_active) $pdo->query("UPDATE tahun_ajaran SET is_active = 0");
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO tahun_ajaran (id, tahun, semester, tanggal_mulai, tanggal_selesai, is_active) VALUES (UUID(), ?, ?, ?, ?, ?)");
                $stmt->execute([$tahun, $semester, $tanggal_mulai, $tanggal_selesai, $is_active]);
            } else {
                $stmt = $pdo->prepare("UPDATE tahun_ajaran SET tahun=?, semester=?, tanggal_mulai=?, tanggal_selesai=?, is_active=? WHERE id=?");
                $stmt->execute([$tahun, $semester, $tanggal_mulai, $tanggal_selesai, $is_active, postString('id', 36)]);
            }
            logAudit($pdo, 'SIMPAN_TAHUN_AJARAN', 'Menyimpan tahun ajaran ' . $tahun . ' ' . $semester);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        redirect(appUrl('admin/tahun_ajaran.php'));
    }
}

$tahunList = $pdo->query("SELECT * FROM tahun_ajaran ORDER BY tahun DESC, semester DESC")->fetchAll();
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tahun Ajaran</h1>
        <p class="page-desc"><?= count($tahunList) ?> tahun ajaran terdaftar</p>
    </div>
    <button class="btn btn-primary h-10 px-4 text-sm rounded-lg gap-2" onclick="openTahunModal(false)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah
    </button>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Tahun Ajaran</th>
                    <th>Semester</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tahunList as $t): ?>
                <tr class="animate-in" style="animation-delay: 0ms;">
                    <td class="font-semibold"><?= escape($t['tahun']) ?></td>
                    <td><?= escape($t['semester']) ?></td>
                    <td><?= date('d/m/Y', strtotime($t['tanggal_mulai'])) ?> - <?= date('d/m/Y', strtotime($t['tanggal_selesai'])) ?></td>
                    <td>
                        <?php if ($t['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Tidak Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <button class="btn btn-ghost btn-icon btn-sm" onclick='openTahunModal(<?= jsonForHtml($t) ?>)' title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalTahun">
    <div class="modal-content">
        <form method="POST">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 id="modalTitle">Tambah Tahun Ajaran</h5>
                <button type="button" class="modal-close" onclick="closeModal('modalTahun')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="form-row">
                    <div>
                        <label class="label">Tahun (misal: 2023/2024) <span class="required">*</span></label>
                        <input type="text" class="input" name="tahun" id="formTahun" pattern="\d{4}/\d{4}" placeholder="2026/2027" required>
                    </div>
                    <div>
                        <label class="form-label">Semester <span class="required">*</span></label>
                        <select class="input" name="semester" id="formSemester" required>
                            <option value="GANJIL">Ganjil</option>
                            <option value="GENAP">Genap</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                        <input type="date" class="input" name="tanggal_mulai" id="formMulai" required>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Selesai <span class="required">*</span></label>
                        <input type="date" class="input" name="tanggal_selesai" id="formSelesai" required>
                    </div>
                </div>
                <div>
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="formActive">
                        <span class="form-check-label">Jadikan Tahun Ajaran Aktif</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalTahun')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTahunModal(data) {
    var modal = document.getElementById('modalTahun');
    document.getElementById('modalTitle').innerText = data ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran';
    document.getElementById('formAction').value = data ? 'edit' : 'add';
    document.getElementById('formId').value = data ? data.id : '';
    document.getElementById('formTahun').value = data ? data.tahun : '';
    document.getElementById('formSemester').value = data ? data.semester : 'GANJIL';
    document.getElementById('formMulai').value = data ? data.tanggal_mulai : '';
    document.getElementById('formSelesai').value = data ? data.tanggal_selesai : '';
    document.getElementById('formActive').checked = data ? data.is_active == 1 : false;
    modal.classList.add('show');
}
</script>

<?php include '../includes/footer.php'; ?>
