<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/condidatureC.php';

$candidatureController = new CondidatureC();
$sessionUser = $_SESSION['utilisateur'] ?? [];

if (!isset($sessionUser['id']) || (($sessionUser['role'] ?? '') !== 'admin')) {
    $defaultAdmin = $candidatureController->getDefaultUserByRole('admin');
    if ($defaultAdmin) {
        $_SESSION['utilisateur'] = [
            'id' => (int) $defaultAdmin['id'],
            'role' => 'admin',
            'nom' => $defaultAdmin['nom'],
            'email' => $defaultAdmin['email'],
        ];
        $sessionUser = $_SESSION['utilisateur'];
    }
}

$backActive = 'forum';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office — Forum · Cre8Connect</title>
    <link rel="stylesheet" href="../css/backoffice.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo htmlspecialchars((string) filemtime(__DIR__ . '/../layout/back-layout.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css?v=<?php echo htmlspecialchars((string) (@filemtime(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?: 0), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="../css/new_style_backoffice.css">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
    <div class="container-scroller cre8-admin-page">
        <?php require_once dirname(__DIR__) . '/layout/sidebar.php'; ?>
        <main class="page-body-wrapper cre8-admin-main">
            <?php require_once dirname(__DIR__) . '/layout/header.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper admin-shell">
                    <header class="admin-header card grid-margin">
                        <div class="admin-header-main card-body">
                            <div>
                                <h1>Forum</h1>
                                <p>Community forum administration will appear here when the module is connected.</p>
                            </div>
                        </div>
                    </header>
                    <div class="card grid-margin">
                        <div class="card-body">
                            <p class="text-muted mb-0">No forum data to display yet. This page is ready for future threads and moderation tools.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../layout/back-layout.js?v=<?php echo htmlspecialchars((string) filemtime(__DIR__ . '/../layout/back-layout.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
