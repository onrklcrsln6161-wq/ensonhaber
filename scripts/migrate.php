<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/functions.php';
$id=DB_HOST==='sqlite'?'INTEGER PRIMARY KEY AUTOINCREMENT':'BIGINT AUTO_INCREMENT PRIMARY KEY';
db()->exec("CREATE TABLE IF NOT EXISTS editorial_log (id $id, actor_id INT NOT NULL, action VARCHAR(80) NOT NULL, news_id INT DEFAULT NULL, created_at DATETIME NOT NULL)");
db()->exec("CREATE TABLE IF NOT EXISTS news_revisions (id $id, news_id INT NOT NULL, actor_id INT NOT NULL, snapshot LONGTEXT NOT NULL, created_at DATETIME NOT NULL)");
db()->exec('CREATE TABLE IF NOT EXISTS news_trash (news_id INT PRIMARY KEY, actor_id INT NOT NULL, created_at DATETIME NOT NULL)');
echo "Editorial migration complete\n";

require_once __DIR__.'/migrate-feeds.php';
