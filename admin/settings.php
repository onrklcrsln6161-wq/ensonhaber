<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin']);

$fields = ['site_name', 'site_slogan', 'phone', 'whatsapp_ihbar', 'footer_text', 'meta_description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
    foreach ($fields as $key) {
        $stmt->execute([trim($_POST[$key] ?? ''), $key]);
    }
    $logoPath = handle_image_upload('logo', 'logo');
    if ($logoPath) {
        db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')->execute([$logoPath, 'logo_path']);
    }
    flash_set('success', 'Ayarlar güncellendi.');
    redirect('settings.php');
}

$current = [];
$stmt = db()->query('SELECT setting_key, setting_value FROM settings');
foreach ($stmt->fetchAll() as $row) {
    $current[$row['setting_key']] = $row['setting_value'];
}

$__adminTitle = 'Site Ayarları';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Site Ayarları</h3>

<div class="card-panel" style="max-width:680px;">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Site Adı</label>
            <input type="text" name="site_name" class="form-control" value="<?= e($current['site_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Slogan</label>
            <input type="text" name="site_slogan" class="form-control" value="<?= e($current['site_slogan'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Logo</label><br>
            <?php if (!empty($current['logo_path'])): ?><img src="<?= e(image_url($current['logo_path'])) ?>" style="max-height:48px;" class="mb-2"><br><?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
            <label class="form-label">Meta Açıklama (SEO)</label>
            <textarea name="meta_description" class="form-control" rows="2"><?= e($current['meta_description'] ?? '') ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" class="form-control" value="<?= e($current['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">WhatsApp İhbar Hattı</label>
                <input type="text" name="whatsapp_ihbar" class="form-control" value="<?= e($current['whatsapp_ihbar'] ?? '') ?>">
            </div>
        </div>
        <p><a href="social.php">Sosyal medya hesaplarını yönet →</a></p>
        <div class="mb-3">
            <label class="form-label">Footer Metni</label>
            <input type="text" name="footer_text" class="form-control" value="<?= e($current['footer_text'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-danger">Kaydet</button>
    </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
