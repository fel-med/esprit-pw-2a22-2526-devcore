<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
cc_start_session();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
require_once '../../../Controleur/commentC.php';

$commentC = new CommentC();
$pageTitle = 'All Comments';
$currentPage = 'comments';

$searchType = trim($_GET['searchType'] ?? '');
$keyword    = trim($_GET['keyword']    ?? '');
$comments   = ($searchType !== '' || $keyword !== '')
    ? $commentC->searchCommentsAdmin($searchType, $keyword)
    : $commentC->listAllCommentsAdmin();

$stats = $commentC->getAdminStats();

$totalComments = (int)($stats['totalComments'] ?? 0);
$totalLikes    = (int)($stats['totalLikes']    ?? 0);
$totalDislikes = (int)($stats['totalDislikes'] ?? 0);
$approvalPct   = ($totalLikes + $totalDislikes) > 0
    ? round($totalLikes / ($totalLikes + $totalDislikes) * 100) : 0;

$perPageOptions = [5, 10, 25, 50];
$perPage = (int)($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}
$totalFiltered = count($comments);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$visibleComments = array_slice($comments, $offset, $perPage);

$commentsJson = json_encode(array_map(fn($c) => [
    'user'       => $c['userName'] ?? ('User #' . $c['idUser']),
    'type'       => $c['commentedItem'] ?? 'post',
    'likes'      => (int)($c['numberOfLike']    ?? 0),
    'dislikes'   => (int)($c['numberOfDislike'] ?? 0),
    'hasText'    => !empty(trim((string)($c['text']    ?? ''))),
    'hasSticker' => !empty($c['Sticker'] ?? $c['sticker'] ?? ''),
    'hasImage'   => !empty($c['image'] ?? ''),
    'text'       => mb_strimwidth((string)($c['text'] ?? ''), 0, 40, '…'),
], $comments), JSON_UNESCAPED_UNICODE);

function community_comment_asset_version($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}

function community_comment_page_items($current, $total) {
    if ($total <= 7) {
        return range(1, $total);
    }

    $pages = [1, $total, $current, $current - 1, $current + 1];
    $pages = array_values(array_unique(array_filter($pages, fn($p) => $p >= 1 && $p <= $total)));
    sort($pages);

    $result = [];
    $previous = null;
    foreach ($pages as $p) {
        if ($previous !== null && $p - $previous > 1) {
            $result[] = '…';
        }
        $result[] = $p;
        $previous = $p;
    }
    return $result;
}

$pageUrl = function ($targetPage) use ($perPage) {
    $params = $_GET;
    $params['page'] = $targetPage;
    $params['per_page'] = $perPage;
    return './index.php?' . http_build_query($params);
};

