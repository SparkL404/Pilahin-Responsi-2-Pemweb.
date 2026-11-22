const $=s=>document.querySelector(s);
const bins=$('#bins'),ans=$('#ans'),list=$('#list'),qInput=$('#q'),sug=$('#sug');
const DEFAULT_ANS=ans?ans.textContent:'';
const reducedMotion=()=>Boolean(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches);
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const BIN_ICONS={
 organik:'<path d="M17 5C9.5 5 5 9.5 5 17c7.5 0 12-4.5 12-12Z"/><path d="M5 17c3.5-3.5 6.5-5.5 10-7"/>',
 plastik:'<path d="M10 3h4v3l2 2.5V20a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V8.5L10 6Z"/><path d="M9 12h6"/>',
 kertas:'<path d="M7 3h7l4 4v14H7Z"/><path d="M14 3v4h4"/><path d="M10 12h5M10 16h5"/>',
 b3:'<path d="M12 4 3 20h18Z"/><path d="M12 10v4.5"/><circle cx="12" cy="17.2" r=".7" fill="currentColor" stroke="none"/>',
 residu:'<path d="m7 7 10 10M17 7 7 17"/><circle cx="12" cy="12" r="8"/>',
 elektronik:'<path d="M9 4v5M15 4v5M7 9h10v3a5 5 0 0 1-10 0V9ZM12 17v4"/>',
 default:'<path d="M9.5 6v12M14.5 6v12"/>'
};

let activeSlug=null,activeToss=null;
let sugItems=[],sugIdx=-1,sugTimer=null,sugCtrl=null;

async function api(url,options){
 const res=await fetch(url,options);
 let data=null;
 try{data=await res.json()}catch(e){throw new Error('Respons server tidak valid.')}
 if(!res.ok){throw new Error((data&&data.error)?data.error:'Terjadi kesalahan pada server.')}
 return data;
}

function clearChipActive(){
 document.querySelectorAll('#chips .chip.is-active').forEach(c=>c.classList.remove('is-active'));
}

function popAnswer(){
 if(!ans||reducedMotion())return;
 ans.classList.remove('pop');
 void ans.offsetWidth;
 ans.classList.add('pop');
}

function triggerCatch(slug){
 if(!bins||reducedMotion())return;
 const target=Array.from(bins.querySelectorAll('.bin')).find(b=>b.dataset.k===slug);
 if(!target)return;
 target.classList.remove('catch');
 void target.offsetWidth;
 target.classList.add('catch');
 target.addEventListener('animationend',()=>target.classList.remove('catch'),{once:true});
}

function tossToBin(label,slug,color,originEl){
 if(activeToss){
  activeToss.anim.cancel();
  activeToss.el.remove();
  activeToss=null;
 }
 const targetBin=Array.from(bins.querySelectorAll('.bin')).find(b=>b.dataset.k===slug);
 const body=targetBin?targetBin.querySelector('.body'):null;
 const startEl=originEl||qInput;
 if(!body||!startEl||reducedMotion()||!Element.prototype.animate){
  triggerCatch(slug);
  return;
 }
 const sr=startEl.getBoundingClientRect(),tr=body.getBoundingClientRect();
 const vw=window.innerWidth,vh=window.innerHeight;
 if(tr.bottom<0||tr.top>vh){
  triggerCatch(slug);
  return;
 }
 const pill=document.createElement('span');
 pill.className='toss-pill';
 pill.textContent=label;
 pill.style.setProperty('--c',color||'#DDEB9D');
 document.body.appendChild(pill);
 const pr=pill.getBoundingClientRect();
 const sx=Math.max(12,Math.min(vw-pr.width-12,sr.left+Math.min(sr.width*0.45,140)-pr.width/2));
 const sy=sr.top+sr.height/2-pr.height/2;
 const ex=Math.max(12,Math.min(vw-pr.width-12,tr.left+tr.width/2-pr.width/2));
 const ey=tr.top+tr.height*0.18-pr.height/2;
 const cx=(sx+ex)/2;
 const cy=Math.min(sy,ey)-Math.min(72,Math.max(36,Math.abs(ex-sx)*0.22));
 const frames=[];
 for(let i=0;i<=6;i++){
  const t=i/6,u=1-t;
  const x=u*u*sx+2*u*t*cx+t*t*ex;
  const y=u*u*sy+2*u*t*cy+t*t*ey;
  const s=1-0.28*t*t;
  const op=i===6?0:(t>0.8?(1-(t-0.8)/0.2):1);
  frames.push({transform:`translate(${x}px,${y}px) scale(${s})`,opacity:op});
 }
 const anim=pill.animate(frames,{duration:430,easing:'cubic-bezier(0.22, 1, 0.36, 1)',fill:'forwards'});
 activeToss={el:pill,anim};
 anim.onfinish=()=>{
  pill.remove();
  if(activeToss&&activeToss.el===pill)activeToss=null;
  triggerCatch(slug);
 };
 anim.oncancel=()=>{pill.remove()};
}

