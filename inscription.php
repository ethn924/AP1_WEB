<?php
session_start();
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) die("Erreur connexion BDD");

$message = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = q($_POST['nom'] ?? '');
    $prenom = q($_POST['prenom'] ?? '');
    $login = q($_POST['login'] ?? '');
    $email = q($_POST['email'] ?? '');
    $motdepasse = md5($_POST['motdepasse'] ?? '');
    $confirmation = md5($_POST['confirmation'] ?? '');
    $type = i($_POST['type'] ?? 0);
    
    if (!$nom || !$prenom || !$login || !$email) $error = "Tous les champs sont requis";
    elseif ($motdepasse !== $confirmation) $error = "Les mots de passe ne correspondent pas";
    elseif (fetchOne("SELECT * FROM utilisateur WHERE login = '$login'")) $error = "Ce login existe déjà";
    else {
        if (execQuery("INSERT INTO utilisateur (nom, prenom, login, email, motdepasse, type) VALUES ('$nom', '$prenom', '$login', '$email', '$motdepasse', $type)")) {
            $uid = mysqli_insert_id($bdd);
            $_SESSION['user_id_verification'] = $uid;
            $_SESSION['email_verification'] = $email;
            header("Location: verifier_email.php");
            exit;
        } else $error = "Erreur création compte";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="container">
        <h2>👤 Créer un compte</h2>
        <?php if ($message): ?>
            <div class="message success">✅ <?php echo htmlspecialchars($message); ?></div>
            <div class="links"><p><a href="index.php">🔐 Se connecter</a></p></div>
        <?php else: ?>
            <?php if ($error): ?><div class="message error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom :</label>
                        <input type="text" id="nom" name="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom :</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="login">Login :</label>
                    <input type="text" id="login" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="type">Type :</label>
                    <select id="type" name="type" required>
                        <option value="" disabled <?php echo !isset($_POST['type']) ? 'selected' : ''; ?>>-- Sélectionner --</option>
                        <option value="0" <?php echo (isset($_POST['type']) && $_POST['type'] == '0') ? 'selected' : ''; ?>>👨‍🎓 Étudiant</option>
                        <option value="1" <?php echo (isset($_POST['type']) && $_POST['type'] == '1') ? 'selected' : ''; ?>>👨‍🏫 Professeur</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="motdepasse">Mot de passe :</label>
                        <input type="password" id="motdepasse" name="motdepasse" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmation">Confirmer :</label>
                        <input type="password" id="confirmation" name="confirmation" required>
                    </div>
                </div>
                <button type="submit">📝 S'inscrire</button>
            </form>
            <div class="links"><p><a href="index.php">🔐 Retour à la connexion</a></p></div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($bdd); ?>
