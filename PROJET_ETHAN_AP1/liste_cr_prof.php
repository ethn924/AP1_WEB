<?php
session_start();
include '_conf.php';
include 'fonctions.php';

// Fonction pour formater la date et l'heure en français avec majuscules
function formatDateFrench($date)
{
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');

    $date_str = date('d F Y à H\hi', strtotime($date));
    $date_str = str_replace($english_months, $french_months, $date_str);

    return $date_str;
}

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Traitement de la mise à jour du statut "vu"
if (isset($_POST['update_vu'])) {
    $cr_num = intval($_POST['cr_num']);
    $vu_value = intval($_POST['vu_value']);

    $update_query = "UPDATE cr SET vu = $vu_value WHERE num = $cr_num";
    if (mysqli_query($bdd, $update_query)) {
        // Créer une notification pour l'élève
        $cr_query = "SELECT num_utilisateur FROM cr WHERE num = $cr_num";
        $cr_result = mysqli_query($bdd, $cr_query);
        $cr_data = mysqli_fetch_assoc($cr_result);
        
        if ($vu_value == 1) {
            creerNotification(
                $cr_data['num_utilisateur'],
                'cr_vu',
                'Compte rendu marqué comme vu',
                'Votre compte rendu a été consulté par un professeur.',
                'liste_cr.php'
            );
        }
        
        // Reconstruction de l'URL pour la redirection
        $redirect_url = "liste_cr_prof.php";
        $params = array();
        
        if (isset($_GET['sort'])) $params[] = 'sort=' . $_GET['sort'];
        if (isset($_GET['eleve'])) $params[] = 'eleve=' . $_GET['eleve'];
        if (isset($_GET['view'])) $params[] = 'view=' . $_GET['view'];
        if (isset($_GET['view_stage'])) $params[] = 'view_stage=' . $_GET['view_stage'];
        if (isset($_GET['search'])) $params[] = 'search=' . urlencode($_GET['search']);
        
        if (!empty($params)) {
            $redirect_url .= '?' . implode('&', $params);
        }
        
        header("Location: " . $redirect_url);
        exit();
    } else {
        $error = "Erreur lors de la mise à jour : " . mysqli_error($bdd);
        logger("Erreur update_vu: " . mysqli_error($bdd), $_SESSION['Sid'], 'liste_cr_prof.php');
    }
}

// Traitement de l'ajout de commentaire
if (isset($_POST['ajouter_commentaire'])) {
    $cr_num = intval($_POST['cr_num']);
    $commentaire = mysqli_real_escape_string($bdd, $_POST['commentaire']);
    $professeur_id = $_SESSION['Sid'];
    
    if (ajouterCommentaire($cr_num, $professeur_id, $commentaire)) {
        // Créer une notification pour l'élève
        $cr_query = "SELECT num_utilisateur FROM cr WHERE num = $cr_num";
        $cr_result = mysqli_query($bdd, $cr_query);
        $cr_data = mysqli_fetch_assoc($cr_result);
        
        creerNotification(
            $cr_data['num_utilisateur'],
            'commentaire',
            'Nouveau commentaire sur votre CR',
            'Un professeur a ajouté un commentaire à votre compte rendu.',
            'liste_cr.php'
        );
        
        $message_commentaire = "Commentaire ajouté avec succès !";
    } else {
        $error_commentaire = "Erreur lors de l'ajout du commentaire : " . mysqli_error($bdd);
        logger("Erreur ajout commentaire: " . mysqli_error($bdd), $_SESSION['Sid'], 'liste_cr_prof.php');
    }
}

// Récupérer la liste des élèves pour le filtre
$eleves_query = "SELECT DISTINCT u.num, u.nom, u.prenom FROM utilisateur u 
                 JOIN cr ON cr.num_utilisateur = u.num 
                 WHERE u.type = 0 
                 ORDER BY u.nom ASC, u.prenom ASC";
$eleves_result = mysqli_query($bdd, $eleves_query);

// Déterminer l'ordre de tri et les conditions WHERE
$where_conditions = array();
$order_by = "cr.datetime DESC"; // Tri par défaut

// Filtre par élève spécifique
if (isset($_GET['eleve']) && !empty($_GET['eleve'])) {
    $eleve_id = intval($_GET['eleve']);
    $where_conditions[] = "cr.num_utilisateur = $eleve_id";
}

