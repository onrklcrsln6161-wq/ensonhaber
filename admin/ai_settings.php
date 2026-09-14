<?php
require_once __DIR__.'/../includes/auth.php';$__admin=require_role(['super_admin']);$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$model=trim($_POST['model']??'');if($model!==''&&!preg_match('/^[a-zA-Z0-9._-]{1,100}$/',$model))$error='Geçerli model kimliği girin.';else{save_setting('ai_model',$model);save_setting('ai_enabled',isset($_POST['enabled'])?'1':'0');editorial_log('ai.settings_updated');flash_set('success','Yapay zekâ ayarları kaydedildi.');redirect('ai_settings.php');}}
$__adminTitle='Yapay Zekâ Ayarları';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Yapay Zekâ Ayarları</h1><p>API anahtarı sunucunun <code>OPENAI_API_KEY</code> ortam değişkeninde tutulur. Bu alana veya haberlere anahtar yazmayın.</p><p>Anahtar durumu: <strong><?= getenv('OPENAI_API_KEY')?'Tanımlı':'Tanımlı değil' ?></strong></p>
<?php if($error): ?><p class="alert alert-danger"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="card-panel"><?= csrf_field() ?><label for="model" class="form-label">Hesabınızda kullanılabilir Responses API model kimliği</label><input class="form-control mb-3" id="model" name="model" value="<?= e(get_setting('ai_model')) ?>" maxlength="100"><label class="d-block mb-3"><input type="checkbox" name="enabled" <?= get_setting('ai_enabled','0')==='1'?'checked':'' ?>> Haber editöründe yapay zekâyı etkinleştir</label><button class="btn btn-danger">Kaydet</button></form><p>Öneri başına API kullanımı ücret doğurabilir. Kullanıcı başına saatte 20 istek sınırı vardır. İstek yalnızca kullanıcı düğmeye bastığında gönderilir; otomatik kayıt veya yayın yapılmaz.</p>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
