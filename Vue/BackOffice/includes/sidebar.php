<?php
/**
 * Compatibility wrapper for old BackOffice pages.
 * The real sidebar is Vue/BackOffice/layout/sidebar.php.
 * This wrapper maps old $activeMenu values and prints a tiny late CSS guard
 * because old modules still contain inline .sidebar rules in their <head>.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8Marker = '/Vue/BackOffice/';
$cre8Pos = strpos($cre8SelfPath, $cre8Marker);
$backBoRootWeb = ($cre8Pos !== false ? substr($cre8SelfPath, 0, $cre8Pos) : '') . '/Vue/BackOffice';

if (!isset($backActive)) {
    $legacyActive = strtolower((string) ($activeMenu ?? 'dashboard'));
    $legacyMap = [
        'dashboard' => 'dashboard',
        'utilisateur' => 'users', 'user' => 'users', 'users' => 'users',
        'reclamation' => 'reclamations', 'reclamations' => 'reclamations',
        'offre' => 'collaborations', 'offres' => 'collaborations', 'offers' => 'collaborations',
        'condidature' => 'collaborations', 'candidature' => 'collaborations', 'collaboration' => 'collaborations', 'collaborations' => 'collaborations',
        'campagne' => 'campaigns', 'campagnes' => 'campaigns', 'campaign' => 'campaigns', 'campaigns' => 'campaigns',
        'produit' => 'products', 'produits' => 'products', 'product' => 'products', 'products' => 'products',
        'contrat' => 'contracts', 'contrats' => 'contracts', 'contract' => 'contracts', 'contracts' => 'contracts',
        'post' => 'posts', 'posts' => 'posts',
        'comment' => 'comments', 'comments' => 'comments',
        'evenement' => 'events', 'event' => 'events', 'events' => 'events',
        'forum' => 'forum',
    ];
    $backActive = $legacyMap[$legacyActive] ?? 'dashboard';
}

if (!defined('CRE8_BACK_LAYOUT_CSS_PRINTED')) {
    define('CRE8_BACK_LAYOUT_CSS_PRINTED', true);
    $backLayoutCssFile = __DIR__ . '/../layout/back-layout.css';
    $backLayoutCssUrl = $backBoRootWeb . '/layout/back-layout.css';
    if (is_file($backLayoutCssFile)) {
        $backLayoutCssUrl .= '?v=' . rawurlencode((string) filemtime($backLayoutCssFile));
    }
    echo '<link rel="stylesheet" href="' . htmlspecialchars($backLayoutCssUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

require_once __DIR__ . '/../layout/sidebar.php';

if (!defined('CRE8_BACK_SIDEBAR_LATE_FIX_PRINTED')) {
    define('CRE8_BACK_SIDEBAR_LATE_FIX_PRINTED', true);
    ?>
<style id="cre8-sidebar-late-fix">
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items,
body.light-mode #sidebar .nav>li.nav-item.menu-items,
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items:hover,
body.light-mode #sidebar .nav>li.nav-item.menu-items:hover{background:transparent!important;background-color:transparent!important;box-shadow:none!important}
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items>a.nav-link,
body.light-mode #sidebar .nav>li.nav-item.menu-items>a.nav-link{background:transparent!important;background-color:transparent!important;color:#111827!important;box-shadow:none!important}
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items:not(.active):not(.back-nav-disabled):hover>a.nav-link,
body.light-mode #sidebar .nav>li.nav-item.menu-items:not(.active):not(.back-nav-disabled):hover>a.nav-link,
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items:not(.active):not(.back-nav-disabled)>a.nav-link:hover,
body.light-mode #sidebar .nav>li.nav-item.menu-items:not(.active):not(.back-nav-disabled)>a.nav-link:hover{background:#f1f5f9!important;background-color:#f1f5f9!important;color:#111827!important}
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items.active>a.nav-link,
body.light-mode #sidebar .nav>li.nav-item.menu-items.active>a.nav-link{background:#0d0e14!important;background-color:#0d0e14!important;color:#fff!important}
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items.back-nav-disabled,
body.light-mode #sidebar .nav>li.nav-item.menu-items.back-nav-disabled,
html[data-theme="light"] body #sidebar .nav>li.nav-item.menu-items.back-nav-disabled>a.nav-link,
body.light-mode #sidebar .nav>li.nav-item.menu-items.back-nav-disabled>a.nav-link{background:transparent!important;background-color:transparent!important;color:#94a3b8!important;opacity:.78!important;cursor:not-allowed!important}
</style>
<?php } ?>