if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comments Dashboard — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= community_comment_asset_version(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= community_comment_asset_version(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= community_comment_asset_version(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= community_comment_asset_version(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<link rel="stylesheet" href="../community-center-admin.css<?= community_comment_asset_version(__DIR__ . '/../community-center-admin.css') ?>">
<link rel="stylesheet" href="../unified-table-admin.css<?= community_comment_asset_version(__DIR__ . '/../unified-table-admin.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>

<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'comments';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
        <div class="content-wrapper community-center-shell">
            <section class="cc-page-head">
                <div>
                    <p class="cc-kicker" data-i18n="community.kicker">Community Center</p>
                    <h1 data-i18n="comments.title">Comments Dashboard</h1>
                    <p data-i18n="comments.subtitle">Search replies, inspect comment targets, and moderate conversation activity.</p>
                </div>
            </section>

            <nav class="cc-entity-tabs" aria-label="Community Center sections" data-i18n-aria-label="community.tabsAria">
                <a class="cc-entity-tab" href="../post/index.php">
                    <span class="cc-tab-icon"><i class="mdi mdi-newspaper-variant-outline"></i></span>
                    <span><strong data-i18n="community.tab.posts">Posts</strong><small data-i18n="community.tab.postsSub">Content moderation and performance</small></span>
                </a>
                <a class="cc-entity-tab is-active" href="../comment/index.php" aria-current="page">
                    <span class="cc-tab-icon"><i class="mdi mdi-comment-multiple-outline"></i></span>
                    <span><strong data-i18n="community.tab.comments">Comments</strong><small data-i18n="community.tab.commentsSub">Replies, reactions, and links</small></span>
                </a>
            </nav>

            <section class="cc-statistics-panel" data-cc-stats>
                <div class="cc-section-head">
                    <div>
                        <h2 data-i18n="comments.stats.title">Comment indicators</h2>
                        <p data-i18n="comments.stats.subtitle">Conversation volume, reactions, and format breakdown.</p>
                    </div>
                    <button type="button" class="cc-secondary-btn" data-cc-stats-toggle data-label-hide="Hide statistics" data-label-show="Show statistics" data-i18n="common.hideStatistics">Hide statistics</button>
                </div>

                <div class="cc-kpi-grid">
                    <article class="cc-kpi-card cc-kpi-purple">
                        <span data-i18n="comments.kpi.total">Total comments</span>
                        <strong><?= number_format($totalComments, 0, ',', ' ') ?></strong>
                        <small data-i18n="comments.kpi.totalSub">Comments and replies</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-pink">
                        <span data-i18n="comments.kpi.likes">Total likes</span>
                        <strong><?= number_format($totalLikes, 0, ',', ' ') ?></strong>
                        <small data-i18n="comments.kpi.likesSub">Positive reactions</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-yellow">
                        <span data-i18n="comments.kpi.dislikes">Total dislikes</span>
                        <strong><?= number_format($totalDislikes, 0, ',', ' ') ?></strong>
                        <small data-i18n="comments.kpi.dislikesSub">Negative reactions</small>
                    </article>
                    <article class="cc-kpi-card cc-kpi-green">
                        <span data-i18n="comments.kpi.approval">Approval rate</span>
                        <strong><?= $approvalPct ?>%</strong>
                        <small data-i18n="comments.kpi.approvalSub">Likes vs reactions</small>
                    </article>
                </div>

                <div class="cc-stats-body cc-charts-grid">
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.engagement">Engagement Breakdown</h3><p data-i18n="comments.chart.engagementSub">Likes, dislikes, and neutral comments.</p></div>
                        <div class="cc-chart-legend" id="leg-engagement"></div>
                        <div class="cc-chart-canvas"><canvas id="chartEngagement"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.target">Comment Target</h3><p data-i18n="comments.chart.targetSub">Comments on posts vs replies.</p></div>
                        <div class="cc-chart-legend" id="leg-target"></div>
                        <div class="cc-chart-canvas"><canvas id="chartTarget"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.format">Comment Format</h3><p data-i18n="comments.chart.formatSub">Text, stickers, and images.</p></div>
                        <div class="cc-chart-legend" id="leg-format"></div>
                        <div class="cc-chart-canvas"><canvas id="chartFormat"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.users">User Activity</h3><p data-i18n="comments.chart.usersSub">Top users by comment volume.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartUsers"></canvas></div>
                    </article>
                    <article class="cc-chart-card cc-chart-wide">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.topLikes">Top Comments by Likes</h3><p data-i18n="comments.chart.topLikesSub">Most liked comments and dislikes.</p></div>
                        <div class="cc-chart-canvas"><canvas id="chartTopComments"></canvas></div>
                    </article>
                    <article class="cc-chart-card">
                        <div class="cc-chart-head"><h3 data-i18n="comments.chart.polar">Cumulative Likes by User</h3><p data-i18n="comments.chart.polarSub">Popularity distribution.</p></div>
                        <div class="cc-chart-legend" id="leg-polar"></div>
                        <div class="cc-chart-canvas"><canvas id="chartPolar"></canvas></div>
                    </article>
                </div>
            </section>

            <section class="cc-filter-card">
                <div class="cc-filter-head">
                    <div>
                        <h2 data-i18n="comments.filter.title">Filter comments</h2>
                        <p data-i18n="comments.filter.subtitle">Search by post ID, comment ID, creator name, or creator ID.</p>
                    </div>
                </div>
                <form method="GET" class="cc-filter-grid">
                    <label class="cc-filter-field">
                        <span data-i18n="comments.filter.searchBy">Search by</span>
                        <select name="searchType">
                            <option value="" data-i18n-opt="comments.filter.all">All comments</option>
                            <option value="postId" <?= $searchType === 'postId' ? 'selected' : '' ?> data-i18n-opt="comments.filter.postId">Post ID</option>
                            <option value="commentId" <?= $searchType === 'commentId' ? 'selected' : '' ?> data-i18n-opt="comments.filter.commentId">Comment ID (show replies)</option>
                            <option value="creator" <?= $searchType === 'creator' ? 'selected' : '' ?> data-i18n-opt="comments.filter.creator">Creator</option>
                        </select>
                    </label>
                    <label class="cc-filter-field cc-filter-wide">
                        <span data-i18n="common.keyword">Keyword</span>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Enter post id, comment id, creator name, or creator id" data-i18n-placeholder="comments.filter.placeholder">
                    </label>
                    <label class="cc-filter-field">
                        <span data-i18n="common.rows">Rows</span>
                        <select name="per_page">
                            <?php foreach ($perPageOptions as $option) : ?>
                                <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="cc-filter-actions">
                        <button type="submit" class="cc-primary-btn"><i class="mdi mdi-magnify"></i> <span data-i18n="common.search">Search</span></button>
                        <a href="./index.php" class="cc-secondary-btn" data-i18n="common.reset">Reset</a>
                    </div>
                </form>
            </section>

            <section class="cc-table-card">
                <div class="cc-table-head">
                    <div>
                        <h2 data-i18n="comments.table.title">Comments moderation list</h2>
                        <p><span><?= number_format($totalFiltered, 0, ',', ' ') ?></span> <span data-i18n="comments.table.found">comments found</span></p>
                    </div>
                </div>

                <div id="ccResultsRegion" class="cc-results-region">
                    <?php if ($searchType === 'postId' && $keyword !== '') : ?>
                        <div class="cc-alert">
                            Showing comments linked to post <strong>#<?= htmlspecialchars($keyword) ?></strong>.
                        </div>
                    <?php endif; ?>

                    <?php if (empty($visibleComments)) : ?>
                        <div class="cc-empty-state">
                            <span><i class="mdi mdi-comment-alert-outline"></i></span>
                            <strong data-i18n="comments.empty.title">No comments found</strong>
                            <p data-i18n="comments.empty.subtitle">Try another search or reset the filters.</p>
                        </div>
                    <?php else : ?>
                        <div class="cc-table-wrap">
                            <table class="cc-table cc-comments-table" id="commentsTable">
                                <thead>
                                    <tr>
                                        <th data-i18n="comments.table.id">Comment ID</th>
                                        <th data-i18n="comments.table.target">Target</th>
                                        <th data-i18n="comments.table.creator">Creator</th>
                                        <th data-i18n="comments.table.text">Text</th>
                                        <th data-i18n="comments.table.sticker">Sticker</th>
                                        <th data-i18n="comments.table.image">Image</th>
                                        <th data-i18n="comments.table.likes">Likes</th>
                                        <th data-i18n="comments.table.dislikes">Dislikes</th>
                                        <th data-i18n="common.actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="commentsTableBody">
                                <?php foreach ($visibleComments as $comment) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$comment['id']) ?></td>
                                        <td>
                                            <div class="cc-person-cell">
                                                <strong><?= htmlspecialchars((string)$comment['commentedItem']) ?> #<?= htmlspecialchars((string)$comment['idCommentedElement']) ?></strong>
                                                <span data-i18n="<?= ($comment['commentedItem'] ?? '') === 'post' ? 'comments.target.postDiscussion' : 'comments.target.replyThread' ?>"><?= ($comment['commentedItem'] ?? '') === 'post' ? 'Post discussion' : 'Reply thread' ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cc-person-cell">
                                                <strong><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></strong>
                                                <span>#<?= htmlspecialchars((string)$comment['idUser']) ?></span>
                                            </div>
                                        </td>
                                        <td><div class="cc-excerpt"><?= htmlspecialchars(mb_strimwidth((string)($comment['text'] ?? ''), 0, 120, '...')) ?></div></td>
                                        <td><?= !empty($comment['Sticker'] ?? $comment['sticker'] ?? '') ? htmlspecialchars($comment['Sticker'] ?? $comment['sticker']) : '<span class="cc-badge cc-badge-muted" data-i18n="comments.table.none">None</span>' ?></td>
                                        <td>
                                            <?php if (!empty($comment['image'])) : ?>
                                                <img src="../../public/<?= htmlspecialchars($comment['image']) ?>" alt="comment image" data-i18n-title="comments.table.commentImage" class="cc-media-thumb">
                                            <?php else : ?>
                                                <span class="cc-badge cc-badge-muted" data-i18n="comments.table.noImage">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format((int)($comment['numberOfLike'] ?? 0), 0, ',', ' ') ?></td>
                                        <td><?= number_format((int)($comment['numberOfDislike'] ?? 0), 0, ',', ' ') ?></td>
                                        <td>
                                            <div class="cc-actions-stack">
                                                <?php if (($comment['commentedItem'] ?? '') === 'post') : ?>
                                                    <a href="../post/details.php?id=<?= urlencode($comment['idCommentedElement']) ?>" class="cc-action-btn cc-action-primary">
                                                        <i class="mdi mdi-eye-outline"></i> <span data-i18n="community.tab.posts">Post</span>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="./index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="cc-action-btn cc-action-muted">
                                                    <i class="mdi mdi-source-branch"></i> <span data-i18n="comments.action.replies">Replies</span>
                                                </a>
                                                <a href="./delete.php?id=<?= urlencode($comment['id']) ?>&searchType=<?= urlencode($searchType) ?>&keyword=<?= urlencode($keyword) ?>" class="cc-action-btn cc-action-danger js-admin-delete">
                                                    <i class="mdi mdi-delete-outline"></i> <span data-i18n="common.delete">Delete</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="cc-pagination">
                            <p>
                                <span data-i18n="comments.pagination.showing">Showing</span> <?= $totalFiltered === 0 ? 0 : number_format($offset + 1, 0, ',', ' ') ?>–<?= number_format(min($offset + $perPage, $totalFiltered), 0, ',', ' ') ?>
                                <span data-i18n="common.of">of</span> <?= number_format($totalFiltered, 0, ',', ' ') ?> <span data-i18n="community.tab.comments">comments</span> · <span data-i18n="common.page">Page</span> <?= $page ?> <span data-i18n="common.of">of</span> <?= $totalPages ?>
                            </p>
                            <nav aria-label="Comments pagination" data-i18n-aria-label="comments.pagination.aria">
                                <a class="cc-page-btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($pageUrl($page - 1)) ?>">‹</a>
                                <?php foreach (community_comment_page_items($page, $totalPages) as $item) : ?>
                                    <?php if ($item === '…') : ?>
                                        <span class="cc-page-ellipsis">…</span>
                                    <?php else : ?>
                                        <a class="cc-page-btn <?= $item === $page ? 'is-active' : '' ?>" href="<?= htmlspecialchars($pageUrl((int)$item)) ?>"><?= $item ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <a class="cc-page-btn <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($pageUrl($page + 1)) ?>">›</a>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
</div>


<script>
window.cre8BackRegisterTranslations && window.cre8BackRegisterTranslations({
  en: {
    'community.kicker': 'Community Center',
    'community.tabsAria': 'Community Center sections',
    'community.tab.posts': 'Posts',
    'community.tab.postsSub': 'Content moderation and performance',
    'community.tab.comments': 'Comments',
    'community.tab.commentsSub': 'Replies, reactions, and links',
    'comments.title': 'Comments Dashboard',
    'comments.subtitle': 'Search replies, inspect comment targets, and moderate conversation activity.',
    'comments.stats.title': 'Comment indicators',
    'comments.stats.subtitle': 'Conversation volume, reactions, and format breakdown.',
    'comments.kpi.total': 'Total comments',
    'comments.kpi.totalSub': 'Comments and replies',
    'comments.kpi.likes': 'Total likes',
    'comments.kpi.likesSub': 'Positive reactions',
    'comments.kpi.dislikes': 'Total dislikes',
    'comments.kpi.dislikesSub': 'Negative reactions',
    'comments.kpi.approval': 'Approval rate',
    'comments.kpi.approvalSub': 'Likes vs reactions',
    'comments.chart.engagement': 'Engagement Breakdown',
    'comments.chart.engagementSub': 'Likes, dislikes, and neutral comments.',
    'comments.chart.target': 'Comment Target',
    'comments.chart.targetSub': 'Comments on posts vs replies.',
    'comments.chart.format': 'Comment Format',
    'comments.chart.formatSub': 'Text, stickers, and images.',
    'comments.chart.users': 'User Activity',
    'comments.chart.usersSub': 'Top users by comment volume.',
    'comments.chart.topLikes': 'Top Comments by Likes',
    'comments.chart.topLikesSub': 'Most liked comments and dislikes.',
    'comments.chart.polar': 'Cumulative Likes by User',
    'comments.chart.polarSub': 'Popularity distribution.',
    'comments.filter.title': 'Filter comments',
    'comments.filter.subtitle': 'Search by post ID, comment ID, creator name, or creator ID.',
    'comments.filter.searchBy': 'Search by',
    'comments.filter.all': 'All comments',
    'comments.filter.postId': 'Post ID',
    'comments.filter.commentId': 'Comment ID (show replies)',
    'comments.filter.creator': 'Creator',
    'comments.filter.placeholder': 'Enter post id, comment id, creator name, or creator id',
    'comments.table.title': 'Comments moderation list',
    'comments.table.found': 'comments found',
    'comments.table.id': 'Comment ID',
    'comments.table.target': 'Target',
    'comments.table.creator': 'Creator',
    'comments.table.text': 'Text',
    'comments.table.sticker': 'Sticker',
    'comments.table.image': 'Image',
    'comments.table.likes': 'Likes',
    'comments.table.dislikes': 'Dislikes',
    'comments.table.noImage': 'No image',
    'comments.table.commentImage': 'Comment image',
    'comments.empty.title': 'No comments found',
    'comments.empty.subtitle': 'Try another search or reset the filters.',
    'comments.pagination.showing': 'Showing',
    'comments.pagination.aria': 'Comments pagination',
    'comments.action.openTarget': 'Open target',
    'comments.action.viewTarget': 'View target',
    'comments.action.replies': 'Replies',
    'comments.table.none': 'None',
    'comments.target.postDiscussion': 'Post discussion',
    'comments.target.replyThread': 'Reply thread',
    'comments.confirmDelete': 'Are you sure you want to delete this comment?'
  },
  fr: {
    'community.kicker': 'Centre communauté',
    'community.tabsAria': 'Sections du centre communauté',
    'community.tab.posts': 'Publications',
    'community.tab.postsSub': 'Modération et performance du contenu',
    'community.tab.comments': 'Commentaires',
    'community.tab.commentsSub': 'Réponses, réactions et liens',
    'comments.title': 'Tableau des commentaires',
    'comments.subtitle': 'Recherchez les réponses, inspectez les cibles et modérez l’activité des conversations.',
    'comments.stats.title': 'Indicateurs des commentaires',
    'comments.stats.subtitle': 'Volume des conversations, réactions et formats.',
    'comments.kpi.total': 'Total commentaires',
    'comments.kpi.totalSub': 'Commentaires et réponses',
    'comments.kpi.likes': 'Total likes',
    'comments.kpi.likesSub': 'Réactions positives',
    'comments.kpi.dislikes': 'Total dislikes',
    'comments.kpi.dislikesSub': 'Réactions négatives',
    'comments.kpi.approval': 'Taux d’approbation',
    'comments.kpi.approvalSub': 'Likes vs réactions',
    'comments.chart.engagement': 'Répartition de l’engagement',
    'comments.chart.engagementSub': 'Likes, dislikes et commentaires neutres.',
    'comments.chart.target': 'Cible du commentaire',
    'comments.chart.targetSub': 'Commentaires sur publications vs réponses.',
    'comments.chart.format': 'Format des commentaires',
    'comments.chart.formatSub': 'Texte, stickers et images.',
    'comments.chart.users': 'Activité des utilisateurs',
    'comments.chart.usersSub': 'Utilisateurs les plus actifs.',
    'comments.chart.topLikes': 'Commentaires les plus aimés',
    'comments.chart.topLikesSub': 'Commentaires avec le plus de likes et dislikes.',
    'comments.chart.polar': 'Likes cumulés par utilisateur',
    'comments.chart.polarSub': 'Distribution de popularité.',
    'comments.filter.title': 'Filtrer les commentaires',
    'comments.filter.subtitle': 'Rechercher par ID publication, ID commentaire, nom ou ID créateur.',
    'comments.filter.searchBy': 'Rechercher par',
    'comments.filter.all': 'Tous les commentaires',
    'comments.filter.postId': 'ID publication',
    'comments.filter.commentId': 'ID commentaire (afficher réponses)',
    'comments.filter.creator': 'Créateur',
    'comments.filter.placeholder': 'Entrer ID publication, ID commentaire, nom ou ID créateur',
    'comments.table.title': 'Liste de modération des commentaires',
    'comments.table.found': 'commentaires trouvés',
    'comments.table.id': 'ID commentaire',
    'comments.table.target': 'Cible',
    'comments.table.creator': 'Créateur',
    'comments.table.text': 'Texte',
    'comments.table.sticker': 'Sticker',
    'comments.table.image': 'Image',
    'comments.table.likes': 'Likes',
    'comments.table.dislikes': 'Dislikes',
    'comments.table.noImage': 'Aucune image',
    'comments.table.commentImage': 'Image du commentaire',
    'comments.empty.title': 'Aucun commentaire trouvé',
    'comments.empty.subtitle': 'Essayez une autre recherche ou réinitialisez les filtres.',
    'comments.pagination.showing': 'Affichage',
    'comments.pagination.aria': 'Pagination des commentaires',
    'comments.action.openTarget': 'Ouvrir la cible',
    'comments.action.viewTarget': 'Voir la cible',
    'comments.action.replies': 'Réponses',
    'comments.table.none': 'Aucun',
    'comments.target.postDiscussion': 'Discussion de publication',
    'comments.target.replyThread': 'Fil de réponse',
    'comments.confirmDelete': 'Voulez-vous vraiment supprimer ce commentaire ?'
  }
});
</script>

<script>
(function () {
    const P = ['#9b5de0', '#e11d74', '#0090e7', '#55d36a', '#f59e0b', '#d78fee'];
    const raw = <?= $commentsJson ?>;

    function buildLegend(id, items) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.map(([label, color]) => `<span><i style="background:${color}"></i>${label}</span>`).join('');
    }

    function tickColor() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? '#475569' : '#cbd5e1';
    }

    const totalLikes = raw.reduce((a, c) => a + c.likes, 0);
    const totalDislikes = raw.reduce((a, c) => a + c.dislikes, 0);
    const neutrals = Math.max(0, raw.length - raw.filter(c => c.likes > 0 || c.dislikes > 0).length);

    buildLegend('leg-engagement', [[`Likes (${totalLikes.toLocaleString()})`, P[0]], [`Dislikes (${totalDislikes.toLocaleString()})`, P[3]], [`Neutral (${neutrals.toLocaleString()})`, P[5]]]);
    new Chart(document.getElementById('chartEngagement'), {
        type: 'doughnut',
        data: { labels: ['Likes', 'Dislikes', 'Neutral'], datasets: [{ data: [totalLikes, totalDislikes, neutrals], backgroundColor: [P[0], P[3], P[5]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const onPost = raw.filter(c => c.type === 'post').length;
    const onComment = raw.filter(c => c.type === 'comment').length;
    buildLegend('leg-target', [[`On post (${onPost})`, P[0]], [`Reply (${onComment})`, P[2]]]);
    new Chart(document.getElementById('chartTarget'), {
        type: 'pie',
        data: { labels: ['On post', 'Reply to a comment'], datasets: [{ data: [onPost, onComment], backgroundColor: [P[0], P[2]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const withText = raw.filter(c => c.hasText).length;
    const withSticker = raw.filter(c => c.hasSticker && !c.hasText).length;
    const withImage = raw.filter(c => c.hasImage && !c.hasText && !c.hasSticker).length;
    buildLegend('leg-format', [[`Text (${withText})`, P[0]], [`Sticker (${withSticker})`, P[4]], [`Image (${withImage})`, P[2]]]);
    new Chart(document.getElementById('chartFormat'), {
        type: 'pie',
        data: { labels: ['Text', 'Sticker', 'Image'], datasets: [{ data: [withText, withSticker, withImage], backgroundColor: [P[0], P[4], P[2]], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const usersMap = {};
    raw.forEach(c => {
        if (!usersMap[c.user]) usersMap[c.user] = { comments: 0, likes: 0 };
        usersMap[c.user].comments++;
        usersMap[c.user].likes += c.likes;
    });
    const users = Object.entries(usersMap).sort((a, b) => b[1].comments - a[1].comments).slice(0, 6);

    new Chart(document.getElementById('chartUsers'), {
        type: 'bar',
        data: { labels: users.map(u => u[0]), datasets: [{ label: 'Comments', data: users.map(u => u[1].comments), backgroundColor: users.map((_, i) => P[i % P.length]), borderRadius: 7 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tickColor() }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    const top8 = [...raw].sort((a, b) => b.likes - a.likes).slice(0, 8);
    new Chart(document.getElementById('chartTopComments'), {
        type: 'bar',
        data: { labels: top8.map(c => c.text || '(empty)'), datasets: [
            { label: 'Likes', data: top8.map(c => c.likes), backgroundColor: P[0], borderRadius: 5 },
            { label: 'Dislikes', data: top8.map(c => c.dislikes), backgroundColor: P[3], borderRadius: 5 }
        ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tickColor() } } }, scales: { x: { ticks: { color: tickColor(), maxRotation: 45 }, grid: { display: false } }, y: { ticks: { color: tickColor() }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });

    buildLegend('leg-polar', users.map((u, i) => [`${u[0]} (${u[1].likes.toLocaleString()} likes)`, P[i % P.length]]));
    new Chart(document.getElementById('chartPolar'), {
        type: 'polarArea',
        data: { labels: users.map(u => u[0]), datasets: [{ data: users.map(u => u[1].likes), backgroundColor: users.map((_, i) => P[i % P.length] + 'cc') }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { r: { ticks: { color: tickColor(), backdropColor: 'transparent' }, grid: { color: 'rgba(148,163,184,.14)' } } } }
    });
})();
</script>
<script src="../layout/back-layout.js<?= community_comment_asset_version(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script src="../community-center-admin.js<?= community_comment_asset_version(__DIR__ . '/../community-center-admin.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-admin-delete').forEach(function (button) {
        button.addEventListener('click', function (e) {
            const message = window.cre8BackText ? window.cre8BackText('comments.confirmDelete') : 'Are you sure you want to delete this comment?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
