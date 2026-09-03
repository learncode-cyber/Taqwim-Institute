<?php
function get_theme_css(): string {
    global $pdo;
    static $css = null;
    if ($css !== null) return $css;

    $stmt = $pdo->query("SELECT key_name,value FROM settings WHERE key_name IN
        ('theme_primary','theme_gold','theme_dark')");
    $s = [];
    foreach ($stmt->fetchAll() as $r) $s[$r['key_name']] = $r['value'];

    $p = $s['theme_primary'] ?? '#1e5c32';
    $g = $s['theme_gold']    ?? '#b8963e';
    $d = $s['theme_dark']    ?? '#14381e';

    $css = ":root{" .
        "--p900:".darken($d,15).";--sb-bg:{$d};--p700:".lighten($d,15).";" .
        "--p600:{$p};--p500:".lighten($p,15).";--p400:".lighten($p,35).";" .
        "--p100:".rgba_c($p,.07).";--p50:".rgba_c($p,.03).";" .
        "--gold:{$g};--gold-l:".lighten($g,20).";--gold-bg:".rgba_c($g,.07).";" .
        "--ink:#1c2b1e;--body:#3a4a3c;--muted:#6b7a6d;" .
        "--border:#dde8df;--surface:#ffffff;--bg:#f5f8f5;}";
    return $css;
}

function get_branding(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT key_name,value FROM settings WHERE key_name IN
        ('site_name','site_tagline','site_logo','site_footer_text','currency_symbol','currency_code')");
    $b = [];
    foreach ($stmt->fetchAll() as $r) $b[$r['key_name']] = $r['value'];
    return $b;
}

function hex2rgb(string $hex): array {
    $hex = ltrim($hex,'#');
    if (strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))];
}
function lighten(string $hex, int $amt): string {
    [$r,$g,$b]=hex2rgb($hex);
    return sprintf('#%02x%02x%02x',min(255,$r+$amt),min(255,$g+$amt),min(255,$b+$amt));
}
function darken(string $hex, int $amt): string {
    [$r,$g,$b]=hex2rgb($hex);
    return sprintf('#%02x%02x%02x',max(0,$r-$amt),max(0,$g-$amt),max(0,$b-$amt));
}
function rgba_c(string $hex, float $a): string {
    [$r,$g,$b]=hex2rgb($hex);
    return "rgba($r,$g,$b,$a)";
}
