<?php
function panel_role_name(string $role): string { return ['super_admin'=>'Yönetici Paneli','editor'=>'Editör Paneli','author'=>'Yazar Paneli'][$role] ?? 'Yetkisiz'; }
function panel_path(string $role): string { return ['super_admin'=>'admin','editor'=>'editor','author'=>'author'][$role] ?? 'admin'; }
function panel_can_access(string $role, string $page): bool {
    $common = ['index.php','news.php','news_form.php','profile.php','ai_assist.php','logout.php'];
    $editor = ['feeds.php','revisions.php','categories.php','sliders.php','comments.php','media.php'];
    $admin = ['feed_sources.php','ai_settings.php','trash.php','audit.php','readiness.php','news_delete.php','users.php','user_form.php','settings.php','social.php','pages.php','market_ticker.php','live_data.php'];
    return in_array($role, ['super_admin','editor','author'], true) &&
        (in_array($page, $common, true) || ($role !== 'author' && in_array($page, $editor, true)) || ($role === 'super_admin' && in_array($page, $admin, true)));
}
function can_edit_news(array $user, array $news): bool {
    return in_array($user['role'], ['super_admin','editor'], true) ||
        ($user['role'] === 'author' && (int)$user['id'] === (int)$news['author_id'] && in_array($news['status'], ['draft','pending'], true));
}
function allowed_news_statuses(string $role): array {
    return $role === 'author' ? ['draft'=>'Taslak','pending'=>'Editöre gönder'] : ['draft'=>'Taslak','pending'=>'Onay Bekliyor','published'=>'Yayınla'];
}
