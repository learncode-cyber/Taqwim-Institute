<?php
/**
 * AR Qudrix Super Admin
 * 
 * SECURITY: এই folder-এর নাম পরিবর্তন করুন!
 * 
 * ১. এই folder "superadmin" → যেকোনো নাম দিন
 *    যেমন: "ar-panel", "qudrix", "manage", "ctrl" ইত্যাদি
 *    URL হবে: yourdomain.com/YOUR_FOLDER_NAME/?key=ARQudrix@SuperKey2025
 * 
 * ২. SA_SECRET_KEY পরিবর্তন করুন (নিচে)
 * 
 * © Abdullah Raiyan | abdullahraiyan.com
 */
session_start();

// ══════════════════════════════════════════
// SECRET ACCESS KEY — এটা পরিবর্তন করুন!
// URL হবে: yourdomain.com/superadmin/?key=YOUR_SECRET
// ══════════════════════════════════════════
define('SA_SECRET_KEY', 'ARQudrix@SuperKey2025');

// Key check (login page access করতেও key লাগবে)
if (!isset($_SESSION['sa_id'])) {
    $provided_key = $_GET['key'] ?? $_SESSION['sa_key'] ?? '';
    if ($provided_key !== SA_SECRET_KEY) {
        http_response_code(404);
        die('<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 Not Found</h1></body></html>');
    }
    $_SESSION['sa_key'] = $provided_key; // remember key in session
}

