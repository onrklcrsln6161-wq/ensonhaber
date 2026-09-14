<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/editorial.php';

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $stmt = db()->prepare('SELECT id, username, full_name, email, role, avatar FROM admins WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: false;
    }
    return $admin ?: null;
}

function require_login(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('login.php');
    }
    $page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $area = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'));
    if (!panel_can_access($admin['role'], $page) || (in_array($area, ['editor','author'], true) && $area !== panel_path($admin['role']))) {
        http_response_code(403); exit('Bu işlem için yetkiniz yok.');
    }
    if ($area === 'admin' && $admin['role'] !== 'super_admin') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(403); exit('Kendi panelinizi kullanın.'); }
        redirect(BASE_URL . '/' . panel_path($admin['role']) . '/' . $page . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }
    return $admin;
}

function require_role(array $roles): array
{
    $admin = require_login();
    if (!in_array($admin['role'], $roles, true)) {
        http_response_code(403);
        die('Bu islem icin yetkiniz yok.');
    }
    return $admin;
}

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Bu IP son LOGIN_LOCKOUT_MINUTES icinde LOGIN_MAX_ATTEMPTS'i asti mi? */
function is_login_locked(string $ip): bool
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND created_at > ?");
    $cutoff = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $stmt->execute([$ip, $cutoff]);
    return (int)$stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(string $ip, string $username): void
{
    db()->prepare('INSERT INTO login_attempts (ip_address, username, created_at) VALUES (?, ?, ?)')->execute([$ip, mb_substr($username, 0, 50), date('Y-m-d H:i:s')]);
    // Eski kayitlari zaman zaman temizle (tabloyu sisirmesin)
    if (random_int(1, 20) === 1) {
        $cutoff = date('Y-m-d H:i:s', time() - 24 * 3600);
        db()->prepare('DELETE FROM login_attempts WHERE created_at < ?')->execute([$cutoff]);
    }
}

function clear_login_attempts(string $ip): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}

function attempt_login(string $username, string $password): bool
{
    if (is_login_locked(client_ip())) return false;
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $upd = db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
        $upd->execute([$admin['id']]);
        clear_login_attempts(client_ip());
        return true;
    }
    record_failed_login(client_ip(), $username);
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
