<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = intval($_SESSION['Sid']);

// Marquer toutes comme lues
if (isset($_POST['tout_marquer_lu'])) {
    $query = "UPDATE notifications SET lue = 1 WHERE utilisateur_id = $user_id AND lue = 0";
    mysqli_query($bdd, $query);
}

// Récupérer toutes les notifications
$query = "SELECT * FROM notifications WHERE utilisateur_id = $user_id ORDER BY date_creation DESC";
$result = mysqli_query($bdd, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes notifications</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="common.css">
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    
    <?php afficherHeaderPage('🔔', 'Notifications', 'Consultez toutes vos notifications'); ?>
    
    <div class="container">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="button-container">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="tout_marquer_lu">✅ Tout marquer comme lu</button>
                </form>
            </div>
            
            <?php while ($notif = mysqli_fetch_assoc($result)): ?>
                <div class="notification <?php echo $notif['lue'] ? '' : 'non-lue'; ?>">
                    <h4><?php echo htmlspecialchars($notif['titre']); ?></h4>
                    <p><?php echo htmlspecialchars($notif['message']); ?></p>
                    <div class="notification-date">
                        📅 <?php echo date('d/m/Y à H:i', strtotime($notif['date_creation'])); ?>
                        <?php if ($notif['lien']): ?>
                            | <a href="<?php echo htmlspecialchars($notif['lien']); ?>">🔗 Voir</a>
                        <?php endif; ?>
                        <?php if (!$notif['lue']): ?>
                            | <a href="marquer_lue.php?id=<?php echo $notif['id']; ?>">📖 Marquer comme lue</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Vous n'avez aucune notification pour le moment</p>
                <p style="font-size: 0.9em; color: #999;">Les notifications apparaîtront ici dès qu'il y aura des mises à jour sur vos comptes rendus</p>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>