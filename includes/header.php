<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/weather.php';

$__categories = db()->query('SELECT id, name, slug FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order')->fetchAll();
$__ticker = db()->query('SELECT * FROM market_ticker ORDER BY sort_order')->fetchAll();
$__weather = get_weather();
$__marketLive = false; // Mixed manual/API values must not imply a real-time feed.
$__breaking = db()->query("SELECT id, title, slug FROM news WHERE status = 'published' AND is_breaking = 1 ORDER BY published_at DESC LIMIT 8")->fetchAll();
$__siteName = get_setting('site_name', 'Haber Portalim');
$__logo = get_setting('logo_path', '');
$__pageTitle = $__pageTitle ?? $__siteName;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($__pageTitle) ?></title>
<meta name="description" content="<?= e($__pageDescription ?? get_setting('meta_description')) ?>">
<?php
// REQUEST_URI'yi degil, BASE_URL + script adini kullaniyoruz: BASE_URL alt klasorde olabilir
// ve REQUEST_URI zaten o klasoru icerir, ikisini birlestirmek yolu ikiye katlardi.
$__canonicalUrl = $__canonicalUrl ?? (
    BASE_URL . '/' . basename($_SERVER['SCRIPT_NAME']) .
    (http_build_query(array_intersect_key($_GET, array_flip(['slug', 'page', 'q']))) ? '?' . http_build_query(array_intersect_key($_GET, array_flip(['slug', 'page', 'q']))) : '')
);
$__shareImage = $__pageImage ?? image_url($__logo ?: null);
?>
<link rel="canonical" href="<?= e($__canonicalUrl) ?>">
<meta property="og:type" content="<?= e($__ogType ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($__siteName) ?>">
<meta property="og:title" content="<?= e($__pageTitle) ?>">
<meta property="og:description" content="<?= e($__pageDescription ?? get_setting('meta_description')) ?>">
<meta property="og:image" content="<?= e($__shareImage) ?>">
<meta property="og:url" content="<?= e($__canonicalUrl) ?>">
<meta property="og:locale" content="tr_TR">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($__pageTitle) ?>">
<meta name="twitter:description" content="<?= e($__pageDescription ?? get_setting('meta_description')) ?>">
<meta name="twitter:image" content="<?= e($__shareImage) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e($__siteName) ?> RSS" href="<?= e(BASE_URL) ?>/rss.php">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body<?= ($__ogType ?? '') === 'article' ? ' class="reading-page"' : '' ?>>

<div class="topbar">
    <div class="container">
        <span class="date"><?= e(turkish_date()) ?></span>
        <?php if ($__weather): ?>
            <span class="weather-widget" title="<?= e(weather_label($__weather['weathercode'])) ?>">
                <i class="bi <?= e(weather_icon($__weather['weathercode'], $__weather['is_day'])) ?>"></i>
                <?= e($__weather['city']) ?> <?= (int)$__weather['temperature'] ?>°C
            </span>
        <?php endif; ?>
        <span class="social">
            <?php foreach (social_accounts() as $account): ?><a href="<?= e($account['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($account['name']) ?>"><i class="bi bi-<?= e($account['icon']) ?>"></i></a><?php endforeach; ?>
        </span>
    </div>
</div>

<div class="market-ticker">
    <div class="container">
        <?php if ($__marketLive): ?><span class="live-badge" title="Kurlar otomatik güncelleniyor">● CANLI</span><?php endif; ?>
        <div class="track">
            <?php for ($i = 0; $i < (($__ogType ?? '') === 'article' ? 1 : 2); $i++): // iki kez basip kesintisiz kaydirma efekti ?>
                <?php foreach ($__ticker as $t): ?>
                    <div class="market-item">
                        <span class="label"><?= e($t['label']) ?></span>
                        <span class="value"><?= e($t['value']) ?></span>
                        <span class="change <?= $t['change_percent'] >= 0 ? 'up' : 'down' ?>">
                            <i class="bi <?= $t['change_percent'] >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill' ?>"></i> %<?= e(number_format(abs($t['change_percent']), 2, ',', '.')) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</div>

<header class="main-header">
    <div class="container">
        <a href="<?= e(BASE_URL) ?>/index.php" class="logo">
            <?php if ($__logo): ?>
                <img src="<?= e(image_url($__logo)) ?>" alt="<?= e($__siteName) ?>">
            <?php else: ?>
                <?= e($__siteName) ?>
                <span class="slogan"><?= e(get_setting('site_slogan')) ?></span>
            <?php endif; ?>
        </a>
        <form class="header-search" action="<?= e(BASE_URL) ?>/search.php" method="get">
            <input type="text" name="q" placeholder="Haber ara..." value="<?= e($_GET['q'] ?? '') ?>" required>
            <button type="submit" aria-label="Ara"><i class="bi bi-search"></i></button>
        </form>
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Menüyü aç/kapat" aria-expanded="false" aria-controls="mainNav">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<nav class="main-nav" id="mainNav">
    <div class="container">
        <ul>
            <?php foreach ($__categories as $cat): ?>
                <li><a href="<?= e(category_url($cat)) ?>" class="<?= (($_GET['slug'] ?? '') === $cat['slug']) ? 'active' : '' ?>"><?= e($cat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<?php if ($__breaking): ?>
<div class="breaking-ticker">
    <span class="badge"><i class="bi bi-broadcast"></i> SON DAKİKA</span>
    <div class="scroller">
        <div class="scroller-track">
            <?php for ($i = 0; $i < (($__ogType ?? '') === 'article' ? 1 : 2); $i++): ?>
                <?php foreach ($__breaking as $b): ?>
                    <a href="<?= e(news_url($b)) ?>"><?= e($b['title']) ?></a>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container">
<?php if ($flash = flash_get()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" style="margin-top:16px;"><?= e($flash['message']) ?></div>
<?php endif; ?>
</div>
