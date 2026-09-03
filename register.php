<?php
require_once 'includes/db.php';
require_once 'includes/theme.php';
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? 'assets/img/'.$b['site_logo'] : 'assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$pixel   = get_setting('meta_pixel_id');
$error   = $_SESSION['error']??''; unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>ভর্তি হন — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="assets/css/theme.css.php">
<link rel="stylesheet" href="assets/css/style.css">
<?php if($pixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?=htmlspecialchars($pixel)?>');fbq('track','PageView');</script>
<?php endif; ?>
<style>
body{background:var(--g800);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:16px 16px max(20px,env(safe-area-inset-bottom));position:relative;}
body::after{content:'﷽';position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);font-size:min(240px,55vw);color:rgba(255,255,255,.025);pointer-events:none;line-height:1;z-index:0;}
.wrap{width:100%;max-width:480px;position:relative;z-index:1;padding:8px 0 24px;}
.logo-top{text-align:center;margin-bottom:20px;}
.logo-ring{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--g700),var(--g600));border:3px solid var(--gold);margin:0 auto 10px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.3);}
.logo-ring img{width:44px;height:44px;object-fit:contain;}
.logo-top h1{color:#fff;font-size:1.2rem;font-weight:700;}
.logo-top p{color:rgba(255,255,255,.4);font-size:.75rem;}
.card{background:#fff;border-radius:20px;padding:22px 18px;box-shadow:0 20px 56px rgba(0,0,0,.4);}
.steps{display:flex;align-items:center;gap:6px;margin-bottom:22px;}
.sd{width:30px;height:30px;border-radius:50%;font-size:.78rem;font-weight:700;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;}
.sd.done{background:var(--g600);color:#fff;} .sd.active{background:var(--gold);color:var(--g800);} .sd.idle{background:var(--border);color:var(--muted);}
.sl{flex:1;height:3px;border-radius:3px;background:var(--border);transition:background .3s;} .sl.done{background:var(--g600);}
.pkg-card{border:2px solid var(--border);border-radius:12px;padding:13px 14px;cursor:pointer;display:flex;gap:11px;align-items:flex-start;margin-bottom:9px;transition:all .15s;}
.pkg-card:hover{border-color:var(--g500);} .pkg-card.sel{border-color:var(--g600);background:var(--g100);}
.pkg-card.gsel{border-color:var(--gold);background:#fffdf5;}
.pkg-radio{width:20px;height:20px;border-radius:50%;border:2px solid var(--border);flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.pkg-card.sel .pkg-radio{border-color:var(--g600);background:var(--g600);}
.pkg-card.gsel .pkg-radio{border-color:var(--gold);background:var(--gold);}
.pkg-radio::after{content:'';width:8px;height:8px;border-radius:50%;background:#fff;}
.pkg-name{font-weight:700;color:var(--ink);font-size:.9rem;}
.pkg-price{font-size:1.1rem;font-weight:700;color:var(--g600);margin:2px 0;}
.pkg-card.gsel .pkg-price{color:var(--gold);}
.pkg-feat{font-size:.74rem;color:var(--muted);line-height:1.5;}
.ptag{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.67rem;font-weight:700;margin-top:4px;}
.ptag-pop{background:#dcfce7;color:#166534;} .ptag-best{background:#fef9c3;color:#92400e;}
.sum{background:var(--g50);border:1px solid var(--border);border-radius:10px;padding:13px;margin-bottom:14px;}
.srow{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:.875rem;}
.srow:last-child{border-bottom:none;padding-bottom:0;}
.srow span:first-child{color:var(--muted);} .srow span:last-child{font-weight:700;color:var(--ink);}
.btn-reg{width:100%;padding:13px;background:var(--g600);color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:var(--font);cursor:pointer;margin-top:4px;transition:all .15s;}
.btn-reg:hover{background:var(--g500);} .btn-reg:disabled{opacity:.6;cursor:not-allowed;}
.btn-back{width:100%;padding:11px;background:var(--g50);color:var(--muted);border:1.5px solid var(--border);border-radius:9px;font-size:.9rem;font-weight:600;font-family:var(--font);cursor:pointer;margin-top:8px;}
.err-b{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;font-size:.85rem;display:none;margin-bottom:10px;}
.err-b.show{display:block;}
.link-row{text-align:center;margin-top:14px;}
.link-row a{color:rgba(255,255,255,.4);font-size:.8rem;text-decoration:none;}
</style>
<script>
(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
</script>
<script src="assets/js/theme.js" defer></script>
</head>
<body>
<div class="wrap">
  <div class="logo-top">
    <div class="logo-ring"><img src="<?= htmlspecialchars($__logo) ?>" alt="<?= htmlspecialchars($__sname) ?>"></div>
    <h1><?= htmlspecialchars($__sname) ?></h1>
    <p>নতুন ছাত্র ভর্তি</p>
  </div>
  <div class="card">
    <div class="steps">
      <div class="sd active" id="sd1">১</div><div class="sl" id="sl1"></div>
      <div class="sd idle"   id="sd2">২</div><div class="sl" id="sl2"></div>
      <div class="sd idle"   id="sd3">৩</div>
    </div>
    <?php if($error): ?><div class="err-b show">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- STEP 1 -->
    <div id="s1">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:14px;">👤 ব্যক্তিগত তথ্য</h2>
      <div class="form-group"><label>পূর্ণ নাম *</label><input type="text" id="fn" placeholder="আপনার নাম" autocomplete="name"></div>
      <div class="form-grid">
        <div class="form-group"><label>ইমেইল *</label><input type="email" id="fe" placeholder="email@example.com" inputmode="email"></div>
        <div class="form-group"><label>WhatsApp *</label><input type="tel" id="fp" placeholder="01XXXXXXXXX" inputmode="tel"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label>পাসওয়ার্ড *</label><input type="password" id="fw" placeholder="কমপক্ষে ৬ অক্ষর"></div>
        <div class="form-group"><label>কার জন্য?</label>
          <select id="ff"><option value="নিজের জন্য">নিজের জন্য</option><option value="সন্তানের জন্য">সন্তানের জন্য</option><option value="পরিবারের জন্য">পরিবারের জন্য</option></select>
        </div>
      </div>
      <div class="form-group"><label>গার্ডিয়ান নম্বর (ঐচ্ছিক)</label><input type="tel" id="fg" placeholder="01XXXXXXXXX"></div>
      <div class="err-b" id="e1"></div>
      <button class="btn-reg" onclick="goStep(2)">পরবর্তী →</button>
    </div>

    <!-- STEP 2 -->
    <div id="s2" style="display:none;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:14px;">📦 প্যাকেজ বেছে নিন</h2>
      <div id="pk_basic" class="pkg-card" onclick="pickPkg('basic',this)">
        <div class="pkg-radio"></div>
        <div><div class="pkg-name">🥉 বেসিক</div><div class="pkg-price">৳২,০০০<span style="font-size:.8rem;font-weight:400;color:var(--muted);"> /মাস</span></div><div class="pkg-feat">৮টি গ্রুপ ক্লাস · ৩০ মিনিট · মাসিক রিপোর্ট</div></div>
      </div>
      <div id="pk_standard" class="pkg-card sel" onclick="pickPkg('standard',this)">
        <div class="pkg-radio"></div>
        <div><div class="pkg-name">🥈 স্ট্যান্ডার্ড</div><div class="pkg-price">৳৩,২০০<span style="font-size:.8rem;font-weight:400;color:var(--muted);"> /মাস</span></div><div class="pkg-feat">১০টি ক্লাস · ছোট গ্রুপ · WhatsApp সাপোর্ট</div><div><span class="ptag ptag-pop">⭐ সবচেয়ে জনপ্রিয়</span></div></div>
      </div>
      <div id="pk_premium" class="pkg-card gsel" onclick="pickPkg('premium',this)">
        <div class="pkg-radio"></div>
        <div><div class="pkg-name">🥇 প্রিমিয়াম</div><div class="pkg-price">৳৩,৮০০<span style="font-size:.8rem;font-weight:400;color:var(--muted);"> /মাস</span></div><div class="pkg-feat">১৬টি ১-on-১ · ৪৫ মিনিট · সার্টিফিকেট</div><div><span class="ptag ptag-best">🏆 সেরা মান</span></div></div>
      </div>
      <button class="btn-reg" style="margin-top:14px;" onclick="goStep(3)">পরবর্তী →</button>
      <button class="btn-back" onclick="goStep(1)">← পিছনে</button>
    </div>

    <!-- STEP 3 -->
    <div id="s3" style="display:none;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:14px;">✅ নিশ্চিত করুন</h2>
      <div class="sum" id="sumBox"></div>
      <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:11px 13px;font-size:.82rem;color:#92400e;margin-bottom:14px;">⚠️ ভর্তির পর bKash/Nagad-এ পেমেন্ট করুন। অ্যাডমিন কনফার্ম করলে ক্লাস শুরু হবে।</div>
      <form id="regForm" action="api/auth.php" method="POST">
        <input type="hidden" name="action"         value="register">
        <input type="hidden" name="name"           id="hn">
        <input type="hidden" name="email"          id="he">
        <input type="hidden" name="phone"          id="hp">
        <input type="hidden" name="password"       id="hw">
        <input type="hidden" name="package"        id="hpkg" value="standard">
        <input type="hidden" name="for_whom"       id="hf">
        <input type="hidden" name="guardian_phone" id="hg">
        <button type="submit" class="btn-reg" id="regBtn" onclick="prep()">✅ ভর্তি সম্পন্ন করুন</button>
      </form>
      <button class="btn-back" onclick="goStep(2)">← পিছনে</button>
    </div>
  </div>
  <div class="link-row"><a href="login.php">← লগিনে ফিরুন</a></div>
</div>
<script>
const PKG={basic:'বেসিক — ৳২,০০০/মাস',standard:'স্ট্যান্ডার্ড — ৳৩,২০০/মাস',premium:'প্রিমিয়াম — ৳৩,৮০০/মাস'};
const PAMT={basic:2000,standard:3200,premium:3800};
let selPkg='standard';

function pickPkg(pkg,el){
  selPkg=pkg;
  document.querySelectorAll('.pkg-card').forEach(c=>c.classList.remove('sel','gsel'));
  el.classList.add(pkg==='premium'?'gsel':'sel');
}

function goStep(n){
  if(n===2){
    const nm=document.getElementById('fn').value.trim();
    const em=document.getElementById('fe').value.trim();
    const ph=document.getElementById('fp').value.trim();
    const pw=document.getElementById('fw').value.trim();
    const el=document.getElementById('e1');
    if(!nm||!em||!ph||!pw){el.textContent='❌ সব আবশ্যক (*) ঘর পূরণ করুন।';el.classList.add('show');return;}
    if(pw.length<6){el.textContent='❌ পাসওয়ার্ড কমপক্ষে ৬ অক্ষর।';el.classList.add('show');return;}
    el.classList.remove('show');
  }
  if(n===3){
    document.getElementById('sumBox').innerHTML=
      `<div class="srow"><span>নাম:</span><span>${document.getElementById('fn').value.trim()}</span></div>
       <div class="srow"><span>WhatsApp:</span><span>${document.getElementById('fp').value.trim()}</span></div>
       <div class="srow"><span>কার জন্য:</span><span>${document.getElementById('ff').value}</span></div>
       <div class="srow"><span>প্যাকেজ:</span><span style="color:var(--g600)">${PKG[selPkg]}</span></div>`;
  }
  ['s1','s2','s3'].forEach(id=>document.getElementById(id).style.display='none');
  document.getElementById('s'+n).style.display='block';
  [1,2,3].forEach(i=>{
    document.getElementById('sd'+i).className='sd '+(i<n?'done':i===n?'active':'idle');
    if(i<3) document.getElementById('sl'+i).className='sl '+(i<n?'done':'');
  });
}

function prep(){
  document.getElementById('hn').value=document.getElementById('fn').value.trim();
  document.getElementById('he').value=document.getElementById('fe').value.trim();
  document.getElementById('hp').value=document.getElementById('fp').value.trim();
  document.getElementById('hw').value=document.getElementById('fw').value.trim();
  document.getElementById('hpkg').value=selPkg;
  document.getElementById('hf').value=document.getElementById('ff').value;
  document.getElementById('hg').value=document.getElementById('fg').value.trim();
  <?php if($pixel): ?>if(typeof fbq!=='undefined')fbq('track','CompleteRegistration',{value:PAMT[selPkg],currency:'BDT'});<?php endif; ?>
  document.getElementById('regBtn').disabled=true;
  document.getElementById('regBtn').textContent='ভর্তি হচ্ছে...';
}
</script>
<div style="text-align:center;padding:10px;font-size:.68rem;color:rgba(255,255,255,.18);position:fixed;bottom:0;left:0;right:0;pointer-events:none;">
  Designed &amp; Developed by <a href="https://abdullahraiyan.com" target="_blank" rel="noopener" style="color:rgba(255,255,255,.25);text-decoration:none;pointer-events:all;">Abdullah Raiyan</a>
</div>
</body>
</html>
