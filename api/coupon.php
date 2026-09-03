<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── VALIDATE COUPON (AJAX) ──
if ($action === 'validate') {
    $code   = strtoupper(trim($_POST['code']   ?? ''));
    $amount = floatval($_POST['amount'] ?? 0);
    if (!$code) { echo json_encode(['ok'=>false,'msg'=>'কুপন কোড দিন।']); exit; }

    $today = date('Y-m-d');
    $stmt  = $pdo->prepare("
        SELECT * FROM coupons
        WHERE code=? AND is_active=1
          AND (valid_from  IS NULL OR valid_from  <= ?)
          AND (valid_until IS NULL OR valid_until >= ?)
    ");
    $stmt->execute([$code, $today, $today]);
    $cp = $stmt->fetch();

    if (!$cp) { echo json_encode(['ok'=>false,'msg'=>'কুপন বৈধ নয় বা মেয়াদ শেষ।']); exit; }
    if ($cp['max_uses'] !== null && $cp['used_count'] >= $cp['max_uses']) {
        echo json_encode(['ok'=>false,'msg'=>'এই কুপনের ব্যবহার সীমা শেষ।']); exit;
    }
    if ($amount > 0 && $amount < $cp['min_amount']) {
        echo json_encode(['ok'=>false,'msg'=>'ন্যূনতম ৳'.number_format($cp['min_amount']).' হতে হবে।']); exit;
    }

    $discount = $cp['type']==='percent'
        ? round($amount * $cp['value'] / 100)
        : min($cp['value'], $amount);
    $final = max(0, $amount - $discount);

    echo json_encode([
        'ok'        => true,
        'coupon_id' => $cp['id'],
        'type'      => $cp['type'],
        'value'     => $cp['value'],
        'discount'  => $discount,
        'final'     => $final,
        'msg'       => $cp['type']==='percent'
            ? "✅ {$cp['value']}% ছাড়! ৳".number_format($discount)." সাশ্রয়।"
            : "✅ ৳".number_format($discount)." ছাড়!",
    ]);
    exit;
}

// ── ADMIN: CREATE ──
if ($action === 'create') {
    require_role(['admin']);
    $code    = strtoupper(trim($_POST['code']     ?? ''));
    $type    = in_array($_POST['type']??'', ['percent','fixed']) ? $_POST['type'] : 'percent';
    $value   = floatval($_POST['value']     ?? 0);
    $min_amt = floatval($_POST['min_amount'] ?? 0);
    $maxu    = !empty($_POST['max_uses'])    ? intval($_POST['max_uses'])   : null;
    $from    = !empty($_POST['valid_from'])  ? $_POST['valid_from']  : null;
    $until   = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;

    if (!$code || $value <= 0) {
        $_SESSION['flash_err'] = 'কোড ও মান আবশ্যক।';
        header('Location: ../admin/coupons.php'); exit;
    }
    try {
        $pdo->prepare("INSERT INTO coupons (code,type,value,min_amount,max_uses,valid_from,valid_until) VALUES (?,?,?,?,?,?,?)")
            ->execute([$code, $type, $value, $min_amt, $maxu, $from, $until]);
        $_SESSION['flash'] = "কুপন '{$code}' তৈরি হয়েছে ✅";
    } catch (\PDOException $e) {
        $_SESSION['flash_err'] = 'এই কোড আগেই আছে।';
    }
    header('Location: ../admin/coupons.php'); exit;
}

// ── ADMIN: TOGGLE ──
if ($action === 'toggle') {
    require_role(['admin']);
    $pdo->prepare("UPDATE coupons SET is_active=1-is_active WHERE id=?")
        ->execute([intval($_GET['id'] ?? 0)]);
    header('Location: ../admin/coupons.php'); exit;
}

// ── ADMIN: DELETE ──
if ($action === 'delete') {
    require_role(['admin']);
    $pdo->prepare("DELETE FROM coupons WHERE id=?")
        ->execute([intval($_GET['id'] ?? 0)]);
    $_SESSION['flash'] = 'মুছে ফেলা হয়েছে।';
    header('Location: ../admin/coupons.php'); exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'msg'=>'Invalid action']);
