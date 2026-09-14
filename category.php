<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1');
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    $__pageTitle = 'Kategori Bulunamadı - ' . get_setting('site_name');
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/not-found.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));

$countStmt = db()->prepare("SELECT COUNT(*) FROM news WHERE status = 'published' AND category_id = ?");
$countStmt->execute([$category['id']]);
$total = (int)$countStmt->fetchColumn();
$p = paginate($total, NEWS_PER_PAGE, $page);

$stmt = db()->prepare("
    SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
           c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' AND n.category_id = ?
    ORDER BY n.published_at DESC LIMIT " . (int)NEWS_PER_PAGE . " OFFSET " . (int)$p['offset']
);
$stmt->execute([$category['id']]);
$items = $stmt->fetchAll();

$__pageTitle = $category['name'] . ' Haberleri - ' . get_setting('site_name');
require __DIR__ . '/includes/header.php';
?>
<main class="container listing-page">

<div class="breadcrumb" style="margin-top:16px;">
    <a href="<?= e(BASE_URL) ?>/index.php">Ana Sayfa</a> / <?= e($category['name']) ?>
</div>
<h1 class="section-title"><?= e($category['name']) ?></h1>

<div class="news-grid">
    <?php foreach ($items as $n): ?>
        <?= news_card_html($n) ?>
    <?php endforeach; ?>
</div>
<?php if (!$items): ?><p>Bu kategoride henüz haber yok.</p><?php endif; ?>

<?php if ($p['totalPages'] > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
        <?php if ($i === $p['currentPage']): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?slug=<?= e($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
