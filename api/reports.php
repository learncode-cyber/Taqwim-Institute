<?php
require_once '../includes/db.php';
$user = require_role(['teacher']);
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: ../teacher/index.php'); exit; }

$sid     = intval($_POST['student_id']??0);
$type    = $_POST['report_type']??'weekly';
$grade   = $_POST['tilawat_grade']??'good';
$content = trim($_POST['content']??'');
$hw      = trim($_POST['homework']??'');

if (!$sid||!$content) { $_SESSION['flash_err']='ছাত্র ও বিবরণ আবশ্যক।'; header('Location: ../teacher/index.php'); exit; }

$pdo->prepare("INSERT INTO reports (teacher_id,student_id,report_type,tilawat_grade,content,homework) VALUES (?,?,?,?,?,?)")
    ->execute([$user['id'],$sid,$type,$grade,$content,$hw]);

$std = $pdo->prepare("SELECT name,phone FROM users WHERE id=?");
$std->execute([$sid]); $student=$std->fetch();

$grade_bn=['excellent'=>'চমৎকার ⭐⭐⭐⭐⭐','good'=>'ভালো ⭐⭐⭐⭐','average'=>'মাঝারি ⭐⭐⭐','needs_improvement'=>'উন্নতি প্রয়োজন ⭐⭐'];
$type_bn=['weekly'=>'সাপ্তাহিক','monthly'=>'মাসিক','special'=>'বিশেষ'];

$wa_text = urlencode("📋 *".($type_bn[$type]??$type)." রিপোর্ট*\n\nছাত্র: {$student['name']}\nশিক্ষক: {$user['name']}\nমান: ".($grade_bn[$grade]??$grade)."\n\n{$content}".($hw?"\n\n📚 হোমওয়ার্ক: {$hw}":'')."\n\nতারিখ: ".date('d M Y'));
$wp = preg_replace('/^0/','88',$student['phone']??'');

$_SESSION['flash']     = 'রিপোর্ট সেভ হয়েছে ✅';
$_SESSION['report_wa'] = $wp ? "https://wa.me/{$wp}?text={$wa_text}" : '';
header('Location: ../teacher/index.php'); exit;
