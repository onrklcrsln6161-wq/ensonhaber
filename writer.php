<?php
require_once __DIR__.'/includes/functions.php';
$id=(int)($_GET['id']??0);$stmt=db()->prepare("SELECT a.id,a.full_name FROM admins a WHERE a.id=? AND EXISTS(SELECT 1 FROM news n WHERE n.author_id=a.id AND n.status='published')");$stmt->execute([$id]);$writer=$stmt->fetch();
if(!$writer){http_response_code(404);require __DIR__.'/404.php';exit;}
$page=max(1,(int)($_GET['page']??1));$stmt=db()->prepare("SELECT COUNT(*) FROM news WHERE author_id=? AND status='published'");$stmt->execute([$id]);$p=paginate((int)$stmt->fetchColumn(),12,$page);
$stmt=db()->prepare("SELECT n.*,c.slug AS category_slug,c.name AS category_name FROM news n JOIN categories c ON c.id=n.category_id WHERE n.author_id=? AND n.status='published' ORDER BY n.published_at DESC LIMIT 12 OFFSET ".$p['offset']);$stmt->execute([$id]);$items=$stmt->fetchAll();
$__pageTitle=$writer['full_name'].' - '.get_setting('site_name');$__canonicalUrl=BASE_URL.'/writer.php?id='.$id.($page>1?'&page='.$page:'');require __DIR__.'/includes/header.php';
?>
<main class="container publication-page"><h1><?= e($writer['full_name']) ?></h1><p><?= nl2br(e(get_setting('author_bio_'.$id))) ?></p><h2 class="section-title">Yazarın haberleri</h2><div class="news-grid"><?php foreach($items as $n) echo news_card_html($n); ?></div><nav class="pagination" aria-label="Yazar haberleri"><?php if($page>1): ?><a href="?id=<?= $id ?>&page=<?= $page-1 ?>">Önceki</a><?php endif; ?><?php if($page<$p['totalPages']): ?><a href="?id=<?= $id ?>&page=<?= $page+1 ?>">Sonraki</a><?php endif; ?></nav></main>
<?php require __DIR__.'/includes/footer.php'; ?>
