<?php
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
$__pageTitle = 'Sayfa Bulunamadı - ' . get_setting('site_name');
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/not-found.php';
require __DIR__ . '/includes/footer.php';
