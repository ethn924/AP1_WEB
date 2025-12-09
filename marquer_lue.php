<?php session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit;
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if ($bdd && isset($_GET['id'])) {
    marquerNotificationLue(intval($_GET['id']));
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'accueil.php'));
exit;