<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $ids = $_POST['id'] ?? [];
        foreach ($ids as $idx => $id) {
            db()->prepare('UPDATE market_ticker SET label=?, value=?, change_percent=?, sort_order=? WHERE id=?')
                ->execute([
                    trim($_POST['label'][$idx]),
                    trim($_POST['value'][$idx]),
                    (float)str_replace(',', '.', $_POST['change_percent'][$idx]),
                    (int)$_POST['sort_order'][$idx],
                    (int)$id,
                ]);
        }
        flash_set('success', 'Piyasa verileri güncellendi.');
    } elseif ($action === 'add') {
        db()->prepare('INSERT INTO market_ticker (code, label, value, change_percent, sort_order) VALUES (?,?,?,?,?)')
            ->execute([strtoupper(trim($_POST['code'])), trim($_POST['label']), trim($_POST['value']), 0, 99]);
        flash_set('success', 'Yeni gösterge eklendi.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM market_ticker WHERE id = ?')->execute([(int)$_POST['id']]);
        flash_set('success', 'Gösterge silindi.');
    }
    redirect('market_ticker.php');
}

$items = db()->query('SELECT * FROM market_ticker ORDER BY sort_order')->fetchAll();
$__adminTitle = 'Piyasa Verileri';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Piyasa Verileri (Döviz / Altın / Borsa Bandı)</h3>
<p class="text-muted">Ana sayfanın üstünde kayan döviz kuru bandındaki değerleri buradan elle güncelleyebilirsiniz. USD/EUR/GBP/Bitcoin için otomatik canlı güncelleme açmak isterseniz <a href="live_data.php">Canlı Veriler</a> sayfasına göz atın.</p>

<div class="card-panel">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <table class="table align-middle">
            <thead><tr><th>Kod</th><th>Etiket</th><th>Değer</th><th>Değişim %</th><th>Sıra</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $t): ?>
                <tr>
                    <td><?= e($t['code']) ?><input type="hidden" name="id[]" value="<?= $t['id'] ?>"></td>
                    <td><input type="text" name="label[]" class="form-control form-control-sm" value="<?= e($t['label']) ?>"></td>
                    <td><input type="text" name="value[]" class="form-control form-control-sm" value="<?= e($t['value']) ?>"></td>
                    <td><input type="text" name="change_percent[]" class="form-control form-control-sm" value="<?= e($t['change_percent']) ?>"></td>
                    <td><input type="number" name="sort_order[]" class="form-control form-control-sm" value="<?= (int)$t['sort_order'] ?>" style="width:80px;"></td>
                    <td>
                        <button type="submit" form="delTicker<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Silinsin mi?">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-danger">Tümünü Kaydet</button>
    </form>

    <?php foreach ($items as $t): ?>
        <form id="delTicker<?= $t['id'] ?>" method="post" style="display:none;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
        </form>
    <?php endforeach; ?>
</div>

<div class="card-panel">
    <h5>Yeni Gösterge Ekle</h5>
    <form method="post" class="row g-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="col-md-3"><input type="text" name="code" class="form-control" placeholder="Kod (ör. USD)" required></div>
        <div class="col-md-3"><input type="text" name="label" class="form-control" placeholder="Etiket (ör. DOLAR)" required></div>
        <div class="col-md-3"><input type="text" name="value" class="form-control" placeholder="Değer (ör. 48,60)" required></div>
        <div class="col-md-3"><button type="submit" class="btn btn-outline-danger w-100">Ekle</button></div>
    </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
