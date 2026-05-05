<?php
session_start();

require_once __DIR__ . '/../../../Controleur/offreC.php';

$controller = new OffreC();
$errors = [];

if (isset($_GET['logout'])) {
    unset($_SESSION['utilisateur']);
}

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

$cre8shieldSuspiciousEmails = [
    'cre8shield.fake.brand@cre8connect.test',
    'cre8shield.fake.creator@cre8connect.test',
];

$cre8shieldVictimEmails = [
    'sami@cre8connect.tn',
    'fitboost@cre8connect.tn',
];

$cre8shieldSuspiciousShownInDirectory = false;
$cre8shieldVictimShownInDirectory = false;

/** @return 'suspicious'|'victim'|null */
$cre8shieldLoginHighlight = static function (string $email) use ($cre8shieldSuspiciousEmails, $cre8shieldVictimEmails): ?string {
    $e = strtolower(trim($email));

    foreach ($cre8shieldSuspiciousEmails as $addr) {
        if ($e === strtolower($addr)) {
            return 'suspicious';
        }
    }

    foreach ($cre8shieldVictimEmails as $addr) {
        if ($e === strtolower($addr)) {
            return 'victim';
        }
    }

    return null;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Login - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <link rel="stylesheet" href="offre.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/offre.css')); ?>">
    <style>
        /* Demo-only: highlights Cre8Shield seeded test accounts on this page only */
        .cre8shield-demo-user {
            border: 2px solid rgba(239, 68, 68, 0.9) !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18), 0 0 22px rgba(239, 68, 68, 0.45) !important;
        }

        .cre8shield-demo-badge {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
            font-weight: 700;
            font-size: 12px;
        }

        .cre8shield-victim-user {
            border: 2px solid rgba(245, 158, 11, 0.95) !important;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18), 0 0 22px rgba(245, 158, 11, 0.45) !important;
        }

        .cre8shield-victim-badge {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.14);
            color: #92400e;
            font-weight: 700;
            font-size: 12px;
        }

        .cre8shield-demo-reference {
            margin-top: 2rem;
            border-color: rgba(239, 68, 68, 0.35);
        }

        .cre8shield-demo-reference-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cre8shield-demo-reference-item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid rgba(239, 68, 68, 0.9);
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18), 0 0 22px rgba(239, 68, 68, 0.45);
            font-family: ui-monospace, monospace;
            font-size: 0.9rem;
        }

        .cre8shield-victim-reference {
            margin-top: 2rem;
            border-color: rgba(245, 158, 11, 0.35);
        }

        .cre8shield-victim-reference-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cre8shield-victim-reference-item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid rgba(245, 158, 11, 0.95);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18), 0 0 22px rgba(245, 158, 11, 0.45);
            font-family: ui-monospace, monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <main class="container py-5">
        <div class="offre-page-shell login-directory-shell">
            <section class="module-hero">
                <div class="theme-toggle-corner"><?php require __DIR__ . '/../condidature/theme_toggle.php'; ?></div>
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
                                <?php
                                $cre8shieldHl = $cre8shieldLoginHighlight((string) ($user['email'] ?? ''));
                                if ($cre8shieldHl === 'suspicious') {
                                    $cre8shieldSuspiciousShownInDirectory = true;
                                } elseif ($cre8shieldHl === 'victim') {
                                    $cre8shieldVictimShownInDirectory = true;
                                }
                                $cre8shieldCardClass = '';
                                if ($cre8shieldHl === 'suspicious') {
                                    $cre8shieldCardClass = ' cre8shield-demo-user';
                                } elseif ($cre8shieldHl === 'victim') {
                                    $cre8shieldCardClass = ' cre8shield-victim-user';
                                }
                                ?>
                                <form method="post" action="login.php" class="login-user-form">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="login-user-card<?php echo $cre8shieldCardClass; ?>">
                                        <?php if ($cre8shieldHl === 'suspicious'): ?>
                                            <span class="cre8shield-demo-badge">Cre8Shield suspicious test user</span>
                                        <?php elseif ($cre8shieldHl === 'victim'): ?>
                                            <span class="cre8shield-victim-badge">Cre8Shield victim test user</span>
                                        <?php endif; ?>
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
                                <?php
                                $cre8shieldHl = $cre8shieldLoginHighlight((string) ($user['email'] ?? ''));
                                if ($cre8shieldHl === 'suspicious') {
                                    $cre8shieldSuspiciousShownInDirectory = true;
                                } elseif ($cre8shieldHl === 'victim') {
                                    $cre8shieldVictimShownInDirectory = true;
                                }
                                $cre8shieldCardClass = '';
                                if ($cre8shieldHl === 'suspicious') {
                                    $cre8shieldCardClass = ' cre8shield-demo-user';
                                } elseif ($cre8shieldHl === 'victim') {
                                    $cre8shieldCardClass = ' cre8shield-victim-user';
                                }
                                ?>
                                <form method="post" action="login.php" class="login-user-form">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="login-user-card<?php echo $cre8shieldCardClass; ?>">
                                        <?php if ($cre8shieldHl === 'suspicious'): ?>
                                            <span class="cre8shield-demo-badge">Cre8Shield suspicious test user</span>
                                        <?php elseif ($cre8shieldHl === 'victim'): ?>
                                            <span class="cre8shield-victim-badge">Cre8Shield victim test user</span>
                                        <?php endif; ?>
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
                            <?php
                            $cre8shieldHl = $cre8shieldLoginHighlight((string) ($user['email'] ?? ''));
                            if ($cre8shieldHl === 'suspicious') {
                                $cre8shieldSuspiciousShownInDirectory = true;
                            } elseif ($cre8shieldHl === 'victim') {
                                $cre8shieldVictimShownInDirectory = true;
                            }
                            $cre8shieldCardClass = '';
                            if ($cre8shieldHl === 'suspicious') {
                                $cre8shieldCardClass = ' cre8shield-demo-user';
                            } elseif ($cre8shieldHl === 'victim') {
                                $cre8shieldCardClass = ' cre8shield-victim-user';
                            }
                            ?>
                            <form method="post" action="login.php" class="login-user-form">
                                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="login-user-card login-user-card-admin<?php echo $cre8shieldCardClass; ?>">
                                    <?php if ($cre8shieldHl === 'suspicious'): ?>
                                        <span class="cre8shield-demo-badge">Cre8Shield suspicious test user</span>
                                    <?php elseif ($cre8shieldHl === 'victim'): ?>
                                        <span class="cre8shield-victim-badge">Cre8Shield victim test user</span>
                                    <?php endif; ?>
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

            <?php if (!$cre8shieldSuspiciousShownInDirectory): ?>
                <section class="section-card cre8shield-demo-reference" aria-label="Cre8Shield suspicious test user emails">
                    <div class="login-directory-head">
                        <div>
                            <span class="login-directory-kicker">Testing</span>
                            <h2 class="section-title">Cre8Shield suspicious test users</h2>
                            <p class="section-subtitle text-muted">Fake suspicious accounts for Cre8Shield tests. They are not shown in the lists above right now.</p>
                        </div>
                    </div>
                    <ul class="cre8shield-demo-reference-list">
                        <?php foreach ($cre8shieldSuspiciousEmails as $addr): ?>
                            <li class="cre8shield-demo-reference-item">
                                <span class="cre8shield-demo-badge">Cre8Shield suspicious test user</span>
                                <span><?php echo htmlspecialchars($addr, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!$cre8shieldVictimShownInDirectory): ?>
                <section class="section-card cre8shield-victim-reference" aria-label="Cre8Shield victim test user emails">
                    <div class="login-directory-head">
                        <div>
                            <span class="login-directory-kicker">Testing</span>
                            <h2 class="section-title">Cre8Shield victim test users</h2>
                            <p class="section-subtitle text-muted">Victim scenario accounts for Cre8Shield tests. They are not shown in the lists above right now.</p>
                        </div>
                    </div>
                    <ul class="cre8shield-victim-reference-list">
                        <?php foreach ($cre8shieldVictimEmails as $addr): ?>
                            <li class="cre8shield-victim-reference-item">
                                <span class="cre8shield-victim-badge">Cre8Shield victim test user</span>
                                <span><?php echo htmlspecialchars($addr, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
