<?php
require_once __DIR__ . '/../includes/weather.php';

$__adminTitle = 'Kontrol Paneli';
require __DIR__ . '/includes/admin_header.php';

$stats = [
    'total_news' => (int)db()->query("SELECT COUNT(*) FROM news")->fetchColumn(),
    'published' => (int)db()->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
    'draft' => (int)db()->query("SELECT COUNT(*) FROM news WHERE status='draft'")->fetchColumn(),
    'pending_comments' => (int)db()->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(),
    'total_views' => (int)db()->query("SELECT COALESCE(SUM(views),0) FROM news")->fetchColumn(),
];

$recentNews = db()->query("
    SELECT n.id, n.title, n.slug, n.status, n.views, n.created_at, c.name AS category_name, a.full_name AS author_name
    FROM news n JOIN categories c ON c.id = n.category_id JOIN admins a ON a.id = n.author_id
    ORDER BY n.created_at DESC LIMIT 8
")->fetchAll();

$recentComments = db()->query("
    SELECT cm.*, n.title AS news_title, n.slug AS news_slug
    FROM comments cm JOIN news n ON n.id = cm.news_id
    WHERE cm.status = 'pending' ORDER BY cm.created_at DESC LIMIT 5
")->fetchAll();

$categoryDist = db()->query("
    SELECT c.name, COUNT(n.id) AS total
    FROM categories c LEFT JOIN news n ON n.category_id = c.id
    GROUP BY c.id, c.name HAVING total > 0 ORDER BY total DESC
")->fetchAll();

$topRead = db()->query("
    SELECT title, views FROM news WHERE status = 'published' ORDER BY views DESC LIMIT 5
")->fetchAll();

$currentWeather = get_weather();
$lastMarketUpdate = get_setting('market_last_auto_update', '');
?>

<h3 class="mb-4">Merhaba, <?= e($__admin['full_name']) ?> 👋</h3>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_news'] ?></div>
            <div class="stat-label">Toplam Haber</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#1a9f4d;">
            <div class="stat-value"><?= $stats['published'] ?></div>
            <div class="stat-label">Yayında</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#f0ad4e;">
            <div class="stat-value"><?= $stats['draft'] ?></div>
            <div class="stat-label">Taslak</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#0d6efd;">
            <div class="stat-value"><?= number_format($stats['total_views'], 0, ',', '.') ?></div>
            <div class="stat-label">Toplam Görüntülenme</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-1">
    <div class="col-md-6">
        <div class="card-panel d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <?php if ($currentWeather): ?>
                    <div style="font-size:32px;color:var(--admin-accent);"><i class="bi <?= e(weather_icon($currentWeather['weathercode'], $currentWeather['is_day'])) ?>"></i></div>
                    <div>
                        <div class="fw-bold"><?= e($currentWeather['city']) ?> — <?= (int)$currentWeather['temperature'] ?>°C</div>
                        <div class="text-muted small"><?= e(weather_label($currentWeather['weathercode'])) ?></div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Hava durumu verisi yok.</div>
                <?php endif; ?>
            </div>
            <a href="live_data.php" class="btn btn-sm btn-outline-secondary">Yönet</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-panel d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-bold"><i class="bi bi-graph-up-arrow text-danger"></i> Canlı Piyasa Verisi</div>
                <div class="text-muted small"><?= $lastMarketUpdate ? 'Son güncelleme: ' . e(format_date($lastMarketUpdate)) : 'Henüz otomatik güncelleme yapılmadı' ?></div>
            </div>
            <a href="live_data.php" class="btn btn-sm btn-outline-secondary">Yönet</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-1">
    <div class="col-lg-6">
        <div class="card-panel">
            <h6 class="mb-3">Kategori Bazında Haber Dağılımı</h6>
            <?php if ($categoryDist): ?>
                <canvas id="categoryChart" height="180"></canvas>
            <?php else: ?>
                <p class="text-muted mb-0">Henüz veri yok.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel">
            <h6 class="mb-3">En Çok Okunan 5 Haber</h6>
            <?php if ($topRead): ?>
                <canvas id="topReadChart" height="180"></canvas>
            <?php else: ?>
                <p class="text-muted mb-0">Henüz veri yok.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Son Haberler</h5>
                <a href="news.php" class="btn btn-sm btn-outline-danger">Tümünü Gör</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Başlık</th><th>Kategori</th><th>Durum</th><th>Görüntülenme</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentNews as $n): ?>
                        <tr>
                            <td><?= e(excerpt($n['title'], 50)) ?></td>
                            <td><?= e($n['category_name']) ?></td>
                            <td>
                                <?php $badge = ['published' => 'success', 'draft' => 'secondary', 'pending' => 'warning'][$n['status']]; ?>
                                <span class="badge text-bg-<?= $badge ?>"><?= e($n['status']) ?></span>
                            </td>
                            <td><?= (int)$n['views'] ?></td>
                            <td><a href="news_form.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary">Düzenle</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentNews): ?><tr><td colspan="5" class="text-center text-muted">Henüz haber yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Onay Bekleyen Yorumlar</h5>
                <span class="badge text-bg-danger"><?= $stats['pending_comments'] ?></span>
            </div>
            <?php foreach ($recentComments as $c): ?>
                <div class="border-bottom py-2">
                    <strong><?= e($c['name']) ?></strong>
                    <p class="mb-1 small text-muted"><?= e(excerpt($c['comment'], 80)) ?></p>
                    <a href="comments.php" class="small">"<?= e(excerpt($c['news_title'], 30)) ?>" haberinde</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$recentComments): ?><p class="text-muted mb-0">Bekleyen yorum yok.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($categoryDist || $topRead): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
<script>
<?php if ($categoryDist): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($categoryDist, 'name'), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($categoryDist, 'total'))) ?>,
            backgroundColor: ['#e30613','#1a1a2e','#1a9f4d','#f0ad4e','#0d6efd','#6f42c1','#20c997','#fd7e14','#6c757d']
        }]
    },
    options: { plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } } }
});
<?php endif; ?>
<?php if ($topRead): ?>
new Chart(document.getElementById('topReadChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($t) => excerpt($t, 28), array_column($topRead, 'title')), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Görüntülenme',
            data: <?= json_encode(array_map('intval', array_column($topRead, 'views'))) ?>,
            backgroundColor: '#e30613'
        }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
