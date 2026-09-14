<?php
if(PHP_SAPI!=='cli')exit;
$source=new PDO('sqlite:'.__DIR__.'/../config/demo.sqlite');$target=__DIR__.'/../config/qa-check.sqlite';
if(file_exists($target))throw new RuntimeException('QA database already exists.');
$source->exec('VACUUM INTO '.$source->quote($target));$db=new PDO('sqlite:'.$target);$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
foreach(['super_admin'=>'qa_admin','editor'=>'qa_editor','author'=>'qa_author'] as $role=>$name){$db->prepare('INSERT INTO admins(username,password_hash,full_name,email,role,is_active) VALUES(?,?,?,?,?,1)')->execute([$name,password_hash('QaOnly-2026-Check!',PASSWORD_DEFAULT),$name,$name.'@example.invalid',$role]);}
echo "Isolated QA database prepared\n";
