<?php
require_once __DIR__ . '/../includes/auth.php';
$__admin = require_login();

$id = (int)($_GET['id'] ?? 0);
$news = null;
$newsTags = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if (!$news || news_is_trashed($id)) {
        flash_set('error', 'Haber bulunamadı.');
        redirect('news.php');
    }
    if (!can_edit_news($__admin, $news)) {
        http_response_code(403);
        die('Bu haberi düzenleme yetkiniz yok.');
    }
    $tagStmt = db()->prepare('SELECT name FROM tags t JOIN news_tags nt ON nt.tag_id = t.id WHERE nt.news_id = ?');
    $tagStmt->execute([$id]);
    $newsTags = implode(', ', array_column($tagStmt->fetchAll(), 'name'));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = clean_article_html($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'pending'], true) ? $_POST['status'] : 'draft';
    if (!array_key_exists($status, allowed_news_statuses($__admin['role']))) { http_response_code(403); exit('Haber yayımlama yetkiniz yok.'); }
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $isSlider = isset($_POST['is_slider']) ? 1 : 0;
    $sliderOrder = (int)($_POST['slider_order'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    if ($__admin['role'] === 'author') { $isBreaking = (int)($news['is_breaking'] ?? 0); $isSlider = (int)($news['is_slider'] ?? 0); $sliderOrder = (int)($news['slider_order'] ?? 0); $isFeatured = (int)($news['is_featured'] ?? 0); }
    $tagsInput = trim($_POST['tags'] ?? '');

    if ($title === '') $errors[] = 'Başlık zorunludur.';
    if (mb_strlen($title) > 255 || mb_strlen($summary) > 500) $errors[] = 'Başlık 255, özet 500 karakteri aşamaz.';
    if ($content === '' || trim(strip_tags($content)) === '') $errors[] = 'İçerik zorunludur.';
    $categoryCheck=db()->prepare('SELECT 1 FROM categories WHERE id=? AND is_active=1');$categoryCheck->execute([$categoryId]);
    if (!$categoryCheck->fetchColumn()) $errors[] = 'Aktif bir kategori seçmelisiniz.';
    $tagNames=array_values(array_unique(array_filter(array_map('trim',explode(',',$tagsInput)))));
    if(count($tagNames)>30) $errors[]='En fazla 30 etiket girebilirsiniz.';
    foreach($tagNames as $tagName) if(mb_strlen($tagName)>60) { $errors[]='Etiketler en fazla 60 karakter olabilir.'; break; }

    if (!$errors) {
      try {
        $slug = $news['slug'] ?? unique_slug('news', $title);
        $coverImage = $news['cover_image'] ?? null;
        $uploaded = handle_image_upload('cover_image', 'news');
        if ($uploaded) $coverImage = $uploaded;

        db()->beginTransaction();
        if ($id) {
            $freshStmt=db()->prepare('SELECT * FROM news WHERE id=?'.(DB_HOST==='sqlite'?'':' FOR UPDATE'));$freshStmt->execute([$id]);$fresh=$freshStmt->fetch();
            if(!$fresh || news_is_trashed($id) || !can_edit_news($__admin,$fresh)) throw new RuntimeException('Haberin durumu veya yetkiniz değişti. Sayfayı yenileyin.');
            if(!hash_equals(news_edit_token($fresh),(string)($_POST['edit_token']??''))) throw new RuntimeException('Bu haber başka bir işlemde değiştirildi. Metninizi kopyalayıp sayfayı yenileyin; değişikliklerin üzerine yazılmadı.');
            snapshot_news($id);
            $sql = "UPDATE news SET updated_at=NOW(), title=?, slug=?, summary=?, content=?, cover_image=?, category_id=?, status=?,
                    is_breaking=?, is_slider=?, slider_order=?, is_featured=?, published_at = CASE WHEN ? = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END
                    WHERE id=?";
            db()->prepare($sql)->execute([$title, $slug, $summary, $content, $coverImage, $categoryId, $status, $isBreaking, $isSlider, $sliderOrder, $isFeatured, $status, $id]);
            $newsId = $id;
        } else {
            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
            $sql = "INSERT INTO news (title, slug, summary, content, cover_image, category_id, author_id, status,
                    is_breaking, is_slider, slider_order, is_featured, published_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            db()->prepare($sql)->execute([$title, $slug, $summary, $content, $coverImage, $categoryId, $__admin['id'], $status, $isBreaking, $isSlider, $sliderOrder, $isFeatured, $publishedAt]);
            $newsId = (int)db()->lastInsertId();
        }

        // Etiketleri senkronize et
        db()->prepare('DELETE FROM news_tags WHERE news_id = ?')->execute([$newsId]);
        if ($tagsInput !== '') {
            $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));
            foreach ($tagNames as $tagName) {
                $tagSlug = slugify($tagName);
                $find = db()->prepare('SELECT id FROM tags WHERE slug = ?');
                $find->execute([$tagSlug]);
                $tagId = $find->fetchColumn();
                if (!$tagId) {
                    db()->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tagSlug]);
                    $tagId = db()->lastInsertId();
                }
                $exists = db()->prepare('SELECT 1 FROM news_tags WHERE news_id = ? AND tag_id = ?');
                $exists->execute([$newsId, $tagId]);
                if (!$exists->fetchColumn()) {
                    db()->prepare('INSERT INTO news_tags (news_id, tag_id) VALUES (?, ?)')->execute([$newsId, $tagId]);
                }
            }
        }

        // Galeri gorselleri
        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['name'] as $idx => $name) {
                if ($_FILES['gallery']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $_FILES['__single'] = [
                    'name' => $_FILES['gallery']['name'][$idx],
                    'type' => $_FILES['gallery']['type'][$idx],
                    'tmp_name' => $_FILES['gallery']['tmp_name'][$idx],
                    'error' => $_FILES['gallery']['error'][$idx],
                    'size' => $_FILES['gallery']['size'][$idx],
                ];
                $path = handle_image_upload('__single', 'news');
                if ($path) {
                    db()->prepare('INSERT INTO news_gallery (news_id, image_path, sort_order) VALUES (?, ?, ?)')->execute([$newsId, $path, $idx]);
                }
            }
        }

        editorial_log($id ? 'news.updated' : 'news.created', $newsId);
        db()->commit();
        flash_set('success', $id ? 'Haber güncellendi.' : 'Haber oluşturuldu.');
        redirect('news_form.php?id=' . $newsId);
      } catch(Throwable $error) {
        if(db()->inTransaction()) db()->rollBack();
        $errors[]=$error instanceof PDOException ? 'Haber kaydedilemedi. Verileriniz formda korundu; yeniden deneyin.' : $error->getMessage();
      }
    }
}

