<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/feeds.php';$__admin=require_role(['super_admin']);$error=null;
$custom=json_decode(get_setting('custom_feeds','{}'),true)?:[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();try{
 if(isset($_POST['remove'])){unset($custom[(string)$_POST['remove']]);}
 else{
 $name=trim($_POST['name']??'');$url=trim($_POST['url']??'');$domain=strtolower(trim($_POST['domain']??''));
 if($name===''||mb_strlen($name)>80||strlen($url)>500||!public_feed_host($domain))throw new RuntimeException('Kaynak adı, HTTPS akış adresi ve haberlerin alan adını girin.');
 if(count($custom)>=50)throw new RuntimeException('En fazla 50 özel kaynak eklenebilir.');
 $items=parse_news_feed(fetch_public_feed($url),[$domain]);if(!$items)throw new RuntimeException('Bu alan adına ait haber bulunamadı. RSS adresini ve haber alan adını kontrol edin.');
 $custom['custom-'.substr(hash('sha256',$url),0,16)]=[$name,$url,[$domain]];
 }
 save_setting('custom_feeds',json_encode($custom,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));editorial_log('feed.custom_sources_updated');flash_set('success','Kaynak listesi güncellendi. Haber çekilmedi veya yayımlanmadı.');redirect('feed_sources.php');
 }catch(Throwable $e){$error=$e->getMessage();}
}
$__adminTitle='Haber Kaynakları';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Haber Kaynağı Ekle</h1><p>RSS 2.0 veya Atom akışı sunan haber sitelerini ekleyin. Kaydetme sırasında akış doğrulanır; haber aktarılmaz. RSS sunmayan/erişimi kısıtlayan siteler için özel entegrasyon gerekir.</p>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="card-panel"><?= csrf_field() ?>
<label class="form-label" for="name">Kaynak adı</label><input id="name" name="name" class="form-control mb-3" required maxlength="80">
<label class="form-label" for="url">RSS / Atom adresi (HTTPS)</label><input id="url" name="url" type="url" class="form-control mb-3" placeholder="https://haber-sitesi.com/rss" required maxlength="500">
<label class="form-label" for="domain">Haber bağlantılarının alan adı</label><input id="domain" name="domain" class="form-control mb-3" placeholder="haber-sitesi.com" required>
<button class="btn btn-danger">Doğrula ve kaynak ekle</button></form>
<?php foreach($custom as $key=>$source): ?><div class="card-panel"><strong><?= e($source[0]) ?></strong><p><?= e($source[1]) ?></p><form method="post"><?= csrf_field() ?><button class="btn btn-outline-danger" name="remove" value="<?= e($key) ?>">Kaynağı listeden kaldır</button></form></div><?php endforeach; ?>
<a href="feeds.php">Haber botuna dön</a><?php require __DIR__.'/includes/admin_footer.php'; ?>
