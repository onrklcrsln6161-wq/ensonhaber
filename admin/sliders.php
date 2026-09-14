<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orders = $_POST['order'] ?? [];
    foreach ($orders as $newsId => $order) {
        db()->prepare('UPDATE news SET slider_order = ? WHERE id = ?')->execute([(int)$order, (int)$newsId]);
    }
    if (!empty($_POST['remove_id'])) {
        db()->prepare('UPDATE news SET is_slider = 0 WHERE id = ?')->execute([(int)$_POST['remove_id']]);
    }
    if (!empty($_POST['add_id'])) {
        $current = (int)db()->query("SELECT COUNT(*) FROM news WHERE is_slider = 1 AND status='published'")->fetchColumn();
        if ($current >= 8) {
            flash_set('error', 'Slider en fazla 8 haber gösterebilir. Önce birini kaldırın.');
        } else {
            $add=db()->prepare("UPDATE news SET is_slider = 1, slider_order = ? WHERE id = ? AND status='published' AND NOT EXISTS(SELECT 1 FROM news_trash t WHERE t.news_id=news.id)");$add->execute([$current, (int)$_POST['add_id']]);
            flash_set($add->rowCount() ? 'success' : 'error', $add->rowCount() ? 'Haber slider’a eklendi.' : 'Yayında olan bir haber seçin.');
        }
    }
    redirect('sliders.php');
}

$sliderNews = db()->query("
    SELECT n.id, n.title, n.slug, n.cover_image, n.slider_order, c.name AS category_name
    FROM news n JOIN categories c ON c.id = n.category_id
    WHERE n.is_slider = 1 AND n.status = 'published' ORDER BY n.slider_order
")->fetchAll();

$q = trim($_GET['q'] ?? '');
$available = [];
if ($q !== '') {
    $stmt = db()->prepare("SELECT id, title, slug, cover_image FROM news WHERE status='published' AND is_slider = 0 AND title LIKE ? LIMIT 10");
    $stmt->execute(['%' . $q . '%']);
    $available = $stmt->fetchAll();
}

$__adminTitle = 'Slider Yönetimi';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Ana Sayfa Slider Yönetimi</h3>
<p class="text-muted">Ana sayfadaki büyük görsel slider'da gösterilecek haberleri buradan seçip sıralayabilirsiniz. (En fazla 8 haber)</p>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-panel">
            <h5 class="mb-3">Slider'daki Haberler</h5>
            <form method="post">
                <?= csrf_field() ?>
                <table class="table align-middle">
                    <thead><tr><th></th><th>Başlık</th><th style="width:90px;">Sıra</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($sliderNews as $n): ?>
                        <tr>
                            <td><img class="table-thumb" src="<?= e(image_url($n['cover_image'])) ?>" alt=""></td>
                            <td><?= e(excerpt($n['title'], 45)) ?><br><small class="text-muted"><?= e($n['category_name']) ?></small></td>
                            <td><input type="number" name="order[<?= $n['id'] ?>]" value="<?= (int)$n['slider_order'] ?>" class="form-control form-control-sm"></td>
                            <td>
                                <button type="submit" name="remove_id" value="<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger" formnovalidate>Kaldır</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$sliderNews): ?><tr><td colspan="4" class="text-center text-muted py-3">Slider'da haber yok. Sağdan haber ekleyin.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <?php if ($sliderNews): ?><button type="submit" class="btn btn-danger">Sırayı Kaydet</button><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-panel">
            <h5 class="mb-3">Slider'a Haber Ekle</h5>
            <form method="get" class="mb-3 d-flex gap-2">
                <input type="text" name="q" class="form-control" placeholder="Haber başlığı ara..." value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary">Ara</button>
            </form>
            <?php foreach ($available as $n): ?>
                <form method="post" class="d-flex align-items-center gap-2 border-bottom py-2">
                    <?= csrf_field() ?>
                    <img class="table-thumb" src="<?= e(image_url($n['cover_image'])) ?>" alt="">
                    <span class="flex-grow-1 small"><?= e(excerpt($n['title'], 45)) ?></span>
                    <button type="submit" name="add_id" value="<?= $n['id'] ?>" class="btn btn-sm btn-outline-success">Ekle</button>
                </form>
            <?php endforeach; ?>
            <?php if ($q !== '' && !$available): ?><p class="text-muted">Sonuç bulunamadı.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
