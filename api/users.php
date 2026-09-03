<?php
require_once '../includes/db.php';
require_role(['admin']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_student') {
    $name=$_POST['name']??''; $email=$_POST['email']??''; $phone=$_POST['phone']??''; $pass=$_POST['password']??'';
    $pkg=$_POST['package']??'basic'; $guardian=$_POST['guardian_phone']??'';
    if (!$name||!$email||!$phone||!$pass) { $_SESSION['flash_err']='সব আবশ্যক ঘর পূরণ করুন'; header('Location: ../admin/index.php'); exit; }
    $ex=$pdo->prepare("SELECT id FROM users WHERE email=?"); $ex->execute([$email]);
    if ($ex->fetch()) { $_SESSION['flash_err']='এই ইমেইল আগেই আছে'; header('Location: ../admin/index.php'); exit; }
    $pdo->prepare("INSERT INTO users (name,email,phone,password,role,package,guardian_phone) VALUES (?,?,?,?,'student',?,?)")
        ->execute([$name,$email,$phone,password_hash($pass,PASSWORD_DEFAULT),$pkg,$guardian]);
    send_telegram("👨‍🎓 *নতুন ছাত্র যোগ!*\n\n👤 {$name}\n📱 {$phone}\n📦 {$pkg}");
    $_SESSION['flash'] = "{$name} যোগ করা হয়েছে ✅";
}

if ($action === 'add_teacher') {
    $name=$_POST['name']??''; $email=$_POST['email']??''; $phone=$_POST['phone']??''; $pass=$_POST['password']??''; $bio=$_POST['bio']??'';
    if (!$name||!$email||!$phone||!$pass) { $_SESSION['flash_err']='সব আবশ্যক ঘর পূরণ করুন'; header('Location: ../admin/index.php'); exit; }
    $ex=$pdo->prepare("SELECT id FROM users WHERE email=?"); $ex->execute([$email]);
    if ($ex->fetch()) { $_SESSION['flash_err']='এই ইমেইল আগেই আছে'; header('Location: ../admin/index.php'); exit; }
    $pdo->prepare("INSERT INTO users (name,email,phone,password,role,bio) VALUES (?,?,?,?,'teacher',?)")
        ->execute([$name,$email,$phone,password_hash($pass,PASSWORD_DEFAULT),$bio]);
    $_SESSION['flash'] = "{$name} শিক্ষক হিসেবে যোগ হয়েছে ✅";
}

if ($action === 'delete') {
    $id=intval($_GET['id']??0);
    $chk=$pdo->prepare("SELECT role FROM users WHERE id=?"); $chk->execute([$id]);
    $row=$chk->fetch();
    if ($row && $row['role']!=='admin') { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]); $_SESSION['flash']='মুছে ফেলা হয়েছে'; }
}

header('Location: ../admin/index.php'); exit;