function mark(k){
 activeSlug=k||null;
 if(!k)clearChipActive();
 bins.classList.toggle('has',!!k);
 bins.querySelectorAll('.bin').forEach(b=>{
  const on=b.dataset.k===k;
  b.classList.toggle('on',on);
  b.setAttribute('aria-pressed',on?'true':'false');
 });
}

function showCat(v,prefix){
 ans.innerHTML=prefix+'<b>'+esc(v.name)+'</b>, tempat sampah '+esc(v.bin)+'.<small>Contoh: '+esc(v.examples)+'</small>';
 mark(v.slug);
 popAnswer();
}

function closeSug(){
 clearTimeout(sugTimer);
 if(sugCtrl){sugCtrl.abort();sugCtrl=null}
 sugItems=[];sugIdx=-1;
 if(sug){sug.innerHTML='';sug.classList.add('hidden')}
 if(qInput){qInput.setAttribute('aria-expanded','false');qInput.removeAttribute('aria-activedescendant')}
}

function highlightMatch(text,query){
 const lowerText=text.toLowerCase(),lowerQ=query.toLowerCase();
 const pos=lowerText.indexOf(lowerQ);
 if(pos===-1||!lowerQ)return esc(text);
 return esc(text.slice(0,pos))+'<mark>'+esc(text.slice(pos,pos+query.length))+'</mark>'+esc(text.slice(pos+query.length));
}

function setSugIndex(idx){
 const rows=sug?Array.from(sug.children):[];
 if(!rows.length)return;
 sugIdx=(idx+rows.length)%rows.length;
 rows.forEach((li,i)=>{
  const on=i===sugIdx;
  li.classList.toggle('is-active',on);
  li.setAttribute('aria-selected',on?'true':'false');
 });
 const activeEl=rows[sugIdx];
 if(activeEl){
  qInput.setAttribute('aria-activedescendant',activeEl.id);
  activeEl.scrollIntoView({block:'nearest'});
 }
}

function pickSug(index,originEl){
 const item=sugItems[index];
 if(!item)return;
 const sr=(originEl&&originEl.getBoundingClientRect)?originEl.getBoundingClientRect():qInput.getBoundingClientRect();
 const rectProxy={getBoundingClientRect:()=>sr};
 qInput.value=item.keyword;
 closeSug();
 clearChipActive();
 showCat(item,'<b>'+esc(item.keyword)+'</b> masuk ke ');
 tossToBin(item.keyword,item.slug,item.color,rectProxy);
}

function renderSug(items,query){
 if(!sug)return;
 sugItems=Array.isArray(items)?items:[];
 sugIdx=-1;
 if(!sugItems.length){closeSug();return}
 sug.innerHTML=sugItems.map((it,i)=>'<li class="sug-item" id="sug-opt-'+i+'" role="option" aria-selected="false" data-i="'+i+'" style="--c:'+esc(it.color)+'"><span class="sug-kw">'+highlightMatch(it.keyword,query)+'</span><span class="sug-cat"><span class="sug-dot" aria-hidden="true"></span>'+esc(it.name)+'</span></li>').join('');
 sug.classList.remove('hidden');
 qInput.setAttribute('aria-expanded','true');
 qInput.removeAttribute('aria-activedescendant');
}

