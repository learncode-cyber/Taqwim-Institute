<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['student']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';

$course_slug = $_GET['slug'] ?? '';
$lesson_id   = intval($_GET['lesson'] ?? 0);

// Load course
$cs = $pdo->prepare("
    SELECT c.*,cc.name AS cat_name,cc.icon AS cat_icon
    FROM courses c JOIN course_categories cc ON cc.id=c.category_id
    WHERE c.slug=? AND c.is_active=1
");
$cs->execute([$course_slug]);
$course = $cs->fetch();

if (!$course) { header('Location: index.php'); exit; }

// Check enrollment
$enr = $pdo->prepare("SELECT * FROM course_enrollments WHERE course_id=? AND student_id=? AND status='active'");
$enr->execute([$course['id'],$user['id']]);
$enrollment = $enr->fetch();

if (!$enrollment) { header('Location: index.php'); exit; }

// Load modules + lessons
$mods = $pdo->prepare("SELECT * FROM course_modules WHERE course_id=? AND is_active=1 ORDER BY sort_order");
$mods->execute([$course['id']]);
$modules = $mods->fetchAll();

foreach ($modules as &$m) {
    $ls = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id=? AND is_active=1 ORDER BY sort_order");
    $ls->execute([$m['id']]);
    $m['lessons'] = $ls->fetchAll();
}

// Load progress
$prog_stmt = $pdo->prepare("SELECT lesson_id FROM lesson_progress WHERE student_id=? AND course_id=? AND is_completed=1");
$prog_stmt->execute([$user['id'],$course['id']]);
$completed_ids = array_column($prog_stmt->fetchAll(), 'lesson_id');

// Total lessons & progress
$total_lessons = array_sum(array_map(fn($m)=>count($m['lessons']), $modules));
$completed_cnt = count($completed_ids);
$progress_pct  = $total_lessons > 0 ? round($completed_cnt/$total_lessons*100) : 0;

// Current lesson
$current_lesson = null;
$all_lessons    = [];
foreach ($modules as $m) foreach ($m['lessons'] as $l) $all_lessons[] = $l;

if ($lesson_id) {
    foreach ($all_lessons as $l) if ($l['id']==$lesson_id) { $current_lesson=$l; break; }
}
if (!$current_lesson && !empty($all_lessons)) {
    // First incomplete lesson
    foreach ($all_lessons as $l) {
        if (!in_array($l['id'], $completed_ids)) { $current_lesson=$l; break; }
    }
    if (!$current_lesson) $current_lesson = $all_lessons[0];
}

// Mark lesson complete (AJAX)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_complete'])) {
    $lid = intval($_POST['lesson_id'] ?? 0);
    $cid = intval($_POST['course_id'] ?? 0);
    if ($lid && $cid) {
        $pdo->prepare("INSERT INTO lesson_progress (student_id,course_id,lesson_id,is_completed,completed_at) VALUES (?,?,?,1,NOW()) ON DUPLICATE KEY UPDATE is_completed=1,completed_at=NOW()")
            ->execute([$user['id'],$cid,$lid]);
        // Update course completion
        $done = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE student_id=? AND course_id=? AND is_completed=1");
        $done->execute([$user['id'],$cid]);
        echo json_encode(['ok'=>true,'completed'=>$done->fetchColumn(),'total'=>$total_lessons]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// YouTube embed helper
function yt_embed(string $url): string {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m);
    return $m[1] ?? '';
}

$type_icons = ['video'=>'🎬','pdf'=>'📄','text'=>'📝','live'=>'🔴','quiz'=>'🧠'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= htmlspecialchars($current_lesson['title']??'') ?> — <?= htmlspecialchars($course['title']) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.course-layout{display:flex;min-height:100vh;}
/* Sidebar */
.course-sidebar{
  width:300px;flex-shrink:0;background:var(--g800);
  position:fixed;top:0;left:0;height:100vh;
  overflow-y:auto;z-index:300;
  transform:translateX(-100%);transition:transform .25s;
}
.course-sidebar.open{transform:translateX(0);}
.cs-header{padding:16px;border-bottom:1px solid rgba(255,255,255,.08);}
.cs-title{color:#fff;font-size:.88rem;font-weight:700;line-height:1.3;margin-bottom:6px;}
.cs-progress{margin-top:8px;}
.cs-prog-bar{height:6px;background:rgba(255,255,255,.12);border-radius:3px;overflow:hidden;}
.cs-prog-fill{height:100%;background:var(--gold);border-radius:3px;transition:width .4s;}
.cs-prog-text{font-size:.7rem;color:rgba(255,255,255,.4);margin-top:4px;}
.cs-back{display:flex;align-items:center;gap:6px;color:rgba(255,255,255,.5);font-size:.78rem;text-decoration:none;margin-bottom:12px;transition:color .15s;}
.cs-back:hover{color:#fff;}

/* Module list */
.module-sec{border-bottom:1px solid rgba(255,255,255,.06);}
.module-hd{display:flex;align-items:center;gap:8px;padding:12px 16px;cursor:pointer;}
.module-hd span{color:rgba(255,255,255,.7);font-size:.82rem;font-weight:700;flex:1;}
.module-hd .arr{color:rgba(255,255,255,.3);font-size:.7rem;transition:transform .2s;}
.module-hd.open .arr{transform:rotate(180deg);}
.lesson-list-cs{display:none;}
.module-hd.open+.lesson-list-cs{display:block;}
.lesson-item{display:flex;align-items:center;gap:9px;padding:10px 16px 10px 32px;cursor:pointer;transition:background .15s;text-decoration:none;}
.lesson-item:hover{background:rgba(255,255,255,.05);}
.lesson-item.active{background:rgba(255,255,255,.1);}
.lesson-item.done .li-icon{color:#4ade80;}
.li-icon{font-size:.9rem;flex-shrink:0;width:18px;text-align:center;}
.li-title{color:rgba(255,255,255,.75);font-size:.78rem;line-height:1.4;flex:1;}
.lesson-item.active .li-title{color:#fff;font-weight:600;}
.li-dur{color:rgba(255,255,255,.3);font-size:.68rem;flex-shrink:0;}

/* Main content */
.course-main{flex:1;margin-left:0;min-width:0;background:var(--bg);display:flex;flex-direction:column;}
.course-topbar{
  height:54px;background:white;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;padding:0 16px;
  position:sticky;top:0;z-index:200;box-shadow:var(--shadow-xs);
}
.sidebar-toggle{width:36px;height:36px;border:none;background:none;font-size:1.2rem;cursor:pointer;border-radius:7px;transition:background .15s;}
.sidebar-toggle:hover{background:var(--g50);}
.lesson-title-bar{font-size:.9rem;font-weight:700;color:var(--ink);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.prog-chip{background:var(--g100);color:var(--g600);padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}

/* Content area */
.content-area{flex:1;padding:16px;max-width:900px;width:100%;}

/* Video player */
.video-wrap{position:relative;padding-bottom:56.25%;height:0;border-radius:var(--radius-lg);overflow:hidden;background:#000;margin-bottom:20px;box-shadow:var(--shadow-md);}
.video-wrap iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:none;}

/* PDF viewer */
.pdf-wrap{width:100%;height:70vh;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border);margin-bottom:20px;box-shadow:var(--shadow-sm);}
.pdf-wrap iframe{width:100%;height:100%;border:none;}

/* Text content */
.text-content{background:white;border-radius:var(--radius-lg);padding:24px;border:1px solid var(--border);margin-bottom:20px;font-size:.95rem;line-height:1.8;color:var(--body);}

/* Live class */
.live-card{background:linear-gradient(135deg,#7f1d1d,#991b1b);border-radius:var(--radius-lg);padding:28px;text-align:center;color:#fff;margin-bottom:20px;}
.live-card .pulse{width:16px;height:16px;background:#f87171;border-radius:50%;display:inline-block;animation:pulse 1.5s infinite;margin-right:8px;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.6;transform:scale(1.2);}}

/* Lesson info card */
.lesson-info-card{background:white;border-radius:var(--radius-lg);padding:18px;border:1px solid var(--border);margin-bottom:16px;}
.lesson-nav{display:flex;gap:10px;justify-content:space-between;margin-bottom:16px;}
.complete-btn{width:100%;padding:13px;background:var(--g600);color:#fff;border:none;border-radius:var(--radius);font-size:.95rem;font-weight:700;cursor:pointer;font-family:var(--font);transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.complete-btn:hover{background:var(--g500);}
.complete-btn.done{background:#16a34a;}
.complete-btn:disabled{opacity:.6;cursor:not-allowed;}

/* Overlay */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:299;}
.sidebar-overlay.active{display:block;}

@media(min-width:900px){
  .course-sidebar{transform:translateX(0);}
  .course-main{margin-left:300px;}
  .sidebar-toggle{display:none;}
  .sidebar-overlay{display:none!important;}
}
</style>
</head>
<body>
<div class="course-layout">

<!-- COURSE SIDEBAR -->
<aside class="course-sidebar" id="courseSidebar">
  <div class="cs-header">
    <a href="index.php" class="cs-back">← ড্যাশবোর্ডে ফিরুন</a>
    <div class="cs-title"><?= htmlspecialchars($course['cat_icon']??'📚') ?> <?= htmlspecialchars($course['title']) ?></div>
    <div class="cs-progress">
      <div class="cs-prog-bar"><div class="cs-prog-fill" id="progFill" style="width:<?= $progress_pct ?>%"></div></div>
      <div class="cs-prog-text"><span id="progText"><?= $completed_cnt ?>/<?= $total_lessons ?></span> Lesson · <span id="progPct"><?= $progress_pct ?>%</span> সম্পন্ন</div>
    </div>
  </div>

  <!-- Module + Lesson list -->
  <?php foreach($modules as $mi=>$mod): ?>
  <div class="module-sec">
    <div class="module-hd <?= $mi===0?'open':'' ?>" onclick="toggleMod(this)">
      <span>📦 <?= htmlspecialchars($mod['title']) ?></span>
      <span class="arr">▼</span>
    </div>
    <div class="lesson-list-cs">
      <?php foreach($mod['lessons'] as $les):
        $is_done   = in_array($les['id'], $completed_ids);
        $is_active = $current_lesson && $les['id']==$current_lesson['id'];
      ?>
      <a href="course.php?slug=<?= urlencode($course['slug']) ?>&lesson=<?= $les['id'] ?>"
         class="lesson-item <?= $is_active?'active':'' ?> <?= $is_done?'done':'' ?>">
        <span class="li-icon"><?= $is_done ? '✅' : ($type_icons[$les['type']]??'📝') ?></span>
        <span class="li-title"><?= htmlspecialchars($les['title']) ?></span>
        <?php if($les['duration']): ?><span class="li-dur"><?= htmlspecialchars($les['duration']) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- MAIN -->
<div class="course-main">
  <div class="course-topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    <span class="lesson-title-bar"><?= htmlspecialchars($current_lesson['title']??'') ?></span>
    <span class="prog-chip"><?= $progress_pct ?>% সম্পন্ন</span>
  </div>

  <div class="content-area">
    <?php if($current_lesson): ?>

    <!-- ── VIDEO ── -->
    <?php if($current_lesson['type']==='video' && $current_lesson['content_url']): ?>
      <?php $yt_id = yt_embed($current_lesson['content_url']); ?>
      <?php if($yt_id): ?>
      <div class="video-wrap">
        <iframe src="https://www.youtube.com/embed/<?= $yt_id ?>?rel=0&modestbranding=1"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>
      <?php else: ?>
      <div class="video-wrap">
        <video controls style="position:absolute;top:0;left:0;width:100%;height:100%;background:#000;">
          <source src="<?= htmlspecialchars($current_lesson['content_url']) ?>">
        </video>
      </div>
      <?php endif; ?>

    <!-- ── PDF ── -->
    <?php elseif($current_lesson['type']==='pdf' && $current_lesson['content_url']): ?>
      <div class="pdf-wrap">
        <iframe src="<?= htmlspecialchars($current_lesson['content_url']) ?>#toolbar=1" type="application/pdf"></iframe>
      </div>
      <div style="text-align:center;margin-bottom:16px;">
        <a href="<?= htmlspecialchars($current_lesson['content_url']) ?>" target="_blank" class="btn btn-outline btn-sm">📥 PDF ডাউনলোড করুন</a>
      </div>

    <!-- ── TEXT ── -->
    <?php elseif($current_lesson['type']==='text'): ?>
      <div class="text-content">
        <?php if($current_lesson['content_url']): ?>
          <?= nl2br(htmlspecialchars($current_lesson['content_url'])) ?>
        <?php else: ?>
          <p style="color:var(--muted);text-align:center;">কোনো কন্টেন্ট যোগ করা হয়নি।</p>
        <?php endif; ?>
      </div>

    <!-- ── LIVE ── -->
    <?php elseif($current_lesson['type']==='live'): ?>
      <div class="live-card">
        <div style="font-size:2rem;margin-bottom:12px;">🔴</div>
        <h2 style="font-size:1.2rem;margin-bottom:8px;"><?= htmlspecialchars($current_lesson['title']) ?></h2>
        <p style="color:rgba(255,255,255,.7);margin-bottom:20px;">এটি একটি Live Class। নির্ধারিত সময়ে নিচের লিংকে যোগ দিন।</p>
        <?php if($current_lesson['content_url']): ?>
        <a href="<?= htmlspecialchars($current_lesson['content_url']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#991b1b;padding:12px 24px;border-radius:var(--radius);font-weight:700;text-decoration:none;font-size:.95rem;">
          <span class="pulse"></span>Live Class-এ যোগ দিন
        </a>
        <?php endif; ?>
      </div>

    <!-- ── QUIZ ── -->
    <?php elseif($current_lesson['type']==='quiz'): ?>
      <div style="background:white;border-radius:var(--radius-lg);padding:28px;text-align:center;border:1px solid var(--border);margin-bottom:20px;">
        <div style="font-size:3rem;margin-bottom:12px;">🧠</div>
        <h2 style="font-size:1.1rem;margin-bottom:8px;"><?= htmlspecialchars($current_lesson['title']) ?></h2>
        <p style="color:var(--muted);margin-bottom:20px;">Quiz Engine Phase 10-এ যোগ হবে।</p>
        <div style="background:var(--g50);border-radius:var(--radius);padding:14px;font-size:.82rem;color:var(--muted);">শীঘ্রই আসছে...</div>
      </div>

    <?php else: ?>
      <div class="empty"><span class="empty-icon">📦</span><p>কোনো কন্টেন্ট নেই।</p></div>
    <?php endif; ?>

    <!-- Lesson Info -->
    <div class="lesson-info-card">
      <h2 style="font-size:1rem;font-weight:700;color:var(--ink);margin-bottom:6px;"><?= htmlspecialchars($current_lesson['title']) ?></h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:.78rem;color:var(--muted);margin-bottom:14px;">
        <span><?= $type_icons[$current_lesson['type']]??'📝' ?> <?= strtoupper($current_lesson['type']) ?></span>
        <?php if($current_lesson['duration']): ?><span>⏱ <?= htmlspecialchars($current_lesson['duration']) ?></span><?php endif; ?>
        <?php if(in_array($current_lesson['id'],$completed_ids)): ?><span style="color:#16a34a;font-weight:700;">✅ সম্পন্ন</span><?php endif; ?>
      </div>

      <!-- Navigation -->
      <div class="lesson-nav">
        <?php
        $cur_idx = array_search($current_lesson, $all_lessons);
        $prev = $cur_idx > 0 ? $all_lessons[$cur_idx-1] : null;
        $next = $cur_idx < count($all_lessons)-1 ? $all_lessons[$cur_idx+1] : null;
        ?>
        <?php if($prev): ?>
        <a href="course.php?slug=<?= urlencode($course['slug']) ?>&lesson=<?= $prev['id'] ?>" class="btn btn-outline btn-sm">← আগের Lesson</a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if($next): ?>
        <a href="course.php?slug=<?= urlencode($course['slug']) ?>&lesson=<?= $next['id'] ?>" class="btn btn-primary btn-sm">পরের Lesson →</a>
        <?php endif; ?>
      </div>

      <!-- Complete button -->
      <?php $is_done = in_array($current_lesson['id'], $completed_ids); ?>
      <button class="complete-btn <?= $is_done?'done':'' ?>"
              id="completeBtn"
              onclick="markComplete(<?= $current_lesson['id'] ?>,<?= $course['id'] ?>)"
              <?= $is_done?'disabled':'' ?>>
        <?= $is_done ? '✅ সম্পন্ন হয়েছে' : '✅ সম্পন্ন হিসেবে চিহ্নিত করুন' ?>
      </button>
    </div>

    <?php else: ?>
    <div class="empty" style="padding:60px;">
      <span class="empty-icon">📚</span>
      <p>এই কোর্সে এখনো কোনো Lesson যোগ করা হয়নি।</p>
      <a href="index.php" class="btn btn-primary" style="margin-top:16px;">← ড্যাশবোর্ডে ফিরুন</a>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<div id="toast-container"></div>
<script>
function toggleSidebar(){
  document.getElementById('courseSidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar(){
  document.getElementById('courseSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}
function toggleMod(el){
  el.classList.toggle('open');
}

async function markComplete(lessonId, courseId) {
  const btn = document.getElementById('completeBtn');
  btn.disabled = true;
  btn.textContent = '⏳ সেভ হচ্ছে...';

  const fd = new FormData();
  fd.append('mark_complete','1');
  fd.append('lesson_id', lessonId);
  fd.append('course_id', courseId);

  const r = await fetch(window.location.href, {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    btn.textContent = '✅ সম্পন্ন হয়েছে';
    btn.classList.add('done');

    // Update progress
    const pct = Math.round(d.completed/d.total*100);
    document.getElementById('progFill').style.width = pct+'%';
    document.getElementById('progText').textContent = d.completed+'/'+d.total;
    document.getElementById('progPct').textContent = pct+'%';
    document.querySelector('.prog-chip').textContent = pct+'% সম্পন্ন';

    toast('✅ Lesson সম্পন্ন! '+pct+'% প্রগ্রেস');

    // Auto-next after 1.5s
    const nextLink = document.querySelector('.lesson-nav a.btn-primary');
    if (nextLink) setTimeout(()=>{ window.location.href=nextLink.href; }, 1800);
  }
}

function toast(msg){
  const el=document.createElement('div');
  el.className='toast';
  el.textContent=msg;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(()=>el.remove(),3500);
}
</script>
</body>
</html>
