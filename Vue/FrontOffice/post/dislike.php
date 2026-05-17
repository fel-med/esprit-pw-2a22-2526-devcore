<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/notificationC.php';

header('Content-Type: application/json; charset=utf-8');
$actorId = cc_require_login('../utilisateur/login.php');

function cre8_post_details_link_for_notification(string $postId): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '';
    if (($pos = strpos($script, '/Vue/')) !== false) {
        $base = substr($script, 0, $pos);
    }

    return $base . '/Vue/FrontOffice/post/details.php?id=' . rawurlencode($postId);
}

function cre8_current_actor_name(): string
{
    $name = $_SESSION['nom']
        ?? ($_SESSION['user']['nom'] ?? null)
        ?? ($_SESSION['utilisateur']['nom'] ?? null)
        ?? ($_SESSION['user']['name'] ?? null)
        ?? ($_SESSION['utilisateur']['name'] ?? null)
        ?? '';

    $name = trim((string) $name);
    return $name !== '' ? $name : 'Someone';
}

function cre8_notify_post_reaction(PostC $postC, string $postId, int $actorId, string $reactionType): void
{
    try {
        $post = $postC->showPost($postId);
        $ownerId = isset($post['idCreateur']) ? (int) $post['idCreateur'] : 0;
        if ($ownerId <= 0 || $actorId <= 0 || $ownerId === $actorId) {
            return;
        }

        $actorName = cre8_current_actor_name();
        $notificationC = new NotificationC();
        $notificationC->createNotification(
            $ownerId,
            'post_reaction',
            'New reaction on your post',
            $actorName . ' reacted to your post.',
            cre8_post_details_link_for_notification($postId),
            'post',
            $postId,
            $actorId,
            cc_current_user_role(),
            'post_reaction_' . $reactionType . '_' . $postId . '_' . $actorId . '_' . $ownerId,
            [
                'reaction_type' => $reactionType,
                'post_id' => $postId,
                'actor_name' => $actorName,
            ]
        );
    } catch (Throwable $e) {
        error_log('Post reaction notification failed: ' . $e->getMessage());
    }
}

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing post ID.']);
    exit();
}

$postId = $_GET['id'];

if (!isset($_SESSION['votes'])) {
    $_SESSION['votes'] = [];
}

$existingVote = $_SESSION['votes'][$postId] ?? null;

if ($existingVote === 'dislike') {
    // Déjà disliké → annuler
    $postC->decrementDislike($postId);
    unset($_SESSION['votes'][$postId]);
    $userVote = null;

} elseif ($existingVote === 'like') {
    // Avait liké → basculer vers dislike
    $postC->decrementLike($postId);
    $postC->incrementDislike($postId);
    $_SESSION['votes'][$postId] = 'dislike';
    $userVote = 'dislike';
    cre8_notify_post_reaction($postC, (string) $postId, (int) $actorId, 'dislike');

} else {
    // Aucun vote → disliker
    $postC->incrementDislike($postId);
    $_SESSION['votes'][$postId] = 'dislike';
    $userVote = 'dislike';
    cre8_notify_post_reaction($postC, (string) $postId, (int) $actorId, 'dislike');
}

echo json_encode([
    'success'  => true,
    'postId'   => $postId,
    'likes'    => $postC->getLikeCount($postId),
    'dislikes' => $postC->getDislikeCount($postId),
    'userVote' => $userVote
]);
exit();
