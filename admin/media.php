<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && !empty($_FILES['file']['name'])) {
        $path = handle_image_upload('file', 'media');
        if ($path) {
            $stmt = db()->prepare('INSERT INTO media (filename, file_path, file_type, file_size, uploaded_by) VALUES (?,?,?,?,?)');
            $stmt->execute([basename($path), $path, $_FILES['file']['type'], $_FILES['file']['size'], $__admin['id']]);
            flash_set('success', 'Dosya yüklendi.');
        } else {
            flash_set('error', 'Dosya yüklenemedi. Sadece jpg, png, webp, gif (max 5MB) kabul edilir.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT * FROM media WHERE id = ?');
        $stmt->execute([$id]);
        $file = $stmt->fetch();
        if ($file) {
            $full = UPLOAD_PATH . '/' . $file['file_path'];
            if (is_file($full)) @unlink($full);
            db()->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            flash_set('success', 'Dosya silindi.');
        }
    }
    redirect('media.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$total = (int)db()->query('SELECT COUNT(*) FROM media')->fetchColumn();
$p = paginate($total, $perPage, $page);
$files = db()->query("SELECT * FROM media ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$p['offset']}")->fetchAll();

$__adminTitle = 'Medya Kütüphanesi';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Medya Kütüphanesi</h3>

<div class="card-panel">
    <form method="post" enctype="multipart/form-data" class="d-flex gap-2 mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <input type="file" name="file" class="form-control" accept="image/*" required>
        <button type="submit" class="btn btn-danger text-nowrap">Yükle</button>
    </form>

    <div class="row g-3">
        <?php foreach ($files as $f): ?>
            <div class="col-6 col-md-3 col-lg-2">
                <div class="border rounded p-2 text-center h-100 d-flex flex-column">
                    <img src="<?= e(image_url($f['file_path'])) ?>" class="rounded mb-2" style="width:100%;aspect-ratio:1;object-fit:cover;">
                    <small class="text-truncate d-block mb-2" title="<?= e($f['filename']) ?>"><?= e($f['filename']) ?></small>
                    <form method="post" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" data-confirm="Silinsin mi?">Sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$files): ?><p class="text-muted">Henüz medya yüklenmedi.</p><?php endif; ?>
    </div>

    <?php if ($p['totalPages'] > 1): ?>
    <nav class="mt-3"><ul class="pagination">
        <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $p['currentPage'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
