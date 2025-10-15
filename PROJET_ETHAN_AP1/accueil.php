<?php
session_start();
include '_conf.php';

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
            header("Location: verifier_email.php");
            exit();
        }
    } else {
        $error = "Erreur de mot de passe";
    }
}

if (isset($_POST['deco'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['Sid'])) {
?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accueil</title>
    </head>
    <body>
        <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

        <h1>Bienvenue <?php echo $_SESSION['Sprenom'] . " " . $_SESSION['Snom']; ?> !</h1>

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