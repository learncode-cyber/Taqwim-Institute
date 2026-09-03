<?php
require_once '../includes/db.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = current_user();

// ── STUDENT: Submit Payment ──
if ($action === 'submit') {
    if (!$user || $user['role'] !== 'student') {
        header('Location: ../login.php'); exit;
    }

    $amount       = intval($_POST['amount']       ?? 0);
    $method       = $_POST['method']              ?? '';
    $txn_id       = trim($_POST['txn_id']         ?? '');
    $coupon_code  = strtoupper(trim($_POST['coupon_code']  ?? ''));
    $coupon_id    = intval($_POST['coupon_id']    ?? 0);
    $discount     = floatval($_POST['discount']   ?? 0);
    $final_amount = intval($_POST['final_amount'] ?? $amount);

    if (!$amount || !$method || !$txn_id) {
        $_SESSION['flash_err'] = 'সব ঘর পূরণ করুন।';
        header('Location: ../student/index.php'); exit;
    }

    // Re-validate coupon server-side (security)
    if ($coupon_id > 0 && $discount > 0) {
        $today = date('Y-m-d');
        $cp = $pdo->prepare("
            SELECT * FROM coupons
            WHERE id=? AND is_active=1
              AND (valid_until IS NULL OR valid_until >= ?)
        ");
        $cp->execute([$coupon_id, $today]);
        $coupon = $cp->fetch();

        if ($coupon && ($coupon['max_uses'] === null || $coupon['used_count'] < $coupon['max_uses'])) {
            $discount     = $coupon['type']==='percent'
                ? round($amount * $coupon['value'] / 100)
                : min($coupon['value'], $amount);
            $final_amount = max(0, $amount - $discount);
        } else {
            $coupon_id = 0; $discount = 0; $final_amount = $amount;
        }
    } else {
        $coupon_id = 0; $discount = 0; $final_amount = $amount;
    }

    // Insert payment
    $pdo->prepare("
        INSERT INTO payments (student_id, amount, package, method, txn_id, month_year)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
        $user['id'], $final_amount,
        $user['package'] ?? 'basic',
        $method, $txn_id, date('Y-m')
    ]);
    $pay_id = $pdo->lastInsertId();

    // Log coupon use + update counter
    if ($coupon_id > 0 && $discount > 0) {
        $pdo->prepare("
            INSERT INTO coupon_uses (coupon_id, student_id, payment_id, discount)
            VALUES (?,?,?,?)
        ")->execute([$coupon_id, $user['id'], $pay_id, $discount]);

        $pdo->prepare("UPDATE coupons SET used_count=used_count+1 WHERE id=?")
            ->execute([$coupon_id]);
    }

    // Telegram notification
    $disc_line = $discount > 0
        ? "\n🏷️ কুপন ({$coupon_code}): -৳{$discount}"
        : '';
    send_telegram(
        "💰 *নতুন পেমেন্ট সাবমিট!*\n\n" .
        "👤 ছাত্র: {$user['name']}\n" .
        "💵 মূল: ৳{$amount}{$disc_line}\n" .
        "✅ পরিশোধ: ৳{$final_amount}\n" .
        "📱 মাধ্যম: {$method}\n" .
        "🔑 TxnID: {$txn_id}\n" .
        "⏰ সময়: " . date('d M Y H:i')
    );

    $_SESSION['flash'] = 'পেমেন্ট সাবমিট হয়েছে ✅ অ্যাডমিন ২৪ ঘণ্টার মধ্যে কনফার্ম করবেন।';
    header('Location: ../student/index.php'); exit;
}

// ── ADMIN: Confirm Payment ──
if ($action === 'confirm') {
    require_role(['admin']);
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { header('Location: ../admin/index.php'); exit; }

    $pdo->prepare("
        UPDATE payments
        SET status='confirmed', confirmed_by=?, confirmed_at=NOW()
        WHERE id=?
    ")->execute([$_SESSION['user_id'], $id]);

    $pay = $pdo->prepare("
        SELECT p.*, u.name, u.phone
        FROM payments p JOIN users u ON p.student_id=u.id
        WHERE p.id=?
    ");
    $pay->execute([$id]);
    $p = $pay->fetch();
    if ($p) {
        send_telegram(
            "✅ *পেমেন্ট কনফার্ম!*\n\n" .
            "👤 {$p['name']}\n💵 ৳{$p['amount']}\n📱 {$p['method']}"
        );
    }

    $_SESSION['flash'] = 'পেমেন্ট কনফার্ম হয়েছে ✅';
    header('Location: ../admin/index.php'); exit;
}

// ── ADMIN: Reject Payment ──
if ($action === 'reject') {
    require_role(['admin']);
    $pdo->prepare("UPDATE payments SET status='rejected' WHERE id=?")
        ->execute([intval($_GET['id'] ?? 0)]);
    $_SESSION['flash'] = 'পেমেন্ট বাতিল করা হয়েছে।';
    header('Location: ../admin/index.php'); exit;
}

header('Location: ../login.php'); exit;
