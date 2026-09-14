<?php
putenv('DB_HOST=sqlite');putenv('DB_NAME=:memory:');
require __DIR__.'/../scripts/migrate.php';require_once __DIR__.'/../scripts/migrate-feeds.php';require __DIR__.'/../includes/feeds.php';require __DIR__.'/../includes/permissions.php';
db()->exec('CREATE TABLE settings(setting_key TEXT PRIMARY KEY,setting_value TEXT)');
function ok($v,$name){if(!$v)throw new RuntimeException($name);echo "PASS: $name\n";}
$xml='<rss><channel><item><title>Test &amp; News</title><link>https://www.bbc.com/news/articles/test?at_medium=RSS</link><pubDate>Sun, 13 Sep 2026 12:00:00 GMT</pubDate></item><item><title>Bad</title><link>https://127.0.0.1/private</link></item></channel></rss>';
$items=parse_news_feed($xml,['bbc.com']);ok(count($items)===1,'Untrusted article host rejected');ok($items[0]['url']==='https://www.bbc.com/news/articles/test','Tracking removed');ok($items[0]['title']==='Test & News','XML title decoded');
try{parse_news_feed('<!DOCTYPE rss [<!ENTITY x SYSTEM "file:///etc/passwd">]><rss/>',['bbc.com']);throw new LogicException('Entity accepted');}catch(RuntimeException $e){ok(true,'DTD rejected');}
db()->exec("CREATE TABLE news(id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,slug TEXT UNIQUE,summary TEXT,content TEXT,category_id INT,author_id INT,status TEXT)");
ok(import_feed_items('bbc-world',$items,1,1)===1,'First import');ok(import_feed_items('bbc-world',$items,1,1)===0,'Repeated import skipped');
ok(db()->query('SELECT status FROM news')->fetchColumn()==='pending','Import never publishes');
ok((int)db()->query('SELECT COUNT(*) FROM news')->fetchColumn()===1,'No duplicate news');
ok(!panel_can_access('author','feeds.php')&&panel_can_access('editor','feeds.php'),'Feed role restriction');

$atom='<feed xmlns="http://www.w3.org/2005/Atom"><entry><title>Atom deneme</title><link href="https://www.bbc.com/story?id=1&amp;utm_source=rss"/><updated>2026-09-13T12:00:00Z</updated></entry></feed>';
$parsed=parse_news_feed($atom,['bbc.com']);ok(count($parsed)===1 && $parsed[0]['url']==='https://www.bbc.com/story?id=1','Atom attributes and essential query retained');
