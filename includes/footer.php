<?php
$__mostRead = db()->query("
    SELECT n.id, n.title, n.slug, c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' ORDER BY n.views DESC LIMIT 5
")->fetchAll();
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h5><?= e(get_setting('site_name')) ?></h5>
                <p><?= e(get_setting('site_slogan')) ?></p>
                <?php if ($phone = get_setting('whatsapp_ihbar')): ?><p>İhbar Hattı: <?= e($phone) ?></p><?php endif; ?>
            </div>
            <div>
                <h5>Kategoriler</h5>
                <ul>
                    <?php foreach (db()->query('SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order LIMIT 6')->fetchAll() as $c): ?>
                        <li><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h5>Çok Okunanlar</h5>
                <ul>
                    <?php foreach ($__mostRead as $n): ?>
                        <li><a href="<?= e(news_url($n)) ?>"><?= e(excerpt($n['title'], 60)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h5>Bizi Takip Edin</h5>
                <ul>
                    <?php foreach(social_accounts() as $account): ?><li><a href="<?= e($account['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($account['name']) ?></a></li><?php endforeach; ?>
                    <li><a href="<?= e(BASE_URL) ?>/rss.php">RSS Haber Akışı</a></li>
                </ul>
            </div>
        </div>
        <nav class="publication-links" aria-label="Kurumsal bilgiler">
        <?php foreach(publication_pages() as $slug=>$label): if(trim(get_setting('page_'.$slug))==='') continue; ?>
        <a href="<?= e(BASE_URL) ?>/page.php?slug=<?= e($slug) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        </nav>
        <div class="footer-bottom"><?= e(get_setting('footer_text')) ?></div>
    </div>
</footer>
<script src="<?= e(BASE_URL) ?>/assets/js/main.js"></script>
</body>
</html>
