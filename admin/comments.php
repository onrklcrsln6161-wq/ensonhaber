<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        db()->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$id]);
        flash_set('success', 'Yorum onaylandı.');
    } elseif ($action === 'reject') {
        db()->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?")->execute([$id]);
        flash_set('success', 'Yorum reddedildi.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
        flash_set('success', 'Yorum silindi.');
    }
    redirect('comments.php?status=' . urlencode($_GET['status'] ?? ''));
}

$statusFilter = $_GET['status'] ?? 'pending';
$where = '';
$params = [];
if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $where = 'WHERE cm.status = ?';
    $params[] = $statusFilter;
}
$stmt = db()->prepare("
    SELECT cm.*, n.title AS news_title, n.slug AS news_slug
    FROM comments cm JOIN news n ON n.id = cm.news_id
    {$where} ORDER BY cm.created_at DESC LIMIT 100
");
$stmt->execute($params);
$comments = $stmt->fetchAll();

$__adminTitle = 'Yorumlar';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Yorum Moderasyonu</h3>

<div class="mb-3 btn-group">
    <a href="?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-danger' : 'btn-outline-danger' ?>">Bekleyen</a>
    <a href="?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-danger' : 'btn-outline-danger' ?>">Onaylı</a>
    <a href="?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Reddedilen</a>
    <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-danger' : 'btn-outline-danger' ?>">Tümü</a>
</div>

<div class="card-panel">
    <?php foreach ($comments as $c): ?>
        <div class="border-bottom py-3">
            <div class="d-flex justify-content-between">
                <div>
                    <strong><?= e($c['name']) ?></strong> <span class="text-muted small">(<?= e($c['email']) ?>)</span>
                    <span class="text-muted small ms-2"><?= e(time_ago($c['created_at'])) ?></span>
                </div>
                <span class="badge text-bg-<?= ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$c['status']] ?>"><?= e($c['status']) ?></span>
            </div>
            <p class="my-2"><?= nl2br(e($c['comment'])) ?></p>
            <div class="small text-muted mb-2">Haber: <a href="<?= e(BASE_URL) ?>/news.php?slug=<?= e($c['news_slug']) ?>" target="_blank"><?= e($c['news_title']) ?></a></div>
            <form method="post" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <?php if ($c['status'] !== 'approved'): ?><button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Onayla</button><?php endif; ?>
                <?php if ($c['status'] !== 'rejected'): ?><button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-secondary">Reddet</button><?php endif; ?>
                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Yorumu silmek istediğinize emin misiniz?">Sil</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (!$comments): ?><p class="text-muted mb-0 text-center py-3">Bu filtrede yorum yok.</p><?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
