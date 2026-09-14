<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/weather.php';
require_once __DIR__ . '/../includes/market_api.php';
$__admin = require_role(['super_admin']);

$marketResult = null;
$weatherResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_weather') {
        $city = trim($_POST['weather_city'] ?? '');
        $lat = trim($_POST['weather_lat'] ?? '');
        $lon = trim($_POST['weather_lon'] ?? '');
        if ($city !== '' && is_numeric($lat) && is_numeric($lon)) {
            $stmt = db()->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
            $stmt->execute([$city, 'weather_city']);
            $stmt->execute([$lat, 'weather_lat']);
            $stmt->execute([$lon, 'weather_lon']);
            refresh_weather();
            flash_set('success', 'Hava durumu konumu güncellendi.');
        } else {
            flash_set('error', 'Geçerli bir şehir ve koordinat girin.');
        }
        redirect('live_data.php');
    } elseif ($action === 'refresh_weather') {
        $weatherResult = refresh_weather();
        flash_set($weatherResult ? 'success' : 'error', $weatherResult ? 'Hava durumu güncellendi.' : 'Hava durumu servisine ulaşılamadı, lütfen tekrar deneyin.');
        redirect('live_data.php');
    } elseif ($action === 'refresh_market') {
        $result = fetch_live_market_rates();
        $msg = 'Güncellenen: ' . implode(', ', $result['success']);
        if ($result['failed']) $msg .= ' — Başarısız: ' . implode(', ', $result['failed']);
        flash_set($result['success'] ? 'success' : 'error', $msg);
        redirect('live_data.php');
    } elseif ($action === 'toggle_auto') {
        $current = get_setting('market_auto_update_enabled', '0');
        $stmt = db()->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'market_auto_update_enabled'");
        $stmt->execute([$current === '1' ? '0' : '1']);
        redirect('live_data.php');
    } elseif ($action === 'regenerate_token') {
        db()->prepare("UPDATE settings SET setting_value = '' WHERE setting_key = 'market_cron_token'")->execute();
        get_or_create_cron_token();
        flash_set('success', 'Cron erişim anahtarı yenilendi. Eski bağlantı artık çalışmayacak.');
        redirect('live_data.php');
    }
}

$weatherCity = get_setting('weather_city', 'İstanbul');
$weatherLat = get_setting('weather_lat', '41.0082');
$weatherLon = get_setting('weather_lon', '28.9784');
$currentWeather = get_weather();
$autoUpdateEnabled = get_setting('market_auto_update_enabled', '0') === '1';
$lastAutoUpdate = get_setting('market_last_auto_update', '');
$cronToken = get_or_create_cron_token();
$cronUrl = BASE_URL . '/cron/update_market.php?token=' . $cronToken;

$__adminTitle = 'Canlı Veriler';
require __DIR__ . '/includes/admin_header.php';
?>

