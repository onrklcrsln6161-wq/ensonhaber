<?php
require_once __DIR__.'/includes/functions.php';
$slug=$_GET['slug']??'';$pages=publication_pages();
$content=is_string($slug) && isset($pages[$slug]) ? get_setting('page_'.$slug) : '';
if(trim($content)==='') { http_response_code(404); require __DIR__.'/404.php'; exit; }
$__pageTitle=$pages[$slug].' - '.get_setting('site_name');
$__pageDescription=excerpt($content,160);
require __DIR__.'/includes/header.php';
?>
<main class="container publication-page"><h1><?= e($pages[$slug]) ?></h1><div class="article-content"><?= nl2br(e($content)) ?></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>
