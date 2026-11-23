<?php
// 꾸샘 클래스카드 답지 찾기 (나스 web 공유 폴더판)
// PDF는 아래 폴더에서 읽어 이 페이지를 통해 내보낸다.
$CANDIDATES = [];
foreach (['volume1','volume2','volume3','volume4'] as $v) $CANDIDATES[] = "/$v/homes/gje/Drive/클래스카드 테스트";
$ROOT = null;
foreach ($CANDIDATES as $c) { if (@is_dir($c)) { $ROOT = $c; break; } }

function fail($msg) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

if (isset($_GET['list'])) {
  header('Cache-Control: no-store');
  if (!$ROOT) fail("'클래스카드 테스트' 폴더를 읽을 수 없습니다. PHP open_basedir 또는 http 읽기 권한을 확인해 주세요. (open_basedir=" . ini_get('open_basedir') . ")");
  $out = [];
  try {
    $it = new RecursiveIteratorIterator(
      new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($ROOT, FilesystemIterator::SKIP_DOTS),
        function ($cur) { $n = $cur->getFilename(); return !($n[0] === '.' || $n[0] === '@' || $n[0] === '#'); }
      )
    );
    foreach ($it as $f) {
      if (!$f->isFile() || strtolower($f->getExtension()) !== 'pdf') continue;
      $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($ROOT) + 1));
      if (class_exists('Normalizer')) $rel = Normalizer::normalize($rel, Normalizer::FORM_C);
      $out[] = $rel;
    }
  } catch (Exception $e) { fail('폴더를 읽는 중 오류: ' . $e->getMessage()); }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

