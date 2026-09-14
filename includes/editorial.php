<?php
function editorial_log(string $action, ?int $newsId=null): void {
 db()->prepare('INSERT INTO editorial_log(actor_id,action,news_id,created_at) VALUES (?,?,?,?)')->execute([(int)($_SESSION['admin_id']??0),$action,$newsId,date('Y-m-d H:i:s')]);
}
function snapshot_news(int $id): void {
 $s=db()->prepare('SELECT title,summary,content,cover_image FROM news WHERE id=?');$s->execute([$id]);$news=$s->fetch();
 if($news) db()->prepare('INSERT INTO news_revisions(news_id,actor_id,snapshot,created_at) VALUES (?,?,?,?)')->execute([$id,(int)($_SESSION['admin_id']??0),json_encode($news,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),date('Y-m-d H:i:s')]);
}
function news_is_trashed(int $id): bool {$s=db()->prepare('SELECT 1 FROM news_trash WHERE news_id=?');$s->execute([$id]);return (bool)$s->fetchColumn();}

function news_edit_token(array $news): string {
 $state=[];foreach(['title','summary','content','cover_image','category_id','status','is_breaking','is_slider','slider_order','is_featured','updated_at'] as $key)$state[$key]=(string)($news[$key]??'');
 return hash('sha256',json_encode($state,JSON_THROW_ON_ERROR));
}
