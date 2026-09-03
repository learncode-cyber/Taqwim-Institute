<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['admin']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

$tab = $_GET['tab'] ?? 'quizzes';
$view_quiz = intval($_GET['view_quiz'] ?? 0);

// Load data
try {
    $courses = $pdo->query("SELECT id,title,slug FROM courses WHERE is_active=1 ORDER BY title")->fetchAll();
    $quizzes = $pdo->query("
        SELECT q.*,c.title AS course_title,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=q.id) AS q_count,
        (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=q.id) AS attempt_count
        FROM quizzes q JOIN courses c ON c.id=q.course_id
        ORDER BY q.created_at DESC
    ")->fetchAll();
    $assignments = $pdo->query("
        SELECT a.*,c.title AS course_title,u.name AS teacher_name,
        (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id=a.id) AS sub_count
        FROM assignments a
        JOIN courses c ON c.id=a.course_id
        JOIN users u ON u.id=a.teacher_id
        ORDER BY a.created_at DESC
    ")->fetchAll();
} catch(Exception $e) {
    $courses=[]; $quizzes=[]; $assignments=[];
}

// Load quiz detail
$quiz_detail = null; $questions = [];
if ($view_quiz) {
    $qd=$pdo->prepare("SELECT q.*,c.title AS course_title FROM quizzes q JOIN courses c ON c.id=q.course_id WHERE q.id=?");
    $qd->execute([$view_quiz]); $quiz_detail=$qd->fetch();
    if ($quiz_detail) {
        $qs=$pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY sort_order");
        $qs->execute([$view_quiz]); $questions=$qs->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Quiz & Assignment — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
<script src="../assets/js/theme.js" defer></script>
<style>
.q-row{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:16px 18px;margin-bottom:10px;transition:all .2s;}
.q-row:hover{box-shadow:var(--shadow-sm);}
.q-num-badge{width:32px;height:32px;border-radius:50%;background:var(--p100);color:var(--p600);font-weight:700;font-size:.82rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.opt-row{display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.82rem;}
.opt-key{width:24px;height:24px;border-radius:50%;background:var(--surface-3);font-weight:700;font-size:.72rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.opt-key.correct{background:rgba(22,163,74,.15);color:#166534;}
.opt-correct-mark{color:#16a34a;font-size:.7rem;font-weight:700;margin-left:4px;}
</style>
</head>
<body>
<div class="app">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark"><img src="<?= htmlspecialchars($__logo) ?>" alt="" style="width:28px;height:28px;object-fit:contain;"></div>
    <div class="logo-text"><strong><?= htmlspecialchars($__sname) ?></strong><small>অ্যাডমিন</small></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">ম্যানেজমেন্ট</div>
    <a class="nav-link" href="index.php"><span class="nav-icon">📊</span>Dashboard</a>
    <a class="nav-link" href="courses.php"><span class="nav-icon">🎓</span>কোর্সসমূহ</a>
    <a class="nav-link active"><span class="nav-icon">🧠</span>Quiz & Assignment</a>
    <a class="nav-link" href="crm.php"><span class="nav-icon">🎯</span>CRM</a>
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
      <span class="page-title">
        <?php if($quiz_detail): ?>
          <a href="quiz.php?tab=quizzes" style="color:var(--muted);text-decoration:none;">🧠 Quiz</a> → <?= htmlspecialchars($quiz_detail['title']) ?>
        <?php else: ?>
          🧠 Quiz & Assignment
        <?php endif; ?>
      </span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()"></button>
      <span class="theme-icon" onclick="toggleTheme()" style="cursor:pointer;font-size:.9rem;">🌙</span>
      <?php if(!$quiz_detail): ?>
      <button class="btn btn-primary btn-sm always" onclick="openModal('addQuizModal')">+ নতুন Quiz</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):    ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err):?><div class="alert alert-danger  mb-12">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

    <?php if($quiz_detail): ?>
    <!-- ═══ QUIZ DETAIL ═══ -->
    <div class="card mb-16">
      <div class="card-body" style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
        <div style="font-size:2.5rem;">🧠</div>
        <div style="flex:1;">
          <h2 style="font-size:1.1rem;margin-bottom:4px;"><?= htmlspecialchars($quiz_detail['title']) ?></h2>
          <div style="font-size:.8rem;color:var(--muted);">📚 <?= htmlspecialchars($quiz_detail['course_title']) ?></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <span class="badge badge-active">Pass: <?= $quiz_detail['pass_mark'] ?>%</span>
          <span class="badge badge-pending">Max attempts: <?= $quiz_detail['max_attempts'] ?></span>
          <?php if($quiz_detail['time_limit']): ?><span class="badge badge-done">⏱ <?= $quiz_detail['time_limit'] ?> মি.</span><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <div class="stat"><div class="stat-label">প্রশ্ন</div><div class="stat-value"><?= count($questions) ?></div></div>
      <div class="stat gold"><div class="stat-label">মোট নম্বর</div><div class="stat-value"><?= array_sum(array_column($questions,'marks')) ?></div></div>
      <div class="stat info"><div class="stat-label">Attempts</div><div class="stat-value">
        <?php $ac=$pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=?");$ac->execute([$view_quiz]);echo $ac->fetchColumn(); ?>
      </div></div>
      <div class="stat teal"><div class="stat-label">Pass rate</div><div class="stat-value">
        <?php
        $tc=$pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=?");$tc->execute([$view_quiz]);$total_att=$tc->fetchColumn();
        $pc=$pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND passed=1");$pc->execute([$view_quiz]);$pass_att=$pc->fetchColumn();
        echo $total_att>0?round($pass_att/$total_att*100).'%':'—';
        ?>
      </div></div>
    </div>

    <!-- Questions List -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);">📋 প্রশ্নসমূহ</h2>
      <button class="btn btn-primary btn-sm" onclick="openModal('addQModal')">+ প্রশ্ন যোগ করুন</button>
    </div>

    <?php if(empty($questions)): ?>
    <div class="empty" style="background:var(--surface);border-radius:var(--r-lg);padding:40px;">
      <span class="empty-icon">❓</span><p>কোনো প্রশ্ন নেই। "+ প্রশ্ন যোগ করুন" ক্লিক করুন।</p>
    </div>
    <?php else: foreach($questions as $qi=>$q): ?>
    <div class="q-row">
      <div style="display:flex;gap:12px;align-items:flex-start;">
        <div class="q-num-badge"><?= $qi+1 ?></div>
        <div style="flex:1;">
          <div style="font-size:.9rem;font-weight:600;color:var(--ink);margin-bottom:8px;"><?= htmlspecialchars($q['question']) ?></div>
          <?php if($q['type']==='mcq'): ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;">
            <?php foreach(['a','b','c','d'] as $k): if(!$q["option_{$k}"]) continue; $is_correct=($q['correct']===$k); ?>
            <div class="opt-row">
              <div class="opt-key <?= $is_correct?'correct':'' ?>"><?= strtoupper($k) ?></div>
              <span style="color:<?=$is_correct?'#166534':'var(--muted)'?>;font-weight:<?=$is_correct?'700':'400'?>;"><?= htmlspecialchars($q["option_{$k}"]) ?></span>
              <?php if($is_correct): ?><span class="opt-correct-mark">✓ সঠিক</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php elseif($q['type']==='true_false'): ?>
          <div class="opt-row">
            <span class="badge <?= $q['correct']==='true'?'badge-active':'badge-cancelled' ?>">
              সঠিক উত্তর: <?= $q['correct']==='true'?'সত্য (True)':'মিথ্যা (False)' ?>
            </span>
          </div>
          <?php else: ?>
          <div style="font-size:.78rem;color:var(--muted);">Short answer type</div>
          <?php endif; ?>
          <?php if($q['explanation']): ?>
          <div style="margin-top:6px;font-size:.78rem;color:#1d4ed8;background:rgba(30,144,255,.06);padding:6px 10px;border-radius:var(--r-xs);">💡 <?= htmlspecialchars($q['explanation']) ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;">
          <span class="badge badge-pending"><?= $q['marks'] ?> নম্বর</span>
          <a href="?view_quiz=<?=$view_quiz?>&del_q=<?=$q['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- DELETE QUESTION action -->
    <?php if(intval($_GET['del_q']??0) && $view_quiz): ?>
    <?php $pdo->prepare("DELETE FROM quiz_questions WHERE id=? AND quiz_id=?")->execute([intval($_GET['del_q']),$view_quiz]); header("Location: quiz.php?view_quiz={$view_quiz}"); exit; ?>
    <?php endif; ?>

    <!-- ADD QUESTION MODAL -->
    <div class="modal-overlay" id="addQModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>❓ নতুন প্রশ্ন যোগ করুন</h3><button class="modal-close" onclick="closeModal('addQModal')">✕</button></div>
        <div class="modal-body">
          <div id="qFormArea">
            <div class="form-group">
              <label>প্রশ্নের ধরন</label>
              <select id="qType" onchange="renderQForm()">
                <option value="mcq">MCQ (Multiple Choice)</option>
                <option value="true_false">True / False</option>
                <option value="short">Short Answer</option>
              </select>
            </div>
            <div class="form-group"><label>প্রশ্ন *</label><textarea id="qText" placeholder="প্রশ্ন লিখুন..." style="min-height:80px;"></textarea></div>
            <div id="optionsArea"></div>
            <div class="form-grid">
              <div class="form-group"><label>নম্বর</label><input type="number" id="qMarks" value="1" min="1"></div>
              <div class="form-group"><label>ক্রম</label><input type="number" id="qSort" value="<?= count($questions)+1 ?>" min="1"></div>
            </div>
            <div class="form-group"><label>ব্যাখ্যা (ঐচ্ছিক)</label><input type="text" id="qExp" placeholder="সঠিক উত্তরের ব্যাখ্যা..."></div>
            <button class="btn btn-primary btn-full" onclick="addQuestion()">✅ প্রশ্ন যোগ করুন</button>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- ═══ QUIZ LIST ═══ -->
    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;background:var(--surface);padding:8px;border-radius:var(--r-lg);border:1px solid var(--border);">
      <button class="btn <?= $tab==='quizzes'?'btn-primary':'btn-ghost' ?> btn-sm" onclick="location.href='quiz.php?tab=quizzes'">🧠 Quiz (<?= count($quizzes) ?>)</button>
      <button class="btn <?= $tab==='assignments'?'btn-primary':'btn-ghost' ?> btn-sm" onclick="location.href='quiz.php?tab=assignments'">📝 Assignment (<?= count($assignments) ?>)</button>
    </div>

    <?php if($tab==='quizzes'): ?>
    <!-- Quiz list -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);">🧠 Quiz তালিকা</h2>
      <button class="btn btn-primary btn-sm" onclick="openModal('addQuizModal')">+ নতুন Quiz</button>
    </div>
    <?php if(empty($quizzes)): ?>
    <div class="empty" style="background:var(--surface);border-radius:var(--r-lg);padding:40px;"><span class="empty-icon">🧠</span><p>কোনো Quiz নেই।</p></div>
    <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>শিরোনাম</th><th>কোর্স</th><th>প্রশ্ন</th><th>Pass Mark</th><th>Attempts</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($quizzes as $qz): ?>
          <tr>
            <td><strong><?= htmlspecialchars($qz['title']) ?></strong></td>
            <td class="text-sm text-muted"><?= htmlspecialchars($qz['course_title']) ?></td>
            <td><span class="badge badge-active"><?= $qz['q_count'] ?> প্রশ্ন</span></td>
            <td><?= $qz['pass_mark'] ?>%</td>
            <td><?= $qz['attempt_count'] ?> বার</td>
            <td>
              <a href="quiz.php?view_quiz=<?= $qz['id'] ?>" class="btn btn-outline btn-sm">📝 প্রশ্ন</a>
              <a href="?del_quiz=<?= $qz['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Assignment list -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <h2 style="font-size:.95rem;font-weight:700;color:var(--ink);">📝 Assignment তালিকা</h2>
      <button class="btn btn-primary btn-sm" onclick="openModal('addAssModal')">+ নতুন Assignment</button>
    </div>
    <?php if(empty($assignments)): ?>
    <div class="empty" style="background:var(--surface);border-radius:var(--r-lg);padding:40px;"><span class="empty-icon">📝</span><p>কোনো Assignment নেই।</p></div>
    <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>শিরোনাম</th><th>কোর্স</th><th>Deadline</th><th>সর্বোচ্চ নম্বর</th><th>Submit</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($assignments as $ass): ?>
          <tr>
            <td><strong><?= htmlspecialchars($ass['title']) ?></strong></td>
            <td class="text-sm text-muted"><?= htmlspecialchars($ass['course_title']) ?></td>
            <td class="text-sm"><?= $ass['due_date'] ? date('d M Y',strtotime($ass['due_date'])) : '—' ?></td>
            <td><?= $ass['max_marks'] ?></td>
            <td><span class="badge badge-pending"><?= $ass['sub_count'] ?> জমা</span></td>
            <td><a href="?del_ass=<?= $ass['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('মুছবেন?')">🗑</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; // tab ?>

    <!-- Handle deletes -->
    <?php
    if(intval($_GET['del_quiz']??0)){$pdo->prepare("DELETE FROM quizzes WHERE id=?")->execute([intval($_GET['del_quiz'])]);header('Location: quiz.php?tab=quizzes');exit;}
    if(intval($_GET['del_ass']??0)){$pdo->prepare("DELETE FROM assignments WHERE id=?")->execute([intval($_GET['del_ass'])]);header('Location: quiz.php?tab=assignments');exit;}
    ?>

    <!-- ADD QUIZ MODAL -->
    <div class="modal-overlay" id="addQuizModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>🧠 নতুন Quiz তৈরি করুন</h3><button class="modal-close" onclick="closeModal('addQuizModal')">✕</button></div>
        <div class="modal-body">
          <div id="quizCreateArea">
            <div class="form-group"><label>Quiz শিরোনাম *</label><input type="text" id="qzTitle" placeholder="যেমন: Module 1 Quiz"></div>
            <div class="form-group">
              <label>কোর্স *</label>
              <select id="qzCourse">
                <option value="">— বেছে নিন —</option>
                <?php foreach($courses as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['title'])?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label>বিবরণ</label><textarea id="qzDesc" style="min-height:60px;"></textarea></div>
            <div class="form-grid">
              <div class="form-group"><label>সময়সীমা (মিনিট)</label><input type="number" id="qzTime" placeholder="খালি = সীমাহীন" min="1"></div>
              <div class="form-group"><label>Pass Mark (%)</label><input type="number" id="qzPass" value="60" min="1" max="100"></div>
            </div>
            <div class="form-group"><label>সর্বোচ্চ Attempt</label><input type="number" id="qzMaxAtt" value="3" min="1"></div>
            <button class="btn btn-primary btn-full" onclick="createQuiz()">✅ Quiz তৈরি করুন</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD ASSIGNMENT MODAL -->
    <div class="modal-overlay" id="addAssModal">
      <div class="modal-box"><div class="modal-drag"></div>
        <div class="modal-head"><h3>📝 নতুন Assignment তৈরি করুন</h3><button class="modal-close" onclick="closeModal('addAssModal')">✕</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Assignment শিরোনাম *</label><input type="text" id="assTitle"></div>
          <div class="form-group">
            <label>কোর্স *</label>
            <select id="assCourse">
              <option value="">— বেছে নিন —</option>
              <?php foreach($courses as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['title'])?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>বিবরণ ও নির্দেশনা</label><textarea id="assDesc" style="min-height:80px;"></textarea></div>
          <div class="form-grid">
            <div class="form-group"><label>Deadline</label><input type="datetime-local" id="assDue"></div>
            <div class="form-group"><label>সর্বোচ্চ নম্বর</label><input type="number" id="assMark" value="100"></div>
          </div>
          <button class="btn btn-primary btn-full" onclick="createAssignment()">✅ Assignment তৈরি করুন</button>
        </div>
      </div>
    </div>
    <?php endif; // view_quiz ?>

  </div>
</div>
</div>

<div class="bottom-nav"><div class="bottom-nav-inner">
  <a class="bottom-nav-item" href="index.php"><span class="b-icon">📊</span>Dashboard</a>
  <a class="bottom-nav-item" href="courses.php"><span class="b-icon">🎓</span>কোর্স</a>
  <a class="bottom-nav-item active"><span class="b-icon">🧠</span>Quiz</a>
  <a class="bottom-nav-item" href="crm.php"><span class="b-icon">🎯</span>CRM</a>
</div></div>
<div id="toast-container"></div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('active');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('active');}
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));
function toast(msg,t='success'){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}

