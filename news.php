<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("
    SELECT n.*, c.name AS category_name, c.slug AS category_slug, a.full_name AS author_name
    FROM news n
    JOIN categories c ON c.id = n.category_id
    JOIN admins a ON a.id = n.author_id
    WHERE n.slug = ? AND n.status = 'published'
");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $__pageTitle = 'Haber Bulunamadı - ' . get_setting('site_name');
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/not-found.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Goruntulenme sayisini artir (oturum basina bir kez)
$__seenKey = 'seen_news_' . $article['id'];
if (empty($_SESSION[$__seenKey])) {
    db()->prepare('UPDATE news SET views = views + 1 WHERE id = ?')->execute([$article['id']]);
    $_SESSION[$__seenKey] = true;
}

$tagsStmt = db()->prepare('SELECT t.name, t.slug FROM tags t JOIN news_tags nt ON nt.tag_id = t.id WHERE nt.news_id = ?');
$tagsStmt->execute([$article['id']]);
$tags = $tagsStmt->fetchAll();

$galleryStmt = db()->prepare('SELECT image_path FROM news_gallery WHERE news_id = ? ORDER BY sort_order');
$galleryStmt->execute([$article['id']]);
$gallery = $galleryStmt->fetchAll();

$relatedStmt = db()->prepare("
    SELECT n.id, n.title, n.slug, n.cover_image, n.published_at, n.created_at,
           c.slug AS category_slug, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.status = 'published' AND n.category_id = ? AND n.id != ?
    ORDER BY n.published_at DESC LIMIT 4
");
$relatedStmt->execute([$article['category_id'], $article['id']]);
$related = $relatedStmt->fetchAll();

$commentError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($name === '' || $email === '' || $comment === '') {
        $commentError = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $commentError = 'Geçerli bir e-posta adresi girin.';
    } elseif (mb_strlen($name) > 100 || mb_strlen($email) > 150 || mb_strlen($comment) > 5000) {
        $commentError = 'Ad en fazla 100, e-posta 150 ve yorum 5000 karakter olabilir.';
    } elseif (time() - (int)($_SESSION['last_comment_at'] ?? 0) < 60) {
        $commentError = 'Yeni bir yorum göndermeden önce bir dakika bekleyin.';
    } else {
        $ins = db()->prepare('INSERT INTO comments (news_id, name, email, comment, status, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([$article['id'], $name, $email, $comment, 'pending', $_SERVER['REMOTE_ADDR'] ?? null]);
        $_SESSION['last_comment_at'] = time();
        flash_set('success', 'Yorumunuz alindi, onaylandiktan sonra yayinlanacaktir.');
        redirect(news_url($article) . '#yorumlar');
    }
}

$commentsStmt = db()->prepare("SELECT * FROM comments WHERE news_id = ? AND status = 'approved' ORDER BY created_at DESC");
$commentsStmt->execute([$article['id']]);
$comments = $commentsStmt->fetchAll();

$feedStmt=db()->prepare('SELECT * FROM feed_imports WHERE news_id=?');$feedStmt->execute([$article['id']]);$feedSource=$feedStmt->fetch();
$__pageTitle = $article['title'] . ' - ' . get_setting('site_name');
$__pageDescription = excerpt($article['summary'] ?: $article['content'], 160);
$__ogType = 'article';
$__canonicalUrl = news_url($article);
if ($article['cover_image']) $__pageImage = image_url($article['cover_image']);
$__articleSchema = ['@context'=>'https://schema.org','@type'=>'NewsArticle','headline'=>$article['title'],'description'=>$__pageDescription,'mainEntityOfPage'=>news_url($article),'datePublished'=>date('c',strtotime($article['published_at'] ?? $article['created_at'])),'dateModified'=>date('c',strtotime($article['updated_at'] ?? $article['published_at'])),'author'=>['@type'=>'Person','name'=>$article['author_name'],'url'=>BASE_URL.'/writer.php?id='.$article['author_id']],'publisher'=>['@type'=>'Organization','name'=>get_setting('site_name'),'url'=>BASE_URL],'articleSection'=>$article['category_name'],'inLanguage'=>'tr-TR'];
if (!empty($article['cover_image'])) $__articleSchema['image']=[image_url($article['cover_image'])];
require __DIR__ . '/includes/header.php';
?>
<script type="application/ld+json"><?= json_encode($__articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<div class="page-layout">
<article>
    <div class="breadcrumb">
        <a href="<?= e(BASE_URL) ?>/index.php">Ana Sayfa</a> /
        <a href="<?= e(category_url(['slug' => $article['category_slug']])) ?>"><?= e($article['category_name']) ?></a>
    </div>

    <div class="article-header">
        <h1 class="article-title"><?= e($article['title']) ?></h1>
        <?php if ($article['summary']): ?><p class="article-summary"><?= e($article['summary']) ?></p><?php endif; ?>
        <div class="article-meta">
            <span><i class="bi bi-person-fill"></i> <a href="<?= e(BASE_URL) ?>/writer.php?id=<?= (int)$article['author_id'] ?>"><?= e($article['author_name']) ?></a></span>
            <span><i class="bi bi-clock-fill"></i> <?= e(format_date($article['published_at'] ?? $article['created_at'])) ?></span>
            <span><i class="bi bi-eye-fill"></i> <?= (int)$article['views'] ?> görüntülenme</span>
        </div>
    </div>

    <?php
    $__shareUrl = rawurlencode($__canonicalUrl);
    $__shareTitle = rawurlencode($article['title']);
    ?>
    <div class="share-buttons">
        <span class="share-label">Paylaş:</span>
        <a href="https://wa.me/?text=<?= $__shareTitle ?>%20<?= $__shareUrl ?>" target="_blank" rel="noopener" class="share-btn whatsapp" aria-label="WhatsApp'ta paylaş"><i class="bi bi-whatsapp"></i></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $__shareUrl ?>" target="_blank" rel="noopener" class="share-btn facebook" aria-label="Facebook'ta paylaş"><i class="bi bi-facebook"></i></a>
        <a href="https://twitter.com/intent/tweet?text=<?= $__shareTitle ?>&url=<?= $__shareUrl ?>" target="_blank" rel="noopener" class="share-btn twitter" aria-label="X'te paylaş"><i class="bi bi-twitter-x"></i></a>
        <a href="https://t.me/share/url?url=<?= $__shareUrl ?>&text=<?= $__shareTitle ?>" target="_blank" rel="noopener" class="share-btn telegram" aria-label="Telegram'da paylaş"><i class="bi bi-telegram"></i></a>
    </div>

    <img class="article-cover" src="<?= e(image_url($article['cover_image'])) ?>" alt="<?= e($article['title']) ?>">

    <p class="reading-time"><?= max(1, (int)ceil(count(preg_split('/\s+/u', trim(strip_tags($article['content'])))) / 200)) ?> dakika okuma</p>
    <div class="article-content"><?= clean_article_html($article['content']) ?></div>

    <?php if ($gallery): ?>
        <div class="news-grid" style="margin-top:24px;">
            <?php foreach ($gallery as $g): ?>
                <img src="<?= e(image_url($g['image_path'])) ?>" style="border-radius:6px;width:100%;aspect-ratio:4/3;object-fit:cover;">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($tags): ?>
        <div class="article-tags">
            <?php foreach ($tags as $t): ?>
                <a href="<?= e(BASE_URL) ?>/search.php?q=<?= urlencode($t['name']) ?>">#<?= e($t['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if($feedSource): ?><aside class="alert" style="background:#f5f5f7"><strong>Kaynak:</strong> <a href="<?= e($feedSource['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e(parse_url($feedSource['source_url'],PHP_URL_HOST)) ?> — Orijinal haber</a><?php if($feedSource['source_published_at']): ?><div>Kaynak tarihi: <?= e(format_date($feedSource['source_published_at'])) ?></div><?php endif; ?><small>Kaynak habere dayanan kısa Türkçe özet; tam metin için kaynak bağlantısını ziyaret edin.</small></aside><?php endif; ?>
    <section class="comments-section" id="yorumlar">
        <h2 class="section-title">Yorumlar (<?= count($comments) ?>)</h2>

        <?php if ($commentError): ?><div class="alert alert-error"><?= e($commentError) ?></div><?php endif; ?>

        <form class="comment-form" method="post" action="<?= e(news_url($article)) ?>#yorumlar">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="Adınız" required value="<?= e($_POST['name'] ?? '') ?>">
            <input type="email" name="email" placeholder="E-posta adresiniz (yayınlanmaz)" required value="<?= e($_POST['email'] ?? '') ?>">
            <textarea name="comment" rows="4" placeholder="Yorumunuz" required><?= e($_POST['comment'] ?? '') ?></textarea>
            <button type="submit">Yorum Gönder</button>
        </form>

        <?php foreach ($comments as $c): ?>
            <div class="comment-item">
                <span class="name"><?= e($c['name']) ?></span>
                <span class="date"><?= e(time_ago($c['created_at'])) ?></span>
                <p><?= nl2br(e($c['comment'])) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?><p>Henüz yorum yapılmamış. İlk yorumu siz yapın.</p><?php endif; ?>
    </section>
</article>

<aside>
    <div class="widget">
        <h4>İlgili Haberler</h4>
        <div class="widget-body">
            <?php foreach ($related as $r): ?>
                <?= news_row_html($r) ?>
            <?php endforeach; ?>
            <?php if (!$related): ?><p>İlgili haber bulunamadı.</p><?php endif; ?>
        </div>
    </div>
</aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
