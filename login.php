<?php
require_once 'includes/db.php';
require_once 'includes/theme.php';
$b     = get_branding();
$__logo  = !empty($b['site_logo']) ? 'assets/img/'.$b['site_logo'] : 'assets/img/logo.png';
$__sname = $b['site_name']    ?? 'Taqwim Institute';
$__tag   = $b['site_tagline'] ?? 'Knowledge · Character · Guidance';
$sess = current_user();
if ($sess) { $d=['admin'=>'admin/index.php','teacher'=>'teacher/index.php','student'=>'student/index.php']; header('Location:'.($d[$sess['role']]??'login.php')); exit; }
$error = $_SESSION['error']??''; unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>লগিন — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="assets/css/theme.css.php">
<link rel="stylesheet" href="assets/css/style.css">
<style>
body{background:var(--bg);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;position:relative;overflow:hidden;transition:background .3s ease;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse at 20% 80%, var(--p100) 0%, transparent 50%),radial-gradient(ellipse at 80% 20%, var(--gold-glow) 0%, transparent 50%);pointer-events:none;z-index:0;}
body::after{content:'﷽';position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);font-size:min(280px,60vw);color:var(--border);pointer-events:none;line-height:1;z-index:0;font-family:var(--font-ar);}
.wrap{width:100%;max-width:420px;position:relative;z-index:1;}
.logo-block{text-align:center;margin-bottom:28px;}
.logo-ring{width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,var(--g700),var(--g600));border:3px solid var(--gold);margin:0 auto 14px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.3),0 0 0 6px rgba(184,150,62,.1);}
.logo-ring img{width:52px;height:52px;object-fit:contain;}
.logo-block h1{color:var(--ink);font-size:1.4rem;font-weight:700;margin-bottom:4px;}
.logo-block p{color:var(--muted);font-size:.82rem;letter-spacing:.06em;}
.card{background:var(--surface);border-radius:20px;padding:28px 24px;box-shadow:var(--shadow-lg);border:1px solid var(--border);}
.role-tabs{display:flex;background:var(--g50);border-radius:var(--radius);padding:4px;gap:4px;margin-bottom:22px;}
.role-tab{flex:1;padding:9px 4px;text-align:center;border-radius:7px;cursor:pointer;font-size:.78rem;font-weight:600;color:var(--muted);border:none;background:none;font-family:var(--font);transition:all .15s;}
.role-tab.active{background:var(--surface);color:var(--p600);box-shadow:var(--shadow-xs);}
.input-wrap{position:relative;}
.input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:1rem;color:var(--muted);pointer-events:none;}
.input-wrap input{padding-left:38px;}
.btn-login{width:100%;padding:14px;background:var(--p600);color:#fff;border:none;border-radius:var(--r-sm);font-size:1rem;font-weight:700;font-family:var(--font-bn);cursor:pointer;transition:var(--trans);margin-top:4px;box-shadow:0 4px 14px rgba(22,163,74,.3);}
.btn-login:hover{background:var(--p500);transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.4);}
.err-box{background:rgba(255,71,87,.1);color:var(--danger);border:1px solid rgba(255,71,87,.2);border-radius:var(--r-sm);padding:10px 14px;font-size:.875rem;margin-bottom:14px;}
.links{text-align:center;margin-top:18px;display:flex;flex-direction:column;gap:8px;}
.links a{color:var(--muted);font-size:.82rem;text-decoration:none;}
.links .reg{color:var(--p600);font-weight:700;font-size:.9rem;}
</style>
<script>
(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
</script>
<script src="assets/js/theme.js" defer></script>
</head>
<body>
<div style="position:fixed;top:16px;right:16px;z-index:100;display:flex;align-items:center;gap:8px;">
  <button class="theme-toggle" onclick="toggleTheme()" style="background:var(--surface);border:1px solid var(--border);"></button>
  <span class="theme-icon" onclick="toggleTheme()" style="cursor:pointer;font-size:.9rem;">🌙</span>
</div>
<div class="wrap">
  <div class="logo-block">
    <div class="logo-ring"><img src="<?= htmlspecialchars($__logo) ?>" alt="<?= htmlspecialchars($__sname) ?>"></div>
    <h1><?= htmlspecialchars($__sname) ?></h1>
    <p><?= htmlspecialchars($__tag) ?></p>
  </div>
  <div class="card">
    <div class="role-tabs">
      <button class="role-tab active" onclick="setRole(this)">👨‍🎓 ছাত্র</button>
      <button class="role-tab" onclick="setRole(this)">👨‍🏫 শিক্ষক</button>
      <button class="role-tab" onclick="setRole(this)">⚙️ অ্যাডমিন</button>
    </div>
    <?php if($error): ?><div class="err-box">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form action="api/auth.php" method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>ইমেইল</label>
        <div class="input-wrap"><span class="input-icon">✉️</span><input type="email" name="email" required placeholder="আপনার ইমেইল" autocomplete="email"></div>
      </div>
      <div class="form-group">
        <label>পাসওয়ার্ড</label>
        <div class="input-wrap"><span class="input-icon">🔐</span><input type="password" name="password" required placeholder="পাসওয়ার্ড" autocomplete="current-password"></div>
      </div>
      <button type="submit" class="btn-login">প্রবেশ করুন →</button>
    </form>
  </div>
  <div class="links">
    <a href="register.php" class="reg">নতুন ছাত্র? ভর্তি হন →</a>
    <a href="index.php">← হোম পেজ</a>
  </div>
</div>
<script>function setRole(el){document.querySelectorAll('.role-tab').forEach(t=>t.classList.remove('active'));el.classList.add('active');}</script>
<div style="text-align:center;padding:10px;font-size:.68rem;color:rgba(255,255,255,.18);position:fixed;bottom:0;left:0;right:0;pointer-events:none;">
  Designed &amp; Developed by <a href="https://abdullahraiyan.com" target="_blank" rel="noopener" style="color:rgba(255,255,255,.25);text-decoration:none;pointer-events:all;">Abdullah Raiyan</a>
</div>
</body>
</html>
