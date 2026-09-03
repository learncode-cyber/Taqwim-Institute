<?php
session_start();

// ── DB CONFIG (Hostinger এ পরিবর্তন করুন) ──
define('DB_HOST', 'localhost');
define('DB_NAME', 'u290513561_talim_database');
define('DB_USER', 'u290513561_talim_database');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('SITE_URL', 'https://arprimemarket.shop');

// ── PDO Connection ──
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error'=>'DB Connection failed: '.$e->getMessage()]));
}

// ── AUTH HELPERS ──
function current_user() {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $s = $pdo->prepare("SELECT * FROM users WHERE id=? AND is_active=1");
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

function require_role(array $roles) {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json')!==false)) {
            http_response_code(401);
            die(json_encode(['error'=>'Unauthorized']));
        }
        header('Location: '.SITE_URL.'/login.php'); exit;
    }
    return $user;
}

// ── TELEGRAM ──
function send_telegram(string $msg): bool {
    global $pdo;
    $token = get_setting('telegram_bot_token');
    $chat  = get_setting('telegram_chat_id');
    if (!$token || !$chat) return false;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>5,
        CURLOPT_POSTFIELDS=>http_build_query([
            'chat_id'=>$chat,'text'=>$msg,'parse_mode'=>'Markdown'
        ])
    ]);
    $r = curl_exec($ch); curl_close($ch);
    return $r !== false;
}

// ── SETTINGS ──
function get_setting(string $key): string {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        $s = $pdo->prepare("SELECT value FROM settings WHERE key_name=?");
        $s->execute([$key]);
        $cache[$key] = $s->fetchColumn() ?: '';
    }
    return $cache[$key];
}

// ── JSON RESPONSE ──
function json_response($data, int $code=200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── PACKAGE HELPERS ──
function pkg_name(string $pkg): string {
    return ['basic'=>'বেসিক','standard'=>'স্ট্যান্ডার্ড','premium'=>'প্রিমিয়াম'][$pkg] ?? $pkg;
}
function pkg_price(string $pkg): int {
    return ['basic'=>2000,'standard'=>3200,'premium'=>3800][$pkg] ?? 2000;
}
