<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 0) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

function formatDateFrench($date) {
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $date_str = date('d F Y à H\hi', strtotime($date));
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}

$user_id = $_SESSION['Sid'];
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
</head>
<body>
    <h1>Mes comptes rendus</h1>
    <p><a href="accueil.php">← Retour à l'accueil</a> | <a href="tableau_bord_eleve.php">📊 Tableau de bord</a></p>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-top: 20px;">
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cr = mysqli_fetch_assoc($result)): 
                    // Récupérer les commentaires et qui a vu
                    $commentaires = getCommentaires($cr['num']);
                    $pieces_jointes = getPiecesJointes($cr['num']);
                    
                    // Récupérer qui a vu ce CR
                    $qui_a_vu_query = "SELECT DISTINCT COUNT(*) as nb_vus FROM (
                        SELECT num FROM utilisateur WHERE type = 1
                    ) prof WHERE $cr[vu] = 1";
                    $statut_vu = $cr['vu'] == 1 ? "✅ Consulté" : "❌ Non consulté";
                ?>
                <tr>
                    <td><?php echo formatDateFrench($cr['datetime']); ?></td>
                    <td>
                        <?php 
                        $desc = htmlspecialchars($cr['description']);
                        echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                        ?>
                    </td>
                    <td><?php echo $statut_vu; ?></td>
                    <td>
                        <a href="?detail=<?php echo $cr['num']; ?>" style="text-decoration: none; background: #007bff; color: white; padding: 8px 15px; border-radius: 4px; display: inline-block;">
                            📄 Voir détails
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun compte rendu trouvé.</p>
    <?php endif; ?>

    <!-- Modal de détail du CR -->
    <?php if ($cr_detail): 
        $commentaires = getCommentaires($cr_detail['num']);
        $pieces_jointes = getPiecesJointes($cr_detail['num']);
        
        // Récupérer les professeurs qui ont vu ce CR
        $profs_qui_ont_vu_query = "SELECT DISTINCT u.num, u.prenom, u.nom FROM utilisateur u WHERE u.type = 1 AND $cr_detail[vu] = 1 LIMIT 5";
        $profs_qui_ont_vu = mysqli_query($bdd, $profs_qui_ont_vu_query);
    ?>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 700px; width: 90%; max-height: 85vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                <h2>Détail du compte rendu</h2>
                <a href="liste_cr.php" style="text-decoration: none; font-size: 24px; color: #999;">✕</a>
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p><strong>Date de création :</strong> <?php echo formatDateFrench($cr_detail['datetime']); ?></p>
                <p><strong>Statut :</strong> <?php echo $cr_detail['vu'] == 1 ? "✅ Consulté par des professeurs" : "❌ Pas encore consulté"; ?></p>
            </div>

            <!-- Section qui a vu -->
            <?php if ($cr_detail['vu'] == 1): ?>
            <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <strong>📊 Consulté par les professeurs</strong>
                <p style="margin-top: 8px; color: #155724;">Votre compte rendu a été consulté et examiné par des professeurs.</p>
            </div>
            <?php endif; ?>

            <!-- Section description -->
            <div style="margin-bottom: 25px;">
                <h3>Description</h3>
                <p style="white-space: pre-wrap; line-height: 1.6;">
                    <?php echo htmlspecialchars($cr_detail['description']); ?>
                </p>
            </div>

            <!-- Section pièces jointes -->
            <?php if (!empty($pieces_jointes)): ?>
            <div style="margin-bottom: 25px;">
                <h3>📎 Pièces jointes</h3>
                <?php foreach ($pieces_jointes as $piece): ?>
                    <div style="background: #f0f0f0; padding: 10px; margin: 8px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <span>📄 <?php echo htmlspecialchars($piece['nom_fichier']); ?> (<?php echo formaterTailleFichier($piece['taille']); ?>)</span>
                        <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank" style="background: #007bff; color: white; padding: 5px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                            Télécharger
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Section commentaires -->
            <div style="margin-bottom: 25px;">
                <h3>💬 Commentaires des professeurs</h3>
                <?php if (!empty($commentaires)): ?>
                    <?php foreach ($commentaires as $commentaire): ?>
                        <div style="background: #e7f3ff; padding: 15px; margin: 12px 0; border-left: 4px solid #007bff; border-radius: 4px;">
                            <div style="margin-bottom: 8px;">
                                <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                <span style="color: #999; font-size: 12px;">
                                    — <?php echo formatDateFrench($commentaire['date_creation']); ?>
                                </span>
                            </div>
                            <p style="white-space: pre-wrap; line-height: 1.5; margin: 0;">
                                <?php echo htmlspecialchars($commentaire['commentaire']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999; font-style: italic;">Aucun commentaire pour le moment.</p>
                <?php endif; ?>
            </div>

            <div style="text-align: center; padding-top: 15px; border-top: 2px solid #eee;">
                <a href="liste_cr.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    Fermer
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <br><br>
</body>
</html>