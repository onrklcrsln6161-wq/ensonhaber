<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/feeds.php';
$__admin=require_role(['super_admin','editor']);$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  if(($_POST['action']??'')==='sources'){
   if($__admin['role']!=='super_admin'){http_response_code(403);exit('Yetkiniz yok.');}
   foreach(feed_catalog() as $key=>$source)save_setting('feed_enabled_'.$key,isset($_POST['enabled'][$key])?'1':'0');
   editorial_log('feed.sources_updated');flash_set('success','Kaynak seçimleri kaydedildi.');redirect('feeds.php');
  }
  $key=(string)($_POST['source']??'');$category=(int)db()->query("SELECT id FROM categories WHERE slug='dunya' AND is_active=1")->fetchColumn();
  if(!$category)throw new RuntimeException('Aktif Dünya kategorisi gerekli.');
  $count=run_news_feed($key,(int)$__admin['id'],$category);flash_set('success',$count.' haber onay kuyruğuna eklendi; hiçbir haber yayımlanmadı.');redirect('feeds.php');
 }catch(Throwable $e){$error=$e->getMessage();}
}
$list=db()->query('SELECT f.*,n.title,n.status FROM feed_imports f JOIN news n ON n.id=f.news_id WHERE NOT EXISTS(SELECT 1 FROM news_trash t WHERE t.news_id=n.id) ORDER BY f.imported_at DESC,f.news_id DESC LIMIT 100')->fetchAll();
$__adminTitle='Dünya Haber Botu';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Dünya Haber Botu</h1><p>RSS başlıklarını ve kaynak bağlantılarını getirir. Tüm kayıtlar editör onayı bekler; otomatik yayın yoktur. Tam metin ve kaynak görselleri kopyalanmaz. Yabancı dildeki başlıkları yayımlamadan önce düzenleyin.</p>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="card-panel"><?= csrf_field() ?><label for="source" class="form-label">Kaynak</label><select id="source" name="source" class="form-select mb-3"><?php foreach(feed_catalog() as $key=>$source): ?><option value="<?= e($key) ?>"><?= e($source[0]) ?></option><?php endforeach; ?></select><button class="btn btn-danger">Haberleri onay kuyruğuna çek</button></form>
<?php if($__admin['role']==='super_admin'): ?><p><a class="btn btn-outline-primary" href="feed_sources.php">Kaynak ekle / kaldır</a></p><?php endif; ?><p>Manuel çalışma: yalnızca yukarıdaki düğmeye bastığınızda haber çekilir.</p>
<h2 class="h5">Son 100 içe aktarım</h2><div class="table-responsive"><table class="table"><thead><tr><th>Başlık</th><th>Durum</th><th>Kaynak</th><th></th></tr></thead><tbody><?php foreach($list as $row): ?><tr><td><?= e($row['title']) ?></td><td><?= e(['pending'=>'Onay bekliyor','published'=>'Yayında','draft'=>'Taslak'][$row['status']]??$row['status']) ?></td><td><a href="<?= e($row['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e(feed_catalog()[$row['source_key']][0]??$row['source_key']) ?></a></td><td><a href="news_form.php?id=<?= (int)$row['news_id'] ?>">İncele / düzenle</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
