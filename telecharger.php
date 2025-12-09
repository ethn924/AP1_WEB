<?php session_start();
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
$user_id = $_SESSION['Sid'] ?? 0;
$user_type = $_SESSION['Stype'] ?? -1;

if (!$bdd || !isset($_GET['id'])) die('Erreur');

$piece = mysqli_fetch_assoc(mysqli_query($bdd, "SELECT * FROM pieces_jointes WHERE id = " . intval($_GET['id'])));
if (!$piece) die('Fichier non trouvé');

if ($user_type == 0) {
    $cr = mysqli_fetch_assoc(mysqli_query($bdd, "SELECT num_utilisateur FROM cr WHERE num = " . intval($piece['cr_id'])));
    if (!$cr || $cr['num_utilisateur'] != $user_id) die('Accès non autorisé');
} elseif ($user_type != 1) die('Accès non autorisé');

$file = $dossier_upload . $piece['donnees'];
if (!file_exists($file)) die('Fichier non trouvé');

header('Content-Type: ' . $piece['type_mime']);
header('Content-Disposition: attachment; filename="' . $piece['nom_fichier'] . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;