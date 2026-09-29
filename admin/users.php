<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $username = postString('username', 100);
        $nama = postString('nama', 150);
        $role = postString('role', 10);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if ($username === '' || $nama === '' || !in_array($role, ['ADMIN', 'GURU'], true)) {
            abortRequest('Data pengguna tidak valid.');
        }
        $duplicate = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$username, postString('id', 36)]);
        if ($duplicate->fetchColumn()) abortRequest('Username sudah digunakan.');

        if ($_POST['action'] === 'add') {
            $plainPassword = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (strlen($plainPassword) < 8) abortRequest('Password minimal 8 karakter.');
            $password = password_hash($plainPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (id, username, password, role, nama, is_active) VALUES (UUID(), ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role, $nama, $is_active]);
            logAudit($pdo, 'CREATE_USER', "Menambah user $username");
        } else {
            $id = postString('id', 36);
            if ($id === '') abortRequest('Pengguna tidak ditemukan.', 404);
            if ($id === $_SESSION['user_id'] && (!$is_active || $role !== 'ADMIN')) {
                abortRequest('Anda tidak dapat menonaktifkan atau menurunkan peran akun sendiri.');
            }
            if (!$is_active || $role !== 'GURU') {
                $wali = $pdo->prepare('SELECT id FROM kelas WHERE wali_kelas_id = ? LIMIT 1');
                $wali->execute([$id]);
                if ($wali->fetchColumn()) abortRequest('Lepaskan pengguna dari wali kelas sebelum mengubah peran atau menonaktifkannya.');
            }
            $newPassword = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) abortRequest('Password minimal 8 karakter.');
                $password = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, nama = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $password, $role, $nama, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, nama = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $role, $nama, $is_active, $id]);
            }
            logAudit($pdo, 'UPDATE_USER', "Mengubah user $username");
        }
        redirect(appUrl('admin/users.php'));
    }
}

$userList = $pdo->query("SELECT *, nama FROM users ORDER BY created_at DESC")->fetchAll();
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Data Pengguna</h1>
        <p class="page-desc"><?= count($userList) ?> pengguna terdaftar</p>
    </div>
    <button class="btn btn-primary h-10 px-4 text-sm rounded-lg gap-2" onclick="openUserModal(false)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah User
    </button>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="w-24 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userList as $u): ?>
                <tr class="animate-in" style="animation-delay: 0ms;">
                    <td class="font-semibold"><?= escape($u['nama']) ?></td>
                    <td class="font-mono text-xs font-medium"><?= escape($u['username']) ?></td>
                    <td><span class="badge badge-info"><?= escape($u['role']) ?></span></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-error">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="flex justify-center gap-1">
                            <button class="btn btn-ghost btn-icon btn-sm" onclick='openUserModal(<?= jsonForHtml(['id'=>$u['id'],'username'=>$u['username'],'nama'=>$u['nama'],'role'=>$u['role'],'is_active'=>$u['is_active']]) ?>)' title="Edit">
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

<div class="modal-overlay" id="modalUser">
    <div class="modal-content">
        <form method="POST">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 id="modalTitle">Tambah User</h5>
                <button type="button" class="modal-close" onclick="closeModal('modalUser')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="form-row">
                    <div>
                        <label class="label">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" class="input" name="nama" id="formNama" required>
                    </div>
                    <div>
                        <label class="label">Username <span class="required">*</span></label>
                        <input type="text" class="input" name="username" id="formUsername" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label class="label">Password <span class="required" id="pwdRequired">*</span>
                            <small style="color:var(--color-text-muted);font-weight:400;" id="pwdHelp"></small>
                        </label>
                        <input type="password" class="input" name="password" id="formPassword" minlength="8" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="label">Role <span class="required">*</span></label>
                        <select class="input" name="role" id="formRole" required>
                            <option value="GURU">Guru</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="formActive" checked>
                        <span class="form-check-label">Akun Aktif</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalUser')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal(data) {
    var modal = document.getElementById('modalUser');
    document.getElementById('modalTitle').innerText = data ? 'Edit User' : 'Tambah User';
    document.getElementById('formAction').value = data ? 'edit' : 'add';
    document.getElementById('formId').value = data ? data.id : '';
    document.getElementById('formNama').value = data ? data.nama : '';
    document.getElementById('formUsername').value = data ? data.username : '';
    document.getElementById('formRole').value = data ? data.role : 'GURU';
    document.getElementById('formActive').checked = data ? data.is_active == 1 : true;
    if (data) {
        document.getElementById('formPassword').required = false;
        document.getElementById('pwdHelp').innerText = '(kosongkan jika tidak diubah)';
        document.getElementById('pwdRequired').style.display = 'none';
    } else {
        document.getElementById('formPassword').required = true;
        document.getElementById('pwdHelp').innerText = '';
        document.getElementById('pwdRequired').style.display = 'inline';
    }
    modal.classList.add('show');
}
window.closeModal = function(id) {
    document.getElementById(id).classList.remove('show');
};
</script>

<?php include '../includes/footer.php'; ?>
