<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['student']);
$b       = get_branding();
$__sname = $b['site_name']    ?? 'Taqwim Institute';
$__tag   = $b['site_tagline'] ?? 'Knowledge · Character · Guidance';
$__logo  = !empty($b['site_logo']) ? '../assets/img/'.$b['site_logo'] : '../assets/img/logo.png';

$course_slug = $_GET['course'] ?? '';
$cert_id_get = $_GET['verify'] ?? '';

// Verify mode (public)
if ($cert_id_get) {
    $vs = $pdo->prepare("
        SELECT ct.*,u.name AS student_name,c.title AS course_title,
               cc.name AS cat_name,cc.icon AS cat_icon
        FROM certificates ct
        JOIN users u ON u.id=ct.student_id
        JOIN courses c ON c.id=ct.course_id
        JOIN course_categories cc ON cc.id=c.category_id
        WHERE ct.cert_id=? AND ct.is_valid=1
    ");
    $vs->execute([$cert_id_get]);
    $cert = $vs->fetch();
    if (!$cert) {
        echo '<!DOCTYPE html><html lang="bn"><head><meta charset="UTF-8"><title>Invalid Certificate</title></head><body style="font-family:sans-serif;text-align:center;padding:60px;"><h1 style="color:#dc2626;">❌ Certificate পাওয়া যায়নি</h1><p>এই Certificate ID টি বৈধ নয়।</p></body></html>';
        exit;
    }
    // Show verification page
    ?>
    <!DOCTYPE html>
    <html lang="bn">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Certificate Verified — <?= htmlspecialchars($__sname) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Inter:wght@400;700;800&family=Amiri:wght@400;700&display=swap">
    <style>
    body{font-family:'Hind Siliguri',sans-serif;background:#f0fdf4;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
    .box{background:white;border-radius:20px;padding:36px;max-width:480px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.1);}
    .tick{font-size:4rem;margin-bottom:12px;display:block;}
    h1{font-size:1.4rem;font-weight:800;color:#166534;margin-bottom:6px;}
    .cert-id{font-family:'Inter',sans-serif;background:#f0fdf4;border:1px solid #bbf7d0;padding:8px 16px;border-radius:40px;font-size:.9rem;font-weight:700;color:#166534;display:inline-block;margin:12px 0;}
    .info{text-align:left;background:#f9fafb;border-radius:12px;padding:16px;margin-top:16px;}
    .row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #e5e7eb;font-size:.875rem;}
    .row:last-child{border-bottom:none;}
    .row span:first-child{color:#6b7280;}
    .row span:last-child{font-weight:700;color:#0d1117;}
    </style>
    </head>
    <body>
    <div class="box">
      <span class="tick">✅</span>
      <h1>Certificate Verified!</h1>
      <p style="color:#6b7280;font-size:.9rem;">এই Certificate টি বৈধ এবং নিশ্চিত।</p>
      <div class="cert-id"><?= htmlspecialchars($cert['cert_id']) ?></div>
      <div class="info">
        <div class="row"><span>ছাত্রের নাম</span><span><?= htmlspecialchars($cert['student_name']) ?></span></div>
        <div class="row"><span>কোর্স</span><span><?= htmlspecialchars($cert['course_title']) ?></span></div>
        <div class="row"><span>প্রদানকারী</span><span><?= htmlspecialchars($__sname) ?></span></div>
        <div class="row"><span>প্রদানের তারিখ</span><span><?= date('d M Y',strtotime($cert['issued_at'])) ?></span></div>
        <div class="row"><span>মেয়াদ</span><span><?= $cert['expires_at'] ? date('d M Y',strtotime($cert['expires_at'])) : 'আজীবন' ?></span></div>
      </div>
      <a href="<?= htmlspecialchars(get_setting('SITE_URL') ?: 'https://arprimemarket.shop') ?>" style="display:inline-block;margin-top:20px;color:#16a34a;font-size:.85rem;">← প্রতিষ্ঠানে ফিরুন</a>
    </div>
    </body></html>
    <?php
    exit;
}

// Student certificate view
if (!$course_slug) { header('Location: index.php'); exit; }

$cs = $pdo->prepare("SELECT c.*,cc.name AS cat_name,cc.icon AS cat_icon FROM courses c JOIN course_categories cc ON cc.id=c.category_id WHERE c.slug=?");
$cs->execute([$course_slug]); $course=$cs->fetch();
if (!$course) { header('Location: index.php'); exit; }

$ct = $pdo->prepare("SELECT * FROM certificates WHERE course_id=? AND student_id=? AND is_valid=1");
$ct->execute([$course['id'],$user['id']]); $cert=$ct->fetch();

// Try to auto-issue if all lessons complete
if (!$cert) {
    $total_q=$pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id=? AND is_active=1");
    $total_q->execute([$course['id']]); $total=$total_q->fetchColumn();
    $done_q=$pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE course_id=? AND student_id=? AND is_completed=1");
    $done_q->execute([$course['id'],$user['id']]); $done=$done_q->fetchColumn();

    if ($total > 0 && $done >= $total) {
        $new_cert_id='TAQWIM-'.strtoupper(substr(md5($user['id'].$course['id'].time()),0,4)).'-'.strtoupper(substr(md5(time().$user['id']),0,4));
        try {
            $pdo->prepare("INSERT INTO certificates (course_id,student_id,cert_id) VALUES (?,?,?)")
                ->execute([$course['id'],$user['id'],$new_cert_id]);
            $ct2=$pdo->prepare("SELECT * FROM certificates WHERE course_id=? AND student_id=?");
            $ct2->execute([$course['id'],$user['id']]); $cert=$ct2->fetch();
        } catch(\PDOException $e) {
            $ct2=$pdo->prepare("SELECT * FROM certificates WHERE course_id=? AND student_id=?");
            $ct2->execute([$course['id'],$user['id']]); $cert=$ct2->fetch();
        }
    }
}

$site_url = defined('SITE_URL') ? SITE_URL : 'https://arprimemarket.shop';
$verify_url = $site_url.'/student/certificate.php?verify='.($cert['cert_id']??'');
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Certificate — <?= htmlspecialchars($course['title']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Inter:wght@300;400;700;800;900&family=Amiri:wght@400;700&display=swap">
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem('taqwim_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
<script src="../assets/js/theme.js" defer></script>
<style>
.cert-page{background:var(--bg);min-height:100vh;padding:20px;}
.cert-topbar{display:flex;align-items:center;gap:12px;margin-bottom:24px;}

/* THE CERTIFICATE */
.certificate{
  max-width:860px;margin:0 auto 28px;
  background:#fffdf5;
  border:1px solid #e5d9b0;
  border-radius:4px;
  position:relative;
  overflow:hidden;
  box-shadow:0 24px 64px rgba(0,0,0,.15);
  font-family:'Inter',sans-serif;
  aspect-ratio:1.414/1;
}

/* Ornamental border */
.cert-border{
  position:absolute;inset:16px;
  border:2.5px solid #c9a227;
  border-radius:2px;
  pointer-events:none;z-index:2;
}
.cert-border::before{
  content:'';position:absolute;inset:6px;
  border:1px solid rgba(201,162,39,.3);
  border-radius:1px;
}

/* Watermark */
.cert-watermark{
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  font-family:'Amiri',serif;
  font-size:min(220px,28vw);
  color:rgba(201,162,39,.04);
  pointer-events:none;z-index:0;
  line-height:1;user-select:none;
}

/* Corner ornaments */
.cert-corner{position:absolute;font-size:1.4rem;color:rgba(201,162,39,.4);z-index:2;}
.cert-corner.tl{top:28px;left:28px;}
.cert-corner.tr{top:28px;right:28px;}
.cert-corner.bl{bottom:28px;left:28px;}
.cert-corner.br{bottom:28px;right:28px;}

/* Content */
.cert-content{
  position:relative;z-index:1;
  display:flex;flex-direction:column;align-items:center;
  justify-content:center;height:100%;
  padding:min(40px,5%);text-align:center;
}
.cert-institution{
  font-family:'Amiri',serif;
  font-size:clamp(.8rem,1.8vw,1.3rem);
  color:#c9a227;font-weight:700;
  letter-spacing:.12em;
  text-transform:uppercase;
  margin-bottom:min(8px,1.5%);
}
.cert-title-main{
  font-family:'Inter',sans-serif;
  font-size:clamp(1.2rem,3vw,2.2rem);
  font-weight:900;
  color:#0a0f1e;
  letter-spacing:-.01em;
  margin-bottom:min(12px,2%);
}
.cert-divider{
  width:min(200px,30%);height:2px;
  background:linear-gradient(90deg,transparent,#c9a227,transparent);
  margin:0 auto min(14px,2%);
}
.cert-presented{
  font-size:clamp(.65rem,1.4vw,.95rem);
  color:#6b7280;font-weight:400;
  margin-bottom:min(8px,1.5%);
}
.cert-student{
  font-family:'Amiri',serif;
  font-size:clamp(1.2rem,3vw,2.4rem);
  font-weight:700;color:#0d1f12;
  margin-bottom:min(10px,1.5%);
  font-style:italic;
}
.cert-course-label{
  font-size:clamp(.6rem,1.2vw,.8rem);color:#6b7280;
  text-transform:uppercase;letter-spacing:.1em;
  margin-bottom:min(4px,1%);
}
.cert-course{
  font-size:clamp(.85rem,2vw,1.3rem);
  font-weight:700;color:#166534;
  margin-bottom:min(16px,2.5%);
  max-width:80%;
}
.cert-meta-row{
  display:flex;gap:min(32px,5%);align-items:flex-end;
  margin-top:min(12px,2%);
}
.cert-meta-item{text-align:center;}
.cert-meta-value{
  font-size:clamp(.65rem,1.3vw,.85rem);
  font-weight:700;color:#0d1f12;
  border-top:1.5px solid #0d1f12;
  padding-top:6px;margin-top:4px;
  min-width:min(100px,15vw);
}
.cert-meta-label{font-size:clamp(.55rem,1vw,.7rem);color:#9ca3af;}
.cert-id-box{
  font-size:clamp(.5rem,.9vw,.65rem);
  color:#9ca3af;margin-top:min(10px,1.5%);
  font-family:'Inter',sans-serif;letter-spacing:.04em;
}

/* Action buttons */
.cert-actions{
  display:flex;gap:12px;justify-content:center;
  flex-wrap:wrap;margin-bottom:20px;
}

/* No cert */
.no-cert{
  max-width:540px;margin:0 auto;
  text-align:center;padding:48px 28px;
  background:var(--surface);border-radius:var(--r-xl);
  border:1px solid var(--border);box-shadow:var(--shadow-sm);
}

@media print {
  .cert-topbar,.cert-actions,.no-cert-actions { display:none !important; }
  .cert-page { padding: 0; background: white; }
  .certificate { box-shadow: none; max-width: 100%; margin: 0; }
}
</style>
</head>
<body>
<div class="cert-page">
  <!-- Top bar -->
  <div class="cert-topbar" style="max-width:900px;margin:0 auto 20px;">
    <a href="course.php?slug=<?= urlencode($course_slug) ?>" class="btn btn-ghost btn-sm">← কোর্সে ফিরুন</a>
    <span style="flex:1;font-size:.9rem;font-weight:700;color:var(--ink);">🎓 Certificate</span>
    <button class="theme-toggle" onclick="toggleTheme()"></button>
    <span class="theme-icon" onclick="toggleTheme()" style="cursor:pointer;font-size:.9rem;">🌙</span>
  </div>

  <?php if ($cert): ?>

  <!-- THE CERTIFICATE -->
  <div class="certificate" id="certificate">
    <div class="cert-watermark">﷽</div>
    <div class="cert-border"></div>
    <span class="cert-corner tl">✦</span>
    <span class="cert-corner tr">✦</span>
    <span class="cert-corner bl">✦</span>
    <span class="cert-corner br">✦</span>

    <div class="cert-content">
      <div class="cert-institution"><?= htmlspecialchars($__sname) ?></div>
      <div style="font-size:clamp(.55rem,1.1vw,.75rem);color:#9ca3af;letter-spacing:.2em;text-transform:uppercase;margin-bottom:min(14px,2%);">
        <?= htmlspecialchars($__tag) ?>
      </div>

      <div class="cert-title-main">Certificate of Completion</div>
      <div class="cert-divider"></div>

      <div class="cert-presented">এই সার্টিফিকেট প্রদান করা হচ্ছে</div>
      <div class="cert-student"><?= htmlspecialchars($user['name']) ?></div>

      <div class="cert-course-label">সফলভাবে সম্পন্ন করেছেন</div>
      <div class="cert-course"><?= $course['cat_icon']??'📚' ?> <?= htmlspecialchars($course['title']) ?></div>

      <div class="cert-meta-row">
        <div class="cert-meta-item">
          <div class="cert-meta-label">প্রদানের তারিখ</div>
          <div class="cert-meta-value"><?= date('d M Y',strtotime($cert['issued_at'])) ?></div>
        </div>
        <div style="font-size:clamp(1rem,2vw,1.5rem);color:#c9a227;align-self:center;">✦</div>
        <div class="cert-meta-item">
          <div class="cert-meta-label">Certificate ID</div>
          <div class="cert-meta-value" style="font-family:'Inter',monospace;font-size:clamp(.55rem,1vw,.7rem);"><?= htmlspecialchars($cert['cert_id']) ?></div>
        </div>
        <div style="font-size:clamp(1rem,2vw,1.5rem);color:#c9a227;align-self:center;">✦</div>
        <div class="cert-meta-item">
          <div class="cert-meta-label">স্বাক্ষরিত</div>
          <div class="cert-meta-value" style="font-family:'Amiri',serif;font-style:italic;">AR Qudrix</div>
        </div>
      </div>

      <div class="cert-id-box">
        Verify: <?= htmlspecialchars($verify_url) ?>
      </div>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="cert-actions">
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Download PDF</button>
    <button class="btn btn-gold" onclick="copyVerifyLink()">🔗 Verify Link কপি করুন</button>
    <a href="<?= htmlspecialchars($verify_url) ?>" target="_blank" class="btn btn-outline">✅ Verify করুন</a>
    <a href="index.php" class="btn btn-ghost">← Dashboard</a>
  </div>

  <div style="text-align:center;font-size:.78rem;color:var(--muted);max-width:540px;margin:0 auto;">
    💡 Print বাটনে ক্লিক করে PDF হিসেবে Save করুন। Verify link দিয়ে যেকেউ এই Certificate-এর সত্যতা যাচাই করতে পারবেন।
  </div>

  <?php else: ?>

  <!-- No certificate yet -->
  <div class="no-cert">
    <div style="font-size:3rem;margin-bottom:14px;">🎓</div>
    <h2 style="font-size:1.2rem;font-weight:700;color:var(--ink);margin-bottom:8px;">Certificate এখনো পাননি</h2>
    <p style="color:var(--muted);margin-bottom:20px;font-size:.9rem;">
      সব Lesson সম্পন্ন করলে Certificate automatically issue হবে।
    </p>
    <?php
    $total_q=$pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id=? AND is_active=1");
    $total_q->execute([$course['id']]); $total=$total_q->fetchColumn();
    $done_q=$pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE course_id=? AND student_id=? AND is_completed=1");
    $done_q->execute([$course['id'],$user['id']]); $done=$done_q->fetchColumn();
    $pct = $total > 0 ? round($done/$total*100) : 0;
    ?>
    <div style="margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:6px;">
        <span style="color:var(--muted);">অগ্রগতি</span>
        <span style="font-weight:700;color:var(--p600);"><?=$done?>/<?=$total?> Lesson (<?=$pct?>%)</span>
      </div>
      <div class="progress-wrap" style="height:10px;">
        <div class="progress-bar" style="width:<?=$pct?>%"></div>
      </div>
    </div>
    <a href="course.php?slug=<?= urlencode($course_slug) ?>" class="btn btn-primary">📚 কোর্স চালিয়ে যান →</a>
  </div>

  <?php endif; ?>
</div>

<div id="toast-container"></div>
<script>
function copyVerifyLink() {
  const url = '<?= addslashes($verify_url) ?>';
  navigator.clipboard.writeText(url).then(()=>{
    toast('✅ Verify link copied!');
  });
}
function toast(msg){const el=document.createElement('div');el.className='toast';el.textContent=msg;document.getElementById('toast-container').appendChild(el);setTimeout(()=>el.remove(),3000);}
</script>
</body>
</html>
