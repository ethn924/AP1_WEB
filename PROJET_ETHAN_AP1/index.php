<?php
include '_conf.php'; // Fichier de configuration qui contient les variables de connexion.
?>

<?php
// Si la connexion est réussie :
if ($bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD)) {
    echo "Connexion à la base de données réussie.<br>";
}
// Si la connexion échoue :
else {
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
    <form action="traitement_connexion.php" method="POST">
        <label for="identifiant">Identifiant :</label><br>
        <input type="text" id="identifiant" name="identifiant" required><br><br>

        <label for="mdp">Mot de passe :</label><br>
        <input type="password" id="mdp" name="mdp" required><br><br>

        <button type="submit">Se connecter</button>
    </form>

    <p><a href="sendmail.php">Mot de passe oublié ?</a></p>
</body>
</html>
