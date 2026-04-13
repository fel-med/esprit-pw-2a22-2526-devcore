<?php
session_start();

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$errors = [];

function getWorkspaceRedirectByRole($role)
{
    return match ((string) $role) {
        'marque' => 'brand_index.php',
        'createur' => 'creator_list.php',
        'admin' => '../../BackOffice/offre/index.php',
        default => 'login.php',
    };
}

function getWorkspaceActionLabel($role)
{
    return match ((string) $role) {
        'marque' => 'Open brand workspace',
        'createur' => 'Open creator inbox',
        'admin' => 'Open admin dashboard',
        default => 'Open workspace',
    };
}

function getWorkspaceRoleLabel($role)
{
    return match ((string) $role) {
        'marque' => 'Brand',
        'createur' => 'Creator',
        'admin' => 'Admin',
        default => ucwords((string) $role),
    };
}

function getUserStatusLabel($status)
{
    return match ((string) $status) {
        'actif' => 'Ready',
        'en_attente' => 'Pending',
        'suspendu' => 'Limited',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim((string) ($_POST['id'] ?? ''));

    if ($id === '') {
        $errors[] = 'Please choose a user to continue.';
    } elseif (!ctype_digit($id)) {
        $errors[] = 'The selected user ID is invalid.';
    }

    if (empty($errors)) {
        $userMap = $controller->getUsersByIds([(int) $id]);
        $user = $userMap[(int) $id] ?? null;
        $role = $user['role'] ?? '';
        $status = $user['statut'] ?? '';

        if ($user && in_array($role, ['marque', 'createur', 'admin'], true) && $status !== 'bloque') {
            $_SESSION['utilisateur'] = [
                'id' => (int) $user['id'],
                'role' => $role,
            ];

            header('Location: ' . getWorkspaceRedirectByRole($role));
            exit;
        }

        $errors[] = 'This account is not available for the offer module right now.';
    }
}

$directory = $controller->getLoginDirectoryUsers(['marque', 'createur', 'admin']);
$brands = $directory['marque'] ?? [];
$creators = $directory['createur'] ?? [];
$admins = $directory['admin'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Login - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css">
</head>
<body>
    <main class="container py-5">
        <div class="offre-page-shell login-directory-shell">
            <section class="module-hero">
                <span class="module-eyebrow">Workspace access</span>
                <h1 class="display-5 fw-bold mt-3 mb-2 gradient-title">Choose a user and enter the module</h1>
                <p class="lead text-muted">Pick a brand, creator, or admin account below. Each card logs you in immediately and sends you to the matching workspace.</p>
            </section>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="login-directory-board">
                <section class="section-card login-directory-section">
                    <div class="login-directory-head">
                        <div>
                            <span class="login-directory-kicker">Brands</span>
                            <h2 class="section-title">Brand accounts</h2>
                            <p class="section-subtitle">Open the FrontOffice offer management side as a brand.</p>
                        </div>
                        <span class="offer-chip"><?php echo count($brands); ?> users</span>
                    </div>

                    <?php if (!empty($brands)): ?>
                        <div class="login-user-grid">
                            <?php foreach ($brands as $user): ?>
                                <form method="post" action="login.php" class="login-user-form">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="login-user-card">
                                        <span class="login-user-role brand">Brand</span>
                                        <strong><?php echo htmlspecialchars($user['nom']); ?></strong>
                                        <span class="login-user-id">ID #<?php echo (int) $user['id']; ?></span>
                                        <span class="login-user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        <span class="login-user-meta">
                                            <span class="creator-pill"><?php echo htmlspecialchars(getUserStatusLabel($user['statut'])); ?></span>
                                        </span>
                                        <span class="login-user-action"><?php echo htmlspecialchars(getWorkspaceActionLabel($user['role'])); ?></span>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-card login-empty-card">
                            <div class="empty-state-icon">!</div>
                            <h3 class="section-title">No brand accounts available</h3>
                            <p class="section-subtitle">Brand users will appear here as soon as they are available for login.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="section-card login-directory-section">
                    <div class="login-directory-head">
                        <div>
                            <span class="login-directory-kicker">Creators</span>
                            <h2 class="section-title">Creator accounts</h2>
                            <p class="section-subtitle">Open the invitation inbox as a creator and respond to offers.</p>
                        </div>
                        <span class="offer-chip"><?php echo count($creators); ?> users</span>
                    </div>

                    <?php if (!empty($creators)): ?>
                        <div class="login-user-grid">
                            <?php foreach ($creators as $user): ?>
                                <form method="post" action="login.php" class="login-user-form">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="login-user-card">
                                        <span class="login-user-role creator">Creator</span>
                                        <strong><?php echo htmlspecialchars($user['nom']); ?></strong>
                                        <span class="login-user-id">ID #<?php echo (int) $user['id']; ?></span>
                                        <span class="login-user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        <span class="login-user-meta">
                                            <span class="creator-pill"><?php echo htmlspecialchars(getUserStatusLabel($user['statut'])); ?></span>
                                        </span>
                                        <span class="login-user-action"><?php echo htmlspecialchars(getWorkspaceActionLabel($user['role'])); ?></span>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-card login-empty-card">
                            <div class="empty-state-icon">!</div>
                            <h3 class="section-title">No creator accounts available</h3>
                            <p class="section-subtitle">Creator users will appear here as soon as they are available for login.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="section-card login-admin-section">
                <div class="login-directory-head">
                    <div>
                        <span class="login-directory-kicker">Admin</span>
                        <h2 class="section-title">Admin access</h2>
                        <p class="section-subtitle">Switch directly to the BackOffice dashboard for offer administration.</p>
                    </div>
                    <span class="offer-chip"><?php echo count($admins); ?> users</span>
                </div>

                <?php if (!empty($admins)): ?>
                    <div class="login-admin-grid">
                        <?php foreach ($admins as $user): ?>
                            <form method="post" action="login.php" class="login-user-form">
                                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="login-user-card login-user-card-admin">
                                    <span class="login-user-role admin"><?php echo htmlspecialchars(getWorkspaceRoleLabel($user['role'])); ?></span>
                                    <strong><?php echo htmlspecialchars($user['nom']); ?></strong>
                                    <span class="login-user-id">ID #<?php echo (int) $user['id']; ?></span>
                                    <span class="login-user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                    <span class="login-user-meta">
                                        <span class="creator-pill"><?php echo htmlspecialchars(getUserStatusLabel($user['statut'])); ?></span>
                                    </span>
                                    <span class="login-user-action"><?php echo htmlspecialchars(getWorkspaceActionLabel($user['role'])); ?></span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card login-empty-card">
                        <div class="empty-state-icon">!</div>
                        <h3 class="section-title">No admin account available</h3>
                        <p class="section-subtitle">Add an admin account to enable direct dashboard access from this module login.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
