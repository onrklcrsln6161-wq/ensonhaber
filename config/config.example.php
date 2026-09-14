<?php
// ============================================================
// Genel yapilandirma - kendi sunucu bilgilerinizi girin
// ============================================================

// getenv() ile gecici override, sunucuda ortam degiskeni yoksa asagidaki
// varsayilanlar kullanilir - kendi MySQL bilgilerinizi buraya girin.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'haber_portali');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Sitenin kok URL'i (sonunda / OLMADAN), orn: http://localhost/haber-portali
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/haber-portali');

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

define('SITE_TIMEZONE', 'Europe/Istanbul');
date_default_timezone_set(SITE_TIMEZONE);

// Sayfalama
define('NEWS_PER_PAGE', 12);

// Oturum guvenligi
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0'); // production'da kapali tutun, gelistirmede '1' yapabilirsiniz
