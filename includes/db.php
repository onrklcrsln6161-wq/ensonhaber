<?php
require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // DB_HOST='sqlite' test/gelistirme icin (DB_NAME sqlite dosya yolu olur).
        $dsn = DB_HOST === 'sqlite'
            ? 'sqlite:' . DB_NAME
            : 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            if (DB_HOST === 'sqlite') {
                // MySQL'in NOW() fonksiyonunu sqlite test modunda karsilar
                $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
            } else {
                // MySQL sunucusunun saat dilimini PHP ile eslestir (NOW()/CURRENT_TIMESTAMP tutarliligi icin)
                $pdo->exec("SET time_zone = '+03:00'");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            die('Veritabanina baglanilamadi. Lutfen config/config.php dosyasindaki DB bilgilerini kontrol edin.');
        }
    }
    return $pdo;
}
