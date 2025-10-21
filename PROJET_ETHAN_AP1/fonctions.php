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

// ========================================
// NOUVELLES FONCTIONS POUR LES 11 FONCTIONNALITÉS
// ========================================

/**
 * Crée une nouvelle version d'un CR lors de sa modification
 */
function creerVersionCR($cr_id, $utilisateur_id, $titre, $description, $contenu_html, $note_version = '') {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    $utilisateur_id = intval($utilisateur_id);
    $titre_escape = mysqli_real_escape_string($bdd, $titre);
    $description_escape = mysqli_real_escape_string($bdd, $description);
    $contenu_html_escape = mysqli_real_escape_string($bdd, $contenu_html);
    $note_version_escape = mysqli_real_escape_string($bdd, $note_version);
    
    // Obtenir le numéro de version suivant
    $query_version = "SELECT MAX(numero_version) as max_version FROM versions_cr WHERE cr_id = $cr_id";
    $result = mysqli_query($bdd, $query_version);
    $row = mysqli_fetch_assoc($result);
    $next_version = ($row['max_version'] ?? 0) + 1;
    
    $query = "INSERT INTO versions_cr (cr_id, numero_version, titre, description, contenu_html, utilisateur_id, note_version) 
              VALUES ($cr_id, $next_version, '$titre_escape', '$description_escape', '$contenu_html_escape', $utilisateur_id, '$note_version_escape')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère l'historique des versions d'un CR
 */
function getVersionsCR($cr_id) {
    global $bdd;
    
    if (!$bdd) return array();
    
    $cr_id = intval($cr_id);
    $query = "SELECT v.*, u.nom, u.prenom FROM versions_cr v 
              JOIN utilisateur u ON v.utilisateur_id = u.num 
              WHERE v.cr_id = $cr_id 
              ORDER BY v.numero_version DESC";
    
    $result = mysqli_query($bdd, $query);
    $versions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $versions[] = $row;
    }
    
    return $versions;
}

/**
 * Restaure une version antérieure d'un CR
 */
function restaurerVersionCR($version_id, $cr_id, $utilisateur_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $version_id = intval($version_id);
    $cr_id = intval($cr_id);
    
    // Récupérer les données de la version à restaurer
    $query = "SELECT * FROM versions_cr WHERE id = $version_id AND cr_id = $cr_id";
    $result = mysqli_query($bdd, $query);
    $version = mysqli_fetch_assoc($result);
    
    if (!$version) return false;
    
    // Créer une version de sauvegarde actuelle
    $current_query = "SELECT * FROM cr WHERE num = $cr_id";
    $current_result = mysqli_query($bdd, $current_query);
    $current = mysqli_fetch_assoc($current_result);
    
    creerVersionCR($cr_id, $utilisateur_id, $current['titre'], $current['description'], $current['contenu_html'], 'Sauvegarde avant restauration');
    
    // Restaurer la version
    $titre_escape = mysqli_real_escape_string($bdd, $version['titre']);
    $description_escape = mysqli_real_escape_string($bdd, $version['description']);
    $contenu_html_escape = mysqli_real_escape_string($bdd, $version['contenu_html']);
    
    $update_query = "UPDATE cr SET titre = '$titre_escape', description = '$description_escape', contenu_html = '$contenu_html_escape', num_version = " . ($version['numero_version'] + 1) . " WHERE num = $cr_id";
    
    return mysqli_query($bdd, $update_query);
}

/**
 * Initialise le statut d'un CR (brouillon)
 */
