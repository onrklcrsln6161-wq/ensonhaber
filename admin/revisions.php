<?php
require_once __DIR__.'/../includes/auth.php';$__admin=require_role(['super_admin','editor']);
$id=(int)($_GET['id']??0);$stmt=db()->prepare('SELECT * FROM news WHERE id=?');$stmt->execute([$id]);$n=$stmt->fetch();
if(!$n || news_is_trashed($id)){http_response_code(404);exit('Haber bulunamadı.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$stmt=db()->prepare('SELECT snapshot FROM news_revisions WHERE id=? AND news_id=?');$stmt->execute([(int)($_POST['revision']??0),$id]);$raw=$stmt->fetchColumn();
 if(!$raw){http_response_code(404);exit('Revizyon bulunamadı.');}
 $old=json_decode($raw,true,512,JSON_THROW_ON_ERROR);db()->beginTransaction();snapshot_news($id);
 db()->prepare("UPDATE news SET title=?,summary=?,content=?,cover_image=?,status='draft',updated_at=NOW() WHERE id=?")->execute([$old['title'],$old['summary'],clean_article_html($old['content']),$old['cover_image'],$id]);
 editorial_log('news.revision_restored',$id);db()->commit();flash_set('success','İçerik taslak olarak geri alındı. Kategori, etiket ve galeri değiştirilmedi.');redirect('news_form.php?id='.$id);
}
$stmt=db()->prepare('SELECT r.*,a.full_name FROM news_revisions r LEFT JOIN admins a ON a.id=r.actor_id WHERE news_id=? ORDER BY r.id DESC LIMIT 100');$stmt->execute([$id]);$list=$stmt->fetchAll();
$__adminTitle='Revizyon Geçmişi';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Revizyon Geçmişi</h1><p>Başlık, özet, içerik ve kapak görseli geri alınır. Haber taslağa döner. En son 100 kayıt gösterilir.</p>
<?php foreach($list as $revision): $old=json_decode($revision['snapshot'],true); ?><details class="card-panel"><summary><?= e($revision['created_at'].' — '.$revision['full_name']) ?></summary><h2 class="h5 mt-3"><?= e($old['title']) ?></h2><p><?= e($old['summary']) ?></p><div><?= clean_article_html($old['content']) ?></div><form method="post"><?= csrf_field() ?><input type="hidden" name="revision" value="<?= (int)$revision['id'] ?>"><button class="btn btn-outline-primary mt-3">Bu içeriği taslak olarak geri al</button></form></details><?php endforeach; ?>
<?php if(!$list): ?><p>Henüz kaydedilmiş eski sürüm yok.</p><?php endif; require __DIR__.'/includes/admin_footer.php'; ?>
