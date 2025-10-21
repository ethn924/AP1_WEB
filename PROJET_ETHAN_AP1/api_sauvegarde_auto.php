<?php
session_start();
include '_conf.php';
include 'fonctions.php';

header('Content-Type: application/json');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['Sid'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    echo json_encode(['success' => false, 'message' => 'Erreur connexion BDD']);
    exit;
}

// Récupération des données POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cr_id']) || !isset($data['contenu_html']) || !isset($data['description'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$cr_id = intval($data['cr_id']);
$contenu_html = $data['contenu_html'];
$description = $data['description'];

// Vérifier l'accès au CR
$query = "SELECT * FROM cr WHERE num = $cr_id AND num_utilisateur = {$_SESSION['Sid']}";
$result = mysqli_query($bdd, $query);

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Ajouter la sauvegarde auto
if (ajouterSauvegardeAuto($cr_id, $_SESSION['Sid'], $contenu_html, $description)) {
    echo json_encode([
        'success' => true,
        'message' => 'Sauvegarde automatique effectuée',
        'timestamp' => date('d/m/Y à H:i:s')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
}

mysqli_close($bdd);
?>