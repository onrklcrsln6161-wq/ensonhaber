<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$categories = db()->query('SELECT slug FROM categories WHERE is_active = 1')->fetchAll();
$news = db()->query("SELECT slug, updated_at FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 5000")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= e(BASE_URL) ?>/index.php</loc>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach ($categories as $c): ?>
    <url>
        <loc><?= e(BASE_URL) ?>/category.php?slug=<?= urlencode($c['slug']) ?></loc>
        <changefreq>hourly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($news as $n): ?>
    <url>
        <loc><?= e(BASE_URL) ?>/news.php?slug=<?= urlencode($n['slug']) ?></loc>
        <lastmod><?= e(date('c', strtotime($n['updated_at']))) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach(publication_pages() as $slug=>$label): if(trim(get_setting('page_'.$slug))==='') continue; ?>
    <url><loc><?= e(BASE_URL.'/page.php?slug='.$slug) ?></loc></url>
    <?php endforeach; ?>
</urlset>