<h3 class="mb-3">Canlı Veri Kaynakları</h3>
<p class="text-muted">Sitedeki hava durumu ve piyasa bandını (döviz/bitcoin) ücretsiz, anahtar gerektirmeyen açık API'lerden otomatik besleyebilirsiniz.</p>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <h5 class="mb-3"><i class="bi bi-cloud-sun"></i> Hava Durumu</h5>

            <?php if ($currentWeather): ?>
                <div class="d-flex align-items-center gap-3 mb-3 p-3" style="background:#f6f7fb;border-radius:8px;">
                    <div style="font-size:40px;color:var(--admin-accent);"><i class="bi <?= e(weather_icon($currentWeather['weathercode'], $currentWeather['is_day'])) ?>"></i></div>
                    <div>
                        <div class="fw-bold"><?= e($currentWeather['city']) ?> — <?= (int)$currentWeather['temperature'] ?>°C</div>
                        <div class="text-muted small"><?= e(weather_label($currentWeather['weathercode'])) ?> · Rüzgar <?= (int)($currentWeather['windspeed'] ?? 0) ?> km/s</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Hava durumu servisine henüz ulaşılamadı.</div>
            <?php endif; ?>

            <form method="post" class="row g-2 mb-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_weather">
                <div class="col-12">
                    <label class="form-label">Hazır Şehir Seç</label>
                    <select class="form-select" id="citySelect">
                        <option value="">— Manuel koordinat gir —</option>
                        <?php foreach (weather_city_presets() as $name => $coords): ?>
                            <option value="<?= e($name) ?>" data-lat="<?= $coords[0] ?>" data-lon="<?= $coords[1] ?>" <?= $weatherCity === $name ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Şehir Adı</label>
                    <input type="text" name="weather_city" id="weatherCityInput" class="form-control" value="<?= e($weatherCity) ?>" required>
                </div>
                <div class="col-3">
                    <label class="form-label">Enlem</label>
                    <input type="text" name="weather_lat" id="weatherLatInput" class="form-control" value="<?= e($weatherLat) ?>" required>
                </div>
                <div class="col-3">
                    <label class="form-label">Boylam</label>
                    <input type="text" name="weather_lon" id="weatherLonInput" class="form-control" value="<?= e($weatherLon) ?>" required>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-danger btn-sm">Konumu Kaydet ve Güncelle</button>
                </div>
            </form>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="refresh_weather">
                <button type="submit" class="btn btn-outline-secondary btn-sm">🔄 Şimdi Yenile</button>
            </form>
            <p class="text-muted small mt-2 mb-0">Kaynak: Open-Meteo (ücretsiz, anahtarsız). Veriler 30 dakikada bir otomatik tazelenir.</p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-panel h-100">
            <h5 class="mb-3"><i class="bi bi-graph-up-arrow"></i> Canlı Piyasa Verisi</h5>
            <p class="small">USD, EUR, GBP ve Bitcoin/TL kurları açık API'lerden otomatik çekilip üst banda yansıtılabilir. Altın, gümüş ve BIST değerleri için ücretsiz güvenilir bir kaynak olmadığından bu değerler <a href="market_ticker.php">Piyasa Verileri</a> sayfasından elle girilmeye devam eder.</p>

            <div class="mb-3">
                <span class="badge text-bg-<?= $lastAutoUpdate ? 'success' : 'secondary' ?>">
                    <?= $lastAutoUpdate ? 'Son güncelleme: ' . e(format_date($lastAutoUpdate)) : 'Henüz otomatik güncelleme yapılmadı' ?>
                </span>
            </div>

            <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="refresh_market">
                <button type="submit" class="btn btn-danger btn-sm">🔄 Şimdi Canlı Verilerle Güncelle</button>
            </form>

            <hr>

            <h6>Otomatik Güncelleme (Cron)</h6>
            <p class="small text-muted">Sunucunuzda bir zamanlanmış görev (cron job) kurarsanız kurlar sizin müdahale etmenize gerek kalmadan düzenli aralıklarla otomatik güncellenir. Anahtar birinin eline geçse bile aşağıdaki anahtar kapalıyken cron çağrısı hiçbir şey yapmaz.</p>

            <form method="post" class="mb-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_auto">
                <button type="submit" class="btn btn-sm <?= $autoUpdateEnabled ? 'btn-success' : 'btn-outline-secondary' ?>">
                    <?= $autoUpdateEnabled ? '✓ Otomatik Güncelleme Açık' : 'Otomatik Güncelleme Kapalı' ?>
                </button>
            </form>

            <div class="mb-2">
                <label class="form-label small">Cron URL'si (örn. her 10 dakikada bir çağırın)</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" readonly value="<?= e($cronUrl) ?>" onclick="this.select()">
                </div>
            </div>
            <code class="small d-block mb-2">*/10 * * * * curl -s "<?= e($cronUrl) ?>" &gt;/dev/null 2&gt;&amp;1</code>
            <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="regenerate_token">
                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Anahtar yenilenirse eski cron bağlantısı çalışmayı keser, emin misiniz?">Anahtarı Yenile</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('citySelect').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('weatherCityInput').value = opt.value;
        document.getElementById('weatherLatInput').value = opt.dataset.lat;
        document.getElementById('weatherLonInput').value = opt.dataset.lon;
    }
});
</script>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
