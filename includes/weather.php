<?php
require_once __DIR__ . '/functions.php';

const WEATHER_CACHE_SECONDS = 1800; // 30 dakika

// [etiket, gunduz Bootstrap Icons sinifi, gece Bootstrap Icons sinifi]
const WEATHER_CODE_MAP = [
    0 => ['Açık', 'bi-sun-fill', 'bi-moon-stars-fill'],
    1 => ['Az Bulutlu', 'bi-cloud-sun-fill', 'bi-cloud-moon-fill'],
    2 => ['Parçalı Bulutlu', 'bi-cloud-sun-fill', 'bi-cloud-moon-fill'],
    3 => ['Kapalı', 'bi-clouds-fill', 'bi-clouds-fill'],
    45 => ['Sisli', 'bi-cloud-fog2-fill', 'bi-cloud-fog2-fill'],
    48 => ['Kırağılı Sis', 'bi-cloud-fog2-fill', 'bi-cloud-fog2-fill'],
    51 => ['Hafif Çiseleyen Yağmur', 'bi-cloud-drizzle-fill', 'bi-cloud-drizzle-fill'],
    53 => ['Çiseleyen Yağmur', 'bi-cloud-drizzle-fill', 'bi-cloud-drizzle-fill'],
    55 => ['Yoğun Çiseleyen Yağmur', 'bi-cloud-drizzle-fill', 'bi-cloud-drizzle-fill'],
    61 => ['Hafif Yağmurlu', 'bi-cloud-rain-fill', 'bi-cloud-rain-fill'],
    63 => ['Yağmurlu', 'bi-cloud-rain-fill', 'bi-cloud-rain-fill'],
    65 => ['Şiddetli Yağmurlu', 'bi-cloud-rain-heavy-fill', 'bi-cloud-rain-heavy-fill'],
    71 => ['Hafif Kar Yağışlı', 'bi-cloud-snow-fill', 'bi-cloud-snow-fill'],
    73 => ['Kar Yağışlı', 'bi-cloud-snow-fill', 'bi-cloud-snow-fill'],
    75 => ['Yoğun Kar Yağışlı', 'bi-cloud-snow-fill', 'bi-cloud-snow-fill'],
    80 => ['Sağanak Yağışlı', 'bi-cloud-rain-fill', 'bi-cloud-rain-fill'],
    81 => ['Kuvvetli Sağanak', 'bi-cloud-rain-heavy-fill', 'bi-cloud-rain-heavy-fill'],
    82 => ['Şiddetli Sağanak', 'bi-cloud-rain-heavy-fill', 'bi-cloud-rain-heavy-fill'],
    95 => ['Gök Gürültülü Fırtına', 'bi-cloud-lightning-rain-fill', 'bi-cloud-lightning-rain-fill'],
    96 => ['Dolulu Fırtına', 'bi-cloud-lightning-rain-fill', 'bi-cloud-lightning-rain-fill'],
    99 => ['Şiddetli Dolulu Fırtına', 'bi-cloud-lightning-rain-fill', 'bi-cloud-lightning-rain-fill'],
];

function weather_label(int $code): string
{
    return WEATHER_CODE_MAP[$code][0] ?? 'Bilinmiyor';
}

/** Bootstrap Icons sinif adini doner (ornek: "bi-sun-fill"), gerceklestirme <i class="bi ..."> ile yapilir */
function weather_icon(int $code, int $isDay = 1): string
{
    $entry = WEATHER_CODE_MAP[$code] ?? null;
    if (!$entry) return 'bi-thermometer-half';
    return $isDay ? $entry[1] : $entry[2];
}

/**
 * Ayarlarda tanimli sehir icin hava durumunu getirir (30 dk onbellekli).
 * API'ye ulasilamazsa son bilinen (eski) onbellegi, o da yoksa null doner.
 */
function get_weather(bool $forceRefresh = false): ?array
{
    $cacheJson = get_setting('weather_cache_json', '');
    $cacheTime = get_setting('weather_cache_time', '');
    $isFresh = $cacheJson !== '' && $cacheTime !== '' && (time() - strtotime($cacheTime)) < WEATHER_CACHE_SECONDS;

    if (!$forceRefresh && $isFresh) {
        $data = json_decode($cacheJson, true);
        if ($data) return $data;
    }

    $fresh = refresh_weather();
    if ($fresh) return $fresh;

    // API basarisiz oldu, eski onbellek varsa onu kullan (hic gostermemekten iyidir)
    if ($cacheJson !== '') {
        $data = json_decode($cacheJson, true);
        if ($data) return $data;
    }

    return null;
}

function refresh_weather(): ?array
{
    $lat = get_setting('weather_lat', '41.0082');
    $lon = get_setting('weather_lon', '28.9784');
    $city = get_setting('weather_city', 'İstanbul');

    $url = sprintf(
        'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current_weather=true&timezone=Europe%%2FIstanbul',
        rawurlencode($lat),
        rawurlencode($lon)
    );
    $json = http_get_json($url);

    if (empty($json['current_weather'])) {
        return null;
    }

    $cw = $json['current_weather'];
    $data = [
        'city' => $city,
        'temperature' => round((float)$cw['temperature']),
        'weathercode' => (int)$cw['weathercode'],
        'is_day' => (int)($cw['is_day'] ?? 1),
        'windspeed' => round((float)($cw['windspeed'] ?? 0)),
    ];

    $upd = db()->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'weather_cache_json'");
    $upd->execute([json_encode($data, JSON_UNESCAPED_UNICODE)]);
    $upd2 = db()->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'weather_cache_time'");
    $upd2->execute([date('Y-m-d H:i:s')]);

    return $data;
}

/** admin panelinde sehir secimi icin hazir Turkiye il listesi (isim => [enlem, boylam]) */
function weather_city_presets(): array
{
    return [
        'İstanbul' => [41.0082, 28.9784],
        'Ankara' => [39.9334, 32.8597],
        'İzmir' => [38.4237, 27.1428],
        'Bursa' => [40.1885, 29.0610],
        'Antalya' => [36.8969, 30.7133],
        'Adana' => [37.0000, 35.3213],
        'Konya' => [37.8746, 32.4932],
        'Gaziantep' => [37.0662, 37.3833],
        'Trabzon' => [41.0027, 39.7168],
        'Diyarbakır' => [37.9144, 40.2306],
        'Samsun' => [41.2867, 36.3300],
        'Eskişehir' => [39.7767, 30.5206],
    ];
}
