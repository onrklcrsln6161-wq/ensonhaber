<?php
require_once __DIR__.'/../includes/auth.php';$__admin=require_role(['super_admin']);
if($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf();$id=(int)($_POST['id']??0);db()->beginTransaction();db()->prepare('DELETE FROM news_trash WHERE news_id=?')->execute([$id]);editorial_log('news.restored_as_draft',$id);db()->commit();flash_set('success','Haber taslak olarak geri alındı.');redirect('trash.php'); }
$list=db()->query('SELECT n.id,n.title,t.created_at FROM news_trash t JOIN news n ON n.id=t.news_id ORDER BY t.created_at DESC LIMIT 100')->fetchAll();
$__adminTitle='Çöp Kutusu';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Çöp Kutusu</h1><p>Geri alınan haberler taslak kalır; tekrar yayın için incelenmelidir. En son 100 kayıt gösterilir.</p>
<?php foreach($list as $n): ?><div class="card-panel"><strong><?= e($n['title']) ?></strong><form method="post" class="mt-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn btn-outline-primary">Taslak olarak geri al</button></form></div><?php endforeach; ?>
<?php if(!$list): ?><p>Çöp kutusu boş.</p><?php endif; require __DIR__.'/includes/admin_footer.php'; ?>
