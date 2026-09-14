<?php
require __DIR__ . '/../includes/permissions.php';
$count = 0;
function expect($value, $label) { global $count; if (!$value) { fwrite(STDERR, "FAIL: $label\n"); exit(1); } $count++; }
foreach (['social.php','pages.php','users.php','user_form.php','settings.php','live_data.php','market_ticker.php','news_delete.php'] as $page) {
 expect(panel_can_access('super_admin',$page), "admin $page");
 expect(!panel_can_access('editor',$page), "editor denied $page");
 expect(!panel_can_access('author',$page), "author denied $page");
}
foreach (['media.php','comments.php','sliders.php','categories.php'] as $page) {
 expect(panel_can_access('editor',$page), "editor $page"); expect(!panel_can_access('author',$page), "author denied $page");
}
foreach (['draft','pending','published'] as $status) {
 $news = ['author_id'=>1,'status'=>$status];
 expect(can_edit_news(['id'=>1,'role'=>'author'],$news) === ($status !== 'published'), 'own workflow');
 expect(!can_edit_news(['id'=>2,'role'=>'author'],$news), 'other author denied');
 expect(can_edit_news(['id'=>2,'role'=>'editor'],$news), 'editor workflow');
}
expect(!isset(allowed_news_statuses('author')['published']), 'author cannot publish');
expect(!panel_can_access('unknown','index.php'), 'unknown role denied');
expect(!panel_can_access('editor','unknown.php'), 'unknown endpoint denied');
echo "$count permission checks passed\n";
