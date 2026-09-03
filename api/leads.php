<?php
require_once '../includes/db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }

if (isset($_POST['name'],$_POST['phone'])) {
    $name=$_POST['name']??''; $phone=$_POST['phone']??'';
    $email=$_POST['email']??''; $course=$_POST['course']??'';
    $for=$_POST['for_whom']??''; $pkg=$_POST['package']??'';
    $is_ajax=!empty($_POST['ajax']);

    $pdo->prepare("INSERT INTO leads (name,phone,email,course,for_whom,package_interest,source) VALUES (?,?,?,?,?,?,'landing_page')")
        ->execute([$name,$phone,$email,$course,$for,$pkg]);

    send_telegram("📋 *নতুন লিড!*\n\n👤 {$name}\n📱 {$phone}\n📚 {$course}\n🎯 {$for}\n⏰ ".date('d M Y H:i'));

    $wa_num = get_setting('whatsapp_number');
    $msg    = urlencode("আস-সালামু আলাইকুম! আমি {$name}। Taqwim Institute-এ ভর্তি হতে আগ্রহী। কোর্স: {$course}");
    $url    = $wa_num ? "https://wa.me/".preg_replace('/^0/','88',$wa_num)."?text={$msg}" : '';

    if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'wa'=>$url]); exit; }
    if ($url) { header("Location: {$url}"); exit; }
    header('Location: ../index.php'); exit;
}

if (isset($_POST['id'],$_POST['status'])) {
    require_role(['admin']);
    $pdo->prepare("UPDATE leads SET status=? WHERE id=?")->execute([$_POST['status'],$_POST['id']]);
    header('Location: ../admin/index.php'); exit;
}
http_response_code(400);
