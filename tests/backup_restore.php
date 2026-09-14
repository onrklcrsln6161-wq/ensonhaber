<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$files=glob(__DIR__.'/../config/backups/*.sql');rsort($files);if(!$files)throw new RuntimeException('No backup');
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec(file_get_contents($files[0]));
$source=new PDO('sqlite:'.__DIR__.'/../config/demo.sqlite');
foreach($source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN) as $name){$quoted='"'.str_replace('"','""',$name).'"';if($db->query('SELECT COUNT(*) FROM '.$quoted)->fetchColumn()!=$source->query('SELECT COUNT(*) FROM '.$quoted)->fetchColumn())throw new RuntimeException('Count mismatch');}
echo "PASS: SQLite backup restored into isolated memory database; all table counts match\n";
