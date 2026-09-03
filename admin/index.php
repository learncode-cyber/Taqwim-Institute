<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user  = require_role(['admin']);
$b     = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
$tab = $_GET['tab'] ?? 'dashboard';

// Save settings
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_settings'])) {
    $keys = ['site_name','meta_pixel_id','telegram_bot_token','telegram_chat_id','whatsapp_number','bkash_number','nagad_number','meet_default_link','zoom_default_link','facebook_page','youtube_channel','admin_email'];
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        $pdo->prepare("INSERT INTO settings (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?")->execute([$k,$v,$v]);
    }
    $flash = 'সেটিংস সেভ হয়েছে ✅'; $tab='settings';
}

// Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$total_classes  = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$pending_pays   = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
$total_leads    = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='confirmed'")->fetchColumn();

// Data
$students = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY created_at DESC")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role='teacher' ORDER BY created_at DESC")->fetchAll();
$classes  = $pdo->query("SELECT c.*,u.name AS teacher_name,(SELECT COUNT(*) FROM class_students WHERE class_id=c.id) AS std_count FROM classes c JOIN users u ON u.id=c.teacher_id ORDER BY c.class_date DESC,c.class_time DESC LIMIT 30")->fetchAll();
$payments = $pdo->query("SELECT p.*,u.name AS student_name FROM payments p JOIN users u ON p.student_id=u.id ORDER BY p.created_at DESC LIMIT 30")->fetchAll();
$leads    = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 30")->fetchAll();
$wa_num   = get_setting('whatsapp_number');

// Settings for form
$skeys = ['site_name','meta_pixel_id','telegram_bot_token','telegram_chat_id','whatsapp_number','bkash_number','nagad_number','meet_default_link','zoom_default_link','facebook_page','youtube_channel','admin_email'];
$cfg=[]; foreach($pdo->query("SELECT key_name,value FROM settings") as $r) $cfg[$r['key_name']]=$r['value'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>অ্যাডমিন — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<?php if(!empty($cfg['meta_pixel_id'])): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?=htmlspecialchars($cfg['meta_pixel_id'])?>');fbq('track','PageView');</script>
<?php endif; ?>
<style>
.tab-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;background:white;padding:10px;border-radius:var(--r-lg);border:1px solid var(--border);}
.tab-btn{padding:8px 14px;border-radius:var(--r-sm);border:none;background:none;font-family:var(--font-bn);font-size:.82rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all .15s;white-space:nowrap;}
.tab-btn.active{background:var(--p600);color:#fff;}
.tab-btn:hover:not(.active){background:var(--p50);color:var(--p600);}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;display:flex;align-items:flex-end;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
.modal-overlay.active{opacity:1;pointer-events:all;}
.modal-box{background:white;border-radius:20px 20px 0 0;width:100%;max-width:520px;max-height:88vh;overflow-y:auto;transform:translateY(100%);transition:transform .3s;}
.modal-overlay.active .modal-box{transform:translateY(0);}
.modal-drag{width:40px;height:4px;background:var(--border);border-radius:2px;margin:12px auto 0;}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px 10px;border-bottom:1px solid var(--border);}
.modal-head h3{font-size:1rem;font-weight:700;color:var(--ink);}
.modal-close{width:30px;height:30px;border-radius:50%;border:none;background:var(--p50);color:var(--muted);font-size:1rem;cursor:pointer;}
.modal-body{padding:16px 18px 28px;}
@media(min-width:900px){.modal-overlay{align-items:center;}.modal-box{border-radius:var(--r-xl);max-height:80vh;}}

