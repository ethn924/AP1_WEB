<?php
session_start();
include '_conf.php';

// Test de connexion à la base de données
if ($bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD)) {
    echo "Connexion à la base de données réussie.<br>";
} else {
    echo "Erreur de connexion à la base de données";
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
</head>

<body>
    <h2>Connexion</h2>
    <!-- Formulaire envoyé vers accueil.php qui gère l'authentification -->
    <form action="accueil.php" method="POST">
        <label for="login">Login :</label><br>
        <input type="text" id="login" name="login" required><br><br>

        <label for="motdepasse">Mot de passe :</label><br>
        <input type="password" id="motdepasse" name="motdepasse" required><br><br>

        <button type="submit" name="send_con">Se connecter</button>
    </form>

    <p><a href="sendmail.php">Mot de passe oublié ?</a></p>
    <p><a href="inscription.php">Créer un compte</a></p>
</body>

</html>