<?php
session_start();
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

$message = '';
$error = '';
$verification_complete = false;

// Vérification initiale
if (isset($_SESSION['user_id_verification']) && isset($_SESSION['email_verification'])) {
    $user_id = $_SESSION['user_id_verification'];
    $email = $_SESSION['email_verification'];
} elseif (isset($_SESSION['Sid'])) {
    // L'utilisateur est connecté mais veut revalider son email
    $user_id = $_SESSION['Sid'];
    $query = "SELECT email, email_valide FROM utilisateur WHERE num = $user_id";
    $result = mysqli_query($bdd, $query);
    $user = mysqli_fetch_assoc($result);
    $email = $user['email'];

    if ($user['email_valide'] == 1) {
        header("Location: accueil.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}

// Envoi du code de vérification (initial ou renvoi)
if (isset($_POST['envoyer_code']) || isset($_GET['renvoyer'])) {
    $code_verification = sprintf("%06d", mt_rand(1, 999999));
    $code_verification_escape = mysqli_real_escape_string($bdd, $code_verification);

    $update_query = "UPDATE utilisateur SET code_verification = '$code_verification_escape' WHERE num = $user_id";

    if (mysqli_query($bdd, $update_query)) {
        // Envoi de l'email de vérification avec PHPMailer
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
            $mail->Subject = 'Vérification de votre adresse email';
            $mail->Body = "Bonjour,\n\n";
            $mail->Body .= "Votre code de vérification est : $code_verification\n\n";
            $mail->Body .= "Veuillez entrer ce code sur le site pour activer votre compte.\n\n";
            $mail->Body .= "Cordialement,\nL'équipe du site";

            $mail->send();
            $_SESSION['code_sent'] = true;
            header("Location: verifier_email.php");
            exit();

        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
            logger("Erreur envoi email vérification: " . $mail->ErrorInfo, $user_id, 'valider_email.php');
        }
    } else {
        $error = "Erreur lors de la génération du code: " . mysqli_error($bdd);
        logger("Erreur génération code vérification: " . mysqli_error($bdd), $user_id, 'valider_email.php');
    }
}

// Vérification du code
if (isset($_POST['verifier_code'])) {
    $code_saisi = mysqli_real_escape_string($bdd, $_POST['code_verification']);

    $query = "SELECT code_verification FROM utilisateur WHERE num = $user_id";
    $result = mysqli_query($bdd, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user['code_verification'] === $code_saisi) {
        // Code correct - valider l'email
        $update_query = "UPDATE utilisateur SET email_valide = 1, code_verification = NULL WHERE num = $user_id";

        if (mysqli_query($bdd, $update_query)) {
            // Marquer la session pour afficher la confirmation
            $_SESSION['email_verified'] = true;
            $_SESSION['verified_user_id'] = $user_id;
            $verification_complete = true;
            $message = "✅ Email vérifié avec succès!";
        } else {
            $error = "Erreur lors de la validation: " . mysqli_error($bdd);
            logger("Erreur validation email: " . mysqli_error($bdd), $user_id, 'valider_email.php');
        }
    } else {
        $error = "Code de vérification incorrect";
    }
}

// Connexion automatique après confirmation
if (isset($_POST['connect_yes'])) {
    if (isset($_SESSION['verified_user_id'])) {
        $user_id = $_SESSION['verified_user_id'];
        $query = "SELECT * FROM utilisateur WHERE num = $user_id";
        $result = mysqli_query($bdd, $query);
        $user_data = mysqli_fetch_assoc($result);

        $_SESSION['Sid'] = $user_data['num'];
        $_SESSION['Slogin'] = $user_data['login'];
        $_SESSION['Sprenom'] = $user_data['prenom'];
        $_SESSION['Snom'] = $user_data['nom'];
        $_SESSION['Stype'] = $user_data['type'];

        unset($_SESSION['user_id_verification']);
        unset($_SESSION['email_verification']);
        unset($_SESSION['email_verified']);
        unset($_SESSION['verified_user_id']);
        unset($_SESSION['code_sent']);

        header("Location: accueil.php");
        exit();
    }
}

// Redirection vers connexion
if (isset($_POST['connect_no'])) {
    unset($_SESSION['user_id_verification']);
    unset($_SESSION['email_verification']);
    unset($_SESSION['email_verified']);
    unset($_SESSION['verified_user_id']);
    unset($_SESSION['code_sent']);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification d'email</title>
    <link rel="stylesheet" href="auth.css">
</head>

<body>
    <div class="container">
        <h2>🔐 Vérification d'email</h2>

        <?php if ($verification_complete || isset($_SESSION['email_verified'])): ?>
            <!-- Écran de confirmation après vérification -->
            <div class="message success">✅ Email vérifié avec succès!</div>
            
            <p class="info-text" style="font-size: 1.1em; margin: 30px 0;">
                Votre adresse email <strong><?php echo htmlspecialchars($email); ?></strong> a été vérifiée.
            </p>

            <p class="info-text" style="margin: 30px 0;">
                Souhaitez-vous vous connecter maintenant?
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 30px 0;">
                <form method="POST" style="display: contents;">
                    <button type="submit" name="connect_yes" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        ✅ Se connecter
                    </button>
                </form>
                <form method="POST" style="display: contents;">
                    <button type="submit" name="connect_no" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                        🔙 Retour à la connexion
                    </button>
                </form>
            </div>

        <?php else: ?>
            <!-- Écran de vérification normal -->
            <?php if ($message): ?>
                <div class="message success">✅ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <p class="info-text">
                Veuillez vérifier votre adresse email <strong><?php echo htmlspecialchars($email); ?></strong> pour activer votre compte.
            </p>

            <?php if (!isset($_SESSION['code_sent'])): ?>
                <div class="description">
                    Un code de vérification va vous être envoyé par email. Vous devrez le saisir pour valider votre compte.
                </div>
                <form method="POST">
                    <button type="submit" name="envoyer_code">📧 Recevoir le code de vérification</button>
                </form>
            <?php else: ?>
                <div class="description">
                    Entrez le code de vérification que vous avez reçu par email. Ce code contient 6 chiffres.
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="code_verification">Code de vérification (6 chiffres) :</label>
                        <input type="text" id="code_verification" name="code_verification" required maxlength="6"
                            pattern="[0-9]{6}" placeholder="000000" inputmode="numeric">
                    </div>
                    <button type="submit" name="verifier_code">✅ Vérifier le code</button>
                </form>

                <div class="links">
                    <p><a href="?renvoyer=1">🔄 Renvoyer le code</a></p>
                </div>
            <?php endif; ?>

            <div class="separator">—</div>

            <div class="links">
                <p><a href="deconnexion.php">🚪 Annuler et se déconnecter</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>