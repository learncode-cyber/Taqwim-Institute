<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user  = require_role(['admin']);
$flash = $flash_err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_branding'])) {
    if (!empty($_FILES['site_logo']['name'])) {
        $allowed=['image/png','image/jpeg','image/svg+xml','image/webp'];
        $mime=mime_content_type($_FILES['site_logo']['tmp_name']);
        if (!in_array($mime,$allowed)) { $flash_err='শুধু PNG, JPG, SVG, WEBP আপলোড করুন।'; }
        else {
            $ext=pathinfo($_FILES['site_logo']['name'],PATHINFO_EXTENSION);
            $fn='logo_'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'],'../assets/img/'.$fn)) {
                $pdo->prepare("INSERT INTO settings (key_name,value) VALUES ('site_logo',?) ON DUPLICATE KEY UPDATE value=?")->execute([$fn,$fn]);
            }
        }
    }
    foreach (['site_name','site_tagline','theme_primary','theme_gold','theme_dark','theme_preset','site_footer_text','currency_symbol','currency_code'] as $k) {
        if (isset($_POST[$k])) {
            $v=trim($_POST[$k]);
            $pdo->prepare("INSERT INTO settings (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?")->execute([$k,$v,$v]);
        }
    }
    if (!$flash_err) $flash='ব্র্যান্ডিং আপডেট হয়েছে ✅';
}

$stmt=$pdo->query("SELECT key_name,value FROM settings");
$cfg=[]; foreach($stmt->fetchAll() as $r) $cfg[$r['key_name']]=$r['value'];
$b=get_branding();
$__logo=!empty($cfg['site_logo'])?'../assets/img/'.$cfg['site_logo']:'../assets/img/logo.png';
$__sname=$cfg['site_name']??'Taqwim Institute';

