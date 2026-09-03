<?php
require_once 'includes/db.php';
require_once 'includes/theme.php';
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? 'assets/img/'.$b['site_logo'] : 'assets/img/logo.png';
$__sname = $b['site_name']    ?? 'Taqwim Institute';
$__tag   = $b['site_tagline'] ?? 'Knowledge · Character · Guidance';
$pixel   = get_setting('meta_pixel_id');
$fb      = get_setting('facebook_page');
$yt      = get_setting('youtube_channel');
$wa      = get_setting('whatsapp_number');
$sess = current_user();
if ($sess) { $d=['admin'=>'admin/index.php','teacher'=>'teacher/index.php','student'=>'student/index.php']; header('Location:'.($d[$sess['role']]??'login.php')); exit; }
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="description" content="<?= htmlspecialchars($__sname) ?> — অনলাইনে শুদ্ধ কুরআন শিখুন">
<title><?= htmlspecialchars($__sname) ?> — শুদ্ধ কুরআন শিখুন</title>
<link rel="stylesheet" href="assets/css/theme.css.php">
<link rel="stylesheet" href="assets/css/style.css">
<?php if($pixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?=htmlspecialchars($pixel)?>');fbq('track','PageView');</script>
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* ── Landing Page Premium Styles ── */
:root { --radius: 14px; --radius-sm: 10px; --radius-lg: 20px; --font: 'Hind Siliguri', system-ui, sans-serif; }
body { background: var(--bg); color: var(--body); transition: background .3s ease, color .3s ease; overflow-x: hidden; }

/* NAVBAR */
.nav {
  position: sticky; top: 0; z-index: 400;
  background: rgba(10,15,30,.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,.06);
  padding: 0 24px;
  display: flex; align-items: center; justify-content: space-between;
  height: 64px;
  transition: all .3s ease;
}
[data-theme="light"] .nav {
  background: rgba(255,255,255,.85);
  border-bottom-color: rgba(0,0,0,.06);
}
.nav-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.nav-logo-img {
  width: 38px; height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--gold-d), var(--gold));
  padding: 4px; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px var(--gold-glow);
}
.nav-logo-img img { width: 100%; height: 100%; object-fit: contain; }
.nav-logo strong { color: #fff; font-size: .9rem; font-weight: 700; display: block; line-height: 1.2; }
.nav-logo small  { color: rgba(255,255,255,.35); font-size: .62rem; letter-spacing: .07em; }
[data-theme="light"] .nav-logo strong { color: var(--ink); }
[data-theme="light"] .nav-logo small  { color: var(--muted); }
.nav-links { display: flex; align-items: center; gap: 6px; }
.nav-links a {
  color: rgba(255,255,255,.65); font-size: .85rem;
  text-decoration: none; padding: 7px 14px;
  border-radius: var(--r-full);
  transition: all .2s;
}
[data-theme="light"] .nav-links a { color: var(--body); }
.nav-links a:hover { color: #fff; background: rgba(255,255,255,.08); }
[data-theme="light"] .nav-links a:hover { color: var(--ink); background: var(--surface-3); }
.nav-cta {
  background: linear-gradient(135deg, var(--gold-d), var(--gold)) !important;
  color: #fff !important; font-weight: 700 !important;
  padding: 9px 20px !important;
  border-radius: var(--r-full) !important;
  box-shadow: 0 4px 14px var(--gold-glow) !important;
}
.nav-cta:hover { transform: translateY(-1px); box-shadow: 0 6px 20px var(--gold-glow) !important; }
.nav-theme { display: flex; align-items: center; gap: 8px; }
.theme-icon { cursor: pointer; font-size: .9rem; }

/* HERO */
.hero {
  min-height: 92vh;
  display: flex; align-items: center; justify-content: center;
  text-align: center; padding: 80px 20px 60px;
  background:
    radial-gradient(ellipse at 15% 70%, rgba(201,162,39,.12) 0%, transparent 50%),
    radial-gradient(ellipse at 85% 25%, rgba(0,212,170,.08) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 50%, rgba(22,163,74,.06) 0%, transparent 70%),
    #0a0f1e;
  position: relative; overflow: hidden;
}
[data-theme="light"] .hero {
  background:
    radial-gradient(ellipse at 15% 70%, rgba(201,162,39,.08) 0%, transparent 50%),
    radial-gradient(ellipse at 85% 25%, rgba(0,212,170,.06) 0%, transparent 50%),
    #f4f6f9;
}
.hero-bg-ar {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  font-size: min(340px, 65vw);
  color: rgba(255,255,255,.02);
  pointer-events: none; line-height: 1;
  font-family: 'Amiri', serif;
  user-select: none;
  animation: floatAr 8s ease-in-out infinite;
}
@keyframes floatAr {
  0%,100% { transform: translate(-50%,-50%) rotate(-2deg); }
  50%      { transform: translate(-50%,-52%) rotate(2deg); }
}
[data-theme="light"] .hero-bg-ar { color: rgba(0,0,0,.03); }

.hero-inner { position: relative; z-index: 1; max-width: 780px; }

.h-badge {
  display: inline-flex; align-items: center; gap: 7px;
  background: rgba(201,162,39,.12);
  border: 1px solid rgba(201,162,39,.25);
  color: var(--gold-l); padding: 6px 18px;
  border-radius: var(--r-full);
  font-size: .8rem; font-weight: 600;
  margin-bottom: 24px;
  letter-spacing: .04em;
  backdrop-filter: blur(8px);
  animation: badgePop .6s cubic-bezier(.34,1.56,.64,1) forwards;
}
@keyframes badgePop {
  from { opacity: 0; transform: scale(.8) translateY(10px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

.bismillah {
  font-size: clamp(1.3rem, 4vw, 2rem);
  color: var(--gold);
  margin-bottom: 20px; display: block;
  font-family: 'Amiri', serif; line-height: 1.6;
  text-shadow: 0 0 30px var(--gold-glow);
  animation: fadeUp .8s ease .1s both;
}

.hero h1 {
  font-size: clamp(1.9rem, 5.5vw, 3.2rem);
  color: #fff; font-weight: 800;
  line-height: 1.2; margin-bottom: 18px;
  animation: fadeUp .8s ease .2s both;
}
[data-theme="light"] .hero h1 { color: var(--ink); }
.hero h1 .hl {
  background: linear-gradient(135deg, var(--gold), var(--teal));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-sub {
  color: rgba(255,255,255,.6);
  font-size: clamp(.95rem, 2.5vw, 1.1rem);
  margin-bottom: 36px;
  max-width: 560px; margin-left: auto; margin-right: auto;
  line-height: 1.8;
  animation: fadeUp .8s ease .3s both;
}
[data-theme="light"] .hero-sub { color: var(--body); }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.hero-badges {
  display: flex; flex-wrap: wrap; justify-content: center;
  gap: 10px; margin-bottom: 36px;
  animation: fadeUp .8s ease .4s both;
}
.hb {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  color: rgba(255,255,255,.8);
  padding: 7px 16px; border-radius: var(--r-full);
  font-size: .8rem;
  display: flex; align-items: center; gap: 6px;
  backdrop-filter: blur(8px);
  transition: all .2s;
}
.hb:hover { background: rgba(255,255,255,.1); transform: translateY(-2px); }
[data-theme="light"] .hb { background: white; color: var(--body); border-color: var(--border); box-shadow: var(--shadow-xs); }

.hero-btns {
  display: flex; flex-wrap: wrap; gap: 14px; justify-content: center;
  animation: fadeUp .8s ease .5s both;
}

/* CTA buttons */
.btn-main {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, var(--gold-d), var(--gold));
  color: #fff; padding: 15px 32px;
  border-radius: var(--r-full);
  font-size: 1rem; font-weight: 700;
  border: none; cursor: pointer; font-family: var(--font);
  transition: all .25s cubic-bezier(.4,0,.2,1);
  text-decoration: none;
  box-shadow: 0 6px 24px var(--gold-glow);
}
.btn-main:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 32px var(--gold-glow);
}
.btn-sec {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,.07);
  color: rgba(255,255,255,.9); padding: 15px 28px;
  border-radius: var(--r-full);
  font-size: 1rem; font-weight: 600;
  border: 1.5px solid rgba(255,255,255,.15);
  font-family: var(--font); transition: all .2s;
  text-decoration: none; backdrop-filter: blur(8px);
}
.btn-sec:hover { background: rgba(255,255,255,.12); transform: translateY(-2px); }
[data-theme="light"] .btn-sec { background: white; color: var(--body); border-color: var(--border); box-shadow: var(--shadow-sm); }

/* Stats bar */
.stats-bar {
  display: flex; flex-wrap: wrap; justify-content: center;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: var(--r-xl);
  margin-top: 52px; overflow: hidden;
  backdrop-filter: blur(12px);
  animation: fadeUp .8s ease .6s both;
}
[data-theme="light"] .stats-bar { background: white; border-color: var(--border); box-shadow: var(--shadow-sm); }
.si { flex: 1; min-width: 110px; padding: 22px 16px; text-align: center; border-right: 1px solid rgba(255,255,255,.07); }
.si:last-child { border-right: none; }
[data-theme="light"] .si { border-right-color: var(--border); }
.si-num {
  font-size: 1.7rem; font-weight: 800;
  background: linear-gradient(135deg, var(--gold), var(--teal));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  font-family: 'Inter', sans-serif;
}
.si-lbl { font-size: .73rem; color: rgba(255,255,255,.4); margin-top: 4px; }
[data-theme="light"] .si-lbl { color: var(--muted); }

/* SECTIONS */
.sec { padding: 80px 20px; }
.sec-w { background: var(--surface); }
.sec-l { background: var(--bg); }
.sec-d { background: #0a0f1e; }
[data-theme="light"] .sec-d { background: var(--surface-2); }
.sec-inner { max-width: 980px; margin: 0 auto; }
.sec-head { text-align: center; margin-bottom: 52px; }
.sec-lbl {
  display: inline-block;
  background: var(--p100);
  color: var(--p600);
  border: 1px solid rgba(22,163,74,.15);
  padding: 4px 14px; border-radius: var(--r-full);
  font-size: .73rem; font-weight: 700;
  letter-spacing: .08em; margin-bottom: 12px;
}
.sec-lbl.gl { background: var(--gold-glow); color: var(--gold); border-color: rgba(201,162,39,.2); }
.sec-title { font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight: 800; color: var(--ink); margin-bottom: 8px; }
.sec-title.w { color: #fff; }
.sec-sub { color: var(--muted); font-size: .92rem; max-width: 500px; margin: 0 auto; }
.sec-sub.w { color: rgba(255,255,255,.5); }

/* PROBLEM */
.prob-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px,1fr)); gap: 14px; }
.prob-card {
  background: rgba(255,71,87,.06);
  border: 1px solid rgba(255,71,87,.12);
  border-radius: var(--r-lg); padding: 20px;
  display: flex; gap: 14px;
  transition: all .2s;
}
.prob-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); }
.prob-card .ic { font-size: 1.6rem; flex-shrink: 0; }
.prob-card p { font-size: .875rem; color: var(--body); font-weight: 500; line-height: 1.6; }

