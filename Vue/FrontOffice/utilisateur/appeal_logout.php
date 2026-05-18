<?php
session_start();

unset(
    $_SESSION['suspended_appeal'],
    $_SESSION['connected'],
    $_SESSION['id'],
    $_SESSION['nom'],
    $_SESSION['email'],
    $_SESSION['role'],
    $_SESSION['user'],
    $_SESSION['utilisateur']
);

header('Location: login.php');
exit;
