<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin', 'editor']);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $errors[] = 'Kategori adı zorunludur.';
        } else {
            $slug = unique_slug('categories', $name, $id ?: null);
            if ($id) {
                db()->prepare('UPDATE categories SET name=?, slug=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute([$name, $slug, $sortOrder, $isActive, $id]);
                flash_set('success', 'Kategori güncellendi.');
            } else {
                db()->prepare('INSERT INTO categories (name, slug, sort_order, is_active) VALUES (?,?,?,?)')
                    ->execute([$name, $slug, $sortOrder, $isActive]);
                flash_set('success', 'Kategori eklendi.');
            }
            redirect('categories.php');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $countStmt = db()->prepare('SELECT COUNT(*) FROM news WHERE category_id = ?');
        $countStmt->execute([$id]);
        if ((int)$countStmt->fetchColumn() > 0) {
            flash_set('error', 'Bu kategoride haberler var, önce onları taşıyın veya silin.');
        } else {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash_set('success', 'Kategori silindi.');
        }
        redirect('categories.php');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$categories = db()->query('
    SELECT c.*, (SELECT COUNT(*) FROM news WHERE category_id = c.id) AS news_count
    FROM categories c ORDER BY c.sort_order
')->fetchAll();

$__adminTitle = 'Kategoriler';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Kategoriler</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card-panel">
            <h5><?= $editCategory ? 'Kategoriyi Düzenle' : 'Yeni Kategori' ?></h5>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?? '' ?>">
                <div class="mb-3">
                    <label class="form-label">Kategori Adı</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($editCategory['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)($editCategory['sort_order'] ?? 0) ?>">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="catActive" <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="catActive">Aktif</label>
                </div>
                <button type="submit" class="btn btn-danger"><?= $editCategory ? 'Güncelle' : 'Ekle' ?></button>
                <?php if ($editCategory): ?><a href="categories.php" class="btn btn-outline-secondary">İptal</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card-panel">
            <table class="table align-middle">
                <thead><tr><th>Ad</th><th>Sıra</th><th>Haber Sayısı</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= e($c['name']) ?></td>
                        <td><?= (int)$c['sort_order'] ?></td>
                        <td><?= (int)$c['news_count'] ?></td>
                        <td><?= $c['is_active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Pasif</span>' ?></td>
                        <td class="text-nowrap">
                            <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Kategoriyi silmek istediğinize emin misiniz?"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
