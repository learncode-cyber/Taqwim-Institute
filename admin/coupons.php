<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user    = require_role(['admin']);
$brand   = get_branding();
$__logo  = !empty($brand['site_logo']) ? '../assets/img/'.$brand['site_logo'] : '../assets/img/logo.png';
$__sname = $brand['site_name'] ?? 'Taqwim Institute';
$flash     = $_SESSION['flash']     ?? ''; unset($_SESSION['flash']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);

$coupons = $pdo->query("
    SELECT c.*,
      (SELECT COUNT(*) FROM coupon_uses WHERE coupon_id=c.id) AS use_count
    FROM coupons c ORDER BY c.created_at DESC
")->fetchAll();

$uses_recent = $pdo->query("
    SELECT cu.*, c.code, u.name AS student_name
    FROM coupon_uses cu
    JOIN coupons c ON c.id=cu.coupon_id
    JOIN users   u ON u.id=cu.student_id
    ORDER BY cu.used_at DESC LIMIT 15
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>কুপন — <?= htmlspecialchars($__sname) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.coupon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px,1fr));
  gap: 14px; margin-bottom: 24px;
}
.coupon-card {
  background: white;
  border: 2px dashed var(--border);
  border-radius: var(--r-lg);
  padding: 16px 18px;
  position: relative; transition: all .15s;
}
.coupon-card.on  { border-color: var(--p600); }
.coupon-card.off { opacity: .55; }
.cut-line {
  font-size: .62rem; color: var(--border);
  letter-spacing: 3px; margin-bottom: 10px; display: block;
}
.c-code {
  font-size: 1.35rem; font-weight: 700;
  letter-spacing: .12em; font-family: monospace;
  color: var(--p600); margin-bottom: 8px;
}
.coupon-card.off .c-code { color: var(--muted); }
.c-badge {
  display: inline-flex; align-items: center; gap: 5px;
  background: var(--p100); color: var(--p600);
  padding: 4px 12px; border-radius: 20px;
  font-size: .85rem; font-weight: 700; margin-bottom: 10px;
}
.coupon-card.off .c-badge { background: #f3f4f6; color: var(--muted); }
.c-meta { font-size: .78rem; color: var(--muted); line-height: 1.9; }
.c-status-on  { background: #dcfce7; color: #166534; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.c-status-off { background: #fee2e2; color: #991b1b; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.c-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }

/* Quick preset buttons */
.preset-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.preset-btn { background: var(--p50); border: 1.5px solid var(--border); color: var(--p600); padding: 5px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600; cursor: pointer; font-family: var(--font-bn); transition: all .15s; }
.preset-btn:hover { background: var(--p100); border-color: var(--p600); }

/* Live preview box */
.live-preview {
  background: var(--p50); border: 1px solid var(--p600);
  border-radius: var(--r-sm); padding: 12px 14px;
  font-size: .875rem; margin-bottom: 14px; display: none;
}

@media(max-width:600px) {
  .coupon-grid { grid-template-columns: 1fr; }
}
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
      <small>অ্যাডমিন প্যানেল</small>
    </div>
  </div>
  <div class="nav-section">
    <div class="nav-label">ম্যানেজমেন্ট</div>
    <a class="nav-link" href="index.php"><span class="nav-icon">📊</span>ড্যাশবোর্ড</a>
    <div class="nav-label" style="margin-top:8px;">কনফিগারেশন</div>
    <a class="nav-link active"><span class="nav-icon">🎟️</span>কুপন</a>
    <a class="nav-link" href="branding.php"><span class="nav-icon">🎨</span>White Label</a>
    <a class="nav-link" href="index.php?tab=settings"><span class="nav-icon">⚙️</span>সেটিংস</a>
    <a class="nav-link" href="change_password.php"><span class="nav-icon">🔐</span>পাসওয়ার্ড</a>
  </div>
  <div class="sidebar-user">
    <div class="user-row">
      <div class="user-av"><?= mb_substr($user['name'],0,1) ?></div>
      <div class="user-info">
        <strong><?= htmlspecialchars($user['name']) ?></strong>
        <span>Admin</span>
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
      <span class="page-title">🎟️ কুপন ম্যানেজমেন্ট</span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Theme switch" aria-label="Toggle theme"></button>
      <span class="theme-icon" style="font-size:.9rem;cursor:pointer;" onclick="toggleTheme()">🌙</span>
      <button class="btn btn-primary btn-sm always" onclick="openModal('addModal')">+ নতুন কুপন</button>
    </div>
  </div>

  <div class="page-body">
    <?php if($flash):     ?><div class="alert alert-success mb-12">✅ <?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if($flash_err): ?><div class="alert alert-danger  mb-12">❌ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <?php
      $total     = count($coupons);
      $active    = count(array_filter($coupons, fn($c)=>$c['is_active']));
      $total_use = array_sum(array_column($coupons,'use_count'));
      $total_sav = $pdo->query("SELECT COALESCE(SUM(discount),0) FROM coupon_uses")->fetchColumn();
      ?>
      <div class="stat"><div class="stat-label">মোট কুপন</div><div class="stat-value"><?= $total ?></div></div>
      <div class="stat gold"><div class="stat-label">সক্রিয়</div><div class="stat-value"><?= $active ?></div></div>
      <div class="stat info"><div class="stat-label">মোট ব্যবহার</div><div class="stat-value"><?= $total_use ?></div></div>
      <div class="stat danger"><div class="stat-label">মোট সাশ্রয়</div><div class="stat-value" style="font-size:1.2rem;">৳<?= number_format($total_sav) ?></div></div>
    </div>

    <!-- Coupon Cards -->
    <?php if(empty($coupons)): ?>
    <div class="empty" style="background:white;border-radius:var(--r-lg);padding:48px;">
      <span class="empty-icon">🎟️</span>
      <p>কোনো কুপন নেই। উপরে "+ নতুন কুপন" বাটনে ক্লিক করুন।</p>
    </div>
    <?php else: ?>
    <div class="coupon-grid">
      <?php foreach($coupons as $cp): ?>
      <div class="coupon-card <?= $cp['is_active'] ? 'on' : 'off' ?>">
        <span class="cut-line">✂ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─</span>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:4px;">
          <div class="c-code"><?= htmlspecialchars($cp['code']) ?></div>
          <span class="<?= $cp['is_active'] ? 'c-status-on' : 'c-status-off' ?>">
            <?= $cp['is_active'] ? '✅ সক্রিয়' : '❌ বন্ধ' ?>
          </span>
        </div>
        <div class="c-badge">
          🏷️ <?= $cp['type']==='percent' ? $cp['value'].'% ছাড়' : '৳'.number_format($cp['value']).' ছাড়' ?>
        </div>
        <div class="c-meta">
          <?php if($cp['min_amount']>0): ?>
          <span>ন্যূনতম: ৳<?= number_format($cp['min_amount']) ?></span>
          <?php endif; ?>
          <span>ব্যবহার: <?= $cp['use_count'] ?><?= $cp['max_uses']!==null ? ' / '.$cp['max_uses'] : ' (সীমাহীন)' ?></span>
          <?php if($cp['valid_from']):  ?><span>শুরু: <?= date('d M Y',strtotime($cp['valid_from'])) ?></span><?php endif; ?>
          <?php if($cp['valid_until']): ?><span>শেষ: <?= date('d M Y',strtotime($cp['valid_until'])) ?></span><?php endif; ?>
        </div>
        <div class="c-actions">
          <a href="../api/coupon.php?action=toggle&id=<?= $cp['id'] ?>"
             class="btn btn-outline btn-sm">
            <?= $cp['is_active'] ? '⏸ বন্ধ' : '▶ চালু' ?>
          </a>
          <a href="../api/coupon.php?action=delete&id=<?= $cp['id'] ?>"
             class="btn btn-danger btn-sm"
             onclick="return confirm('\'<?= htmlspecialchars($cp['code']) ?>\' মুছবেন?')">🗑</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Usage History -->
    <div class="card">
      <div class="card-head"><h2>📋 সাম্প্রতিক ব্যবহার</h2></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>তারিখ</th><th>কুপন</th><th>ছাত্র</th><th>ছাড়</th></tr>
          </thead>
          <tbody>
            <?php if(empty($uses_recent)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:24px;">এখনো কোনো ব্যবহার নেই</td></tr>
            <?php else: foreach($uses_recent as $u): ?>
            <tr>
              <td class="text-sm text-muted"><?= date('d M, H:i', strtotime($u['used_at'])) ?></td>
              <td><span style="font-family:monospace;font-weight:700;color:var(--p600);"><?= htmlspecialchars($u['code']) ?></span></td>
              <td><?= htmlspecialchars($u['student_name']) ?></td>
              <td style="color:var(--danger);font-weight:700;">-৳<?= number_format($u['discount']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- ADD COUPON MODAL -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-drag"></div>
    <div class="modal-head">
      <h3>🎟️ নতুন কুপন তৈরি করুন</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <div class="modal-body">
      <!-- Quick presets -->
      <div style="margin-bottom:16px;">
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:8px;font-weight:600;">⚡ দ্রুত প্রিসেট:</div>
        <div class="preset-row">
          <button class="preset-btn" onclick="preset(10,'percent')">10% ছাড়</button>
          <button class="preset-btn" onclick="preset(20,'percent')">20% ছাড়</button>
          <button class="preset-btn" onclick="preset(25,'percent')">25% ছাড়</button>
          <button class="preset-btn" onclick="preset(50,'percent')">50% ছাড়</button>
          <button class="preset-btn" onclick="preset(100,'percent')">100% ফ্রি</button>
          <button class="preset-btn" onclick="preset(500,'fixed')">৳৫০০ ছাড়</button>
          <button class="preset-btn" onclick="preset(1000,'fixed')">৳১,০০০ ছাড়</button>
        </div>
      </div>

      <form action="../api/coupon.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label>কুপন কোড <span style="color:var(--danger)">*</span></label>
          <input type="text" name="code" id="codeInput" required
            placeholder="যেমন: EID50 বা RAMADAN30"
            style="text-transform:uppercase;letter-spacing:.1em;font-size:1.1rem;font-weight:700;"
            oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')">
          <div class="input-hint">শুধু A-Z এবং 0-9 ব্যবহার করুন</div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>ছাড়ের ধরন *</label>
            <select name="type" id="typeSelect" onchange="updatePreview()">
              <option value="percent">% পার্সেন্ট</option>
              <option value="fixed">৳ নির্দিষ্ট টাকা</option>
            </select>
          </div>
          <div class="form-group">
            <label>ছাড়ের মান *</label>
            <input type="number" name="value" id="discVal" required
              placeholder="যেমন: 20" min="1" oninput="updatePreview()">
          </div>
        </div>

        <!-- Live Preview -->
        <div class="live-preview" id="livePreview">
          <strong>👁️ প্রিভিউ:</strong>
          <div id="pvContent" style="margin-top:6px;line-height:1.9;"></div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>ন্যূনতম পরিমাণ (৳)</label>
            <input type="number" name="min_amount" placeholder="0 = যেকোনো" min="0">
          </div>
          <div class="form-group">
            <label>সর্বোচ্চ ব্যবহার</label>
            <input type="number" name="max_uses" placeholder="খালি = সীমাহীন" min="1">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>শুরুর তারিখ</label>
            <input type="date" name="valid_from">
          </div>
          <div class="form-group">
            <label>শেষ তারিখ</label>
            <input type="date" name="valid_until">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">✅ কুপন তৈরি করুন</button>
      </form>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('active'); }
function closeSidebar()  { document.getElementById('sidebar').classList.remove('open');  document.getElementById('overlay').classList.remove('active'); }
function openModal(id)   { document.getElementById(id).classList.add('active'); }
function closeModal(id)  { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('active'); }));

function preset(val, type) {
  document.getElementById('typeSelect').value = type;
  document.getElementById('discVal').value    = val;
  updatePreview();
}

function updatePreview() {
  const type = document.getElementById('typeSelect').value;
  const val  = parseFloat(document.getElementById('discVal').value) || 0;
  const pv   = document.getElementById('livePreview');
  const pc   = document.getElementById('pvContent');
  if (!val) { pv.style.display='none'; return; }

  const packages = [{name:'বেসিক',amt:2000},{name:'স্ট্যান্ডার্ড',amt:3200},{name:'প্রিমিয়াম',amt:3800}];
  const rows = packages.map(p => {
    const disc = type==='percent' ? Math.round(p.amt*val/100) : Math.min(val,p.amt);
    const fin  = p.amt - disc;
    return `<div style="display:flex;justify-content:space-between;font-size:.82rem;">
      <span>${p.name} ৳${p.amt.toLocaleString()}</span>
      <span><del style="color:var(--muted)">৳${p.amt.toLocaleString()}</del> →
      <strong style="color:var(--p600)">৳${fin.toLocaleString()}</strong>
      <span style="color:var(--danger);font-size:.75rem;"> (-৳${disc.toLocaleString()})</span></span>
    </div>`;
  });
  pc.innerHTML = rows.join('');
  pv.style.display = 'block';
}
</script>
</body>
</html>
