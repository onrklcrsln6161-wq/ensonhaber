<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_admin()) {
    redirect(BASE_URL . '/' . panel_path(current_admin()['role']) . '/index.php');
}

$error = null;
$locked = is_login_locked(client_ip());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (attempt_login($username, $password)) {
        redirect(BASE_URL . '/' . panel_path(current_admin()['role']) . '/index.php');
    }
    $locked = is_login_locked(client_ip());
    $error = $locked
        ? 'Çok fazla hatalı deneme yapıldı. Güvenlik nedeniyle ' . LOGIN_LOCKOUT_MINUTES . ' dakika giriş kilitlendi.'
        : 'Kullanıcı adı veya şifre hatalı.';
} elseif ($locked) {
    $error = 'Çok fazla hatalı deneme yapıldı. Güvenlik nedeniyle ' . LOGIN_LOCKOUT_MINUTES . ' dakika bekleyin.';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Girişi - <?= e(get_setting('site_name')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/admin/assets/css/admin.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="text-center mb-4">
            <i class="bi bi-newspaper" style="font-size:34px;color:#e30613;"></i>
            <h4 class="mt-2 mb-0"><?= e(get_setting('site_name')) ?></h4>
            <small class="text-muted">Yönetici · Editör · Yazar Girişi</small>
        </div>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <fieldset <?= $locked ? 'disabled' : '' ?>>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger w-100">Giriş Yap</button>
        </form>
        </fieldset>
        <?php if (!$locked): ?>
        <p class="text-center text-muted mt-3 mb-0" style="font-size:12px;">Varsayılan: admin / Admin123!</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
