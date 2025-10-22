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

/**
 * Récupère le statut d'un CR
 */
function getStatutCR($cr_id) {
    global $bdd;
    
    if (!$bdd) {
        return null;
    }
    
    $cr_id = intval($cr_id);
    $query = "SELECT * FROM statuts_cr WHERE cr_id = $cr_id LIMIT 1";
    $result = mysqli_query($bdd, $query);
    
    if (!$result) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

/**
 * Génère la navigation standard avec accueil et tableau de bord
 */
function afficherNavigation() {
    if (!isset($_SESSION['Stype'])) {
        return;
    }
    
    $dashboard = ($_SESSION['Stype'] == 0) ? 'tableau_bord_eleve.php' : 'tableau_bord_prof.php';
    $icon = ($_SESSION['Stype'] == 0) ? '📊' : '👨‍🏫';
    
    echo '<div style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">';
    echo '<p style="margin: 5px 0;"><a href="accueil.php" style="color: #007bff; text-decoration: none;">← Retour à l\'accueil</a></p>';
    echo '<p style="margin: 5px 0;"><a href="' . $dashboard . '" style="color: #28a745; text-decoration: none;">' . $icon . ' Tableau de bord</a></p>';
    echo '</div>';
}

/**
 * Génère un menu de fonctionnalités accessible partout
 */
function afficherMenuFonctionnalites() {
    if (!isset($_SESSION['Stype'])) {
        return;
    }
    
    $type = $_SESSION['Stype'];
    $dashboard = ($type == 0) ? 'tableau_bord_eleve.php' : 'tableau_bord_prof.php';
    $dashboard_icon = ($type == 0) ? '📊' : '👨‍🏫';
    
    echo '<div style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px; border: 1px solid #dee2e6;">';
    echo '<h3 style="margin-top: 0;">🔗 Accès rapide aux fonctionnalités</h3>';
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">';
    
    echo '<a href="' . $dashboard . '" style="display: block; padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; text-align: center; font-weight: bold; font-size: 1.1em;">⬅️ ' . $dashboard_icon . ' Tableau de bord</a>';
    
    if ($type == 0) {
        echo '<a href="editer_cr.php" style="display: block; padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; text-align: center;">📝 Créer un CR</a>';
        echo '<a href="liste_cr.php" style="display: block; padding: 10px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; text-align: center;">📋 Mes CRs</a>';
        echo '<a href="recherche_cr.php" style="display: block; padding: 10px; background: #6f42c1; color: white; text-decoration: none; border-radius: 4px; text-align: center;">🔍 Rechercher</a>';
        echo '<a href="mon_stage.php" style="display: block; padding: 10px; background: #fd7e14; color: white; text-decoration: none; border-radius: 4px; text-align: center;">🏢 Mon stage</a>';
        echo '<a href="perso.php" style="display: block; padding: 10px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; text-align: center;">⚙️ Mon profil</a>';
    } else {
        echo '<a href="liste_cr_prof.php" style="display: block; padding: 10px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; text-align: center;">📋 Réviser les CRs</a>';
        echo '<a href="recherche_cr.php" style="display: block; padding: 10px; background: #6f42c1; color: white; text-decoration: none; border-radius: 4px; text-align: center;">🔍 Rechercher</a>';
        echo '<a href="gestion_groupes.php" style="display: block; padding: 10px; background: #20c997; color: white; text-decoration: none; border-radius: 4px; text-align: center;">👥 Groupes</a>';
        echo '<a href="gestion_modeles.php" style="display: block; padding: 10px; background: #ffc107; color: #333; text-decoration: none; border-radius: 4px; text-align: center;">📄 Modèles</a>';
        echo '<a href="analytics_advanced.php" style="display: block; padding: 10px; background: #20c997; color: white; text-decoration: none; border-radius: 4px; text-align: center;">📊 Statistiques</a>';
        echo '<a href="validations_cr.php" style="display: block; padding: 10px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; text-align: center;">✅ Validations</a>';
        echo '<a href="gestion_rappels.php" style="display: block; padding: 10px; background: #fd7e14; color: white; text-decoration: none; border-radius: 4px; text-align: center;">🔔 Rappels</a>';
    }
    
    echo '</div>';
    echo '</div>';
}
?>