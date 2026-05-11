<?php
require_once __DIR__ . '/session_bridge.php';

$currentFrontUser = cre8_front_session_user();

$userName = $currentFrontUser['nom']
    ?? $_SESSION['nom']
    ?? ($_SESSION['user']['nom'] ?? ($_SESSION['utilisateur']['nom'] ?? 'Utilisateur'));
$userName = trim((string) $userName);
$userName = $userName !== '' ? $userName : 'Utilisateur';
$userInitial = function_exists('mb_substr')
    ? mb_substr($userName, 0, 1, 'UTF-8')
    : substr($userName, 0, 1);
$userInitial = strtoupper((string) $userInitial);
$userInitial = $userInitial !== '' ? $userInitial : 'U';

$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$frontOfficeMarker = '/Vue/FrontOffice/';
$frontOfficePos = strpos($currentPath, $frontOfficeMarker);

if ($frontOfficePos !== false) {
    // Called from a FrontOffice view file
    $projectBase = substr($currentPath, 0, $frontOfficePos);
} else {
    // Called from a Controller or other path — walk up to find project root
    // by stripping everything from /Controleur/ or /Vue/ onward
    foreach (['/Controleur/', '/Vue/'] as $marker) {
        $pos = strpos($currentPath, $marker);
        if ($pos !== false) {
            $projectBase = substr($currentPath, 0, $pos);
            break;
        }
    }
    if (!isset($projectBase)) {
        $projectBase = rtrim(dirname(dirname($currentPath)), '/');
    }
}
$frontBaseUrl = $projectBase . '/Vue/FrontOffice';

$sessionRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');

$homeUrl = $frontBaseUrl . '/utilisateur/creator.php';
$reclamationUrl = $frontBaseUrl . '/utilisateur/reclamation.php';
$offersUrl = $sessionRole === 'marque'
    ? $frontBaseUrl . '/offre/brand_index.php'
    : $frontBaseUrl . '/offre/creator_list.php';
$candidaturesUrl = $sessionRole === 'marque'
    ? $frontBaseUrl . '/condidature/brand_index.php'
    : $frontBaseUrl . '/condidature/index.php';
$campaignsUrl = $sessionRole === 'marque'
    ? $frontBaseUrl . '/campagne/index.php'
    : $frontBaseUrl . '/campagne/indexC.php';
$productsUrl = $sessionRole === 'marque'
    ? $frontBaseUrl . '/produit/index.php'
    : $frontBaseUrl . '/produit/indexC.php';
$contractsUrl = $sessionRole === 'marque'
    ? $frontBaseUrl . '/contrat/index.php'
    : $frontBaseUrl . '/contrat/indexC.php';
$portfolioUrl = $frontBaseUrl . '/post/portfolio.php';
$postsUrl = $frontBaseUrl . '/post/index.php';
$createPostUrl = $frontBaseUrl . '/post/create.php';
$eventsUrl = $frontBaseUrl . '/evenement/index.php';
$forumUrl  = $projectBase . '/Controleur/forumC.php';
$logoutUrl = $frontBaseUrl . '/utilisateur/logout.php';

if (!isset($frontActive)) {
    if (strpos($currentPath, '/utilisateur/creator.php') !== false) {
        $frontActive = 'home';
    } elseif (strpos($currentPath, '/utilisateur/reclamation.php') !== false) {
        $frontActive = 'reclamation';
    } elseif (strpos($currentPath, '/post/') !== false) {
        $frontActive = 'myspace';
    } elseif (strpos($currentPath, '/offre/') !== false || strpos($currentPath, '/condidature/') !== false || strpos($currentPath, '/candidature/') !== false) {
        $frontActive = 'collaborations';
    } elseif (strpos($currentPath, '/campagne/') !== false || strpos($currentPath, '/produit/') !== false || strpos($currentPath, '/contrat/') !== false) {
        $frontActive = 'campaigns';
    } elseif (strpos($currentPath, '/evenement/') !== false || strpos($currentPath, '/forum/') !== false) {
        $frontActive = 'events';
    } else {
        $frontActive = '';
    }
}
?>
<script>
(function () {
    try {
        var theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        if (document.body) {
            document.body.classList.toggle('dark-mode', theme === 'dark');
            document.body.classList.toggle('light-mode', theme !== 'dark');
        }
    } catch (e) {}
})();
</script>
<nav class="front-nav cre8-front-header" aria-label="FrontOffice navigation">
    <a class="front-nav-logo cre8-front-brand" href="<?php echo htmlspecialchars($homeUrl); ?>" aria-label="Cre8Connect home">
        <img src="<?php echo htmlspecialchars($projectBase . '/Vue/public/images/logoweb.png'); ?>" alt="Cre8Connect" class="front-header-logo">
    </a>

    <ul class="front-nav-links cre8-front-nav">
        <li class="front-nav-item">
            <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="cre8-front-nav-link <?php echo $frontActive === 'home' ? 'active is-active' : ''; ?>">
                <i class="bi bi-house"></i> Home
            </a>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'collaborations' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-briefcase"></i> Collaborations <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($offersUrl); ?>" role="menuitem">Offers</a>
                <a href="<?php echo htmlspecialchars($candidaturesUrl); ?>" role="menuitem">Candidatures</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'campaigns' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-megaphone"></i> Campaigns <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($campaignsUrl); ?>" role="menuitem">Campagnes</a>
                <a href="<?php echo htmlspecialchars($productsUrl); ?>" role="menuitem">Produits</a>
                <a href="<?php echo htmlspecialchars($contractsUrl); ?>" role="menuitem">Contrats</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'myspace' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-person-badge"></i> Posts <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($portfolioUrl); ?>" role="menuitem">My Space</a>
                <a href="<?php echo htmlspecialchars($postsUrl); ?>" role="menuitem">Feeds</a>
                <a href="<?php echo htmlspecialchars($createPostUrl); ?>" role="menuitem">Create Post</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'events' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-calendar-event"></i> Events <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($eventsUrl); ?>" role="menuitem">Événements</a>
                <a href="<?php echo htmlspecialchars($forumUrl); ?>" role="menuitem">Forum</a>
            </div>
        </li>

        <li class="front-nav-item">
            <a href="<?php echo htmlspecialchars($reclamationUrl); ?>" class="cre8-front-nav-link <?php echo $frontActive === 'reclamation' ? 'active is-active' : ''; ?>">
                <i class="bi bi-flag"></i> Réclamation
            </a>
        </li>

        <li class="front-nav-item">
            <button class="front-theme-btn" id="themeBtn" title="Toggle dark mode" type="button">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>
        </li>
        <li class="front-nav-item">
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="front-nav-logout cre8-front-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>

    <div class="front-nav-right cre8-front-user">
        <div class="front-nav-badge cre8-front-role-pill">
            <i class="bi bi-person-circle"></i>
            <?php echo htmlspecialchars($userName); ?>
        </div>
        <div class="front-nav-avatar cre8-front-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
    </div>
</nav>
