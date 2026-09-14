<?php
$__adminTitle = 'Haberler';
require __DIR__ . '/includes/admin_header.php';

$statusFilter = $_GET['status'] ?? '';
$catFilter = (int)($_GET['category'] ?? 0);
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ['NOT EXISTS (SELECT 1 FROM news_trash nt WHERE nt.news_id=n.id)'];
$params = [];
if ($statusFilter && in_array($statusFilter, ['draft', 'published', 'pending'], true)) {
    $where[] = 'n.status = ?';
    $params[] = $statusFilter;
}
if ($catFilter) {
    $where[] = 'n.category_id = ?';
    $params[] = $catFilter;
}
if ($q !== '') {
    $where[] = 'n.title LIKE ?';
    $params[] = '%' . $q . '%';
}
// Yazar rolu sadece kendi haberlerini gorur
if ($__admin['role'] === 'author') {
    $where[] = 'n.author_id = ?';
    $params[] = $__admin['id'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM news n {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$p = paginate($total, $perPage, $page);

$stmt = db()->prepare("
    SELECT n.*, c.name AS category_name, a.full_name AS author_name
    FROM news n JOIN categories c ON c.id = n.category_id JOIN admins a ON a.id = n.author_id
    {$whereSql}
    ORDER BY n.created_at DESC LIMIT {$perPage} OFFSET {$p['offset']}
");
$stmt->execute($params);
$list = $stmt->fetchAll();

$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Haberler</h3>
    <a href="news_form.php" class="btn btn-danger"><i class="bi bi-plus-circle"></i> Yeni Haber</a>
</div>

<div class="card-panel">
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" placeholder="Başlıkta ara..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Tüm Durumlar</option>
                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Yayında</option>
                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Taslak</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Onay Bekliyor</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filtrele</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th></th><th>Başlık</th><th>Kategori</th><th>Yazar</th><th>Durum</th><th>Slider</th><th>Görüntülenme</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($list as $n): ?>
                <tr>
                    <td><img class="table-thumb" src="<?= e(image_url($n['cover_image'])) ?>" alt=""></td>
                    <td><?= e(excerpt($n['title'], 55)) ?></td>
                    <td><?= e($n['category_name']) ?></td>
                    <td><?= e($n['author_name']) ?></td>
                    <td>
                        <?php $badge = ['published' => 'success', 'draft' => 'secondary', 'pending' => 'warning'][$n['status']]; ?>
                        <span class="badge text-bg-<?= $badge ?>"><?= e($n['status']) ?></span>
                    </td>
                    <td><?= $n['is_slider'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?></td>
                    <td><?= (int)$n['views'] ?></td>
                    <td><small><?= e(format_date($n['created_at'])) ?></small></td>
                    <td class="text-nowrap">
                        <?php if (can_edit_news($__admin, $n)): ?><a href="news_form.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a><?php endif; ?>
                        <a href="<?= e(BASE_URL) ?>/news.php?slug=<?= e($n['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <?php if ($__admin['role'] === 'super_admin'): ?><form action="news_delete.php" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Bu haberi silmek istediğinize emin misiniz?"><i class="bi bi-trash"></i></button>
                        </form><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$list): ?><tr><td colspan="9" class="text-center text-muted py-4">Haber bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($p['totalPages'] > 1): ?>
    <nav><ul class="pagination">
        <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $p['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&status=<?= e($statusFilter) ?>&category=<?= $catFilter ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
