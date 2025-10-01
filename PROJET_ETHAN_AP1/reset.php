<?php
include '_conf.php';

// Connexion à la base de données
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Vérifier que le token est présent dans l'URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Lien invalide'); // Arrêt du script si le token est absent
}
$token = mysqli_real_escape_string($bdd, $_GET['token']);

// Rechercher l'utilisateur correspondant au token
$query = "SELECT num FROM utilisateur WHERE token = '$token'";
$result = mysqli_query($bdd, $query);

if (mysqli_num_rows($result) === 0) { // On vérifie que le token est valide et qu'il est bien attrribué à l'utilisateur concerné
    die('Token invalide'); // Aucun utilisateur trouvé avec ce token donc j'arrête le script ça sert à rien de continuer
}

$user = mysqli_fetch_assoc($result);

// Traitement du formulaire de réinitialisation
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['password_confirm'];
    
    // Validation des mots de passe
    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) { // Pour l'instant simplement 6 caractères minimum, je dois améliorer ça plus tard
        $error = "Le mot de passe doit faire au moins 6 caractères";
    } else {
        // Hachage MD5 du nouveau mot de passe avec la fonction md5() trouvée sur internet
        $md5_hash = md5($password);
        
        // Mise à jour du mot de passe et suppression du token
        $update_query = "UPDATE utilisateur SET motdepasse = '$md5_hash', token = '' WHERE num = " . $user['num']; // Je définis le nouveau mot de passe et supprime le token
        $update_result = mysqli_query($bdd, $update_query); // Exécution de la requête
        
        if ($update_result) {
            echo "Votre mot de passe a bien été réinitialisé.<br>";
            echo "<a href='index.php'>Se connecter</a>";
            exit; // Si tout est OK, le script est fini sinon message erreur
        } else {
            $error = "Erreur lors de la mise à jour: " . mysqli_error($bdd); 
        }
    }
}
?>

<!-- Formulaire de réinitialisation de mot de passe -->
<form method="post">
    <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
    <input type="password" name="password" placeholder="Nouveau mot de passe" required>
    <input type="password" name="password_confirm" placeholder="Confirmer" required>
    <button type="submit">Changer le mot de passe</button>
</form>