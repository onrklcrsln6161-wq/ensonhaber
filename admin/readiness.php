<?php
require_once __DIR__.'/../includes/auth.php';$__admin=require_role(['super_admin']);
$checks=[];
foreach(publication_pages() as $slug=>$label) $checks[]=[$label,trim(get_setting('page_'.$slug))!=='','pages.php'];
$checks[]=['En az bir sosyal hesap',count(social_accounts())>0,'social.php'];
$checks[]=['Logo',get_setting('logo_path')!=='','settings.php'];
$checks[]=['Üretim HTTPS adresi',str_starts_with(BASE_URL,'https://') && !str_contains(BASE_URL,'localhost'),''];
$checks[]=['MySQL bağlantısı',DB_HOST!=='sqlite',''];
$checks[]=['Yayımlayan yazarların biyografileri',true,''];
foreach(db()->query("SELECT DISTINCT author_id FROM news WHERE status='published'")->fetchAll() as $author) if(!trim(get_setting('author_bio_'.$author['author_id']))) $checks[count($checks)-1][1]=false;
$__adminTitle='Yayın Hazırlığı';require __DIR__.'/includes/admin_header.php';
?>
<h1 class="h3">Yayın Hazırlığı</h1><p>Bu kontroller alanların doluluğunu denetler; metinlerin doğruluğu veya yayının tüm gereklilikleri karşıladığı anlamına gelmez.</p><ul class="list-group mb-4">
<?php foreach($checks as [$label,$ok,$link]): ?><li class="list-group-item d-flex justify-content-between"><span><?= e($label) ?></span><span><?= $ok?'✓ Hazır':'Eksik' ?> <?php if($link): ?><a href="<?= e($link) ?>">Düzenle</a><?php endif; ?></span></li><?php endforeach; ?></ul>
<div class="card-panel"><h2 class="h5">Sunucuda doğrulanacaklar</h2><p>Yedekleme ve geri yükleme, e-posta teslimatı, zamanlanmış işler, gerçek piyasa sağlayıcısı, içerik/görsel yayın hakları ve Search Console kontrolleri ayrıca doğrulanmalıdır. E-posta gönderimi bu sürümde yapılandırılmamıştır.</p><p>Veritabanı yedeği: sunucuda <code>php scripts/backup.php</code>. Görseller için <code>uploads</code> klasörünü ayrıca yedekleyin. Yedekler kişisel veri içerir; herkese açık depoya yüklemeyin.</p></div>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
