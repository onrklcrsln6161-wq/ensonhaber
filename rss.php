<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$siteName = get_setting('site_name');
$siteSlogan = get_setting('site_slogan');

$news = db()->query("
    SELECT n.title, n.slug, n.summary, n.published_at, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT 30
")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
    <title><?= e($siteName) ?></title>
    <link><?= e(BASE_URL) ?>/index.php</link>
    <description><?= e($siteSlogan) ?></description>
    <language>tr-TR</language>
    <lastBuildDate><?= e(date('r')) ?></lastBuildDate>
    <?php foreach ($news as $n): ?>
    <item>
        <title><?= e($n['title']) ?></title>
        <link><?= e(BASE_URL) ?>/news.php?slug=<?= urlencode($n['slug']) ?></link>
        <guid><?= e(BASE_URL) ?>/news.php?slug=<?= urlencode($n['slug']) ?></guid>
        <category><?= e($n['category_name']) ?></category>
        <pubDate><?= e(date('r', strtotime($n['published_at']))) ?></pubDate>
        <description><?= e(excerpt($n['summary'] ?? '', 200)) ?></description>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