.search-wrap{position:relative;margin-bottom:14px;}
.search-wrap input{padding-left:36px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r-sm);width:100%;font-size:.875rem;}
.search-wrap input:focus{border-color:var(--p600);box-shadow:0 0 0 3px var(--p100);}
.search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.95rem;pointer-events:none;}
.no-result{text-align:center;padding:28px;color:var(--muted);font-size:.875rem;display:none;}
</style>
<script>
// Prevent flash of wrong theme
(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
</script>
<script src="../assets/js/theme.js" defer></script>
</head>
<body>
<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark"><img src="<?= htmlspecialchars($__logo) ?>" alt="Logo" style="width:28px;height:28px;object-fit:contain;"></div>
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>অ্যাডমিন প্যানেল</small></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">ম্যানেজমেন্ট</div>
    <a class="nav-link <?=$tab==='dashboard'?'active':''?>" onclick="switchTab('dashboard',this)"><span class="nav-icon">📊</span>ড্যাশবোর্ড</a>
    <a class="nav-link <?=$tab==='students'?'active':''?>" onclick="switchTab('students',this)"><span class="nav-icon">👨‍🎓</span>ছাত্র</a>
    <a class="nav-link <?=$tab==='teachers'?'active':''?>" onclick="switchTab('teachers',this)"><span class="nav-icon">👨‍🏫</span>শিক্ষক</a>
    <a class="nav-link <?=$tab==='classes'?'active':''?>" onclick="switchTab('classes',this)"><span class="nav-icon">📅</span>ক্লাস</a>
    <a class="nav-link <?=$tab==='payments'?'active':''?>" onclick="switchTab('payments',this)"><span class="nav-icon">💳</span>পেমেন্ট</a>
    <a class="nav-link <?=$tab==='leads'?'active':''?>" onclick="switchTab('leads',this)"><span class="nav-icon">📋</span>লিড</a>
    <div class="nav-label" style="margin-top:8px;">কনফিগারেশন</div>
    <a class="nav-link <?=$tab==='settings'?'active':''?>" onclick="switchTab('settings',this)"><span class="nav-icon">⚙️</span>সেটিংস</a>
    <a class="nav-link" href="courses.php"><span class="nav-icon">🎓</span>কোর্সসমূহ</a>
    <a class="nav-link" href="quiz.php"><span class="nav-icon">🧠</span>Quiz & Assignment</a>
    <a class="nav-link" href="crm.php"><span class="nav-icon">🎯</span>CRM</a>
    <a class="nav-link" href="coupons.php"><span class="nav-icon">🎟️</span>কুপন</a>
    <a class="nav-link" href="branding.php"><span class="nav-icon">🎨</span>White Label</a>
    <a class="nav-link" href="change_password.php"><span class="nav-icon">🔐</span>পাসওয়ার্ড</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row"><div class="user-av"><?= mb_substr($user['name'],0,1) ?></div><div class="user-info"><strong><?= htmlspecialchars($user['name']) ?></strong><span>Admin</span></div></div>
    <a href="../api/auth.php?action=logout" class="btn-logout">লগআউট</a>
  </div>
</aside>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left"><button class="menu-toggle" onclick="toggleSidebar()">☰</button><span class="page-title" id="pt">ড্যাশবোর্ড</span></div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span>
      <button class="btn btn-primary btn-sm always" id="addBtn" onclick="openAddModal()" style="display:none;">+ যোগ করুন</button>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):    ?><div class="alert alert-success mb-12">✅ <?=htmlspecialchars($flash)?></div><?php endif;?>
    <?php if($flash_err):?><div class="alert alert-danger  mb-12">❌ <?=htmlspecialchars($flash_err)?></div><?php endif;?>

    <!-- DASHBOARD -->
    <div id="p-dashboard" class="tab-pane active">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat"><div class="stat-label">মোট ছাত্র</div><div class="stat-value"><?=$total_students?></div></div>
        <div class="stat gold"><div class="stat-label">মোট শিক্ষক</div><div class="stat-value"><?=$total_teachers?></div></div>
        <div class="stat info"><div class="stat-label">মোট ক্লাস</div><div class="stat-value"><?=$total_classes?></div></div>
        <div class="stat danger"><div class="stat-label">পেন্ডিং পেমেন্ট</div><div class="stat-value"><?=$pending_pays?></div></div>
        <div class="stat gold"><div class="stat-label">লিড</div><div class="stat-value"><?=$total_leads?></div></div>
        <div class="stat"><div class="stat-label">মোট আয়</div><div class="stat-value" style="font-size:1.2rem;">৳<?=number_format($total_revenue)?></div></div>
      </div>
      <div class="card">
        <div class="card-head"><h2>📥 দ্রুত লিংক</h2></div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px;">
          <a href="../api/export.php?type=students" class="btn btn-outline btn-sm">📥 ছাত্র CSV</a>
          <a href="../api/export.php?type=payments" class="btn btn-outline btn-sm">📥 পেমেন্ট CSV</a>
          <a href="../api/export.php?type=leads"    class="btn btn-outline btn-sm">📥 লিড CSV</a>
          <a href="../api/test_telegram.php" target="_blank" class="btn btn-ghost btn-sm">📱 Telegram Test</a>
          <a href="coupons.php" class="btn btn-gold btn-sm">🎟️ কুপন ম্যানেজ</a>
          <a href="branding.php" class="btn btn-primary btn-sm">🎨 White Label</a>
        </div>
      </div>
    </div>

    <!-- STUDENTS -->
    <div id="p-students" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>👨‍🎓 ছাত্র তালিকা (<?=count($students)?>)</h2><button class="btn btn-primary btn-sm" onclick="openModal('addStudentModal')">+ নতুন ছাত্র</button></div>
        <div style="padding:12px 16px 0;"><div class="search-wrap"><span class="search-icon">🔍</span><input type="text" placeholder="নাম, ইমেইল বা ফোন..." oninput="liveSearch(this,'tStd','nStd')"></div></div>
        <div class="table-wrap"><div class="no-result" id="nStd">😕 পাওয়া যায়নি</div>
          <table id="tStd">
            <thead><tr><th>নাম</th><th>ইমেইল</th><th>ফোন</th><th>প্যাকেজ</th><th>যোগদান</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($students as $s): ?>
            <tr>
              <td><strong><?=htmlspecialchars($s['name'])?></strong></td>
              <td class="text-sm text-muted"><?=htmlspecialchars($s['email'])?></td>
              <td><?=htmlspecialchars($s['phone'])?></td>
              <td><span class="badge badge-<?=$s['package']?>"><?=pkg_name($s['package']??'basic')?></span></td>
              <td class="text-sm text-muted"><?=date('d M Y',strtotime($s['created_at']))?></td>
              <td>
                <?php if($wa_num): $wp=preg_replace('/^0/','88',$s['phone']); $msg=urlencode("আস-সালামু আলাইকুম {$s['name']}! {$__sname} থেকে যোগাযোগ করছি।"); ?>
                <a href="https://wa.me/<?=$wp?>?text=<?=$msg?>" target="_blank" class="btn btn-wa btn-sm">📱</a>
                <?php endif; ?>
                <a href="../api/users.php?action=delete&id=<?=$s['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TEACHERS -->
    <div id="p-teachers" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>👨‍🏫 শিক্ষক তালিকা</h2><button class="btn btn-primary btn-sm" onclick="openModal('addTeacherModal')">+ নতুন শিক্ষক</button></div>
        <div style="padding:12px 16px 0;"><div class="search-wrap"><span class="search-icon">🔍</span><input type="text" placeholder="নাম, ইমেইল বা ফোন..." oninput="liveSearch(this,'tTch','nTch')"></div></div>
        <div class="table-wrap"><div class="no-result" id="nTch">😕 পাওয়া যায়নি</div>
          <table id="tTch">
            <thead><tr><th>নাম</th><th>ইমেইল</th><th>ফোন</th><th>পরিচিতি</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($teachers as $t): ?>
            <tr>
              <td><strong><?=htmlspecialchars($t['name'])?></strong></td>
              <td class="text-sm text-muted"><?=htmlspecialchars($t['email'])?></td>
              <td><?=htmlspecialchars($t['phone'])?></td>
              <td class="text-sm text-muted truncate" style="max-width:150px;"><?=htmlspecialchars($t['bio']??'')?></td>
              <td><a href="../api/users.php?action=delete&id=<?=$t['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CLASSES -->
    <div id="p-classes" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>📅 ক্লাস তালিকা</h2><button class="btn btn-primary btn-sm" onclick="openModal('addClassModal')">+ নতুন ক্লাস</button></div>
        <div style="padding:12px 16px 0;"><div class="search-wrap"><span class="search-icon">🔍</span><input type="text" placeholder="শিরোনাম বা শিক্ষকের নাম..." oninput="liveSearch(this,'tCls','nCls')"></div></div>
        <div class="table-wrap"><div class="no-result" id="nCls">😕 পাওয়া যায়নি</div>
          <table id="tCls">
            <thead><tr><th>শিরোনাম</th><th>শিক্ষক</th><th>তারিখ</th><th>সময়</th><th>ছাত্র</th><th>অবস্থা</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($classes as $c): ?>
            <tr>
              <td class="truncate" style="max-width:140px;"><strong><?=htmlspecialchars($c['title'])?></strong></td>
              <td class="text-sm"><?=htmlspecialchars($c['teacher_name'])?></td>
              <td class="text-sm text-muted"><?=date('d M Y',strtotime($c['class_date']))?></td>
              <td class="text-sm"><?=$c['class_time']?></td>
              <td><span class="badge badge-active"><?=$c['std_count']?> জন</span></td>
              <td><span class="badge <?=$c['status']==='completed'?'badge-done':($c['status']==='cancelled'?'badge-cancelled':'badge-active')?>"><?=$c['status']==='completed'?'সম্পন্ন':($c['status']==='cancelled'?'বাতিল':'নির্ধারিত')?></span></td>
              <td><a href="../api/classes.php?action=delete&id=<?=$c['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PAYMENTS -->
    <div id="p-payments" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>💳 পেমেন্ট তালিকা</h2></div>
        <div style="padding:12px 16px 0;"><div class="search-wrap"><span class="search-icon">🔍</span><input type="text" placeholder="ছাত্রের নাম বা TxnID..." oninput="liveSearch(this,'tPay','nPay')"></div></div>
        <div class="table-wrap"><div class="no-result" id="nPay">😕 পাওয়া যায়নি</div>
          <table id="tPay">
            <thead><tr><th>তারিখ</th><th>ছাত্র</th><th>পরিমাণ</th><th>মাধ্যম</th><th>TxnID</th><th>অবস্থা</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($payments as $p): ?>
            <tr>
              <td class="text-sm text-muted"><?=date('d M',strtotime($p['created_at']))?></td>
              <td><?=htmlspecialchars($p['student_name'])?></td>
              <td><strong>৳<?=number_format($p['amount'])?></strong></td>
              <td><?=$p['method']==='bkash'?'📱 bKash':'💜 Nagad'?></td>
              <td style="font-family:monospace;font-size:.78rem;"><?=htmlspecialchars($p['txn_id'])?></td>
              <td><span class="badge <?=$p['status']==='confirmed'?'badge-active':($p['status']==='rejected'?'badge-cancelled':'badge-pending')?>"><?=$p['status']==='confirmed'?'✅ কনফার্ম':($p['status']==='rejected'?'❌ বাতিল':'⏳ অপেক্ষমাণ')?></span></td>
              <td>
                <?php if($p['status']==='pending'): ?>
                <a href="../api/payments.php?action=confirm&id=<?=$p['id']?>" class="btn btn-primary btn-sm" onclick="return confirm('কনফার্ম করবেন?')">✅</a>
                <a href="../api/payments.php?action=reject&id=<?=$p['id']?>"  class="btn btn-danger  btn-sm" onclick="return confirm('বাতিল করবেন?')">❌</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- LEADS -->
    <div id="p-leads" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>📋 লিড তালিকা</h2></div>
        <div style="padding:12px 16px 0;"><div class="search-wrap"><span class="search-icon">🔍</span><input type="text" placeholder="নাম, ফোন বা কোর্স..." oninput="liveSearch(this,'tLead','nLead')"></div></div>
        <div class="table-wrap"><div class="no-result" id="nLead">😕 পাওয়া যায়নি</div>
          <table id="tLead">
            <thead><tr><th>নাম</th><th>ফোন</th><th>কোর্স</th><th>তারিখ</th><th>স্ট্যাটাস</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($leads as $l): $wp=preg_replace('/^0/','88',$l['phone']);
              $msg=urlencode("আস-সালামু আলাইকুম {$l['name']}! {$__sname} থেকে ভর্তির বিষয়ে কথা বলতে চাই।"); ?>
            <tr>
              <td><strong><?=htmlspecialchars($l['name'])?></strong></td>
              <td><?=htmlspecialchars($l['phone'])?></td>
              <td class="text-sm text-muted"><?=htmlspecialchars($l['course']??'')?></td>
              <td class="text-sm text-muted"><?=date('d M',strtotime($l['created_at']))?></td>
              <td>
                <form action="../api/leads.php" method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?=$l['id']?>">
                  <select name="status" onchange="this.form.submit()" class="text-sm" style="padding:4px 8px;border-radius:6px;border:1.5px solid var(--border);font-size:.78rem;">
                    <?php foreach(['new'=>'নতুন','contacted'=>'যোগাযোগ','enrolled'=>'ভর্তি','cancelled'=>'বাতিল'] as $v=>$lbl): ?>
                    <option value="<?=$v?>" <?=$l['status']===$v?'selected':''?>><?=$lbl?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><a href="https://wa.me/<?=$wp?>?text=<?=$msg?>" target="_blank" class="btn btn-wa btn-sm">📱 WA</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SETTINGS -->
    <div id="p-settings" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>⚙️ সিস্টেম সেটিংস</h2></div>
        <div class="card-body">
          <form action="" method="POST">
            <input type="hidden" name="save_settings" value="1">
            <div class="form-grid">
              <div class="form-group"><label>সাইটের নাম</label><input type="text" name="site_name" value="<?=htmlspecialchars($cfg['site_name']??'')?>"></div>
              <div class="form-group"><label>Meta Pixel ID</label><input type="text" name="meta_pixel_id" value="<?=htmlspecialchars($cfg['meta_pixel_id']??'')?>" placeholder="123456789"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>Telegram Bot Token</label><input type="text" name="telegram_bot_token" value="<?=htmlspecialchars($cfg['telegram_bot_token']??'')?>" placeholder="123456:ABC..."></div>
              <div class="form-group"><label>Telegram Chat ID</label><input type="text" name="telegram_chat_id" value="<?=htmlspecialchars($cfg['telegram_chat_id']??'')?>" placeholder="-100123456789"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>WhatsApp নম্বর</label><input type="text" name="whatsapp_number" value="<?=htmlspecialchars($cfg['whatsapp_number']??'')?>" placeholder="01XXXXXXXXX"></div>
              <div class="form-group"><label>bKash নম্বর</label><input type="text" name="bkash_number" value="<?=htmlspecialchars($cfg['bkash_number']??'')?>" placeholder="01XXXXXXXXX"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>Nagad নম্বর</label><input type="text" name="nagad_number" value="<?=htmlspecialchars($cfg['nagad_number']??'')?>" placeholder="01XXXXXXXXX"></div>
              <div class="form-group"><label>Admin Email</label><input type="email" name="admin_email" value="<?=htmlspecialchars($cfg['admin_email']??'')?>"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>Google Meet Default Link</label><input type="text" name="meet_default_link" value="<?=htmlspecialchars($cfg['meet_default_link']??'')?>" placeholder="meet.google.com/xxx"></div>
              <div class="form-group"><label>Zoom Default Link</label><input type="text" name="zoom_default_link" value="<?=htmlspecialchars($cfg['zoom_default_link']??'')?>" placeholder="zoom.us/j/xxx"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>Facebook Page</label><input type="text" name="facebook_page" value="<?=htmlspecialchars($cfg['facebook_page']??'')?>"></div>
              <div class="form-group"><label>YouTube Channel</label><input type="text" name="youtube_channel" value="<?=htmlspecialchars($cfg['youtube_channel']??'')?>"></div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <button type="submit" class="btn btn-primary">💾 সেটিংস সেভ করুন</button>
              <a href="../api/test_telegram.php" target="_blank" class="btn btn-outline">📱 Telegram টেস্ট</a>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /main -->
