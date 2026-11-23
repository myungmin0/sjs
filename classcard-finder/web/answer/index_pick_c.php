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

// 첫 화면에 보일 교재(폴더) 목록. 이 파일 옆 _picked.json 에 저장한다.
$PICK = __DIR__ . '/_picked.json';
if (isset($_GET['picked'])) {
  header('Cache-Control: no-store');
  header('Content-Type: application/json; charset=utf-8');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) fail('잘못된 요청');
    $in = array_values(array_unique(array_filter($in, 'is_string')));
    $ok = @file_put_contents($PICK, json_encode($in, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    if ($ok === false) fail('나스에 저장하지 못했습니다(web\\answer 폴더 쓰기 권한 필요).');
    echo json_encode(['ok' => true, 'n' => count($in)]);
    exit;
  }
  $d = @file_get_contents($PICK);
  echo ($d === false) ? 'null' : $d;
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
<meta name="apple-mobile-web-app-title" content="답지 C">
<title>C · 클래스카드 답지 찾기</title>
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
.empty,.loading{padding:40px 0;text-align:center;color:var(--muted)}
.pickbar{display:flex;align-items:center;gap:8px;padding-top:12px}
.pickbar .chips{flex:1;min-width:0;padding-top:2px}
#pickBtn{flex:none;font:inherit;font-weight:700;font-size:.9rem;padding:9px 14px;border-radius:999px;border:1.5px dashed var(--accent);background:transparent;color:var(--accent);cursor:pointer;white-space:nowrap}
#picker{position:fixed;inset:0;background:rgba(10,14,20,.45);display:flex;align-items:flex-end;justify-content:center;z-index:20}
#picker[hidden]{display:none}
.ppanel{background:var(--surface);width:min(560px,100%);max-height:88vh;border-radius:16px 16px 0 0;display:flex;flex-direction:column}
.phead{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:16px 16px 10px;border-bottom:1px solid var(--line)}
.phead h3{margin:0;font-size:1.05rem}
.phead small{color:var(--muted);font-size:.8rem}
.plist{overflow-y:auto;-webkit-overflow-scrolling:touch;padding:6px 16px}
.prow{display:flex;align-items:center;gap:12px;min-height:52px;border-bottom:1px solid var(--line);cursor:pointer}
.prow:last-child{border-bottom:0}
.prow span{flex:1;min-width:0;overflow-wrap:anywhere;font-size:.95rem}
.prow em{font-style:normal;color:var(--muted);font-size:.8rem}
.sw{appearance:none;-webkit-appearance:none;flex:none;width:50px;height:30px;border-radius:999px;background:var(--line);position:relative;cursor:pointer;transition:background .15s}
.sw::after{content:"";position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .15s}
.sw:checked{background:var(--accent)}
.sw:checked::after{transform:translateX(20px)}
.pfoot{display:flex;gap:8px;padding:12px 16px calc(12px + env(safe-area-inset-bottom));border-top:1px solid var(--line)}
.pfoot button{flex:1;font:inherit;font-weight:700;min-height:48px;border-radius:12px;border:0;cursor:pointer;background:var(--accent-soft);color:var(--accent)}
.pfoot button.main{background:var(--accent);color:#fff}
#pmsg{font-size:.8rem;color:var(--pen);padding:0 16px}
.pg{font-size:.78rem;color:var(--muted);text-align:center;margin-top:-2px}
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
    <div class="hint">초성이나 교재 이름 일부 + DAY 번호. 인쇄: 파일을 열고 공유 → 프린트. 답이 든 파일은 프린트 창 '범위'에서 시험지 쪽만 고르세요.</div>
  </header>
  <div class="pickbar"><div class="chips" id="chips"></div><button id="pickBtn" type="button">교재 고르기</button></div>
  <main id="list"><div class="loading">나스에서 목록을 읽는 중…</div></main>
</div>



<div id="picker" hidden>
  <div class="ppanel" role="dialog" aria-modal="true" aria-labelledby="pTitle">
    <div class="phead"><div><h3 id="pTitle">첫 화면에 보일 교재</h3><small id="pCount"></small></div>
      <div style="display:flex;gap:6px"><button class="chip" id="pAllOn" type="button">모두 켜기</button><button class="chip" id="pAllOff" type="button">모두 끄기</button></div></div>
    <div class="plist" id="plist"></div>
    <div id="pmsg"></div>
    <div class="pfoot"><button id="pCancel" type="button">취소</button><button class="main" id="pSave" type="button">저장</button></div>
  </div>
</div>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc="_answer_page/pdf.worker.min.js";
const CHO="ㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎ";
const cho=s=>{let o="";for(const ch of s){const c=ch.charCodeAt(0)-0xAC00;o+=(c>=0&&c<11172)?CHO[Math.floor(c/588)]:ch;}return o;};
const norm=s=>s.normalize("NFC").toLowerCase().replace(/[\s\[\]\-_&()~.,#]/g,"");
const $=id=>document.getElementById(id);
const fileUrl=p=>"?file="+encodeURIComponent(p);
const pad=x=>String(x).padStart(2,"0");
let BOOKS=[],book=-1,PICKED=null; // PICKED: 보일 교재 폴더 이름 목록(null이면 전부)
const shownBook=b=>!PICKED||PICKED.includes(b.name);

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
    if(!shownBook(b))return;
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
        const a=document.createElement("a");a.className="f"+(f.ans?" ans":"");a.href=fileUrl(f.path)+"&n=x.pdf";a.textContent=f.label;
        row.append(a);t.appendChild(row);
        const pg=document.createElement("div");pg.className="pg";pg.dataset.path=f.path;pg.dataset.lab=f.label;t.appendChild(pg);
      });
      g.appendChild(t);shown++;
    });
    sec.appendChild(g);list.appendChild(sec);
  });
  if(!shown)list.innerHTML=(PICKED&&!PICKED.length)?'<div class="empty">켜 둔 교재가 없습니다. 오른쪽 위 \'교재 고르기\'에서 켜 주세요.</div>':'<div class="empty">찾는 파일이 없습니다. 교재 이름이나 번호를 바꿔 보세요.</div>';
}
function chips(){const c=$("chips");c.innerHTML="";
  [{i:-1,disp:"전체"},...BOOKS.filter(shownBook)].forEach(x=>{const btn=document.createElement("button");btn.type="button";btn.className="chip";
    btn.setAttribute("aria-pressed",String(book===x.i));btn.textContent=x.disp;btn.onclick=()=>{book=x.i;chips();render();};c.appendChild(btn);});}

