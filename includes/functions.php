<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/publishing.php';
require_once __DIR__ . '/content.php';

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Disardan JSON ceken kucuk yardimci (curl varsa onu, yoksa file_get_contents'i kullanir).
 * Basarisiz olursa null doner - cagiran taraf bunu ele almali.
 */
function http_get_json(string $url, int $timeoutSec = 6): ?array
{
    $data = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_CONNECTTIMEOUT => $timeoutSec,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // bazi API'ler (ornek: CoinGecko) HTTP/2 istemcilerini bot korumasinda engelliyor
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; HaberPortali/1.0; +https://bilsenneoldu.com.tr)',
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_string($body) && $body !== '' && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) $data = $decoded;
        }
    }

    if ($data === null && ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => [
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
            'header' => "User-Agent: Mozilla/5.0 (compatible; HaberPortali/1.0)\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        // yonlendirme (redirect) oldugunda $http_response_header tum hoplarin basliklarini
        // sirayla icerir; asil sonuc son "HTTP/..." satirindadir, ilkinde degil.
        $statusLines = array_filter($http_response_header ?? [], fn($h) => stripos($h, 'HTTP/') === 0);
        $lastStatusLine = end($statusLines) ?: '';
        if ($body !== false && (str_contains($lastStatusLine, ' 200 ') || $lastStatusLine === '')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) $data = $decoded;
        }
    }

    return $data;
}

function slugify(string $text): string
{
    $map = [
        'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i',
        'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text === '' ? uniqid() : $text;
}

function unique_slug(string $table, string $baseSlug, ?int $excludeId = null): string
{
    $pdo = db();
    $slug = slugify($baseSlug);
    $original = $slug;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?" . ($excludeId ? ' AND id != ?' : '');
        $params = $excludeId ? [$slug, $excludeId] : [$slug];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $original . '-' . (++$i);
    }
}

function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 2592000) return floor($diff / 86400) . ' gün önce';
    return format_date($datetime);
}

function format_date(string $datetime, string $format = 'd.m.Y H:i'): string
{
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function turkish_date(?string $datetime = null): string
{
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    $ts = $datetime ? strtotime($datetime) : time();
    return $days[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function excerpt(string $text, int $length = 150): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Gecersiz istek (CSRF dogrulamasi basarisiz). Lutfen formu yeniden yukleyip deneyin.');
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Yuklenen resmi guvenli sekilde uploads/news klasorune tasir.
 * Basarili olursa dosya adini, olmazsa null doner.
 */
function handle_image_upload(string $inputName, string $subdir = 'news'): ?string
{
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $ext = $allowed[$mime];
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetDir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $target = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return $subdir . '/' . $filename;
}

function image_url(?string $path): string
{
    if (!$path) {
        return BASE_URL . '/assets/img/placeholder.svg';
    }
    return UPLOAD_URL . '/' . ltrim($path, '/');
}

function news_url(array $n): string
{
    return BASE_URL . '/news.php?slug=' . urlencode($n['slug']);
}

function category_url(array $c): string
{
    return BASE_URL . '/category.php?slug=' . urlencode($c['slug']);
}

function news_card_html(array $n): string
{
    ob_start(); ?>
    <div class="news-card">
        <a class="thumb" href="<?= e(news_url($n)) ?>">
            <img src="<?= e(image_url($n['cover_image'])) ?>" alt="<?= e($n['title']) ?>" loading="lazy">
        </a>
        <div class="body">
            <a class="cat" href="<?= e(category_url(['slug' => $n['category_slug']])) ?>"><?= e($n['category_name']) ?></a>
            <h3><a href="<?= e(news_url($n)) ?>"><?= e($n['title']) ?></a></h3>
            <div class="meta"><?= e(time_ago($n['published_at'] ?? $n['created_at'])) ?></div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function news_row_html(array $n): string
{
    ob_start(); ?>
    <div class="news-list-row">
        <a class="thumb" href="<?= e(news_url($n)) ?>">
            <img src="<?= e(image_url($n['cover_image'])) ?>" alt="<?= e($n['title']) ?>" loading="lazy">
        </a>
        <div class="body">
            <a class="cat" href="<?= e(category_url(['slug' => $n['category_slug']])) ?>"><?= e($n['category_name']) ?></a>
            <h3><a href="<?= e(news_url($n)) ?>"><?= e($n['title']) ?></a></h3>
            <div class="meta"><?= e(time_ago($n['published_at'] ?? $n['created_at'])) ?></div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function paginate(int $totalItems, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return compact('totalPages', 'currentPage', 'offset');
}
