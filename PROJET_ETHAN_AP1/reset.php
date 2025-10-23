<?php
include '_conf.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Lien invalide');
}

$token = trim($_GET['token']);
$token = mysqli_real_escape_string($bdd, $token);

$query = "SELECT num, token_created_at FROM utilisateur WHERE token = '$token' AND token != ''";
$result = mysqli_query($bdd, $query);

if (mysqli_num_rows($result) === 0) {
    die('Token invalide');
}

$user = mysqli_fetch_assoc($result);

if ($user['token_created_at']) {
    $token_time = strtotime($user['token_created_at']);
    $current_time = time();
    $time_diff = $current_time - $token_time;

    if ($time_diff > 3600) {
        mysqli_query($bdd, "UPDATE utilisateur SET token = '', token_created_at = NULL WHERE num = " . $user['num']);
        die('Ce lien a expiré. Veuillez faire une nouvelle demande de réinitialisation.');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['password_confirm'];

    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        $md5_hash = md5($password);
        $md5_hash_escape = mysqli_real_escape_string($bdd, $md5_hash);

        $update_query = "UPDATE utilisateur SET motdepasse = '$md5_hash_escape', token = '', token_created_at = NULL WHERE num = " . intval($user['num']);
        $update_result = mysqli_query($bdd, $update_query);

        if ($update_result) {
            echo "Votre mot de passe a bien été réinitialisé.<br>";
            echo "<a href='index.php'>Se connecter</a>";
            exit;
        } else {
            $error = "Erreur lors de la mise à jour";
        }
    }
}
?>

<form method="post">
    <?php if (!empty($error))
        echo "<p style='color:red'>$error</p>"; ?>
    <input type="password" name="password" placeholder="Nouveau mot de passe" required>
    <input type="password" name="password_confirm" placeholder="Confirmer" required>
    <button type="submit">Changer le mot de passe</button>
</form>