/* SOLUTION */
.sol-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 16px; }
.sol-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  padding: 28px 18px; text-align: center;
  transition: all .25s cubic-bezier(.4,0,.2,1);
  box-shadow: var(--shadow-xs);
  position: relative; overflow: hidden;
}
.sol-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--p600), var(--teal));
  transform: scaleX(0); transition: transform .3s ease;
}
.sol-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--p600); }
.sol-card:hover::before { transform: scaleX(1); }
.sol-card .ic { font-size: 2.2rem; margin-bottom: 12px; display: block; }
.sol-card h3 { font-size: .9rem; font-weight: 700; color: var(--ink); margin-bottom: 5px; }
.sol-card p  { font-size: .75rem; color: var(--muted); }

/* TEACHERS */
.tch-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 20px; }
.tch-card {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: var(--r-xl); padding: 28px 22px; text-align: center;
  transition: all .25s;
  backdrop-filter: blur(8px);
}
[data-theme="light"] .tch-card { background: var(--surface); border-color: var(--border); }
.tch-card:hover { transform: translateY(-4px); background: rgba(255,255,255,.07); box-shadow: 0 12px 40px rgba(0,0,0,.2); }
.tch-av {
  width: 88px; height: 88px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--p700), var(--p600));
  border: 3px solid var(--gold);
  margin: 0 auto 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.2rem; overflow: hidden;
  box-shadow: 0 8px 28px rgba(0,0,0,.2), 0 0 0 6px var(--gold-glow);
}
.tch-av img { width: 100%; height: 100%; object-fit: contain; }
.tch-card h3 { color: #fff; font-size: 1.05rem; font-weight: 700; margin-bottom: 5px; }
[data-theme="light"] .tch-card h3 { color: var(--ink); }
.tch-role  { color: var(--gold-l); font-size: .8rem; font-weight: 600; margin-bottom: 12px; }
.tch-card p { color: rgba(255,255,255,.55); font-size: .82rem; line-height: 1.7; }
[data-theme="light"] .tch-card p { color: var(--muted); }
.tch-tags { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 14px; }
.tch-tag {
  background: rgba(201,162,39,.1); color: var(--gold-l);
  border: 1px solid rgba(201,162,39,.2);
  padding: 3px 11px; border-radius: var(--r-full);
  font-size: .7rem; font-weight: 600;
}

/* PRICING */
.price-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; align-items: center; }
@media(max-width: 640px) { .price-grid { grid-template-columns: 1fr; } }
.pc {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--r-xl); padding: 28px 22px;
  position: relative; transition: all .25s;
}
.pc:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
.pc.pop {
  border-color: var(--p600);
  transform: scale(1.04);
  box-shadow: 0 12px 40px rgba(22,163,74,.2);
}
.pc.pop:hover { transform: scale(1.04) translateY(-3px); }
.pc.best {
  border-color: var(--gold);
  background: linear-gradient(160deg, var(--surface), rgba(201,162,39,.04));
}
.pc-top {
  position: absolute; top: -14px; left: 50%;
  transform: translateX(-50%);
  padding: 4px 16px; border-radius: var(--r-full);
  font-size: .72rem; font-weight: 700; white-space: nowrap;
}
.pt-pop  { background: var(--p600); color: #fff; }
.pt-best { background: linear-gradient(135deg, var(--gold-d), var(--gold)); color: #fff; }
.pc-name { font-size: .85rem; color: var(--muted); font-weight: 600; margin-bottom: 6px; }
.pc-amt  { font-size: 2.2rem; font-weight: 800; color: var(--ink); line-height: 1; margin-bottom: 4px; font-family: 'Inter', sans-serif; }
.pc-amt span { font-size: .82rem; font-weight: 400; color: var(--muted); }
.pc-desc { font-size: .76rem; color: var(--muted); margin-bottom: 18px; }
.pc-feats { list-style: none; margin-bottom: 22px; }
.pc-feats li { display: flex; align-items: flex-start; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--border); font-size: .85rem; }
.pc-feats li:last-child { border-bottom: none; }
.pfy { color: var(--p600); font-weight: 700; flex-shrink: 0; }
.pfn { color: var(--muted); flex-shrink: 0; }

/* TESTIMONIALS */
.testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 16px; }
.testi {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-xl); padding: 24px;
  transition: all .2s;
}
.testi:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
.stars { color: var(--gold); font-size: 1rem; letter-spacing: 2px; margin-bottom: 12px; }
.testi p { font-size: .875rem; color: var(--body); font-style: italic; margin-bottom: 16px; line-height: 1.8; }
.testi-auth { display: flex; align-items: center; gap: 10px; }
.tav { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--p700), var(--p600)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; flex-shrink: 0; }
.testi-auth strong { display: block; font-size: .85rem; color: var(--ink); }
.testi-auth span   { font-size: .72rem; color: var(--muted); }

