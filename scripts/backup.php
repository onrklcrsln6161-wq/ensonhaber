<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../includes/functions.php';
$dir=ROOT_PATH.'/config/backups';if(!is_dir($dir))mkdir($dir,0700,true);
$file=$dir.'/database-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
$out=fopen($file,'xb');if(!$out)throw new RuntimeException('Yedek dosyası açılamadı.');
try {
 $pdo=db();$pdo->beginTransaction();
 $tables=DB_HOST==='sqlite'?$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN):$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
 fwrite($out,"-- Restore into an EMPTY database of the same database engine.\n");
 if(DB_HOST!=='sqlite')fwrite($out,"SET FOREIGN_KEY_CHECKS=0;\n");
 foreach($tables as $table){
  $quoted='`'.str_replace('`','``',$table).'`';
  if(DB_HOST==='sqlite'){$s=$pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");$s->execute([$table]);$ddl=$s->fetchColumn();}else{$ddl=$pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_NUM)[1];}
  fwrite($out,$ddl.";\n");
  $rows=$pdo->query('SELECT * FROM '.$quoted);
  while($row=$rows->fetch(PDO::FETCH_ASSOC)){
   $cols=array_map(fn($key)=>'`'.str_replace('`','``',$key).'`',array_keys($row));
   $values=array_map(fn($value)=>$value===null?'NULL':$pdo->quote((string)$value),array_values($row));
   fwrite($out,'INSERT INTO '.$quoted.' ('.implode(',',$cols).') VALUES ('.implode(',',$values).");\n");
  }
 }
 if(DB_HOST==='sqlite')foreach($pdo->query("SELECT sql FROM sqlite_master WHERE type IN ('index','trigger','view') AND sql IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $ddl)fwrite($out,$ddl.";\n");
 if(DB_HOST!=='sqlite')fwrite($out,"SET FOREIGN_KEY_CHECKS=1;\n");
 $pdo->commit();fclose($out);echo $file.PHP_EOL;
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();fclose($out);throw $e;}
