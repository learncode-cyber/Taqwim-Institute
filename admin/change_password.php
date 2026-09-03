<?php
require_once '../includes/db.php';
require_once '../includes/theme.php';
$user = require_role(['admin']);
$b    = get_branding();
$flash = $flash_err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $cur=$_POST['current']??''; $new=$_POST['new_pass']??''; $conf=$_POST['confirm']??'';
    if (!password_verify($cur,$user['password'])) $flash_err='বর্তমান পাসওয়ার্ড ভুল।';
    elseif (strlen($new)<8) $flash_err='নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষর।';
    elseif ($new!==$conf) $flash_err='নতুন পাসওয়ার্ড মিলছে না।';
    else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$user['id']]);
        $flash='পাসওয়ার্ড পরিবর্তন হয়েছে ✅';
    }
}
?><!DOCTYPE html>
<html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>পাসওয়ার্ড — <?= htmlspecialchars($b['site_name']??'Taqwim') ?></title>
<link rel="stylesheet" href="../assets/css/theme.css.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);}.box{background:white;border-radius:var(--radius-lg);padding:32px 28px;width:100%;max-width:400px;box-shadow:var(--shadow-md);}h2{font-size:1.1rem;margin-bottom:20px;}</style>
</head><body>
<div class="box">
  <h2>🔐 পাসওয়ার্ড পরিবর্তন</h2>
  <?php if($flash):?><div class="alert alert-success mb-12">✅ <?=htmlspecialchars($flash)?></div><?php endif;?>
  <?php if($flash_err):?><div class="alert alert-danger mb-12">❌ <?=htmlspecialchars($flash_err)?></div><?php endif;?>
  <form method="POST">
    <div class="form-group"><label>বর্তমান পাসওয়ার্ড</label><input type="password" name="current" required></div>
    <div class="form-group"><label>নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label><input type="password" name="new_pass" required></div>
    <div class="form-group"><label>নতুন পাসওয়ার্ড নিশ্চিত করুন</label><input type="password" name="confirm" required></div>
    <button type="submit" class="btn btn-primary btn-full">✅ পরিবর্তন করুন</button>
    <a href="index.php" class="btn btn-ghost btn-full" style="margin-top:8px;">← ড্যাশবোর্ড</a>
  </form>
</div></body></html>