if (isset($_GET['file'])) {
  if (!$ROOT) { http_response_code(404); exit; }
  $rel = $_GET['file'];
  $path = $ROOT . '/' . $rel;
  if (!@is_file($path) && class_exists('Normalizer')) {
    $alt = $ROOT . '/' . Normalizer::normalize($rel, Normalizer::FORM_D);
    if (@is_file($alt)) $path = $alt;
  }
  $real = realpath($path);
  $rootReal = realpath($ROOT);
  if (!$real || strpos($real, $rootReal . '/') !== 0 || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') { http_response_code(404); exit('not found'); }
  header('Content-Type: application/pdf');
  header('Content-Length: ' . filesize($real));
  header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode(basename($real)));
  header('Cache-Control: private, max-age=300');
  readfile($real);
  exit;
}
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="답지 찾기">
<title>클래스카드 답지 찾기</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap">
<script src="_answer_page/pdf.min.js"></script>
<style>
:root{
  --bg:#f3f5f8; --surface:#fff; --line:#dde2ea; --fg:#1b2230; --muted:#667085;
  --accent:#2350c8; --accent-soft:#e6ecfb; --pen:#d33a2c; --pen-soft:#fbe9e6; --hit:#fff4c2;
  --ff:"Noto Sans KR",-apple-system,"Apple SD Gothic Neo",sans-serif; --fm:"IBM Plex Mono",ui-monospace,Menlo,monospace;
}
@media (prefers-color-scheme:dark){:root{
  --bg:#12161d; --surface:#1a2029; --line:#2c3441; --fg:#e8ecf2; --muted:#9aa4b5;
  --accent:#7d9bff; --accent-soft:#1f2a47; --pen:#ff7a6b; --pen-soft:#3a1f1c; --hit:#3b3416; color-scheme:dark}}
*{box-sizing:border-box}
html,body{margin:0}
body{background:var(--bg);color:var(--fg);font-family:var(--ff);font-size:16px;-webkit-text-size-adjust:100%}
.wrap{max-width:1000px;margin:0 auto;padding:0 max(16px,env(safe-area-inset-right)) 48px max(16px,env(safe-area-inset-left))}
header{position:sticky;top:0;z-index:5;background:var(--surface);border-bottom:1px solid var(--line);margin:0 calc(-1*max(16px,env(safe-area-inset-left)));padding:calc(12px + env(safe-area-inset-top)) max(16px,env(safe-area-inset-right)) 12px max(16px,env(safe-area-inset-left))}
.top{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:10px}
h1{font-size:1.15rem;font-weight:800;margin:0}
.count{font-size:.8rem;color:var(--muted)}
.search{display:flex;gap:8px}
#q{flex:1;min-width:0;font:inherit;font-size:1.25rem;padding:12px 14px;border:2px solid var(--line);border-radius:12px;background:var(--bg);color:var(--fg)}
#q:focus{outline:none;border-color:var(--accent)}
.btn{font:inherit;font-weight:700;border:0;border-radius:12px;padding:0 16px;min-height:44px;background:var(--accent-soft);color:var(--accent);cursor:pointer}
.hint{font-size:.8rem;color:var(--muted);margin-top:8px}
.chips{display:flex;gap:8px;overflow-x:auto;padding:14px 0 4px;-webkit-overflow-scrolling:touch}
.chip{flex:none;font:inherit;font-weight:600;font-size:.9rem;padding:9px 14px;border-radius:999px;border:1.5px solid var(--line);background:var(--surface);color:var(--fg);cursor:pointer;white-space:nowrap}
.chip[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:#fff}
section.book{margin-top:22px}
.bookhead{font-size:1.02rem;font-weight:700;margin:0 0 10px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
.day{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:10px;display:flex;flex-direction:column;gap:8px;min-width:0}
.day.hit{background:var(--hit);border-color:var(--pen)}
.dn{font-family:var(--fm);font-weight:600;font-size:1rem;overflow-wrap:anywhere}
.dn span{font-family:var(--ff);font-size:.72rem;color:var(--muted);margin-right:4px}
.frow{display:flex;gap:6px}
.f{flex:1;min-width:0;display:flex;align-items:center;justify-content:center;min-height:44px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;background:var(--accent-soft);color:var(--accent);padding:0 6px;text-align:center}
.f.ans{background:var(--pen-soft);color:var(--pen)}
.p{flex:none;width:48px;min-height:44px;border:1.5px solid var(--line);border-radius:9px;background:var(--surface);color:var(--fg);font:inherit;font-size:1.1rem;cursor:pointer}
.empty,.loading{padding:40px 0;text-align:center;color:var(--muted)}
/* 인쇄 창 */
#sheet{position:fixed;inset:0;background:rgba(10,14,20,.45);display:flex;align-items:flex-end;justify-content:center;z-index:20}
#sheet[hidden]{display:none}
.panel{background:var(--surface);width:min(520px,100%);border-radius:16px 16px 0 0;padding:18px max(16px,env(safe-area-inset-right)) calc(18px + env(safe-area-inset-bottom)) max(16px,env(safe-area-inset-left));display:flex;flex-direction:column;gap:12px}
.panel h3{margin:0;font-size:1rem;overflow-wrap:anywhere}
.panel .sub{font-size:.85rem;color:var(--muted)}
.opts{display:grid;gap:8px}
.opt{font:inherit;font-weight:700;min-height:52px;border-radius:12px;border:0;background:var(--accent-soft);color:var(--accent);cursor:pointer}
.opt.main{background:var(--accent);color:#fff}
.range{display:flex;gap:8px;align-items:center}
.range input{width:70px;font:inherit;font-size:1.1rem;padding:8px;border:1.5px solid var(--line);border-radius:10px;background:var(--bg);color:var(--fg);text-align:center}
#printArea{display:none}
@media print{
  @page{size:A4;margin:0}
  body>*:not(#printArea){display:none!important}
  #printArea{display:block}
  #printArea img{display:block;width:100%;height:auto;page-break-after:always;break-after:page}
  #printArea img:last-child{page-break-after:auto;break-after:auto}
}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="top"><h1>클래스카드 답지 찾기</h1><span class="count" id="count"></span></div>
    <div class="search">
      <input id="q" type="search" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="예: ㄱㅂ 12 · 스타터 23 · 어휘끝 5">
      <button class="btn" id="clear" type="button">지우기</button>
    </div>
    <div class="hint">초성이나 교재 이름 일부 + DAY 번호. 🖨 버튼은 시험지 쪽만 바로 인쇄합니다.</div>
  </header>
  <div class="chips" id="chips"></div>
  <main id="list"><div class="loading">나스에서 목록을 읽는 중…</div></main>
</div>

<div id="sheet" hidden>
  <div class="panel" role="dialog" aria-modal="true">
    <h3 id="sTitle"></h3>
    <div class="sub" id="sSub">쪽 수를 확인하는 중…</div>
    <div class="opts">
      <button class="opt main" id="pHalf" type="button" disabled>시험지만 인쇄</button>
      <button class="opt" id="pAll" type="button" disabled>전체 인쇄</button>
      <div class="range"><input id="r1" type="number" min="1" inputmode="numeric"> ~ <input id="r2" type="number" min="1" inputmode="numeric">쪽 <button class="opt" id="pRange" type="button" style="flex:1" disabled>이 쪽만 인쇄</button></div>
      <button class="opt" id="sClose" type="button" style="background:transparent;color:var(--muted)">닫기</button>
    </div>
  </div>
</div>
<div id="printArea"></div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc="_answer_page/pdf.worker.min.js";
const CHO="ㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎ";
const cho=s=>{let o="";for(const ch of s){const c=ch.charCodeAt(0)-0xAC00;o+=(c>=0&&c<11172)?CHO[Math.floor(c/588)]:ch;}return o;};
const norm=s=>s.normalize("NFC").toLowerCase().replace(/[\s\[\]\-_&()~.,#]/g,"");
const $=id=>document.getElementById(id);
const fileUrl=p=>"?file="+encodeURIComponent(p);
const pad=x=>String(x).padStart(2,"0");
let BOOKS=[],book=-1;

function build(paths){
  const map=new Map();
  paths.forEach(p=>{
    const parts=p.split("/");
    const folder=parts.length>1?parts[0]:"(기타)";
    const name=parts[parts.length-1].replace(/\.pdf$/i,"");
    const sub=parts.slice(1,-1).join(" / ");
    if(!map.has(folder))map.set(folder,new Map());
    const groups=map.get(folder);
    const m=name.match(/day\s*0*(\d+)(?:\s*(?:[-&~,]|및)\s*(?:day\s*)?0*(\d+))?/i);
    let key,a=null,b=null,head,label;
    if(m){a=+m[1];b=m[2]?+m[2]:a;key=sub+"|"+a+"-"+b;head=(sub?sub+" · ":"")+"DAY "+pad(a)+(b!==a?"–"+pad(b):"");
      label=name.slice(m.index+m[0].length).replace(/^[\s\-_]+/,"").trim();}
    else{key=sub+"|"+name;head=(sub?sub+" · ":"")+name;label="";}
    if(!groups.has(key))groups.set(key,{a,b,head,files:[],nums:(name.match(/\d+/g)||[]).map(Number)});
    groups.get(key).files.push({path:p,label:label||"PDF",ans:/답/.test(label)});
  });
  const coll=new Intl.Collator("ko",{numeric:true});
  BOOKS=[...map.entries()].sort((x,y)=>coll.compare(x[0],y[0])).map(([name,groups],i)=>{
    const days=[...groups.values()].sort((x,y)=>(x.a!=null&&y.a!=null&&x.head.split(" · ").slice(0,-1).join()===y.head.split(" · ").slice(0,-1).join())?x.a-y.a:coll.compare(x.head,y.head));
    days.forEach(d=>d.files.sort((x,y)=>(x.label==="PDF"?-1:0)-(y.label==="PDF"?-1:0)||coll.compare(x.label,y.label)));
    const disp=name.replace(/^\d+(_\d+)?[_\s]+/,"");
    return {i,name,disp,days,key:norm(name),ckey:norm(cho(name))};
  });
}

function parse(q){const nums=(q.match(/\d+/g)||[]).map(Number);
  const words=q.replace(/\d+/g," ").replace(/day/ig," ").split(/\s+/).map(norm).filter(Boolean);return {n:nums.length?nums[0]:null,words};}
const bookOk=(b,ws)=>ws.every(w=>b.key.includes(w)||b.ckey.includes(norm(cho(w))));
const dayOk=(d,n)=>d.a!=null?(n>=d.a&&n<=d.b):d.nums.includes(n);

function render(){
  const {n,words}=parse($("q").value.trim());const list=$("list");list.innerHTML="";let shown=0;
  BOOKS.forEach(b=>{
    if(book>=0&&b.i!==book)return; if(words.length&&!bookOk(b,words))return;
    let days=b.days; if(n!==null)days=days.filter(d=>dayOk(d,n)); if(!days.length)return;
    const sec=document.createElement("section");sec.className="book";
    const h=document.createElement("h2");h.className="bookhead";h.textContent=b.name;sec.appendChild(h);
    const g=document.createElement("div");g.className="grid";
    days.forEach(d=>{
      const t=document.createElement("div");t.className="day"+(n!==null?" hit":"");
      const dn=document.createElement("div");dn.className="dn";dn.textContent=d.head;t.appendChild(dn);
      d.files.forEach(f=>{
        const row=document.createElement("div");row.className="frow";
        const a=document.createElement("a");a.className="f"+(f.ans?" ans":"");a.href=fileUrl(f.path);a.target="_blank";a.textContent=f.label;
        const p=document.createElement("button");p.className="p";p.type="button";p.textContent="🖨";p.setAttribute("aria-label",f.label+" 인쇄");
        p.onclick=()=>openSheet(f,d.head,b.disp);p.__re={path:f.path,head:d.head,book:b.disp};
        row.append(a,p);t.appendChild(row);
      });
      g.appendChild(t);shown++;
    });
    sec.appendChild(g);list.appendChild(sec);
  });
  if(!shown)list.innerHTML='<div class="empty">찾는 파일이 없습니다. 교재 이름이나 번호를 바꿔 보세요.</div>';
}
function chips(){const c=$("chips");c.innerHTML="";
  [{i:-1,disp:"전체"},...BOOKS].forEach(x=>{const btn=document.createElement("button");btn.type="button";btn.className="chip";
    btn.setAttribute("aria-pressed",String(book===x.i));btn.textContent=x.disp;btn.onclick=()=>{book=x.i;chips();render();};c.appendChild(btn);});}

/* 인쇄 */
// 사파리는 버튼을 누른 그 순간에만 인쇄 창을 허용한다. 그래서 쪽 그림을 먼저 만들어 두고, 누르면 바로 인쇄한다.
let cur=null,prepared={key:"",ready:false},job=0;
const BTN={pHalf:"",pAll:"",pRange:""};
function setBtn(id,text,dis){$(id).textContent=text;$(id).disabled=!!dis;}
async function prepare(a,b){
  const my=++job;prepared={key:a+"-"+b,ready:false};
  const imgs=[];
  for(let i=a;i<=b;i++){
    const page=await cur.getPage(i);const vp=page.getViewport({scale:2.4});
    const cv=document.createElement("canvas");cv.width=vp.width;cv.height=vp.height;
    await page.render({canvasContext:cv.getContext("2d"),viewport:vp}).promise;
    const img=new Image();img.src=cv.toDataURL("image/jpeg",.92);await img.decode();imgs.push(img);
    if(my!==job)return false;
  }
  const area=$("printArea");area.innerHTML="";imgs.forEach(im=>area.appendChild(im));
  prepared.ready=true;return true;
}
// 사파리는 한 화면에서 인쇄를 두 번째부터 막는다. 그래서 인쇄가 끝나면 화면을 몰래 새로 불러와
// (검색어·교재·스크롤은 그대로) 다음 인쇄도 '첫 번째 인쇄'가 되게 한다.
let printed=false;
const SKEY="ccState";
function saveState(reopen){try{sessionStorage.setItem(SKEY,JSON.stringify({q:$("q").value,book,y:window.scrollY,reopen:reopen||null}));}catch(e){}}
function freshReload(reopen){saveState(reopen);location.reload();}
function press(id,a,b){
  if(printed){freshReload(cur&&cur.__f?{path:cur.__f.path,head:cur.__head,book:cur.__book}:null);return;}
  if(prepared.key===a+"-"+b&&prepared.ready){printed=true;window.print();return;}   // 누른 순간 바로 인쇄
  setBtn(id,"준비 중…",true);
  prepare(a,b).then(ok=>{if(ok)setBtn(id,"준비 완료 · 눌러서 인쇄",false);});
}
async function openSheet(f,head,bookName){
  job++;prepared={key:"",ready:false};$("printArea").innerHTML="";
  $("sheet").hidden=false;$("sTitle").textContent=bookName+" · "+head+" · "+f.label;$("sSub").textContent="쪽 수를 확인하는 중…";
  ["pHalf","pAll","pRange"].forEach(id=>$(id).disabled=true);
  try{
    const doc=await pdfjsLib.getDocument(fileUrl(f.path)).promise;cur=doc;doc.__f=f;doc.__head=head;doc.__book=bookName;const n=doc.numPages;
    // 답이 함께 든 파일은 앞 절반이 시험지, 뒤 절반이 답지(4쪽 이상 짝수일 때)
    const testPages=(!/시험지/.test(f.label)&&n>=4&&n%2===0)?n/2:n;
    $("sSub").textContent=`전체 ${n}쪽 · 시험지 ${testPages===n?"전체":"1–"+testPages+"쪽"}`;
    BTN.pHalf=testPages===n?`시험지 인쇄 (${n}쪽)`:`시험지만 인쇄 (1–${testPages}쪽)`;BTN.pAll="전체 인쇄";BTN.pRange="이 쪽만 인쇄";
    setBtn("pAll",BTN.pAll,false);setBtn("pRange",BTN.pRange,false);
    $("pHalf").onclick=()=>press("pHalf",1,testPages);
    $("pAll").onclick=()=>press("pAll",1,n);
    $("r1").value=1;$("r2").value=testPages;$("r1").max=$("r2").max=n;
    $("pRange").onclick=()=>{const a=Math.max(1,+$("r1").value||1),b=Math.min(n,+$("r2").value||n);press("pRange",a,Math.max(a,b));};
    // 시험지 쪽은 미리 준비해 두어 한 번 누르면 바로 인쇄되게
    setBtn("pHalf","시험지 준비 중…",true);
    prepare(1,testPages).then(ok=>{if(ok)setBtn("pHalf",BTN.pHalf,false);});
  }catch(e){$("sSub").textContent="파일을 열지 못했습니다: "+e.message;}
}
// 아이패드는 인쇄 창이 '뜨는 순간' afterprint를 보낸다. 그때 새로고침하면 인쇄 창이 닫히므로,
// 인쇄 창이 닫힌 뒤 화면을 처음 건드릴 때 몰래 새로 불러온다(누른 🖨 파일은 다시 열어 준다).
let armed=false;
window.addEventListener("afterprint",()=>{setTimeout(()=>{armed=true;},1200);});
document.addEventListener("pointerdown",e=>{
  if(!armed)return;armed=false;
  const pb=e.target.closest&&e.target.closest("button.p");
  let re=null;
  if(pb&&pb.__re)re=pb.__re;
  else if(!$("sheet").hidden&&cur&&cur.__f&&e.target.id!=="sheet"&&!(e.target.closest&&e.target.closest("#sClose")))re={path:cur.__f.path,head:cur.__head,book:cur.__book};
  e.preventDefault();e.stopPropagation();freshReload(re);
},true);
$("sClose").onclick=()=>{job++;$("sheet").hidden=true;};
$("sheet").addEventListener("click",e=>{if(e.target.id==="sheet")$("sheet").hidden=true;});

$("q").addEventListener("input",render);
$("clear").onclick=()=>{$("q").value="";render();$("q").focus();};
fetch("?list=1").then(async r=>{const j=await r.json();if(!r.ok||j.error)throw new Error(j.error||r.status);return j;}).then(paths=>{build(paths);
  $("count").textContent=`교재 ${BOOKS.length}개 · 파일 ${paths.length}개`;
  let st=null;try{st=JSON.parse(sessionStorage.getItem(SKEY)||"null");sessionStorage.removeItem(SKEY);}catch(e){}
  if(st){$("q").value=st.q||"";book=typeof st.book==="number"?st.book:-1;}
  chips();render();
  if(st){window.scrollTo(0,st.y||0);
    if(st.reopen){for(const b of BOOKS)for(const d of b.days)for(const f of d.files)if(f.path===st.reopen.path){openSheet(f,st.reopen.head,st.reopen.book);return;}}}
  })
 .catch(e=>{$("list").innerHTML='<div class="empty">목록을 읽지 못했습니다. '+e.message+'</div>';});
</script>
</body>
</html>
