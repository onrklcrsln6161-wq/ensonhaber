<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('news.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM news WHERE id = ?');
$stmt->execute([$id]);
$news = $stmt->fetch();

if ($news) {
    if ($__admin['role'] === 'author' && (int)$news['author_id'] !== (int)$__admin['id']) {
        http_response_code(403);
        die('Bu haberi silme yetkiniz yok.');
    }
    if (!news_is_trashed($id)) {
        db()->beginTransaction();
        snapshot_news($id);
        db()->prepare('INSERT INTO news_trash(news_id,actor_id,created_at) VALUES (?,?,?)')->execute([$id,$__admin['id'],date('Y-m-d H:i:s')]);
        db()->prepare("UPDATE news SET status='draft',is_slider=0,is_breaking=0,is_featured=0,updated_at=NOW() WHERE id=?")->execute([$id]);
        editorial_log('news.trashed',$id); db()->commit();
    }
    flash_set('success', 'Haber çöp kutusuna taşındı; geri alabilirsiniz.');
} else {
    flash_set('error', 'Haber bulunamadı.');
}

redirect('news.php');
