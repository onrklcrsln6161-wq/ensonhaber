<?php
// Only published stories from active categories appear in the discovery modules.
$discoverCategories = db()->query("SELECT c.id, c.name, c.slug, COUNT(n.id) AS news_count
    FROM categories c LEFT JOIN news n ON n.category_id=c.id AND n.status='published'
    WHERE c.is_active=1 GROUP BY c.id,c.name,c.slug,c.sort_order ORDER BY c.sort_order,c.id")->fetchAll();
$categoryNames = ['gundem'=>'Gündem','dunya'=>'Dünya','saglik'=>'Sağlık','yasam'=>'Yaşam'];
$categoryIcons = ['gundem'=>'bi-newspaper','dunya'=>'bi-globe2','ekonomi'=>'bi-graph-up','spor'=>'bi-trophy','teknoloji'=>'bi-cpu','magazin'=>'bi-stars','saglik'=>'bi-heart-pulse','yasam'=>'bi-sun'];
$activeCategoryIds = array_map('intval', array_column($discoverCategories, 'id'));
?>
<section class="widget category-directory" aria-labelledby="category-directory-title">
    <h2 id="category-directory-title"><i class="bi bi-grid" aria-hidden="true"></i> Haber kategorileri</h2>
    <p class="discovery-intro">İlgi alanınızı seçin, haberleri keşfedin.</p>
    <nav class="category-shortcuts" aria-label="Tüm haber kategorileri">
        <?php foreach ($discoverCategories as $cat): ?>
        <a href="<?= e(category_url($cat)) ?>">
            <i class="bi <?= e($categoryIcons[$cat['slug']] ?? 'bi-newspaper') ?>" aria-hidden="true"></i>
            <span><?= e($categoryNames[$cat['slug']] ?? $cat['name']) ?><small><?= (int)$cat['news_count'] ?> haber</small></span>
            <span class="shortcut-arrow" aria-hidden="true">›</span>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php if (!$discoverCategories): ?><p class="discovery-intro">Kategoriler henüz eklenmedi.</p><?php endif; ?>
</section>

<div class="discovery-stories">
<?php foreach ($newsByCat as $discoverBlock):
    $cat = $discoverBlock['category'];
    if (!in_array((int)$cat['id'], $activeCategoryIds, true)) continue;
    $picks = array_slice($discoverBlock['items'], 0, 2);
    if (!$picks) continue;
    $categoryLabel = $categoryNames[$cat['slug']] ?? $cat['name'];
?>
<section class="widget category-picks" aria-labelledby="pick-<?= (int)$cat['id'] ?>">
    <h2 id="pick-<?= (int)$cat['id'] ?>"><a href="<?= e(category_url($cat)) ?>"><?= e($categoryLabel) ?> haberleri <span aria-hidden="true">↗</span></a></h2>
    <div class="widget-body">
        <?php foreach ($picks as $pick): ?>
        <article class="discovery-story">
            <a class="discovery-story-image" href="<?= e(news_url($pick)) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(image_url($pick['cover_image'])) ?>" alt="" width="144" height="100" loading="lazy"></a>
            <div><h3><a href="<?= e(news_url($pick)) ?>"><?= e($pick['title']) ?></a></h3><time datetime="<?= e(date(DATE_ATOM, strtotime($pick['published_at'] ?? $pick['created_at']))) ?>"><?= e(format_date($pick['published_at'] ?? $pick['created_at'], 'd.m.Y H:i')) ?></time></div>
        </article>
        <?php endforeach; ?>
        <a class="category-more" href="<?= e(category_url($cat)) ?>">Tüm <?= e($categoryLabel) ?> haberleri <span aria-hidden="true">→</span></a>
    </div>
</section>
<?php endforeach; ?>
</div>

<?php if ($__weather): ?>
<section class="widget discovery-weather" aria-labelledby="discovery-weather-title">
    <h2 id="discovery-weather-title">Hava durumu</h2>
    <div class="weather-summary"><i class="bi <?= e(weather_icon($__weather['weathercode'], $__weather['is_day'])) ?>" aria-hidden="true"></i><div><strong><?= e($__weather['city']) ?> · <?= (int)$__weather['temperature'] ?>°C</strong><span><?= e(weather_label($__weather['weathercode'])) ?></span></div></div>
    <p class="discovery-intro">Rüzgâr: <?= (int)$__weather['windspeed'] ?> km/sa<br>Kaynak: Open-Meteo · Son alınan kayıt<?php if ($weatherTime = get_setting('weather_cache_time')): ?><br><?= e(format_date($weatherTime)) ?><?php endif; ?></p>
</section>
<?php endif; ?>
<section class="widget discovery-follow" aria-labelledby="follow-title">
    <h2 id="follow-title"><i class="bi bi-rss" aria-hidden="true"></i> Haberleri takip edin</h2>
    <p class="discovery-intro">Yeni yayınlanan haberleri RSS okuyucunuzdan takip edebilirsiniz.</p>
    <a class="category-more" href="<?= e(BASE_URL) ?>/rss.php">RSS haber akışı <span aria-hidden="true">→</span></a>
    <a class="category-more" href="<?= e(BASE_URL) ?>/sitemap.php">Site haritası <span aria-hidden="true">→</span></a>
</section>
