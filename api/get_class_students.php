<?php
require_once '../includes/db.php';
$user = require_role(['teacher','admin']);
$cid  = intval($_GET['class_id']??0);
if (!$cid) json_response(['error'=>'class_id required'],400);

if ($user['role']==='teacher') {
    $chk=$pdo->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?");
    $chk->execute([$cid,$user['id']]);
    if (!$chk->fetch()) json_response(['error'=>'Unauthorized'],403);
}

$stmt=$pdo->prepare("SELECT u.id,u.name,u.phone,COALESCE(a.status,'') AS att_status FROM users u JOIN class_students cs ON cs.student_id=u.id LEFT JOIN attendance a ON a.class_id=? AND a.student_id=u.id WHERE cs.class_id=? ORDER BY u.name");
$stmt->execute([$cid,$cid]);
$cls=$pdo->prepare("SELECT title,class_date,class_time,notes FROM classes WHERE id=?");
$cls->execute([$cid]);
json_response(['students'=>$stmt->fetchAll(),'class_info'=>$cls->fetch()]);
