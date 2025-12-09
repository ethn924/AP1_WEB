<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 0) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) die("Erreur connexion BDD");

$user_id = i($_SESSION['Sid']);

if (isset($_POST['delete_cr'])) {
    $cr_id = i($_POST['delete_cr']);
    $check = fetchOne("SELECT * FROM cr WHERE num = $cr_id AND num_utilisateur = $user_id");
    
    if ($check) {
        execQuery("DELETE FROM cr WHERE num = $cr_id");
        execQuery("DELETE FROM commentaires WHERE cr_id = $cr_id");
        execQuery("DELETE FROM pieces_jointes WHERE cr_id = $cr_id");
        header("Location: liste_cr.php");
        exit;
    }
}

$query = "SELECT * FROM cr WHERE num_utilisateur = $user_id ORDER BY datetime DESC";
$result = mysqli_query($bdd, $query);

// Gestion de l'affichage du détail d'un CR
$cr_detail = null;
if (isset($_GET['detail']) && !empty($_GET['detail'])) {
    $cr_num = intval($_GET['detail']);
    $detail_query = "SELECT * FROM cr WHERE num = $cr_num AND num_utilisateur = $user_id";
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
    <title>Mes comptes rendus</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="cr.css">
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <?php afficherHeaderPage('📋', 'Mes comptes rendus', 'Consultez et gérez tous vos comptes rendus'); ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div style="overflow-x: auto; margin: 20px 0;">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>📝 Titre</th>
                    <th>📅 Date et heure</th>
                    <th>👁️ Aperçu</th>
                    <th>✓ Statut</th>
                    <th>⚙️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cr = mysqli_fetch_assoc($result)): 
                    $commentaires = getCommentaires($cr['num']);
                    $pieces_jointes = getPiecesJointes($cr['num']);
                    $statut_vu = $cr['vu'] == 1 ? "✅ Consulté" : "⏳ Non consulté";
                ?>
                <tr>
                    <td class="col-titre">
                        <?php 
                        $titre = htmlspecialchars($cr['titre'] ?? 'Sans titre');
                        echo (strlen($titre) > 35) ? substr($titre, 0, 35) . '...' : $titre;
                        ?>
                    </td>
                    <td class="col-date">
                        <?php echo formatDateFrench($cr['datetime']); ?>
                    </td>
                    <td class="col-apercu">
                        <?php 
                        $apercu = strip_tags($cr['contenu_html'] ?? '');
                        $apercu_court = (strlen($apercu) > 60) ? substr($apercu, 0, 60) . '...' : $apercu;
                        echo htmlspecialchars($apercu_court);
                        ?>
                    </td>
                    <td class="col-statut">
                        <?php echo $statut_vu; ?>
                    </td>
                    <td class="col-actions">
                        <div class="action-buttons">
                            <a href="?detail=<?php echo $cr['num']; ?>" class="btn-action btn-info">📄 Détails</a>
                            <a href="export_pdf_cr.php?id=<?php echo $cr['num']; ?>" class="btn-action btn-success" target="_blank">📥 PDF</a>
                            <a href="editer_cr.php?id=<?php echo $cr['num']; ?>" class="btn-action btn-warning">✏️ Modifier</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce compte rendu ?');">
                                <input type="hidden" name="delete_cr" value="<?php echo $cr['num']; ?>">
                                <button type="submit" class="btn-action btn-danger">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
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
                <a href="liste_cr.php" style="text-decoration: none; font-size: 24px; color: #999; cursor: pointer;">✕</a>
            </div>

            <div class="modal-body">
                <div class="info-box">
                    <?php if (!empty($cr_detail['titre'])): ?>
                        <h2 style="margin: 0 0 15px 0; color: #333;">📝 <?php echo htmlspecialchars($cr_detail['titre']); ?></h2>
                    <?php endif; ?>
                    <p><strong>Date de création :</strong> <?php echo formatDateFrench($cr_detail['datetime']); ?></p>
                    <p><strong>Statut :</strong> <?php echo $cr_detail['vu'] == 1 ? "✅ Consulté par des professeurs" : "⏳ Pas encore consulté"; ?></p>
                </div>

                <!-- Section qui a vu -->
                <?php if ($cr_detail['vu'] == 1): ?>
                <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <strong>📊 Consulté par les professeurs</strong>
                    <p style="margin-top: 8px; color: #155724;">Votre compte rendu a été consulté et examiné par des professeurs.</p>
                </div>
                <?php endif; ?>

                <?php if (!empty($cr_detail['description'])): ?>
                <h3 class="section-title">Description courte</h3>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin-bottom: 20px; line-height: 1.6; text-align: left; word-wrap: break-word; color: #666; font-size: 14px;">
                    <?php echo htmlspecialchars($cr_detail['description']); ?>
                </div>
                <?php endif; ?>

                <!-- Section contenu HTML -->
                <?php if (!empty($cr_detail['contenu_html']) && !empty(trim(strip_tags($cr_detail['contenu_html'])))): ?>
                <h3 class="section-title">Contenu du compte rendu</h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; line-height: 1.8;">
                    <?php 
                    $html = $cr_detail['contenu_html'];
                    $html = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>;)*<\/p>/i', '', $html);
                    $html = preg_replace('/margin(-left|-right|-top|-bottom)?:[^;]*;?/i', '', $html);
                    $html = preg_replace('/padding(-left|-right|-top|-bottom)?:[^;]*;?/i', '', $html);
                    echo $html;
                    ?>
                </div>
                <?php endif; ?>

                <!-- Section pièces jointes -->
                <?php if (!empty($pieces_jointes)): ?>
                <h3 class="section-title">📎 Pièces jointes</h3>
                <div style="margin-bottom: 20px;">
                    <?php foreach ($pieces_jointes as $piece): ?>
                        <div class="file-item">
                            <span>📄 <?php echo htmlspecialchars($piece['nom_fichier']); ?> (<?php echo formaterTailleFichier($piece['taille']); ?>)</span>
                            <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                Télécharger
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Section commentaires -->
                <h3 class="section-title">💬 Commentaires des professeurs</h3>
                <?php if (!empty($commentaires)): ?>
                    <div style="margin-bottom: 20px;">
                        <?php foreach ($commentaires as $commentaire): ?>
                            <div class="comment-item">
                                <div style="margin-bottom: 8px;">
                                    <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                    <span style="color: #999; font-size: 12px;">
                                        — <?php echo formatDateFrench($commentaire['date_creation']); ?>
                                    </span>
                                </div>
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
                <?php else: ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                        <p style="color: #999; font-style: italic; margin: 0;">Aucun commentaire pour le moment.</p>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; padding-top: 15px; border-top: 2px solid #eee;">
                    <a href="liste_cr.php" class="btn btn-secondary btn-block">
                        Fermer
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php include 'footer.php'; ?>