</div><!-- /app -->

<!-- BOTTOM NAV -->
<div class="bottom-nav"><div class="bottom-nav-inner">
  <button class="bottom-nav-item active" id="bn-0" onclick="switchTab('dashboard',null,0)"><span class="b-icon">📊</span>হোম</button>
  <button class="bottom-nav-item" id="bn-1" onclick="switchTab('students',null,1)"><span class="b-icon">👨‍🎓</span>ছাত্র</button>
  <button class="bottom-nav-item" id="bn-2" onclick="switchTab('classes',null,2)"><span class="b-icon">📅</span>ক্লাস</button>
  <button class="bottom-nav-item" id="bn-3" onclick="switchTab('payments',null,3)"><span class="b-icon">💳</span>পেমেন্ট</button>
  <button class="bottom-nav-item" id="bn-4" onclick="switchTab('settings',null,4)"><span class="b-icon">⚙️</span>সেটিংস</button>
</div></div>

<!-- ADD STUDENT MODAL -->
<div class="modal-overlay" id="addStudentModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3>👨‍🎓 নতুন ছাত্র যোগ করুন</h3><button class="modal-close" onclick="closeModal('addStudentModal')">✕</button></div>
    <div class="modal-body">
      <form action="../api/users.php" method="POST">
        <input type="hidden" name="action" value="add_student">
        <div class="form-group"><label>পূর্ণ নাম *</label><input type="text" name="name" required placeholder="ছাত্রের নাম"></div>
        <div class="form-grid">
          <div class="form-group"><label>ইমেইল *</label><input type="email" name="email" required placeholder="email@example.com"></div>
          <div class="form-group"><label>WhatsApp *</label><input type="tel" name="phone" required placeholder="01XXXXXXXXX"></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label>পাসওয়ার্ড *</label><input type="password" name="password" required placeholder="কমপক্ষে ৬ অক্ষর"></div>
          <div class="form-group"><label>প্যাকেজ</label>
            <select name="package"><option value="basic">বেসিক ৳২,০০০</option><option value="standard" selected>স্ট্যান্ডার্ড ৳৩,২০০</option><option value="premium">প্রিমিয়াম ৳৩,৮০০</option></select>
          </div>
        </div>
        <div class="form-group"><label>গার্ডিয়ান নম্বর</label><input type="tel" name="guardian_phone" placeholder="01XXXXXXXXX"></div>
        <button type="submit" class="btn btn-primary btn-full">✅ ছাত্র যোগ করুন</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD TEACHER MODAL -->