if ($errors) {
    $news = array_merge($news ?? [], ['title'=>$title, 'summary'=>$summary, 'content'=>$content, 'category_id'=>$categoryId, 'status'=>$status, 'is_breaking'=>$isBreaking, 'is_slider'=>$isSlider, 'slider_order'=>$sliderOrder, 'is_featured'=>$isFeatured]);
    $newsTags = $tagsInput;
}
$categories = db()->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY sort_order')->fetchAll();
$__adminTitle = $id ? 'Haberi Düzenle' : 'Yeni Haber';
require __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">

<?php if ($id && $__admin['role'] !== 'author'): ?><p><a href="revisions.php?id=<?= $id ?>">Haber revizyon geçmişi</a></p><?php endif; ?>
<?php if($id): $fs=db()->prepare('SELECT source_url,source_title FROM feed_imports WHERE news_id=?');$fs->execute([$id]);$source=$fs->fetch();if($source): ?><div class="alert alert-info">RSS kaynağı: <a href="<?= e($source['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($source['source_title']) ?></a>. Kaynağı inceleyip içeriği düzenledikten sonra yayımlayın.</div><?php endif; endif; ?>
<h3 class="mb-3"><?= $id ? 'Haberi Düzenle' : 'Yeni Haber Ekle' ?></h3>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<section class="card-panel" id="ai-panel" aria-label="Yapay zekâ editör yardımcısı">
<h2 class="h5">Yapay zekâ editör yardımcısı</h2>
<p class="small text-muted">Öneri oluşturduğunuzda başlık, spot ve içerik OpenAI API'ye gönderilir. Öneriler doğrulanmış haber değildir; kontrol ederek uygulayın. Haber otomatik kaydedilmez veya yayımlanmaz.</p>
<label class="form-label" for="ai-task">İşlem</label><select id="ai-task" class="form-select mb-2"><option value="title">Başlık öner</option><option value="summary">Spot hazırla</option><option value="content">Türkçe haber dilini düzenle</option></select>
<button type="button" class="btn btn-outline-primary" id="ai-generate">Öneri oluştur</button>
<p id="ai-status" class="mt-2" role="status" aria-live="polite"></p>
<div id="ai-result" hidden><label for="ai-suggestion" class="form-label">Öneriyi incele / düzenle</label><textarea id="ai-suggestion" class="form-control mb-2" rows="6"></textarea><button type="button" class="btn btn-outline-success" id="ai-apply">Seçilen alana uygula</button></div>
<button type="button" class="btn btn-outline-secondary mt-2" id="ai-undo" hidden>Son uygulamayı geri al</button>
</section>
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if($id): ?><input type="hidden" name="edit_token" value="<?= e($_POST['edit_token'] ?? news_edit_token($news)) ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel">
                <div class="mb-3">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($news['title'] ?? ($_POST['title'] ?? '')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Spot / Özet</label>
                    <textarea name="summary" class="form-control" rows="2" maxlength="500"><?= e($news['summary'] ?? ($_POST['summary'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">İçerik *</label>
                    <div id="editor" style="height:320px;background:#fff;" hidden></div>
                    <textarea name="content" id="contentInput" class="form-control" rows="14"><?= e(clean_article_html($news['content'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Galeri (opsiyonel, birden fazla seçebilirsiniz)</label>
                    <input type="file" name="gallery[]" class="form-control" multiple accept="image/*">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-panel">
                <div class="mb-3">
                    <label class="form-label">Kategori *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)($news['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <?php foreach (allowed_news_statuses($__admin['role']) as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($news['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kapak Görseli</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                    <?php if (!empty($news['cover_image'])): ?>
                        <img src="<?= e(image_url($news['cover_image'])) ?>" class="mt-2 rounded" style="max-width:100%;">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Etiketler (virgülle ayırın)</label>
                    <input type="text" name="tags" class="form-control" value="<?= e($newsTags) ?>" placeholder="ekonomi, dolar, borsa">
                </div>
                <?php if ($__admin['role'] !== 'author'): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_breaking" id="isBreaking" <?= !empty($news['is_breaking']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isBreaking">Son Dakika (kayan bantta göster)</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?= !empty($news['is_featured']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isFeatured">Öne Çıkan Haber</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_slider" id="isSlider" <?= !empty($news['is_slider']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isSlider">Ana Sayfa Slider'ında Göster</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slider Sırası</label>
                    <input type="number" name="slider_order" class="form-control" value="<?= (int)($news['slider_order'] ?? 0) ?>">
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-danger w-100"><?= $id ? 'Güncelle' : 'Kaydet' ?></button>
            </div>
        </div>
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
var input = document.getElementById('contentInput');
if (typeof Quill !== 'undefined') {
    document.getElementById('editor').hidden = false;
    var quill = new Quill('#editor', { theme: 'snow' });
    quill.clipboard.dangerouslyPasteHTML(input.value);
    input.hidden = true;
    input.closest('form').addEventListener('submit', function () { input.value = quill.root.innerHTML; });
}
</script>

<script src="<?= e(BASE_URL) ?>/admin/assets/js/ai-assist.js"></script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
