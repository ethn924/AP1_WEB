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
    
    echo '<div style="margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 15px; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
    echo '<p style="margin: 5px 0;"><a href="accueil.php" style="color: #007bff; text-decoration: none; font-weight: bold;">← Retour à l\'accueil</a></p>';
    echo '<p style="margin: 5px 0;"><a href="' . $dashboard . '" style="color: #28a745; text-decoration: none; font-weight: bold;">' . $icon . ' Tableau de bord</a></p>';
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
    
    $style_btn = "display: block; padding: 10px; color: white; text-decoration: none; border-radius: 4px; text-align: center; font-size: 0.95em; transition: opacity 0.2s;";
    $style_btn_hover = "opacity: 0.9;";
    
    echo '<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; margin: 20px 0; border-radius: 6px; border: 2px solid #dee2e6; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    echo '<h2 style="margin: 0 0 15px 0; color: #333;">🔗 TOUTES LES FONCTIONNALITÉS</h2>';
    
    if ($type == 0) {
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">';
        
        // Barre rouge - Accueil/Tableau de bord
        echo '<a href="' . $dashboard . '" style="' . $style_btn . ' background: #dc3545; font-weight: bold; font-size: 1em;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">⬅️ Accueil</a>';
        
        // Section CRs
        echo '<a href="editer_cr.php" style="' . $style_btn . ' background: #007bff;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📝 Créer CR</a>';
        echo '<a href="liste_cr.php" style="' . $style_btn . ' background: #17a2b8;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📋 Mes CRs</a>';
        echo '<a href="export_cr.php" style="' . $style_btn . ' background: #28a745;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📥 Exporter</a>';
        
        // Section Recherche & Infos
        echo '<a href="recherche_cr.php" style="' . $style_btn . ' background: #6f42c1;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">🔍 Rechercher</a>';
        echo '<a href="mon_stage.php" style="' . $style_btn . ' background: #fd7e14;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">🏢 Mon stage</a>';
        echo '<a href="notifications.php" style="' . $style_btn . ' background: #e83e8c;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">🔔 Notifications</a>';
        
        // Section Paramètres
        echo '<a href="perso.php" style="' . $style_btn . ' background: #6c757d;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">⚙️ Profil</a>';
        
        echo '</div>';
        
        echo '<div style="margin-top: 15px; font-size: 0.9em; color: #666; font-style: italic;">';
        echo '💡 <strong>Astuce:</strong> Depuis chaque CR, vous pouvez aussi consulter son <a href="historique_cr.php" style="color: #007bff; text-decoration: none;">historique des versions</a>';
        echo '</div>';
        
    } else {
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">';
        
        // Barre rouge - Accueil
        echo '<a href="' . $dashboard . '" style="' . $style_btn . ' background: #dc3545; font-weight: bold; font-size: 1em;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">⬅️ Accueil</a>';
        
        // Section Révision CRs
        echo '<a href="liste_cr_prof.php" style="' . $style_btn . ' background: #fd7e14;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📋 Réviser CRs</a>';
        echo '<a href="export_cr.php" style="' . $style_btn . ' background: #28a745;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📥 Exporter</a>';
        echo '<a href="validations_cr.php" style="' . $style_btn . ' background: #17a2b8;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">✅ Validations</a>';
        echo '<a href="recherche_cr.php" style="' . $style_btn . ' background: #6f42c1;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">🔍 Rechercher</a>';
        echo '<a href="gestion_groupes.php" style="' . $style_btn . ' background: #20c997;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">👥 Groupes</a>';
        echo '<a href="liste_eleves.php" style="' . $style_btn . ' background: #0dcaf0;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">👨‍🎓 Élèves</a>';
        echo '<a href="gestion_modeles.php" style="' . $style_btn . ' background: #ffc107; color: #333;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📄 Modèles</a>';
        echo '<a href="gestion_rappels.php" style="' . $style_btn . ' background: #e83e8c;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">🔔 Rappels</a>';
        echo '<a href="analytics_advanced.php" style="' . $style_btn . ' background: #6c757d;" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">📊 Stats</a>';
        
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Ajoute une sauvegarde automatique
 */
function ajouterSauvegardeAuto($cr_id, $utilisateur_id, $contenu_html, $description = '') {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $cr_id = intval($cr_id);
    $utilisateur_id = intval($utilisateur_id);
    $contenu_html = mysqli_real_escape_string($bdd, $contenu_html);
    $description = mysqli_real_escape_string($bdd, $description);
    
    $query = "INSERT INTO sauvegardes_auto (cr_id, utilisateur_id, contenu_html, description) 
              VALUES ($cr_id, $utilisateur_id, '$contenu_html', '$description')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Calcule les analytics pour un groupe donné
 */
function calculerAnalyticsGroupe($groupe_id = null) {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $groupe_condition = $groupe_id ? " AND c.groupe_id = $groupe_id" : "";
    
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN s.statut = 'soumis' THEN 1 ELSE 0 END) as cr_soumis,
        SUM(CASE WHEN s.statut = 'evalue' THEN 1 ELSE 0 END) as cr_evalues,
        SUM(CASE WHEN s.statut = 'approuve' THEN 1 ELSE 0 END) as cr_approuves
    FROM cr c
    LEFT JOIN statuts_cr s ON c.num = s.cr_id
    WHERE c.archivé = 0 $groupe_condition";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    
    $total = $row['total'] ?? 0;
    $soumis = $row['cr_soumis'] ?? 0;
    $evalues = $row['cr_evalues'] ?? 0;
    $approuves = $row['cr_approuves'] ?? 0;
    
    return array(
        'total_cr' => $total,
        'cr_soumis' => $soumis,
        'cr_evalues' => $evalues,
        'cr_approuves' => $approuves,
        'taux_soumission' => ($total > 0) ? round(($soumis / $total) * 100, 1) : 0,
        'taux_evaluation' => ($total > 0) ? round(($evalues / $total) * 100, 1) : 0
    );
}

/**
 * Récupère les membres d'un groupe
 */
function getMembresGroupe($groupe_id) {
    global $bdd;
    
    if (!$bdd) {
        return array();
    }
    
    $groupe_id = intval($groupe_id);
    
    $query = "SELECT u.num, u.nom, u.prenom 
              FROM membres_groupe mg
              JOIN utilisateur u ON mg.utilisateur_id = u.num
              WHERE mg.groupe_id = $groupe_id
              ORDER BY u.nom, u.prenom";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return array();
    }
    
    $membres = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $membres[] = $row;
    }
    
    return $membres;
}

