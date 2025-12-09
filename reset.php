<?php
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) die("Erreur connexion BDD");

if (!isset($_GET['token']) || empty($_GET['token'])) die('Lien invalide');

$token = trim($_GET['token']);
$stmt = $bdd->prepare("SELECT num, token_created_at FROM utilisateur WHERE token = ? AND token != ''");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die('Token invalide');

$user = $result->fetch_assoc();
$error = '';
$success_message = '';

if ($user['token_created_at']) {
    $token_time = strtotime($user['token_created_at']);
    if (time() - $token_time > 3600) {
        $stmt2 = $bdd->prepare("UPDATE utilisateur SET token = '', token_created_at = NULL WHERE num = ?");
        $stmt2->bind_param("i", $user['num']);
        $stmt2->execute();
        die('Ce lien a expiré. Veuillez faire une nouvelle demande de réinitialisation.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    
    if (strlen($password) < 8) $error = "Le mot de passe doit contenir au moins 8 caractères";
    elseif ($password !== $confirm) $error = "Les mots de passe ne correspondent pas";
    else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt3 = $bdd->prepare("UPDATE utilisateur SET motdepasse = ?, token = '', token_created_at = NULL WHERE num = ?");
        $stmt3->bind_param("si", $hash, $user['num']);
        
        if ($stmt3->execute()) $success_message = "✅ Votre mot de passe a bien été réinitialisé. Vous pouvez maintenant vous connecter.";
        else $error = "Erreur lors de la mise à jour";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <link rel="stylesheet" href="auth.css">
</head>

<body>
    <div class="container">
        <h2>🔑 Réinitialiser le mot de passe</h2>

        <div class="description">
            Entrez votre nouveau mot de passe. Assurez-vous qu'il est suffisamment sécurisé.
        </div>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
            <div class="links">
                <p><a href="index.php">🔐 Se connecter</a></p>
            </div>
        <?php else: ?>
            <form method="post">
                <?php if (!empty($error)) echo "<div class='message error'>❌ " . htmlspecialchars($error) . "</div>"; ?>
                
                <div class="form-group">
                    <label for="password">Nouveau mot de passe :</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre nouveau mot de passe" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe :</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmez votre mot de passe" required>
                </div>

                <button type="submit">🔐 Changer le mot de passe</button>
            </form>

            <div class="links">
                <p><a href="index.php">🔐 Retour à la connexion</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>