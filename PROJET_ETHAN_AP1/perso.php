<?php
session_start();
include '_conf.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = $_SESSION['Sid'];
$query = "SELECT * FROM utilisateur WHERE num = $user_id";
$result = mysqli_query($bdd, $query);
$user = mysqli_fetch_assoc($result);

$message = '';
$error = '';

if (isset($_POST['update_password'])) {
    $old_password = md5($_POST['old_password']);
    $new_password = md5($_POST['new_password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    if ($old_password !== $user['motdepasse']) {
        $error = "Ancien mot de passe incorrect";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas";
    } else {
        $update_query = "UPDATE utilisateur SET motdepasse = '$new_password' WHERE num = $user_id";
        
        if (mysqli_query($bdd, $update_query)) {
            $message = "Mot de passe mis à jour avec succès";
        } else {
            $error = "Erreur lors de la mise à jour du mot de passe";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Informations personnelles</title>
</head>
<body>
    <h2>Informations personnelles</h2>
    
    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <p><strong>Nom:</strong> <?php echo $user['nom']; ?></p>
    <p><strong>Prénom:</strong> <?php echo $user['prenom']; ?></p>
    <p><strong>Login:</strong> <?php echo $user['login']; ?></p>
    <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
    
    <h3>Changer le mot de passe</h3>
    <form method="POST">
        <label>Ancien mot de passe :</label><br>
        <input type="password" name="old_password" required><br><br>
        
        <label>Nouveau mot de passe :</label><br>
        <input type="password" name="new_password" required><br><br>
        
        <label>Confirmer le nouveau mot de passe:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        
        <button type="submit" name="update_password">Changer le mot de passe</button>
    </form>
    
    <p><a href="accueil.php">Retour à l'accueil</a></p>
</body>
</html>