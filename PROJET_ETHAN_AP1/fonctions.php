<?php
// Fonctions utilitaires pour tout le site

/**
 * Génère un token sécurisé
 */
function genererToken($longueur = 32) {
    return bin2hex(random_bytes($longueur));
}

/**
 * Valide un email
 */
function validerEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Crée une notification
 */
function creerNotification($utilisateur_id, $type, $titre, $message, $lien = null) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return false;
    }
    
    $utilisateur_id = intval($utilisateur_id);
    $type = mysqli_real_escape_string($bdd, $type);
    $titre = mysqli_real_escape_string($bdd, $titre);
    $message = mysqli_real_escape_string($bdd, $message);
    $lien = $lien ? "'" . mysqli_real_escape_string($bdd, $lien) . "'" : 'NULL';
    
    $query = "INSERT INTO notifications (utilisateur_id, type, titre, message, lien) 
              VALUES ($utilisateur_id, '$type', '$titre', '$message', $lien)";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère les notifications non lues d'un utilisateur
 */
function getNotificationsNonLues($utilisateur_id) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return array();
    }
    
    $utilisateur_id = intval($utilisateur_id);
    $query = "SELECT * FROM notifications 
              WHERE utilisateur_id = $utilisateur_id AND lue = 0 
              ORDER BY date_creation DESC 
              LIMIT 10";
    
    $result = mysqli_query($bdd, $query);
    $notifications = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Marque une notification comme lue
 */
function marquerNotificationLue($notification_id) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return false;
    }
    
    $notification_id = intval($notification_id);
    $query = "UPDATE notifications SET lue = 1 WHERE id = $notification_id";
    
    return mysqli_query($bdd, $query);
}

/**
 * Formate la taille d'un fichier
 */
function formaterTailleFichier($octets) {
    $unites = ['o', 'Ko', 'Mo', 'Go'];
    $octets = max($octets, 0);
    $puissance = floor(($octets ? log($octets) : 0) / log(1024));
    $puissance = min($puissance, count($unites) - 1);
    $octets /= pow(1024, $puissance);
    
    return round($octets, 2) . ' ' . $unites[$puissance];
}

/**
 * Détecte le type MIME d'un fichier
 */
function detecterTypeMime($chemin_fichier) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $chemin_fichier);
    finfo_close($finfo);
    
    if ($mime_type === false) {
        $mime_type = 'application/octet-stream';
    }
    
    return $mime_type;
}

/**
 * Sauvegarde un fichier uploadé
 */
function sauvegarderFichier($fichier, $cr_id) {
    global $bdd, $dossier_upload, $taille_max_fichier, $types_autorises;
    
    if (!$bdd) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors de l'upload: " . $fichier['error']);
    }
    
    if ($fichier['size'] > $taille_max_fichier) {
        throw new Exception("Fichier trop volumineux");
    }
    
    $type_mime = detecterTypeMime($fichier['tmp_name']);
    if (!in_array($type_mime, $types_autorises)) {
        throw new Exception("Type de fichier non autorisé: " . $type_mime);
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
    $nom_fichier_unique = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fichier['name']);
    $chemin_complet = $dossier_upload . $nom_fichier_unique;
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($dossier_upload)) {
        mkdir($dossier_upload, 0755, true);
    }
    
    if (!move_uploaded_file($fichier['tmp_name'], $chemin_complet)) {
        throw new Exception("Erreur lors du déplacement du fichier");
    }
    
    // Sauvegarder dans la base de données
    $cr_id = intval($cr_id);
    $nom_fichier_escape = mysqli_real_escape_string($bdd, $fichier['name']);
    $type_mime_escape = mysqli_real_escape_string($bdd, $type_mime);
    $taille = intval($fichier['size']);
    $chemin_escape = mysqli_real_escape_string($bdd, $nom_fichier_unique);
    
    $query = "INSERT INTO pieces_jointes (cr_id, nom_fichier, type_mime, taille, donnees) 
              VALUES ($cr_id, '$nom_fichier_escape', '$type_mime_escape', $taille, '$chemin_escape')";
    
    if (!mysqli_query($bdd, $query)) {
        // Supprimer le fichier en cas d'erreur
        if (file_exists($chemin_complet)) {
            unlink($chemin_complet);
        }
        throw new Exception("Erreur lors de la sauvegarde en base de données: " . mysqli_error($bdd));
    }
    
    return mysqli_insert_id($bdd);
}

/**
 * Récupère les pièces jointes d'un CR
 */
function getPiecesJointes($cr_id) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return array();
    }
    
    $cr_id = intval($cr_id);
    $query = "SELECT * FROM pieces_jointes WHERE cr_id = $cr_id ORDER BY date_upload DESC";
    
    $result = mysqli_query($bdd, $query);
    $pieces_jointes = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $pieces_jointes[] = $row;
    }
    
    return $pieces_jointes;
}

/**
 * Supprime une pièce jointe
 */
function supprimerPieceJointe($piece_jointe_id) {
    global $bdd, $dossier_upload;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return false;
    }
    
    $piece_jointe_id = intval($piece_jointe_id);
    
    // Récupérer le chemin du fichier
    $query = "SELECT donnees FROM pieces_jointes WHERE id = $piece_jointe_id";
    $result = mysqli_query($bdd, $query);
    $piece_jointe = mysqli_fetch_assoc($result);
    
    if ($piece_jointe) {
        // Supprimer le fichier physique
        $chemin_fichier = $dossier_upload . $piece_jointe['donnees'];
        if (file_exists($chemin_fichier)) {
            unlink($chemin_fichier);
        }
        
        // Supprimer l'entrée en base de données
        $query = "DELETE FROM pieces_jointes WHERE id = $piece_jointe_id";
        return mysqli_query($bdd, $query);
    }
    
    return false;
}

/**
 * Récupère les commentaires d'un CR
 */
function getCommentaires($cr_id) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return array();
    }
    
    $cr_id = intval($cr_id);
    $query = "SELECT c.*, u.nom, u.prenom 
              FROM commentaires c 
              JOIN utilisateur u ON c.professeur_id = u.num 
              WHERE c.cr_id = $cr_id 
              ORDER BY c.date_creation ASC";
    
    $result = mysqli_query($bdd, $query);
    $commentaires = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $commentaires[] = $row;
    }
    
    return $commentaires;
}

/**
 * Ajoute un commentaire à un CR
 */
function ajouterCommentaire($cr_id, $professeur_id, $commentaire) {
    global $bdd;
    
    // Vérifier que $bdd est disponible
    if (!$bdd) {
        return false;
    }
    
    $cr_id = intval($cr_id);
    $professeur_id = intval($professeur_id);
    $commentaire_escape = mysqli_real_escape_string($bdd, $commentaire);
    
    $query = "INSERT INTO commentaires (cr_id, professeur_id, commentaire) 
              VALUES ($cr_id, $professeur_id, '$commentaire_escape')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Vérifie si une date est aujourd'hui
 */
function estDateAujourdhui($date) {
    $today = date('Y-m-d');
    return ($date == $today);
}
?>