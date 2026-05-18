<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../layout/early-theme.php';
require_once __DIR__ . '/../../../Controleur/session_helper.php';
require_once __DIR__ . '/../../../Controleur/serverCenterC.php';

cc_require_hyper_admin('../../FrontOffice/utilisateur/login.php');

$backActive = 'server_center';
$serverCenter = new ServerCenterC(dirname(__DIR__, 3));
$summary = $serverCenter->getServerSummary();
$disk = $serverCenter->getDiskSummary($summary['project_root']);
$database = $serverCenter->getDatabaseStatus();
$folders = $serverCenter->getFolderChecks();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php cre8_bo_early_theme_print_head_script(); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Server Center - Cre8connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../layout/back-layout.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.css')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <style>
        .server-center-page .diag-card {
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.08);
            min-height: 100%;
        }
        .server-center-page .diag-value {
            font-size: 1.05rem;
            font-weight: 700;
            word-break: break-word;
        }
        .server-center-page .folder-table td,
        .server-center-page .folder-table th {
            vertical-align: middle;
        }
        .server-center-page .path-cell {
            max-width: 360px;
            word-break: break-word;
        }
        body.light-mode .server-center-page .diag-card {
            background: #fff;
            color: #111827;
            border-color: #e5e7eb;
        }
    </style>
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper cre8-admin-main">
        <?php require_once __DIR__ . '/../layout/header.php'; ?>
        <div class="main-panel">
            <div class="content-wrapper server-center-page">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <h2 class="mb-1" data-i18n="serverCenter.title">Server Center</h2>
                        <p class="text-muted mb-0" data-i18n="serverCenter.subtitle">Read-only diagnostics for Hyper Admin.</p>
                    </div>
                    <span class="badge bg-info text-dark" data-i18n="serverCenter.readOnlyBadge">Read-only</span>
                </div>

                <div class="alert alert-info" role="alert">
                    <span data-i18n="serverCenter.readOnlyAlert">This page is read-only. It does not execute commands, restart services, delete files, or modify the database.</span>
                </div>

                <div class="row g-4 mb-4">
                    <?php
                    $cards = [
                        ['label' => 'PHP version', 'key' => 'serverCenter.card.phpVersion', 'value' => $summary['php_version']],
                        ['label' => 'OS family', 'key' => 'serverCenter.card.osFamily', 'value' => $summary['os_family'] . ' / ' . $summary['os_name']],
                        ['label' => 'Server software', 'key' => 'serverCenter.card.serverSoftware', 'value' => $summary['server_software']],
                        ['label' => 'Project root', 'key' => 'serverCenter.card.projectRoot', 'value' => $summary['project_root']],
                        ['label' => 'Document root', 'key' => 'serverCenter.card.documentRoot', 'value' => $summary['document_root']],
                        ['label' => 'Current time', 'key' => 'serverCenter.card.currentTime', 'value' => $summary['current_time']],
                    ];
                    foreach ($cards as $card):
                    ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card diag-card">
                                <div class="card-body">
                                    <div class="text-muted small mb-2"><span data-i18n="<?php echo htmlspecialchars($card['key']); ?>"><?php echo htmlspecialchars($card['label']); ?></span></div>
                                    <div class="diag-value"><?php echo htmlspecialchars((string)$card['value']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-7">
                        <div class="card diag-card">
                            <div class="card-body">
                                <h5 class="card-title" data-i18n="serverCenter.disk.title">Disk summary</h5>
                                <?php if ($disk['available']): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span data-i18n="serverCenter.disk.used">Used</span>
                                            <strong><?php echo htmlspecialchars((string)$disk['percent_used']); ?>%</strong>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, max(0, (float)$disk['percent_used'])); ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col">
                                            <div class="text-muted small" data-i18n="serverCenter.disk.total">Total</div>
                                            <strong><?php echo htmlspecialchars($disk['total_human']); ?></strong>
                                        </div>
                                        <div class="col">
                                            <div class="text-muted small" data-i18n="serverCenter.disk.used">Used</div>
                                            <strong><?php echo htmlspecialchars($disk['used_human']); ?></strong>
                                        </div>
                                        <div class="col">
                                            <div class="text-muted small" data-i18n="serverCenter.disk.free">Free</div>
                                            <strong><?php echo htmlspecialchars($disk['free_human']); ?></strong>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0" data-i18n="serverCenter.disk.unavailable">Disk information is unavailable for this path.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card diag-card">
                            <div class="card-body">
                                <h5 class="card-title" data-i18n="serverCenter.database.title">Database status</h5>
                                <span class="badge bg-<?php echo $database['connected'] ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($database['message']); ?>
                                </span>
                                <p class="text-muted mt-3 mb-0"><span data-i18n="serverCenter.database.note">Connection is checked with a safe SELECT 1 query. No database rows are modified.</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card diag-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title" data-i18n="serverCenter.folder.title">Folder health</h5>
                        <div class="table-responsive">
                            <table class="table table-hover folder-table">
                                <thead>
                                <tr>
                                    <th data-i18n="serverCenter.folder.folder">Folder</th>
                                    <th data-i18n="serverCenter.folder.exists">Exists</th>
                                    <th data-i18n="serverCenter.folder.readable">Readable</th>
                                    <th data-i18n="serverCenter.folder.writable">Writable</th>
                                    <th data-i18n="serverCenter.folder.size">Size</th>
                                    <th data-i18n="serverCenter.folder.files">Files</th>
                                    <th data-i18n="serverCenter.folder.directories">Directories</th>
                                    <th data-i18n="serverCenter.folder.note">Note</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($folders as $folder): ?>
                                    <tr>
                                        <td class="path-cell">
                                            <strong><?php echo htmlspecialchars($folder['label']); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($folder['path']); ?></div>
                                        </td>
                                        <td><span class="badge bg-<?php echo $folder['exists'] ? 'success' : 'secondary'; ?>"><span data-i18n="<?php echo $folder['exists'] ? 'common.yes' : 'common.no'; ?>"><?php echo $folder['exists'] ? 'Yes' : 'No'; ?></span></span></td>
                                        <td><span class="badge bg-<?php echo $folder['is_readable'] ? 'success' : 'warning'; ?>"><span data-i18n="<?php echo $folder['is_readable'] ? 'common.yes' : 'common.no'; ?>"><?php echo $folder['is_readable'] ? 'Yes' : 'No'; ?></span></span></td>
                                        <td><span class="badge bg-<?php echo $folder['is_writable'] ? 'success' : 'warning'; ?>"><span data-i18n="<?php echo $folder['is_writable'] ? 'common.yes' : 'common.no'; ?>"><?php echo $folder['is_writable'] ? 'Yes' : 'No'; ?></span></span></td>
                                        <td><?php echo htmlspecialchars($folder['size_human']); ?></td>
                                        <td><?php echo (int)$folder['file_count']; ?></td>
                                        <td><?php echo (int)$folder['directory_count']; ?></td>
                                        <td>
                                            <?php if ($folder['partial']): ?>
                                                <span class="badge bg-info text-dark" data-i18n="serverCenter.folder.partial">Partial</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($folder['note'] ?: '-'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card diag-card">
                    <div class="card-body">
                        <h5 class="card-title" data-i18n="serverCenter.safety.title">Safety checklist</h5>
                        <ul class="mb-0">
                            <li data-i18n="serverCenter.safety.noShell">No shell commands are used by this page.</li>
                            <li data-i18n="serverCenter.safety.noSecrets">No secrets, passwords, cookies, sessions, diagnostic dumps, or config contents are displayed.</li>
                            <li data-i18n="serverCenter.safety.noWrites">No restart, delete, backup, or write action is available here.</li>
                            <li data-i18n="serverCenter.safety.safeScan">Large folder scans stop at a safe file limit and mark results as partial.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    'common.yes': 'Yes',
    'common.no': 'No',
    'serverCenter.title': 'Server Center',
    'serverCenter.subtitle': 'Read-only diagnostics for Hyper Admin.',
    'serverCenter.readOnlyBadge': 'Read-only',
    'serverCenter.readOnlyAlert': 'This page is read-only. It does not execute commands, restart services, delete files, or modify the database.',
    'serverCenter.card.phpVersion': 'PHP version',
    'serverCenter.card.osFamily': 'OS family',
    'serverCenter.card.serverSoftware': 'Server software',
    'serverCenter.card.projectRoot': 'Project root',
    'serverCenter.card.documentRoot': 'Document root',
    'serverCenter.card.currentTime': 'Current time',
    'serverCenter.disk.title': 'Disk summary',
    'serverCenter.disk.used': 'Used',
    'serverCenter.disk.total': 'Total',
    'serverCenter.disk.free': 'Free',
    'serverCenter.disk.unavailable': 'Disk information is unavailable for this path.',
    'serverCenter.database.title': 'Database status',
    'serverCenter.database.note': 'Connection is checked with a safe SELECT 1 query. No database rows are modified.',
    'serverCenter.folder.title': 'Folder health',
    'serverCenter.folder.folder': 'Folder',
    'serverCenter.folder.exists': 'Exists',
    'serverCenter.folder.readable': 'Readable',
    'serverCenter.folder.writable': 'Writable',
    'serverCenter.folder.size': 'Size',
    'serverCenter.folder.files': 'Files',
    'serverCenter.folder.directories': 'Directories',
    'serverCenter.folder.note': 'Note',
    'serverCenter.folder.partial': 'Partial',
    'serverCenter.safety.title': 'Safety checklist',
    'serverCenter.safety.noShell': 'No shell commands are used by this page.',
    'serverCenter.safety.noSecrets': 'No secrets, passwords, cookies, sessions, diagnostic dumps, or config contents are displayed.',
    'serverCenter.safety.noWrites': 'No restart, delete, backup, or write action is available here.',
    'serverCenter.safety.safeScan': 'Large folder scans stop at a safe file limit and mark results as partial.'
  },
  fr: {
    'common.yes': 'Oui',
    'common.no': 'Non',
    'serverCenter.title': 'Centre serveur',
    'serverCenter.subtitle': 'Diagnostics en lecture seule pour Hyper Admin.',
    'serverCenter.readOnlyBadge': 'Lecture seule',
    'serverCenter.readOnlyAlert': 'Cette page est en lecture seule. Elle n execute aucune commande, ne redemarre aucun service, ne supprime aucun fichier et ne modifie pas la base de donnees.',
    'serverCenter.card.phpVersion': 'Version PHP',
    'serverCenter.card.osFamily': 'Famille OS',
    'serverCenter.card.serverSoftware': 'Logiciel serveur',
    'serverCenter.card.projectRoot': 'Racine du projet',
    'serverCenter.card.documentRoot': 'Racine du document',
    'serverCenter.card.currentTime': 'Heure actuelle',
    'serverCenter.disk.title': 'Resume du disque',
    'serverCenter.disk.used': 'Utilise',
    'serverCenter.disk.total': 'Total',
    'serverCenter.disk.free': 'Libre',
    'serverCenter.disk.unavailable': 'Les informations disque sont indisponibles pour ce chemin.',
    'serverCenter.database.title': 'Etat de la base de donnees',
    'serverCenter.database.note': 'La connexion est verifiee avec une requete SELECT 1 sure. Aucune ligne de la base n est modifiee.',
    'serverCenter.folder.title': 'Etat des dossiers',
    'serverCenter.folder.folder': 'Dossier',
    'serverCenter.folder.exists': 'Existe',
    'serverCenter.folder.readable': 'Lisible',
    'serverCenter.folder.writable': 'Modifiable',
    'serverCenter.folder.size': 'Taille',
    'serverCenter.folder.files': 'Fichiers',
    'serverCenter.folder.directories': 'Repertoires',
    'serverCenter.folder.note': 'Note',
    'serverCenter.folder.partial': 'Partiel',
    'serverCenter.safety.title': 'Liste de securite',
    'serverCenter.safety.noShell': 'Aucune commande shell n est utilisee par cette page.',
    'serverCenter.safety.noSecrets': 'Aucun secret, mot de passe, cookie, session, dump de diagnostic ou contenu de configuration n est affiche.',
    'serverCenter.safety.noWrites': 'Aucune action de redemarrage, suppression, sauvegarde ou ecriture n est disponible ici.',
    'serverCenter.safety.safeScan': 'Les grands scans de dossiers s arretent a une limite sure de fichiers et marquent les resultats comme partiels.'
  }
});
</script>
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
<script src="../layout/back-layout.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/back-layout.js')); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
