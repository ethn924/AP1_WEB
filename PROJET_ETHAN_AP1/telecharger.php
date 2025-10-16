<?php
session_start();
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID de fichier non spécifié');
}

$piece_jointe_id = intval($_GET['id']);

// Récupérer les informations du fichier
$query = "SELECT * FROM pieces_jointes WHERE id = $piece_jointe_id";
$result = mysqli_query($bdd, $query);

if (mysqli_num_rows($result) === 0) {
    die('Fichier non trouvé');
}

$piece_jointe = mysqli_fetch_assoc($result);

// Vérifier les permissions
$user_id = $_SESSION['Sid'] ?? 0;
$user_type = $_SESSION['Stype'] ?? -1;

if ($user_type == 0) { // Élève
    // Vérifier que le CR appartient à l'élève
    $cr_query = "SELECT num_utilisateur FROM cr WHERE num = " . $piece_jointe['cr_id'];
    $cr_result = mysqli_query($bdd, $cr_query);
    $cr_data = mysqli_fetch_assoc($cr_result);
    
    if ($cr_data['num_utilisateur'] != $user_id) {
        die('Accès non autorisé');
    }
} elseif ($user_type != 1) { // Ni élève ni professeur
    die('Accès non autorisé');
}

// Chemin du fichier - CORRECTION ICI
$chemin_fichier = $dossier_upload . $piece_jointe['donnees'];

if (!file_exists($chemin_fichier)) {
    die('Fichier non trouvé sur le serveur: ' . $chemin_fichier);
}

// En-têtes pour le téléchargement
header('Content-Description: File Transfer');
header('Content-Type: ' . $piece_jointe['type_mime']);
header('Content-Disposition: attachment; filename="' . $piece_jointe['nom_fichier'] . '"');
header('Content-Length: ' . filesize($chemin_fichier));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Lire et envoyer le fichier
readfile($chemin_fichier);
exit;
?>