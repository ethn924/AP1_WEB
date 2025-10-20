<?php
session_start();
include '_conf.php';
include 'fonctions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = $_SESSION['Sid'];
$query = "SELECT * FROM utilisateur WHERE num = $user_id";
$result = mysqli_query($bdd, $query);
$user = mysqli_fetch_assoc($result);

$message = '';
$error = '';

if (isset($_POST['update_password'])) {
    $old_password = md5($_POST['old_password']);
    $new_password = md5($_POST['new_password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    if ($old_password !== $user['motdepasse']) {
        $error = "Ancien mot de passe incorrect";
        logger("Tentative changement mot de passe échouée - ancien mot de passe incorrect", $user_id, 'perso.php');
    } elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas";
        logger("Tentative changement mot de passe échouée - mots de passe différents", $user_id, 'perso.php');
    } else {
        $update_query = "UPDATE utilisateur SET motdepasse = '$new_password' WHERE num = $user_id";
        
        if (mysqli_query($bdd, $update_query)) {
            $message = "Mot de passe mis à jour avec succès";
            logger("Mot de passe changé avec succès", $user_id, 'perso.php');
        } else {
            $error = "Erreur lors de la mise à jour du mot de passe";
            logger("Erreur changement mot de passe: " . mysqli_error($bdd), $user_id, 'perso.php');
        }
    }
}

if (isset($_POST['renvoyer_validation'])) {
    // Régénérer le code de vérification
    $code_verification = sprintf("%06d", mt_rand(1, 999999));
    
    $update_query = "UPDATE utilisateur SET code_verification = '$code_verification', email_valide = 0 WHERE num = $user_id";
    
    if (mysqli_query($bdd, $update_query)) {
        // Envoi de l'email de vérification
        require __DIR__ . '/phpmailer/Exception.php';
        require __DIR__ . '/phpmailer/PHPMailer.php';
        require __DIR__ . '/phpmailer/SMTP.php';

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
            $mail->addAddress($user['email']);
            $mail->isHTML(false);
            $mail->Subject = 'Vérification de votre adresse email';
            $mail->Body = "Bonjour " . $user['prenom'] . ",\n\n";
            $mail->Body .= "Votre code de vérification est : $code_verification\n\n";
            $mail->Body .= "Veuillez entrer ce code sur le site pour activer votre compte.\n\n";
            $mail->Body .= "Cordialement,\nL'équipe du site";

            $mail->send();
            
            $_SESSION['user_id_verification'] = $user_id;
            $_SESSION['email_verification'] = $user['email'];
            header("Location: verifier_email.php");
            exit();
            
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
            logger("Erreur renvoi validation email: " . $mail->ErrorInfo, $user_id, 'perso.php');
        }
    } else {
        $error = "Erreur lors de la génération du code: " . mysqli_error($bdd);
        logger("Erreur génération code validation: " . mysqli_error($bdd), $user_id, 'perso.php');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Informations personnelles</title>
    <style>
        .email-status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .email-valide {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .email-non-valide {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h2>Informations personnelles</h2>
    
    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <p><strong>Nom:</strong> <?php echo $user['nom']; ?></p>
    <p><strong>Prénom:</strong> <?php echo $user['prenom']; ?></p>
    <p><strong>Login:</strong> <?php echo $user['login']; ?></p>
    <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
    
    <div class="email-status <?php echo $user['email_valide'] ? 'email-valide' : 'email-non-valide'; ?>">
        <strong>Statut de l'email :</strong>
        <?php if ($user['email_valide']): ?>
            ✅ Email vérifié
        <?php else: ?>
            ❌ Email non vérifié
            <form method="POST" style="display: inline;">
                <button type="submit" name="renvoyer_validation">Renvoyer l'email de validation</button>
            </form>
        <?php endif; ?>
    </div>
    
    <h3>Changer le mot de passe</h3>
    <form method="POST">
        <label>Ancien mot de passe :</label><br>
        <input type="password" name="old_password" required><br><br>
        
        <label>Nouveau mot de passe :</label><br>
        <input type="password" name="new_password" required><br><br>
        
        <label>Confirmer le nouveau mot de passe:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        
        <button type="submit" name="update_password">Changer le mot de passe</button>
    </form>
    
    <p><a href="accueil.php">Retour à l'accueil</a></p>
</body>
</html>