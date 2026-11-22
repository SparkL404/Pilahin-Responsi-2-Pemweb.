const $=s=>document.querySelector(s);
const bins=$('#bins'),ans=$('#ans'),list=$('#list');
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
/* Pengganti server: semua data diambil dari data.js, laporan disimpan di browser (localStorage) */
const REPORT_KEY='pilahin_reports';
function getReports(){try{return JSON.parse(localStorage.getItem(REPORT_KEY))||[]}catch(e){return window.__reports||[]}}
function setReports(list){try{localStorage.setItem(REPORT_KEY,JSON.stringify(list))}catch(e){window.__reports=list}}
function searchLocal(q){
 const hits=DATA.items
  .filter(i=>i.keyword===q||q.includes(i.keyword)||i.keyword.includes(q))
  .sort((a,b)=>(b.keyword===q)-(a.keyword===q)||b.keyword.length-a.keyword.length);
 if(!hits.length)return null;
 return Object.assign({keyword:hits[0].keyword},DATA.categories.find(c=>c.slug===hits[0].category));
}
async function api(p,o){
 if(p==='/api/categories')return DATA.categories;
 if(p==='/api/quiz')return DATA.quiz;
 if(p.startsWith('/api/search')){
  const q=decodeURIComponent(p.split('q=')[1]||'').trim().toLowerCase().slice(0,80);
  if(!q)throw new Error('Parameter q wajib diisi');
  return {query:q,match:searchLocal(q)};
 }
 if(p==='/api/reports'&&o&&o.method==='POST'){
  const {type,location,notes=''}=JSON.parse(o.body);
  const types=DATA.categories.map(c=>c.name).concat('Campuran');
  if(!types.includes(type))throw new Error('Jenis sampah tidak valid');
  if(!location||!location.trim()||location.length>200)throw new Error('Lokasi wajib diisi (maks. 200 karakter)');
  if(notes.length>1000)throw new Error('Keterangan maksimal 1000 karakter');
  const list=getReports();
  const r={id:list.length+1,waste_type:type,location:location.trim(),notes:notes.trim(),created_at:new Date().toISOString()};
  list.push(r);setReports(list);
  return {id:r.id};
 }
 throw new Error('Tidak ditemukan');
}
function mark(k){bins.classList.toggle('has',!!k);bins.querySelectorAll('.bin').forEach(b=>b.classList.toggle('on',b.dataset.k===k))}
function showCat(v,prefix){
 ans.innerHTML=prefix+'<b>'+esc(v.name)+'</b>, tempat sampah '+esc(v.bin)+'.<small>Contoh: '+esc(v.examples)+'</small>';mark(v.slug);
}
async function loadCats(){
 try{
  const cats=await api('/api/categories');
  cats.forEach(v=>{
   const b=document.createElement('button');b.type='button';b.className='bin';b.dataset.k=v.slug;b.style.setProperty('--c',v.color);
   b.innerHTML='<div class="lid"></div><div class="body"></div><span>'+esc(v.name)+'</span>';
   b.onclick=()=>showCat(v,'');
   bins.appendChild(b);
   const r=document.createElement('div');r.className='row';r.style.setProperty('--c',v.color);
   r.innerHTML='<i></i><h3>'+esc(v.name)+'</h3><p><b>Tempat sampah '+esc(v.bin)+'.</b> '+esc(v.examples)+'.</p>';
   list.appendChild(r);
  });
 }catch(e){ans.textContent='Gagal memuat data.'}
}
async function search(){
 const t=$('#q').value.trim();
 if(!t){ans.textContent='Ketik nama barang dulu, misalnya "baterai".';mark(null);return}
 try{
  const d=await api('/api/search?q='+encodeURIComponent(t));
  if(d.match){showCat(d.match,'<b>'+esc(t)+'</b> masuk ke ')}
  else{ans.innerHTML='Belum ada data untuk "'+esc(t)+'".<small>Coba kata yang lebih umum, atau masukkan ke Residu jika tidak bisa didaur ulang.</small>';mark(null)}
 }catch(e){ans.textContent=e.message}
}
$('#go').onclick=search;$('#q').addEventListener('keydown',e=>{if(e.key==='Enter')search()});

let Q=[],qi=0,sc=0;
function rq(){
 const box=$('#quiz');
 if(!Q.length){box.textContent='Soal belum tersedia.';return}
 if(qi>=Q.length){box.innerHTML='<h3 style="font-size:30px">Skor kamu '+sc+' dari '+Q.length+'</h3><p class="lead">'+(sc>=4?'Bagus. Kamu sudah paham cara memilah sampah.':'Lumayan. Cek lagi bagian kategori, lalu coba sekali lagi.')+'</p><button class="btn" id="rs" type="button" style="margin-top:18px">Ulangi kuis</button>';$('#rs').onclick=()=>{qi=0;sc=0;rq()};return}
 const q=Q[qi],a=q.answer;
 box.innerHTML='<div class="meta">Pertanyaan '+(qi+1)+' dari '+Q.length+'</div><h3 style="font-size:26px">'+esc(q.question)+'</h3>'+q.options.map((x,i)=>'<button class="opt" data-i="'+i+'" type="button">'+esc(x)+'</button>').join('')+'<p class="fb" id="fb" aria-live="polite"></p><button class="btn hidden" id="nx" type="button">'+(qi===Q.length-1?'Lihat skor':'Lanjut')+'</button>';
 box.querySelectorAll('.opt').forEach(b=>b.onclick=()=>{
  const i=+b.dataset.i;if(i===a)sc++;
  box.querySelectorAll('.opt').forEach((x,j)=>{x.disabled=true;if(j===a)x.classList.add('ok');else if(j===i)x.classList.add('no')});
  $('#fb').textContent=(i===a?'Benar. ':'Belum tepat. ')+q.explanation;$('#nx').classList.remove('hidden');$('#nx').focus();
  $('#nx').onclick=()=>{qi++;rq()};
 });
}
async function loadQuiz(){
 try{Q=await api('/api/quiz');rq()}catch(e){$('#quiz').textContent='Gagal memuat kuis.'}
}

$('#rf').addEventListener('submit',async e=>{
 e.preventDefault();$('#rerr').textContent='';
 try{
  await api('/api/reports',{method:'POST',headers:{'Content-Type':'application/json'},
   body:JSON.stringify({type:$('#rj').value,location:$('#rl').value,notes:$('#rk').value})});
  $('#dtxt').textContent='Terima kasih. Laporan '+$('#rj').value.toLowerCase()+' di '+$('#rl').value+' sudah dicatat.';
  $('#rf').classList.add('hidden');$('#done').classList.remove('hidden');
 }catch(err){$('#rerr').textContent=err.message}
});
$('#again').onclick=()=>{$('#rf').reset();$('#rf').classList.remove('hidden');$('#done').classList.add('hidden')};

loadCats();loadQuiz();
