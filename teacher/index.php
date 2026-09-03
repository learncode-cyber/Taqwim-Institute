<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['teacher']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$wa_num  = get_setting('whatsapp_number');
$bkash   = get_setting('bkash_number');
$nagad   = get_setting('nagad_number');
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
$report_wa = $_SESSION['report_wa'] ?? ''; unset($_SESSION['report_wa']);
$today   = date('Y-m-d');

$cls_stmt = $pdo->prepare("SELECT c.*,(SELECT COUNT(*) FROM class_students WHERE class_id=c.id) AS std_count FROM classes c WHERE c.teacher_id=? ORDER BY c.class_date DESC,c.class_time ASC");
$cls_stmt->execute([$user['id']]);
$all_cls = $cls_stmt->fetchAll();

$today_cls = array_filter($all_cls,fn($c)=>$c['class_date']===$today);
$upcoming  = array_filter($all_cls,fn($c)=>$c['class_date']>$today&&$c['status']==='scheduled');
$done_cls  = array_filter($all_cls,fn($c)=>$c['status']==='completed');
$sched_cls = array_filter($all_cls,fn($c)=>$c['status']==='scheduled');

$std_stmt = $pdo->prepare("SELECT DISTINCT u.* FROM users u JOIN class_students cs ON cs.student_id=u.id JOIN classes c ON c.id=cs.class_id WHERE c.teacher_id=? ORDER BY u.name");
$std_stmt->execute([$user['id']]);
$students = $std_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>শিক্ষক ড্যাশবোর্ড — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.att-list{display:flex;flex-direction:column;gap:8px;margin-bottom:12px;}
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
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>শিক্ষক পোর্টাল</small></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">মেনু</div>
    <a class="nav-link active" onclick="sw('home',this,0)"><span class="nav-icon">🏠</span>ড্যাশবোর্ড</a>
    <a class="nav-link" onclick="sw('classes',this,1)"><span class="nav-icon">📅</span>আমার ক্লাস</a>
    <a class="nav-link" onclick="sw('students',this,2)"><span class="nav-icon">👨‍🎓</span>আমার ছাত্ররা</a>
    <a class="nav-link" onclick="sw('attendance',this,3)"><span class="nav-icon">✅</span>অ্যাটেন্ডেন্স</a>
    <a class="nav-link" onclick="sw('reports',this,4)"><span class="nav-icon">📝</span>রিপোর্ট</a>
    <a class="nav-link" onclick="sw('notify',this,4)"><span class="nav-icon">📢</span>WhatsApp নোটিস</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row"><div class="user-av"><?= mb_substr($user['name'],0,1) ?></div><div class="user-info"><strong><?= htmlspecialchars($user['name']) ?></strong><span>শিক্ষক</span></div></div>
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
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span><span style="font-size:.75rem;color:var(--muted);"><?= date('d M Y') ?></span></div>
  </div>

  <div class="page-body">
    <?php if($flash):    ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err):?><div class="alert alert-danger  mb-12">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>
    <?php if($report_wa):?>
    <div class="alert alert-success mb-12" style="flex-wrap:wrap;gap:10px;">
      ✅ রিপোর্ট সেভ হয়েছে।
      <a href="<?= htmlspecialchars($report_wa) ?>" target="_blank" class="btn btn-wa btn-sm" style="margin-left:auto;">📱 গার্ডিয়ানকে WA পাঠান</a>
    </div>
    <?php endif; ?>

    <!-- HOME -->
    <div id="p-home" class="tab-pane active">
      <div style="background:linear-gradient(135deg,var(--sb-bg),var(--p700));border-radius:var(--r-lg);padding:18px;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--gold);color:var(--sb-bg);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;flex-shrink:0;"><?= mb_substr($user['name'],0,1) ?></div>
        <div>
          <div style="font-size:1rem;font-weight:700;">আস-সালামু আলাইকুম, <?= htmlspecialchars($user['name']) ?>!</div>
          <div style="font-size:.8rem;color:rgba(255,255,255,.6);"><?= htmlspecialchars($user['bio']??'শিক্ষক') ?></div>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat"><div class="stat-label">মোট ক্লাস</div><div class="stat-value"><?= count($all_cls) ?></div></div>
        <div class="stat gold"><div class="stat-label">আজকের ক্লাস</div><div class="stat-value"><?= count($today_cls) ?></div></div>
        <div class="stat info"><div class="stat-label">সম্পন্ন</div><div class="stat-value"><?= count($done_cls) ?></div></div>
        <div class="stat danger"><div class="stat-label">মোট ছাত্র</div><div class="stat-value"><?= count($students) ?></div></div>
      </div>
      <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:12px;">📅 আজকের ক্লাস</div>
      <?php if(empty($today_cls)): ?>
        <div class="empty" style="padding:28px;background:white;border-radius:var(--r-lg);"><span class="empty-icon">☀️</span><p>আজ কোনো ক্লাস নেই</p></div>
      <?php else: foreach($today_cls as $c):
        $link=$c['platform']==='zoom'?$c['zoom_link']:$c['meet_link']; ?>
        <div class="class-card today">
          <div class="class-top">
            <div class="class-time"><div class="t"><?= $c['class_time'] ?></div><div class="d"><?= $c['duration'] ?>মি.</div></div>
            <div class="class-sep"></div>
            <div class="class-info"><h3><?= htmlspecialchars($c['title']) ?></h3><div class="class-meta"><span>👥 <?= $c['std_count'] ?> জন</span><span><?= $c['platform']==='zoom'?'🎥 Zoom':'📹 Meet' ?></span></div></div>
          </div>
          <div class="class-btns">
            <?php if($link): ?><a href="<?= htmlspecialchars($link) ?>" target="_blank" class="btn <?= $c['platform']==='zoom'?'btn-zoom':'btn-meet' ?> btn-sm"><?= $c['platform']==='zoom'?'🎥 Zoom শুরু':'📹 Meet শুরু' ?></a><?php endif; ?>
            <button class="btn btn-outline btn-sm" onclick="quickAtt('<?= $c['id'] ?>')">✅ অ্যাটেন্ডেন্স</button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- CLASSES -->
    <div id="p-classes" class="tab-pane">
      <?php if(empty($all_cls)): ?>
        <div class="empty"><span class="empty-icon">📅</span><p>কোনো ক্লাস নেই</p></div>
      <?php else: foreach($all_cls as $c):
        $type=$c['status']==='completed'?'done':($c['class_date']===$today?'today':'upcoming');
        $link=$c['platform']==='zoom'?$c['zoom_link']:$c['meet_link']; ?>
        <div class="class-card <?= $type ?>">
          <div class="class-top">
            <div class="class-time"><div class="t"><?= $c['class_time'] ?></div><div class="d"><?= date('d M',strtotime($c['class_date'])) ?></div></div>
            <div class="class-sep"></div>
            <div class="class-info">
              <h3><?= htmlspecialchars($c['title']) ?></h3>
              <div class="class-meta">
                <span>👥 <?= $c['std_count'] ?> জন</span>
                <span class="badge <?= $c['status']==='completed'?'badge-done':'badge-active' ?>"><?= $c['status']==='completed'?'সম্পন্ন':'নির্ধারিত' ?></span>
              </div>
            </div>
          </div>
          <?php if($c['status']!=='completed' && $link): ?>
          <div class="class-btns">
            <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="btn <?= $c['platform']==='zoom'?'btn-zoom':'btn-meet' ?> btn-sm">যোগ দিন</a>
            <button class="btn btn-outline btn-sm" onclick="quickAtt('<?= $c['id'] ?>')">✅ অ্যাটেন্ডেন্স</button>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- STUDENTS -->
    <div id="p-students" class="tab-pane">
      <?php if(empty($students)): ?>
        <div class="empty"><span class="empty-icon">👨‍🎓</span><p>এখনো ছাত্র নেই</p></div>
      <?php else: foreach($students as $s):
        $wp=preg_replace('/^0/','88',$s['phone']);
        $msg=urlencode("আস-সালামু আলাইকুম! {$__sname} থেকে {$user['name']} বলছি।"); ?>
        <div class="card mb-12">
          <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="width:42px;height:42px;border-radius:50%;background:var(--p600);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;"><?= mb_substr($s['name'],0,1) ?></div>
            <div style="flex:1;min-width:100px;">
              <div style="font-weight:700;color:var(--ink);font-size:.9rem;"><?= htmlspecialchars($s['name']) ?></div>
              <div style="font-size:.75rem;color:var(--muted);">📱 <?= htmlspecialchars($s['phone']) ?></div>
            </div>
            <span class="badge badge-<?= $s['package']??'basic' ?>"><?= pkg_name($s['package']??'basic') ?></span>
            <a href="https://wa.me/<?= $wp ?>?text=<?= $msg ?>" target="_blank" class="btn btn-wa btn-sm">📱 WA</a>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- ATTENDANCE -->
    <div id="p-attendance" class="tab-pane">
      <div class="card mb-16">
        <div class="card-head"><h2>✅ অ্যাটেন্ডেন্স মার্ক করুন</h2></div>
        <div class="card-body">
          <div class="form-group">
            <label>ক্লাস বেছে নিন</label>
            <select id="attSel" onchange="loadAtt()">
              <option value="">— ক্লাস বেছে নিন —</option>
              <?php foreach($sched_cls as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?> — <?= date('d M',strtotime($c['class_date'])) ?> <?= $c['class_time'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="attList" class="att-list"></div>
          <div id="attActions" style="display:none;margin-top:12px;">
            <textarea id="attNotes" placeholder="ক্লাস নোট..." style="width:100%;min-height:70px;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-bn);font-size:.875rem;outline:none;margin-bottom:10px;"></textarea>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <button class="btn btn-primary" onclick="saveAtt()">✅ সেভ করুন</button>
              <button class="btn btn-ghost" onclick="markAll(true)">সবাই উপস্থিত</button>
              <button class="btn btn-ghost" onclick="markAll(false)">সবাই অনুপস্থিত</button>
            </div>
          </div>
        </div>
      </div>
      <!-- History -->
      <div class="card">
        <div class="card-head"><h2>📋 অ্যাটেন্ডেন্স ইতিহাস</h2></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>তারিখ</th><th>ক্লাস</th><th>উপস্থিত</th><th>অনুপস্থিত</th></tr></thead>
            <tbody>
              <?php foreach($done_cls as $c):
                $att=$pdo->prepare("SELECT status,COUNT(*) as n FROM attendance WHERE class_id=? GROUP BY status");
                $att->execute([$c['id']]); $ar=$att->fetchAll(PDO::FETCH_KEY_PAIR); ?>
              <tr>
                <td class="text-sm text-muted"><?= date('d M',strtotime($c['class_date'])) ?></td>
                <td class="truncate" style="max-width:120px;"><?= htmlspecialchars($c['title']) ?></td>
                <td style="color:var(--p600);font-weight:700;"><?= $ar['present']??0 ?></td>
                <td style="color:var(--danger);font-weight:700;"><?= $ar['absent']??0 ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- REPORTS -->
    <div id="p-reports" class="tab-pane">
      <div class="card">
        <div class="card-head"><h2>📝 নতুন রিপোর্ট লিখুন</h2></div>
        <div class="card-body">
          <form action="../api/reports.php" method="POST">
            <div class="form-grid">
              <div class="form-group"><label>ছাত্র *</label>
                <select name="student_id" required>
                  <option value="">— বেছে নিন —</option>
                  <?php foreach($students as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>ধরন</label>
                <select name="report_type"><option value="weekly">সাপ্তাহিক</option><option value="monthly">মাসিক</option><option value="special">বিশেষ</option></select>
              </div>
            </div>
            <div class="form-group"><label>তেলাওয়াত মান</label>
              <select name="tilawat_grade">
                <option value="excellent">চমৎকার ⭐⭐⭐⭐⭐</option>
                <option value="good">ভালো ⭐⭐⭐⭐</option>
                <option value="average">মাঝারি ⭐⭐⭐</option>
                <option value="needs_improvement">উন্নতি প্রয়োজন ⭐⭐</option>
              </select>
            </div>
            <div class="form-group"><label>বিবরণ *</label><textarea name="content" required placeholder="ছাত্রের অগ্রগতি, দুর্বলতা ও পরামর্শ..."></textarea></div>
            <div class="form-group"><label>হোমওয়ার্ক</label><input type="text" name="homework" placeholder="যেমন: সুরা ইখলাস ৫ বার পড়বে"></div>
            <button type="submit" class="btn btn-primary">📝 রিপোর্ট সেভ করুন</button>
          </form>
        </div>
      </div>
    </div>

    <!-- NOTIFY -->
    <div id="p-notify" class="tab-pane">
      <div class="card mb-16">
        <div class="card-head"><h2>📅 ক্লাস রিমাইন্ডার</h2></div>
        <div class="card-body">
          <div class="alert alert-info" style="margin-bottom:14px;">📱 বাটন ক্লিক → WhatsApp খুলবে রেডি মেসেজ সহ</div>
          <?php foreach($sched_cls as $c):
            $std_list=$pdo->prepare("SELECT u.name,u.phone FROM users u JOIN class_students cs ON cs.student_id=u.id WHERE cs.class_id=?");
            $std_list->execute([$c['id']]); $stds=$std_list->fetchAll();
            $link=$c['platform']==='zoom'?$c['zoom_link']:$c['meet_link']; ?>
            <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
              <div style="font-size:.85rem;font-weight:700;color:var(--ink);margin-bottom:8px;">📅 <?=htmlspecialchars($c['title'])?> — <?=date('d M',strtotime($c['class_date']))?> <?=$c['class_time']?></div>
              <div style="display:flex;flex-wrap:wrap;gap:6px;">
                <?php foreach($stds as $s):
                  $wp=preg_replace('/^0/','88',$s['phone']);
                  $msg=urlencode("📚 *ক্লাস রিমাইন্ডার*\n\nআস-সালামু আলাইকুম {$s['name']}!\n\n\"{$c['title']}\" আজ {$c['class_date']} {$c['class_time']}-এ।".($link?"\n🔗 লিংক: {$link}":'')."\n\n— {$user['name']}, {$__sname}"); ?>
                  <a href="https://wa.me/<?=$wp?>?text=<?=$msg?>" target="_blank" class="btn btn-primary btn-sm">📱 <?=htmlspecialchars($s['name'])?></a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h2>💰 বেতন রিমাইন্ডার</h2></div>
        <div class="card-body">
          <div style="display:flex;flex-wrap:wrap;gap:7px;">
            <?php foreach($students as $s):
              $wp=preg_replace('/^0/','88',$s['phone']);
              $msg=urlencode("💰 *বেতন রিমাইন্ডার — {$__sname}*\n\nআস-সালামু আলাইকুম {$s['name']} এর অভিভাবক,\n\nএই মাসের বেতন পরিশোধ করুন।".($bkash?"\nbKash: {$bkash}":'').($nagad?"\nNagad: {$nagad}":'')."\n\nজাযাকাল্লাহু খাইরান।"); ?>
              <a href="https://wa.me/<?=$wp?>?text=<?=$msg?>" target="_blank" class="btn btn-gold btn-sm">💰 <?=htmlspecialchars($s['name'])?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<!-- BOTTOM NAV -->
<div class="bottom-nav"><div class="bottom-nav-inner">
  <button class="bottom-nav-item active" id="bn-0" onclick="sw('home',null,0)"><span class="b-icon">🏠</span>হোম</button>
  <button class="bottom-nav-item" id="bn-1" onclick="sw('classes',null,1)"><span class="b-icon">📅</span>ক্লাস</button>
  <button class="bottom-nav-item" id="bn-2" onclick="sw('attendance',null,2)"><span class="b-icon">✅</span>অ্যাটেন্ডেন্স</button>
  <button class="bottom-nav-item" id="bn-3" onclick="sw('reports',null,3)"><span class="b-icon">📝</span>রিপোর্ট</button>
  <button class="bottom-nav-item" id="bn-4" onclick="sw('notify',null,4)"><span class="b-icon">📢</span>নোটিস</button>
</div></div>
<div id="toast-container"></div>

<script>
const TITLES={home:'ড্যাশবোর্ড',classes:'আমার ক্লাস',students:'আমার ছাত্ররা',attendance:'অ্যাটেন্ডেন্স',reports:'রিপোর্ট',notify:'WhatsApp নোটিস'};
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

// Attendance
let attData={};
function quickAtt(cid){sw('attendance',document.querySelectorAll('.nav-link')[3],2);document.getElementById('attSel').value=cid;loadAtt();}
async function loadAtt(){
  const cid=document.getElementById('attSel').value;
  const list=document.getElementById('attList');
  const actions=document.getElementById('attActions');
  if(!cid){list.innerHTML='';actions.style.display='none';return;}
  attData={};
  const r=await fetch('../api/get_class_students.php?class_id='+cid);
  const d=await r.json();
  list.innerHTML=d.students.map(s=>`
    <div class="student-chip" data-sid="${s.id}">
      <div class="chip-av" data-id="${s.id}">${s.name?s.name[0]:'?'}</div>
      <div class="chip-info"><strong>${s.name}</strong><span>${s.phone}</span></div>
      <div class="att-toggle">
        <button class="att-btn att-present ${s.att_status==='present'?'on':''}" onclick="setA('${s.id}',true,this)">উপস্থিত</button>
        <button class="att-btn att-absent  ${s.att_status==='absent'?'on':''}"  onclick="setA('${s.id}',false,this)">অনুপস্থিত</button>
      </div>
    </div>`).join('');
  // pre-fill existing
  d.students.forEach(s=>{if(s.att_status)attData[String(s.id)]=s.att_status==='present';});
  actions.style.display='block';
}
function setA(id,present,btn){
  attData[String(id)]=present;
  const wrap=btn.closest('.att-toggle');
  wrap.querySelectorAll('.att-present').forEach(b=>b.classList.toggle('on',present));
  wrap.querySelectorAll('.att-absent').forEach(b=>b.classList.toggle('on',!present));
}
function markAll(p){
  document.querySelectorAll('.student-chip').forEach(chip=>{
    const id=chip.dataset.sid;
    attData[String(id)]=p;
    chip.querySelectorAll('.att-present').forEach(b=>b.classList.toggle('on',p));
    chip.querySelectorAll('.att-absent').forEach(b=>b.classList.toggle('on',!p));
  });
}
async function saveAtt(){
  const cid=document.getElementById('attSel').value;
  const notes=document.getElementById('attNotes').value;
  const present=Object.entries(attData).filter(([k,v])=>v).map(([k])=>k);
  const fd=new FormData();
  fd.append('action','attendance');fd.append('class_id',cid);fd.append('notes',notes);
  present.forEach(id=>fd.append('present[]',id));
  const r=await fetch('../api/classes.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('অ্যাটেন্ডেন্স সেভ হয়েছে ✅','success');document.getElementById('attSel').value='';document.getElementById('attList').innerHTML='';document.getElementById('attActions').style.display='none';}
  else toast('সমস্যা হয়েছে','danger');
}
function toast(msg,type='success'){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
