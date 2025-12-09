<?php
session_start();
include '_conf.php';
include 'fonctions.php';

isset($_SESSION['Sid']) && $_SESSION['Stype'] == 1 or die(header("Location: index.php"));
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD) or die("Erreur connexion BDD");
$msg = '';

if (isset($_POST['update_vu'])) {
    $crn = intval($_POST['cr_num']);
    $vu = intval($_POST['vu_value']);
    if (mysqli_query($bdd, "UPDATE cr SET vu = $vu WHERE num = $crn")) {
        $d = mysqli_fetch_assoc(mysqli_query($bdd, "SELECT num_utilisateur FROM cr WHERE num = $crn"));
        if ($vu == 1) creerNotification($d['num_utilisateur'], 'cr_vu', 'Votre compte rendu a été consulté', 'Un professeur a consulté et examiné votre compte rendu.', 'liste_cr.php?detail=' . $crn);
        $url = "liste_cr_prof.php";
        $p = [];
        if (isset($_GET['sort']) && in_array($_GET['sort'], ['eleve', 'date_asc', 'date_desc', 'vu', 'non_vu'])) $p[] = 'sort=' . $_GET['sort'];
        if (isset($_GET['eleve'])) $p[] = 'eleve=' . intval($_GET['eleve']);
        if (isset($_GET['search'])) $p[] = 'search=' . urlencode($_GET['search']);
        header("Location: " . $url . (!empty($p) ? '?' . implode('&', $p) : ''));
        exit();
    }
}

$msg = '';
if (isset($_POST['ajouter_commentaire'])) {
    $crn = intval($_POST['cr_num']);
    $com = q($_POST['commentaire']);
    if (ajouterCommentaire($crn, $_SESSION['Sid'], $com)) {
        $d = mysqli_fetch_assoc(mysqli_query($bdd, "SELECT num_utilisateur FROM cr WHERE num = $crn"));
        creerNotification($d['num_utilisateur'], 'commentaire', 'Nouveau commentaire sur votre CR', 'Un professeur a ajouté un commentaire sur votre compte rendu.', 'liste_cr.php?detail=' . $crn);
        $msg = "Commentaire ajouté avec succès !";
    }
}

$elr = mysqli_query($bdd, "SELECT DISTINCT u.num, u.nom, u.prenom FROM utilisateur u JOIN cr ON cr.num_utilisateur = u.num WHERE u.type = 0 ORDER BY u.nom ASC, u.prenom ASC");

$w = [];
$o = "cr.datetime DESC";
if (isset($_GET['eleve']) && !empty($_GET['eleve'])) $w[] = "cr.num_utilisateur = " . intval($_GET['eleve']);
if (isset($_GET['search']) && !empty($_GET['search'])) $w[] = "(cr.description LIKE '%" . q($_GET['search']) . "%' OR u.nom LIKE '%" . q($_GET['search']) . "%' OR u.prenom LIKE '%" . q($_GET['search']) . "%')";
if (isset($_GET['sort'])) {
    $s = ['eleve' => "u.nom ASC, u.prenom ASC, cr.datetime DESC", 'date_asc' => "cr.datetime ASC", 'date_desc' => "cr.datetime DESC", 'vu' => null, 'non_vu' => null];
    if (isset($s[$_GET['sort']])) {
        if ($s[$_GET['sort']]) $o = $s[$_GET['sort']];
        else $w[] = "cr.vu = " . ($_GET['sort'] == 'vu' ? 1 : 0);
    }
}

$r = mysqli_query($bdd, "SELECT cr.*, u.nom, u.prenom FROM cr JOIN utilisateur u ON cr.num_utilisateur = u.num " . (!empty($w) ? "WHERE " . implode(" AND ", $w) : "") . " ORDER BY $o");
$cd = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $dr = mysqli_query($bdd, "SELECT cr.*, u.nom, u.prenom FROM cr JOIN utilisateur u ON cr.num_utilisateur = u.num WHERE cr.num = " . intval($_GET['view']));
    $cd = mysqli_num_rows($dr) > 0 ? mysqli_fetch_assoc($dr) : null;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des comptes rendus</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="cr.css">
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
    <?php afficherHeaderNavigation(); ?>
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
                mysqli_data_seek($elr, 0);
                while ($eleve = mysqli_fetch_assoc($elr)):
                    ?>
                    <option value="<?php echo $eleve['num']; ?>" <?php echo (isset($_GET['eleve']) && $_GET['eleve'] == $eleve['num']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <?php if (mysqli_num_rows($r) > 0): ?>
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
                <?php while ($cr = mysqli_fetch_assoc($r)):
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
                            <br>
                            <a href="export_pdf_cr.php?id=<?php echo $cr['num']; ?>" class="btn btn-success btn-sm" target="_blank" style="margin-top: 5px;">
                                📥 Exporter PDF
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
    <?php if ($cd):
        $commentaires = getCommentaires($cd['num']);
        $pieces_jointes = getPiecesJointes($cd['num']);
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
                            <?php echo htmlspecialchars($cd['prenom'] . ' ' . $cd['nom']); ?></p>
                        <p><strong>Date de création :</strong> <?php echo formatDateFrench($cd['datetime']); ?></p>
                        <p><strong>Statut :</strong> <?php echo $cd['vu'] ? '✅ Consulté' : '⏳ Non consulté'; ?></p>
                    </div>

                    <h3 class="section-title">Description</h3>
                    <div
                        style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin-bottom: 20px; line-height: 1.6; text-align: left; word-wrap: break-word;">
                        <?php
                        $desc = $cd['description'];
                        // Nettoyage complet des espaces superflus
                        $desc = trim($desc);
                        $desc = preg_replace('/^[ \t]+/m', '', $desc);
                        $desc = preg_replace('/\n{3,}/', "\n\n", $desc);
                        echo nl2br(htmlspecialchars($desc));
                        ?>
                    </div>

                    <!-- Contenu HTML -->
                    <?php if (!empty(trim(strip_tags($cd['contenu_html'])))): ?>
                        <h3 class="section-title">Contenu du compte rendu</h3>
                        <div
                            style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; line-height: 1.8;">
                            <?php
                            $html = $cd['contenu_html'];
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
                        <input type="hidden" name="cr_num" value="<?php echo $cd['num']; ?>">
                        <textarea name="commentaire" rows="5"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; margin-bottom: 10px;"
                            placeholder="Votre commentaire..." required></textarea>
                        <button type="submit" name="ajouter_commentaire" class="btn btn-success">
                            Ajouter le commentaire
                        </button>
                    </form>

                    <?php if ($msg): ?>
                        <div
                            style="background: #d4edda; color: #155724; padding: 12px; margin-top: 15px; border-radius: 4px; border-left: 4px solid #28a745;">
                            ✅ <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bouton marquer comme vu/non vu -->
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="cr_num" value="<?php echo $cd['num']; ?>">
                        <?php if ($cd['vu'] == 0): ?>
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
    <?php include 'footer.php'; ?>