<div class="modal-overlay" id="addTeacherModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3>👨‍🏫 নতুন শিক্ষক যোগ করুন</h3><button class="modal-close" onclick="closeModal('addTeacherModal')">✕</button></div>
    <div class="modal-body">
      <form action="../api/users.php" method="POST">
        <input type="hidden" name="action" value="add_teacher">
        <div class="form-group"><label>পূর্ণ নাম *</label><input type="text" name="name" required placeholder="শিক্ষকের নাম"></div>
        <div class="form-grid">
          <div class="form-group"><label>ইমেইল *</label><input type="email" name="email" required></div>
          <div class="form-group"><label>ফোন *</label><input type="tel" name="phone" required placeholder="01XXXXXXXXX"></div>
        </div>
        <div class="form-group"><label>পাসওয়ার্ড *</label><input type="password" name="password" required placeholder="কমপক্ষে ৬ অক্ষর"></div>
        <div class="form-group"><label>পরিচিতি / Bio</label><textarea name="bio" placeholder="যেমন: তাজওয়িদ বিশেষজ্ঞ, মিশর থেকে পাশ করা" style="min-height:70px;"></textarea></div>
        <button type="submit" class="btn btn-primary btn-full">✅ শিক্ষক যোগ করুন</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD CLASS MODAL -->
