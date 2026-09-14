<?php
function social_platforms(): array {
 return ['facebook'=>['Facebook','facebook',['facebook.com','fb.com']], 'twitter'=>['X (Twitter)','twitter-x',['x.com','twitter.com']], 'instagram'=>['Instagram','instagram',['instagram.com']], 'youtube'=>['YouTube','youtube',['youtube.com','youtu.be']], 'linkedin'=>['LinkedIn','linkedin',['linkedin.com']], 'tiktok'=>['TikTok','tiktok',['tiktok.com']], 'telegram'=>['Telegram','telegram',['t.me','telegram.me']], 'whatsapp'=>['WhatsApp','whatsapp',['whatsapp.com','wa.me']]];
}
function valid_social_url(string $value, array $domains): bool {
 if (!filter_var($value,FILTER_VALIDATE_URL) || strlen($value)>500) return false;
 $parts=parse_url($value);
 if (($parts['scheme']??'')!=='https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return false;
 $host=strtolower($parts['host']??'');
 foreach($domains as $domain) if($host===$domain || str_ends_with($host,'.'.$domain)) return true;
 return false;
}
function social_accounts(): array {
 $out=[];
 foreach(social_platforms() as $key=>$platform) {
  $url=get_setting($key.'_url');
  if(get_setting($key.'_enabled','1')==='1' && valid_social_url($url,$platform[2])) $out[]=['name'=>$platform[0],'icon'=>$platform[1],'url'=>$url];
 }
 return $out;
}
function save_setting(string $key, string $value): void {
 $sql=DB_HOST==='sqlite' ? 'INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value' : 'INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)';
 db()->prepare($sql)->execute([$key,$value]);
}
function publication_pages(): array { return ['kunye'=>'Künye','iletisim'=>'İletişim','hakkimizda'=>'Hakkımızda','yayin-ilkeleri'=>'Yayın ve Düzeltme İlkeleri','gizlilik'=>'Gizlilik Politikası']; }
