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

$user_id = $_SESSION['Sid'];

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
    <title>Mes notifications</title>
    <style>
        .notification {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .notification.non-lue {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        .notification-date {
            font-size: 0.8em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <h2>Mes notifications</h2>
    
    <form method="POST">
        <button type="submit" name="tout_marquer_lu">Tout marquer comme lu</button>
    </form>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($notif = mysqli_fetch_assoc($result)): ?>
            <div class="notification <?php echo $notif['lue'] ? '' : 'non-lue'; ?>">
                <h4><?php echo htmlspecialchars($notif['titre']); ?></h4>
                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                <div class="notification-date">
                    <?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?>
                    <?php if ($notif['lien']): ?>
                        - <a href="<?php echo htmlspecialchars($notif['lien']); ?>">Voir</a>
                    <?php endif; ?>
                    <?php if (!$notif['lue']): ?>
                        - <a href="marquer_lue.php?id=<?php echo $notif['id']; ?>">Marquer comme lue</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Aucune notification.</p>
    <?php endif; ?>
</body>
</html>