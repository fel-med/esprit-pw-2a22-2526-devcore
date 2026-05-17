<?php
require_once __DIR__ . '/session_bridge.php';
require_once __DIR__ . '/../../../Controleur/profileC.php';
$notificationController = null;

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
$profileUploadWeb = $projectBase . '/Vue/public/uploads/profile';

$notificationUserId = !empty($currentFrontUser['id']) ? (int) $currentFrontUser['id'] : 0;
$notificationActionUrl = $frontBaseUrl . '/layout/notification_actions.php';
$notificationPageUrl = $frontBaseUrl . '/notifications/index.php';

if ($notificationUserId > 0) {
    $notificationPath = __DIR__ . '/../../../Controleur/notificationC.php';
    if (is_file($notificationPath)) {
        require_once $notificationPath;
        if (class_exists('NotificationC')) {
            try {
                $notificationController = new NotificationC();
            } catch (Throwable $e) {
                $notificationController = null;
            }
        }
    }
}

$sessionRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');

if ($notificationUserId > 0) {
    $condidatureControllerPath = __DIR__ . '/../../../Controleur/condidatureC.php';
    if (is_file($condidatureControllerPath)) {
        try {
            require_once $condidatureControllerPath;
            if (class_exists('CondidatureC')) {
                $deadlineNotificationC = new CondidatureC();
                if ($sessionRole === 'marque' && method_exists($deadlineNotificationC, 'generateBrandDeadlineSoonNotifications')) {
                    $deadlineNotificationC->generateBrandDeadlineSoonNotifications($notificationUserId);
                } elseif ($sessionRole !== 'marque' && method_exists($deadlineNotificationC, 'generateCreatorDeadlineSoonNotifications')) {
                    $deadlineNotificationC->generateCreatorDeadlineSoonNotifications($notificationUserId);
                }
            }
        } catch (Throwable $e) {
            // Header must stay safe even if legacy notification generation fails.
        }
    }
}

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
$profileSettingsUrl = $frontBaseUrl . '/utilisateur/profile_settings.php';
$profileImageUrl = null;