/* COURSE CATALOG */
.cat-tab {
  padding: 8px 20px;
  border-radius: var(--r-full);
  border: 1.5px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.05);
  color: rgba(255,255,255,.65);
  font-size: .8rem; font-weight: 600;
  cursor: pointer; font-family: var(--font);
  transition: all .2s;
  backdrop-filter: blur(6px);
}
[data-theme="light"] .cat-tab { background: white; color: var(--body); border-color: var(--border); box-shadow: var(--shadow-xs); }
.cat-tab.active, .cat-tab:hover {
  background: linear-gradient(135deg, var(--gold-d), var(--gold));
  color: #fff; border-color: var(--gold);
  box-shadow: 0 4px 14px var(--gold-glow);
  transform: translateY(-1px);
}
.course-item { transition: all .25s cubic-bezier(.4,0,.2,1) !important; }
.course-item:hover { transform: translateY(-5px) !important; box-shadow: var(--shadow-md) !important; }

/* FAQ */
.faq-list { max-width: 660px; margin: 0 auto; }
.faq-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  margin-bottom: 10px; overflow: hidden;
}
.faq-q {
  width: 100%; text-align: left;
  padding: 17px 22px;
  background: none; border: none; cursor: pointer;
  font-family: var(--font); font-size: .93rem;
  font-weight: 600; color: var(--ink);
  display: flex; justify-content: space-between;
  align-items: center; gap: 14px;
}
.faq-arr { color: var(--p600); transition: transform .25s cubic-bezier(.4,0,.2,1); font-size: .85rem; }
.faq-item.open .faq-arr { transform: rotate(180deg); }
.faq-a { max-height: 0; overflow: hidden; transition: max-height .35s cubic-bezier(.4,0,.2,1); }
.faq-item.open .faq-a { max-height: 200px; }
.faq-a p { padding: 0 22px 18px; font-size: .875rem; color: var(--muted); line-height: 1.8; }

