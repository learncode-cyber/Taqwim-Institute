<?php
require_once '../includes/db.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── ENROLL STUDENT (Admin) ──
if ($action === 'enroll') {
    require_role(['admin']);
    $cid = intval($_POST['course_id']  ?? 0);
    $sid = intval($_POST['student_id'] ?? 0);
    $exp = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    if (!$cid || !$sid) { $_SESSION['flash_err']='কোর্স ও ছাত্র আবশ্যক।'; header('Location: ../admin/courses.php'); exit; }
    try {
        $pdo->prepare("INSERT INTO course_enrollments (course_id,student_id,expires_at) VALUES (?,?,?)")
            ->execute([$cid,$sid,$exp]);
        $_SESSION['flash'] = 'ছাত্র ভর্তি সম্পন্ন ✅';
    } catch(\PDOException $e) {
        $_SESSION['flash_err'] = 'এই ছাত্র ইতিমধ্যে ভর্তি আছেন।';
    }
    header('Location: ../admin/courses.php?view='.$cid); exit;
}

// ── REMOVE ENROLLMENT ──
if ($action === 'remove') {
    require_role(['admin']);
    $id  = intval($_GET['id']  ?? 0);
    $cid = intval($_GET['cid'] ?? 0);
    $pdo->prepare("DELETE FROM course_enrollments WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = 'ভর্তি বাতিল করা হয়েছে।';
    header('Location: ../admin/courses.php?view='.$cid); exit;
}

// ── SELF ENROLL (Student — free courses) ──
if ($action === 'self_enroll') {
    $user = require_role(['student']);
    $cid  = intval($_POST['course_id'] ?? 0);
    $cs   = $pdo->prepare("SELECT * FROM courses WHERE id=? AND is_active=1 AND is_free=1");
    $cs->execute([$cid]); $course=$cs->fetch();
    if (!$course) { json_response(['ok'=>false,'msg'=>'ফ্রি কোর্স নয়।'],403); }
    try {
        $pdo->prepare("INSERT INTO course_enrollments (course_id,student_id) VALUES (?,?)")->execute([$cid,$user['id']]);
        json_response(['ok'=>true,'msg'=>'ভর্তি সম্পন্ন! কোর্স শুরু করুন।']);
    } catch(\PDOException $e) {
        json_response(['ok'=>false,'msg'=>'ইতিমধ্যে ভর্তি আছেন।']);
    }
}

header('Location: ../login.php'); exit;
