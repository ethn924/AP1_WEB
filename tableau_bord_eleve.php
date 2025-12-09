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
$user_id = intval($_SESSION['Sid']);
$stats_query = "SELECT
COUNT(*) as total_cr,
SUM(CASE WHEN vu = 1 THEN 1 ELSE 0 END) as cr_vus,
SUM(CASE WHEN vu = 0 THEN 1 ELSE 0 END) as cr_non_vus,
MAX(datetime) as dernier_cr
FROM cr
WHERE num_utilisateur = $user_id";
$stats_result = mysqli_query($bdd, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
$commentaires_query = "SELECT c.*, cr.description as cr_description, u.nom, u.prenom
FROM commentaires c
JOIN cr ON c.cr_id = cr.num
JOIN utilisateur u ON c.professeur_id = u.num
WHERE cr.num_utilisateur = $user_id
ORDER BY c.date_creation DESC
LIMIT 5";
$commentaires_result = mysqli_query($bdd, $commentaires_query);
$derniers_cr_query = "SELECT * FROM cr
WHERE num_utilisateur = $user_id
ORDER BY datetime DESC
LIMIT 5";
$derniers_cr_result = mysqli_query($bdd, $derniers_cr_query);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Élève</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <?php afficherHeaderNavigation(); ?>
    <?php afficherHeaderPage('📊', 'Tableau de bord', 'Suivez vos comptes rendus et les retours de vos professeurs'); ?>
    
    <div class="container">
        <div class="profile-section">
            <h3>🎯 Actions rapides</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                <a href="editer_cr.php" class="btn-update" style="text-align: center; padding: 12px; display: block;">✏️ Nouveau CR</a>
                <a href="liste_cr.php" class="btn-update" style="text-align: center; padding: 12px; display: block;">📋 Mes CRs</a>
                <a href="tutoriel.php" class="btn-update" style="text-align: center; padding: 12px; display: block;">🚀 Tutoriel</a>
            </div>
        </div>
            
        <div class="profile-section">
            <h3>📊 Vos statistiques</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #28a745;">
                    <div style="font-size: 28px; font-weight: bold; color: #667eea;"><?php echo $stats['total_cr'] ?: 0; ?></div>
                    <div style="font-size: 0.9em; color: #666;">CRs créés</div>
                </div>
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #28a745;">
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo $stats['cr_vus'] ?: 0; ?></div>
                    <div style="font-size: 0.9em; color: #666;">Consultés</div>
                </div>
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #dc3545;">
                    <div style="font-size: 28px; font-weight: bold; color: #dc3545;"><?php echo $stats['cr_non_vus'] ?: 0; ?></div>
                    <div style="font-size: 0.9em; color: #666;">En attente</div>
                </div>
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #667eea;">
                    <div style="font-size: 0.9em; color: #667eea; font-weight: 600;"><?php echo $stats['dernier_cr'] ? formatDateFrench($stats['dernier_cr']) : 'Aucun'; ?></div>
                    <div style="font-size: 0.85em; color: #666;">Dernier CR</div>
                </div>
            </div>
        </div>
            
        <div class="profile-section">
            <h3>📝 Derniers comptes rendus</h3>
                <?php if (mysqli_num_rows($derniers_cr_result) > 0): ?>
                    <ul class="cr-list">
                        <?php while ($cr = mysqli_fetch_assoc($derniers_cr_result)): ?>
                            <li class="cr-item">
                                <div class="cr-item-header">
                                    <strong>📋 <?php echo htmlspecialchars($cr['titre'] ?: strip_tags(substr($cr['description'] ?: 'CR sans titre', 0, 60))); ?></strong>
                                    <span class="cr-item-status <?php echo $cr['vu'] ? 'consulte' : 'non-consulte'; ?>">
                                        <?php echo $cr['vu'] ? '✅ Consulté' : '❌ Non consulté'; ?>
                                    </span>
                                </div>
                                <div class="cr-item-meta">
                                    📅 Créé le <?php echo formatDateFrench($cr['datetime']); ?>
                                </div>
                                <?php if (!empty($cr['description'])): ?>
                                    <p class="cr-item-preview"><?php echo htmlspecialchars(substr($cr['description'], 0, 100)) . (strlen($cr['description']) > 100 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <a href="liste_cr.php?detail=<?php echo $cr['num']; ?>" class="quick-link-btn">Voir en détail →</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <p>📭 Vous n'avez pas encore créé de compte rendu</p>
                        <a href="editer_cr.php" class="quick-link-btn" style="display: inline-block;">Créer mon premier CR →</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3>💬 Commentaires des professeurs</h3>
                <?php if (mysqli_num_rows($commentaires_result) > 0): ?>
                    <div class="comments-list">
                        <?php while ($commentaire = mysqli_fetch_assoc($commentaires_result)): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <strong>👨‍🏫 <?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                    <span class="comment-date"><?php echo formatDateFrench($commentaire['date_creation']); ?></span>
                                </div>
                                <div class="comment-cr-ref">
                                    📄 <em><?php echo htmlspecialchars(substr($commentaire['cr_description'] ?: 'CR', 0, 60)) . (strlen($commentaire['cr_description'] ?: '') > 60 ? '...' : ''); ?></em>
                                </div>
                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?></p>
                                <a href="liste_cr.php?detail=<?php echo $commentaire['cr_id']; ?>" class="quick-link-btn">Voir le CR complet →</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>💭 Aucun commentaire pour le moment</p>
                        <p style="font-size: 0.9em; color: #999;">Vos professeurs ajouteront leurs retours ici</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="link-group">
            <a href="accueil.php" class="retour-btn">← Retour à l'accueil</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>