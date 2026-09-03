<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['student']);
$b       = get_branding();
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';
$__sname = $b['site_name'] ?? 'Taqwim Institute';
$quiz_id = intval($_GET['quiz_id'] ?? 0);
if (!$quiz_id) { header('Location: index.php'); exit; }

$q = $pdo->prepare("SELECT qz.*,c.title AS course_title,c.slug AS course_slug FROM quizzes qz JOIN courses c ON c.id=qz.course_id WHERE qz.id=? AND qz.is_active=1");
$q->execute([$quiz_id]); $quiz=$q->fetch();
if (!$quiz) { header('Location: index.php'); exit; }

// Check enrollment
$enr=$pdo->prepare("SELECT id FROM course_enrollments WHERE course_id=? AND student_id=? AND status='active'");
$enr->execute([$quiz['course_id'],$user['id']]);
if (!$enr->fetch()) { header('Location: index.php'); exit; }

// Attempts used
$att=$pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$att->execute([$quiz_id,$user['id']]); $attempts_used=$att->fetchColumn();

// Best score
$best=$pdo->prepare("SELECT MAX(percentage) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$best->execute([$quiz_id,$user['id']]); $best_score=$best->fetchColumn();

// Questions
$qs=$pdo->prepare("SELECT id,question,type,option_a,option_b,option_c,option_d,marks FROM quiz_questions WHERE quiz_id=? ORDER BY sort_order");
$qs->execute([$quiz_id]); $questions=$qs->fetchAll();
$total_q = count($questions);
$total_marks = array_sum(array_column($questions,'marks'));
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= htmlspecialchars($quiz['title']) ?> — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
<script src="../assets/js/theme.js" defer></script>
<style>
.quiz-wrap{max-width:720px;margin:0 auto;padding:20px;}
.quiz-header{background:var(--surface);border-radius:var(--r-lg);border:1px solid var(--border);padding:22px;margin-bottom:20px;box-shadow:var(--shadow-xs);}
.quiz-title{font-size:1.2rem;font-weight:800;color:var(--ink);margin-bottom:4px;}
.quiz-meta{display:flex;flex-wrap:wrap;gap:10px;font-size:.8rem;color:var(--muted);}
.quiz-stat{display:flex;align-items:center;gap:5px;}
.timer-bar{
  position:sticky;top:60px;z-index:100;
  background:var(--surface);border-bottom:1px solid var(--border);
  padding:10px 20px;display:flex;align-items:center;justify-content:space-between;
  box-shadow:var(--shadow-xs);
}
.timer{font-size:1.1rem;font-weight:700;color:var(--ink);font-family:'Inter',sans-serif;}
.timer.urgent{color:var(--danger);animation:pulse 1s infinite;}
.q-progress{flex:1;margin:0 16px;}
.q-num{font-size:.78rem;color:var(--muted);margin-bottom:5px;}
.q-card{
  background:var(--surface);border-radius:var(--r-lg);
  border:1px solid var(--border);padding:24px;
  margin-bottom:16px;box-shadow:var(--shadow-xs);
  display:none;
}
.q-card.active{display:block;animation:fadeSlideIn .25s ease;}
.q-text{font-size:1rem;font-weight:600;color:var(--ink);line-height:1.6;margin-bottom:18px;}
.q-number{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.options{display:flex;flex-direction:column;gap:10px;}
.option-btn{
  display:flex;align-items:center;gap:13px;
  padding:13px 16px;
  border:2px solid var(--border);
  border-radius:var(--r);
  background:var(--surface);
  cursor:pointer;font-family:var(--font-bn);font-size:.9rem;
  color:var(--body);text-align:left;
  transition:all .2s cubic-bezier(.4,0,.2,1);
}
.option-btn:hover{border-color:var(--p600);background:var(--p50);}
.option-btn.selected{border-color:var(--p600);background:var(--p100);color:var(--p600);font-weight:600;}
.option-btn.correct{border-color:#16a34a;background:rgba(22,163,74,.1);color:#166534;}
.option-btn.wrong{border-color:var(--danger);background:rgba(255,71,87,.08);color:var(--danger);}
.opt-key{width:32px;height:32px;border-radius:50%;background:var(--surface-3);font-weight:700;font-size:.82rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;}
.option-btn.selected .opt-key{background:var(--p600);color:#fff;}
.option-btn.correct .opt-key{background:#16a34a;color:#fff;}
.option-btn.wrong   .opt-key{background:var(--danger);color:#fff;}
.tf-options{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.nav-btns{display:flex;justify-content:space-between;gap:12px;margin-top:18px;}

/* Result screen */
.result-card{background:var(--surface);border-radius:var(--r-xl);border:1px solid var(--border);padding:36px 28px;text-align:center;box-shadow:var(--shadow-md);}
.result-ring{
  width:140px;height:140px;border-radius:50%;
  background:conic-gradient(var(--p600) var(--pct), var(--surface-3) 0);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;position:relative;
  box-shadow:0 0 0 8px var(--p50);
}
.result-ring-inner{
  width:110px;height:110px;border-radius:50%;
  background:var(--surface);display:flex;flex-direction:column;
  align-items:center;justify-content:center;
}
.result-pct{font-size:2rem;font-weight:800;color:var(--ink);font-family:'Inter',sans-serif;}
.result-label{font-size:.7rem;font-weight:600;color:var(--muted);}
.result-badge{display:inline-block;padding:5px 18px;border-radius:var(--r-full);font-size:.875rem;font-weight:700;margin:12px 0;}
.passed-badge{background:rgba(22,163,74,.12);color:#166534;border:1px solid rgba(22,163,74,.2);}
.failed-badge{background:rgba(255,71,87,.12);color:var(--danger);border:1px solid rgba(255,71,87,.2);}
.result-details{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:20px 0;text-align:center;}
.rd{background:var(--surface-2);border-radius:var(--r);padding:12px;}
.rd-num{font-size:1.3rem;font-weight:800;color:var(--ink);font-family:'Inter',sans-serif;}
.rd-lbl{font-size:.72rem;color:var(--muted);}
.explanation{background:rgba(30,144,255,.06);border:1px solid rgba(30,144,255,.15);border-radius:var(--r-sm);padding:10px 14px;font-size:.82rem;color:#1d4ed8;margin-top:8px;text-align:left;}

/* Start screen */
.start-card{background:var(--surface);border-radius:var(--r-xl);border:1px solid var(--border);padding:36px;text-align:center;box-shadow:var(--shadow-md);}
.start-icon{font-size:4rem;margin-bottom:16px;display:block;}
</style>
</head>
<body>
<!-- Topbar -->
<div style="height:56px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:12px;position:sticky;top:0;z-index:200;box-shadow:var(--shadow-xs);">
  <a href="course.php?slug=<?= urlencode($quiz['course_slug']) ?>" style="color:var(--muted);font-size:.85rem;text-decoration:none;">← কোর্সে ফিরুন</a>
  <span style="flex:1;font-size:.9rem;font-weight:700;color:var(--ink);">🧠 <?= htmlspecialchars($quiz['title']) ?></span>
  <button class="theme-toggle" onclick="toggleTheme()"></button>
  <span class="theme-icon" onclick="toggleTheme()" style="cursor:pointer;font-size:.9rem;">🌙</span>
</div>

<div class="quiz-wrap">

  <!-- ═══ START SCREEN ═══ -->
  <div id="startScreen">
    <div class="start-card">
      <span class="start-icon">🧠</span>
      <h1 style="font-size:1.4rem;font-weight:800;color:var(--ink);margin-bottom:8px;"><?= htmlspecialchars($quiz['title']) ?></h1>
      <?php if($quiz['description']): ?>
      <p style="color:var(--muted);margin-bottom:20px;"><?= htmlspecialchars($quiz['description']) ?></p>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin:24px 0;">
        <div class="rd"><div class="rd-num"><?= $total_q ?></div><div class="rd-lbl">প্রশ্ন</div></div>
        <div class="rd"><div class="rd-num"><?= $total_marks ?></div><div class="rd-lbl">মোট নম্বর</div></div>
        <div class="rd"><div class="rd-num"><?= $quiz['pass_mark'] ?>%</div><div class="rd-lbl">পাস মার্ক</div></div>
        <div class="rd"><div class="rd-num"><?= $quiz['time_limit'] ?? '∞' ?><?= $quiz['time_limit'] ? ' মি.' : '' ?></div><div class="rd-lbl">সময়</div></div>
        <div class="rd"><div class="rd-num"><?= $quiz['max_attempts']-$attempts_used ?></div><div class="rd-lbl">Attempt বাকি</div></div>
        <?php if($best_score): ?><div class="rd"><div class="rd-num"><?= $best_score ?>%</div><div class="rd-lbl">সেরা স্কোর</div></div><?php endif; ?>
      </div>

      <?php if($attempts_used >= $quiz['max_attempts'] && $quiz['max_attempts'] > 0): ?>
      <div class="alert alert-danger" style="margin-bottom:16px;">❌ সর্বোচ্চ <?= $quiz['max_attempts'] ?>টি attempt শেষ হয়েছে।</div>
      <a href="course.php?slug=<?= urlencode($quiz['course_slug']) ?>" class="btn btn-outline">← কোর্সে ফিরুন</a>
      <?php else: ?>
      <div class="alert alert-info" style="margin-bottom:20px;text-align:left;">
        ℹ️ Quiz শুরু করলে সময় গণনা শুরু হবে। মাঝখানে বন্ধ করলে attempt নষ্ট হবে না।
      </div>
      <button class="btn btn-primary btn-lg" onclick="startQuiz()" style="min-width:200px;">
        🚀 Quiz শুরু করুন
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══ QUIZ SCREEN ═══ -->
  <div id="quizScreen" style="display:none;">
    <!-- Timer + Progress bar -->
    <div class="timer-bar">
      <div class="timer" id="timerDisplay">
        <?= $quiz['time_limit'] ? sprintf('%02d:%02d', $quiz['time_limit'], 0) : '∞' ?>
      </div>
      <div class="q-progress">
        <div class="q-num" id="qProgressText">প্রশ্ন ১ / <?= $total_q ?></div>
        <div class="progress-wrap"><div class="progress-bar" id="qProgressBar" style="width:0%"></div></div>
      </div>
      <span id="qScoreChip" style="font-size:.78rem;font-weight:700;color:var(--p600);background:var(--p100);padding:4px 10px;border-radius:var(--r-full);">নম্বর: ০</span>
    </div>

    <!-- Questions -->
    <?php foreach($questions as $idx=>$q): ?>
    <div class="q-card" id="q-<?= $idx ?>" data-qid="<?= $q['id'] ?>" data-marks="<?= $q['marks'] ?>">
      <div class="q-number">প্রশ্ন <?= $idx+1 ?> / <?= $total_q ?> &nbsp;·&nbsp; <?= $q['marks'] ?> নম্বর</div>
      <div class="q-text"><?= htmlspecialchars($q['question']) ?></div>

      <?php if($q['type']==='mcq'): ?>
      <div class="options">
        <?php foreach(['a','b','c','d'] as $key): if(!$q["option_{$key}"]) continue; ?>
        <button class="option-btn" data-key="<?= $key ?>"
                onclick="selectOption(this, <?= $idx ?>, '<?= $key ?>')">
          <span class="opt-key"><?= strtoupper($key) ?></span>
          <span><?= htmlspecialchars($q["option_{$key}"]) ?></span>
        </button>
        <?php endforeach; ?>
      </div>

      <?php elseif($q['type']==='true_false'): ?>
      <div class="tf-options">
        <button class="option-btn" data-key="true"  onclick="selectOption(this,<?=$idx?>,'true')">
          <span class="opt-key">✓</span><span>সত্য (True)</span>
        </button>
        <button class="option-btn" data-key="false" onclick="selectOption(this,<?=$idx?>,'false')">
          <span class="opt-key">✗</span><span>মিথ্যা (False)</span>
        </button>
      </div>

      <?php else: ?>
      <textarea class="short-ans" placeholder="আপনার উত্তর লিখুন..."
                oninput="answers[<?=$idx?>]={qid:<?=$q['id']?>,val:this.value}"
                style="width:100%;min-height:80px;padding:12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--font-bn);font-size:.9rem;color:var(--ink);background:var(--surface);outline:none;resize:vertical;"></textarea>
      <?php endif; ?>

      <div class="nav-btns">
        <button class="btn btn-ghost" onclick="prevQ(<?=$idx?>)" <?= $idx===0?'disabled':'' ?>>← আগের</button>
        <?php if($idx===$total_q-1): ?>
        <button class="btn btn-primary" onclick="showSubmitConfirm()">✅ Submit করুন</button>
        <?php else: ?>
        <button class="btn btn-primary" onclick="nextQ(<?=$idx?>)">পরের →</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══ RESULT SCREEN ═══ -->
  <div id="resultScreen" style="display:none;"></div>

</div><!-- /quiz-wrap -->

<!-- Submit confirm modal -->
<div class="modal-overlay" id="submitModal">
  <div class="modal-box"><div class="modal-drag"></div>
    <div class="modal-head"><h3>✅ Quiz Submit করবেন?</h3><button class="modal-close" onclick="closeModal('submitModal')">✕</button></div>
    <div class="modal-body">
      <div id="submitSummary" style="background:var(--surface-2);border-radius:var(--r-sm);padding:14px;margin-bottom:16px;font-size:.875rem;"></div>
      <div class="alert alert-warn" style="margin-bottom:16px;">⚠️ Submit করলে আর পরিবর্তন করা যাবে না।</div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-ghost" onclick="closeModal('submitModal')" style="flex:1;">আরো দেখি</button>
        <button class="btn btn-primary" onclick="submitQuiz()" style="flex:1;">✅ Submit করুন</button>
      </div>
    </div>
  </div>
</div>
<div id="toast-container"></div>

<script>
const QUIZ_ID    = <?= $quiz_id ?>;
const TOTAL_Q    = <?= $total_q ?>;
const TIME_LIMIT = <?= $quiz['time_limit'] ?? 0 ?> * 60; // seconds, 0=unlimited
const PASS_MARK  = <?= $quiz['pass_mark'] ?>;
const COURSE_SLUG= '<?= addslashes($quiz['course_slug']) ?>';
const QUESTIONS  = <?= json_encode($questions) ?>;

let currentQ   = 0;
let answers    = {}; // {idx: {qid, val}}
let startTime  = null;
let timerInt   = null;
let timeLeft   = TIME_LIMIT;
let submitted  = false;

function startQuiz() {
  document.getElementById('startScreen').style.display='none';
  document.getElementById('quizScreen').style.display='block';
  showQ(0);
  startTime = Date.now();
  if (TIME_LIMIT > 0) startTimer();
}

function showQ(idx) {
  document.querySelectorAll('.q-card').forEach(c=>c.classList.remove('active'));
  const el = document.getElementById('q-'+idx);
  if (el) { el.classList.add('active'); currentQ=idx; }
  const pct = Math.round((idx+1)/TOTAL_Q*100);
  document.getElementById('qProgressBar').style.width = pct+'%';
  document.getElementById('qProgressText').textContent = `প্রশ্ন ${idx+1} / ${TOTAL_Q}`;
}

function nextQ(idx) {
  if (idx < TOTAL_Q-1) showQ(idx+1);
}
function prevQ(idx) {
  if (idx > 0) showQ(idx-1);
}

function selectOption(btn, idx, key) {
  const card = document.getElementById('q-'+idx);
  card.querySelectorAll('.option-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');
  const qid = parseInt(card.dataset.qid);
  answers[idx] = {qid, val: key};
  // Score chip update
  const scored = Object.keys(answers).length;
  document.getElementById('qScoreChip').textContent = `উত্তর: ${scored}/${TOTAL_Q}`;
}

// Timer
function startTimer() {
  const display = document.getElementById('timerDisplay');
  timerInt = setInterval(()=>{
    timeLeft--;
    const m = Math.floor(timeLeft/60);
    const s = timeLeft%60;
    display.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (timeLeft <= 60) display.classList.add('urgent');
    if (timeLeft <= 0) { clearInterval(timerInt); submitQuiz(); }
  }, 1000);
}

function showSubmitConfirm() {
  const answered = Object.keys(answers).length;
  const unanswered = TOTAL_Q - answered;
  document.getElementById('submitSummary').innerHTML =
    `<div>✅ উত্তর দেওয়া হয়েছে: <strong>${answered}/${TOTAL_Q}</strong></div>` +
    (unanswered ? `<div style="color:var(--danger);">⚠️ উত্তর দেওয়া হয়নি: ${unanswered}টি</div>` : '');
  openModal('submitModal');
}

async function submitQuiz() {
  if (submitted) return;
  submitted = true;
  closeModal('submitModal');
  if (timerInt) clearInterval(timerInt);
  const timeTaken = Math.round((Date.now()-startTime)/1000);

  // Build answers object by qid
  const ans_by_qid = {};
  Object.values(answers).forEach(a=>{ ans_by_qid[a.qid]=a.val; });

  const fd = new FormData();
  fd.append('action','submit');
  fd.append('quiz_id', QUIZ_ID);
  fd.append('answers', JSON.stringify(ans_by_qid));
  fd.append('time_taken', timeTaken);

  document.getElementById('quizScreen').innerHTML = '<div style="text-align:center;padding:60px;"><div style="font-size:3rem;margin-bottom:16px;">⏳</div><div style="font-size:1rem;font-weight:600;color:var(--ink);">ফলাফল প্রস্তুত হচ্ছে...</div></div>';

  const r = await fetch('../api/quiz.php', {method:'POST',body:fd});
  const d = await r.json();
  showResult(d, timeTaken);
}

function showResult(d, timeTaken) {
  document.getElementById('quizScreen').style.display='none';
  const rs = document.getElementById('resultScreen');
  rs.style.display='block';

  const pct       = d.percentage;
  const passed    = d.passed;
  const conic_pct = pct * 3.6; // degrees

  let reviewHTML = '';
  if (d.results) {
    QUESTIONS.forEach((q,i)=>{
      const res = d.results[q.id];
      if (!res) return;
      const icon = res.is_correct ? '✅' : '❌';
      const opts = ['a','b','c','d'];
      let optHTML = '';
      opts.forEach(k=>{
        if (!q['option_'+k]) return;
        const cls = k===res.correct ? 'correct' : (k===res.given && !res.is_correct ? 'wrong' : '');
        optHTML += `<div class="option-btn ${cls}" style="pointer-events:none;margin-bottom:6px;">
          <span class="opt-key">${k.toUpperCase()}</span>
          <span>${q['option_'+k]}</span>
        </div>`;
      });
      reviewHTML += `
        <div class="q-card active" style="border-left:3px solid ${res.is_correct?'#16a34a':'var(--danger)'};">
          <div class="q-number">${icon} প্রশ্ন ${i+1} · ${q.marks} নম্বর</div>
          <div class="q-text">${q.question}</div>
          <div class="options">${optHTML}</div>
          ${res.explanation?`<div class="explanation">💡 ${res.explanation}</div>`:''}
        </div>`;
    });
  }

  const mins = Math.floor(timeTaken/60);
  const secs = timeTaken%60;
  rs.innerHTML = `
    <div class="result-card" style="margin-bottom:20px;">
      <div class="result-ring" style="--pct:${conic_pct}deg">
        <div class="result-ring-inner">
          <div class="result-pct">${pct}%</div>
          <div class="result-label">স্কোর</div>
        </div>
      </div>
      <h2 style="font-size:1.3rem;font-weight:800;color:var(--ink);margin-bottom:6px;">
        ${passed ? '🎉 অভিনন্দন! পাস করেছেন!' : '😔 আবার চেষ্টা করুন'}
      </h2>
      <span class="result-badge ${passed?'passed-badge':'failed-badge'}">
        ${passed ? '✅ পাস' : '❌ ফেল'} · পাস মার্ক ${d.pass_mark}%
      </span>
      <div class="result-details">
        <div class="rd"><div class="rd-num">${d.score}/${d.total_marks}</div><div class="rd-lbl">নম্বর</div></div>
        <div class="rd"><div class="rd-num">${Object.values(d.results||{}).filter(r=>r.is_correct).length}/${TOTAL_Q}</div><div class="rd-lbl">সঠিক</div></div>
        <div class="rd"><div class="rd-num">${mins}:${String(secs).padStart(2,'0')}</div><div class="rd-lbl">সময় নিয়েছেন</div></div>
      </div>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="course.php?slug=${COURSE_SLUG}" class="btn btn-outline">← কোর্সে ফিরুন</a>
        ${!passed ? '<button class="btn btn-primary" onclick="location.reload()">🔄 আবার চেষ্টা করুন</button>' : ''}
        ${passed ? '<a href="certificate.php?course='+COURSE_SLUG+'" class="btn btn-gold">🎓 Certificate দেখুন</a>' : ''}
      </div>
    </div>
    <h3 style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:14px;">📋 বিস্তারিত উত্তর পর্যালোচনা</h3>
    ${reviewHTML}
  `;
}

function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));
function toast(msg){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
