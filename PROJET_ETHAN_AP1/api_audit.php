<?php
include '_conf.php';
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) { die("Erreur BDD"); }

function enregistrerAudit($utilisateur_id, $action, $entite, $entite_id = null, $description = null, $anciennes_donnees = null, $nouvelles_donnees = null) {
    global $bdd;
    if (!$bdd) { return false; }
    
    $utilisateur_id = intval($utilisateur_id);
    $action = mysqli_real_escape_string($bdd, $action);
    $entite = mysqli_real_escape_string($bdd, $entite);
    $entite_id = $entite_id ? intval($entite_id) : 'NULL';
    $description = $description ? "'" . mysqli_real_escape_string($bdd, $description) . "'" : 'NULL';
    $anciennes_donnees = $anciennes_donnees ? "'" . mysqli_real_escape_string($bdd, json_encode($anciennes_donnees)) . "'" : 'NULL';
    $nouvelles_donnees = $nouvelles_donnees ? "'" . mysqli_real_escape_string($bdd, json_encode($nouvelles_donnees)) . "'" : 'NULL';
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'INCONNU';
    $ip = mysqli_real_escape_string($bdd, $ip);
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'INCONNU';
    $user_agent = mysqli_real_escape_string($bdd, substr($user_agent, 0, 255));
    
    $query = "INSERT INTO audit_trail (utilisateur_id, action, entite, entite_id, description, anciennes_donnees, nouvelles_donnees, adresse_ip, user_agent)
              VALUES ($utilisateur_id, '$action', '$entite', $entite_id, $description, $anciennes_donnees, $nouvelles_donnees, '$ip', '$user_agent')";
    
    return mysqli_query($bdd, $query);
}

function enregistrerAuditVersion($cr_id, $numero_version, $utilisateur_id, $titre, $description, $contenu_html, $note_version, $type_modification = 'modification', $ancien_contenu = null) {
    global $bdd;
    if (!$bdd) { return false; }
    
    $cr_id = intval($cr_id);
    $numero_version = intval($numero_version);
    $utilisateur_id = intval($utilisateur_id);
    $titre = $titre ? "'" . mysqli_real_escape_string($bdd, $titre) . "'" : 'NULL';
    $description = $description ? "'" . mysqli_real_escape_string($bdd, $description) . "'" : 'NULL';
    $contenu_html = "'" . mysqli_real_escape_string($bdd, $contenu_html) . "'";
    $note_version = $note_version ? "'" . mysqli_real_escape_string($bdd, $note_version) . "'" : 'NULL';
    $type_modification = mysqli_real_escape_string($bdd, $type_modification);
    
    $nb_ajoutes = 0;
    $nb_supprimes = 0;
    
    if ($ancien_contenu) {
        $nb_ajoutes = strlen($contenu_html) - strlen($ancien_contenu);
        $nb_supprimes = max(0, strlen($ancien_contenu) - strlen($contenu_html));
    } else {
        $nb_ajoutes = strlen($contenu_html);
    }
    
    $taille_fichier = strlen($contenu_html);
    
    $query = "INSERT INTO versions_cr_audit (cr_id, numero_version, titre, description, contenu_html, utilisateur_id, note_version, type_modification, nb_caracteres_ajoutes, nb_caracteres_supprimes, taille_fichier)
              VALUES ($cr_id, $numero_version, $titre, $description, $contenu_html, $utilisateur_id, $note_version, '$type_modification', $nb_ajoutes, $nb_supprimes, $taille_fichier)";
    
    return mysqli_query($bdd, $query);
}

function obtenirHistoriqueAudit($filtres = []) {
    global $bdd;
    if (!$bdd) { return []; }
    
    $query = "SELECT a.*, u.nom, u.prenom FROM audit_trail a
              LEFT JOIN utilisateur u ON a.utilisateur_id = u.num WHERE 1=1";
    
    if (!empty($filtres['utilisateur_id'])) {
        $query .= " AND a.utilisateur_id = " . intval($filtres['utilisateur_id']);
    }
    if (!empty($filtres['action'])) {
        $query .= " AND a.action = '" . mysqli_real_escape_string($bdd, $filtres['action']) . "'";
    }
    if (!empty($filtres['entite'])) {
        $query .= " AND a.entite = '" . mysqli_real_escape_string($bdd, $filtres['entite']) . "'";
    }
    if (!empty($filtres['date_debut'])) {
        $query .= " AND DATE(a.date_action) >= '" . mysqli_real_escape_string($bdd, $filtres['date_debut']) . "'";
    }
    if (!empty($filtres['date_fin'])) {
        $query .= " AND DATE(a.date_action) <= '" . mysqli_real_escape_string($bdd, $filtres['date_fin']) . "'";
    }
    
    $query .= " ORDER BY a.date_action DESC LIMIT 1000";
    
    $result = mysqli_query($bdd, $query);
    $historique = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $historique[] = $row;
    }
    return $historique;
}

function obtenirStatistiquesAudit() {
    global $bdd;
    if (!$bdd) { return []; }
    
    $stats = [];
    
    $query = "SELECT COUNT(*) as total FROM audit_trail";
    $result = mysqli_query($bdd, $query);
    $stats['total_actions'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    $query = "SELECT action, COUNT(*) as count FROM audit_trail GROUP BY action";
    $result = mysqli_query($bdd, $query);
    $stats['actions_par_type'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['actions_par_type'][$row['action']] = $row['count'];
    }
    
    $query = "SELECT COUNT(DISTINCT utilisateur_id) as count FROM audit_trail";
    $result = mysqli_query($bdd, $query);
    $stats['nb_utilisateurs_actifs'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    $query = "SELECT DATE(date_action) as date, COUNT(*) as count FROM audit_trail GROUP BY DATE(date_action) ORDER BY date DESC LIMIT 30";
    $result = mysqli_query($bdd, $query);
    $stats['actions_par_jour'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['actions_par_jour'][$row['date']] = $row['count'];
    }
    
    return $stats;
}

function supprimerAuditAncien($jours = 365) {
    global $bdd;
    if (!$bdd) { return false; }
    
    $date_limite = date('Y-m-d', strtotime("-$jours days"));
    $query = "DELETE FROM audit_trail WHERE DATE(date_action) < '$date_limite'";
    
    return mysqli_query($bdd, $query);
}

mysqli_close($bdd);
?>