// Recherche dans les descriptions
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($bdd, $_GET['search']);
    $where_conditions[] = "(cr.description LIKE '%$search_term%' OR u.nom LIKE '%$search_term%' OR u.prenom LIKE '%$search_term%')";
}

// Filtre par statut vu/non vu
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'eleve':
            $order_by = "u.nom ASC, u.prenom ASC, cr.datetime DESC";
            break;
        case 'date_asc':
            $order_by = "cr.datetime ASC";
            break;
        case 'date_desc':
            $order_by = "cr.datetime DESC";
            break;
        case 'vu':
            $where_conditions[] = "cr.vu = 1";
            $order_by = "cr.datetime DESC";
            break;
        case 'non_vu':
            $where_conditions[] = "cr.vu = 0";
            $order_by = "cr.datetime DESC";
            break;
    }
}

// Construire la clause WHERE
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT cr.*, u.nom, u.prenom FROM cr 
          JOIN utilisateur u ON cr.num_utilisateur = u.num 
          $where_clause
          ORDER BY $order_by";
$result = mysqli_query($bdd, $query);

// Gestion de l'affichage détaillé d'un CR
$cr_detail = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $cr_num = intval($_GET['view']);
    $detail_query = "SELECT cr.*, u.nom, u.prenom FROM cr 
                     JOIN utilisateur u ON cr.num_utilisateur = u.num 
                     WHERE cr.num = $cr_num";
    $detail_result = mysqli_query($bdd, $detail_query);
    if (mysqli_num_rows($detail_result) > 0) {
        $cr_detail = mysqli_fetch_assoc($detail_result);
    }
}

