<?php
require_once __DIR__.'/../includes/auth.php';
$__admin=require_role(['super_admin']);
$errors=[]; $values=[];
foreach(social_platforms() as $key=>$platform) { $values[$key.'_url']=get_setting($key.'_url'); $values[$key.'_enabled']=get_setting($key.'_enabled','1'); }
if($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 foreach(social_platforms() as $key=>$platform) {
  $url=trim($_POST[$key.'_url']??'');
  $values[$key.'_url']=$url; $values[$key.'_enabled']=isset($_POST[$key.'_enabled'])?'1':'0';
  if($url!=='' && !valid_social_url($url,$platform[2])) $errors[]=$platform[0].': https:// ile başlayan, platforma ait geçerli bir hesap bağlantısı girin.';
 }
 if(!$errors) {
  db()->beginTransaction();
  try { foreach($values as $key=>$value) save_setting($key,$value); editorial_log('social.updated'); db()->commit(); } catch(Throwable $e) { db()->rollBack(); throw $e; }
  flash_set('success','Sosyal medya hesapları güncellendi.'); redirect('social.php');
 }
}
$__adminTitle='Sosyal Medya Hesapları'; require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Sosyal Medya Hesapları</h1>
<p class="text-muted">Hesap bağlantılarını ekleyin. Boş bırakılan veya gösterimi kapatılan hesaplar üst menüde ve sayfa altında görünmez.</p>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="card-panel">
<?= csrf_field() ?><div class="row g-3">
<?php foreach(social_platforms() as $key=>$platform): ?>
<div class="col-md-6"><label class="form-label" for="<?= e($key) ?>"><i class="bi bi-<?= e($platform[1]) ?>"></i> <?= e($platform[0]) ?></label>
<input class="form-control" id="<?= e($key) ?>" type="url" maxlength="500" name="<?= e($key) ?>_url" placeholder="https://<?= e($platform[2][0]) ?>/..." value="<?= e($values[$key.'_url']) ?>">
<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="<?= e($key) ?>_enabled" <?= $values[$key.'_enabled']==='1'?'checked':'' ?>> Sitede göster</label></div>
<?php endforeach; ?></div><button class="btn btn-danger mt-4">Hesapları kaydet</button></form>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
