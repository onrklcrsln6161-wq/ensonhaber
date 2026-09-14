<?php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~(?:^|/)\.[^/]+|(?:^|/)(?:config|includes|tests|scripts|cron|\.git)(?:/|$)|\.(?:sql|sqlite|log|ini|md|bak|ps1)$~i', $path)) {
    http_response_code(404); exit('Not found');
}
if (str_starts_with($path, '/uploads/') && str_ends_with(strtolower($path), '.php')) { http_response_code(404); exit; }
$file = realpath(__DIR__ . $path);
if ($path === '/' || ($file && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR))) return false;
require __DIR__ . '/404.php';