/* FINAL CTA */
.final-cta {
  background: linear-gradient(135deg, var(--p700), #0d1f12);
  text-align: center; padding: 88px 20px;
  position: relative; overflow: hidden;
}
.final-cta::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(circle at 30% 50%, var(--gold-glow) 0%, transparent 50%),
    radial-gradient(circle at 70% 50%, rgba(0,212,170,.08) 0%, transparent 50%);
}
.final-cta h2  { font-size: clamp(1.5rem, 3.5vw, 2.2rem); color: #fff; margin-bottom: 12px; position: relative; font-weight: 800; }
.final-cta p   { color: rgba(255,255,255,.6); margin-bottom: 32px; position: relative; }

/* FOOTER */
.footer {
  background: #060a14;
  padding: 32px 20px;
  text-align: center;
  color: rgba(255,255,255,.25);
  font-size: .8rem;
}
[data-theme="light"] .footer { background: var(--bg-2); color: var(--muted); }
.footer-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-bottom: 12px; }
.footer-links a {
  color: rgba(255,255,255,.3); text-decoration: none;
  font-size: .8rem; transition: color .2s;
}
[data-theme="light"] .footer-links a { color: var(--muted); }
.footer-links a:hover { color: var(--gold-l); }

/* Scroll reveal */
.reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }

