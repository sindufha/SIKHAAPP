<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('ADMIN');
include '../includes/header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$total = $pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$logs = $pdo->query("SELECT a.*, u.nama FROM audit_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
$totalPages = ceil($total / $limit);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Log Aktivitas</h1>
        <p class="page-desc"><?= number_format($total) ?> aktivitas tercatat</p>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr class="animate-in" style="animation-delay: 0ms;">
                    <td class="text-muted"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
                    <td class="font-semibold"><?= escape($l['nama'] ?? 'Sistem') ?></td>
                    <td><span class="badge badge-info"><?= escape($l['aksi']) ?></span></td>
                    <td><?= escape($l['deskripsi'] ?? '-') ?></td>
                    <td class="text-muted font-mono"><?= escape($l['ip'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a class="page-link <?= $page <= 1 ? 'disabled' : '' ?>" href="?page=<?= $page - 1 ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="page-link <?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>" href="?page=<?= $page + 1 ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