if (!empty($currentFrontUser['id'])) {
    try {
        $profileC = new ProfileC();
        $profileImageUrl = $profileC->getProfileImageUrl((int) $currentFrontUser['id'], $profileUploadWeb);
    } catch (Throwable $e) {
        $profileImageUrl = null;
    }
}

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
<script src="<?php echo htmlspecialchars($frontBaseUrl . '/layout/front-translate.js'); ?>" defer></script>
<nav class="front-nav cre8-front-header" aria-label="FrontOffice navigation" data-i18n-title="header.navAria">
    <a class="front-nav-logo cre8-front-brand" href="<?php echo htmlspecialchars($homeUrl); ?>" aria-label="Cre8Connect home" data-i18n-title="header.homeAria">
        <img src="<?php echo htmlspecialchars($projectBase . '/Vue/public/images/logoweb.png'); ?>" alt="Cre8Connect" class="front-header-logo">
    </a>

    <ul class="front-nav-links cre8-front-nav">
        <li class="front-nav-item">
            <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="cre8-front-nav-link <?php echo $frontActive === 'home' ? 'active is-active' : ''; ?>">
                <i class="bi bi-house"></i> <span data-i18n="header.home">Home</span>
            </a>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'collaborations' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-briefcase"></i> <span data-i18n="header.collaborations">Collaborations</span> <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($offersUrl); ?>" role="menuitem" data-i18n="header.offers">Offers</a>
                <a href="<?php echo htmlspecialchars($candidaturesUrl); ?>" role="menuitem" data-i18n="header.applications">Applications</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'campaigns' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-megaphone"></i> <span data-i18n="header.campaigns">Campaigns</span> <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($campaignsUrl); ?>" role="menuitem" data-i18n="header.campaigns">Campaigns</a>
                <a href="<?php echo htmlspecialchars($productsUrl); ?>" role="menuitem" data-i18n="header.products">Products</a>
                <a href="<?php echo htmlspecialchars($contractsUrl); ?>" role="menuitem" data-i18n="header.contracts">Contracts</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'myspace' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-person-badge"></i> <span data-i18n="header.posts">Posts</span> <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($portfolioUrl); ?>" role="menuitem" data-i18n="header.mySpace">My Space</a>
                <a href="<?php echo htmlspecialchars($postsUrl); ?>" role="menuitem" data-i18n="header.feeds">Feeds</a>
                <a href="<?php echo htmlspecialchars($createPostUrl); ?>" role="menuitem" data-i18n="header.createPost">Create Post</a>
            </div>
        </li>

        <li class="front-nav-item front-nav-dropdown-item">
            <button class="front-nav-trigger cre8-front-nav-link <?php echo $frontActive === 'events' ? 'active is-active' : ''; ?>" type="button" aria-haspopup="true">
                <i class="bi bi-calendar-event"></i> <span data-i18n="header.events">Events</span> <i class="bi bi-chevron-down front-nav-caret"></i>
            </button>
            <div class="front-nav-dropdown" role="menu">
                <a href="<?php echo htmlspecialchars($eventsUrl); ?>" role="menuitem" data-i18n="header.events">Events</a>
                <a href="<?php echo htmlspecialchars($forumUrl); ?>" role="menuitem" data-i18n="header.forum">Forum</a>
            </div>
        </li>

        <li class="front-nav-item">
            <a href="<?php echo htmlspecialchars($reclamationUrl); ?>" class="cre8-front-nav-link <?php echo $frontActive === 'reclamation' ? 'active is-active' : ''; ?>">
                <i class="bi bi-flag"></i> <span data-i18n="header.complaints">Complaints</span>
            </a>
        </li>

        <li class="front-nav-item front-notification-item">
            <?php
            $notificationWidgetPath = __DIR__ . '/../condidature/notification_widget.php';
            if (is_file($notificationWidgetPath)) {
                require $notificationWidgetPath;
            } else {
                ?>
                <button class="front-icon-btn front-notification-btn" type="button" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>
                <?php
            }
            ?>
        </li>
    </ul>

    <div class="front-nav-right cre8-front-user front-profile-menu" tabindex="0" role="button" aria-haspopup="true" aria-label="Profile menu" data-i18n-title="header.profileMenuAria">
        <div class="front-nav-badge cre8-front-role-pill">
            <?php echo htmlspecialchars($userName); ?>
        </div>
        <?php if ($profileImageUrl): ?>
            <img class="front-nav-avatar cre8-front-avatar" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile photo">
        <?php else: ?>
            <div class="front-nav-avatar cre8-front-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
        <?php endif; ?>
        <i class="bi bi-chevron-down front-profile-caret" aria-hidden="true"></i>

        <div class="front-profile-dropdown" role="menu">
            <a class="front-profile-menu-row front-profile-settings-row" href="<?php echo htmlspecialchars($profileSettingsUrl); ?>" role="menuitem">
                <span class="front-profile-row-left">
                    <span class="front-profile-row-icon"><i class="bi bi-person-gear"></i></span>
                    <span class="front-profile-row-text" data-i18n="header.profileSettings">Profile settings</span>
                </span>
            </a>

            <button type="button" class="front-profile-menu-row front-profile-language-toggle" data-lang-toggle role="menuitem" aria-label="Switch language" data-i18n-title="header.switchLanguageTitle">
                <span class="front-profile-row-left">
                    <span class="front-profile-row-icon"><i class="bi bi-globe2"></i></span>
                    <span class="front-profile-row-text" data-i18n="header.language">Language</span>
                </span>
                <span class="front-profile-lang-mini" aria-hidden="true">
                    <span class="front-profile-lang-chip" data-profile-lang-chip="en">EN</span>
                    <span class="front-profile-lang-chip" data-profile-lang-chip="fr">FR</span>
                </span>
                <span class="sr-only">Current language: <span data-profile-lang-current>EN</span></span>
            </button>

            <button class="front-profile-menu-row front-profile-theme-btn" id="themeBtn" title="Toggle dark mode" data-i18n-title="header.toggleThemeTitle" type="button" role="menuitem">
                <span class="front-profile-row-left">
                    <span class="front-profile-row-icon"><i class="bi bi-moon-stars" id="themeIcon"></i></span>
                    <span class="front-profile-row-text" data-i18n="header.appearance">Appearance</span>
                </span>
                <span class="front-profile-theme-switch" aria-hidden="true">
                    <span class="front-profile-theme-dot"></span>
                    <span class="front-profile-theme-label"><span class="theme-label-light" data-i18n="header.themeLight">Light</span><span class="theme-label-dark" data-i18n="header.themeDark">Dark</span></span>
                </span>
            </button>

            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="front-profile-menu-row front-profile-logout" role="menuitem">
                <span class="front-profile-row-left">
                    <span class="front-profile-row-icon"><i class="bi bi-box-arrow-right"></i></span>
                    <span class="front-profile-row-text" data-i18n="header.logout">Logout</span>
                </span>
            </a>
        </div>
    </div>
</nav>


