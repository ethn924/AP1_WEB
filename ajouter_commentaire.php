<?php
require_once '_conf.php';
require_once 'fonctions.php';

if (!$loggedIn || $userType !== 1) {
    header('Location: index.php');
    exit;
}

$crId = (int)($_POST['cr_id'] ?? 0);
$commentaire = $_POST['commentaire'] ?? '';

if (!empty($commentaire)) {
    addComment($crId, $userId, $commentaire);
}

header('Location: voir_cr.php?id=' . $crId);
exit;
?>
