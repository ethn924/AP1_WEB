<?php
include '_conf.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

// Connexion à la base de données
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
$msg = '';

// Traitement du formulaire d'envoi d'email
if ($_POST && !empty($_POST['email'])) {
    $email = mysqli_real_escape_string($bdd, $_POST['email']);
    
    // Rechercher l'utilisateur par email
    $res = mysqli_query($bdd, "SELECT num, login FROM utilisateur WHERE email='$email'");
    
    if (mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
        
        // Génération d'un token sécurisé avec une fonction trouvée sur internet
        $token = bin2hex(random_bytes(32));
        
        // Enregistrement du token dans la base de données
        mysqli_query($bdd, "UPDATE utilisateur SET token='$token' WHERE num=".$user['num']);
        
        // Construction du lien de réinitialisation
        $lien = "https://www.sioslam.fr/2025LLALIE/PROJET_ETHAN_AP1/reset.php?token=$token"; // Ma page dédiée aux changements de mot de passe
        
        // Configuration et envoi de l'email avec PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@sioslam.fr';
            $mail->Password = '&5&Y@*QHb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataire et contenu de l'email (conversion des valeurs entrées en HTML)
            $mail->setFrom('contact@sioslam.fr', 'CONTACT SIOSLAM');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Oubli de mot de passe';
            $mail->Body = "Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href='$lien'>$lien</a>"; // Je trouve pas très sécurisé de donner le mdp dans l'email
            // Donc on demande le reset c'est mieux

            $mail->send(); // J'envoie le mail
            $msg = 'Email envoyé ! Vérifiez votre boîte mail.';
        } catch (Exception $e) {
            $msg = "Erreur d'envoi : " . $e->getMessage(); // Message d'erreur en cas d'échec
        }
    } else {
        $msg = 'Email non trouvé dans la base de données';
    }
}
?>

<!-- Formulaire de demande de réinitialisation -->
<form method="post">
    <input type="email" name="email" placeholder="Votre email" required>
    <button type="submit">Recevoir le lien de réinitialisation</button>
    <?php if ($msg) echo "<p>$msg</p>"; ?>
</form>