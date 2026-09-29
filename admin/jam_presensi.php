<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if ($_POST['action'] === 'save') {
        $jamMasuk = postString('jam_masuk', 8);
        $jamPulang = postString('jam_pulang', 8) ?: null;
        $toleransi = filter_var($_POST['toleransi_menit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1440]]);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jamMasuk)
            || ($jamPulang !== null && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jamPulang)) || $toleransi === false) {
            abortRequest('Pengaturan jam presensi tidak valid.');
        }
        $stmt = $pdo->prepare("UPDATE jam_presensi SET jam_masuk = ?, toleransi_menit = ?, jam_pulang = ? WHERE id = ?");
        $stmt->execute([$jamMasuk, $toleransi, $jamPulang, postString('id', 36)]);
        logAudit($pdo, 'UPDATE_JAM_PRESENSI', 'Mengubah pengaturan jam presensi');
    } elseif ($_POST['action'] === 'toggle') {
        $pdo->prepare("UPDATE jam_presensi SET is_active = CASE WHEN id = ? THEN 1 ELSE 0 END")->execute([postString('id', 36)]);
        logAudit($pdo, 'AKTIFKAN_JAM_PRESENSI', 'Mengaktifkan pengaturan jam presensi');
    }
    redirect(appUrl('admin/jam_presensi.php'));
}

$jamList = $pdo->query("SELECT * FROM jam_presensi ORDER BY created_at DESC")->fetchAll();
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Jam Presensi</h1>
        <p class="page-desc"><?= count($jamList) ?> pengaturan jam presensi</p>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Jam Masuk</th>
                    <th>Toleransi (menit)</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jamList as $j): ?>
                <tr class="animate-in" style="animation-delay: 0ms;">
                    <td class="font-semibold"><?= escape($j['jam_masuk']) ?></td>
                    <td><?= (int)$j['toleransi_menit'] ?> menit</td>
                    <td><?= escape($j['jam_pulang'] ?? '-') ?></td>
                    <td>
                        <?php if ($j['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <button class="btn btn-ghost btn-icon btn-sm" onclick="editJam(<?= jsonForHtml($j) ?>)" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <?php if (!$j['is_active']): ?>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                <button class="btn btn-ghost btn-icon btn-sm" title="Aktifkan" style="color:var(--color-success);">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalEdit">
    <div class="modal-content">
        <form method="POST">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5>Edit Jam Presensi</h5>
                <button type="button" class="modal-close" onclick="closeModal('modalEdit')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="formId">
                <div class="form-row">
                    <div>
                        <label class="label">Jam Masuk <span class="required">*</span></label>
                        <input type="time" class="input" name="jam_masuk" id="formMasuk" required>
                    </div>
                    <div>
                        <label class="label">Toleransi (menit) <span class="required">*</span></label>
                        <input type="number" class="input" name="toleransi_menit" id="formToleransi" min="0" max="1440" required>
                    </div>
                    <div>
                        <label class="label">Jam Pulang</label>
                        <input type="time" class="input" name="jam_pulang" id="formPulang">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editJam(d) {
    document.getElementById('formId').value = d.id;
    document.getElementById('formMasuk').value = d.jam_masuk;
    document.getElementById('formToleransi').value = d.toleransi_menit;
    document.getElementById('formPulang').value = d.jam_pulang || '';
    openModal('modalEdit');
}
</script>

<?php include '../includes/footer.php'; ?>
