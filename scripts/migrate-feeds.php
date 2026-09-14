<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/functions.php';
db()->exec('CREATE TABLE IF NOT EXISTS feed_imports(news_id INT PRIMARY KEY, source_key VARCHAR(80) NOT NULL, source_url VARCHAR(500) NOT NULL, url_hash CHAR(64) NOT NULL UNIQUE, source_title VARCHAR(255) NOT NULL, source_published_at DATETIME DEFAULT NULL, imported_at DATETIME NOT NULL)');
echo "Feed migration complete\n";
