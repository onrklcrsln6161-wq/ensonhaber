<?php
// Bu dosya kimlik dogrulamasi olmadan (sunucu cron/curl tarafindan) cagirilir.
// Guvenlik: sadece dogru gizli anahtarla VE admin panelinden "Otomatik Guncelleme"
// acikken calisir. Anahtar admin > Canli Veriler sayfasinda goruntulenir/yenilenir.

require_once __DIR__ . '/../includes/market_api.php';

header('Content-Type: text/plain; charset=utf-8');

$token = $_GET['token'] ?? '';
$expected = get_setting('market_cron_token', '');

if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    echo "Gecersiz anahtar.";
    exit;
}

if (get_setting('market_auto_update_enabled', '0') !== '1') {
    echo "Otomatik guncelleme admin panelinden kapali, islem yapilmadi.";
    exit;
}

// Ayni cron'un cok sik tetiklenip disaridaki API'leri gereksiz yormasini engelle
$last = get_setting('market_last_auto_update', '');
if ($last !== '' && (time() - strtotime($last)) < 60) {
    echo "Cok sik cagrildi, en az 60 saniyede bir calisir.";
    exit;
}

$result = fetch_live_market_rates();
echo "Guncellendi: " . implode(', ', $result['success']);
if ($result['failed']) {
    echo " | Basarisiz: " . implode(', ', $result['failed']);
}
