<?php
include '_conf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$msg = '';

if ($_POST && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    $email = mysqli_real_escape_string($bdd, $email);

    $res = mysqli_query($bdd, "SELECT num, login FROM utilisateur WHERE email='$email'");

    if (mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);

        $token = bin2hex(random_bytes(32));
        $token_escape = mysqli_real_escape_string($bdd, $token);

        $update_query = "UPDATE utilisateur SET token='$token_escape', token_created_at=NOW() WHERE num=" . intval($user['num']);
        $update_result = mysqli_query($bdd, $update_query);

        if (!$update_result) {
            $msg = "Erreur lors de l'enregistrement du token";
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $lien = "$protocol://$host/PROJET_IDRISS_AP1/reset.php?token=" . urlencode($token);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'contact@siolapie.com';
                $mail->Password = 'EmailL@pie25';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('contact@siolapie.com', 'CONTACT SIOLAPIE');
                $mail->addAddress($email);
                $mail->isHTML(false);
                $mail->Subject = 'Réinitialisation de mot de passe';
                $mail->Body = "Bonjour,\n\nVous avez demandé à réinitialiser votre mot de passe.\n\nCliquez sur ce lien pour réinitialiser votre mot de passe :\n$lien\n\nCe lien est valide pendant 1 heure.\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

                $mail->send();
                $msg = 'Email envoyé ! Vérifiez votre boîte mail.';
            } catch (Exception $e) {
                $msg = "Erreur d'envoi";
            }
        }
    } else {
        $msg = 'Email non trouvé dans la base de données';
    }
}

mysqli_close($bdd);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="auth.css">
</head>

<body>
    <div class="container">
        <h2>🔑 Réinitialisation de mot de passe</h2>

        <div class="description">
            Entrez votre adresse email associée à votre compte. Vous recevrez un lien pour réinitialiser votre mot de passe.
        </div>

        <form method="post">
            <div class="form-group">
                <label for="email">Votre email :</label>
                <input type="email" id="email" name="email" placeholder="exemple@email.com" required>
            </div>
            <button type="submit">📧 Recevoir le lien de réinitialisation</button>
        </form>

        <?php if ($msg): ?>
            <?php 
            $is_success = strpos($msg, 'envoyé') !== false || strpos($msg, 'succès') !== false;
            $is_error = strpos($msg, 'Erreur') !== false || strpos($msg, 'non trouvé') !== false;
            ?>
            <div class="message <?php echo $is_success ? 'success' : ($is_error ? 'error' : ''); ?>">
                <?php 
                if ($is_success) echo '✅ ';
                elseif ($is_error) echo '❌ ';
                echo htmlspecialchars($msg); 
                ?>
            </div>
        <?php endif; ?>

        <div class="links">
            <p><a href="index.php">🔐 Retour à la connexion</a></p>
            <p><a href="inscription.php">📝 Créer un compte</a></p>
        </div>
    </div>
</body>

</html>