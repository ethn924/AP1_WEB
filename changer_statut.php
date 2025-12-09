<?php
require_once '_conf.php';
require_once 'fonctions.php';

if (!$loggedIn || $userType !== 1) {
    header('Location: index.php');
    exit;
}

$crId = (int)($_POST['cr_id'] ?? 0);
$statut = $_POST['statut'] ?? '';

if (!empty($statut)) {
    updateCRStatus($crId, $statut, $userId);
}

header('Location: voir_cr.php?id=' . $crId);
exit;
?>
