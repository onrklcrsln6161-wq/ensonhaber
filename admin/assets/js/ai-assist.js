(()=>{
 const form=document.getElementById('contentInput').form, status=document.getElementById('ai-status'),generate=document.getElementById('ai-generate'),result=document.getElementById('ai-result'),suggestion=document.getElementById('ai-suggestion'),undo=document.getElementById('ai-undo');
 let target=null,previous=null;
 const field=(task)=>form.elements.namedItem(task);
 const readContent=()=>typeof quill!=='undefined'?quill.getText():document.getElementById('contentInput').value;
 generate.addEventListener('click',async()=>{
  generate.disabled=true;result.hidden=true;status.textContent='Öneri hazırlanıyor…';target=document.getElementById('ai-task').value;
  const body=new URLSearchParams({csrf_token:form.elements.namedItem('csrf_token').value,task:target,text:field('title').value+'\n'+field('summary').value+'\n'+readContent()});
  try{const response=await fetch('ai_assist.php',{method:'POST',body,credentials:'same-origin'});let data;try{data=await response.json();}catch{throw Error('Oturum veya bağlantı hatası. Haberinizi koruyarak tekrar giriş yapın.');}if(!response.ok)throw Error(data.error||'Öneri alınamadı.');suggestion.value=data.suggestion;result.hidden=false;status.textContent='Öneri hazır. İnceleyip uygula düğmesine basın.';}catch(error){status.textContent=error.message;}finally{generate.disabled=false;}
 });
 document.getElementById('ai-apply').addEventListener('click',()=>{
  previous={target,value:field(target).value,delta:target==='content'&&typeof quill!=='undefined'?quill.getContents():null};
  const text=suggestion.value;
  if(target==='content'){const html=text.split(/\n+/).map(line=>{const p=document.createElement('p');p.textContent=line;return p.outerHTML;}).join('');field(target).value=html;if(typeof quill!=='undefined')quill.setText(text);}
  else field(target).value=text;
  undo.hidden=false;result.hidden=true;status.textContent='Alana uygulandı. Haber henüz kaydedilmedi.';
 });
 undo.addEventListener('click',()=>{if(!previous)return;field(previous.target).value=previous.value;if(previous.delta&&typeof quill!=='undefined')quill.setContents(previous.delta);previous=null;undo.hidden=true;status.textContent='Önceki içerik geri alındı.';});
})();