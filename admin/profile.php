<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bio = trim($_POST['bio'] ?? '');
    if (mb_strlen($bio)>3000) $errors[]='Biyografi en fazla 3000 karakter olabilir.';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli ad ve e-posta girin.';
    }

    if ($newPassword !== '') {
        $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([$__admin['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($currentPassword, $hash)) {
            $errors[] = 'Mevcut şifreniz yanlış.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Yeni şifre en az 8 karakter olmalıdır.';
        }
    }

    if (!$errors) {
        if ($newPassword !== '') {
            db()->prepare('UPDATE admins SET full_name=?, email=?, password_hash=? WHERE id=?')
                ->execute([$fullName, $email, password_hash($newPassword, PASSWORD_DEFAULT), $__admin['id']]);
        } else {
            db()->prepare('UPDATE admins SET full_name=?, email=? WHERE id=?')->execute([$fullName, $email, $__admin['id']]);
        }
        save_setting('author_bio_'.$__admin['id'], $bio);
        editorial_log('profile.updated');
        flash_set('success', 'Profil güncellendi.');
        redirect('profile.php');
    }
}

$stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$__admin['id']]);
$me = $stmt->fetch();

$__adminTitle = 'Profilim';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Profilim</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<div class="card-panel" style="max-width:480px;">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Ad Soyad</label>
            <input type="text" name="full_name" class="form-control" required value="<?= e($me['full_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" required value="<?= e($me['email']) ?>">
        </div>
        <label class="form-label" for="bio">Herkese açık yazar biyografisi</label><textarea id="bio" name="bio" class="form-control" rows="5" maxlength="3000"><?= e($_POST['bio'] ?? get_setting('author_bio_'.$__admin['id'])) ?></textarea>
        <hr>
        <p class="text-muted small">Şifrenizi değiştirmek istemiyorsanız aşağıdaki alanları boş bırakın.</p>
        <div class="mb-3">
            <label class="form-label">Mevcut Şifre</label>
            <input type="password" name="current_password" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Yeni Şifre</label>
            <input type="password" name="new_password" class="form-control" minlength="8">
        </div>
        <button type="submit" class="btn btn-danger">Kaydet</button>
    </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
