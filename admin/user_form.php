<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin']);

$id = (int)($_GET['id'] ?? 0);
$user = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash_set('error', 'Kullanıcı bulunamadı.');
        redirect('users.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['super_admin', 'editor', 'author'], true) ? $_POST['role'] : 'author';
    $password = $_POST['password'] ?? '';

    if($id === (int)$__admin['id'] && $role !== 'super_admin') $errors[]='Kendi yönetici rolünüzü düşüremezsiniz.';
    if ($username === '' || $fullName === '' || $email === '') $errors[] = 'Tüm zorunlu alanları doldurun.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if (!$id && $password === '') $errors[] = 'Yeni kullanıcı için şifre zorunludur.';
    if ($password !== '' && strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';

    if (!$errors) {
        $checkStmt = db()->prepare('SELECT id FROM admins WHERE username = ?' . ($id ? ' AND id != ?' : ''));
        $checkStmt->execute($id ? [$username, $id] : [$username]);
        if ($checkStmt->fetch()) {
            $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
        }
    }

    if (!$errors) {
        if ($id) {
            if ($password !== '') {
                db()->prepare('UPDATE admins SET username=?, full_name=?, email=?, role=?, password_hash=? WHERE id=?')
                    ->execute([$username, $fullName, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                db()->prepare('UPDATE admins SET username=?, full_name=?, email=?, role=? WHERE id=?')
                    ->execute([$username, $fullName, $email, $role, $id]);
            }
            flash_set('success', 'Kullanıcı güncellendi.');
        } else {
            db()->prepare('INSERT INTO admins (username, full_name, email, role, password_hash) VALUES (?,?,?,?,?)')
                ->execute([$username, $fullName, $email, $role, password_hash($password, PASSWORD_DEFAULT)]);
            flash_set('success', 'Kullanıcı oluşturuldu.');
        }
        redirect('users.php');
    }
}

$__adminTitle = $id ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3"><?= $id ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı Ekle' ?></h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<div class="card-panel" style="max-width:520px;">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Ad Soyad</label>
            <input type="text" name="full_name" class="form-control" required value="<?= e($user['full_name'] ?? ($_POST['full_name'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Kullanıcı Adı</label>
            <input type="text" name="username" class="form-control" required value="<?= e($user['username'] ?? ($_POST['username'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" required value="<?= e($user['email'] ?? ($_POST['email'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="role" class="form-select">
                <?php foreach (['super_admin' => 'Süper Yönetici', 'editor' => 'Editör', 'author' => 'Yazar'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($user['role'] ?? 'author') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Şifre <?= $id ? '(değiştirmek istemiyorsanız boş bırakın)' : '' ?></label>
            <input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?> minlength="8">
        </div>
        <button type="submit" class="btn btn-danger"><?= $id ? 'Güncelle' : 'Oluştur' ?></button>
        <a href="users.php" class="btn btn-outline-secondary">İptal</a>
    </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
