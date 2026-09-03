<?php
require_once '../includes/db.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if (!$email || !$pass) { $_SESSION['error']='ইমেইল ও পাসওয়ার্ড দিন'; header('Location: ../login.php'); exit; }
    $s = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
    $s->execute([$email]); $user = $s->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $d = ['admin'=>'../admin/index.php','teacher'=>'../teacher/index.php','student'=>'../student/index.php'];
        header('Location:'.($d[$user['role']]??'../login.php'));
    } else {
        $_SESSION['error'] = 'ইমেইল বা পাসওয়ার্ড ভুল।';
        header('Location: ../login.php');
    }
    exit;
}

if ($action === 'logout') { session_destroy(); header('Location: ../login.php'); exit; }

if ($action === 'register') {
    $name     = trim($_POST['name']           ?? '');
    $email    = trim($_POST['email']          ?? '');
    $phone    = trim($_POST['phone']          ?? '');
    $pass     = trim($_POST['password']       ?? '');
    $pkg      = $_POST['package']             ?? 'basic';
    $for      = $_POST['for_whom']            ?? '';
    $guardian = trim($_POST['guardian_phone'] ?? '');

    if (!$name||!$email||!$phone||!$pass) { $_SESSION['error']='সব আবশ্যক ঘর পূরণ করুন'; header('Location: ../register.php'); exit; }
    if (strlen($pass)<6) { $_SESSION['error']='পাসওয়ার্ড কমপক্ষে ৬ অক্ষর'; header('Location: ../register.php'); exit; }

    $ex = $pdo->prepare("SELECT id FROM users WHERE email=?"); $ex->execute([$email]);
    if ($ex->fetch()) { $_SESSION['error']='এই ইমেইলে একাউন্ট আছে'; header('Location: ../register.php'); exit; }

    $pdo->prepare("INSERT INTO users (name,email,phone,password,role,package,guardian_phone) VALUES (?,?,?,?,'student',?,?)")
        ->execute([$name,$email,$phone,password_hash($pass,PASSWORD_DEFAULT),$pkg,$guardian]);
    $new_id = $pdo->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $new_id;
    $_SESSION['role']    = 'student';

    $pnames = ['basic'=>'বেসিক ৳২,০০০','standard'=>'স্ট্যান্ডার্ড ৳৩,২০০','premium'=>'প্রিমিয়াম ৳৩,৮০০'];
    send_telegram("🎉 *নতুন রেজিস্ট্রেশন!*\n\n👤 {$name}\n📱 {$phone}\n📦 ".($pnames[$pkg]??$pkg)."\n👨‍👩‍👧 {$for}\n⏰ ".date('d M Y H:i'));
    header('Location: ../student/index.php'); exit;
}

http_response_code(404);
