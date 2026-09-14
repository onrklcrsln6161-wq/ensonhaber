<?php
function ai_tasks(): array { return ['title'=>'Yalnızca tek bir tarafsız Türkçe haber başlığı öner; en fazla 120 karakter.','summary'=>'Yalnızca Türkçe spot/özet öner; en fazla 400 karakter.','content'=>'Metni Türkçe haber diliyle düzenle. Yalnızca düzenlenmiş metni düz metin olarak döndür.']; }
function ai_response_text(array $data): string {
 if(($data['status']??'')!=='completed')throw new RuntimeException('Yanıt tamamlanamadı; öneri uygulanmadı.');
 $text='';foreach($data['output']??[] as $item)foreach($item['content']??[] as $part)if(($part['type']??'')==='output_text')$text.=$part['text']??'';
 if(trim($text)==='')throw new RuntimeException('Öneri üretilemedi.');return trim($text);
}
