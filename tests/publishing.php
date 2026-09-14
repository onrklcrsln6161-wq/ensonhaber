<?php
putenv('DB_HOST=sqlite');putenv('DB_NAME=:memory:');
require __DIR__.'/../includes/functions.php';
function assert_ok($v,$label) { if(!$v) {fwrite(STDERR,"FAIL: $label\n");exit(1);} echo "PASS: $label\n"; }
assert_ok(valid_social_url('https://www.instagram.com/example',['instagram.com']),'Valid platform URL');
foreach(['javascript:alert(1)','http://instagram.com/a','https://instagram.com.evil.test/a','https://evil.test/instagram.com','https://name:pass@instagram.com/a','https://instagram.com:99/a'] as $url) assert_ok(!valid_social_url($url,['instagram.com']),'Reject unsafe or wrong host');
db()->exec('CREATE TABLE settings(setting_key TEXT PRIMARY KEY,setting_value TEXT)');
save_setting('instagram_url','https://instagram.com/example');save_setting('instagram_url','https://instagram.com/updated');save_setting('instagram_enabled','0');
assert_ok(db()->query('SELECT COUNT(*) FROM settings')->fetchColumn()==2,'Setting insert/update');
assert_ok(social_accounts()===[],'Disabled and empty accounts hidden');
assert_ok(count(publication_pages())===5,'Publication pages configured');
