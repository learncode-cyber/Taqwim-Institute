<?php
require_once '../includes/db.php';
require_role(['admin']);
$type=$_GET['type']??'';
header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename={$type}_".date('Y-m-d').".csv");
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
if ($type==='students') {
    fputcsv($out,['নাম','ইমেইল','ফোন','প্যাকেজ','যোগদান']);
    foreach ($pdo->query("SELECT name,email,phone,package,created_at FROM users WHERE role='student' ORDER BY created_at DESC") as $r)
        fputcsv($out,[$r['name'],$r['email'],$r['phone'],pkg_name($r['package']),date('d M Y',strtotime($r['created_at']))]);
} elseif ($type==='payments') {
    fputcsv($out,['তারিখ','ছাত্র','পরিমাণ','মাধ্যম','TxnID','অবস্থা']);
    foreach ($pdo->query("SELECT p.*,u.name FROM payments p JOIN users u ON p.student_id=u.id ORDER BY p.created_at DESC") as $r)
        fputcsv($out,[date('d M Y',strtotime($r['created_at'])),$r['name'],$r['amount'],$r['method'],$r['txn_id'],$r['status']]);
} elseif ($type==='leads') {
    fputcsv($out,['নাম','ফোন','কোর্স','তারিখ','স্ট্যাটাস']);
    foreach ($pdo->query("SELECT * FROM leads ORDER BY created_at DESC") as $r)
        fputcsv($out,[$r['name'],$r['phone'],$r['course']??'',date('d M Y',strtotime($r['created_at'])),$r['status']]);
}
fclose($out); exit;
