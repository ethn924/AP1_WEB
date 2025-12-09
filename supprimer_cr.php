<?php
require_once '_conf.php';
require_once 'fonctions.php';

if (!$loggedIn || $userType !== 0) {
    header('Location: index.php');
    exit;
}

$crId = (int)($_GET['id'] ?? 0);
$cr = getCR($crId);

if (!$cr || $cr['num_utilisateur'] !== $userId) {
    header('Location: liste_cr.php');
    exit;
}

deleteCR($crId);
header('Location: liste_cr.php?message=supprimé');
exit;
?>
