<?php
session_start();
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

if (isset($_POST['send_con'])) {
    $login = mysqli_real_escape_string($bdd, $_POST['login']);
    $motdepasse = md5($_POST['motdepasse']);

    $query = "SELECT * FROM utilisateur WHERE login = '$login' AND motdepasse = '$motdepasse'";
    $result = mysqli_query($bdd, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Vérifier si l'email est vérifié
        if ($user['email_valide'] == 1) {
            $_SESSION['Sid'] = $user['num'];
            $_SESSION['Slogin'] = $user['login'];
            $_SESSION['Sprenom'] = $user['prenom'];
            $_SESSION['Snom'] = $user['nom'];
            $_SESSION['Stype'] = $user['type'];
        } else {
            // Email non vérifié - redirection simple sans envoi de mail
            $_SESSION['user_id_verification'] = $user['num'];
            $_SESSION['email_verification'] = $user['email'];
            header("Location: valider_email.php");
            exit();
        }
    } else {
        $error = "Erreur de mot de passe";
        logger("Tentative de connexion échouée pour le login: $login", null, 'accueil.php');
    }
}

if (isset($_POST['deco'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['Sid'])) {
    // Récupérer les notifications
    $notifications = getNotificationsNonLues($_SESSION['Sid']);
?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accueil</title>
        <style>
            .notifications {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                margin: 15px 0;
            }
            .notification {
                background: white;
                border-left: 4px solid #007bff;
                padding: 10px;
                margin: 8px 0;
                border-radius: 3px;
            }
            .notification.non-lue {
                background: #e7f3ff;
                border-left-color: #0056b3;
            }
            .notification-date {
                font-size: 0.8em;
                color: #6c757d;
            }
        </style>
    </head>
    <body>
        <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

        <h1>Bienvenue <?php echo $_SESSION['Sprenom'] . " " . $_SESSION['Snom']; ?> !</h1>

        <!-- Affichage des notifications -->
        <?php if (!empty($notifications)): ?>
            <div class="notifications">
                <h3>🔔 Notifications</h3>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification <?php echo $notif['lue'] ? '' : 'non-lue'; ?>">
                        <strong><?php echo htmlspecialchars($notif['titre']); ?></strong>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <div class="notification-date">
                            <?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?>
                            <?php if ($notif['lien']): ?>
                                - <a href="<?php echo htmlspecialchars($notif['lien']); ?>">Voir</a>
                            <?php endif; ?>
                            - <a href="marquer_lue.php?id=<?php echo $notif['id']; ?>">Marquer comme lue</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p><a href="notifications.php">Voir toutes les notifications</a></p>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['Stype'] == 0): ?>
            <h3>Menu Élève</h3>
            <ul>
                <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                <li><a href="editer_cr.php">Créer/modifier un compte rendu</a></li>
                <li><a href="mon_stage.php">Informations de stage</a></li>
                <li><a href="perso.php">Informations personnelles</a></li>
            </ul>
        <?php else: ?>
            <h3>Menu Professeur</h3>
            <ul>
                <li><a href="liste_eleves.php">Liste des élèves</a></li>
                <li><a href="liste_cr_prof.php">Liste des comptes rendus</a></li>
                <li><a href="export.php?type=cr&format=csv">Exporter les données</a></li>
                <li><a href="statistiques.php">Statistiques</a></li>
            </ul>
            
            <h4>Export des données :</h4>
            <ul>
                <li><a href="export.php?type=cr&format=csv">Export CR (CSV)</a></li>
                <li><a href="export.php?type=cr&format=pdf">Export CR (PDF)</a></li>
                <li><a href="export.php?type=eleves&format=csv">Export Élèves (CSV)</a></li>
                <li><a href="export.php?type=statistiques&format=csv">Export Statistiques (CSV)</a></li>
            </ul>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="deco">Déconnexion</button>
        </form>
    </body>
    </html>
<?php
} else {
    echo "La connexion est perdue, veuillez revenir à la <a href='index.php'>page d'index</a> pour vous reconnecter.";
}
?>