// Question form
function renderQForm() {
  const type = document.getElementById('qType').value;
  let html = '';
  if (type==='mcq') {
    html = ['a','b','c','d'].map(k=>`
      <div class="form-group">
        <label>Option ${k.toUpperCase()}</label>
        <input type="text" id="opt_${k}" placeholder="Option ${k.toUpperCase()}">
      </div>`).join('');
    html += `<div class="form-group"><label>সঠিক উত্তর</label>
      <select id="qCorrect">
        <option value="a">A</option><option value="b">B</option>
        <option value="c">C</option><option value="d">D</option>
      </select></div>`;
  } else if (type==='true_false') {
    html = `<div class="form-group"><label>সঠিক উত্তর</label>
      <select id="qCorrect">
        <option value="true">সত্য (True)</option>
        <option value="false">মিথ্যা (False)</option>
      </select></div>`;
  }
  document.getElementById('optionsArea').innerHTML = html;
}
renderQForm();

async function addQuestion() {
  const type = document.getElementById('qType').value;
  const qtext= document.getElementById('qText').value.trim();
  const marks= document.getElementById('qMarks').value;
  const sort = document.getElementById('qSort').value;
  const exp  = document.getElementById('qExp').value.trim();
  const cor  = document.getElementById('qCorrect')?.value || '';
  if (!qtext) { toast('প্রশ্ন লিখুন','danger'); return; }

  const fd = new FormData();
  fd.append('action','add_question');
  fd.append('quiz_id', <?= $view_quiz ?>);
  fd.append('question', qtext);
  fd.append('type', type);
  fd.append('correct', cor);
  fd.append('marks', marks);
  fd.append('sort_order', sort);
  fd.append('explanation', exp);
  if (type==='mcq') {
    ['a','b','c','d'].forEach(k=>{
      fd.append('option_'+k, document.getElementById('opt_'+k)?.value||'');
    });
  }
  const r = await fetch('../api/quiz.php',{method:'POST',body:fd});
  const d = await r.json();
  if(d.ok){toast('✅ প্রশ্ন যোগ হয়েছে');closeModal('addQModal');setTimeout(()=>location.reload(),800);}
  else toast('সমস্যা হয়েছে','danger');
}

