<?php
require_once __DIR__ . '/includes/functions.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$items = [];
$total = 0;

if ($q !== '') {
    $like = '%' . $q . '%';
    $countStmt = db()->prepare("SELECT COUNT(*) FROM news WHERE status = 'published' AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)");
    $countStmt->execute([$like, $like, $like]);
    $total = (int)$countStmt->fetchColumn();
    $p = paginate($total, NEWS_PER_PAGE, $page);

    $stmt = db()->prepare("
        SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
               c.slug AS category_slug, c.name AS category_name
        FROM news n JOIN categories c ON c.id = n.category_id
        WHERE n.status = 'published' AND (n.title LIKE ? OR n.summary LIKE ? OR n.content LIKE ?)
        ORDER BY n.published_at DESC LIMIT " . (int)NEWS_PER_PAGE . " OFFSET " . (int)$p['offset']
    );
    $stmt->execute([$like, $like, $like]);
    $items = $stmt->fetchAll();
} else {
    $p = ['totalPages' => 1, 'currentPage' => 1];
}

$__pageTitle = 'Arama: ' . $q . ' - ' . get_setting('site_name');
require __DIR__ . '/includes/header.php';
?>
<main class="container listing-page">

<h1 class="section-title">"<?= e($q) ?>" için arama sonuçları (<?= $total ?>)</h1>

<div class="news-grid">
    <?php foreach ($items as $n): ?>
        <?= news_card_html($n) ?>
    <?php endforeach; ?>
</div>
<?php if ($q !== '' && !$items): ?><p>Aramanızla eşleşen haber bulunamadı.</p><?php endif; ?>

<?php if (($p['totalPages'] ?? 1) > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
        <?php if ($i === $p['currentPage']): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
