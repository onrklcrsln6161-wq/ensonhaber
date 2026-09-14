<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!in_array('--manual',$argv,true)){fwrite(STDERR,"Manuel kullanım: php scripts/import-feeds.php --manual\n");exit(1);}
require_once __DIR__.'/../includes/feeds.php';
$actor=(int)db()->query("SELECT id FROM admins WHERE role='super_admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
$category=(int)db()->query("SELECT id FROM categories WHERE slug='dunya' AND is_active=1")->fetchColumn();
if(!$actor||!$category)throw new RuntimeException('Aktif yönetici ve Dünya kategorisi gerekli.');
$_SESSION['admin_id']=$actor;$failed=false;
foreach(feed_catalog() as $key=>$source){if(get_setting('feed_enabled_'.$key,'0')!=='1')continue;
 try{echo $source[0].': '.run_news_feed($key,$actor,$category)." onay bekleyen haber\n";}catch(Throwable $e){$failed=true;fwrite(STDERR,$source[0].': '.$e->getMessage().PHP_EOL);}}
exit($failed?1:0);
