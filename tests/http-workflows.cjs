const base='http://127.0.0.1:8091/';let checks=0;
function ok(value,label){if(!value)throw Error(label);checks++;console.log('PASS '+label);}
const token=html=>html.match(/name="csrf_token" value="([^"]+)"/)?.[1];
const version=html=>html.match(/name="edit_token" value="([^"]+)"/)?.[1];
async function client(user){let cookie='';async function request(path,data){const r=await fetch(base+path,{redirect:'manual',headers:cookie?{cookie}:{},...(data?{method:'POST',body:new URLSearchParams(data)}:{})});cookie=r.headers.get('set-cookie')?.split(';')[0]||cookie;return {status:r.status,location:r.headers.get('location'),text:await r.text()};}let r=await request('admin/login.php');r=await request('admin/login.php',{csrf_token:token(r.text),username:user,password:'QaOnly-2026-Check!'});ok(r.status===302,'login '+user);return request;}
(async()=>{
const admin=await client('qa_admin'),editor=await client('qa_editor'),author=await client('qa_author');
let r=await author('admin/settings.php');ok(r.status===403,'author settings forbidden');r=await editor('admin/users.php');ok(r.status===403,'editor users forbidden');r=await author('author/news_form.php?id=1');ok(r.status===403,'other author news forbidden');
r=await author('author/news_form.php');let csrf=token(r.text);const category=r.text.match(/<option value="(\d+)"/)?.[1];const data={csrf_token:csrf,title:'QA özel haber',summary:'Test özeti',content:'<p>Bu metin yalnızca izole test veritabanında kullanılır.</p>',category_id:category,status:'published'};
r=await author('author/news_form.php',data);ok(r.status===403,'author publication POST forbidden');
r=await author('author/news_form.php',{...data,status:'pending',category_id:'999999'});ok(r.status===200&&r.text.includes('Aktif bir kategori'),'invalid category keeps form');
r=await author('author/news_form.php',{...data,status:'pending',tags:'a'.repeat(61)});ok(r.status===200&&r.text.includes('Etiketler en fazla'),'long tag rejected');
r=await author('author/news_form.php',{...data,status:'pending'});ok(r.status===302,'author submits pending');const id=r.location.match(/id=(\d+)/)[1];
r=await author('author/news_form.php?id='+id);const old=version(r.text);ok(Boolean(old),'edit version issued');
r=await author('author/news_form.php?id='+id,{...data,status:'pending',title:'QA ilk düzenleme',edit_token:old});ok(r.status===302,'first update saved');
r=await author('author/news_form.php?id='+id,{...data,status:'pending',title:'QA eski sürüm',edit_token:old});ok(r.status===200&&r.text.includes('başka bir işlemde değiştirildi'),'stale edit rejected');
r=await author('author/news_form.php?id='+id);ok(r.text.includes('value="QA ilk düzenleme"'),'newer title preserved');
r=await editor('editor/news_form.php?id='+id);const ec=token(r.text);r=await editor('editor/news_form.php?id='+id,{...data,csrf_token:ec,edit_token:version(r.text),title:'QA editör yayını',status:'published'});ok(r.status===302,'editor publication permitted');
r=await author('author/news_form.php?id='+id);ok(r.status===403,'author cannot change published story');
r=await admin('admin/news.php');const ac=token(r.text);r=await admin('admin/news_delete.php',{csrf_token:ac,id});ok(r.status===302,'admin trashes news');r=await admin('admin/news_form.php?id='+id);ok(r.status===302,'trashed news cannot edit');
r=await admin('admin/trash.php',{csrf_token:ac,id});ok(r.status===302,'restore as draft');r=await admin('admin/news_form.php?id='+id);ok(/value="draft" selected/.test(r.text),'restored news stays draft');
r=await editor('editor/revisions.php?id='+id);ok(r.status===200&&r.text.includes('QA ilk düzenleme'),'history contains prior content');
for(const path of ['config/demo.sqlite','scripts/backup.php','.gitignore']){r=await admin(path);ok(r.status===404||r.status===403,'private path blocked '+path);}
console.log(checks+' HTTP workflow checks passed');
})().catch(e=>{console.error(e.message);process.exitCode=1;});