<script>
(function () {
    var headerTranslations = {
        en: {
            'header.navAria': 'FrontOffice navigation',
            'header.homeAria': 'Cre8Connect home',
            'header.profileMenuAria': 'Profile menu',
            'header.home': 'Home',
            'header.collaborations': 'Collaborations',
            'header.offers': 'Offers',
            'header.applications': 'Applications',
            'header.campaigns': 'Campaigns',
            'header.products': 'Products',
            'header.contracts': 'Contracts',
            'header.posts': 'Posts',
            'header.mySpace': 'My Space',
            'header.feeds': 'Feeds',
            'header.createPost': 'Create Post',
            'header.events': 'Events',
            'header.forum': 'Forum',
            'header.complaints': 'Complaints',
            'header.profileSettings': 'Profile settings',
            'header.language': 'Language',
            'header.appearance': 'Appearance',
            'header.themeLight': 'Light',
            'header.themeDark': 'Dark',
            'header.logout': 'Logout',
            'header.switchLanguageTitle': 'Switch language',
            'header.toggleThemeTitle': 'Toggle dark mode'
        },
        fr: {
            'header.navAria': 'Navigation FrontOffice',
            'header.homeAria': 'Accueil Cre8Connect',
            'header.profileMenuAria': 'Menu du profil',
            'header.home': 'Accueil',
            'header.collaborations': 'Collaborations',
            'header.offers': 'Offres',
            'header.applications': 'Candidatures',
            'header.campaigns': 'Campagnes',
            'header.products': 'Produits',
            'header.contracts': 'Contrats',
            'header.posts': 'Publications',
            'header.mySpace': 'Mon espace',
            'header.feeds': 'Fil d’actualité',
            'header.createPost': 'Créer une publication',
            'header.events': 'Événements',
            'header.forum': 'Forum',
            'header.complaints': 'Réclamations',
            'header.profileSettings': 'Paramètres du profil',
            'header.language': 'Langue',
            'header.appearance': 'Apparence',
            'header.themeLight': 'Clair',
            'header.themeDark': 'Sombre',
            'header.logout': 'Déconnexion',
            'header.switchLanguageTitle': 'Changer de langue',
            'header.toggleThemeTitle': 'Changer le mode d’affichage'
        }
    };

    window.cre8TranslationQueue = window.cre8TranslationQueue || [];
    window.cre8TranslationQueue.push(headerTranslations);

    function registerHeaderTranslations() {
        if (typeof window.cre8RegisterTranslations === 'function') {
            window.cre8RegisterTranslations(headerTranslations);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerHeaderTranslations);
    } else {
        registerHeaderTranslations();
    }
})();
</script>

<script>
(function () {
    if (window.__cre8ProfileMenuLanguageToggle) return;
    window.__cre8ProfileMenuLanguageToggle = true;

    function readLang() {
        try {
            return localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang') || 'en';
        } catch (e) {
            return 'en';
        }
    }

    function normalizeLang(lang) {
        return lang === 'fr' ? 'fr' : 'en';
    }

    function updateProfileLanguageState(lang) {
        var activeLang = normalizeLang(lang || readLang());
        document.querySelectorAll('[data-profile-lang-current]').forEach(function (node) {
            node.textContent = activeLang.toUpperCase();
        });
        document.querySelectorAll('[data-profile-lang-chip]').forEach(function (chip) {
            var isActive = chip.getAttribute('data-profile-lang-chip') === activeLang;
            chip.classList.toggle('is-active', isActive);
        });
        document.querySelectorAll('[data-lang-choice]').forEach(function (choice) {
            var isActive = choice.getAttribute('data-lang-choice') === activeLang;
            choice.classList.toggle('is-active', isActive);
            choice.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function setLang(lang) {
        var next = normalizeLang(lang);
        if (typeof window.cre8SetLanguage === 'function') {
            window.cre8SetLanguage(next);
        } else {
            try {
                localStorage.setItem('cre8_front_lang', next);
            } catch (e) {}
            document.dispatchEvent(new CustomEvent('cre8:languagechange', { detail: { lang: next } }));
        }
        updateProfileLanguageState(next);
    }

    document.addEventListener('click', function (event) {
        var row = event.target.closest('[data-lang-toggle]');
        if (!row) return;
        event.preventDefault();
        var current = normalizeLang(readLang());
        setLang(current === 'en' ? 'fr' : 'en');
    });

    function handleLanguageChange(event) {
        updateProfileLanguageState(event.detail && event.detail.lang);
    }

    document.addEventListener('cre8:languagechange', handleLanguageChange);
    window.addEventListener('cre8:languagechange', handleLanguageChange);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { updateProfileLanguageState(); });
    } else {
        updateProfileLanguageState();
    }
})();
</script>
