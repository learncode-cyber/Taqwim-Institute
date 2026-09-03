<?php
// ── Footer Credit (do not remove) ──
// © Abdullah Raiyan | abdullahraiyan.com
define('AR_CREDIT', true);
define('AR_NAME',   'Abdullah Raiyan');
define('AR_URL',    'https://abdullahraiyan.com');
define('AR_YEAR',   date('Y'));

function ar_footer_html(string $bg = '#0a1f10'): string {
    return '<div style="text-align:center;padding:8px 0;font-size:.7rem;color:rgba(255,255,255,.2);background:'.$bg.';">
        Designed &amp; Developed by 
        <a href="'.AR_URL.'" target="_blank" rel="noopener" 
           style="color:rgba(255,255,255,.35);text-decoration:none;"
           onmouseover="this.style.color=\'#b8963e\'" 
           onmouseout="this.style.color=\'rgba(255,255,255,.35)\'">'.AR_NAME.'</a>
        &nbsp;·&nbsp; &copy; '.AR_YEAR.'
    </div>';
}
?>
