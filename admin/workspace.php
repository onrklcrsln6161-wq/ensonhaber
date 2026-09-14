<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_login();
$__adminTitle = panel_role_name($__admin['role']);
$where = $__admin['role'] === 'author' ? ' WHERE author_id = ?' : '';
$params = $__admin['role'] === 'author' ? [$__admin['id']] : [];
$stmt = db()->prepare('SELECT status, COUNT(*) AS total FROM news' . $where . ' GROUP BY status');
$stmt->execute($params);
$counts = array_column($stmt->fetchAll(), 'total', 'status');
require __DIR__ . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3"><?= e($__adminTitle) ?></h1>
<p class="text-muted"><?= $__admin['role'] === 'author' ? 'Kendi taslaklarınızı hazırlayın ve inceleme için editöre gönderin. Yayımlanan haberlerdeki değişiklikleri editörünüz yapar.' : 'Yazarların gönderdiği haberleri inceleyin, düzenleyin ve yayımlayın. Taslak durumuna alarak düzeltme isteyebilirsiniz.' ?></p>
<div class="row g-3 mb-4">
<?php foreach (['draft'=>'Taslaklar','pending'=>'Onay Bekleyenler','published'=>'Yayımlananlar'] as $status=>$label): ?>
<div class="col-md-4"><a class="card-panel d-block text-decoration-none" href="news.php?status=<?= e($status) ?>"><span><?= e($label) ?></span><strong class="d-block display-6"><?= (int)($counts[$status] ?? 0) ?></strong></a></div>
<?php endforeach; ?>
</div>
<div class="card-panel"><h2 class="h5">Hızlı işlemler</h2><a class="btn btn-danger me-2" href="news_form.php">Yeni haber hazırla</a><a class="btn btn-outline-secondary" href="news.php<?= $__admin['role'] === 'editor' ? '?status=pending' : '' ?>"><?= $__admin['role'] === 'editor' ? 'İnceleme kuyruğu' : 'Haberlerim' ?></a></div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