// Gestion de l'affichage des infos de stage
$stage_info = null;
if (isset($_GET['view_stage']) && !empty($_GET['view_stage'])) {
    $eleve_id = intval($_GET['view_stage']);
    $stage_query = "SELECT u.prenom, u.nom, s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, 
                           t.tel as tuteur_tel, t.email as tuteur_email
                    FROM utilisateur u 
                    LEFT JOIN stage s ON u.num_stage = s.num 
                    LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                    WHERE u.num = $eleve_id";
    $stage_result = mysqli_query($bdd, $stage_query);
    if (mysqli_num_rows($stage_result) > 0) {
        $stage_info = mysqli_fetch_assoc($stage_result);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des comptes rendus</title>
    <script src="liste_cr_prof.js"></script>
</head>

<body>
    <h2>Liste de tous les comptes rendus</h2>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- Barre de recherche -->
    <div>
        <form method="GET">
            <input type="text" name="search" placeholder="Rechercher dans les CR..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding: 8px; width: 300px;">
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['eleve'])): ?>
                <input type="hidden" name="eleve" value="<?php echo htmlspecialchars($_GET['eleve']); ?>">
            <?php endif; ?>
            <button type="submit">🔍 Rechercher</button>
            <?php if (isset($_GET['search'])): ?>
                <a href="liste_cr_prof.php?<?php 
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                ?>">❌ Effacer</a>
            <?php endif; ?>
        </form>
    </div>

    <br>

    <!-- Options de tri et filtrage -->
    <div>
        <strong>Trier par :</strong>
        <a href="?sort=eleve<?php 
            echo isset($_GET['eleve']) ? '&eleve=' . $_GET['eleve'] : '';
            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>">
            <button type="button"><?php echo (isset($_GET['sort']) && $_GET['sort'] == 'eleve') ? '▶ Élève (A-Z)' : 'Élève (A-Z)'; ?></button>
        </a>
        <a href="?sort=date_asc<?php 
            echo isset($_GET['eleve']) ? '&eleve=' . $_GET['eleve'] : '';
            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>">
            <button type="button"><?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_asc') ? '▶ Date (plus anciens)' : 'Date (plus anciens)'; ?></button>
        </a>
        <a href="?sort=date_desc<?php 
            echo isset($_GET['eleve']) ? '&eleve=' . $_GET['eleve'] : '';
            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>">
            <button type="button"><?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_desc') ? '▶ Date (plus récents)' : 'Date (plus récents)'; ?></button>
        </a>
        <a href="?sort=vu<?php 
            echo isset($_GET['eleve']) ? '&eleve=' . $_GET['eleve'] : '';
            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>">
            <button type="button"><?php echo (isset($_GET['sort']) && $_GET['sort'] == 'vu') ? '▶ Déjà vus' : 'Déjà vus'; ?></button>
        </a>
        <a href="?sort=non_vu<?php 
            echo isset($_GET['eleve']) ? '&eleve=' . $_GET['eleve'] : '';
            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>">
            <button type="button"><?php echo (isset($_GET['sort']) && $_GET['sort'] == 'non_vu') ? '▶ Non vus' : 'Non vus'; ?></button>
        </a>
        <a href="liste_cr_prof.php"><button type="button">Tous les CR</button></a>
    </div>

    <br>

    <!-- Filtre par élève -->
    <div>
        <strong>Filtrer par élève :</strong>
        <form method="GET" id="filterForm">
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>

            <select name="eleve" id="eleveSelect" onchange="document.getElementById('filterForm').submit()">
                <option value="">-- Tous les élèves --</option>
                <?php
                mysqli_data_seek($eleves_result, 0);
                while ($eleve = mysqli_fetch_assoc($eleves_result)):
                    ?>
                    <option value="<?php echo $eleve['num']; ?>" <?php echo (isset($_GET['eleve']) && $_GET['eleve'] == $eleve['num']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <br>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Date et heure</th>
                    <th>Description</th>
                    <th>Pièces jointes</th>
                    <th>Commentaires</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cr = mysqli_fetch_assoc($result)): 
                    $pieces_jointes = getPiecesJointes($cr['num']);
                    $commentaires = getCommentaires($cr['num']);
                ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?>
                            <br>
                            <a href="?view_stage=<?php echo $cr['num_utilisateur']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>" style="font-size: 12px;">📋 Voir le stage</a>
                        </td>
                        <td><?php echo formatDateFrench($cr['datetime']); ?></td>
                        <td>
                            <a href="?view=<?php echo $cr['num']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>" class="view-cr">
                                <?php
                                $desc = htmlspecialchars($cr['description']);
                                echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($pieces_jointes)): ?>
                                <?php foreach ($pieces_jointes as $piece): ?>
                                    <div style="margin: 2px 0;">
                                        <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank">
                                            📎 <?php echo htmlspecialchars($piece['nom_fichier']); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($commentaires)): ?>
                                <?php echo count($commentaires); ?> commentaire(s)
                            <?php else: ?>
                                Aucun
                            <?php endif; ?>
                        </td>
                        <td><?php echo $cr['vu'] ? '✅ Vu' : '❌ Non vu'; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="cr_num" value="<?php echo $cr['num']; ?>">
                                <?php if ($cr['vu'] == 0): ?>
                                    <input type="hidden" name="vu_value" value="1">
                                    <button type="submit" name="update_vu">Marquer comme vu</button>
                                <?php else: ?>
                                    <input type="hidden" name="vu_value" value="0">
                                    <button type="submit" name="update_vu" class="non-vu-btn">Marquer comme non vu</button>
                                <?php endif; ?>
                            </form>
                            <br>
                            <a href="?view=<?php echo $cr['num']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>">📝 Commenter</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun compte rendu trouvé.</p>
    <?php endif; ?>

    <!-- Modal pour afficher le détail du CR -->
    <?php if ($cr_detail): ?>
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="background: white; margin: 50px auto; padding: 20px; width: 80%; max-width: 700px; position: relative;">
                <span style="position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer;" onclick="window.location.href='liste_cr_prof.php?<?php
                echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                ?>'">&times;</span>

                <h2>Compte rendu détaillé</h2>

                <div>
                    <p><strong>Élève :</strong>
                        <?php echo htmlspecialchars($cr_detail['prenom'] . ' ' . $cr_detail['nom']); ?>
                        <a href="?view_stage=<?php echo $cr_detail['num_utilisateur']; ?><?php
                           echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                           echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                           echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                           ?>" style="margin-left: 10px;">📋 Voir le stage</a>
                    </p>
                    <p><strong>Date de création :</strong> <?php echo formatDateFrench($cr_detail['datetime']); ?></p>
                    <p><strong>Statut :</strong> <?php echo $cr_detail['vu'] ? '✅ Vu' : '❌ Non vu'; ?></p>
                </div>

                <div>
                    <h3>Description :</h3>
                    <p><?php echo nl2br(htmlspecialchars($cr_detail['description'])); ?></p>
                </div>

                <!-- Affichage des pièces jointes -->
                <?php 
                $pieces_jointes = getPiecesJointes($cr_detail['num']);
                if (!empty($pieces_jointes)): ?>
                    <div>
                        <h3>Pièces jointes :</h3>
                        <?php foreach ($pieces_jointes as $piece): ?>
                            <div style="background: #f0f0f0; padding: 8px; margin: 5px 0; border-radius: 3px;">
                                <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank">
                                    📎 <?php echo htmlspecialchars($piece['nom_fichier']); ?>
                                </a>
                                (<?php echo formaterTailleFichier($piece['taille']); ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Affichage des commentaires existants -->
                <?php 
                $commentaires = getCommentaires($cr_detail['num']);
                if (!empty($commentaires)): ?>
                    <div>
                        <h3>Commentaires :</h3>
                        <?php foreach ($commentaires as $commentaire): ?>
                            <div style="background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 3px solid #007bff;">
                                <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                (<?php echo formatDateFrench($commentaire['date_creation']); ?>):<br>
                                <?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulaire pour ajouter un commentaire -->
                <div>
                    <h3>Ajouter un commentaire :</h3>
                    <form method="POST">
                        <input type="hidden" name="cr_num" value="<?php echo $cr_detail['num']; ?>">
                        <textarea name="commentaire" rows="4" cols="60" placeholder="Votre commentaire..." required></textarea><br>
                        <button type="submit" name="ajouter_commentaire">Ajouter le commentaire</button>
                    </form>
                    <?php if (isset($message_commentaire)): ?>
                        <p style="color:green"><?php echo $message_commentaire; ?></p>
                    <?php endif; ?>
                    <?php if (isset($error_commentaire)): ?>
                        <p style="color:red"><?php echo $error_commentaire; ?></p>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="cr_num" value="<?php echo $cr_detail['num']; ?>">
                    <?php if ($cr_detail['vu'] == 0): ?>
                        <input type="hidden" name="vu_value" value="1">
                        <button type="submit" name="update_vu">Marquer comme vu</button>
                    <?php else: ?>
                        <input type="hidden" name="vu_value" value="0">
                        <button type="submit" name="update_vu" class="non-vu-btn">Marquer comme non vu</button>
                    <?php endif; ?>
                </form>

                <p>
                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    ?>">Fermer</a>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour afficher les infos de stage -->
    <?php if ($stage_info): ?>
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="background: white; margin: 50px auto; padding: 20px; width: 80%; max-width: 700px; position: relative;">
                <span style="position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer;" onclick="window.location.href='liste_cr_prof.php?<?php
                echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                echo isset($_GET['view']) ? '&view=' . $_GET['view'] : '';
                ?>'">&times;</span>

                <h2>Informations de stage - <?php echo $stage_info['prenom'] . ' ' . $stage_info['nom']; ?></h2>

                <?php if (!empty($stage_info['nom'])): ?>
                    <h3>Entreprise</h3>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($stage_info['nom'] ?? ''); ?></p>
                    <p><strong>Adresse :</strong> <?php echo htmlspecialchars($stage_info['adresse'] ?? ''); ?></p>
                    <p><strong>Code postal :</strong> <?php echo htmlspecialchars($stage_info['CP'] ?? ''); ?></p>
                    <p><strong>Ville :</strong> <?php echo htmlspecialchars($stage_info['ville'] ?? ''); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tel'] ?? ''); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['email'] ?? ''); ?></p>
                    <p><strong>Libellé du stage :</strong><br><?php echo nl2br(htmlspecialchars($stage_info['libelleStage'] ?? '')); ?></p>

                    <h3>Tuteur en entreprise</h3>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars(($stage_info['tuteur_prenom'] ?? '') . ' ' . ($stage_info['tuteur_nom'] ?? '')); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tuteur_tel'] ?? ''); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['tuteur_email'] ?? ''); ?></p>
                <?php else: ?>
                    <p>Cet élève n'a pas encore renseigné ses informations de stage.</p>
                <?php endif; ?>

                <p>
                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    echo isset($_GET['view']) ? '&view=' . $_GET['view'] : '';
                    ?>">Fermer</a>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <p><a href="accueil.php">Retour à l'accueil</a></p>
</body>

</html>