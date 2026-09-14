<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if ($id === (int)$__admin['id']) {
            flash_set('error', 'Kendi hesabınızı silemezsiniz.');
        } else {
            db()->prepare('UPDATE admins SET is_active=0 WHERE id=?')->execute([$id]);
            editorial_log('user.deactivated');
            flash_set('success', 'Hesap pasif hale getirildi. Haberleri ve yazar bilgileri korundu.');
        }
    } elseif ($action === 'toggle') {
        if($id === (int)$__admin['id']) { flash_set('error','Kendi yönetici hesabınızı pasifleştiremezsiniz.'); } else {
        db()->prepare('UPDATE admins SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        editorial_log('user.status_changed');
        flash_set('success', 'Kullanıcı durumu güncellendi.'); }
    }
    redirect('users.php');
}

$users = db()->query('SELECT * FROM admins ORDER BY created_at DESC')->fetchAll();
$__adminTitle = 'Kullanıcılar';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Yönetici Kullanıcılar</h3>
    <a href="user_form.php" class="btn btn-danger"><i class="bi bi-plus-circle"></i> Yeni Kullanıcı</a>
</div>

<p>Hesap kaldırma işlemi kullanıcıyı pasifleştirir; haberler ve yazarlık bilgileri korunur.</p>
<div class="card-panel">
    <table class="table align-middle">
        <thead><tr><th>Ad Soyad</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son Giriş</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['full_name']) ?></td>
                <td><?= e($u['username']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge text-bg-secondary"><?= e($u['role']) ?></span></td>
                <td>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-success' : 'btn-outline-secondary' ?>"><?= $u['is_active'] ? 'Aktif' : 'Pasif' ?></button>
                    </form>
                </td>
                <td><small><?= $u['last_login'] ? e(format_date($u['last_login'])) : '-' ?></small></td>
                <td class="text-nowrap">
                    <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <?php if ((int)$u['id'] !== (int)$__admin['id']): ?>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Bu hesabı pasif hale getirmek istediğinize emin misiniz?"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
