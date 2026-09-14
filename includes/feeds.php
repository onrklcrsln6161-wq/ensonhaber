<?php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/editorial.php';
require_once __DIR__.'/feed-network.php';
function feed_catalog(): array {
 $catalog = [
 'bbc-world'=>['BBC Dünya','https://feeds.bbci.co.uk/news/world/rss.xml',['bbc.co.uk','bbc.com']],
 'bbc-europe'=>['BBC Avrupa','https://feeds.bbci.co.uk/news/world/europe/rss.xml',['bbc.co.uk','bbc.com']],
 'bbc-asia'=>['BBC Asya','https://feeds.bbci.co.uk/news/world/asia/rss.xml',['bbc.co.uk','bbc.com']],
 'bbc-africa'=>['BBC Afrika','https://feeds.bbci.co.uk/news/world/africa/rss.xml',['bbc.co.uk','bbc.com']],
 'bbc-americas'=>['BBC Amerika','https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml',['bbc.co.uk','bbc.com']],
 'bbc-middle-east'=>['BBC Orta Doğu','https://feeds.bbci.co.uk/news/world/middle_east/rss.xml',['bbc.co.uk','bbc.com']],
 'guardian-world'=>['The Guardian Dünya','https://www.theguardian.com/world/rss',['theguardian.com']],
 ];
 $custom=json_decode(get_setting('custom_feeds','{}'),true) ?: [];
 return array_merge($catalog,$custom);
}
function parse_news_feed(string $xml, array $domains): array {
 if(strlen($xml)>2000000 || preg_match('/<!DOCTYPE|<!ENTITY/i',$xml)) throw new RuntimeException('RSS güvenli boyut/biçim sınırını aşıyor.');
 $previous=libxml_use_internal_errors(true);
 try {$root=simplexml_load_string($xml,SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);if(!$root || (!isset($root->channel) && $root->getName()!=='feed'))throw new RuntimeException('Geçersiz RSS yanıtı.');
 $out=[];
 $atom=$root->getName()==='feed';
 $entries=$atom ? $root->children('http://www.w3.org/2005/Atom')->entry : $root->channel->item;
 foreach($entries as $item){
  $url=trim((string)$item->link);
  if($atom){$url='';foreach($item->link as $link){$attributes=$link->attributes();if(!isset($attributes['rel']) || (string)$attributes['rel']==='alternate'){$url=(string)$attributes['href'];break;}}}
if(!valid_social_url($url,$domains))continue;
  $parts=parse_url($url);$query=[];parse_str($parts['query']??'',$query);foreach(array_keys($query) as $param)if(preg_match('/^(utm_|at_|fbclid$|gclid$)/',$param))unset($query[$param]);ksort($query);$url='https://'.strtolower($parts['host']).($parts['path']??'/').($query?'?'.http_build_query($query):'');
  $title=trim(html_entity_decode(strip_tags((string)$item->title),ENT_QUOTES|ENT_HTML5,'UTF-8'));if($title==='')continue;
  $time=strtotime((string)($atom ? ($item->published ?: $item->updated) : $item->pubDate));
  $out[]=['title'=>mb_substr($title,0,255),'url'=>$url,'date'=>$time?date('Y-m-d H:i:s',$time):null];
  if(count($out)>=30)break;
 }
 return $out;
 }finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
}
function import_feed_items(string $key, array $items, int $actor, int $category): int {
 if(!isset(feed_catalog()[$key]))throw new RuntimeException('Bilinmeyen kaynak.');
 $count=0;
 foreach($items as $item){
  $hash=hash('sha256',$item['url']);$find=db()->prepare('SELECT 1 FROM feed_imports WHERE url_hash=?');$find->execute([$hash]);if($find->fetchColumn())continue;
  db()->beginTransaction();
  try {
   $text='<p>RSS üzerinden alınan haber başlığıdır. Yayımlamadan önce kaynak bağlantısını inceleyip Türkçe haber özetini hazırlayın.</p>';
   db()->prepare("INSERT INTO news(title,slug,summary,content,category_id,author_id,status) VALUES (?,?,?,?,?,?,'pending')")->execute([$item['title'],unique_slug('news',$item['title']),'Editör incelemesi bekleyen kaynak başlığı.',$text,$category,$actor]);
   $id=(int)db()->lastInsertId();
   db()->prepare('INSERT INTO feed_imports(news_id,source_key,source_url,url_hash,source_title,source_published_at,imported_at) VALUES (?,?,?,?,?,?,?)')->execute([$id,$key,$item['url'],$hash,$item['title'],$item['date'],date('Y-m-d H:i:s')]);
   editorial_log('feed.imported_pending',$id);db()->commit();$count++;
  }catch(Throwable $e){db()->rollBack();throw $e;}
 }
 return $count;
}
function run_news_feed(string $key,int $actor,int $category): int {
 $catalog=feed_catalog();if(!isset($catalog[$key]))throw new RuntimeException('Kaynak bulunamadı.');
 $lock=fopen(ROOT_PATH.'/config/feed-import.lock','c');if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Başka bir haber taraması sürüyor.');
 try {
  $xml=fetch_public_feed($catalog[$key][1]);
  $count=import_feed_items($key,parse_news_feed($xml,$catalog[$key][2]),$actor,$category);
  save_setting('feed_last_'.$key,date('Y-m-d H:i:s').' — '.$count.' yeni haber');return $count;
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
