<?php 
session_start();
include '_conf.php';
include 'fonctions.php';
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) die("Erreur BDD");

$erreur = null;
if (isset($_POST['send_con'])) {
    $login = trim($_POST['login'] ?? '');
    $mdp = trim($_POST['motdepasse'] ?? '');
    
    if (!$login || !$mdp) $erreur = "Remplissez tous les champs";
    else {
        $user = fetchOne("SELECT * FROM utilisateur WHERE login = '" . q($login) . "'");
        if ($user && (password_verify($mdp, $user['mdp']) || md5($mdp) === $user['mdp'])) {
            $_SESSION['Sid'] = $user['num'];
            $_SESSION['Snom'] = $user['nom'];
            $_SESSION['Sprenom'] = $user['prenom'];
            $_SESSION['Stype'] = $user['type'];
            $_SESSION['Semail'] = $user['email'];
            header("Location: accueil.php");
            exit;
        } else $erreur = "Identifiants invalides";
    }
} elseif (isset($_SESSION['Sid'])) {
    header("Location: accueil.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="container">
        <h2>🔐 Connexion</h2>
        <?php if ($erreur): ?>
            <div class="message error">❌ <?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="login">Login :</label>
                <input type="text" id="login" name="login" required>
            </div>
            <div class="form-group">
                <label for="motdepasse">Mot de passe :</label>
                <input type="password" id="motdepasse" name="motdepasse" required>
            </div>
            <button type="submit" name="send_con">🔓 Se connecter</button>
        </form>
        <div class="links">
            <p><a href="sendmail.php">🔑 Mot de passe oublié ?</a></p>
            <p style="margin-top: 15px;"><a href="inscription.php">👤 Créer un compte</a></p>
        </div>
        <div class="divider"><span>Pour tester</span></div>
        <div class="test-credentials">
            <strong>Login :</strong> <code>ethan.lalienne</code><br>
            <strong>Mot de passe :</strong> <code>password123</code>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($bdd); ?>