async function createQuiz() {
  const fd = new FormData();
  fd.append('action','create_quiz');
  fd.append('title', document.getElementById('qzTitle').value.trim());
  fd.append('course_id', document.getElementById('qzCourse').value);
  fd.append('description', document.getElementById('qzDesc').value);
  fd.append('time_limit', document.getElementById('qzTime').value||'');
  fd.append('pass_mark', document.getElementById('qzPass').value);
  fd.append('max_attempts', document.getElementById('qzMaxAtt').value);
  if(!fd.get('title')||!fd.get('course_id')){toast('Title ও Course আবশ্যক','danger');return;}
  const r=await fetch('../api/quiz.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('✅ Quiz তৈরি হয়েছে');setTimeout(()=>location.href='quiz.php?view_quiz='+d.quiz_id,900);}
  else toast('সমস্যা হয়েছে','danger');
}

async function createAssignment() {
  const fd = new FormData();
  fd.append('action','create');
  fd.append('title', document.getElementById('assTitle').value.trim());
  fd.append('course_id', document.getElementById('assCourse').value);
  fd.append('description', document.getElementById('assDesc').value);
  fd.append('due_date', document.getElementById('assDue').value.replace('T',' ')+':00');
  fd.append('max_marks', document.getElementById('assMark').value);
  if(!fd.get('title')||!fd.get('course_id')){toast('Title ও Course আবশ্যক','danger');return;}
  const r=await fetch('../api/assignment.php',{method:'POST',body:fd});
  const d=await r.json();
  if(d.ok){toast('✅ Assignment তৈরি হয়েছে');setTimeout(()=>location.href='quiz.php?tab=assignments',900);}
  else toast('সমস্যা হয়েছে','danger');
}
</script>
</body>
</html>