async function fetchSug(query){
 if(sugCtrl){sugCtrl.abort()}
 sugCtrl=typeof AbortController!=='undefined'?new AbortController():null;
 try{
  const opts=sugCtrl?{signal:sugCtrl.signal}:undefined;
  const d=await api('api/search.php?mode=suggest&q='+encodeURIComponent(query),opts);
  if(qInput.value.trim().toLowerCase()!==query.toLowerCase())return;
  renderSug(d.suggestions||[],query);
 }catch(e){
  if(e&&e.name==='AbortError')return;
  closeSug();
 }
}

if(sug){
 sug.addEventListener('mousedown',e=>{e.preventDefault()});
 sug.addEventListener('click',e=>{
  const li=e.target.closest('.sug-item');
  if(!li)return;
  pickSug(Number(li.dataset.i),li);
 });
}

document.addEventListener('click',e=>{
 if(!e.target.closest('.finder-field'))closeSug();
});

async function loadCats(){
 try{
  const cats=await api('api/categories.php');
  bins.innerHTML='';list.innerHTML='';
  const sel=$('#rj');
  if(sel){sel.innerHTML='<option value="">Pilih jenis sampah</option>'}
  cats.forEach(v=>{
   const icon=BIN_ICONS[v.slug]||BIN_ICONS.default;
   const b=document.createElement('button');
   b.type='button';b.className='bin';b.dataset.k=v.slug;
   b.setAttribute('aria-pressed','false');
   b.style.setProperty('--c',v.color);
   b.innerHTML='<span class="lid" aria-hidden="true"></span><span class="body" aria-hidden="true"><svg class="bin-ico" viewBox="0 0 24 24" aria-hidden="true">'+icon+'</svg></span><span class="bin-name">'+esc(v.name)+'</span>';
   b.onclick=()=>{
    closeSug();
    clearChipActive();
    if(activeSlug===v.slug){
     mark(null);
     ans.textContent=DEFAULT_ANS;
     popAnswer();
    }else{
     showCat(v,'');
     triggerCatch(v.slug);
    }
   };
   bins.appendChild(b);
   const r=document.createElement('div');r.className='row';r.style.setProperty('--c',v.color);
   r.innerHTML='<i aria-hidden="true"></i><h3>'+esc(v.name)+'</h3><p><b>Tempat sampah '+esc(v.bin)+'.</b> '+esc(v.examples)+'.</p>';
   list.appendChild(r);
   if(sel){const o=document.createElement('option');o.value=v.name;o.textContent=v.name;sel.appendChild(o)}
  });
  if(sel){const m=document.createElement('option');m.value='Campuran';m.textContent='Campuran';sel.appendChild(m)}
 }catch(e){ans.textContent='Gagal memuat data.'}
}

async function search(originEl){
 closeSug();
 const t=qInput.value.trim();
 if(!t){ans.textContent='Ketik nama barang dulu, misalnya "baterai".';mark(null);popAnswer();return}
 try{
  const d=await api('api/search.php?q='+encodeURIComponent(t));
  if(d.match){
   showCat(d.match,'<b>'+esc(t)+'</b> masuk ke ');
   tossToBin(t,d.match.slug,d.match.color,originEl||qInput);
  }else{
   ans.innerHTML='Belum ada data untuk "'+esc(t)+'".<small>Coba kata yang lebih umum, atau masukkan ke Residu jika tidak bisa didaur ulang.</small>';
   mark(null);
   popAnswer();
  }
 }catch(e){ans.textContent=e.message;popAnswer()}
}

