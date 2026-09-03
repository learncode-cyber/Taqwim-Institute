<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['admin']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── ADD COURSE ──
if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
    $title    = trim($_POST['title']    ?? '');
    $cat_id   = intval($_POST['category_id'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');
    $instr    = trim($_POST['instructor']  ?? '');
    $level    = $_POST['level']   ?? 'beginner';
    $model    = $_POST['model']   ?? 'self_paced';
    $price    = floatval($_POST['price']   ?? 0);
    $sale     = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $dur      = trim($_POST['duration']    ?? '');
    $featured = isset($_POST['is_featured']) ? 1 : 0;
    $free     = isset($_POST['is_free'])     ? 1 : 0;
    $slug     = strtolower(preg_replace('/[^a-z0-9]+/','-', $title)).'-'.time();
    if (!$title || !$cat_id) { $_SESSION['flash_err']='শিরোনাম ও ক্যাটাগরি আবশ্যক।'; header('Location: courses.php'); exit; }
    $pdo->prepare("INSERT INTO courses (category_id,title,slug,description,instructor,level,model,price,sale_price,duration,is_featured,is_free) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$cat_id,$title,$slug,$desc,$instr,$level,$model,$price,$sale,$dur,$featured,$free]);
    $_SESSION['flash'] = "'{$title}' কোর্স যোগ হয়েছে ✅";
    header('Location: courses.php'); exit;
}

// ── ADD MODULE ──
if ($action === 'add_module' && $_SERVER['REQUEST_METHOD']==='POST') {
    $cid   = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $sort  = intval($_POST['sort_order'] ?? 0);
    if (!$cid || !$title) { $_SESSION['flash_err']='শিরোনাম আবশ্যক।'; header("Location: courses.php?view=$cid"); exit; }
    $pdo->prepare("INSERT INTO course_modules (course_id,title,sort_order) VALUES (?,?,?)")->execute([$cid,$title,$sort]);
    $_SESSION['flash'] = 'Module যোগ হয়েছে ✅';
    header("Location: courses.php?view=$cid"); exit;
}

// ── ADD LESSON ──
if ($action === 'add_lesson' && $_SERVER['REQUEST_METHOD']==='POST') {
    $mid   = intval($_POST['module_id']   ?? 0);
    $cid   = intval($_POST['course_id']   ?? 0);
    $title = trim($_POST['title']         ?? '');
    $type  = $_POST['type']               ?? 'video';
    $url   = trim($_POST['content_url']   ?? '');
    $dur   = trim($_POST['duration']      ?? '');
    $free  = isset($_POST['is_free'])     ? 1 : 0;
    $sort  = intval($_POST['sort_order']  ?? 0);
    if (!$mid || !$title) { $_SESSION['flash_err']='শিরোনাম আবশ্যক।'; header("Location: courses.php?view=$cid"); exit; }
    $pdo->prepare("INSERT INTO course_lessons (module_id,course_id,title,type,content_url,duration,is_free,sort_order) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$mid,$cid,$title,$type,$url,$dur,$free,$sort]);
    // Update total_lessons count
    $pdo->prepare("UPDATE courses SET total_lessons=(SELECT COUNT(*) FROM course_lessons WHERE course_id=? AND is_active=1) WHERE id=?")->execute([$cid,$cid]);
    $_SESSION['flash'] = 'Lesson যোগ হয়েছে ✅';
    header("Location: courses.php?view=$cid"); exit;
}

// ── TOGGLE ACTIVE ──
if ($action === 'toggle') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare("UPDATE courses SET is_active=1-is_active WHERE id=?")->execute([$id]);
    header('Location: courses.php'); exit;
}

// ── DELETE COURSE ──
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = 'কোর্স মুছে ফেলা হয়েছে।';
    header('Location: courses.php'); exit;
}

// ── DELETE MODULE ──
if ($action === 'del_module') {
    $id  = intval($_GET['id']  ?? 0);
    $cid = intval($_GET['cid'] ?? 0);
    $pdo->prepare("DELETE FROM course_modules WHERE id=?")->execute([$id]);
    header("Location: courses.php?view=$cid"); exit;
}

// ── DELETE LESSON ──
if ($action === 'del_lesson') {
    $id  = intval($_GET['id']  ?? 0);
    $cid = intval($_GET['cid'] ?? 0);
    $pdo->prepare("DELETE FROM course_lessons WHERE id=?")->execute([$id]);
    $pdo->prepare("UPDATE courses SET total_lessons=(SELECT COUNT(*) FROM course_lessons WHERE course_id=? AND is_active=1) WHERE id=?")->execute([$cid,$cid]);
    header("Location: courses.php?view=$cid"); exit;
}

// ── DATA ──
$categories = $pdo->query("SELECT * FROM course_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$courses    = $pdo->query("SELECT c.*,cc.name AS cat_name,cc.icon AS cat_icon,(SELECT COUNT(*) FROM course_enrollments WHERE course_id=c.id AND status='active') AS enrolled_count FROM courses c JOIN course_categories cc ON cc.id=c.category_id ORDER BY c.sort_order,c.created_at DESC")->fetchAll();

// Course detail view
$view_id = intval($_GET['view'] ?? 0);
$view_course = null; $modules = [];
if ($view_id) {
    $vs = $pdo->prepare("SELECT c.*,cc.name AS cat_name FROM courses c JOIN course_categories cc ON cc.id=c.category_id WHERE c.id=?");
    $vs->execute([$view_id]); $view_course = $vs->fetch();
    if ($view_course) {
        $mods = $pdo->prepare("SELECT * FROM course_modules WHERE course_id=? AND is_active=1 ORDER BY sort_order");
        $mods->execute([$view_id]); $modules = $mods->fetchAll();
        foreach ($modules as &$m) {
            $ls = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id=? AND is_active=1 ORDER BY sort_order");
            $ls->execute([$m['id']]); $m['lessons'] = $ls->fetchAll();
        }
    }
}

$model_bn  = ['self_paced'=>'Self-paced 🎬','cohort'=>'Cohort 📅','subscription'=>'Subscription 🔄','live'=>'Live Class 🔴'];
$level_bn  = ['beginner'=>'শিক্ষানবিশ 🌱','intermediate'=>'মধ্যবর্তী 🌿','advanced'=>'অগ্রসর 🌳'];
$type_icons= ['video'=>'🎬','pdf'=>'📄','text'=>'📝','live'=>'🔴','quiz'=>'🧠'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>কোর্স ম্যানেজমেন্ট — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.course-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:24px;}
.course-card{background:white;border-radius:var(--r-lg);border:1px solid var(--border);overflow:hidden;transition:all .2s;box-shadow:var(--shadow-xs);}
.course-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
.course-card.inactive{opacity:.6;}
.cc-thumb{height:120px;background:linear-gradient(135deg,var(--p700),var(--p600));display:flex;align-items:center;justify-content:center;font-size:3rem;position:relative;}
.cc-thumb .cat-badge{position:absolute;top:8px;left:8px;background:rgba(0,0,0,.4);color:#fff;padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:700;}
.cc-thumb .feat-badge{position:absolute;top:8px;right:8px;background:var(--gold);color:var(--sb-bg);padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:700;}
.cc-body{padding:14px;}
.cc-title{font-size:.9rem;font-weight:700;color:var(--ink);margin-bottom:6px;line-height:1.3;}
.cc-meta{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;}
.cc-tag{font-size:.68rem;padding:2px 8px;border-radius:20px;font-weight:600;}
.tag-model-self_paced{background:#e0f2fe;color:#075985;}
.tag-model-cohort{background:#fef9c3;color:#92400e;}
.tag-model-live{background:#fee2e2;color:#991b1b;}
.tag-model-subscription{background:#f0fdf4;color:#166534;}
.tag-level-beginner{background:var(--p100);color:var(--p600);}
.tag-level-intermediate{background:var(--gold-bg);color:var(--gold);}
.tag-level-advanced{background:#fce7f3;color:#9d174d;}
.cc-price{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.cc-price .main{font-size:1.1rem;font-weight:700;color:var(--p600);}
.cc-price .old{font-size:.82rem;color:var(--muted);text-decoration:line-through;}
.cc-price .free{font-size:1rem;font-weight:700;color:#16a34a;}
.cc-footer{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-top:1px solid var(--border);background:var(--p50);}
.enrolled-count{font-size:.75rem;color:var(--muted);}

/* Module/Lesson tree */
.module-tree{margin-bottom:12px;}
.module-item{background:white;border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:8px;}
.module-head{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--p50);cursor:pointer;}
.module-head h3{font-size:.875rem;font-weight:700;color:var(--ink);flex:1;}
.lesson-list{padding:8px 14px 12px;}
.lesson-row{display:flex;align-items:center;gap:9px;padding:8px 0;border-bottom:1px solid var(--border);}
.lesson-row:last-child{border-bottom:none;}
.lesson-icon{font-size:1rem;flex-shrink:0;}
.lesson-info{flex:1;}
.lesson-title{font-size:.82rem;font-weight:600;color:var(--ink);}
.lesson-meta{font-size:.7rem;color:var(--muted);}
.free-tag{background:#dcfce7;color:#166534;padding:1px 6px;border-radius:20px;font-size:.65rem;font-weight:700;}

/* Search bar */
.search-wrap{position:relative;margin-bottom:14px;}
.search-wrap input{padding-left:36px;width:100%;}
.search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;}
.no-result{text-align:center;padding:28px;color:var(--muted);font-size:.875rem;display:none;}

/* Category filter */
.cat-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.cat-filter{padding:6px 14px;border-radius:20px;border:1.5px solid var(--border);background:none;font-size:.78rem;font-weight:600;color:var(--muted);cursor:pointer;font-family:var(--font-bn);transition:all .15s;}
.cat-filter.active,.cat-filter:hover{background:var(--p600);color:#fff;border-color:var(--p600);}

@media(max-width:600px){.course-grid{grid-template-columns:1fr;}}
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
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>অ্যাডমিন</small></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">ম্যানেজমেন্ট</div>
    <a class="nav-link" href="index.php"><span class="nav-icon">📊</span>ড্যাশবোর্ড</a>
    <a class="nav-link active"><span class="nav-icon">🎓</span>কোর্সসমূহ</a>
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

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
      <span class="page-title">
        <?php if($view_course): ?>
          <a href="courses.php" style="color:var(--muted);text-decoration:none;">🎓 কোর্স</a> → <?= htmlspecialchars($view_course['title']) ?>
        <?php else: ?>
          🎓 কোর্স ম্যানেজমেন্ট
        <?php endif; ?>
      </span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span>
      <?php if(!$view_course): ?>
      <button class="btn btn-primary btn-sm always" onclick="openModal('addCourseModal')">+ নতুন কোর্স</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):    ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err):?><div class="alert alert-danger  mb-12">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

    <?php if($view_course): ?>
    <!-- ═══ COURSE DETAIL VIEW ═══ -->
    <div class="card mb-16">
      <div class="card-body" style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
        <div style="font-size:3rem;"><?= $view_course['cat_icon'] ?? '📚' ?></div>
        <div style="flex:1;min-width:200px;">
          <h2 style="font-size:1.1rem;margin-bottom:4px;"><?= htmlspecialchars($view_course['title']) ?></h2>
          <div style="font-size:.8rem;color:var(--muted);margin-bottom:8px;"><?= htmlspecialchars($view_course['description']??'') ?></div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span class="badge badge-active"><?= $model_bn[$view_course['model']]??$view_course['model'] ?></span>
            <span class="badge badge-pending"><?= $level_bn[$view_course['level']]??$view_course['level'] ?></span>
            <span class="badge badge-done"><?= $view_course['total_lessons'] ?> টি Lesson</span>
            <?php if($view_course['is_free']): ?><span class="badge" style="background:#dcfce7;color:#166534;">🆓 ফ্রি</span><?php endif; ?>
          </div>
        </div>
        <div style="text-align:right;">
          <?php if($view_course['sale_price']): ?>
          <div style="font-size:1.3rem;font-weight:700;color:var(--p600);">৳<?= number_format($view_course['sale_price']) ?></div>
          <div style="font-size:.82rem;color:var(--muted);text-decoration:line-through;">৳<?= number_format($view_course['price']) ?></div>
          <?php else: ?>
          <div style="font-size:1.3rem;font-weight:700;color:var(--p600);"><?= $view_course['is_free'] ? 'ফ্রি' : '৳'.number_format($view_course['price']) ?></div>
          <?php endif; ?>
          <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">⏱ <?= htmlspecialchars($view_course['duration']??'') ?></div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <?php
      $enr_cnt = $pdo->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id=? AND status='active'"); $enr_cnt->execute([$view_id]);
      $mod_cnt = count($modules);
      $les_cnt = $view_course['total_lessons'];
      ?>
      <div class="stat"><div class="stat-label">ভর্তি ছাত্র</div><div class="stat-value"><?= $enr_cnt->fetchColumn() ?></div></div>
      <div class="stat gold"><div class="stat-label">Module</div><div class="stat-value"><?= $mod_cnt ?></div></div>
      <div class="stat info"><div class="stat-label">Lesson</div><div class="stat-value"><?= $les_cnt ?></div></div>
    </div>

    <!-- Modules & Lessons -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);">📚 Modules & Lessons</h2>
      <button class="btn btn-primary btn-sm" onclick="openModal('addModuleModal')">+ Module যোগ করুন</button>
    </div>

    <?php if(empty($modules)): ?>
    <div class="empty" style="background:white;border-radius:var(--r-lg);padding:40px;">
      <span class="empty-icon">📦</span><p>কোনো Module নেই। উপরে "+ Module যোগ করুন" ক্লিক করুন।</p>
    </div>
    <?php else: ?>
    <div class="module-tree">
      <?php foreach($modules as $mod): ?>
      <div class="module-item">
        <div class="module-head" onclick="toggleModule(<?= $mod['id'] ?>)">
          <span>📦</span>
          <h3><?= htmlspecialchars($mod['title']) ?></h3>
          <span class="badge badge-active" style="font-size:.65rem;"><?= count($mod['lessons']) ?> Lesson</span>
          <button class="btn btn-primary btn-sm" onclick="event.stopPropagation();openAddLesson(<?= $mod['id'] ?>,<?= $view_id ?>)" style="margin-left:8px;">+ Lesson</button>
          <a href="courses.php?action=del_module&id=<?= $mod['id'] ?>&cid=<?= $view_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?');event.stopPropagation()">🗑</a>
          <span id="arr-<?= $mod['id'] ?>" style="color:var(--muted);font-size:.8rem;">▼</span>
        </div>
        <div class="lesson-list" id="mod-<?= $mod['id'] ?>">
          <?php if(empty($mod['lessons'])): ?>
          <div style="text-align:center;padding:14px;color:var(--muted);font-size:.82rem;">Lesson নেই। "+ Lesson" বাটনে ক্লিক করুন।</div>
          <?php else: foreach($mod['lessons'] as $les): ?>
          <div class="lesson-row">
            <span class="lesson-icon"><?= $type_icons[$les['type']]??'📝' ?></span>
            <div class="lesson-info">
              <div class="lesson-title"><?= htmlspecialchars($les['title']) ?></div>
              <div class="lesson-meta">
                <?= strtoupper($les['type']) ?>
                <?php if($les['duration']): ?> · ⏱ <?= $les['duration'] ?><?php endif; ?>
                <?php if($les['is_free']): ?> <span class="free-tag">ফ্রি</span><?php endif; ?>
              </div>
            </div>
            <a href="courses.php?action=del_lesson&id=<?= $les['id'] ?>&cid=<?= $view_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ADD MODULE MODAL -->
    <div class="modal-overlay" id="addModuleModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>📦 নতুন Module</h3><button class="modal-close" onclick="closeModal('addModuleModal')">✕</button></div>
        <div class="modal-body">
          <form action="courses.php" method="POST">
            <input type="hidden" name="action" value="add_module">
            <input type="hidden" name="course_id" value="<?= $view_id ?>">
            <div class="form-group"><label>Module শিরোনাম *</label><input type="text" name="title" required placeholder="যেমন: পরিচিতি ও মৌলিক ধারণা"></div>
            <div class="form-group"><label>ক্রম নম্বর</label><input type="number" name="sort_order" value="<?= count($modules)+1 ?>" min="1"></div>
            <button type="submit" class="btn btn-primary btn-full">✅ Module যোগ করুন</button>
          </form>
        </div>
      </div>
    </div>

    <!-- ADD LESSON MODAL -->
    <div class="modal-overlay" id="addLessonModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>🎬 নতুন Lesson</h3><button class="modal-close" onclick="closeModal('addLessonModal')">✕</button></div>
        <div class="modal-body">
          <form action="courses.php" method="POST">
            <input type="hidden" name="action" value="add_lesson">
            <input type="hidden" name="module_id" id="ls_mid">
            <input type="hidden" name="course_id" value="<?= $view_id ?>">
            <div class="form-group"><label>Lesson শিরোনাম *</label><input type="text" name="title" required placeholder="যেমন: ভূমিকা — কী শিখবো"></div>
            <div class="form-grid">
              <div class="form-group"><label>ধরন</label>
                <select name="type" onchange="toggleUrl(this.value)">
                  <option value="video">🎬 Video</option>
                  <option value="pdf">📄 PDF</option>
                  <option value="text">📝 Text</option>
                  <option value="live">🔴 Live Class</option>
                  <option value="quiz">🧠 Quiz</option>
                </select>
              </div>
              <div class="form-group"><label>সময়কাল</label><input type="text" name="duration" placeholder="যেমন: ১৫ মিনিট"></div>
            </div>
            <div class="form-group" id="urlRow"><label>Content URL</label><input type="url" name="content_url" placeholder="YouTube, Google Drive বা PDF link"></div>
            <div class="form-group">
              <label style="display:flex;align-items:center;gap:8px;font-weight:400;">
                <input type="checkbox" name="is_free" style="width:auto;"> এই Lesson বিনামূল্যে দেখা যাবে (Preview)
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-full">✅ Lesson যোগ করুন</button>
          </form>
        </div>
      </div>
    </div>

    <!-- ═══ ENROLLED STUDENTS ═══ -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin:24px 0 14px;flex-wrap:wrap;gap:8px;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);">👨‍🎓 ভর্তি ছাত্রসমূহ</h2>
      <button class="btn btn-primary btn-sm" onclick="openModal('enrollModal')">+ ছাত্র ভর্তি করুন</button>
    </div>
    <?php
    $enr_list = $pdo->prepare("
        SELECT ce.*,u.name,u.phone,u.email,u.package
        FROM course_enrollments ce
        JOIN users u ON u.id=ce.student_id
        WHERE ce.course_id=? ORDER BY ce.enrolled_at DESC
    ");
    $enr_list->execute([$view_id]);
    $enrolled_list = $enr_list->fetchAll();
    ?>
    <div class="card mb-16">
      <div class="table-wrap">
        <table>
          <thead><tr><th>নাম</th><th>ফোন</th><th>প্যাকেজ</th><th>ভর্তির তারিখ</th><th>মেয়াদ</th><th>Action</th></tr></thead>
          <tbody>
          <?php if(empty($enrolled_list)): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:24px;">কোনো ছাত্র ভর্তি নেই</td></tr>
          <?php else: foreach($enrolled_list as $er): ?>
          <tr>
            <td><strong><?= htmlspecialchars($er['name']) ?></strong></td>
            <td class="text-sm"><?= htmlspecialchars($er['phone']) ?></td>
            <td><span class="badge badge-<?= $er['package']??'basic' ?>"><?= pkg_name($er['package']??'basic') ?></span></td>
            <td class="text-sm text-muted"><?= date('d M Y',strtotime($er['enrolled_at'])) ?></td>
            <td class="text-sm text-muted"><?= $er['expires_at'] ? date('d M Y',strtotime($er['expires_at'])) : 'সীমাহীন' ?></td>
            <td><a href="../api/enrollment.php?action=remove&id=<?= $er['id'] ?>&cid=<?= $view_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('বাতিল করবেন?')">🗑</a></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ENROLL MODAL -->
    <div class="modal-overlay" id="enrollModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>👨‍🎓 ছাত্র ভর্তি করুন</h3><button class="modal-close" onclick="closeModal('enrollModal')">✕</button></div>
        <div class="modal-body">
          <form action="../api/enrollment.php" method="POST">
            <input type="hidden" name="action" value="enroll">
            <input type="hidden" name="course_id" value="<?= $view_id ?>">
            <div class="form-group"><label>ছাত্র বেছে নিন *</label>
              <select name="student_id" required>
                <option value="">— বেছে নিন —</option>
                <?php foreach($pdo->query("SELECT id,name,phone FROM users WHERE role='student' ORDER BY name") as $st): ?>
                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?> — <?= htmlspecialchars($st['phone']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label>মেয়াদ শেষ (ঐচ্ছিক)</label><input type="date" name="expires_at" placeholder="খালি রাখলে সীমাহীন"></div>
            <button type="submit" class="btn btn-primary btn-full">✅ ভর্তি করুন</button>
          </form>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- ═══ COURSE LIST ═══ -->
    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <div class="stat"><div class="stat-label">মোট কোর্স</div><div class="stat-value"><?= count($courses) ?></div></div>
      <div class="stat gold"><div class="stat-label">সক্রিয়</div><div class="stat-value"><?= count(array_filter($courses,fn($c)=>$c['is_active'])) ?></div></div>
      <div class="stat info"><div class="stat-label">ভর্তি (মোট)</div><div class="stat-value"><?= array_sum(array_column($courses,'enrolled_count')) ?></div></div>
      <div class="stat danger"><div class="stat-label">Featured</div><div class="stat-value"><?= count(array_filter($courses,fn($c)=>$c['is_featured'])) ?></div></div>
    </div>

    <!-- Category Filter -->
    <div class="cat-filters">
      <button class="cat-filter active" onclick="filterCat('all',this)">সব</button>
      <?php foreach($categories as $cat): ?>
      <button class="cat-filter" onclick="filterCat('<?= $cat['slug'] ?>',this)"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="search-wrap">
      <span class="search-icon">🔍</span>
      <input type="text" placeholder="কোর্সের নাম বা শিক্ষকের নাম দিয়ে খুঁজুন..."
             oninput="searchCourses(this.value)">
    </div>
    <div class="no-result" id="noResult">😕 কোনো কোর্স পাওয়া যায়নি</div>

    <!-- Course Grid -->
    <div class="course-grid" id="courseGrid">
      <?php foreach($courses as $course): ?>
      <div class="course-card <?= $course['is_active']?'':'inactive' ?>"
           data-cat="<?= htmlspecialchars($categories[array_search($course['category_id'],array_column($categories,'id'))]['slug']??'') ?>"
           data-search="<?= strtolower(htmlspecialchars($course['title'].' '.$course['instructor'])) ?>">
        <div class="cc-thumb">
          <span><?= $course['cat_icon']??'📚' ?></span>
          <span class="cat-badge"><?= htmlspecialchars($course['cat_name']) ?></span>
          <?php if($course['is_featured']): ?><span class="feat-badge">⭐ Featured</span><?php endif; ?>
        </div>
        <div class="cc-body">
          <div class="cc-title"><?= htmlspecialchars($course['title']) ?></div>
          <div class="cc-meta">
            <span class="cc-tag tag-model-<?= $course['model'] ?>"><?= $model_bn[$course['model']]??$course['model'] ?></span>
            <span class="cc-tag tag-level-<?= $course['level'] ?>"><?= $level_bn[$course['level']]??$course['level'] ?></span>
          </div>
          <div class="cc-price">
            <?php if($course['is_free']): ?>
              <span class="free">🆓 বিনামূল্যে</span>
            <?php elseif($course['sale_price']): ?>
              <span class="main">৳<?= number_format($course['sale_price']) ?></span>
              <span class="old">৳<?= number_format($course['price']) ?></span>
            <?php else: ?>
              <span class="main">৳<?= number_format($course['price']) ?></span>
            <?php endif; ?>
          </div>
          <div style="font-size:.75rem;color:var(--muted);">
            👨‍🏫 <?= htmlspecialchars($course['instructor']??'') ?>
            <?php if($course['duration']): ?> · ⏱ <?= htmlspecialchars($course['duration']) ?><?php endif; ?>
            · <?= $course['total_lessons'] ?> Lesson
          </div>
        </div>
        <div class="cc-footer">
          <span class="enrolled-count">👨‍🎓 <?= $course['enrolled_count'] ?> ভর্তি</span>
          <div style="display:flex;gap:6px;">
            <a href="courses.php?view=<?= $course['id'] ?>" class="btn btn-outline btn-sm">📝 Module</a>
            <a href="courses.php?action=toggle&id=<?= $course['id'] ?>" class="btn btn-ghost btn-sm"><?= $course['is_active']?'⏸':'▶' ?></a>
            <a href="courses.php?action=delete&id=<?= $course['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($courses)): ?>
      <div style="grid-column:1/-1;" class="empty" style="background:white;border-radius:var(--r-lg);padding:40px;">
        <span class="empty-icon">🎓</span><p>কোনো কোর্স নেই। "+ নতুন কোর্স" বাটনে ক্লিক করুন।</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- ADD COURSE MODAL -->
    <div class="modal-overlay" id="addCourseModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>🎓 নতুন কোর্স যোগ করুন</h3><button class="modal-close" onclick="closeModal('addCourseModal')">✕</button></div>
        <div class="modal-body">
          <form action="courses.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>কোর্সের শিরোনাম *</label><input type="text" name="title" required placeholder="যেমন: Meta Ads মাস্টারক্লাস"></div>
            <div class="form-grid">
              <div class="form-group"><label>ক্যাটাগরি *</label>
                <select name="category_id" required>
                  <option value="">— বেছে নিন —</option>
                  <?php foreach($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>শিক্ষকের নাম</label><input type="text" name="instructor" placeholder="Abdullah Raiyan"></div>
            </div>
            <div class="form-group"><label>বিবরণ</label><textarea name="description" placeholder="কোর্সটি কী, কী শিখবে..." style="min-height:70px;"></textarea></div>
            <div class="form-grid">
              <div class="form-group"><label>Model</label>
                <select name="model">
                  <option value="self_paced">🎬 Self-paced</option>
                  <option value="live">🔴 Live Class</option>
                  <option value="cohort">📅 Cohort</option>
                  <option value="subscription">🔄 Subscription</option>
                </select>
              </div>
              <div class="form-group"><label>Level</label>
                <select name="level">
                  <option value="beginner">🌱 Beginner</option>
                  <option value="intermediate">🌿 Intermediate</option>
                  <option value="advanced">🌳 Advanced</option>
                </select>
              </div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>মূল্য (৳)</label><input type="number" name="price" placeholder="2999" min="0"></div>
              <div class="form-group"><label>Sale মূল্য (৳)</label><input type="number" name="sale_price" placeholder="খালি রাখুন যদি না থাকে" min="0"></div>
            </div>
            <div class="form-group"><label>সময়কাল</label><input type="text" name="duration" placeholder="যেমন: ৪ সপ্তাহ বা ৩ মাস"></div>
            <div style="display:flex;gap:16px;margin-bottom:14px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:400;"><input type="checkbox" name="is_featured" style="width:auto;"> ⭐ Featured</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:400;"><input type="checkbox" name="is_free" style="width:auto;"> 🆓 ফ্রি কোর্স</label>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">✅ কোর্স যোগ করুন</button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>

<div class="bottom-nav"><div class="bottom-nav-inner">
  <a class="bottom-nav-item" href="index.php"><span class="b-icon">📊</span>ড্যাশবোর্ড</a>
  <a class="bottom-nav-item active"><span class="b-icon">🎓</span>কোর্স</a>
  <a class="bottom-nav-item" href="crm.php"><span class="b-icon">🎯</span>CRM</a>
  <a class="bottom-nav-item" href="coupons.php"><span class="b-icon">🎟️</span>কুপন</a>
</div></div>
<div id="toast-container"></div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('active');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('active');}
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));

function toggleModule(id){
  const el=document.getElementById('mod-'+id);
  const arr=document.getElementById('arr-'+id);
  const hidden=el.style.display==='none';
  el.style.display=hidden?'block':'none';
  if(arr) arr.textContent=hidden?'▲':'▼';
}

function openAddLesson(mid,cid){
  document.getElementById('ls_mid').value=mid;
  openModal('addLessonModal');
}

function toggleUrl(type){
  const row=document.getElementById('urlRow');
  row.style.display=['video','pdf','live'].includes(type)?'block':'none';
}

// Category filter
function filterCat(slug,btn){
  document.querySelectorAll('.cat-filter').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.course-card').forEach(card=>{
    card.style.display=(slug==='all'||card.dataset.cat===slug)?'':'none';
  });
  checkNoResult();
}

// Search
function searchCourses(q){
  q=q.toLowerCase().trim();
  document.querySelectorAll('.course-card').forEach(card=>{
    card.style.display=(!q||card.dataset.search.includes(q))?'':'none';
  });
  checkNoResult();
}

function checkNoResult(){
  const vis=[...document.querySelectorAll('.course-card')].filter(c=>c.style.display!=='none').length;
  document.getElementById('noResult').style.display=vis===0?'block':'none';
}

function toast(msg){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
