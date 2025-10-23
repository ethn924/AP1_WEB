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

        $update_query = "UPDATE utilisateur SET token='" . trim($token) . "', token_created_at=NOW() WHERE num=" . $user['num'];
        $update_result = mysqli_query($bdd, $update_query);

        if (!$update_result) {
            $msg = "Erreur lors de l'enregistrement du token";
        } else {
            $lien = "https://www.sioslam.fr/2025LLALIE/PROJET_ETHAN_AP1/reset.php?token=" . urlencode($token);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'contact@sioslam.fr';
                $mail->Password = '&5&Y@*QHb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('contact@sioslam.fr', 'CONTACT SIOSLAM');
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
    <title>Mot de passe oublié</title>
</head>

<body>
    <h2>Réinitialisation de mot de passe</h2>

    <form method="post">
        <label for="email">Votre email :</label><br>
        <input type="email" id="email" name="email" placeholder="Votre email" required><br><br>
        <button type="submit">Recevoir le lien de réinitialisation</button>
    </form>

    <?php if ($msg): ?>
        <p><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <p><a href="index.php">Retour à la connexion</a></p>
</body>

</html>