// ── Super Admin DB Config ──
define('SA_DB_HOST', 'localhost');
define('SA_DB_NAME', 'u290513561_talim_database'); // Same DB, separate tables
define('SA_DB_USER', 'u290513561_talim_database');
define('SA_DB_PASS', 'YOUR_DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=".SA_DB_HOST.";dbname=".SA_DB_NAME.";charset=utf8mb4",
        SA_DB_USER, SA_DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (PDOException $e) { die('DB Error: '.$e->getMessage()); }

// ── Auth ──
function sa_current(): ?array {
    global $pdo;
    if (empty($_SESSION['sa_id'])) return null;
    $s = $pdo->prepare("SELECT * FROM super_admins WHERE id=?");
    $s->execute([$_SESSION['sa_id']]);
    return $s->fetch() ?: null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Login
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $s = $pdo->prepare("SELECT * FROM super_admins WHERE email=?");
    $s->execute([$email]); $sa = $s->fetch();
    if ($sa && password_verify($pass, $sa['password'])) {
        session_regenerate_id(true);
        $_SESSION['sa_id'] = $sa['id'];
        header('Location: index.php'); exit;
    }
    $login_error = 'Email বা Password ভুল।';
}
if ($action === 'logout') { session_destroy(); header('Location: index.php'); exit; }

// ── Create Tenant ──
if ($action === 'add_tenant' && sa_current()) {
    $name  = trim($_POST['name'] ?? '');
    $domain= trim($_POST['domain'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $pkg   = $_POST['package'] ?? 'basic';
    $trial = $_POST['trial_ends'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    if ($name && $domain && $email && $pass) {
        try {
            $pdo->prepare("INSERT INTO tenants (name,domain,admin_email,admin_pass,package,status,trial_ends,notes) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$name,$domain,$email,password_hash($pass,PASSWORD_DEFAULT),$pkg,'trial',$trial?:null,$notes]);
            $flash = "✅ '{$name}' যোগ করা হয়েছে!";
        } catch (\PDOException $e) { $flash_err = 'এই domain আগেই আছে।'; }
    }
}

// ── Update Tenant Status ──
if ($action === 'status' && sa_current()) {
    $pdo->prepare("UPDATE tenants SET status=? WHERE id=?")
        ->execute([$_POST['status'], intval($_POST['id'])]);
    $flash = 'স্ট্যাটাস আপডেট হয়েছে।';
}

// ── Delete Tenant ──
if ($action === 'delete' && sa_current()) {
    $pdo->prepare("DELETE FROM tenants WHERE id=?")->execute([intval($_GET['id'])]);
    $flash = 'ক্লায়েন্ট মুছে ফেলা হয়েছে।';
}

// ── Change SA Password ──
if ($action === 'change_pass' && sa_current()) {
    $sa = sa_current();
    $cur  = $_POST['current'] ?? '';
    $new  = $_POST['new_pass'] ?? '';
    $conf = $_POST['confirm']  ?? '';
    if (!password_verify($cur, $sa['password'])) $flash_err = 'বর্তমান পাসওয়ার্ড ভুল।';
    elseif (strlen($new)<8)  $flash_err = 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষর।';
    elseif ($new!==$conf)    $flash_err = 'পাসওয়ার্ড মিলছে না।';
    else {
        $pdo->prepare("UPDATE super_admins SET password=? WHERE id=?")
            ->execute([password_hash($new,PASSWORD_DEFAULT), $sa['id']]);
        $flash = 'পাসওয়ার্ড পরিবর্তন হয়েছে ✅';
    }
}

$sa = sa_current();
$flash     = $flash     ?? ($_SESSION['flash']     ?? ''); unset($_SESSION['flash']);
$flash_err = $flash_err ?? ($_SESSION['flash_err'] ?? ''); unset($_SESSION['flash_err']);

// ── Data ──
if ($sa) {
    $tenants      = $pdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll();
    $total        = count($tenants);
    $active       = count(array_filter($tenants, fn($t)=>$t['status']==='active'));
    $trial        = count(array_filter($tenants, fn($t)=>$t['status']==='trial'));
    $suspended    = count(array_filter($tenants, fn($t)=>$t['status']==='suspended'));
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Super Admin — AR Qudrix</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --dark: #0a0f1e; --dark2: #111827; --dark3: #1f2937;
  --accent: #6366f1; --accent2: #818cf8;
  --gold: #f59e0b; --gold2: #fbbf24;
  --green: #10b981; --red: #ef4444; --blue: #3b82f6;
  --text: #f9fafb; --muted: #9ca3af; --border: rgba(255,255,255,.08);
  --font: 'Inter', system-ui, sans-serif;
}
html { -webkit-tap-highlight-color: transparent; }
body { font-family: var(--font); background: var(--dark); color: var(--text); min-height: 100vh; }

/* LOGIN */
.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; background: radial-gradient(ellipse at 20% 50%, rgba(99,102,241,.15) 0%, transparent 60%), radial-gradient(ellipse at 80% 20%, rgba(245,158,11,.08) 0%, transparent 50%), var(--dark); }
.login-card { background: var(--dark2); border: 1px solid var(--border); border-radius: 20px; padding: 40px 32px; width: 100%; max-width: 400px; box-shadow: 0 24px 64px rgba(0,0,0,.5); }
.sa-logo { text-align: center; margin-bottom: 28px; }
.sa-logo .ring { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #7c3aed); margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 8px 32px rgba(99,102,241,.4); }
.sa-logo h1 { font-size: 1.3rem; font-weight: 700; }
.sa-logo p  { color: var(--muted); font-size: .82rem; margin-top: 4px; }
.inp { width: 100%; padding: 11px 14px; background: var(--dark3); border: 1px solid var(--border); border-radius: 9px; color: var(--text); font-size: 15px; font-family: var(--font); outline: none; transition: border-color .15s; margin-top: 5px; }
.inp:focus { border-color: var(--accent); }
.fg { margin-bottom: 14px; }
.fg label { font-size: .82rem; font-weight: 600; color: var(--muted); }
.btn-login { width: 100%; padding: 13px; background: var(--accent); color: #fff; border: none; border-radius: 9px; font-size: 1rem; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all .15s; margin-top: 4px; }
.btn-login:hover { background: var(--accent2); }
.err-box { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: .875rem; margin-bottom: 14px; }

/* PANEL */
.panel { display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: var(--dark2); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; }
.sb-logo { padding: 20px 18px; border-bottom: 1px solid var(--border); }
.sb-logo .brand { font-size: 1rem; font-weight: 800; background: linear-gradient(135deg, var(--accent2), var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.sb-logo small { color: var(--muted); font-size: .68rem; display: block; margin-top: 2px; }
.sb-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
.sb-link { display: flex; align-items: center; gap: 9px; padding: 10px 10px; border-radius: 8px; color: var(--muted); font-size: .875rem; cursor: pointer; border: none; background: none; font-family: var(--font); width: 100%; text-align: left; transition: all .15s; margin-bottom: 2px; }
.sb-link:hover { background: var(--dark3); color: var(--text); }
.sb-link.active { background: rgba(99,102,241,.15); color: var(--accent2); }
.sb-icon { width: 20px; text-align: center; flex-shrink: 0; }
.sb-user { padding: 14px 16px; border-top: 1px solid var(--border); }
.sb-user strong { display: block; font-size: .85rem; color: var(--text); }
.sb-user span   { font-size: .72rem; color: var(--muted); }
.btn-logout { display: block; width: 100%; padding: 7px; text-align: center; background: rgba(239,68,68,.1); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); border-radius: 7px; font-size: .78rem; font-weight: 600; cursor: pointer; font-family: var(--font); margin-top: 8px; transition: all .15s; }
.btn-logout:hover { background: rgba(239,68,68,.2); }

.main { margin-left: 240px; flex: 1; min-width: 0; }
.topbar { height: 58px; background: var(--dark2); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: sticky; top: 0; z-index: 99; }
.topbar h2 { font-size: .95rem; font-weight: 700; }
.page-body { padding: 20px; max-width: 1100px; }

/* STATS */
.stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
.stat-box { background: var(--dark2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
.stat-box .num  { font-size: 2rem; font-weight: 800; line-height: 1; }
.stat-box .lbl  { font-size: .75rem; color: var(--muted); margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.stat-box.ac .num { color: var(--green); }
.stat-box.tr .num { color: var(--gold); }
.stat-box.su .num { color: var(--red); }
.stat-box.to .num { color: var(--accent2); }

/* CARDS */
.card { background: var(--dark2); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 20px; }
.card-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--border); }
.card-head h3 { font-size: .95rem; font-weight: 700; }
.card-body { padding: 18px; }

/* TABLE */
.tbl-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .875rem; }
th { padding: 10px 14px; text-align: left; font-size: .72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; background: rgba(255,255,255,.02); white-space: nowrap; }
td { padding: 12px 14px; border-top: 1px solid var(--border); vertical-align: middle; }
tr:hover td { background: rgba(255,255,255,.02); }

/* BADGES */
.badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.b-active    { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.2); }
.b-trial     { background: rgba(245,158,11,.12); color: var(--gold2); border: 1px solid rgba(245,158,11,.2); }
.b-suspended { background: rgba(239,68,68,.12); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }
.b-basic      { background: rgba(99,102,241,.1); color: var(--accent2); }
.b-pro        { background: rgba(245,158,11,.1); color: var(--gold2); }
.b-enterprise { background: rgba(16,185,129,.1); color: #34d399; }

/* BUTTONS */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 8px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer; font-family: var(--font); transition: all .15s; text-decoration: none; white-space: nowrap; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent2); }
.btn-sm { padding: 5px 10px; font-size: .75rem; }
.btn-red { background: rgba(239,68,68,.12); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }
.btn-red:hover { background: var(--red); color: #fff; }
.btn-green { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.2); }
.btn-green:hover { background: var(--green); color: #fff; }
.btn-gold { background: rgba(245,158,11,.12); color: var(--gold2); border: 1px solid rgba(245,158,11,.2); }
.btn-gold:hover { background: var(--gold); color: #fff; }
.btn-full { width: 100%; justify-content: center; }

/* FORMS */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.fg2 { margin-bottom: 13px; }
.fg2 label { display: block; font-size: .8rem; font-weight: 600; color: var(--muted); margin-bottom: 5px; }

/* TAB PANE */
.tab-pane { display: none; }
.tab-pane.active { display: block; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 500; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; pointer-events: none; transition: opacity .2s; }
.modal-overlay.active { opacity: 1; pointer-events: all; }
.modal-box { background: var(--dark2); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); }
.modal-head h3 { font-size: 1rem; font-weight: 700; }
.modal-close { width: 30px; height: 30px; border-radius: 50%; border: none; background: var(--dark3); color: var(--muted); cursor: pointer; font-size: 1rem; }
.modal-body { padding: 20px; }

/* ALERTS */
.alert { padding: 11px 14px; border-radius: 9px; font-size: .875rem; margin-bottom: 14px; }
.alert-ok  { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2); color: #34d399; }
.alert-err { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.2);  color: #fca5a5; }

/* Tenant card mobile */
.tenant-card { background: var(--dark3); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 10px; }

/* Footer */
.sa-footer { padding: 20px; text-align: center; color: var(--muted); font-size: .78rem; border-top: 1px solid var(--border); margin-top: 40px; }
.sa-footer a { color: var(--accent2); text-decoration: none; }

@media(max-width: 768px) {
  .sidebar { transform: translateX(-100%); transition: transform .25s; }
  .sidebar.open { transform: translateX(0); }
  .main { margin-left: 0; }
  .form-grid { grid-template-columns: 1fr; }
  input, select { font-size: 16px !important; }
}
</style>
</head>
<body>

<?php if (!$sa): ?>
<!-- ═══ LOGIN ═══ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="sa-logo">
      <div class="ring">⚡</div>
      <h1>AR Qudrix</h1>
      <p>Super Admin Panel</p>
    </div>
    <?php if(!empty($login_error)): ?><div class="err-box">❌ <?= htmlspecialchars($login_error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="fg"><label>Email</label><input class="inp" type="email" name="email" required placeholder="super@example.com" autocomplete="email"></div>
      <div class="fg"><label>Password</label><input class="inp" type="password" name="password" required placeholder="••••••••" autocomplete="current-password"></div>
      <button type="submit" class="btn-login">প্রবেশ করুন →</button>
    </form>
    <div style="text-align:center;margin-top:20px;font-size:.72rem;color:var(--muted);">
      Powered by <a href="https://abdullahraiyan.com" target="_blank" style="color:var(--accent2);text-decoration:none;">Abdullah Raiyan</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══ PANEL ═══ -->
<div class="panel">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="brand">⚡ AR Qudrix</div>
    <small>Super Admin Panel</small>
  </div>
  <div class="sb-nav">
    <button class="sb-link active" onclick="sw('dashboard',this)"><span class="sb-icon">📊</span>ড্যাশবোর্ড</button>
    <button class="sb-link" onclick="sw('clients',this)"><span class="sb-icon">🏢</span>ক্লায়েন্টস</button>
    <button class="sb-link" onclick="sw('add',this)"><span class="sb-icon">➕</span>নতুন ক্লায়েন্ট</button>
    <button class="sb-link" onclick="sw('password',this)"><span class="sb-icon">🔐</span>পাসওয়ার্ড</button>
  </div>
  <div class="sb-user">
    <strong><?= htmlspecialchars($sa['name']) ?></strong>
    <span><?= htmlspecialchars($sa['email']) ?></span>
    <form method="POST" style="margin-top:8px;">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="btn-logout">লগআউট</button>
    </form>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <h2 id="page-title">📊 ড্যাশবোর্ড</h2>
    <div style="display:flex;gap:8px;align-items:center;">
      <span style="font-size:.78rem;color:var(--muted);"><?= date('d M Y') ?></span>
      <button class="btn btn-primary btn-sm" onclick="sw('add')">+ নতুন ক্লায়েন্ট</button>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):    ?><div class="alert alert-ok">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err):?><div class="alert alert-err">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

    <!-- DASHBOARD -->
    <div id="p-dashboard" class="tab-pane active">
      <div class="stat-row">
        <div class="stat-box to"><div class="num"><?= $total ?></div><div class="lbl">মোট ক্লায়েন্ট</div></div>
        <div class="stat-box ac"><div class="num"><?= $active ?></div><div class="lbl">সক্রিয়</div></div>
        <div class="stat-box tr"><div class="num"><?= $trial ?></div><div class="lbl">ট্রায়াল</div></div>
        <div class="stat-box su"><div class="num"><?= $suspended ?></div><div class="lbl">সাসপেন্ড</div></div>
      </div>

      <div class="card">
        <div class="card-head"><h3>🏢 সাম্প্রতিক ক্লায়েন্ট</h3><button class="btn btn-primary btn-sm" onclick="sw('clients')">সব দেখুন →</button></div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>প্রতিষ্ঠান</th><th>Domain</th><th>Package</th><th>Status</th><th>তারিখ</th></tr></thead>
            <tbody>
              <?php foreach(array_slice($tenants,0,5) as $t): ?>
              <tr>
                <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                <td><a href="https://<?= htmlspecialchars($t['domain']) ?>" target="_blank" style="color:var(--accent2);text-decoration:none;"><?= htmlspecialchars($t['domain']) ?></a></td>
                <td><span class="badge b-<?= $t['package'] ?>"><?= ucfirst($t['package']) ?></span></td>
                <td><span class="badge b-<?= $t['status'] ?>"><?= ['active'=>'✅ সক্রিয়','trial'=>'⏳ ট্রায়াল','suspended'=>'❌ সাসপেন্ড'][$t['status']] ?></span></td>
                <td style="color:var(--muted);font-size:.8rem;"><?= date('d M Y',strtotime($t['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick stats by package -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
        <?php
        $pkgs = ['basic'=>['🥉 Basic','var(--accent2)'],'pro'=>['🥈 Pro','var(--gold2)'],'enterprise'=>['🥇 Enterprise','var(--green)']];
        foreach ($pkgs as $pkg => [$label,$color]):
          $cnt = count(array_filter($tenants, fn($t)=>$t['package']===$pkg));
        ?>
        <div class="card">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div style="font-size:1.8rem;font-weight:800;color:<?= $color ?>;"><?= $cnt ?></div>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px;"><?= $label ?> ক্লায়েন্ট</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CLIENTS -->
    <div id="p-clients" class="tab-pane">
      <div class="card">
        <div class="card-head"><h3>🏢 সকল ক্লায়েন্ট (<?= $total ?>)</h3></div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>#</th><th>প্রতিষ্ঠান</th><th>Domain</th><th>Admin Email</th><th>Package</th><th>Status</th><th>Trial শেষ</th><th>Action</th></tr></thead>
            <tbody>
              <?php foreach($tenants as $i=>$t): ?>
              <tr>
                <td style="color:var(--muted);font-size:.8rem;"><?= $i+1 ?></td>
                <td>
                  <strong><?= htmlspecialchars($t['name']) ?></strong>
                  <?php if($t['notes']): ?><div style="font-size:.72rem;color:var(--muted);"><?= htmlspecialchars($t['notes']) ?></div><?php endif; ?>
                </td>
                <td><a href="https://<?= htmlspecialchars($t['domain']) ?>" target="_blank" style="color:var(--accent2);font-size:.82rem;">🔗 <?= htmlspecialchars($t['domain']) ?></a></td>
                <td style="font-size:.8rem;color:var(--muted);"><?= htmlspecialchars($t['admin_email']) ?></td>
                <td><span class="badge b-<?= $t['package'] ?>"><?= ucfirst($t['package']) ?></span></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <select name="status" onchange="this.form.submit()" style="background:var(--dark3);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;font-size:.75rem;cursor:pointer;">
                      <?php foreach(['active'=>'✅ সক্রিয়','trial'=>'⏳ ট্রায়াল','suspended'=>'❌ সাসপেন্ড'] as $v=>$l): ?>
                      <option value="<?= $v ?>" <?= $t['status']===$v?'selected':'' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td style="font-size:.8rem;color:var(--muted);"><?= $t['trial_ends'] ? date('d M Y',strtotime($t['trial_ends'])) : '—' ?></td>
                <td>
                  <a href="https://<?= htmlspecialchars($t['domain']) ?>/admin" target="_blank" class="btn btn-gold btn-sm">⚙️</a>
                  <a href="?action=delete&id=<?= $t['id'] ?>" class="btn btn-red btn-sm" onclick="return confirm('\'<?= htmlspecialchars($t['name']) ?>\' মুছবেন?')">🗑</a>
                </td>
              </tr>
              <?php endforeach; if(empty($tenants)): ?>
              <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted);">কোনো ক্লায়েন্ট নেই। নতুন ক্লায়েন্ট যোগ করুন।</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ADD CLIENT -->
    <div id="p-add" class="tab-pane">
      <div class="card">
        <div class="card-head"><h3>➕ নতুন ক্লায়েন্ট যোগ করুন</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="add_tenant">
            <div class="form-grid">
              <div class="fg2"><label>প্রতিষ্ঠানের নাম *</label><input class="inp" type="text" name="name" required placeholder="যেমন: Taqwim Institute"></div>
              <div class="fg2"><label>Domain *</label><input class="inp" type="text" name="domain" required placeholder="যেমন: arprimemarket.shop"></div>
            </div>
            <div class="form-grid">
              <div class="fg2"><label>Admin Email *</label><input class="inp" type="email" name="email" required placeholder="admin@domain.com"></div>
              <div class="fg2"><label>Admin Password *</label><input class="inp" type="text" name="password" required placeholder="শক্তিশালী পাসওয়ার্ড"></div>
            </div>
            <div class="form-grid">
              <div class="fg2">
                <label>Package</label>
                <select class="inp" name="package">
                  <option value="basic">🥉 Basic</option>
                  <option value="pro">🥈 Pro</option>
                  <option value="enterprise">🥇 Enterprise</option>
                </select>
              </div>
              <div class="fg2"><label>Trial শেষ তারিখ</label><input class="inp" type="date" name="trial_ends" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
            </div>
            <div class="fg2"><label>নোট (ঐচ্ছিক)</label><textarea class="inp" name="notes" placeholder="ক্লায়েন্ট সম্পর্কে যেকোনো নোট..." style="min-height:70px;resize:vertical;"></textarea></div>

            <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:.82rem;color:var(--muted);line-height:1.8;">
              📋 <strong style="color:var(--accent2);">ক্লায়েন্ট setup checklist:</strong><br>
              1. এই form submit করুন (record রাখার জন্য)<br>
              2. Hostinger-এ নতুন hosting কিনুন / subdomain করুন<br>
              3. LMS ZIP আপলোড করুন → database.sql import করুন<br>
              4. db.php এ credentials বসান → Admin login দিয়ে branding সেট করুন<br>
              5. Status "Active" করুন
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="padding:13px;font-size:1rem;">✅ ক্লায়েন্ট রেকর্ড যোগ করুন</button>
          </form>
        </div>
      </div>
    </div>

    <!-- CHANGE PASSWORD -->
    <div id="p-password" class="tab-pane">
      <div class="card" style="max-width:400px;">
        <div class="card-head"><h3>🔐 পাসওয়ার্ড পরিবর্তন</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="change_pass">
            <div class="fg2"><label>বর্তমান পাসওয়ার্ড</label><input class="inp" type="password" name="current" required></div>
            <div class="fg2"><label>নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label><input class="inp" type="password" name="new_pass" required></div>
            <div class="fg2"><label>নতুন পাসওয়ার্ড নিশ্চিত করুন</label><input class="inp" type="password" name="confirm" required></div>
            <button type="submit" class="btn btn-primary btn-full">✅ পরিবর্তন করুন</button>
          </form>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="sa-footer">
      © <?= date('Y') ?> AR Qudrix Super Admin Panel &nbsp;·&nbsp;
      Designed & Developed by <a href="https://abdullahraiyan.com" target="_blank">Abdullah Raiyan</a>
    </div>

  </div>
</div>
</div>

<?php endif; ?>

<script>
const TITLES={dashboard:'📊 ড্যাশবোর্ড',clients:'🏢 ক্লায়েন্টস',add:'➕ নতুন ক্লায়েন্ট',password:'🔐 পাসওয়ার্ড'};
function sw(tab, el) {
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.getElementById('p-'+tab).classList.add('active');
  document.getElementById('page-title').textContent = TITLES[tab] || tab;
  document.querySelectorAll('.sb-link').forEach(b=>b.classList.remove('active'));
  if(el) el.classList.add('active');
}
</script>
</body>
</html>
