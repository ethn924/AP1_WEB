<?php
$serveurBDD="localhost";
$userBDD="root";
$mdpBDD="root";
$nomBDD="ap1_ethan_2025";

// Configuration pour l'upload de fichiers
$dossier_upload = __DIR__ . '/uploads/';
$taille_max_fichier = 10 * 1024 * 1024; // 10MB
$types_autorises = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

// Configuration pour les logs
$fichier_log = __DIR__ . '/logs/erreurs.log';

// Créer les dossiers nécessaires
if (!file_exists($dossier_upload)) {
    mkdir($dossier_upload, 0777, true);
}
if (!file_exists(dirname($fichier_log))) {
    mkdir(dirname($fichier_log), 0777, true);
}

// Fonction de logging
function logger($message, $utilisateur_id = null, $page = null) {
    global $fichier_log, $bdd;
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Inconnue';
    $page = $page ?? $_SERVER['PHP_SELF'] ?? 'Inconnue';
    $utilisateur_id = $utilisateur_id ?? ($_SESSION['Sid'] ?? null);
    
    $log_message = "[$timestamp] [IP: $ip] [Page: $page] [User: $utilisateur_id] $message\n";
    
    // Log dans le fichier
    file_put_contents($fichier_log, $log_message, FILE_APPEND | LOCK_EX);
    
    // Log dans la base de données
    if ($bdd) {
        $message_escape = mysqli_real_escape_string($bdd, $message);
        $page_escape = mysqli_real_escape_string($bdd, $page);
        $query = "INSERT INTO logs_erreurs (utilisateur_id, page, erreur) 
                  VALUES (" . ($utilisateur_id ?: 'NULL') . ", '$page_escape', '$message_escape')";
        @mysqli_query($bdd, $query);
    }
}
?>