<div class="modal-overlay" id="addClassModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3>📅 নতুন ক্লাস তৈরি করুন</h3><button class="modal-close" onclick="closeModal('addClassModal')">✕</button></div>
    <div class="modal-body">
      <form action="../api/classes.php" method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ক্লাসের শিরোনাম *</label><input type="text" name="title" required placeholder="যেমন: তাজওয়িদ ক্লাস"></div>
        <div class="form-grid">
          <div class="form-group"><label>শিক্ষক *</label>
            <select name="teacher_id" required>
              <option value="">— বেছে নিন —</option>
              <?php foreach($teachers as $t): ?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>ধরন</label>
            <select name="class_type"><option value="group">গ্রুপ</option><option value="individual">একক</option></select>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label>তারিখ *</label><input type="date" name="class_date" required value="<?=date('Y-m-d')?>"></div>
          <div class="form-group"><label>সময় *</label><input type="time" name="class_time" required value="10:00"></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label>সময়কাল (মিনিট)</label><input type="number" name="duration" value="45" min="15" max="120"></div>
          <div class="form-group"><label>Platform</label>
            <select name="platform" onchange="toggleLinks(this.value)">
              <option value="google_meet">Google Meet</option>
              <option value="zoom">Zoom</option>
            </select>
          </div>
        </div>
        <div class="form-group" id="meetRow"><label>Meet Link</label><input type="url" name="meet_link" placeholder="meet.google.com/xxx" value="<?=htmlspecialchars($cfg['meet_default_link']??'')?>"></div>
        <div class="form-group" id="zoomRow" style="display:none;"><label>Zoom Link</label><input type="url" name="zoom_link" placeholder="zoom.us/j/xxx" value="<?=htmlspecialchars($cfg['zoom_default_link']??'')?>"></div>
        <div class="form-group"><label>ছাত্র যোগ করুন</label>
          <div style="max-height:160px;overflow-y:auto;border:1.5px solid var(--border);border-radius:var(--r-sm);padding:8px;">
            <?php foreach($students as $s): ?>
            <label style="display:flex;align-items:center;gap:8px;padding:5px;cursor:pointer;font-size:.85rem;">
              <input type="checkbox" name="student_ids[]" value="<?=$s['id']?>" style="width:auto;">
              <?=htmlspecialchars($s['name'])?> <span style="color:var(--muted);font-size:.75rem;">(<?=pkg_name($s['package']??'basic')?>)</span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">✅ ক্লাস তৈরি করুন</button>
      </form>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script>