// 파일마다 쪽 수를 읽어 '시험지 몇 쪽'을 적어 둔다(보이는 파일이 적을 때만).
const PG={};let pgJob=0;
async function pages(){
  const els=[...document.querySelectorAll(".pg")];if(!els.length||els.length>30)return;
  const my=++pgJob;
  for(const el of els){
    if(my!==pgJob)return;
    const p=el.dataset.path;
    try{ if(PG[p]==null){const d=await pdfjsLib.getDocument(fileUrl(p)).promise;PG[p]=d.numPages;d.destroy();}
      const n=PG[p];const t=(!/시험지/.test(el.dataset.lab)&&n>=4&&n%2===0)?n/2:n;
      el.textContent=t===n?`${n}쪽 · 전부 시험지`:`${n}쪽 · 시험지 1–${t}쪽`;
    }catch(e){}
  }
}
/* 교재 고르기 */
const LKEY="ccPicked";
function openPicker(){
  const list=$("plist");list.innerHTML="";$("pmsg").textContent="";
  BOOKS.forEach(b=>{
    const row=document.createElement("label");row.className="prow";
    const n=b.days.reduce((s,d)=>s+d.files.length,0);
    row.innerHTML=`<span></span><em>${n}개</em>`;row.querySelector("span").textContent=b.name;
    const cb=document.createElement("input");cb.type="checkbox";cb.className="sw";cb.value=b.name;cb.checked=shownBook(b);
    cb.addEventListener("change",cnt);row.appendChild(cb);list.appendChild(row);
  });
  cnt();$("picker").hidden=false;
}
function cnt(){const all=[...document.querySelectorAll("#plist .sw")];$("pCount").textContent=`${all.filter(x=>x.checked).length}개 켬 · 전체 ${all.length}개`;}
$("pickBtn").onclick=openPicker;
$("pCancel").onclick=()=>{$("picker").hidden=true;};
$("picker").addEventListener("click",e=>{if(e.target.id==="picker")$("picker").hidden=true;});
$("pAllOn").onclick=()=>{document.querySelectorAll("#plist .sw").forEach(x=>x.checked=true);cnt();};
$("pAllOff").onclick=()=>{document.querySelectorAll("#plist .sw").forEach(x=>x.checked=false);cnt();};
$("pSave").onclick=async()=>{
  const sel=[...document.querySelectorAll("#plist .sw")].filter(x=>x.checked).map(x=>x.value);
  $("pSave").disabled=true;$("pSave").textContent="저장 중…";
  let ok=false;
  try{const r=await fetch("?picked=1",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(sel)});const j=await r.json();ok=r.ok&&j.ok;if(!ok)$("pmsg").textContent=j.error||"저장 실패";}
  catch(e){$("pmsg").textContent="저장 실패: "+e.message;}
  try{localStorage.setItem(LKEY,JSON.stringify(sel));}catch(e){}
  $("pSave").disabled=false;$("pSave").textContent="저장";
  PICKED=sel;if(book>=0&&!shownBook(BOOKS[book]))book=-1;chips();render();
  if(ok)$("picker").hidden=true;else $("pmsg").textContent+=" · 이 기기에만 저장했습니다.";
};

// 같은 탭에서 파일을 열었다가 뒤로 오면 검색어·교재·스크롤을 그대로 되살린다.
const SKEY="ccState";
function saveState(){try{sessionStorage.setItem(SKEY,JSON.stringify({q:$("q").value,book,y:window.scrollY}));}catch(e){}}
document.addEventListener("click",e=>{if(e.target.closest&&e.target.closest("a.f"))saveState();},true);
let pgT=0;

$("q").addEventListener("input",render);
const _render=render;render=function(){_render();clearTimeout(pgT);pgT=setTimeout(pages,400);};
$("clear").onclick=()=>{$("q").value="";render();$("q").focus();};
Promise.all([
  fetch("?list=1").then(async r=>{const j=await r.json();if(!r.ok||j.error)throw new Error(j.error||r.status);return j;}),
  fetch("?picked=1").then(r=>r.json()).catch(()=>null)
]).then(([paths,pk])=>{build(paths);
  if(!Array.isArray(pk)){try{pk=JSON.parse(localStorage.getItem(LKEY)||"null");}catch(e){pk=null;}}
  PICKED=Array.isArray(pk)?pk:null;
  $("count").textContent=`교재 ${BOOKS.length}개 · 파일 ${paths.length}개`;
  let st=null;try{st=JSON.parse(sessionStorage.getItem(SKEY)||"null");sessionStorage.removeItem(SKEY);}catch(e){}
  if(st){$("q").value=st.q||"";book=typeof st.book==="number"?st.book:-1;}
  chips();render();
  if(st)window.scrollTo(0,st.y||0);
  })
 .catch(e=>{$("list").innerHTML='<div class="empty">목록을 읽지 못했습니다. '+e.message+'</div>';});
</script>
</body>
</html>
