<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['student']);
$__brand = get_branding();
$__logo  = !empty($__brand['site_logo']) ? '../assets/img/'.$__brand['site_logo'] : '../assets/img/logo.png';
$__sname = $__brand['site_name']    ?? 'Taqwim Institute';
$pixel_id = get_setting('meta_pixel_id');
$bkash    = get_setting('bkash_number');
$nagad    = get_setting('nagad_number');
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

// ── Enrolled Courses ──
try {
    $enrolled_courses = $pdo->prepare("
        SELECT c.*,cc.name AS cat_name,cc.icon AS cat_icon,ce.enrolled_at,ce.status AS enroll_status,
        (SELECT COUNT(*) FROM course_lessons WHERE course_id=c.id AND is_active=1) AS total_lessons,
        (SELECT COUNT(*) FROM lesson_progress WHERE course_id=c.id AND student_id=? AND is_completed=1) AS completed_lessons
        FROM course_enrollments ce
        JOIN courses c ON c.id=ce.course_id
        JOIN course_categories cc ON cc.id=c.category_id
        WHERE ce.student_id=? AND ce.status='active'
        ORDER BY ce.enrolled_at DESC
    ");
    $enrolled_courses->execute([$user['id'],$user['id']]);
    $enrolled_courses = $enrolled_courses->fetchAll();
} catch(Exception $e) { $enrolled_courses=[]; }

// ── Available Courses (not enrolled) ──
try {
    $enrolled_ids = array_column($enrolled_courses,'id') ?: [0];
    $ids_ph = implode(',', array_fill(0, count($enrolled_ids), '?'));
    $avail_stmt = $pdo->prepare("
        SELECT c.*,cc.name AS cat_name,cc.icon AS cat_icon
        FROM courses c
        JOIN course_categories cc ON cc.id=c.category_id
        WHERE c.is_active=1 AND c.id NOT IN ($ids_ph)
        ORDER BY c.is_featured DESC, c.sort_order LIMIT 6
    ");
    $avail_stmt->execute($enrolled_ids);
    $available_courses = $avail_stmt->fetchAll();
} catch(Exception $e) { $available_courses=[]; }

// Load student's classes
$cls_stmt = $pdo->prepare("
    SELECT c.*, u.name AS teacher_name
    FROM classes c
    JOIN class_students cs ON cs.class_id = c.id
    JOIN users u ON u.id = c.teacher_id
    WHERE cs.student_id = ?
    ORDER BY c.class_date DESC, c.class_time ASC
");
$cls_stmt->execute([$user['id']]);
$all_cls = $cls_stmt->fetchAll();

$today    = date('Y-m-d');
$today_cls= array_filter($all_cls, fn($c) => $c['class_date']===$today && $c['status']!=='completed');
$upcoming = array_filter($all_cls, fn($c) => $c['class_date']>$today   && $c['status']==='scheduled');
$completed= array_filter($all_cls, fn($c) => $c['status']==='completed');

// Attendance
$att_stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id=?");
$att_stmt->execute([$user['id']]);
$att_all  = $att_stmt->fetchAll();
$att_pct  = count($att_all)
    ? round(count(array_filter($att_all, fn($a)=>$a['status']==='present')) / count($att_all) * 100)
    : 0;

// Payments
$pay_stmt = $pdo->prepare("SELECT * FROM payments WHERE student_id=? ORDER BY created_at DESC");
$pay_stmt->execute([$user['id']]);
$all_pays = $pay_stmt->fetchAll();

// Reports
$rep_stmt = $pdo->prepare("
    SELECT r.*, u.name AS teacher_name
    FROM reports r JOIN users u ON u.id=r.teacher_id
    WHERE r.student_id=? ORDER BY r.created_at DESC
");
$rep_stmt->execute([$user['id']]);
$all_reports = $rep_stmt->fetchAll();

$grade_bn = ['excellent'=>'চমৎকার ⭐⭐⭐⭐⭐','good'=>'ভালো ⭐⭐⭐⭐','average'=>'মাঝারি ⭐⭐⭐','needs_improvement'=>'উন্নতি প্রয়োজন ⭐⭐'];
$type_bn  = ['weekly'=>'সাপ্তাহিক','monthly'=>'মাসিক','special'=>'বিশেষ'];

function classCard($c, $type, $userId, $pdo) {
    $link = $c['platform']==='zoom' ? $c['zoom_link'] : $c['meet_link'];
    $att  = null;
    if ($c['status']==='completed') {
        $a = $pdo->prepare("SELECT status FROM attendance WHERE class_id=? AND student_id=?");
        $a->execute([$c['id'], $userId]);
        $att = $a->fetchColumn();
    }
    $date_fmt = date('d M', strtotime($c['class_date']));
    ob_start(); ?>
    <div class="class-card <?= $type ?>">
        <div class="class-top">
            <div class="class-time">
                <div class="t"><?= $c['class_time'] ?></div>
                <div class="d"><?= $date_fmt ?></div>
            </div>
            <div class="class-sep"></div>
            <div class="class-info">
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <div class="class-meta">
                    <span>👨‍🏫 <?= htmlspecialchars($c['teacher_name']) ?></span>
                    <span><?= $c['platform']==='zoom' ? '🎥 Zoom' : '📹 Meet' ?></span>
                    <?php if($att): ?>
                    <span class="badge <?= $att==='present'?'badge-active':'badge-cancelled' ?>">
                        <?= $att==='present' ? '✅ উপস্থিত' : '❌ অনুপস্থিত' ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if($c['status']!=='completed' && $link): ?>
        <div class="class-btns">
            <a href="<?= htmlspecialchars($link) ?>" target="_blank"
               class="btn <?= $c['platform']==='zoom'?'btn-zoom':'btn-meet' ?> btn-sm">
                <?= $c['platform']==='zoom' ? '🎥 Zoom-এ যোগ দিন' : '📹 Meet-এ যোগ দিন' ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>ড্যাশবোর্ড — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<?php if($pixel_id): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?=htmlspecialchars($pixel_id)?>');fbq('track','PageView');</script>
<?php endif; ?>
<style>
.coupon-row{display:flex;gap:8px;}
.coupon-row input{flex:1;}
.coupon-msg{padding:10px 13px;border-radius:var(--r-sm);font-size:.875rem;font-weight:600;margin-bottom:12px;display:none;}
.coupon-msg.ok  {background:var(--p100);color:var(--p600);border:1px solid var(--p600);}
.coupon-msg.err {background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
.breakdown{background:var(--p50);border:1px solid var(--border);border-radius:var(--r);padding:13px 15px;margin-bottom:14px;display:none;}
.bd-row{display:flex;justify-content:space-between;margin-bottom:6px;font-size:.875rem;}
.bd-row.total{padding-top:8px;border-top:1px solid var(--border);margin-bottom:0;}
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
    <div class="logo-mark">
      <img src="<?= htmlspecialchars($__logo) ?>" alt="Logo" style="width:28px;height:28px;object-fit:contain;">
    </div>
    <div class="logo-text">
      <strong><?= htmlspecialchars($__sname) ?></strong>
      <small>ছাত্র পোর্টাল</small>
    </div>
  </div>
  <div class="nav-section">
    <div class="nav-label">মেনু</div>
    <a class="nav-link active" onclick="sw('home',this,0)"><span class="nav-icon">🏠</span>ড্যাশবোর্ড</a>
    <a class="nav-link" onclick="sw('courses',this,1)"><span class="nav-icon">🎓</span>কোর্সসমূহ</a>
    <a class="nav-link" onclick="sw('schedule',this,2)"><span class="nav-icon">📅</span>ক্লাস শিডিউল</a>
    <a class="nav-link" onclick="sw('progress',this,2)"><span class="nav-icon">📊</span>প্রোগ্রেস</a>
    <a class="nav-link" onclick="sw('reports',this,3)"><span class="nav-icon">📝</span>রিপোর্ট</a>
    <a class="nav-link" onclick="sw('payments',this,4)"><span class="nav-icon">💳</span>পেমেন্ট</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row">
      <div class="user-av"><?= mb_substr($user['name'],0,1) ?></div>
      <div class="user-info">
        <strong><?= htmlspecialchars($user['name']) ?></strong>
        <span>ছাত্র</span>
      </div>
    </div>
    <a href="../api/auth.php?action=logout" class="btn-logout">লগআউট</a>
  </div>
</aside>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
      <span class="page-title" id="pt">ড্যাশবোর্ড</span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span>
      <span style="font-size:.75rem;color:var(--muted);"><?= date('d M Y') ?></span>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):     ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err): ?><div class="alert alert-danger  mb-12">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

    <!-- ═══ HOME ═══ -->
    <div id="p-home" class="tab-pane active">
      <!-- Profile -->
      <div style="background:linear-gradient(135deg,var(--sb-bg),var(--p700));border-radius:var(--r-lg);padding:18px;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--gold);color:var(--sb-bg);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;flex-shrink:0;"><?= mb_substr($user['name'],0,1) ?></div>
        <div>
          <div style="font-size:1rem;font-weight:700;margin-bottom:2px;">আস-সালামু আলাইকুম, <?= htmlspecialchars($user['name']) ?>!</div>
          <div style="font-size:.8rem;color:rgba(255,255,255,.6);">স্বাগতম</div>
        </div>
        <span class="badge badge-<?= $user['package']??'basic' ?>" style="margin-left:auto;"><?= pkg_name($user['package']??'basic') ?></span>
      </div>

      <div class="stats-grid">
        <div class="stat"><div class="stat-label">মোট ক্লাস</div><div class="stat-value"><?= count($all_cls) ?></div></div>
        <div class="stat gold"><div class="stat-label">আজকের ক্লাস</div><div class="stat-value"><?= count($today_cls) ?></div></div>
        <div class="stat info"><div class="stat-label">সম্পন্ন</div><div class="stat-value"><?= count($completed) ?></div></div>
        <div class="stat danger"><div class="stat-label">উপস্থিতি</div><div class="stat-value"><?= $att_pct ?>%</div></div>
      </div>

      <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:12px;">📅 আজকের ক্লাস</div>
      <?php if(empty($today_cls)): ?>
        <div class="empty" style="padding:28px;"><span class="empty-icon">☀️</span><p>আজ কোনো ক্লাস নেই</p></div>
      <?php else: foreach($today_cls as $c) echo classCard($c,'today',$user['id'],$pdo); endif; ?>

      <?php if(!empty($upcoming)): ?>
      <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin:20px 0 12px;">🔜 আসন্ন ক্লাস</div>
      <?php foreach(array_slice($upcoming,0,3) as $c) echo classCard($c,'upcoming',$user['id'],$pdo); endif; ?>
    </div>

    <!-- ═══ COURSES ═══ -->
    <div id="p-courses" class="tab-pane">

      <!-- Enrolled Courses -->
      <?php if(!empty($enrolled_courses)): ?>
      <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:12px;">📚 আমার ভর্তি কোর্সসমূহ</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-bottom:24px;">
        <?php foreach($enrolled_courses as $ec):
          $pct = $ec['total_lessons']>0 ? round($ec['completed_lessons']/$ec['total_lessons']*100) : 0;
        ?>
        <div style="background:white;border-radius:var(--r-lg);border:1px solid var(--border);overflow:hidden;box-shadow:var(--shadow-xs);">
          <div style="height:80px;background:linear-gradient(135deg,var(--p700),var(--p600));display:flex;align-items:center;justify-content:center;font-size:2.2rem;">
            <?= $ec['cat_icon']??'📚' ?>
          </div>
          <div style="padding:13px;">
            <div style="font-size:.875rem;font-weight:700;color:var(--ink);margin-bottom:6px;"><?= htmlspecialchars($ec['title']) ?></div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:8px;">👨‍🏫 <?= htmlspecialchars($ec['instructor']??'') ?> · <?= htmlspecialchars($ec['cat_name']) ?></div>
            <!-- Progress -->
            <div style="display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:4px;">
              <span style="color:var(--muted);">অগ্রগতি</span>
              <span style="font-weight:700;color:var(--p600);"><?= $pct ?>%</span>
            </div>
            <div class="progress-wrap" style="margin-bottom:10px;"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:10px;"><?= $ec['completed_lessons'] ?>/<?= $ec['total_lessons'] ?> Lesson সম্পন্ন</div>
            <div style="display:flex;gap:6px;">
              <a href="course.php?slug=<?= urlencode($ec['slug']) ?>" style="flex:1;display:block;text-align:center;background:var(--p600);color:#fff;padding:8px;border-radius:7px;font-size:.8rem;font-weight:700;text-decoration:none;">▶ চালিয়ে যান</a>
              <?php if($pct>=100): ?><a href="certificate.php?course=<?= urlencode($ec['slug']) ?>" style="display:block;text-align:center;background:var(--gold);color:#fff;padding:8px 10px;border-radius:7px;font-size:.8rem;font-weight:700;text-decoration:none;">🎓</a><?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Available Courses -->
      <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:12px;">🌟 আরো কোর্সসমূহ</div>
      <?php if(empty($available_courses)): ?>
      <div class="empty"><span class="empty-icon">🎓</span><p>সব কোর্সে ইতিমধ্যে ভর্তি আছেন!</p></div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;">
        <?php foreach($available_courses as $ac):
          $price_disp = $ac['is_free'] ? '<span style="color:#16a34a;font-weight:700;">🆓 বিনামূল্যে</span>' : ($ac['sale_price'] ? '<span style="font-weight:700;color:var(--p600);">৳'.number_format($ac['sale_price']).'</span> <span style="text-decoration:line-through;color:var(--muted);font-size:.75rem;">৳'.number_format($ac['price']).'</span>' : '<span style="font-weight:700;color:var(--p600);">৳'.number_format($ac['price']).'</span>');
        ?>
        <div style="background:white;border-radius:var(--r-lg);border:1px solid var(--border);overflow:hidden;box-shadow:var(--shadow-xs);">
          <div style="height:80px;background:linear-gradient(135deg,var(--p700),var(--p600));display:flex;align-items:center;justify-content:center;font-size:2.2rem;">
            <?= $ac['cat_icon']??'📚' ?>
          </div>
          <div style="padding:13px;">
            <div style="font-size:.875rem;font-weight:700;color:var(--ink);margin-bottom:4px;"><?= htmlspecialchars($ac['title']) ?></div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:8px;"><?= htmlspecialchars($ac['cat_name']) ?><?= $ac['duration']?' · ⏱ '.htmlspecialchars($ac['duration']):'' ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <div style="font-size:.875rem;"><?= $price_disp ?></div>
              <a href="../register.php" style="background:var(--gold);color:var(--sb-bg);padding:6px 12px;border-radius:6px;font-size:.75rem;font-weight:700;text-decoration:none;">ভর্তি →</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══ SCHEDULE ═══ -->

    <div id="p-schedule" class="tab-pane">
      <?php if(empty($all_cls)): ?>
        <div class="empty"><span class="empty-icon">📅</span><p>কোনো ক্লাস নেই</p></div>
      <?php else: foreach($all_cls as $c):
        $t = $c['status']==='completed'?'done':($c['class_date']===$today?'today':'upcoming');
        echo classCard($c,$t,$user['id'],$pdo);
      endforeach; endif; ?>
    </div>

    <!-- ═══ PROGRESS ═══ -->
    <div id="p-progress" class="tab-pane">
      <div class="card mb-16">
        <div class="card-head"><h2>📊 উপস্থিতি</h2></div>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
            <span style="font-weight:600;font-size:.875rem;">সামগ্রিক উপস্থিতি</span>
            <span style="font-weight:700;color:var(--p600);"><?= $att_pct ?>%</span>
          </div>
          <div class="progress-wrap"><div class="progress-bar" style="width:<?= $att_pct ?>%"></div></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:5px;">
            <?= count(array_filter($att_all,fn($a)=>$a['status']==='present')) ?> উপস্থিত / <?= count($att_all) ?> মোট
          </div>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat"><div class="stat-label">মোট</div><div class="stat-value"><?= count($all_cls) ?></div></div>
        <div class="stat gold"><div class="stat-label">সম্পন্ন</div><div class="stat-value"><?= count($completed) ?></div></div>
        <div class="stat info"><div class="stat-label">আসন্ন</div><div class="stat-value"><?= count($upcoming) ?></div></div>
        <div class="stat danger"><div class="stat-label">উপস্থিতি</div><div class="stat-value"><?= $att_pct ?>%</div></div>
      </div>
    </div>

    <!-- ═══ REPORTS ═══ -->
    <div id="p-reports" class="tab-pane">
      <?php if(empty($all_reports)): ?>
        <div class="empty"><span class="empty-icon">📝</span><p>কোনো রিপোর্ট নেই</p></div>
      <?php else: foreach($all_reports as $r): ?>
        <div class="card mb-12">
          <div class="card-head">
            <div>
              <h2><?= htmlspecialchars($type_bn[$r['report_type']]??$r['report_type']) ?> রিপোর্ট</h2>
              <div style="font-size:.75rem;color:var(--muted);">👨‍🏫 <?= htmlspecialchars($r['teacher_name']) ?> · <?= date('d M Y',strtotime($r['created_at'])) ?></div>
            </div>
            <span style="font-size:1.2rem;"><?= ['excellent'=>'⭐⭐⭐⭐⭐','good'=>'⭐⭐⭐⭐','average'=>'⭐⭐⭐','needs_improvement'=>'⭐⭐'][$r['tilawat_grade']]??'' ?></span>
          </div>
          <div class="card-body">
            <div style="font-size:.875rem;color:var(--body);margin-bottom:10px;"><?= nl2br(htmlspecialchars($r['content'])) ?></div>
            <?php if($r['homework']): ?>
              <div style="background:var(--p50);border-radius:var(--r-sm);padding:9px 12px;font-size:.82rem;">
                📚 <strong>হোমওয়ার্ক:</strong> <?= htmlspecialchars($r['homework']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- ═══ PAYMENTS ═══ -->
    <div id="p-payments" class="tab-pane">

      <!-- Payment Form -->
      <div class="card mb-16">
        <div class="card-head"><h2>💳 পেমেন্ট করুন</h2></div>
        <div class="card-body">
          <div class="alert alert-info" style="margin-bottom:16px;">
            ℹ️ পেমেন্টের পর TxnID দিন। অ্যাডমিন ২৪ ঘণ্টার মধ্যে কনফার্ম করবেন।
          </div>

          <!-- Payment numbers -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;">
            <?php if($bkash): ?>
            <div style="background:var(--p50);border-radius:var(--r);padding:14px;text-align:center;">
              <div style="font-size:1.5rem;">📱</div>
              <div style="font-weight:700;color:var(--ink);">bKash</div>
              <div style="font-size:1rem;font-weight:700;color:var(--p600);margin:3px 0;"><?= htmlspecialchars($bkash) ?></div>
              <div style="font-size:.72rem;color:var(--muted);">Send Money</div>
            </div>
            <?php endif; ?>
            <?php if($nagad): ?>
            <div style="background:var(--p50);border-radius:var(--r);padding:14px;text-align:center;">
              <div style="font-size:1.5rem;">💜</div>
              <div style="font-weight:700;color:var(--ink);">Nagad</div>
              <div style="font-size:1rem;font-weight:700;color:var(--p600);margin:3px 0;"><?= htmlspecialchars($nagad) ?></div>
              <div style="font-size:.72rem;color:var(--muted);">Send Money</div>
            </div>
            <?php endif; ?>
          </div>

          <form action="../api/payments.php" method="POST" id="payForm">
            <input type="hidden" name="action"       value="submit">
            <input type="hidden" name="coupon_id"    id="h_cid"   value="0">
            <input type="hidden" name="coupon_code"  id="h_ccode" value="">
            <input type="hidden" name="discount"     id="h_disc"  value="0">
            <input type="hidden" name="final_amount" id="h_final" value="">

            <div class="form-grid">
              <div class="form-group">
                <label>পেমেন্ট মাধ্যম</label>
                <select name="method">
                  <option value="bkash">📱 bKash</option>
                  <option value="nagad">💜 Nagad</option>
                </select>
              </div>
              <div class="form-group">
                <label>পরিমাণ (৳) *</label>
                <input type="number" name="amount" id="payAmt"
                  value="<?= pkg_price($user['package']??'basic') ?>"
                  placeholder="<?= pkg_price($user['package']??'basic') ?>"
                  oninput="resetCoupon()" required>
              </div>
            </div>

            <!-- COUPON INPUT -->
            <div class="form-group">
              <label>🏷️ কুপন কোড <span style="color:var(--muted);font-weight:400;">(ঐচ্ছিক)</span></label>
              <div class="coupon-row">
                <input type="text" id="couponInp"
                  placeholder="কুপন কোড লিখুন"
                  style="text-transform:uppercase;letter-spacing:.08em;font-weight:600;"
                  oninput="this.value=this.value.toUpperCase()">
                <button type="button" class="btn btn-outline" id="couponBtn"
                  onclick="applyCoupon()">প্রয়োগ</button>
              </div>
            </div>

            <!-- Message -->
            <div id="couponMsg" class="coupon-msg"></div>

            <!-- Breakdown -->
            <div id="breakdown" class="breakdown">
              <div class="bd-row">
                <span style="color:var(--muted);">মূল মূল্য:</span>
                <span id="bd_orig" style="font-weight:600;"></span>
              </div>
              <div class="bd-row">
                <span style="color:var(--danger);">কুপন ছাড়:</span>
                <span id="bd_disc" style="font-weight:700;color:var(--danger);"></span>
              </div>
              <div class="bd-row total">
                <span style="font-weight:700;color:var(--ink);">পরিশোধযোগ্য:</span>
                <span id="bd_final" style="font-size:1.1rem;font-weight:700;color:var(--p600);"></span>
              </div>
            </div>

            <div class="form-group">
              <label>ট্রানজেকশন ID *</label>
              <input type="text" name="txn_id" required placeholder="যেমন: BKS12345678">
            </div>

            <button type="submit" class="btn btn-primary btn-full"
              onclick="setFinal()">✅ পেমেন্ট সাবমিট করুন</button>
          </form>
        </div>
      </div>

      <!-- Payment History -->
      <div class="card">
        <div class="card-head"><h2>📋 পেমেন্ট ইতিহাস</h2></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>তারিখ</th><th>পরিমাণ</th><th>মাধ্যম</th><th>TxnID</th><th>অবস্থা</th></tr></thead>
            <tbody>
              <?php if(empty($all_pays)): ?>
              <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">কোনো পেমেন্ট নেই</td></tr>
              <?php else: foreach($all_pays as $p): ?>
              <tr>
                <td class="text-sm text-muted"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
                <td><strong>৳<?= number_format($p['amount']) ?></strong></td>
                <td><?= $p['method']==='bkash'?'📱 bKash':'💜 Nagad' ?></td>
                <td style="font-family:monospace;font-size:.8rem;"><?= htmlspecialchars($p['txn_id']) ?></td>
                <td>
                  <span class="badge <?= $p['status']==='confirmed'?'badge-active':($p['status']==='rejected'?'badge-cancelled':'badge-pending') ?>">
                    <?= $p['status']==='confirmed'?'✅ কনফার্ম':($p['status']==='rejected'?'❌ বাতিল':'⏳ অপেক্ষমাণ') ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /p-payments -->
  </div><!-- /page-body -->
</div><!-- /main -->
</div><!-- /app -->

<!-- BOTTOM NAV -->
<div class="bottom-nav">
  <div class="bottom-nav-inner">
    <button class="bottom-nav-item active" id="bn-0" onclick="sw('home',null,0)"><span class="b-icon">🏠</span>হোম</button>
    <button class="bottom-nav-item"        id="bn-1" onclick="sw('schedule',null,1)"><span class="b-icon">📅</span>ক্লাস</button>
    <button class="bottom-nav-item"        id="bn-2" onclick="sw('progress',null,2)"><span class="b-icon">📊</span>প্রোগ্রেস</button>
    <button class="bottom-nav-item"        id="bn-3" onclick="sw('reports',null,3)"><span class="b-icon">📝</span>রিপোর্ট</button>
    <button class="bottom-nav-item"        id="bn-4" onclick="sw('payments',null,4)"><span class="b-icon">💳</span>পেমেন্ট</button>
  </div>
</div>

<div id="toast-container"></div>

<script>
const TITLES = {home:'ড্যাশবোর্ড',courses:'🎓 কোর্সসমূহ',schedule:'ক্লাস শিডিউল',progress:'প্রোগ্রেস',reports:'রিপোর্ট',payments:'পেমেন্ট'};

function sw(page, navEl, bnIdx) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('p-'+page).classList.add('active');
  document.getElementById('pt').textContent = TITLES[page] || page;
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
  if (navEl) navEl.classList.add('active');
  document.querySelectorAll('.bottom-nav-item').forEach(b => b.classList.remove('active'));
  const bn = document.getElementById('bn-'+bnIdx);
  if (bn) bn.classList.add('active');
  closeSidebar();
}

function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('active'); }
function closeSidebar()  { document.getElementById('sidebar').classList.remove('open');  document.getElementById('overlay').classList.remove('active'); }

// ── COUPON ──
let couponApplied = false;

async function applyCoupon() {
  const code   = document.getElementById('couponInp').value.trim();
  const amount = parseFloat(document.getElementById('payAmt').value) || 0;
  const btn    = document.getElementById('couponBtn');

  if (!code)   { showMsg('❌ কুপন কোড লিখুন।','err'); return; }
  if (!amount) { showMsg('❌ আগে পরিমাণ দিন।','err'); return; }

  btn.disabled = true; btn.textContent = '⏳';

  const fd = new FormData();
  fd.append('action','validate');
  fd.append('code',  code);
  fd.append('amount',amount);

  try {
    const r = await fetch('../api/coupon.php', {method:'POST', body:fd});
    const d = await r.json();

    if (!d.ok) { showMsg('❌ '+d.msg,'err'); resetCoupon(); }
    else {
      document.getElementById('h_cid').value   = d.coupon_id;
      document.getElementById('h_ccode').value = code;
      document.getElementById('h_disc').value  = d.discount;
      document.getElementById('h_final').value = d.final;
      couponApplied = true;

      showMsg('✅ '+d.msg, 'ok');

      document.getElementById('bd_orig').textContent  = '৳'+amount.toLocaleString();
      document.getElementById('bd_disc').textContent  = '-৳'+d.discount.toLocaleString();
      document.getElementById('bd_final').textContent = '৳'+d.final.toLocaleString();
      document.getElementById('breakdown').style.display = 'block';
    }
  } catch(e) {
    showMsg('❌ সংযোগ সমস্যা। আবার চেষ্টা করুন।','err');
  }

  btn.disabled = false; btn.textContent = 'প্রয়োগ';
}

function showMsg(msg, type) {
  const el = document.getElementById('couponMsg');
  el.textContent = msg;
  el.className   = 'coupon-msg ' + type;
  el.style.display = 'block';
}

function resetCoupon() {
  couponApplied = false;
  ['h_cid','h_ccode'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('h_disc').value  = '0';
  document.getElementById('h_final').value = '';
  document.getElementById('couponMsg').style.display   = 'none';
  document.getElementById('breakdown').style.display   = 'none';
}

function setFinal() {
  const amount = parseFloat(document.getElementById('payAmt').value) || 0;
  if (!couponApplied) {
    document.getElementById('h_final').value = amount;
  }
}
</script>
</body>
</html>
