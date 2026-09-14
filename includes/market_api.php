<?php
require_once __DIR__ . '/functions.php';

/**
 * Ucretsiz, anahtarsiz kamu API'lerinden anlik USD/EUR/GBP/BTC-TRY kurlarini ceker
 * ve market_ticker tablosunu gunceller. ALTIN/GUMUS/BIST icin guvenilir ucretsiz bir
 * kaynak olmadigindan bu degerler admin tarafindan elle girilmeye devam eder.
 *
 * @return array{success: string[], failed: string[]}
 */
function fetch_live_market_rates(): array
{
    $results = ['success' => [], 'failed' => []];

    $currencyPairs = [
        'USD' => 'https://api.frankfurter.app/latest?from=USD&to=TRY',
        'EUR' => 'https://api.frankfurter.app/latest?from=EUR&to=TRY',
        'GBP' => 'https://api.frankfurter.app/latest?from=GBP&to=TRY',
    ];

    foreach ($currencyPairs as $code => $url) {
        $data = http_get_json($url);
        if (isset($data['rates']['TRY'])) {
            update_ticker_value($code, (float)$data['rates']['TRY'], 2);
            $results['success'][] = $code;
        } else {
            $results['failed'][] = $code;
        }
    }

    $btc = http_get_json('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=try');
    if (isset($btc['bitcoin']['try'])) {
        update_ticker_value('BTC', (float)$btc['bitcoin']['try'], 0);
        $results['success'][] = 'BTC';
    } else {
        $results['failed'][] = 'BTC';
    }

    if ($results['success']) db()->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'market_last_auto_update'")
        ->execute([date('Y-m-d H:i:s')]);

    return $results;
}

function update_ticker_value(string $code, float $rawValue, int $decimals): void
{
    $stmt = db()->prepare('SELECT id, value FROM market_ticker WHERE code = ?');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if (!$row) return;

    $oldValue = turkish_number_to_float($row['value']);
    $changePercent = $oldValue > 0 ? (($rawValue - $oldValue) / $oldValue) * 100 : 0;
    $formatted = number_format($rawValue, $decimals, ',', '.');

    db()->prepare('UPDATE market_ticker SET value = ?, change_percent = ?, updated_at = ? WHERE id = ?')
        ->execute([$formatted, round($changePercent, 2), date('Y-m-d H:i:s'), $row['id']]);
}

/** "3.834.363" veya "48,60" gibi TR formatli sayiyi float'a cevirir */
function turkish_number_to_float(string $value): float
{
    return (float)str_replace(['.', ','], ['', '.'], $value);
}

/** Cron/harici zamanlayici icin gizli bir token uretir (yoksa olusturup kaydeder) */
function get_or_create_cron_token(): string
{
    $token = get_setting('market_cron_token', '');
    if ($token === '') {
        $token = bin2hex(random_bytes(20));
        db()->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'market_cron_token'")->execute([$token]);
    }
    return $token;
}