function initierStatutCR($cr_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    
    $query = "INSERT INTO statuts_cr (cr_id, statut) VALUES ($cr_id, 'brouillon')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Change le statut d'un CR
 */
function changerStatutCR($cr_id, $nouveau_statut, $professeur_id = null) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    $nouveau_statut = mysqli_real_escape_string($bdd, $nouveau_statut);
    
    $date_field = '';
    switch ($nouveau_statut) {
        case 'soumis':
            $date_field = ', date_soumission = NOW()';
            break;
        case 'evalue':
            $date_field = ', date_evaluation = NOW()';
            if ($professeur_id) {
                $professeur_id = intval($professeur_id);
                $date_field .= ", professeur_evaluateur_id = $professeur_id";
            }
            break;
        case 'approuve':
            $date_field = ', date_approbation = NOW()';
            break;
    }
    
    $query = "UPDATE statuts_cr SET statut = '$nouveau_statut' $date_field WHERE cr_id = $cr_id";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère le statut actuel d'un CR
 */
function getStatutCR($cr_id) {
    global $bdd;
    
    if (!$bdd) return null;
    
    $cr_id = intval($cr_id);
    
    $query = "SELECT * FROM statuts_cr WHERE cr_id = $cr_id";
    
    $result = mysqli_query($bdd, $query);
    return mysqli_fetch_assoc($result);
}

/**
 * Ajoute une sauvegarde auto d'un CR
 */
function ajouterSauvegardeAuto($cr_id, $utilisateur_id, $contenu_html, $description) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    $utilisateur_id = intval($utilisateur_id);
    $contenu_html_escape = mysqli_real_escape_string($bdd, $contenu_html);
    $description_escape = mysqli_real_escape_string($bdd, $description);
    
    $query = "INSERT INTO sauvegardes_auto (cr_id, utilisateur_id, contenu_html, description) 
              VALUES ($cr_id, $utilisateur_id, '$contenu_html_escape', '$description_escape')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère la dernière sauvegarde auto
 */
function getDerniereSauvegardeAuto($cr_id) {
    global $bdd;
    
    if (!$bdd) return null;
    
    $cr_id = intval($cr_id);
    
    $query = "SELECT * FROM sauvegardes_auto WHERE cr_id = $cr_id ORDER BY date_sauvegarde DESC LIMIT 1";
    
    $result = mysqli_query($bdd, $query);
    return mysqli_fetch_assoc($result);
}

/**
 * Crée un groupe d'étudiants
 */
function creerGroupe($nom, $description, $professeur_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $nom_escape = mysqli_real_escape_string($bdd, $nom);
    $description_escape = mysqli_real_escape_string($bdd, $description);
    $professeur_id = intval($professeur_id);
    
    $query = "INSERT INTO groupes (nom, description, professeur_responsable_id) 
              VALUES ('$nom_escape', '$description_escape', $professeur_id)";
    
    if (mysqli_query($bdd, $query)) {
        return mysqli_insert_id($bdd);
    }
    
    return false;
}

/**
 * Ajoute un utilisateur à un groupe
 */
function ajouterMembreGroupe($groupe_id, $utilisateur_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $groupe_id = intval($groupe_id);
    $utilisateur_id = intval($utilisateur_id);
    
    $query = "INSERT INTO membres_groupe (groupe_id, utilisateur_id) 
              VALUES ($groupe_id, $utilisateur_id)";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère les membres d'un groupe
 */
function getMembresGroupe($groupe_id) {
    global $bdd;
    
    if (!$bdd) return array();
    
    $groupe_id = intval($groupe_id);
    
    $query = "SELECT u.*, mg.date_ajout FROM utilisateur u 
              JOIN membres_groupe mg ON u.num = mg.utilisateur_id 
              WHERE mg.groupe_id = $groupe_id AND mg.statut = 'actif'
              ORDER BY u.nom, u.prenom";
    
    $result = mysqli_query($bdd, $query);
    $membres = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $membres[] = $row;
    }
    
    return $membres;
}

/**
 * Ajoute une checklist à un modèle
 */
function ajouterChecklistModele($modele_id, $item_texte, $ordre, $obligatoire = 1, $description_aide = '') {
    global $bdd;
    
    if (!$bdd) return false;
    
    $modele_id = intval($modele_id);
    $item_texte_escape = mysqli_real_escape_string($bdd, $item_texte);
    $ordre = intval($ordre);
    $obligatoire = intval($obligatoire);
    $description_aide_escape = mysqli_real_escape_string($bdd, $description_aide);
    
    $query = "INSERT INTO checklists_modeles (modele_id, item_texte, ordre, obligatoire, description_aide) 
              VALUES ($modele_id, '$item_texte_escape', $ordre, $obligatoire, '$description_aide_escape')";
    
    if (mysqli_query($bdd, $query)) {
        return mysqli_insert_id($bdd);
    }
    
    return false;
}

/**
 * Récupère la checklist d'un modèle
 */
function getChecklistModele($modele_id) {
    global $bdd;
    
    if (!$bdd) return array();
    
    $modele_id = intval($modele_id);
    
    $query = "SELECT * FROM checklists_modeles WHERE modele_id = $modele_id ORDER BY ordre";
    
    $result = mysqli_query($bdd, $query);
    $checklist = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $checklist[] = $row;
    }
    
    return $checklist;
}