$presets=[
    'taqwim'  =>['name'=>'Taqwim (Default)',  'primary'=>'#1e5c32','gold'=>'#b8963e','dark'=>'#14381e'],
    'ocean'   =>['name'=>'Ocean Blue',         'primary'=>'#1a4f8a','gold'=>'#e8a020','dark'=>'#0d2d5a'],
    'ruby'    =>['name'=>'Ruby Red',            'primary'=>'#8b1a1a','gold'=>'#d4a017','dark'=>'#4a0e0e'],
    'purple'  =>['name'=>'Royal Purple',        'primary'=>'#5b2d8e','gold'=>'#f0c040','dark'=>'#2d1547'],
    'midnight'=>['name'=>'Midnight Dark',       'primary'=>'#1a1a2e','gold'=>'#e94560','dark'=>'#0f0f1a'],
    'olive'   =>['name'=>'Olive & Sand',        'primary'=>'#5a6e2c','gold'=>'#c8a94a','dark'=>'#2e3a10'],
    'teal'    =>['name'=>'Teal Modern',         'primary'=>'#1a6b6b','gold'=>'#f0a500','dark'=>'#0d3838'],
    'slate'   =>['name'=>'Corporate Slate',     'primary'=>'#2c3e50','gold'=>'#e67e22','dark'=>'#1a252f'],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>White Label — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.brand-page{max-width:800px;}
.preset-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:4px;}
.preset-card{border:2px solid var(--border);border-radius:var(--r);padding:11px 9px;cursor:pointer;text-align:center;transition:all .15s;position:relative;}
.preset-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm);}
.preset-card.active{border-color:var(--p600);box-shadow:0 0 0 3px var(--p100);}
.swatches{display:flex;gap:4px;justify-content:center;margin-bottom:7px;}
.swatch{width:22px;height:22px;border-radius:50%;border:2px solid rgba(255,255,255,.5);}
.preset-card p{font-size:.7rem;font-weight:600;color:var(--ink);}
.tick{display:none;position:absolute;top:5px;right:5px;background:var(--p600);color:#fff;border-radius:50%;width:17px;height:17px;font-size:.6rem;align-items:center;justify-content:center;}
.preset-card.active .tick{display:flex;}
.color-row{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.cprev{width:44px;height:44px;border-radius:var(--r-sm);border:2px solid var(--border);flex-shrink:0;cursor:pointer;}
.color-row label{font-size:.82rem;font-weight:600;color:var(--ink);display:block;margin-bottom:3px;}
.color-row input[type=color]{width:100%;height:36px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer;padding:2px;}
.prev-wrap{border:2px solid var(--border);border-radius:var(--r-lg);overflow:hidden;margin-top:14px;}
.prev-bar{height:46px;display:flex;align-items:center;padding:0 14px;gap:10px;}
.prev-logo{width:30px;height:30px;border-radius:7px;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.prev-logo img{width:100%;height:100%;object-fit:contain;}
.prev-name{color:#fff;font-size:.88rem;font-weight:700;}
.prev-body{background:#f5f8f5;padding:12px 14px;}
.prev-card{background:#fff;border-radius:8px;padding:11px;border-left:4px solid;margin-bottom:8px;}
.prev-btn{display:inline-block;padding:7px 16px;border-radius:6px;font-size:.8rem;font-weight:700;margin-right:7px;}
.logo-drop{border:2px dashed var(--border);border-radius:var(--r);padding:18px;text-align:center;cursor:pointer;transition:border-color .15s;}
.logo-drop:hover{border-color:var(--p600);}
.logo-drop.on{border-color:var(--p600);background:var(--p100);}
.cur-logo{width:110px;height:56px;object-fit:contain;margin:0 auto 8px;display:block;}
@media(max-width:600px){.preset-grid{grid-template-columns:repeat(2,1fr);}}
</style>
<script>
// Prevent flash of wrong theme
(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
</script>
<script src="../assets/js/theme.js" defer></script>
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark"><img src="<?= htmlspecialchars($__logo) ?>" alt="Logo" style="width:28px;height:28px;object-fit:contain;"></div>
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>অ্যাডমিন</small></div>
  </div>
  <div class="nav-section">
    <a class="nav-link" href="index.php"><span class="nav-icon">📊</span>ড্যাশবোর্ড</a>
    <a class="nav-link" href="coupons.php"><span class="nav-icon">🎟️</span>কুপন</a>
    <a class="nav-link active"><span class="nav-icon">🎨</span>White Label</a>
    <a class="nav-link" href="index.php?tab=settings"><span class="nav-icon">⚙️</span>সেটিংস</a>
    <a class="nav-link" href="change_password.php"><span class="nav-icon">🔐</span>পাসওয়ার্ড</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row"><div class="user-av"><?= mb_substr($user['name'],0,1) ?></div><div class="user-info"><strong><?= htmlspecialchars($user['name']) ?></strong><span>Admin</span></div></div>
    <a href="../api/auth.php?action=logout" class="btn-logout">লগআউট</a>
  </div>
</aside>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="menu-toggle" onclick="toggleSidebar()">☰</button><span class="page-title">🎨 White Label ব্র্যান্ডিং</span></div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span><a href="index.php" class="btn btn-ghost btn-sm always">← ড্যাশবোর্ড</a></div>
  </div>
  <div class="page-body">
    <div class="brand-page">
      <?php if($flash):?><div class="alert alert-success mb-12">✅ <?=htmlspecialchars($flash)?></div><?php endif;?>
      <?php if($flash_err):?><div class="alert alert-danger mb-12">❌ <?=htmlspecialchars($flash_err)?></div><?php endif;?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_branding" value="1">

        <!-- Identity -->
        <div class="card mb-16">
          <div class="card-head"><h2>🏢 প্রতিষ্ঠানের পরিচয়</h2></div>
          <div class="card-body">
            <div class="form-grid">
              <div>
                <div class="form-group"><label>প্রতিষ্ঠানের নাম</label><input type="text" name="site_name" id="siteName" value="<?=htmlspecialchars($cfg['site_name']??'')?>" oninput="updatePreview()"></div>
                <div class="form-group"><label>Tagline</label><input type="text" name="site_tagline" value="<?=htmlspecialchars($cfg['site_tagline']??'')?>"></div>
                <div class="form-grid">
                  <div class="form-group"><label>মুদ্রা চিহ্ন</label><input type="text" name="currency_symbol" value="<?=htmlspecialchars($cfg['currency_symbol']??'৳')?>" style="text-align:center;font-size:1.2rem;"></div>
                  <div class="form-group"><label>Currency Code</label><input type="text" name="currency_code" value="<?=htmlspecialchars($cfg['currency_code']??'BDT')?>" style="text-align:center;"></div>
                </div>
              </div>
              <div>
                <label>লোগো আপলোড (PNG/SVG)</label>
                <div class="logo-drop" id="dropZone" onclick="document.getElementById('logoFile').click()">
                  <img src="<?=htmlspecialchars($__logo)?>" class="cur-logo" id="logoPreview">
                  <p style="font-size:.78rem;color:var(--muted);">ক্লিক বা drag করুন<br>PNG, SVG, JPG · Max 2MB</p>
                </div>
                <input type="file" name="site_logo" id="logoFile" accept="image/*" style="display:none;" onchange="prevLogo(this)">
              </div>
            </div>
          </div>
        </div>

        <!-- Presets -->
        <div class="card mb-16">
          <div class="card-head"><h2>🎨 থিম প্রিসেট</h2></div>
          <div class="card-body">
            <div class="preset-grid" id="presetGrid">
              <?php foreach($presets as $key=>$p): $active=($cfg['theme_preset']??'taqwim')===$key?'active':''; ?>
              <div class="preset-card <?=$active?>" data-key="<?=$key?>" onclick="applyPreset('<?=$key?>','<?=$p['primary']?>','<?=$p['gold']?>','<?=$p['dark']?>')">
                <div class="swatches">
                  <div class="swatch" style="background:<?=$p['dark']?>"></div>
                  <div class="swatch" style="background:<?=$p['primary']?>"></div>
                  <div class="swatch" style="background:<?=$p['gold']?>"></div>
                </div>
                <p><?=$p['name']?></p>
                <div class="tick">✓</div>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="theme_preset" id="themePreset" value="<?=htmlspecialchars($cfg['theme_preset']??'taqwim')?>">
          </div>
        </div>

        <!-- Custom Colors -->
        <div class="card mb-16">
          <div class="card-head"><h2>🖌️ কাস্টম রঙ</h2></div>
          <div class="card-body">
            <div class="color-row">
              <div class="cprev" id="pp" style="background:<?=htmlspecialchars($cfg['theme_primary']??'#1e5c32')?>"></div>
              <div style="flex:1;"><label>Primary Color</label><input type="color" name="theme_primary" id="cp" value="<?=htmlspecialchars($cfg['theme_primary']??'#1e5c32')?>" oninput="updateColor('p',this.value)"></div>
            </div>
            <div class="color-row">
              <div class="cprev" id="pg" style="background:<?=htmlspecialchars($cfg['theme_gold']??'#b8963e')?>"></div>
              <div style="flex:1;"><label>Accent / Gold Color</label><input type="color" name="theme_gold" id="cg" value="<?=htmlspecialchars($cfg['theme_gold']??'#b8963e')?>" oninput="updateColor('g',this.value)"></div>
            </div>
            <div class="color-row">
              <div class="cprev" id="pd" style="background:<?=htmlspecialchars($cfg['theme_dark']??'#14381e')?>"></div>
              <div style="flex:1;"><label>Dark / Sidebar Color</label><input type="color" name="theme_dark" id="cd" value="<?=htmlspecialchars($cfg['theme_dark']??'#14381e')?>" oninput="updateColor('d',this.value)"></div>
            </div>

            <!-- Live Preview -->
            <label style="font-size:.82rem;font-weight:600;color:var(--ink);display:block;margin-top:16px;margin-bottom:8px;">👁️ Live Preview</label>
            <div class="prev-wrap">
              <div class="prev-bar" id="pvBar" style="background:<?=$cfg['theme_dark']??'#14381e'?>">
                <div class="prev-logo" id="pvLogoBox" style="background:<?=$cfg['theme_gold']??'#b8963e'?>">
                  <img src="<?=htmlspecialchars($__logo)?>" id="pvLogoImg">
                </div>
                <span class="prev-name" id="pvName"><?=htmlspecialchars($cfg['site_name']??'Taqwim Institute')?></span>
              </div>
              <div class="prev-body">
                <div style="font-size:.875rem;font-weight:700;color:#1c2b1e;margin-bottom:8px;">📅 আজকের ক্লাস</div>
                <div class="prev-card" id="pvCard" style="border-left-color:<?=$cfg['theme_gold']??'#b8963e'?>">
                  <div style="font-size:.8rem;font-weight:600;">তাজওয়িদ ক্লাস</div>
                  <div style="font-size:.72rem;color:#6b7a6d;">👨‍🏫 উস্তাদ রাইয়ান · 📹 Google Meet</div>
                </div>
                <span class="prev-btn" id="pvBtnP" style="background:<?=$cfg['theme_primary']??'#1e5c32'?>;color:white;">✅ যোগ দিন</span>
                <span class="prev-btn" id="pvBtnG" style="background:<?=$cfg['theme_gold']??'#b8963e'?>;color:<?=$cfg['theme_dark']??'#14381e'?>">📱 WhatsApp</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="card mb-16">
          <div class="card-head"><h2>📄 Footer Text</h2></div>
          <div class="card-body">
            <div class="form-group"><label>Footer এ যা দেখাবে</label><input type="text" name="site_footer_text" value="<?=htmlspecialchars($cfg['site_footer_text']??'')?>" placeholder="© 2025 Taqwim Institute"></div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">💾 সব সেটিংস সেভ করুন</button>
        <a href="index.php" class="btn btn-ghost btn-lg" style="margin-left:8px;">বাতিল</a>
      </form>
    </div>
  </div>
</div>
</div>
<div class="bottom-nav"><div class="bottom-nav-inner">
  <a class="bottom-nav-item" href="index.php"><span class="b-icon">📊</span>ড্যাশবোর্ড</a>
  <a class="bottom-nav-item active"><span class="b-icon">🎨</span>ব্র্যান্ডিং</a>
  <a class="bottom-nav-item" href="index.php?tab=settings"><span class="b-icon">⚙️</span>সেটিংস</a>
</div></div>
<div id="toast-container"></div>
<script>
const presets=<?=json_encode($presets,JSON_UNESCAPED_UNICODE)?>;
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('active');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('active');}

function h2r(hex){hex=hex.replace('#','');if(hex.length===3)hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];return[parseInt(hex.substr(0,2),16),parseInt(hex.substr(2,2),16),parseInt(hex.substr(4,2),16)];}
function li(hex,a){let[r,g,b]=h2r(hex);return '#'+[r,g,b].map(x=>Math.min(255,x+a).toString(16).padStart(2,'0')).join('');}
function da(hex,a){let[r,g,b]=h2r(hex);return '#'+[r,g,b].map(x=>Math.max(0,x-a).toString(16).padStart(2,'0')).join('');}

function updateColor(t,val){
  const r=document.documentElement;
  const p=document.getElementById('cp').value;
  const g=document.getElementById('cg').value;
  const d=document.getElementById('cd').value;
  if(t==='p'){document.getElementById('pp').style.background=val;document.getElementById('pvBtnP').style.background=val;document.getElementById('pvCard').style.borderLeftColor=val;}
  if(t==='g'){document.getElementById('pg').style.background=val;document.getElementById('pvBtnG').style.background=val;document.getElementById('pvLogoBox').style.background=val;document.getElementById('pvBtnG').style.color=d;}
  if(t==='d'){document.getElementById('pd').style.background=val;document.getElementById('pvBar').style.background=val;}
  r.style.setProperty('--g800',d);r.style.setProperty('--g600',p);r.style.setProperty('--gold',g);
  r.style.setProperty('--g700',li(d,15));r.style.setProperty('--g500',li(p,15));r.style.setProperty('--gold-l',li(g,20));
  document.getElementById('themePreset').value='custom';
  document.querySelectorAll('.preset-card').forEach(c=>c.classList.remove('active'));
}

function applyPreset(key,p,g,d){
  document.getElementById('cp').value=p;document.getElementById('cg').value=g;document.getElementById('cd').value=d;
  document.getElementById('themePreset').value=key;
  document.querySelectorAll('.preset-card').forEach(c=>c.classList.remove('active'));
  document.querySelector('[data-key="'+key+'"]')?.classList.add('active');
  updateColor('p',p);updateColor('g',g);updateColor('d',d);
  toast('✅ '+presets[key]?.name+' প্রয়োগ হয়েছে','success');
}

function updatePreview(){document.getElementById('pvName').textContent=document.getElementById('siteName').value||'Taqwim Institute';}

function prevLogo(input){
  if(!input.files[0])return;
  const reader=new FileReader();
  reader.onload=e=>{document.getElementById('logoPreview').src=e.target.result;document.getElementById('pvLogoImg').src=e.target.result;};
  reader.readAsDataURL(input.files[0]);
}

const dz=document.getElementById('dropZone');
dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('on');});
dz.addEventListener('dragleave',()=>dz.classList.remove('on'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('on');const f=e.dataTransfer.files[0];if(f){document.getElementById('logoFile').files=e.dataTransfer.files;prevLogo({files:[f]});}});

function toast(msg,type='success'){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
