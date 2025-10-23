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

// Envoi du code de vérification
if (isset($_POST['envoyer_code'])) {
    $code_verification = sprintf("%06d", mt_rand(1, 999999));

    $update_query = "UPDATE utilisateur SET code_verification = '$code_verification' WHERE num = $user_id";

    if (mysqli_query($bdd, $update_query)) {
        // Envoi de l'email de vérification avec PHPMailer
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
            $mail->Subject = 'Vérification de votre adresse email';
            $mail->Body = "Bonjour,\n\n";
            $mail->Body .= "Votre code de vérification est : $code_verification\n\n";
            $mail->Body .= "Veuillez entrer ce code sur le site pour activer votre compte.\n\n";
            $mail->Body .= "Cordialement,\nL'équipe du site";

            $mail->send();
            $message = "Code de vérification envoyé à $email";

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
            unset($_SESSION['user_id_verification']);
            unset($_SESSION['email_verification']);

            // Si l'utilisateur n'était pas encore connecté, le connecter
            if (!isset($_SESSION['Sid'])) {
                $query = "SELECT * FROM utilisateur WHERE num = $user_id";
                $result = mysqli_query($bdd, $query);
                $user_data = mysqli_fetch_assoc($result);

                $_SESSION['Sid'] = $user_data['num'];
                $_SESSION['Slogin'] = $user_data['login'];
                $_SESSION['Sprenom'] = $user_data['prenom'];
                $_SESSION['Snom'] = $user_data['nom'];
                $_SESSION['Stype'] = $user_data['type'];
            }

            header("Location: accueil.php");
            exit();
        } else {
            $error = "Erreur lors de la validation: " . mysqli_error($bdd);
            logger("Erreur validation email: " . mysqli_error($bdd), $user_id, 'valider_email.php');
        }
    } else {
        $error = "Code de vérification incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Vérification d'email</title>
</head>

<body>
    <h2>Vérification de votre adresse email</h2>

    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <p>Veuillez vérifier votre adresse email <strong><?php echo htmlspecialchars($email); ?></strong> pour activer votre
        compte.</p>

    <?php if (!isset($_POST['envoyer_code']) && !isset($_SESSION['code_envoye'])): ?>
        <form method="POST">
            <p>Un code de vérification va vous être envoyé par email.</p>
            <button type="submit" name="envoyer_code">Recevoir le code de vérification</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <label for="code_verification">Code de vérification :</label><br>
            <input type="text" id="code_verification" name="code_verification" required maxlength="6"
                pattern="[0-9]{6}"><br><br>

            <button type="submit" name="verifier_code">Vérifier le code</button>
        </form>

        <p><a href="?renvoyer=1">Renvoyer le code</a></p>
    <?php endif; ?>

    <p><a href="deconnexion.php">Annuler et se déconnecter</a></p>
</body>

</html>