/**
 * Marque un item de checklist comme complété
 */
function marquerChecklistComplete($cr_id, $checklist_item_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    $checklist_item_id = intval($checklist_item_id);
    
    $query = "INSERT INTO validations_cr (cr_id, checklist_item_id, complete, date_verification) 
              VALUES ($cr_id, $checklist_item_id, 1, NOW())
              ON DUPLICATE KEY UPDATE complete = 1, date_verification = NOW()";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère le statut de validation d'un CR
 */
function getValidationsCR($cr_id) {
    global $bdd;
    
    if (!$bdd) return array();
    
    $cr_id = intval($cr_id);
    
    $query = "SELECT v.*, c.item_texte, c.obligatoire FROM validations_cr v 
              JOIN checklists_modeles c ON v.checklist_item_id = c.id 
              WHERE v.cr_id = $cr_id 
              ORDER BY c.ordre";
    
    $result = mysqli_query($bdd, $query);
    $validations = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $validations[] = $row;
    }
    
    return $validations;
}

/**
 * Archive un CR
 */
function archiverCR($cr_id, $utilisateur_id, $raison_archivage = '') {
    global $bdd;
    
    if (!$bdd) return false;
    
    $cr_id = intval($cr_id);
    $utilisateur_id = intval($utilisateur_id);
    $raison_escape = mysqli_real_escape_string($bdd, $raison_archivage);
    
    // Marquer dans la table archives_cr
    $query1 = "INSERT INTO archives_cr (cr_id, utilisateur_id, raison_archivage) 
               VALUES ($cr_id, $utilisateur_id, '$raison_escape')";
    
    mysqli_query($bdd, $query1);
    
    // Marquer dans la table cr
    $query2 = "UPDATE cr SET archivé = 1 WHERE num = $cr_id";
    
    return mysqli_query($bdd, $query2);
}

/**
 * Recherche les CR avec filtres
 */
function rechercherCR($filtres = array()) {
    global $bdd;
    
    if (!$bdd) return array();
    
    $query = "SELECT c.*, u.nom, u.prenom, s.statut FROM cr c 
              JOIN utilisateur u ON c.num_utilisateur = u.num 
              LEFT JOIN statuts_cr s ON c.num = s.cr_id 
              WHERE c.archivé = 0";
    
    if (isset($filtres['titre']) && !empty($filtres['titre'])) {
        $titre_escape = mysqli_real_escape_string($bdd, $filtres['titre']);
        $query .= " AND c.titre LIKE '%$titre_escape%'";
    }
    
    if (isset($filtres['statut']) && !empty($filtres['statut'])) {
        $statut_escape = mysqli_real_escape_string($bdd, $filtres['statut']);
        $query .= " AND s.statut = '$statut_escape'";
    }
    
    if (isset($filtres['date_debut']) && !empty($filtres['date_debut'])) {
        $date_debut = mysqli_real_escape_string($bdd, $filtres['date_debut']);
        $query .= " AND c.date >= '$date_debut'";
    }
    
    if (isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
        $date_fin = mysqli_real_escape_string($bdd, $filtres['date_fin']);
        $query .= " AND c.date <= '$date_fin'";
    }
    
    if (isset($filtres['professeur_id']) && !empty($filtres['professeur_id'])) {
        $prof_id = intval($filtres['professeur_id']);
        $query .= " AND s.professeur_evaluateur_id = $prof_id";
    }
    
    if (isset($filtres['groupe_id']) && !empty($filtres['groupe_id'])) {
        $groupe_id = intval($filtres['groupe_id']);
        $query .= " AND c.groupe_id = $groupe_id";
    }
    
    if (isset($filtres['utilisateur_id']) && !empty($filtres['utilisateur_id'])) {
        $user_id = intval($filtres['utilisateur_id']);
        $query .= " AND c.num_utilisateur = $user_id";
    }
    
    $query .= " ORDER BY c.date DESC";
    
    if (isset($filtres['limit']) && !empty($filtres['limit'])) {
        $limit = intval($filtres['limit']);
        $query .= " LIMIT $limit";
    }
    
    $result = mysqli_query($bdd, $query);
    $crs = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $crs[] = $row;
    }
    
    return $crs;
}

