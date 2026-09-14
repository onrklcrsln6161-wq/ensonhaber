<?php
require_once __DIR__.'/../includes/auth.php';$__admin=require_role(['super_admin']);
$list=db()->query('SELECT l.*,a.full_name FROM editorial_log l LEFT JOIN admins a ON a.id=l.actor_id ORDER BY l.id DESC LIMIT 200')->fetchAll();
$__adminTitle='İşlem Günlüğü';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">İşlem Günlüğü</h1><p>En son 200 kayıt. Haber kaydı, çöp kutusu, geri alma ve kurumsal ayar işlemleri izlenir.</p><div class="table-responsive"><table class="table"><thead><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th><th>Haber</th></tr></thead><tbody>
<?php foreach($list as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e($row['full_name']??'Sistem') ?></td><td><?= e($row['action']) ?></td><td><?= (int)$row['news_id'] ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php require __DIR__.'/includes/admin_footer.php'; ?>
