<?php
require __DIR__.'/../includes/ai.php';require __DIR__.'/../includes/feed-network.php';
function ok($v,$label){if(!$v)throw new RuntimeException($label);echo "PASS: $label\n";}
ok(!public_feed_host('127.0.0.1')&&!public_feed_host('localhost')&&!public_feed_host('host.internal'),'Local hosts blocked');
ok(public_feed_host('feeds.bbci.co.uk'),'Public hostname accepted');
ok(ai_response_text(['status'=>'completed','output'=>[['type'=>'reasoning'],['content'=>[['type'=>'output_text','text'=>'Türkçe öneri']]]]])==='Türkçe öneri','Responses text extracted');
try{ai_response_text(['status'=>'incomplete']);throw new LogicException('Accepted incomplete');}catch(RuntimeException $e){ok(true,'Incomplete output rejected');}
try{ai_response_text(['status'=>'completed','output'=>[['content'=>[['type'=>'refusal','refusal'=>'no']]]]]);throw new LogicException('Accepted refusal');}catch(RuntimeException $e){ok(true,'Refusal not applied as content');}
