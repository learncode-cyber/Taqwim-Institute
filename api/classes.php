<?php
require_once '../includes/db.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = current_user();
if (!$user) { http_response_code(401); exit; }

if ($action === 'add') {
    require_role(['admin']);
    $pdo->prepare("INSERT INTO classes (title,teacher_id,class_date,class_time,duration,platform,meet_link,zoom_link,class_type) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$_POST['title'],$_POST['teacher_id'],$_POST['class_date'],$_POST['class_time'],$_POST['duration']??45,$_POST['platform']??'google_meet',$_POST['meet_link']??'',$_POST['zoom_link']??'',$_POST['class_type']??'group']);
    $cid = $pdo->lastInsertId();
    foreach ($_POST['student_ids']??[] as $sid)
        $pdo->prepare("INSERT IGNORE INTO class_students (class_id,student_id) VALUES (?,?)")->execute([$cid,$sid]);
    $_SESSION['flash'] = 'ক্লাস তৈরি হয়েছে ✅';
    header('Location: ../admin/index.php'); exit;
}

if ($action === 'delete') {
    require_role(['admin']);
    $pdo->prepare("DELETE FROM classes WHERE id=?")->execute([intval($_GET['id']??0)]);
    $_SESSION['flash'] = 'মুছে ফেলা হয়েছে';
    header('Location: ../admin/index.php'); exit;
}

if ($action === 'attendance' && $user['role']==='teacher') {
    $cid     = intval($_POST['class_id']??0);
    $present = array_map('strval', $_POST['present']??[]);
    $notes   = $_POST['notes']??'';

    $chk = $pdo->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?");
    $chk->execute([$cid,$user['id']]);
    if (!$chk->fetch()) { json_response(['ok'=>false,'error'=>'Unauthorized'],403); }

    $all = $pdo->prepare("SELECT student_id FROM class_students WHERE class_id=?");
    $all->execute([$cid]);
    foreach ($all->fetchAll() as $r) {
        $sid    = (string)$r['student_id'];
        $status = in_array($sid,$present) ? 'present' : 'absent';
        $pdo->prepare("INSERT INTO attendance (class_id,student_id,status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=?")
            ->execute([$cid,$r['student_id'],$status,$status]);
    }
    $pdo->prepare("UPDATE classes SET status='completed',notes=? WHERE id=?")->execute([$notes,$cid]);
    json_response(['ok'=>true]);
}

header('Location: ../admin/index.php'); exit;
