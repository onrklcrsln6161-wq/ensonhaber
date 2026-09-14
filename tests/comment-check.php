<?php
$p=new PDO('sqlite:'.__DIR__.'/../config/demo.sqlite');
$text=$p->query("SELECT comment FROM comments WHERE email='demo-check@example.invalid' ORDER BY id DESC LIMIT 1")->fetchColumn();
if($text!=='Yorum metni koruma kontrolü 2026') {fwrite(STDERR,'Comment mismatch');exit(1);}
echo "PASS: Complete comment saved\n";
$p->exec("DELETE FROM comments WHERE email='demo-check@example.invalid'");