$('#go').onclick=()=>search($('#go'));
qInput.addEventListener('keydown',e=>{
 if(e.isComposing)return;
 const open=sug&&!sug.classList.contains('hidden')&&sugItems.length>0;
 if(e.key==='ArrowDown'&&open){
  e.preventDefault();
  setSugIndex(sugIdx+1);
 }else if(e.key==='ArrowUp'&&open){
  e.preventDefault();
  setSugIndex(sugIdx<=0?sugItems.length-1:sugIdx-1);
 }else if(e.key==='Escape'&&open){
  e.preventDefault();
  closeSug();
 }else if(e.key==='Enter'){
  if(open&&sugIdx>=0){
   e.preventDefault();
   pickSug(sugIdx,sug.children[sugIdx]);
  }else{
   closeSug();
   clearChipActive();
   search(qInput);
  }
 }
});
qInput.addEventListener('input',()=>{
 clearChipActive();
 clearTimeout(sugTimer);
 const t=qInput.value.trim();
 if(!t){closeSug();return}
 sugTimer=setTimeout(()=>fetchSug(t),150);
});
qInput.addEventListener('search',()=>{if(!qInput.value.trim()){closeSug();mark(null);ans.textContent=DEFAULT_ANS}});

document.querySelectorAll('#chips .chip').forEach(chip=>{
 chip.addEventListener('click',()=>{
  closeSug();
  qInput.value=chip.dataset.q;
  clearChipActive();
  chip.classList.add('is-active');
  search(chip);
 });
});

let Q=[],qi=0,sc=0;
function ensureQuizShell(box){
 let bar=box.querySelector('.quiz-progress-bar');
 let stage=box.querySelector('.quiz-stage');
 if(!bar||!stage){
  box.innerHTML='<div class="quiz-progress" aria-hidden="true"><span class="quiz-progress-bar"></span></div><div class="quiz-stage"></div>';
  bar=box.querySelector('.quiz-progress-bar');
  stage=box.querySelector('.quiz-stage');
 }
 return {bar,stage};
}

function animateScore(el,target){
 if(!el||reducedMotion()||target<=0){if(el)el.textContent=String(target);return}
 const start=performance.now(),dur=320;
 function step(now){
  const p=Math.min(1,(now-start)/dur);
  const eased=1-Math.pow(1-p,3);
  el.textContent=String(Math.round(target*eased));
  if(p<1)requestAnimationFrame(step);
 }
 el.textContent='0';
 requestAnimationFrame(step);
}

