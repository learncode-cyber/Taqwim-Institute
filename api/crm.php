<?php
require_once '../includes/db.php';
$user   = require_role(['admin']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');

if ($action === 'move_stage') {
    $id=$_POST['id']??0; $stage=$_POST['stage']??'';
    if (!in_array($stage,['new','contacted','demo','enrolled','lost'])){echo json_encode(['ok'=>false]);exit;}
    $pdo->prepare("UPDATE leads SET stage=?,updated_at=NOW() WHERE id=?")->execute([$stage,$id]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'add_log') {
    $pdo->prepare("INSERT INTO comm_logs (type,ref_id,channel,direction,note,logged_by) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['type']??'lead',$_POST['ref_id']??0,$_POST['channel']??'whatsapp',$_POST['direction']??'outbound',trim($_POST['note']??''),$user['id']]);
    echo json_encode(['ok'=>true,'logged_at'=>date('d M Y, H:i'),'logged_by'=>$user['name']]); exit;
}
if ($action === 'get_logs') {
    $s=$pdo->prepare("SELECT cl.*,u.name AS by_name FROM comm_logs cl JOIN users u ON u.id=cl.logged_by WHERE cl.type=? AND cl.ref_id=? ORDER BY cl.logged_at DESC");
    $s->execute([$_GET['type']??'lead',$_GET['ref_id']??0]);
    echo json_encode(['ok'=>true,'logs'=>$s->fetchAll()]); exit;
}
if ($action === 'add_followup') {
    $pdo->prepare("INSERT INTO followups (type,ref_id,ref_name,ref_phone,note,due_at,created_by) VALUES (?,?,?,?,?,?,?)")
        ->execute([$_POST['type']??'lead',$_POST['ref_id']??0,$_POST['ref_name']??'',$_POST['ref_phone']??'',trim($_POST['note']??''),$_POST['due_at']??'',$user['id']]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'done_followup') {
    $pdo->prepare("UPDATE followups SET is_done=1 WHERE id=?")->execute([$_POST['id']??0]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'del_followup') {
    $pdo->prepare("DELETE FROM followups WHERE id=?")->execute([$_GET['id']??0]);
    echo json_encode(['ok'=>true]); exit;
}
echo json_encode(['ok'=>false,'msg'=>'Invalid action']);