/**
 * Crée un rappel de soumission
 */
function creerRappelSoumission($groupe_id, $date_limite, $titre, $description, $professeur_id) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $groupe_id = intval($groupe_id);
    $professeur_id = intval($professeur_id);
    $date_limite = mysqli_real_escape_string($bdd, $date_limite);
    $titre_escape = mysqli_real_escape_string($bdd, $titre);
    $description_escape = mysqli_real_escape_string($bdd, $description);
    
    $query = "INSERT INTO rappels_soumission (groupe_id, date_limite, titre, description, professeur_id) 
              VALUES ($groupe_id, '$date_limite', '$titre_escape', '$description_escape', $professeur_id)";
    
    if (mysqli_query($bdd, $query)) {
        return mysqli_insert_id($bdd);
    }
    
    return false;
}

/**
 * Récupère les rappels actifs
 */
function getRappelsActifs() {
    global $bdd;
    
    if (!$bdd) return array();
    
    $query = "SELECT r.*, g.nom as groupe_nom FROM rappels_soumission r 
              JOIN groupes g ON r.groupe_id = g.id 
              WHERE r.actif = 1 AND r.date_limite >= NOW()
              ORDER BY r.date_limite ASC";
    
    $result = mysqli_query($bdd, $query);
    $rappels = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rappels[] = $row;
    }
    
    return $rappels;
}

/**
 * Calcule les analytics pour un groupe
 */
function calculerAnalyticsGroupe($groupe_id = null) {
    global $bdd;
    
    if (!$bdd) return false;
    
    $mois = date('Y-m');
    $groupe_condition = '';
    
    if ($groupe_id) {
        $groupe_id = intval($groupe_id);
        $groupe_condition = " AND c.groupe_id = $groupe_id";
    }
    
    $query = "
        SELECT 
            COUNT(DISTINCT c.num) as total_cr,
            SUM(CASE WHEN s.statut IN ('soumis', 'evalue', 'approuve') THEN 1 ELSE 0 END) as cr_soumis,
            SUM(CASE WHEN s.statut IN ('evalue', 'approuve') THEN 1 ELSE 0 END) as cr_evalues,
            SUM(CASE WHEN s.statut = 'approuve' THEN 1 ELSE 0 END) as cr_approuves
        FROM cr c 
        LEFT JOIN statuts_cr s ON c.num = s.cr_id
        WHERE DATE_FORMAT(c.date, '%Y-%m') = '$mois' $groupe_condition AND c.archivé = 0
    ";
    
    $result = mysqli_query($bdd, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data) {
        $data['taux_soumission'] = ($data['total_cr'] > 0) ? round(($data['cr_soumis'] / $data['total_cr']) * 100, 2) : 0;
        $data['taux_evaluation'] = ($data['cr_soumis'] > 0) ? round(($data['cr_evalues'] / $data['cr_soumis']) * 100, 2) : 0;
    }
    
    return $data;
}
?>