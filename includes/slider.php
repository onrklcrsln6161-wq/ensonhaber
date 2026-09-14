<?php
$__slides = db()->query("
    SELECT n.id, n.title, n.slug, n.cover_image, c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' AND n.is_slider = 1
    ORDER BY n.slider_order ASC, n.published_at DESC LIMIT 8
")->fetchAll();

if (!$__slides) {
    // Slider bos ise en yeni haberlerle doldur
    $__slides = db()->query("
        SELECT n.id, n.title, n.slug, n.cover_image, c.slug AS category_slug, c.name AS category_name
        FROM news n JOIN categories c ON c.id = n.category_id
        WHERE n.status = 'published'
        ORDER BY n.published_at DESC LIMIT 5
    ")->fetchAll();
}
?>
<?php if ($__slides): ?>
<div class="hero-slider" id="heroSlider">
    <div class="slider-track">
        <?php foreach ($__slides as $i => $s): ?>
            <div class="slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                <a href="<?= e(news_url($s)) ?>">
                    <img src="<?= e(image_url($s['cover_image'])) ?>" alt="<?= e($s['title']) ?>">
                    <div class="slide-caption">
                        <span class="slide-cat"><?= e($s['category_name']) ?></span>
                        <h2 class="slide-title"><?= e($s['title']) ?></h2>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if (count($__slides) > 1): ?>
            <button class="slider-arrow prev" type="button" aria-label="Önceki"><i class="bi bi-chevron-left"></i></button>
            <button class="slider-arrow next" type="button" aria-label="Sonraki"><i class="bi bi-chevron-right"></i></button>
            <div class="slider-dots">
                <?php foreach ($__slides as $i => $s): ?>
                    <button type="button" data-index="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slayt <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if (count($__slides) > 1): ?>
<div class="slider-thumbs">
    <?php foreach ($__slides as $i => $s): ?>
        <div class="thumb <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
            <img src="<?= e(image_url($s['cover_image'])) ?>" alt="<?= e($s['title']) ?>">
            <span><?= e($s['title']) ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