/**
 * Ajoute un élément à la checklist d'un modèle
 */
function ajouterChecklistModele($modele_id, $item_texte, $ordre, $obligatoire, $description_aide) {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $modele_id = intval($modele_id);
    $ordre = intval($ordre);
    $obligatoire = intval($obligatoire);
    $item_texte = mysqli_real_escape_string($bdd, $item_texte);
    $description_aide = mysqli_real_escape_string($bdd, $description_aide);
    
    $query = "INSERT INTO checklists_modeles (modele_id, item_texte, ordre, obligatoire, description_aide) 
              VALUES ($modele_id, '$item_texte', $ordre, $obligatoire, '$description_aide')";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère la checklist d'un modèle
 */
function getChecklistModele($modele_id) {
    global $bdd;
    
    if (!$bdd) {
        return array();
    }
    
    $modele_id = intval($modele_id);
    
    $query = "SELECT * FROM checklists_modeles WHERE modele_id = $modele_id ORDER BY ordre";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return array();
    }
    
    $checklist = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $checklist[] = $row;
    }
    
    return $checklist;
}

/**
 * Crée un rappel de soumission
 */
function creerRappelSoumission($groupe_id, $date_limite, $titre, $description, $professeur_id) {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $groupe_id = intval($groupe_id);
    $professeur_id = intval($professeur_id);
    $date_limite = mysqli_real_escape_string($bdd, $date_limite);
    $titre = mysqli_real_escape_string($bdd, $titre);
    $description = mysqli_real_escape_string($bdd, $description);
    
    $query = "INSERT INTO rappels_soumission (groupe_id, date_limite, titre, description, professeur_id, actif) 
              VALUES ($groupe_id, '$date_limite', '$titre', '$description', $professeur_id, 1)";
    
    return mysqli_query($bdd, $query);
}

/**
 * Restaure une version antérieure d'un CR
 */
function restaurerVersionCR($version_id, $cr_id, $user_id) {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $version_id = intval($version_id);
    $cr_id = intval($cr_id);
    $user_id = intval($user_id);
    
    $query_version = "SELECT * FROM versions_cr WHERE id = $version_id AND cr_id = $cr_id";
    $result_version = mysqli_query($bdd, $query_version);
    
    if (!$result_version || mysqli_num_rows($result_version) == 0) {
        return false;
    }
    
    $version = mysqli_fetch_assoc($result_version);
    
    $contenu = mysqli_real_escape_string($bdd, $version['contenu']);
    $contenu_html = mysqli_real_escape_string($bdd, $version['contenu_html']);
    
    $query_update = "UPDATE cr SET contenu = '$contenu', contenu_html = '$contenu_html' WHERE num = $cr_id";
    
    return mysqli_query($bdd, $query_update);
}

