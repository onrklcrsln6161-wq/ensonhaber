<?php
require_once __DIR__ . '/includes/functions.php';

$__pageTitle = get_setting('site_name') . ' - ' . get_setting('site_slogan');
require __DIR__ . '/includes/header.php';

require __DIR__ . '/includes/slider.php';

$categories = db()->query('SELECT id, name, slug FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order')->fetchAll();

// Gundem (siyaset/genel gundem) sol sutunda dikey liste olarak, daha fazla haberle,
// bosluk kalmadan asagi dogru akar; diger kategoriler standart izgara (grid) ile gosterilir.
$leadCategory = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === 'gundem') { $leadCategory = $cat; break; }
}
if (!$leadCategory && $categories) {
    $leadCategory = $categories[0];
}

$leadItems = [];
if ($leadCategory) {
    $stmt = db()->prepare("
        SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.published_at, n.created_at,
               c.slug AS category_slug, c.name AS category_name
        FROM news n JOIN categories c ON c.id = n.category_id
        WHERE n.status = 'published' AND n.category_id = ?
        ORDER BY n.published_at DESC LIMIT 9
    ");
    $stmt->execute([$leadCategory['id']]);
    $leadItems = $stmt->fetchAll();
}

$newsByCat = [];
$stmt = db()->prepare("
    SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
           c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' AND n.category_id = ?
    ORDER BY n.published_at DESC LIMIT 6
");
foreach ($categories as $cat) {
    if ($leadCategory && (int)$cat['id'] === (int)$leadCategory['id']) continue;
    $stmt->execute([$cat['id']]);
    $items = $stmt->fetchAll();
    if ($items) {
        $newsByCat[$cat['id']] = ['category' => $cat, 'items' => $items];
    }
}

$mostRead = db()->query("
    SELECT n.id, n.title, n.slug, c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' ORDER BY n.views DESC LIMIT 8
")->fetchAll();

// Sag kolon: "Guncel Haberler" / "Gundem" sekmeli kutu
$sidebarRecent = db()->query("
    SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
           c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT 6
")->fetchAll();

$sidebarGundem = [];
if ($leadCategory) {
    $stmt = db()->prepare("
        SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
               c.slug AS category_slug, c.name AS category_name
        FROM news n JOIN categories c ON c.id = n.category_id
        WHERE n.status = 'published' AND n.category_id = ?
        ORDER BY n.published_at DESC LIMIT 6
    ");
    $stmt->execute([$leadCategory['id']]);
    $sidebarGundem = $stmt->fetchAll();
}

// Borsa/Doviz/Altin kutusu icin ticker'i koda gore grupla
$marketGroups = ['borsa' => [], 'doviz' => [], 'altin' => []];
$marketCodeMap = ['BIST' => 'borsa', 'USD' => 'doviz', 'EUR' => 'doviz', 'GBP' => 'doviz', 'BTC' => 'doviz', 'XAU' => 'altin', 'XAG' => 'altin'];
foreach ($__ticker as $t) {
    $group = $marketCodeMap[$t['code']] ?? 'doviz';
    $marketGroups[$group][] = $t;
}
?>

<div class="page-layout home-layout">
    <div>
        <?php if ($leadItems): ?>
            <?php $leadMain = array_shift($leadItems); ?>
            <section class="lead-section">
                <h2 class="section-title"><?= e($leadCategory['name']) ?></h2>
                <a class="lead-story" href="<?= e(news_url($leadMain)) ?>">
                    <div class="lead-story-thumb">
                        <img src="<?= e(image_url($leadMain['cover_image'])) ?>" alt="<?= e($leadMain['title']) ?>" loading="lazy">
                    </div>
                    <div class="lead-story-body">
                        <span class="cat"><?= e($leadMain['category_name']) ?></span>
                        <h3><?= e($leadMain['title']) ?></h3>
                        <?php if (!empty($leadMain['summary'])): ?><p><?= e(excerpt($leadMain['summary'], 140)) ?></p><?php endif; ?>
                        <div class="meta"><?= e(time_ago($leadMain['published_at'] ?? $leadMain['created_at'])) ?></div>
                    </div>
                </a>
                <?php if ($leadItems): ?>
                    <div class="lead-list">
                        <?php foreach ($leadItems as $n): ?>
                            <?= news_row_html($n) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php foreach ($newsByCat as $block): ?>
            <section>
                <h2 class="section-title section-title-link"><a href="<?= e(category_url($block['category'])) ?>"><?= e($block['category']['name']) ?></a><a class="view-all" href="<?= e(category_url($block['category'])) ?>">Tüm haberler <span aria-hidden="true">→</span></a></h2>
                <div class="news-grid">
                    <?php foreach ($block['items'] as $n): ?>
                        <?= news_card_html($n) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if (!$newsByCat && !$leadItems && empty($leadMain)): ?>
            <p>Henüz yayınlanmış haber bulunmuyor. Admin panelinden haber ekleyebilirsiniz.</p>
        <?php endif; ?>
    </div>

    <aside>
        <div class="widget">
            <h4>Çok Okunanlar</h4>
            <div class="widget-body">
                <?php foreach ($mostRead as $i => $n): ?>
                    <div class="most-read-item">
                        <span class="rank"><?= $i + 1 ?></span>
                        <a href="<?= e(news_url($n)) ?>"><?= e($n['title']) ?></a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$mostRead): ?><p>Henüz haber yok.</p><?php endif; ?>
            </div>
        </div>

        <div class="widget tabs-widget">
            <div class="widget-tabs">
                <button type="button" class="tab-btn active" data-tab="guncel">Güncel Haberler</button>
                <button type="button" class="tab-btn" data-tab="gundem">Gündem</button>
            </div>
            <div class="widget-body tab-panel active" data-panel="guncel">
                <?php foreach ($sidebarRecent as $n): ?>
                    <?= news_row_html($n) ?>
                <?php endforeach; ?>
                <?php if (!$sidebarRecent): ?><p>Henüz haber yok.</p><?php endif; ?>
            </div>
            <div class="widget-body tab-panel" data-panel="gundem" hidden>
                <?php foreach ($sidebarGundem as $n): ?>
                    <?= news_row_html($n) ?>
                <?php endforeach; ?>
                <?php if (!$sidebarGundem): ?><p>Bu kategoride henüz haber yok.</p><?php endif; ?>
            </div>
        </div>

        <div class="widget tabs-widget market-widget">
            <p class="market-disclosure">Son kayıtlı değerler. BIST, altın ve gümüş elle güncellenir; bu alan anlık işlem verisi değildir. API değişim yüzdesi önceki kayıtla karşılaştırılır.</p>
            <div class="widget-tabs">
                <button type="button" class="tab-btn active" data-tab="borsa">Borsa</button>
                <button type="button" class="tab-btn" data-tab="doviz">Döviz</button>
                <button type="button" class="tab-btn" data-tab="altin">Altın</button>
                <?php if ($__marketLive): ?><span class="live-dot" title="Canlı veriyle güncelleniyor"></span><?php endif; ?>
            </div>
            <?php foreach ($marketGroups as $groupKey => $groupItems): ?>
                <div class="widget-body market-widget-body tab-panel <?= $groupKey === 'borsa' ? 'active' : '' ?>" data-panel="<?= $groupKey ?>" <?= $groupKey === 'borsa' ? '' : 'hidden' ?>>
                    <?php foreach ($groupItems as $t): ?>
                        <div class="market-widget-item">
                            <span class="label"><?= e($t['label']) ?></span>
                            <span class="value"><?= e($t['value']) ?></span>
                            <span class="change <?= $t['change_percent'] >= 0 ? 'up' : 'down' ?>">
                                <i class="bi <?= $t['change_percent'] >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill' ?>"></i> %<?= e(number_format(abs($t['change_percent']), 2, ',', '.')) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$groupItems): ?><p>Veri yok.</p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php require __DIR__ . '/includes/home-discover.php'; ?>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