const TITLES={dashboard:'ড্যাশবোর্ড',students:'ছাত্র',teachers:'শিক্ষক',classes:'ক্লাস',payments:'পেমেন্ট',leads:'লিড',settings:'সেটিংস'};

function switchTab(tab, navEl, bnIdx) {
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  const el=document.getElementById('p-'+tab);
  if(el) el.classList.add('active');
  document.getElementById('pt').textContent=TITLES[tab]||tab;
  document.querySelectorAll('.nav-link').forEach(n=>n.classList.remove('active'));
  if(navEl) navEl.classList.add('active');
  document.querySelectorAll('.bottom-nav-item').forEach(b=>b.classList.remove('active'));
  if(bnIdx!==undefined){const bn=document.getElementById('bn-'+bnIdx);if(bn)bn.classList.add('active');}
  closeSidebar();
}

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('active');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('active');}
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));

function toggleLinks(v){
  document.getElementById('meetRow').style.display=v==='google_meet'?'block':'none';
  document.getElementById('zoomRow').style.display=v==='zoom'?'block':'none';
}

<?php if($tab!=='dashboard'): ?>
switchTab('<?=$tab?>');
<?php endif; ?>

// ── LIVE SEARCH ──
function liveSearch(inp, tblId, noId) {
  const q   = inp.value.toLowerCase().trim();
  const tbl = document.getElementById(tblId);
  const no  = document.getElementById(noId);
  if (!tbl) return;
  let vis = 0;
  tbl.querySelectorAll('tbody tr').forEach(row => {
    const match = !q || row.innerText.toLowerCase().includes(q);
    row.style.display = match ? '' : 'none';
    if (match) vis++;
  });
  if (no) no.style.display = (vis === 0 && q) ? 'block' : 'none';
}
</script>
</body>
</html>
