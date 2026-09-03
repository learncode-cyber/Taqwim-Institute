<?php
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache');
require_once '../../includes/db.php';
$stmt=$pdo->query("SELECT key_name,value FROM settings WHERE key_name IN ('theme_primary','theme_gold','theme_dark')");
$s=[]; foreach($stmt->fetchAll() as $r) $s[$r['key_name']]=$r['value'];
$p=$s['theme_primary']??'#16a34a';
$g=$s['theme_gold']   ??'#c9a227';
$d=$s['theme_dark']   ??'#0d1f12';
function h2r($h){$h=ltrim($h,'#');if(strlen($h)==3)$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];return[hexdec(substr($h,0,2)),hexdec(substr($h,2,2)),hexdec(substr($h,4,2))];}
function li($h,$a){[$r,$g,$b]=h2r($h);return sprintf('#%02x%02x%02x',min(255,$r+$a),min(255,$g+$a),min(255,$b+$a));}
function da($h,$a){[$r,$g,$b]=h2r($h);return sprintf('#%02x%02x%02x',max(0,$r-$a),max(0,$g-$a),max(0,$b-$a));}
function ra($h,$a){[$r,$g,$b]=h2r($h);return "rgba($r,$g,$b,$a)";}
echo ":root,[data-theme='light']{
  --p600:".li($p,0).";
  --p500:".li($p,20).";
  --p100:".ra($p,.1).";
  --p50: ".ra($p,.05).";
  --gold:".li($g,0).";
  --gold-l:".li($g,25).";
  --gold-d:".da($g,20).";
  --gold-glow:".ra($g,.25).";
  --sb-bg:{$d};
}
[data-theme='dark']{
  --p600:".li($p,25).";
  --p500:".li($p,45).";
  --p100:".ra($p,.12).";
  --gold:".li($g,0).";
  --gold-l:".li($g,25).";
  --gold-d:".da($g,20).";
  --gold-glow:".ra($g,.2).";
  --sb-bg:".da($d,5).";
}";
