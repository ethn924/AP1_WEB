<?php
session_start();
include '_conf.php';
include 'fonctions.php';

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
        $cr_query = "SELECT num_utilisateur FROM cr WHERE num = $cr_num";
        $cr_result = mysqli_query($bdd, $cr_query);
        $cr_data = mysqli_fetch_assoc($cr_result);

        if ($vu_value == 1) {
            creerNotification(
                $cr_data['num_utilisateur'],
                'cr_vu',
                'Votre compte rendu a été consulté',
                'Un professeur a consulté et examiné votre compte rendu.',
                'liste_cr.php?detail=' . $cr_num
            );
        }

        $redirect_url = "liste_cr_prof.php";
        $params = array();

        if (isset($_GET['sort']))
            $params[] = 'sort=' . $_GET['sort'];
        if (isset($_GET['eleve']))
            $params[] = 'eleve=' . $_GET['eleve'];
        if (isset($_GET['search']))
            $params[] = 'search=' . urlencode($_GET['search']);

        if (!empty($params)) {
            $redirect_url .= '?' . implode('&', $params);
        }

        header("Location: " . $redirect_url);
        exit();
    }
}

// Traitement de l'ajout de commentaire
$message_commentaire = '';
$error_commentaire = '';
if (isset($_POST['ajouter_commentaire'])) {
    $cr_num = intval($_POST['cr_num']);
    $commentaire = mysqli_real_escape_string($bdd, $_POST['commentaire']);
    $professeur_id = $_SESSION['Sid'];

    if (ajouterCommentaire($cr_num, $professeur_id, $commentaire)) {
        $cr_query = "SELECT num_utilisateur FROM cr WHERE num = $cr_num";
        $cr_result = mysqli_query($bdd, $cr_query);
        $cr_data = mysqli_fetch_assoc($cr_result);

        creerNotification(
            $cr_data['num_utilisateur'],
            'commentaire',
            'Nouveau commentaire sur votre CR',
            'Un professeur a ajouté un commentaire sur votre compte rendu.',
            'liste_cr.php?detail=' . $cr_num
        );

        $message_commentaire = "Commentaire ajouté avec succès !";
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
$order_by = "cr.datetime DESC";

if (isset($_GET['eleve']) && !empty($_GET['eleve'])) {
    $eleve_id = intval($_GET['eleve']);
    $where_conditions[] = "cr.num_utilisateur = $eleve_id";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($bdd, $_GET['search']);
    $where_conditions[] = "(cr.description LIKE '%$search_term%' OR u.nom LIKE '%$search_term%' OR u.prenom LIKE '%$search_term%')";
}

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
            break;
        case 'non_vu':
            $where_conditions[] = "cr.vu = 0";
            break;
    }
}

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
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des comptes rendus</title>
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 700px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
        }

        .modal-body {
            padding: 20px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .section-title {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .file-item {
            background: #f0f0f0;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .comment-item {
            background: #e7f3ff;
            padding: 12px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-block {
            width: 100%;
            box-sizing: border-box;
        }

        .search-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .filter-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .sort-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .sort-btn {
            background: #e9ecef;
            color: #333;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }

        .sort-btn.active {
            background: #007bff;
            color: white;
        }
    </style>
</head>

<body>
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <h1>📋 Comptes rendus des élèves</h1>

    <!-- Barre de recherche -->
    <div class="search-box">
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Rechercher dans les CR..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                style="padding: 8px; flex: 1; min-width: 250px; border: 1px solid #ddd; border-radius: 4px;">
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['eleve'])): ?>
                <input type="hidden" name="eleve" value="<?php echo htmlspecialchars($_GET['eleve']); ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-sm">
                🔍 Rechercher
            </button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="liste_cr_prof.php<?php
                echo isset($_GET['sort']) ? '?sort=' . $_GET['sort'] : '';
                echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : (isset($_GET['eleve']) ? '?' : '');
                echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                ?>" class="btn btn-secondary btn-sm">
                    ✕ Réinitialiser
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Options de tri -->
    <div class="filter-box">
        <strong>Trier par :</strong>
        <div class="sort-options">
            <a href="?sort=eleve<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                class="sort-btn <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'eleve') ? 'active' : ''; ?>">Élève
                (A-Z)</a>
            <a href="?sort=date_asc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                class="sort-btn <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_asc') ? 'active' : ''; ?>">Date
                (plus anciens)</a>
            <a href="?sort=date_desc<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                class="sort-btn <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_desc') ? 'active' : ''; ?>">Date
                (plus récents)</a>
            <a href="?sort=vu<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                class="sort-btn <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'vu') ? 'active' : ''; ?>">Consultés</a>
            <a href="?sort=non_vu<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                class="sort-btn <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'non_vu') ? 'active' : ''; ?>">Non
                consultés</a>
            <a href="liste_cr_prof.php" class="sort-btn">Tous</a>
        </div>
    </div>

    <!-- Filtre par élève -->
    <div class="filter-box">
        <strong>Filtrer par élève :</strong>
        <form method="GET" style="margin-top: 10px;">
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>

            <select name="eleve" onchange="this.form.submit();"
                style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
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

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1" cellpadding="12" cellspacing="0" style="width: 100%;">
            <thead>
                <tr style="background: #f8f9fa;">
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
                            <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong>
                            <br><a href="?view_stage=<?php echo $cr['num_utilisateur']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>" style="font-size: 12px; color: #007bff;">Voir le stage</a>
                        </td>
                        <td><?php echo formatDateFrench($cr['datetime']); ?></td>
                        <td>
                            <a href="?view=<?php echo $cr['num']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>" style="color: #007bff; text-decoration: none; cursor: pointer;">
                                <?php
                                $desc = strip_tags($cr['description']);
                                echo (strlen($desc) > 80) ? substr($desc, 0, 80) . '...' : $desc;
                                ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($pieces_jointes)): ?>
                                <span style="background: #e7f3ff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo count($pieces_jointes); ?> fichier(s)
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($commentaires)): ?>
                                <span style="background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo count($commentaires); ?> commentaire(s)
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $cr['vu'] ? '✅ Consulté' : '⏳ Non consulté'; ?></td>
                        <td>
                            <form method="POST" style="display: inline; margin-bottom: 5px;">
                                <input type="hidden" name="cr_num" value="<?php echo $cr['num']; ?>">
                                <?php if ($cr['vu'] == 0): ?>
                                    <input type="hidden" name="vu_value" value="1">
                                    <button type="submit" name="update_vu" class="btn btn-success btn-sm">
                                        Marquer comme vu
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="vu_value" value="0">
                                    <button type="submit" name="update_vu" class="btn btn-warning btn-sm">
                                        Marquer comme non vu
                                    </button>
                                <?php endif; ?>
                            </form>
                            <br>
                            <a href="?view=<?php echo $cr['num']; ?><?php
                               echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                               echo isset($_GET['eleve']) ? '&eleve=' . urlencode($_GET['eleve']) : '';
                               echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                               ?>" class="btn btn-primary btn-sm">
                                Voir détails
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background: #f8f9fa; padding: 30px; text-align: center; border-radius: 4px; border: 1px solid #dee2e6;">
            <p style="color: #999; font-size: 16px;">Aucun compte rendu trouvé</p>
        </div>
    <?php endif; ?>

    <!-- Modal pour afficher le détail du CR -->
    <?php if ($cr_detail):
        $commentaires = getCommentaires($cr_detail['num']);
        $pieces_jointes = getPiecesJointes($cr_detail['num']);
        ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 style="margin: 0;">Détail du compte rendu</h2>
                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    ?>" style="text-decoration: none; font-size: 24px; color: #999; cursor: pointer;">✕</a>
                </div>

                <div class="modal-body">
                    <div class="info-box">
                        <p><strong>Élève :</strong>
                            <?php echo htmlspecialchars($cr_detail['prenom'] . ' ' . $cr_detail['nom']); ?></p>
                        <p><strong>Date de création :</strong> <?php echo formatDateFrench($cr_detail['datetime']); ?></p>
                        <p><strong>Statut :</strong> <?php echo $cr_detail['vu'] ? '✅ Consulté' : '⏳ Non consulté'; ?></p>
                    </div>

                    <h3 class="section-title">Description</h3>
                    <div
                        style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin-bottom: 20px; line-height: 1.6; text-align: left; word-wrap: break-word;">
                        <?php
                        $desc = $cr_detail['description'];
                        // Nettoyage complet des espaces superflus
                        $desc = trim($desc);
                        $desc = preg_replace('/^[ \t]+/m', '', $desc);
                        $desc = preg_replace('/\n{3,}/', "\n\n", $desc);
                        echo nl2br(htmlspecialchars($desc));
                        ?>
                    </div>

                    <!-- Contenu HTML -->
                    <?php if (!empty(trim(strip_tags($cr_detail['contenu_html'])))): ?>
                        <h3 class="section-title">Contenu du compte rendu</h3>
                        <div
                            style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; line-height: 1.8;">
                            <?php
                            $html = $cr_detail['contenu_html'];
                            $html = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>;)*<\/p>/i', '', $html);
                            $html = preg_replace('/margin(-left|-right|-top|-bottom)?:[^;]*;?/i', '', $html);
                            $html = preg_replace('/padding(-left|-right|-top|-bottom)?:[^;]*;?/i', '', $html);
                            echo $html;
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pièces jointes -->
                    <?php if (!empty($pieces_jointes)): ?>
                        <h3 class="section-title">Pièces jointes</h3>
                        <div style="margin-bottom: 20px;">
                            <?php foreach ($pieces_jointes as $piece): ?>
                                <div class="file-item">
                                    <span>📄 <?php echo htmlspecialchars($piece['nom_fichier']); ?>
                                        (<?php echo formaterTailleFichier($piece['taille']); ?>)</span>
                                    <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank"
                                        class="btn btn-primary btn-sm">
                                        Télécharger
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Commentaires existants -->
                    <?php if (!empty($commentaires)): ?>
                        <h3 class="section-title">Commentaires existants</h3>
                        <div style="margin-bottom: 20px;">
                            <?php foreach ($commentaires as $commentaire): ?>
                                <div class="comment-item">
                                    <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                    <span style="color: #999; font-size: 12px;">—
                                        <?php echo formatDateFrench($commentaire['date_creation']); ?></span>
                                    <p style="margin: 8px 0 0 0; line-height: 1.5; text-align: left; word-wrap: break-word;">
                                        <?php
                                        $comment = $commentaire['commentaire'];
                                        $comment = trim($comment);
                                        $comment = preg_replace('/^[ \t]+/m', '', $comment);
                                        $comment = preg_replace('/\n{3,}/', "\n\n", $comment);
                                        echo nl2br(htmlspecialchars($comment));
                                        ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire pour ajouter un commentaire -->
                    <h3 class="section-title">Ajouter un commentaire</h3>
                    <form method="POST">
                        <input type="hidden" name="cr_num" value="<?php echo $cr_detail['num']; ?>">
                        <textarea name="commentaire" rows="5"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; margin-bottom: 10px;"
                            placeholder="Votre commentaire..." required></textarea>
                        <button type="submit" name="ajouter_commentaire" class="btn btn-success">
                            Ajouter le commentaire
                        </button>
                    </form>

                    <?php if ($message_commentaire): ?>
                        <div
                            style="background: #d4edda; color: #155724; padding: 12px; margin-top: 15px; border-radius: 4px; border-left: 4px solid #28a745;">
                            ✅ <?php echo $message_commentaire; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bouton marquer comme vu/non vu -->
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="cr_num" value="<?php echo $cr_detail['num']; ?>">
                        <?php if ($cr_detail['vu'] == 0): ?>
                            <input type="hidden" name="vu_value" value="1">
                            <button type="submit" name="update_vu" class="btn btn-success btn-block">
                                ✅ Marquer comme consulté
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="vu_value" value="0">
                            <button type="submit" name="update_vu" class="btn btn-warning btn-block">
                                ⏳ Marquer comme non consulté
                            </button>
                        <?php endif; ?>
                    </form>

                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    ?>" class="btn btn-secondary btn-block" style="margin-top: 10px;">
                        Fermer
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour afficher les infos de stage -->
    <?php if (isset($_GET['view_stage']) && !empty($_GET['view_stage'])):
        $eleve_id = intval($_GET['view_stage']);
        $stage_query = "SELECT u.prenom, u.nom, s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, 
                                t.tel as tuteur_tel, t.email as tuteur_email
                         FROM utilisateur u 
                         LEFT JOIN stage s ON u.num_stage = s.num 
                         LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                         WHERE u.num = $eleve_id";
        $stage_result = mysqli_query($bdd, $stage_query);
        $stage_info = mysqli_fetch_assoc($stage_result);
        ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 style="margin: 0;">Informations de stage</h2>
                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    echo isset($_GET['view']) ? '&view=' . $_GET['view'] : '';
                    ?>" style="text-decoration: none; font-size: 24px; color: #999; cursor: pointer;">✕</a>
                </div>

                <div class="modal-body">
                    <p style="margin-bottom: 15px;"><strong>Élève :</strong>
                        <?php echo htmlspecialchars($stage_info['prenom'] . ' ' . $stage_info['nom']); ?></p>

                    <?php if (!empty($stage_info['nom'])): ?>
                        <h3 class="section-title">Entreprise</h3>
                        <div class="info-box">
                            <p><strong>Nom :</strong> <?php echo htmlspecialchars($stage_info['nom']); ?></p>
                            <p><strong>Adresse :</strong> <?php echo htmlspecialchars($stage_info['adresse']); ?></p>
                            <p><strong>Code postal :</strong> <?php echo htmlspecialchars($stage_info['CP']); ?></p>
                            <p><strong>Ville :</strong> <?php echo htmlspecialchars($stage_info['ville']); ?></p>
                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tel']); ?></p>
                            <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['email']); ?></p>
                            <p style="margin-top: 10px;"><strong>Libellé du stage :</strong></p>
                            <p style="line-height: 1.5; text-align: left; word-wrap: break-word;">
                                <?php
                                $libelle = $stage_info['libelleStage'];
                                $libelle = trim($libelle);
                                $libelle = preg_replace('/^[ \t]+/m', '', $libelle);
                                $libelle = preg_replace('/\n{3,}/', "\n\n", $libelle);
                                echo nl2br(htmlspecialchars($libelle));
                                ?>
                            </p>
                        </div>

                        <h3 class="section-title">Tuteur en entreprise</h3>
                        <div class="info-box">
                            <p><strong>Nom :</strong>
                                <?php echo htmlspecialchars($stage_info['tuteur_prenom'] . ' ' . $stage_info['tuteur_nom']); ?>
                            </p>
                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tuteur_tel']); ?></p>
                            <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['tuteur_email']); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">
                            <p>Cet élève n'a pas encore renseigné ses informations de stage.</p>
                        </div>
                    <?php endif; ?>

                    <a href="liste_cr_prof.php?<?php
                    echo isset($_GET['sort']) ? 'sort=' . $_GET['sort'] : '';
                    echo (isset($_GET['sort']) && isset($_GET['eleve'])) ? '&' : '';
                    echo isset($_GET['eleve']) ? 'eleve=' . $_GET['eleve'] : '';
                    echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    echo isset($_GET['view']) ? '&view=' . $_GET['view'] : '';
                    ?>" class="btn btn-secondary btn-block" style="margin-top: 15px;">
                        Fermer
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>