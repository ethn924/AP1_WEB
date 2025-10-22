<?php
session_start();
require '_conf.php';
require 'fonctions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Sid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

$file = $_FILES['file'];
$user_id = $_SESSION['Sid'];
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

if (!$bdd) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur connexion BDD']);
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 10 * 1024 * 1024;

if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de fichier non autorisé']);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier trop volumineux']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Erreur lors du téléchargement']);
    exit;
}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . md5_file($file['tmp_name']) . '.' . $ext;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de sauvegarder le fichier']);
    exit;
}

$url = '/PROJET_ETHAN_AP1/uploads/' . $filename;
http_response_code(200);
echo json_encode(['location' => $url]);
exit;
?>