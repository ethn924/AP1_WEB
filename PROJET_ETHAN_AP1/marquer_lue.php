<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    marquerNotificationLue($notification_id);
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'accueil.php'));
exit;
?>