@media(max-width: 500px) {
  .nav-links a:not(.nav-cta) { display: none; }
  .hero h1 { font-size: 1.8rem; }
}
</style>
<script>
(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
</script>
<script src="assets/js/theme.js" defer></script>
</head>
<body>

<!-- NAV -->
<nav class="nav">
  <a class="nav-logo" href="#">
    <div class="nav-logo-img"><img src="<?= htmlspecialchars($__logo) ?>" alt="<?= htmlspecialchars($__sname) ?>"></div>
    <div><strong><?= htmlspecialchars($__sname) ?></strong><small><?= htmlspecialchars($__tag) ?></small></div>
  </a>
  <div class="nav-links">
    <a href="#courses">কোর্স</a>
    <a href="#pricing">মূল্য</a>
    <a href="login.php">লগিন</a>
    <a href="register.php" class="nav-cta">ভর্তি হন →</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg">﷽</div>
  <div class="hero-inner">
    <div class="h-badge">🌟 আন্তর্জাতিক মানের কুরআন শিক্ষা</div>
    <span class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>
    <h1>শুদ্ধ কুরআন শিখুন<br><span class="hl">আন্তর্জাতিক শিক্ষকের</span><br>কাছে — ঘরে বসে</h1>
    <p class="hero-sub">তাজওয়িদ · হিফজ · শুদ্ধ তেলাওয়াত · আরবি ভাষা<br>শিশু থেকে বয়স্ক — সবার জন্য, যেকোনো সময়</p>
    <div class="hero-badges">
      <span class="hb">✅ ৫০০+ ছাত্র</span>
      <span class="hb">🎓 আন্তর্জাতিক শিক্ষক</span>
      <span class="hb">📜 সার্টিফিকেট</span>
      <span class="hb">📱 যেকোনো ডিভাইস</span>
    </div>
    <div class="hero-btns">
      <a href="register.php" class="btn-main">📚 এখনই ভর্তি হন →</a>
      <a href="#pricing" class="btn-sec">💰 মূল্য দেখুন</a>
    </div>
    <div class="stats-bar">
      <div class="si"><div class="si-num">৫০০+</div><div class="si-lbl">সক্রিয় ছাত্র</div></div>
      <div class="si"><div class="si-num">৩</div><div class="si-lbl">কোর্স ক্যাটাগরি</div></div>
      <div class="si"><div class="si-num">৯৮%</div><div class="si-lbl">সন্তুষ্টি রেটিং</div></div>
      <div class="si"><div class="si-num">২+</div><div class="si-lbl">দেশে সার্ভিস</div></div>
    </div>
  </div>
</section>

<!-- PROBLEM -->
<section class="sec sec-w">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl">আপনি কি এই সমস্যায়?</span><h2 class="sec-title">অনেকেই এই কষ্টে আছেন</h2></div>
    <div class="prob-grid">
      <div class="prob-card"><span class="ic">😟</span><p>কুরআন পড়তে পারেন কিন্তু উচ্চারণ শুদ্ধ নয়</p></div>
      <div class="prob-card"><span class="ic">👶</span><p>সন্তানকে শেখাতে চান, ভালো শিক্ষক পাচ্ছেন না</p></div>
      <div class="prob-card"><span class="ic">🕐</span><p>মাদ্রাসায় সময়মতো যাওয়া সম্ভব হয় না</p></div>
      <div class="prob-card"><span class="ic">🌍</span><p>দেশের বাইরে আছেন, কাছে কোনো হুজুর নেই</p></div>
    </div>
  </div>
</section>

<!-- SOLUTION -->
<section class="sec sec-l" id="courses">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl">আমাদের সমাধান</span><h2 class="sec-title">আমরা যা শেখাই</h2></div>
    <div class="sol-grid">
      <div class="sol-card"><span class="ic">📖</span><h3>কুরআন পাঠ</h3><p>শূন্য থেকে সহিহ পাঠ</p></div>
      <div class="sol-card"><span class="ic">🎵</span><h3>তাজওয়িদ</h3><p>সঠিক মাখরাজ ও নিয়ম</p></div>
      <div class="sol-card"><span class="ic">🧠</span><h3>হিফজ</h3><p>পদ্ধতিগতভাবে মুখস্থ</p></div>
      <div class="sol-card"><span class="ic">📝</span><h3>আরবি ভাষা</h3><p>মৌলিক আরবি ব্যাকরণ</p></div>
      <div class="sol-card"><span class="ic">📜</span><h3>সার্টিফিকেট</h3><p>আন্তর্জাতিক সার্টিফিকেট</p></div>
      <div class="sol-card"><span class="ic">⏰</span><h3>নিজের সময়ে</h3><p>সকাল বা রাত — যখন সুবিধা</p></div>
    </div>
  </div>
</section>

<!-- TEACHERS -->
<section class="sec sec-d">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl gl">শিক্ষকমণ্ডলী</span><h2 class="sec-title w">অভিজ্ঞ ও যোগ্য শিক্ষকগণ</h2></div>
    <div class="tch-grid">
      <div class="tch-card"><div class="tch-av"><img src="<?= htmlspecialchars($__logo) ?>" alt="Teacher"></div><h3>উস্তাদ (নাম যোগ করুন)</h3><p class="tch-role">প্রধান শিক্ষক</p><p>অভিজ্ঞ হাফেজ ও তাজওয়িদ বিশেষজ্ঞ।</p><div class="tch-tags"><span class="tch-tag">হাফেজ</span><span class="tch-tag">তাজওয়িদ</span></div></div>
      <div class="tch-card"><div class="tch-av"><img src="<?= htmlspecialchars($__logo) ?>" alt="Teacher"></div><h3>উস্তাদ (নাম যোগ করুন)</h3><p class="tch-role">আন্তর্জাতিক শিক্ষক</p><p>মিশরে উচ্চতর ইসলামি শিক্ষা গ্রহণকারী।</p><div class="tch-tags"><span class="tch-tag">আন্তর্জাতিক</span><span class="tch-tag">আরবি</span></div></div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="sec sec-l" id="pricing">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl">প্যাকেজ সমূহ</span><h2 class="sec-title">আপনার জন্য সঠিক প্যাকেজ</h2><p class="sec-sub">আজই ভর্তি হন এবং শেখা শুরু করুন</p></div>
    <div class="price-grid">
      <div class="pc">
        <p class="pc-name">🥉 বেসিক</p><div class="pc-amt">৳২,০০০<span>/মাস</span></div><p class="pc-desc">৮টি গ্রুপ ক্লাস · ৩০ মিনিট</p>
        <ul class="pc-feats"><li><span class="pfy">✓</span>৮টি ক্লাস/মাস</li><li><span class="pfy">✓</span>গ্রুপ ক্লাস</li><li><span class="pfy">✓</span>মাসিক রিপোর্ট</li><li><span class="pfn">✗</span>WhatsApp সাপোর্ট</li><li><span class="pfn">✗</span>সার্টিফিকেট</li></ul>
        <a href="register.php" class="btn btn-outline btn-full" style="justify-content:center;">ভর্তি হন</a>
      </div>
      <div class="pc pop">
        <span class="pc-top pt-pop">⭐ সবচেয়ে জনপ্রিয়</span>
        <p class="pc-name">🥈 স্ট্যান্ডার্ড</p><div class="pc-amt">৳৩,২০০<span>/মাস</span></div><p class="pc-desc">১০টি ছোট গ্রুপ ক্লাস · ৩০ মিনিট</p>
        <ul class="pc-feats"><li><span class="pfy">✓</span>১০টি ক্লাস/মাস</li><li><span class="pfy">✓</span>ছোট গ্রুপ (৩ জন)</li><li><span class="pfy">✓</span>সাপ্তাহিক রিপোর্ট</li><li><span class="pfy">✓</span>WhatsApp সাপোর্ট</li><li><span class="pfn">✗</span>সার্টিফিকেট</li></ul>
        <a href="register.php" class="btn btn-primary btn-full" style="justify-content:center;">ভর্তি হন</a>
      </div>
      <div class="pc best">
        <span class="pc-top pt-best">🏆 সেরা মান</span>
        <p class="pc-name">🥇 প্রিমিয়াম</p><div class="pc-amt">৳৩,৮০০<span>/মাস</span></div><p class="pc-desc">১৬টি ১-on-১ ক্লাস · ৪৫ মিনিট</p>
        <ul class="pc-feats"><li><span class="pfy">✓</span>১৬টি ক্লাস/মাস</li><li><span class="pfy">✓</span>১-on-১ একক ক্লাস</li><li><span class="pfy">✓</span>আন্তর্জাতিক শিক্ষক</li><li><span class="pfy">✓</span>WhatsApp সাপোর্ট</li><li><span class="pfy">✓</span><strong>সার্টিফিকেট</strong></li></ul>
        <a href="register.php" class="btn btn-gold btn-full" style="justify-content:center;">ভর্তি হন</a>
      </div>
    </div>
  </div>
</section>

<!-- COURSE CATALOG -->
<section class="sec sec-w" id="courses">
  <div class="sec-inner">
    <div class="sec-head reveal">
      <span class="sec-lbl">আমাদের কোর্সসমূহ</span>
      <h2 class="sec-title">যেকোনো দক্ষতা অর্জন করুন</h2>
      <p class="sec-sub">Self-paced, Live Class, Cohort — আপনার সুবিধামতো শিখুন</p>
    </div>

    <!-- Category Tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-bottom:28px;" id="catTabs">
      <button class="cat-tab active" onclick="filterCat('all',this)">🌟 সব</button>
      <button class="cat-tab" onclick="filterCat('islamic',this)">📖 ইসলামিক</button>
      <button class="cat-tab" onclick="filterCat('web-dev',this)">💻 ওয়েব ডেভ</button>
      <button class="cat-tab" onclick="filterCat('marketing',this)">📱 মার্কেটিং</button>
      <button class="cat-tab" onclick="filterCat('automation',this)">🤖 অটোমেশন</button>
      <button class="cat-tab" onclick="filterCat('ai-tools',this)">✨ AI টুলস</button>
    </div>

    <!-- Course Cards (loaded from DB via PHP) -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;" id="courseGrid">
      <?php
      try {
        $all_courses = $pdo->query("
          SELECT c.*,cc.slug AS cat_slug,cc.icon AS cat_icon,cc.name AS cat_name
          FROM courses c
          JOIN course_categories cc ON cc.id=c.category_id
          WHERE c.is_active=1
          ORDER BY c.is_featured DESC, c.sort_order, c.created_at DESC
        ")->fetchAll();
      } catch(Exception $e) { $all_courses=[]; }

      $model_labels=['self_paced'=>'Self-paced','cohort'=>'Cohort','live'=>'Live','subscription'=>'Sub'];
      $model_colors=['self_paced'=>'#e0f2fe;color:#075985','cohort'=>'#fef9c3;color:#92400e','live'=>'#fee2e2;color:#991b1b','subscription'=>'#f0fdf4;color:#166534'];
      $level_labels=['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'];

      foreach($all_courses as $course):
        $price_html = '';
        if($course['is_free']) {
          $price_html = '<span style="font-size:1.1rem;font-weight:700;color:#16a34a;">🆓 বিনামূল্যে</span>';
        } elseif($course['sale_price']) {
          $disc = round((($course['price']-$course['sale_price'])/$course['price'])*100);
          $price_html = '<span style="font-size:1.1rem;font-weight:700;color:var(--g600);">৳'.number_format($course['sale_price']).'</span> <span style="text-decoration:line-through;color:#9ca3af;font-size:.82rem;">৳'.number_format($course['price']).'</span> <span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:20px;font-size:.65rem;font-weight:700;">'.$disc.'% ছাড়</span>';
        } else {
          $price_html = '<span style="font-size:1.1rem;font-weight:700;color:var(--g600);">৳'.number_format($course['price']).'</span>';
        }
        $mc = $model_colors[$course['model']]??'#f3f4f6;color:#374151';
      ?>
      <div class="course-item" data-cat="<?= htmlspecialchars($course['cat_slug']) ?>"
           style="background:white;border-radius:var(--radius-lg);border:1px solid var(--border);overflow:hidden;transition:all .2s;box-shadow:var(--shadow-xs);">
        <!-- Thumb -->
        <div style="height:110px;background:linear-gradient(135deg,var(--g700),var(--g600));display:flex;align-items:center;justify-content:center;font-size:2.8rem;position:relative;">
          <span><?= $course['cat_icon']??'📚' ?></span>
          <?php if($course['is_featured']): ?>
          <span style="position:absolute;top:8px;right:8px;background:var(--gold);color:var(--g800);padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;">⭐ Featured</span>
          <?php endif; ?>
          <span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.4);color:#fff;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;"><?= htmlspecialchars($course['cat_name']) ?></span>
        </div>
        <!-- Body -->
        <div style="padding:13px 14px;">
          <h3 style="font-size:.9rem;font-weight:700;color:var(--ink);margin-bottom:6px;line-height:1.3;"><?= htmlspecialchars($course['title']) ?></h3>
          <?php if($course['description']): ?>
          <p style="font-size:.75rem;color:var(--muted);margin-bottom:8px;line-height:1.5;"><?= mb_substr(htmlspecialchars($course['description']),0,80) ?>...</p>
          <?php endif; ?>
          <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
            <span style="background:<?= $mc ?>;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;"><?= $model_labels[$course['model']]??$course['model'] ?></span>
            <span style="background:var(--g100);color:var(--g600);padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;"><?= $level_labels[$course['level']]??$course['level'] ?></span>
            <?php if($course['total_lessons']): ?><span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;"><?= $course['total_lessons'] ?> Lessons</span><?php endif; ?>
          </div>
          <div style="font-size:.72rem;color:var(--muted);margin-bottom:10px;">
            <?php if($course['instructor']): ?>👨‍🏫 <?= htmlspecialchars($course['instructor']) ?><?php endif; ?>
            <?php if($course['duration']): ?> · ⏱ <?= htmlspecialchars($course['duration']) ?><?php endif; ?>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div><?= $price_html ?></div>
            <a href="register.php" style="background:var(--g600);color:#fff;padding:7px 14px;border-radius:7px;font-size:.78rem;font-weight:700;text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--g500)'" onmouseout="this.style.background='var(--g600)'">ভর্তি হন →</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($all_courses)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--muted);">কোনো কোর্স এখনো যোগ করা হয়নি।</div>
      <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:24px;">
      <a href="register.php" class="btn-main">📚 সব কোর্স দেখুন →</a>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->

