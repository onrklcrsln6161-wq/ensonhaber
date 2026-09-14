<?php
require_once __DIR__.'/../includes/auth.php';
$__admin=require_role(['super_admin']);
$pages=publication_pages();$values=[];$errors=[];
foreach($pages as $slug=>$label) $values[$slug]=get_setting('page_'.$slug);
if($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 foreach($pages as $slug=>$label) { $values[$slug]=trim($_POST[$slug]??''); if(mb_strlen($values[$slug])>20000) $errors[]=$label.' en fazla 20.000 karakter olabilir.'; }
 if(!$errors) {
 db()->beginTransaction();
 try { foreach($values as $slug=>$value) save_setting('page_'.$slug,$value); editorial_log('pages.updated'); db()->commit(); } catch(Throwable $e) { db()->rollBack(); throw $e; }
 flash_set('success','Kurumsal sayfalar kaydedildi.'); redirect('pages.php');
 }
}
$__adminTitle='Kurumsal Sayfalar';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Kurumsal Sayfalar</h1>
<p class="text-muted">Metin girilen sayfalar yayımlanır ve alt menüye eklenir. Boş bırakılan sayfalar yayımlanmaz. Gerçek kurum ve iletişim bilgilerinizi kullanın.</p>
<p>Künyede yayıncı ve sorumlu kişileri; iletişimde ulaşılabilir adresleri; yayın ilkelerinde kaynak doğrulama ve düzeltme sürecini açıklayın. Gizlilik metnini sitenizin gerçekten topladığı verilere göre hazırlayın.</p>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="card-panel"><?= csrf_field() ?>
<?php foreach($pages as $slug=>$label): ?>
<div class="mb-4"><label class="form-label" for="<?= e($slug) ?>"><?= e($label) ?></label>
<textarea class="form-control" rows="7" maxlength="20000" id="<?= e($slug) ?>" name="<?= e($slug) ?>"><?= e($values[$slug]) ?></textarea>
<?php if($values[$slug]!==''): ?><a href="<?= e(BASE_URL) ?>/page.php?slug=<?= e($slug) ?>" target="_blank" rel="noopener">Yayımlanan sayfayı görüntüle</a><?php endif; ?>
</div><?php endforeach; ?><button class="btn btn-danger">Sayfaları kaydet</button></form>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
