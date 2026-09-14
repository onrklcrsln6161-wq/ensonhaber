<?php
function public_feed_host(string $host): bool {
 return strlen($host)<=253 && str_contains($host,'.') && !filter_var($host,FILTER_VALIDATE_IP) && !preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/i',$host) && (bool)preg_match('/^[a-z0-9.-]+$/i',$host);
}
function fetch_public_feed(string $url): string {
 $p=parse_url($url);$host=strtolower($p['host']??'');
 if(!filter_var($url,FILTER_VALIDATE_URL)||($p['scheme']??'')!=='https'||!public_feed_host($host)||isset($p['port'])||isset($p['user'])||isset($p['pass']))throw new RuntimeException('Genel erişime açık HTTPS RSS/Atom adresi girin.');
 $ips=gethostbynamel($host);if(!$ips)throw new RuntimeException('Kaynak DNS adresi çözülemedi.');
 foreach($ips as $ip)if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE|(defined('FILTER_FLAG_GLOBAL_RANGE')?FILTER_FLAG_GLOBAL_RANGE:0)))throw new RuntimeException('Yerel/özel ağ adreslerine erişim engellendi.');
 // Connect to the already-validated IP, while validating TLS for the original host.
 $target='https://'.$ips[0].($p['path']??'/').(isset($p['query'])?'?'.$p['query']:'');
 $ctx=stream_context_create(['http'=>['timeout'=>12,'follow_location'=>0,'header'=>"Host: $host\r\nAccept: application/rss+xml, application/atom+xml\r\n",'user_agent'=>'Bilsenneoldu RSS Reader/1.0'],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>$host,'SNI_enabled'=>true]]);
 $xml=@file_get_contents($target,false,$ctx,0,2000001);
 if($xml===false || !preg_match('~^HTTP/\S+ 200\b~',$http_response_header[0]??''))throw new RuntimeException('Akış alınamadı. Yönlendirme varsa doğrudan son RSS adresini girin.');
 return $xml;
}
