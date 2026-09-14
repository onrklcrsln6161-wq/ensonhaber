<?php
require_once __DIR__ . '/../../includes/auth.php';
$__admin = require_login();
$__current = basename($_SERVER['SCRIPT_NAME']);

function nav_active(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($__adminTitle ?? 'Yönetim Paneli') ?> - <?= e(get_setting('site_name')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/admin/assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-newspaper"></i> <span><?= e(panel_role_name($__admin['role'])) ?></span>
        </div>
        <nav class="sidebar-nav">
            <?php if (panel_can_access($__admin['role'], 'index.php')): ?><a href="index.php" class="<?= nav_active('index.php', $__current) ?>"><i class="bi bi-speedometer2"></i> Kontrol Paneli</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'news.php')): ?><a href="news.php" class="<?= nav_active('news.php', $__current) ?>"><i class="bi bi-file-earmark-text"></i> Haberler</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'news_form.php')): ?><a href="news_form.php" class="<?= nav_active('news_form.php', $__current) ?>"><i class="bi bi-plus-circle"></i> Yeni Haber</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'categories.php')): ?><a href="categories.php" class="<?= nav_active('categories.php', $__current) ?>"><i class="bi bi-tags"></i> Kategoriler</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'sliders.php')): ?><a href="sliders.php" class="<?= nav_active('sliders.php', $__current) ?>"><i class="bi bi-images"></i> Slider Yönetimi</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'comments.php')): ?><a href="comments.php" class="<?= nav_active('comments.php', $__current) ?>"><i class="bi bi-chat-dots"></i> Yorumlar</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'media.php')): ?><a href="media.php" class="<?= nav_active('media.php', $__current) ?>"><i class="bi bi-image"></i> Medya Kütüphanesi</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'market_ticker.php')): ?><a href="market_ticker.php" class="<?= nav_active('market_ticker.php', $__current) ?>"><i class="bi bi-graph-up"></i> Piyasa Verileri</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'live_data.php')): ?><a href="live_data.php" class="<?= nav_active('live_data.php', $__current) ?>"><i class="bi bi-broadcast"></i> Canlı Veriler</a><?php endif; ?>
            <?php if ($__admin['role'] === 'super_admin'): ?>
            <?php if (panel_can_access($__admin['role'], 'users.php')): ?><a href="users.php" class="<?= nav_active('users.php', $__current) ?>"><i class="bi bi-people"></i> Kullanıcılar</a><?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'settings.php')): ?><a href="settings.php" class="<?= nav_active('settings.php', $__current) ?>"><i class="bi bi-gear"></i> Site Ayarları</a><?php endif; ?>
            <?php endif; ?>
            <?php if (panel_can_access($__admin['role'], 'profile.php')): ?><a href="profile.php" class="<?= nav_active('profile.php', $__current) ?>"><i class="bi bi-person-circle"></i> Profilim</a><?php endif; ?>
            <?php if ($__admin['role']==='super_admin'): ?>
            <a href="trash.php">Çöp Kutusu</a><a href="audit.php">İşlem Günlüğü</a><a href="readiness.php">Yayın Hazırlığı</a>
            <a href="ai_settings.php">Yapay Zekâ Ayarları</a><a href="social.php"><i class="bi bi-share"></i> Sosyal Medya Hesapları</a>
            <a href="pages.php"><i class="bi bi-info-circle"></i> Kurumsal Sayfalar</a>
            <?php endif; ?>
            <?php if(panel_can_access($__admin['role'],'feeds.php')): ?><a href="feeds.php"><i class="bi bi-rss"></i> Dünya Haber Botu</a><?php endif; ?>
            <a href="<?= e(BASE_URL) ?>/index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Siteyi Görüntüle</a>
            <?php if (panel_can_access($__admin['role'], 'logout.php')): ?><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Çıkış Yap</a><?php endif; ?>
        </nav>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="admin-user"><i class="bi bi-person-fill"></i> <?= e($__admin['full_name']) ?> <small class="text-muted">(<?= e($__admin['role']) ?>)</small></span>
            </div>
        </header>
        <main class="admin-content">
            <?php if ($flash = flash_get()): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