<section class="sec sec-w">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl">ছাত্রদের মতামত</span><h2 class="sec-title">তারা যা বলছেন</h2></div>
    <div class="testi-grid">
      <div class="testi"><div class="stars">★★★★★</div><p>"আলহামদুলিল্লাহ, ৩ মাসেই তেলাওয়াত অনেক সুন্দর হয়েছে।"</p><div class="testi-auth"><div class="tav">র</div><div><strong>রহিমা বেগম</strong><span>ঢাকা</span></div></div></div>
      <div class="testi"><div class="stars">★★★★★</div><p>"আমার ছেলে এখন নিজে নিজে কুরআন পড়তে পারে।"</p><div class="testi-auth"><div class="tav">ক</div><div><strong>করিম সাহেব</strong><span>চট্টগ্রাম</span></div></div></div>
      <div class="testi"><div class="stars">★★★★★</div><p>"দুবাইতে থেকে ভালো হুজুর পাচ্ছিলাম না। এখন মাশাআল্লাহ।"</p><div class="testi-auth"><div class="tav">ম</div><div><strong>মোহাম্মদ আলী</strong><span>দুবাই প্রবাসী</span></div></div></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="sec sec-l">
  <div class="sec-inner">
    <div class="sec-head reveal"><span class="sec-lbl">সাধারণ প্রশ্ন</span><h2 class="sec-title">প্রায়ই জিজ্ঞেস করা প্রশ্ন</h2></div>
    <div class="faq-list">
      <?php foreach([
        ['ভর্তির পর ক্লাস কখন শুরু হবে?','পেমেন্ট কনফার্মের পর ২৪ ঘণ্টার মধ্যে শিক্ষক WhatsApp-এ যোগাযোগ করবেন।'],
        ['ক্লাস কখন হয়? সময় কি নিজে ঠিক করা যায়?','হ্যাঁ, সকাল ৬টা থেকে রাত ১০টা — আপনার পছন্দের সময়ে।'],
        ['ছোট বাচ্চাদের জন্য আলাদা ক্লাস আছে?','হ্যাঁ, ৫ বছর বয়স থেকে শিশু-বান্ধব পদ্ধতিতে পড়ানো হয়।'],
        ['পেমেন্ট কীভাবে করতে হয়?','bKash ও Nagad এর মাধ্যমে পেমেন্ট করুন।'],
        ['ক্লাস মিস হলে কী হবে?','প্রিমিয়াম প্যাকেজে রেকর্ড করা হয়। ২৪ ঘণ্টা আগে জানালে রিশিডিউল করা যাবে।'],
      ] as $q): ?>
      <div class="faq-item">
        <button class="faq-q" onclick="tFaq(this)"><?= $q[0] ?><span class="faq-arr">▼</span></button>
        <div class="faq-a"><p><?= $q[1] ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta">
  <h2>আর দেরি না করে আজই শুরু করুন</h2>
  <p>অনলাইনে শুদ্ধ কুরআন শিখুন — যেকোনো সময়, যেকোনো জায়গা থেকে</p>
  <a href="register.php" class="btn-main">📚 এখনই ভর্তি হন →</a>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-links">
    <?php if($fb): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank">📘 Facebook</a><?php endif; ?>
    <?php if($yt): ?><a href="<?= htmlspecialchars($yt) ?>" target="_blank">▶️ YouTube</a><?php endif; ?>
    <?php if($wa): ?><a href="https://wa.me/<?= preg_replace('/^0/','88',$wa) ?>" target="_blank">📱 WhatsApp</a><?php endif; ?>
    <a href="login.php">লগিন</a><a href="register.php">ভর্তি</a>
  </div>
  <p><?= htmlspecialchars(!empty($b['site_footer_text']) ? $b['site_footer_text'] : '© '.date('Y').' '.htmlspecialchars($__sname).' — সর্বস্বত্ব সংরক্ষিত') ?></p>
  <p style="font-size:.7rem;color:rgba(255,255,255,.2);margin-top:8px;">
    Designed &amp; Developed by 
    <a href="https://abdullahraiyan.com" target="_blank" rel="noopener"
       style="color:rgba(255,255,255,.3);text-decoration:none;"
       onmouseover="this.style.color='#b8963e'"
       onmouseout="this.style.color='rgba(255,255,255,.3)'">Abdullah Raiyan</a>
    &nbsp;&copy; 2026
  </p>
</footer>

<script>
function filterCat(slug,btn){
  document.querySelectorAll('.cat-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.course-item').forEach(card=>{
    card.style.display=(slug==='all'||card.dataset.cat===slug)?'':'none';
  });
}
function tFaq(btn){const item=btn.parentElement;const open=item.classList.contains('open');document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('open'));if(!open)item.classList.add('open');}
</script>

<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(el => {
    if (el.isIntersecting) { el.target.classList.add('visible'); }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>
