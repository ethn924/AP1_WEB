<?php
session_start();
include '_conf.php';

// Inclure PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

if (!isset($_SESSION['user_id_verification']) || !isset($_SESSION['email_verification'])) {
    header("Location: inscription.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = $_SESSION['user_id_verification'];
$email = $_SESSION['email_verification'];
$message = '';
$error = '';

// Vérifier si un code existe déjà pour cet utilisateur
$query_code = "SELECT code_verification FROM utilisateur WHERE num = $user_id";
$result_code = mysqli_query($bdd, $query_code);
$user_data = mysqli_fetch_assoc($result_code);
$has_existing_code = !empty($user_data['code_verification']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['code'])) {
        $code_saisi = mysqli_real_escape_string($bdd, $_POST['code']);
        
        // Vérifier le code
        $query = "SELECT * FROM utilisateur WHERE num = $user_id AND code_verification = '$code_saisi'";
        $result = mysqli_query($bdd, $query);
        
        if (mysqli_num_rows($result) > 0) {
            // Code correct - activer le compte
            $update_query = "UPDATE utilisateur SET email_valide = 1, code_verification = NULL WHERE num = $user_id";
            if (mysqli_query($bdd, $update_query)) {
                session_destroy();
                $message = "Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de l'activation du compte";
            }
        } else {
            $error = "Code de vérification incorrect";
        }
    } elseif (isset($_POST['renvoyer'])) {
        // Regénérer un nouveau code
        $nouveau_code = sprintf("%06d", mt_rand(1, 999999));
        $update_code = "UPDATE utilisateur SET code_verification = '$nouveau_code' WHERE num = $user_id";
        
        if (mysqli_query($bdd, $update_code)) {
            // Renvoyer l'email avec PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'contact@sioslam.fr';  
                $mail->Password   = '&5&Y@*QHb';           
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('contact@sioslam.fr', 'CONTACT SIOSLAM');
                $mail->addAddress($email);
                $mail->isHTML(false);
                $mail->Subject = 'Code de vérification';
                $mail->Body = "Bonjour,\n\n";
                $mail->Body .= "Votre code de vérification est : $nouveau_code\n\n";
                $mail->Body .= "Veuillez entrer ce code sur le site pour activer votre compte.\n\n";
                $mail->Body .= "Cordialement,\nL'équipe du site";

                $mail->send();
                $message = "Code envoyé ! Vérifiez votre boîte email.";
                $has_existing_code = true;
                
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi du code: " . $mail->ErrorInfo;
            }
        } else {
            $error = "Erreur lors de la génération du code";
        }
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
        <?php if (strpos($message, 'succès') !== false): ?>
            <p><a href="index.php">Se connecter</a></p>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (!isset($message) || strpos($message, 'succès') === false): ?>
        
        <?php if ($has_existing_code): ?>
            <p>Un code de vérification a déjà été envoyé à <strong><?php echo htmlspecialchars($email); ?></strong></p>
            <p>Veuillez entrer le code reçu par email :</p>
        <?php else: ?>
            <p>Votre adresse email <strong><?php echo htmlspecialchars($email); ?></strong> n'est pas encore vérifiée.</p>
            <p>Cliquez sur le bouton ci-dessous pour recevoir un code de vérification :</p>
        <?php endif; ?>
        
        <?php if ($has_existing_code): ?>
            <form method="POST">
                <label for="code">Code de vérification :</label><br>
                <input type="text" id="code" name="code" maxlength="6" required><br><br>
                
                <button type="submit">Vérifier</button>
            </form>
        <?php endif; ?>
        
        <br>
        <form method="POST">
            <button type="submit" name="renvoyer">
                <?php echo $has_existing_code ? 'Renvoyer le code' : 'Recevoir un code de vérification'; ?>
            </button>
        </form>
        
        <p><a href="index.php">Retour à la connexion</a></p>
    <?php endif; ?>
</body>
</html>