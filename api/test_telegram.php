<?php
require_once '../includes/db.php';
require_role(['admin']);
$r = send_telegram("✅ *Taqwim Institute*\n\nTelegram সংযোগ সফল! 🎉\n⏰ ".date('d M Y H:i'));
echo $r ? '✅ Telegram টেস্ট সফল!' : '❌ ব্যর্থ। Token ও Chat ID চেক করুন।';
