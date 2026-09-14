<?php
putenv('DB_HOST=sqlite');
putenv('DB_NAME=:memory:');
require __DIR__ . '/../includes/auth.php';
function check($ok, $label) { if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); } echo "PASS: $label\n"; }
$clean = clean_article_html('<p onclick="evil()">Türkçe <strong>haber</strong></p><script>alert(1)</script><a href="javascript:alert(1)">link</a><img src="x" onerror="evil()"><svg onload="evil()"></svg>');
check(str_contains($clean, '<strong>haber</strong>') && str_contains($clean, 'Türkçe'), 'Editorial formatting and Unicode preserved');
check(!preg_match('/script|onclick|onerror|onload|javascript|<svg/i', $clean), 'Executable markup removed');
check(clean_article_html('') === '', 'Empty content supported');
check(str_contains(clean_article_html('<a href="https://example.com/a">safe</a>'), 'href="https://example.com/a"'), 'Safe links preserved');
db()->exec('CREATE TABLE login_attempts(id INTEGER PRIMARY KEY, ip_address TEXT, username TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
check(!is_login_locked('test'), 'Initial login unlocked');
for ($i=0;$i<5;$i++) record_failed_login('test','editor');
check(is_login_locked('test'), 'Five real failures lock login with SQLite timestamps');
db()->prepare('UPDATE login_attempts SET created_at=?')->execute([date('Y-m-d H:i:s', time()-16*60)]);
check(!is_login_locked('test'), 'Lock expires after window');