/**
 * Récupère toutes les versions d'un CR
 */
function getVersionsCR($cr_id) {
    global $bdd;
    
    if (!$bdd) {
        return array();
    }
    
    $cr_id = intval($cr_id);
    
    $query = "SELECT * FROM versions_cr WHERE cr_id = $cr_id ORDER BY date_creation DESC";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return array();
    }
    
    $versions = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $versions[] = $row;
    }
    
    return $versions;
}

/**
 * Recherche des CR avec filtres
 */
function rechercherCR($filtres) {
    global $bdd;
    
    if (!$bdd) {
        return array();
    }
    
    $conditions = array("c.archivé = 0");
    
    if (isset($filtres['titre']) && !empty($filtres['titre'])) {
        $titre = mysqli_real_escape_string($bdd, $filtres['titre']);
        $conditions[] = "c.titre LIKE '%$titre%'";
    }
    
    if (isset($filtres['statut']) && !empty($filtres['statut'])) {
        $statut = mysqli_real_escape_string($bdd, $filtres['statut']);
        $conditions[] = "s.statut = '$statut'";
    }
    
    if (isset($filtres['groupe_id']) && !empty($filtres['groupe_id'])) {
        $groupe_id = intval($filtres['groupe_id']);
        $conditions[] = "c.groupe_id = $groupe_id";
    }
    
    if (isset($filtres['utilisateur_id']) && !empty($filtres['utilisateur_id'])) {
        $utilisateur_id = intval($filtres['utilisateur_id']);
        $conditions[] = "c.num_utilisateur = $utilisateur_id";
    }
    
    if (isset($filtres['professeur_id']) && !empty($filtres['professeur_id'])) {
        $professeur_id = intval($filtres['professeur_id']);
        $conditions[] = "s.professeur_id = $professeur_id";
    }
    
    if (isset($filtres['date_debut']) && !empty($filtres['date_debut'])) {
        $date_debut = mysqli_real_escape_string($bdd, $filtres['date_debut']);
        $conditions[] = "c.date_creation >= '$date_debut'";
    }
    
    if (isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
        $date_fin = mysqli_real_escape_string($bdd, $filtres['date_fin']);
        $conditions[] = "c.date_creation <= '$date_fin'";
    }
    
    $where = implode(" AND ", $conditions);
    
    $query = "SELECT DISTINCT c.* FROM cr c 
              LEFT JOIN statuts_cr s ON c.num = s.cr_id
              WHERE $where
              ORDER BY c.date_creation DESC";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return array();
    }
    
    $resultats = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $resultats[] = $row;
    }
    
    return $resultats;
}

/**
 * Marque un élément de checklist comme complété
 */
function marquerChecklistComplete($cr_id, $checklist_item_id) {
    global $bdd;
    
    if (!$bdd) {
        return false;
    }
    
    $cr_id = intval($cr_id);
    $checklist_item_id = intval($checklist_item_id);
    
    $query = "INSERT INTO validations_cr (cr_id, checklist_item_id, complete) 
              VALUES ($cr_id, $checklist_item_id, 1)
              ON DUPLICATE KEY UPDATE complete = 1";
    
    return mysqli_query($bdd, $query);
}

/**
 * Récupère les validations d'un CR
 */
function getValidationsCR($cr_id) {
    global $bdd;
    
    if (!$bdd) {
        return array();
    }
    
    $cr_id = intval($cr_id);
    
    $query = "SELECT vc.*, cm.item_texte, cm.obligatoire, cm.description_aide 
              FROM checklists_modeles cm
              LEFT JOIN validations_cr vc ON cm.id = vc.checklist_item_id AND vc.cr_id = $cr_id
              ORDER BY cm.ordre";
    
    $result = mysqli_query($bdd, $query);
    if (!$result) {
        return array();
    }
    
    $validations = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $validations[] = $row;
    }
    
    return $validations;
}

/**
 * Formate une date au format français
 */
function formatDateFrench($date) {
    if (!$date) {
        return "Aucun";
    }
    
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
    
    $date_str = date('l d F Y', strtotime($date));
    $date_str = str_replace($english_days, $french_days, $date_str);
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}

/**
 * Formate une date/heure au format français
 */
function formatDateTimeFrench($datetime) {
    if (!$datetime) {
        return "Aucun";
    }
    
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
    
    $date_str = date('l d F Y à H\hi', strtotime($datetime));
    $date_str = str_replace($english_days, $french_days, $date_str);
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}
?>