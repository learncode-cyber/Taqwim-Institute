<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['admin']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$flash   = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$today   = date('Y-m-d');
$now     = date('Y-m-d H:i:s');
$wa_num  = get_setting('whatsapp_number');

// ── Data ──
try {
    $leads_all = $pdo->query("SELECT * FROM leads ORDER BY updated_at DESC, created_at DESC")->fetchAll();
} catch(Exception $e) {
    // Add columns if missing
    try { $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS stage ENUM('new','contacted','demo','enrolled','lost') DEFAULT 'new'"); } catch(Exception $e2) {}
    try { $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch(Exception $e2) {}
    $leads_all = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll();
}

$stages = ['new'=>'নতুন 🆕','contacted'=>'যোগাযোগ 📞','demo'=>'ডেমো 🎯','enrolled'=>'ভর্তি ✅','lost'=>'হারানো ❌'];
$kanban = [];
foreach ($stages as $s=>$_) $kanban[$s] = array_filter($leads_all, fn($l)=>($l['stage']??'new')===$s);

// Followups
try {
    $followups = $pdo->query("SELECT * FROM followups WHERE is_done=0 ORDER BY due_at ASC LIMIT 50")->fetchAll();
} catch(Exception $e) { $followups=[]; }
$overdue      = array_filter($followups, fn($f)=>substr($f['due_at'],0,10)<$today);
$due_today    = array_filter($followups, fn($f)=>substr($f['due_at'],0,10)===$today);
$due_upcoming = array_filter($followups, fn($f)=>substr($f['due_at'],0,10)>$today);

// Analytics
$cur_month = date('Y-m');
$prev_month= date('Y-m', strtotime('-1 month'));
try {
    $r1=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='confirmed' AND month_year=?"); $r1->execute([$cur_month]); $rev_this=$r1->fetchColumn();
    $r2=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='confirmed' AND month_year=?"); $r2->execute([$prev_month]); $rev_last=$r2->fetchColumn();
} catch(Exception $e) { $rev_this=0; $rev_last=0; }

$total_leads    = count($leads_all);
$total_enrolled = count($kanban['enrolled']??[]);
$conv_rate      = $total_leads > 0 ? round($total_enrolled/$total_leads*100) : 0;

// Monthly revenue last 6 months
$rev_6m = [];
for ($i=5; $i>=0; $i--) {
    $m = date('Y-m', strtotime("-{$i} month"));
    $mn= date('M Y', strtotime("-{$i} month"));
    try {
        $r=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='confirmed' AND month_year=?");
        $r->execute([$m]); $rev_6m[]=['month'=>$mn,'amount'=>floatval($r->fetchColumn())];
    } catch(Exception $e) { $rev_6m[]=['month'=>$mn,'amount'=>0]; }
}

// Package breakdown
try {
    $pkg_stats=$pdo->query("SELECT package,COUNT(*) as cnt FROM users WHERE role='student' GROUP BY package")->fetchAll();
    $pkg_map=array_column($pkg_stats,'cnt','package');
} catch(Exception $e) { $pkg_map=[]; }
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>CRM — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.kanban-board{display:flex;gap:12px;overflow-x:auto;padding-bottom:12px;-webkit-overflow-scrolling:touch;}
.kanban-col{flex-shrink:0;width:230px;background:var(--p50);border-radius:var(--r-lg);padding:12px;}
.kanban-col-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.kanban-col-title{font-size:.82rem;font-weight:700;color:var(--ink);}
.kanban-count{background:var(--p600);color:#fff;border-radius:20px;font-size:.68rem;font-weight:700;padding:2px 8px;}
.c-enrolled .kanban-count{background:#16a34a;}
.c-lost .kanban-count{background:var(--danger);}
.kanban-card{background:white;border-radius:var(--r);padding:11px 12px;margin-bottom:8px;box-shadow:var(--shadow-xs);border:1px solid var(--border);cursor:grab;transition:all .15s;}
.kanban-card:hover{box-shadow:var(--shadow-sm);transform:translateY(-1px);}
.kanban-card.dragging{opacity:.4;}
.kc-name{font-size:.85rem;font-weight:700;color:var(--ink);margin-bottom:3px;}
.kc-phone{font-size:.75rem;color:var(--muted);margin-bottom:5px;}
.kc-actions{display:flex;gap:4px;margin-top:8px;}
.kc-btn{flex:1;padding:4px 0;border:1px solid var(--border);border-radius:5px;background:none;font-size:.65rem;font-weight:600;color:var(--muted);cursor:pointer;font-family:var(--font-bn);transition:all .15s;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;}
.kc-btn:hover{background:var(--p50);color:var(--p600);}
.fu-card{background:white;border-radius:var(--r);border:1px solid var(--border);padding:13px 15px;margin-bottom:9px;display:flex;align-items:flex-start;gap:12px;}
.fu-card.overdue{border-left:3px solid var(--danger);}
.fu-card.today-fu{border-left:3px solid var(--gold);}
.fu-card.upcoming-fu{border-left:3px solid var(--p600);}
.fu-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:5px;}
.fu-dot.red{background:var(--danger);}
.fu-dot.gold{background:var(--gold);}
.fu-dot.green{background:var(--p600);}
.fu-info{flex:1;}
.fu-name{font-size:.875rem;font-weight:700;color:var(--ink);}
.fu-note{font-size:.78rem;color:var(--muted);margin-top:2px;}
.fu-time{font-size:.72rem;margin-top:4px;}
.log-entry{display:flex;gap:10px;padding:12px 0;border-bottom:1px solid var(--border);}
.log-entry:last-child{border-bottom:none;}
.log-icon{width:32px;height:32px;border-radius:50%;background:var(--p100);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
.log-note{font-size:.875rem;color:var(--ink);margin-bottom:3px;}
.log-meta{font-size:.72rem;color:var(--muted);}
.bar-chart{display:flex;align-items:flex-end;gap:8px;height:120px;padding-top:10px;}
.bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;}
.bar{width:100%;background:var(--p600);border-radius:4px 4px 0 0;min-height:4px;}
.bar-lbl{font-size:.62rem;color:var(--muted);text-align:center;}
.bar-val{font-size:.65rem;font-weight:700;color:var(--p600);}
.crm-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px;}
.crm-stat{background:white;border-radius:var(--r-lg);border:1px solid var(--border);padding:14px;box-shadow:var(--shadow-xs);}
.crm-stat .num{font-size:1.6rem;font-weight:700;color:var(--ink);}
.crm-stat .lbl{font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-top:3px;}
.crm-stat .trend{font-size:.72rem;margin-top:4px;}
.trend-up{color:#16a34a;} .trend-down{color:var(--danger);}
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
    <div class="logo-mark"><img src="<?= htmlspecialchars($__logo) ?>" alt="" style="width:28px;height:28px;object-fit:contain;"></div>
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>CRM</small></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">CRM</div>
    <a class="nav-link active" onclick="sw('pipeline',this)"><span class="nav-icon">📋</span>Lead Pipeline</a>
    <a class="nav-link" onclick="sw('followups',this)"><span class="nav-icon">📞</span>Follow-up</a>
    <a class="nav-link" onclick="sw('logs',this)"><span class="nav-icon">💬</span>Comm Log</a>
    <a class="nav-link" onclick="sw('analytics',this)"><span class="nav-icon">📊</span>Analytics</a>
    <div class="nav-label" style="margin-top:8px;">লিংক</div>
    <a class="nav-link" href="index.php"><span class="nav-icon">📊</span>Admin Panel</a>
    <a class="nav-link" href="courses.php"><span class="nav-icon">🎓</span>কোর্সসমূহ</a>
    <a class="nav-link" href="coupons.php"><span class="nav-icon">🎟️</span>কুপন</a>
    <a class="nav-link" href="branding.php"><span class="nav-icon">🎨</span>White Label</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row"><div class="user-av"><?= mb_substr($user['name'],0,1) ?></div><div class="user-info"><strong><?= htmlspecialchars($user['name']) ?></strong><span>Admin</span></div></div>
    <a href="../api/auth.php?action=logout" class="btn-logout">লগআউট</a>
  </div>
</aside>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
      <span class="page-title" id="pt">📋 Lead Pipeline</span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span>
      <?php $fu_count=count($due_today)+count($overdue); if($fu_count>0): ?>
      <span class="badge badge-cancelled" style="font-size:.72rem;"><?= $fu_count ?> follow-up বাকি</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash): ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>

    <!-- ═══ PIPELINE ═══ -->
    <div id="p-pipeline" class="tab-pane active">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <p style="font-size:.78rem;color:var(--muted);">কার্ড drag করে stage পরিবর্তন করুন</p>
        <a href="index.php?tab=leads" class="btn btn-outline btn-sm">+ নতুন লিড</a>
      </div>
      <div class="kanban-board" id="kanbanBoard">
        <?php foreach($stages as $stage=>$label): $cards=$kanban[$stage]??[]; ?>
        <div class="kanban-col c-<?= $stage ?>" id="col-<?= $stage ?>" ondragover="event.preventDefault()" ondrop="drop(event,'<?= $stage ?>')">
          <div class="kanban-col-head">
            <span class="kanban-col-title"><?= $label ?></span>
            <span class="kanban-count"><?= count($cards) ?></span>
          </div>
          <?php foreach($cards as $l):
            $wp=preg_replace('/^0/','88',$l['phone']);
            $msg=urlencode("আস-সালামু আলাইকুম {$l['name']}! {$__sname} থেকে যোগাযোগ করছি।");
          ?>
          <div class="kanban-card" draggable="true" id="card-<?= $l['id'] ?>"
               ondragstart="drag(event,<?= $l['id'] ?>)">
            <div class="kc-name"><?= htmlspecialchars($l['name']) ?></div>
            <div class="kc-phone">📱 <?= htmlspecialchars($l['phone']) ?></div>
            <?php if(!empty($l['course'])): ?><div style="font-size:.72rem;color:var(--muted);">📚 <?= htmlspecialchars($l['course']) ?></div><?php endif; ?>
            <div class="kc-actions">
              <a href="https://wa.me/<?=$wp?>?text=<?=$msg?>" target="_blank" class="kc-btn">📱 WA</a>
              <button class="kc-btn" onclick="openLogModal('lead',<?=$l['id']?>,'<?= addslashes($l['name']) ?>')">💬 Log</button>
              <button class="kc-btn" onclick="openFU('lead',<?=$l['id']?>,'<?= addslashes($l['name']) ?>','<?= addslashes($l['phone']) ?>')">📞 FU</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ═══ FOLLOW-UPS ═══ -->
    <div id="p-followups" class="tab-pane">
      <?php if(!empty($overdue)): ?>
      <div class="card mb-16">
        <div class="card-head" style="border-left:3px solid var(--danger);"><h2>⚠️ মেয়াদ শেষ (<?=count($overdue)?>)</h2></div>
        <div class="card-body" style="padding:0 16px;">
          <?php foreach($overdue as $f): $wp=preg_replace('/^0/','88',$f['ref_phone']??''); ?>
          <div class="fu-card overdue">
            <div class="fu-dot red"></div>
            <div class="fu-info">
              <div class="fu-name"><?= htmlspecialchars($f['ref_name']) ?></div>
              <?php if($f['note']): ?><div class="fu-note"><?= htmlspecialchars($f['note']) ?></div><?php endif; ?>
              <div class="fu-time" style="color:var(--danger);">📅 <?= date('d M Y, H:i',strtotime($f['due_at'])) ?></div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
              <?php if($f['ref_phone']): ?><a href="https://wa.me/<?=$wp?>" target="_blank" class="btn btn-wa btn-sm">📱</a><?php endif; ?>
              <button class="btn btn-primary btn-sm" onclick="doneFU(<?=$f['id']?>,this)">✅</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="card mb-16">
        <div class="card-head" style="border-left:3px solid var(--gold);">
          <h2>📅 আজকের Follow-up (<?=count($due_today)?>)</h2>
          <button class="btn btn-primary btn-sm" onclick="openFU('lead',0,'','')">+ যোগ করুন</button>
        </div>
        <div class="card-body" style="padding:0 16px;">
          <?php if(empty($due_today)): ?>
          <div class="empty" style="padding:20px;"><span class="empty-icon">☀️</span><p>আজ কোনো follow-up নেই</p></div>
          <?php else: foreach($due_today as $f): $wp=preg_replace('/^0/','88',$f['ref_phone']??''); ?>
          <div class="fu-card today-fu">
            <div class="fu-dot gold"></div>
            <div class="fu-info">
              <div class="fu-name"><?= htmlspecialchars($f['ref_name']) ?></div>
              <?php if($f['note']): ?><div class="fu-note"><?= htmlspecialchars($f['note']) ?></div><?php endif; ?>
              <div class="fu-time" style="color:var(--gold);">⏰ <?= date('H:i',strtotime($f['due_at'])) ?></div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
              <?php if($f['ref_phone']): ?><a href="https://wa.me/<?=$wp?>" target="_blank" class="btn btn-wa btn-sm">📱</a><?php endif; ?>
              <button class="btn btn-primary btn-sm" onclick="doneFU(<?=$f['id']?>,this)">✅</button>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <?php if(!empty($due_upcoming)): ?>
      <div class="card">
        <div class="card-head" style="border-left:3px solid var(--p600);"><h2>🔜 আসন্ন (<?=count($due_upcoming)?>)</h2></div>
        <div class="card-body" style="padding:0 16px;">
          <?php foreach($due_upcoming as $f): $wp=preg_replace('/^0/','88',$f['ref_phone']??''); ?>
          <div class="fu-card upcoming-fu">
            <div class="fu-dot green"></div>
            <div class="fu-info">
              <div class="fu-name"><?= htmlspecialchars($f['ref_name']) ?></div>
              <?php if($f['note']): ?><div class="fu-note"><?= htmlspecialchars($f['note']) ?></div><?php endif; ?>
              <div class="fu-time" style="color:var(--p600);">📅 <?= date('d M Y, H:i',strtotime($f['due_at'])) ?></div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
              <?php if($f['ref_phone']): ?><a href="https://wa.me/<?=$wp?>" target="_blank" class="btn btn-wa btn-sm">📱</a><?php endif; ?>
              <button class="btn btn-danger btn-sm" onclick="delFU(<?=$f['id']?>,this)">🗑</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══ COMM LOG ═══ -->
    <div id="p-logs" class="tab-pane">
      <div class="card mb-16">
        <div class="card-head"><h2>💬 যোগাযোগ লগ করুন</h2></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group">
              <label>ছাত্র/লিড *</label>
              <select id="logRef">
                <option value="">— বেছে নিন —</option>
                <optgroup label="📋 লিড">
                  <?php foreach($leads_all as $l): ?>
                  <option value="lead|<?=$l['id']?>"><?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['phone']) ?>)</option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="👨‍🎓 ছাত্র">
                  <?php foreach($pdo->query("SELECT id,name,phone FROM users WHERE role='student' ORDER BY name") as $s): ?>
                  <option value="student|<?=$s['id']?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['phone']) ?>)</option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <div class="form-group">
              <label>Channel</label>
              <select id="logChannel">
                <option value="whatsapp">📱 WhatsApp</option>
                <option value="call">📞 Call</option>
                <option value="sms">💬 SMS</option>
                <option value="email">✉️ Email</option>
                <option value="meeting">🤝 Meeting</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Direction</label>
            <div style="display:flex;gap:14px;">
              <label style="display:flex;align-items:center;gap:5px;font-size:.85rem;font-weight:400;"><input type="radio" name="logDir" value="outbound" checked> 📤 আমরা করেছি</label>
              <label style="display:flex;align-items:center;gap:5px;font-size:.85rem;font-weight:400;"><input type="radio" name="logDir" value="inbound"> 📥 তারা করেছে</label>
            </div>
          </div>
          <div class="form-group"><label>নোট *</label><textarea id="logNote" placeholder="কী কথা হয়েছে..." style="min-height:80px;"></textarea></div>
          <button class="btn btn-primary" onclick="addLog()">💬 লগ সেভ করুন</button>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><h2>📜 সাম্প্রতিক লগ</h2></div>
        <div class="card-body" id="recentLogs" style="padding:0 16px;">
          <?php
          try {
            $rlogs=$pdo->query("SELECT cl.*,u.name AS by_name FROM comm_logs cl JOIN users u ON u.id=cl.logged_by ORDER BY cl.logged_at DESC LIMIT 20")->fetchAll();
          } catch(Exception $e){ $rlogs=[]; }
          $ch_icons=['whatsapp'=>'📱','call'=>'📞','sms'=>'💬','email'=>'✉️','meeting'=>'🤝','other'=>'📝'];
          foreach($rlogs as $lg): ?>
          <div class="log-entry">
            <div class="log-icon"><?= $ch_icons[$lg['channel']]??'📝' ?></div>
            <div>
              <div class="log-note"><?= htmlspecialchars($lg['note']) ?></div>
              <div class="log-meta">
                <span class="badge <?=$lg['direction']==='outbound'?'badge-active':'badge-pending'?>"><?=$lg['direction']==='outbound'?'📤 আমরা':'📥 তারা'?></span>
                · <?= htmlspecialchars($lg['by_name']) ?> · <?= date('d M Y H:i',strtotime($lg['logged_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; if(empty($rlogs)): ?>
          <div class="empty" style="padding:24px;"><span class="empty-icon">💬</span><p>কোনো লগ নেই</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══ ANALYTICS ═══ -->
    <div id="p-analytics" class="tab-pane">
      <div class="crm-stats">
        <div class="crm-stat">
          <div class="num">৳<?= number_format($rev_this) ?></div>
          <div class="lbl">এই মাসের আয়</div>
          <?php $diff=$rev_this-$rev_last; ?>
          <div class="trend <?=$diff>=0?'trend-up':'trend-down'?>"><?=$diff>=0?'↑':'↓'?> ৳<?=number_format(abs($diff))?> গত মাসের তুলনায়</div>
        </div>
        <div class="crm-stat"><div class="num"><?=$total_leads?></div><div class="lbl">মোট লিড</div><div class="trend trend-up">↑ <?=count($kanban['new']??[])?> নতুন</div></div>
        <div class="crm-stat"><div class="num"><?=$conv_rate?>%</div><div class="lbl">Conversion Rate</div><div class="trend <?=$conv_rate>=20?'trend-up':'trend-down'?>"><?=$total_enrolled?> ভর্তি / <?=$total_leads?> লিড</div></div>
        <div class="crm-stat"><div class="num"><?=count($overdue)?></div><div class="lbl">Overdue Follow-up</div><div class="trend trend-down"><?=count($due_today)?> আজকের</div></div>
      </div>

      <div class="card mb-16">
        <div class="card-head"><h2>📈 মাসিক আয় (শেষ ৬ মাস)</h2></div>
        <div class="card-body">
          <?php $max_rev=max(array_column($rev_6m,'amount')?:[1]); ?>
          <div class="bar-chart">
            <?php foreach($rev_6m as $r):
              $h=$max_rev>0?max(4,round($r['amount']/$max_rev*100)):4; ?>
            <div class="bar-wrap">
              <div class="bar-val">৳<?=number_format($r['amount']/1000,1)?>k</div>
              <div class="bar" style="height:<?=$h?>%"></div>
              <div class="bar-lbl"><?=substr($r['month'],0,3)?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card mb-16">
        <div class="card-head"><h2>🎯 Pipeline Funnel</h2></div>
        <div class="card-body">
          <?php
          $fc=['new'=>'#9ca3af','contacted'=>'#3b82f6','demo'=>'#f59e0b','enrolled'=>'#16a34a','lost'=>'#ef4444'];
          $tf=max(1,count($leads_all));
          foreach($stages as $s=>$l):
            $cnt=count($kanban[$s]??[]); $pct=round($cnt/$tf*100); ?>
          <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:4px;"><span style="font-weight:600;"><?=$l?></span><span style="color:var(--muted);"><?=$cnt?> (<?=$pct?>%)</span></div>
            <div style="height:24px;background:var(--border);border-radius:6px;overflow:hidden;">
              <div style="height:100%;background:<?=$fc[$s]??'var(--p600)'?>;border-radius:6px;width:<?=$pct?>%;display:flex;align-items:center;padding:0 8px;">
                <?php if($pct>8): ?><span style="font-size:.68rem;color:white;font-weight:700;"><?=$cnt?></span><?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h2>📦 প্যাকেজ বিতরণ</h2></div>
        <div class="card-body">
          <?php
          $pc=['basic'=>'var(--muted)','standard'=>'var(--p600)','premium'=>'var(--gold)'];
          $pl=['basic'=>'বেসিক','standard'=>'স্ট্যান্ডার্ড','premium'=>'প্রিমিয়াম'];
          $ts=array_sum($pkg_map)?:1;
          foreach($pl as $pk=>$pln):
            $cnt=$pkg_map[$pk]??0; $pct=round($cnt/$ts*100); ?>
          <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:4px;"><span style="font-weight:600;color:var(--ink);"><?=$pln?></span><span style="color:var(--muted);"><?=$cnt?> জন (<?=$pct?>%)</span></div>
            <div style="height:12px;background:var(--border);border-radius:6px;overflow:hidden;">
              <div style="height:100%;background:<?=$pc[$pk]?>;border-radius:6px;width:<?=$pct?>%;"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<!-- BOTTOM NAV -->
<div class="bottom-nav"><div class="bottom-nav-inner">
  <button class="bottom-nav-item active" id="bn-0" onclick="sw('pipeline',null,0)"><span class="b-icon">📋</span>Pipeline</button>
  <button class="bottom-nav-item" id="bn-1" onclick="sw('followups',null,1)"><span class="b-icon">📞</span>Follow-up</button>
  <button class="bottom-nav-item" id="bn-2" onclick="sw('logs',null,2)"><span class="b-icon">💬</span>Log</button>
  <button class="bottom-nav-item" id="bn-3" onclick="sw('analytics',null,3)"><span class="b-icon">📊</span>Analytics</button>
</div></div>

<!-- FOLLOW-UP MODAL -->
<div class="modal-overlay" id="fuModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3>📞 Follow-up সেট করুন</h3><button class="modal-close" onclick="closeModal('fuModal')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="fu_type"><input type="hidden" id="fu_ref_id"><input type="hidden" id="fu_ref_phone">
      <div class="form-group"><label>নাম</label><input type="text" id="fu_name" readonly style="background:var(--p50);"></div>
      <div class="form-group"><label>তারিখ ও সময় *</label><input type="datetime-local" id="fu_due" value="<?= date('Y-m-d\TH:i', strtotime('+1 day')) ?>"></div>
      <div class="form-group"><label>নোট</label><textarea id="fu_note" style="min-height:70px;"></textarea></div>
      <button class="btn btn-primary btn-full" onclick="saveFU()">✅ সেভ করুন</button>
    </div>
  </div>
</div>

<!-- LOG MODAL (from kanban) -->
<div class="modal-overlay" id="logModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3 id="logModalTitle">💬 Log</h3><button class="modal-close" onclick="closeModal('logModal')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="lm_type"><input type="hidden" id="lm_ref_id">
      <div id="lm_prev" style="max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--r-sm);padding:10px;margin-bottom:14px;font-size:.82rem;color:var(--muted);">লোড হচ্ছে...</div>
      <div class="form-grid">
        <div class="form-group"><label>Channel</label><select id="lm_ch"><option value="whatsapp">📱 WA</option><option value="call">📞 Call</option><option value="sms">💬 SMS</option><option value="email">✉️ Email</option><option value="meeting">🤝 Meeting</option></select></div>
        <div class="form-group"><label>Direction</label><select id="lm_dir"><option value="outbound">📤 আমরা</option><option value="inbound">📥 তারা</option></select></div>
      </div>
      <div class="form-group"><label>নোট *</label><textarea id="lm_note" style="min-height:70px;"></textarea></div>
      <button class="btn btn-primary btn-full" onclick="saveLogModal()">💬 সেভ করুন</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script>
const TITLES={pipeline:'📋 Lead Pipeline',followups:'📞 Follow-up',logs:'💬 Comm Log',analytics:'📊 Analytics'};
function sw(p,el,bi){
  document.querySelectorAll('.tab-pane').forEach(x=>x.classList.remove('active'));
  document.getElementById('p-'+p).classList.add('active');
  document.getElementById('pt').textContent=TITLES[p]||p;
  document.querySelectorAll('.nav-link').forEach(n=>n.classList.remove('active'));
  if(el)el.classList.add('active');
  document.querySelectorAll('.bottom-nav-item').forEach(b=>b.classList.remove('active'));
  const bn=document.getElementById('bn-'+bi);if(bn)bn.classList.add('active');
  closeSidebar();
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('active');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('active');}
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));

let dragId=null;
function drag(e,id){dragId=id;e.target.classList.add('dragging');}
async function drop(e,stage){
  e.preventDefault();
  if(!dragId)return;
  document.getElementById('card-'+dragId)?.classList.remove('dragging');
  const fd=new FormData();fd.append('action','move_stage');fd.append('id',dragId);fd.append('stage',stage);
  const r=await fetch('../api/crm.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('✅ Stage আপডেট');setTimeout(()=>location.reload(),700);}
  dragId=null;
}

function openFU(type,id,name,phone){
  document.getElementById('fu_type').value=type;
  document.getElementById('fu_ref_id').value=id;
  document.getElementById('fu_name').value=name;
  document.getElementById('fu_ref_phone').value=phone;
  document.getElementById('fu_note').value='';
  openModal('fuModal');
}
async function saveFU(){
  const due=document.getElementById('fu_due').value;
  if(!due){toast('তারিখ দিন','danger');return;}
  const fd=new FormData();
  fd.append('action','add_followup');
  fd.append('type',document.getElementById('fu_type').value);
  fd.append('ref_id',document.getElementById('fu_ref_id').value);
  fd.append('ref_name',document.getElementById('fu_name').value);
  fd.append('ref_phone',document.getElementById('fu_ref_phone').value);
  fd.append('note',document.getElementById('fu_note').value);
  fd.append('due_at',due.replace('T',' ')+':00');
  const r=await fetch('../api/crm.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('✅ Follow-up সেট হয়েছে');closeModal('fuModal');setTimeout(()=>location.reload(),800);}
}
async function doneFU(id,btn){
  const fd=new FormData();fd.append('action','done_followup');fd.append('id',id);
  await fetch('../api/crm.php',{method:'POST',body:fd});
  btn.closest('.fu-card').style.opacity='0';
  setTimeout(()=>btn.closest('.fu-card').remove(),300);
  toast('✅ সম্পন্ন!');
}
async function delFU(id,btn){
  if(!confirm('মুছবেন?'))return;
  await fetch('../api/crm.php?action=del_followup&id='+id);
  btn.closest('.fu-card').remove();
}

let lmType='',lmId=0;
async function openLogModal(type,id,name){
  lmType=type;lmId=id;
  document.getElementById('logModalTitle').textContent='💬 '+name;
  document.getElementById('lm_type').value=type;
  document.getElementById('lm_ref_id').value=id;
  document.getElementById('lm_note').value='';
  document.getElementById('lm_prev').innerHTML='লোড হচ্ছে...';
  openModal('logModal');
  const r=await fetch(`../api/crm.php?action=get_logs&type=${type}&ref_id=${id}`);
  const d=await r.json();
  const icons={whatsapp:'📱',call:'📞',sms:'💬',email:'✉️',meeting:'🤝',other:'📝'};
  if(d.logs&&d.logs.length>0){
    document.getElementById('lm_prev').innerHTML=d.logs.map(l=>`<div style="padding:6px 0;border-bottom:1px solid var(--border);"><span>${icons[l.channel]||'📝'}</span> ${l.note}<br><span style="font-size:.68rem;color:var(--muted);">${l.by_name} · ${l.logged_at}</span></div>`).join('');
  }else{
    document.getElementById('lm_prev').innerHTML='<div style="text-align:center;padding:12px;color:var(--muted);">কোনো লগ নেই</div>';
  }
}
async function saveLogModal(){
  const note=document.getElementById('lm_note').value.trim();
  if(!note){toast('নোট লিখুন','danger');return;}
  const fd=new FormData();
  fd.append('action','add_log');fd.append('type',document.getElementById('lm_type').value);
  fd.append('ref_id',document.getElementById('lm_ref_id').value);
  fd.append('channel',document.getElementById('lm_ch').value);
  fd.append('direction',document.getElementById('lm_dir').value);
  fd.append('note',note);
  const r=await fetch('../api/crm.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('✅ লগ সেভ হয়েছে');closeModal('logModal');}
}

let logRefType='',logRefId=0;
document.getElementById('logRef').onchange=function(){
  const v=this.value;if(!v)return;
  const p=v.split('|');logRefType=p[0];logRefId=parseInt(p[1]);
};
async function addLog(){
  if(!logRefId){toast('ছাত্র/লিড বেছে নিন','danger');return;}
  const note=document.getElementById('logNote').value.trim();
  if(!note){toast('নোট লিখুন','danger');return;}
  const fd=new FormData();
  fd.append('action','add_log');fd.append('type',logRefType);fd.append('ref_id',logRefId);
  fd.append('channel',document.getElementById('logChannel').value);
  fd.append('direction',document.querySelector('input[name=logDir]:checked').value);
  fd.append('note',note);
  const r=await fetch('../api/crm.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){
    document.getElementById('logNote').value='';
    toast('✅ লগ সেভ হয়েছে');
    const icons={whatsapp:'📱',call:'📞',sms:'💬',email:'✉️',meeting:'🤝',other:'📝'};
    const ch=document.getElementById('logChannel').value;
    const el=document.createElement('div');el.className='log-entry';
    el.innerHTML=`<div class="log-icon">${icons[ch]||'📝'}</div><div><div class="log-note">${note}</div><div class="log-meta"><span class="badge badge-active">📤</span> · ${d.logged_by} · ${d.logged_at}</div></div>`;
    document.getElementById('recentLogs').insertBefore(el,document.getElementById('recentLogs').firstChild);
  }
}
function toast(msg,type='success'){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
