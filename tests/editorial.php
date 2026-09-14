<?php
putenv('DB_HOST=sqlite');putenv('DB_NAME=:memory:');
require __DIR__.'/../scripts/migrate.php';require __DIR__.'/../includes/auth.php';
function ok($v,$label){if(!$v)throw new RuntimeException($label);echo "PASS: $label\n";}
db()->exec('CREATE TABLE news(id INTEGER PRIMARY KEY,title TEXT,summary TEXT,content TEXT,cover_image TEXT)');
db()->exec("INSERT INTO news VALUES(1,'İlk başlık','özet','<p>İçerik</p>',NULL)");
$_SESSION['admin_id']=42;
db()->beginTransaction();snapshot_news(1);db()->exec("UPDATE news SET title='İkinci' WHERE id=1");editorial_log('news.updated',1);db()->commit();
$old=json_decode(db()->query('SELECT snapshot FROM news_revisions')->fetchColumn(),true);
ok($old['title']==='İlk başlık','Previous Unicode content preserved');
ok((int)db()->query('SELECT actor_id FROM editorial_log')->fetchColumn()===42,'Actor recorded');
ok(!news_is_trashed(1),'Initial trash state');
db()->exec("INSERT INTO news_trash VALUES(1,42,'2026-09-13')");ok(news_is_trashed(1),'Trash marker');
db()->exec('DELETE FROM news_trash WHERE news_id=1');ok(!news_is_trashed(1),'Restore marker');
db()->beginTransaction();snapshot_news(1);db()->rollBack();ok((int)db()->query('SELECT COUNT(*) FROM news_revisions')->fetchColumn()===1,'Revision rolls back atomically');
foreach(['trash.php','audit.php','readiness.php'] as $page){ok(!panel_can_access('author',$page)&&!panel_can_access('editor',$page)&&panel_can_access('super_admin',$page),'Admin-only '.$page);}
ok(panel_can_access('editor','revisions.php')&&!panel_can_access('author','revisions.php'),'Revision access');