function rq(moveFocus=false){
 const box=$('#quiz');
 if(!Q.length){box.textContent='Soal belum tersedia.';return}
 const {bar,stage}=ensureQuizShell(box);
 const ratio=qi>=Q.length?1:(qi+1)/Q.length;
 bar.style.setProperty('--p',ratio.toFixed(4));
 if(moveFocus&&!reducedMotion()){
  stage.classList.remove('enter');
  void stage.offsetWidth;
  stage.classList.add('enter');
 }
 if(qi>=Q.length){
  const passScore=Math.ceil(Q.length*0.8);
  const passed=sc>=passScore;
  bar.classList.toggle('done',passed);
  const badge=passed?'<svg class="score-badge" viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="19"/><path d="M13.5 22.5 L19.5 28.5 L30.5 16.5"/></svg>':'';
  const heading='<h3 class="quiz-score" tabindex="-1">Skor kamu <span class="score-num">'+sc+'</span> dari '+Q.length+'</h3>';
  stage.innerHTML=(badge?'<div class="score-head">'+badge+heading+'</div>':heading)+'<p class="lead">'+(passed?'Bagus. Kamu sudah paham cara memilah sampah.':'Lumayan. Cek lagi bagian kategori, lalu coba sekali lagi.')+'</p><button class="btn mt-sm" id="rs" type="button">Ulangi kuis</button>';
  animateScore(stage.querySelector('.score-num'),sc);
  $('#rs').onclick=()=>{qi=0;sc=0;rq(true)};
  if(moveFocus){const h=stage.querySelector('h3');if(h)h.focus()}
  api('api/quiz_attempts.php',{
   method:'POST',
   headers:{'Content-Type':'application/json'},
   body:JSON.stringify({score:sc,total:Q.length})
  }).catch(()=>{});
  return;
 }
 bar.classList.remove('done');
 const q=Q[qi],a=q.answer;
 stage.innerHTML='<div class="meta">Pertanyaan '+(qi+1)+' dari '+Q.length+'</div><h3 class="quiz-q" tabindex="-1">'+esc(q.question)+'</h3>'+q.options.map((x,i)=>'<button class="opt" data-i="'+i+'" type="button">'+esc(x)+'</button>').join('')+'<p class="fb" id="fb" aria-live="polite"></p><button class="btn hidden" id="nx" type="button">'+(qi===Q.length-1?'Lihat skor':'Lanjut')+'</button>';
 if(moveFocus){const h=stage.querySelector('h3');if(h)h.focus()}
 stage.querySelectorAll('.opt').forEach(b=>b.onclick=()=>{
  const i=+b.dataset.i;if(i===a)sc++;
  stage.querySelectorAll('.opt').forEach((x,j)=>{x.disabled=true;if(j===a)x.classList.add('ok');else if(j===i)x.classList.add('no')});
  $('#fb').textContent=(i===a?'Benar. ':'Belum tepat. ')+q.explanation;$('#nx').classList.remove('hidden');$('#nx').focus();
  $('#nx').onclick=()=>{qi++;rq(true)};
 });
}
async function loadQuiz(){
 try{Q=await api('api/quiz.php');rq(false)}catch(e){$('#quiz').textContent='Gagal memuat kuis.'}
}

$('#rf').addEventListener('submit',async e=>{
 e.preventDefault();$('#rerr').textContent='';
 const btn=$('#rf button[type="submit"]');
 if(btn){btn.disabled=true}
 try{
  await api('api/reports.php',{method:'POST',headers:{'Content-Type':'application/json'},
   body:JSON.stringify({type:$('#rj').value,location:$('#rl').value,notes:$('#rk').value})});
  $('#dtxt').textContent='Terima kasih. Laporan '+$('#rj').value.toLowerCase()+' di '+$('#rl').value+' sudah dicatat.';
  $('#rf').classList.add('hidden');$('#done').classList.remove('hidden');
  const dh=$('#done h3');if(dh)dh.focus();
 }catch(err){$('#rerr').textContent=err.message}
 finally{if(btn){btn.disabled=false}}
});
$('#again').onclick=()=>{$('#rf').reset();$('#rf').classList.remove('hidden');$('#done').classList.add('hidden');$('#rj').focus()};

function initScrollSpy(){
 const links=Array.from(document.querySelectorAll('header nav a[href^="#"]'));
 if(!links.length)return;
 const sections=links.map(a=>document.querySelector(a.getAttribute('href'))).filter(Boolean);
 const setActive=id=>{
  links.forEach(a=>{
   const on=a.getAttribute('href')==='#'+id;
   a.classList.toggle('is-active',on);
   if(on)a.setAttribute('aria-current','location');
   else a.removeAttribute('aria-current');
  });
 };
 setActive('home');
 if('IntersectionObserver' in window&&sections.length){
  const io=new IntersectionObserver(entries=>{
   const vis=entries.filter(e=>e.isIntersecting).sort((a,b)=>a.boundingClientRect.top-b.boundingClientRect.top);
   if(vis.length)setActive(vis[0].target.id);
  },{rootMargin:'-25% 0px -55% 0px',threshold:0});
  sections.forEach(s=>io.observe(s));
 }
 window.addEventListener('scroll',()=>{
  if(window.scrollY<60){setActive('home');return}
  if(window.innerHeight+window.scrollY>=document.documentElement.scrollHeight-24&&sections.length){
   setActive(sections[sections.length-1].id);
  }
 },{passive:true});
}

initScrollSpy();
loadCats();
loadQuiz();
