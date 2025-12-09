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
if (!$bdd) die("Erreur connexion BDD");

$user_id = i($_SESSION['Sid']);
$user = fetchOne("SELECT * FROM utilisateur WHERE num = $user_id");
$message = '';
$error = '';

if (isset($_POST['update_login'])) {
    $new_login = q(trim($_POST['new_login'] ?? ''));
    
    if (!$new_login) $error = "Le login ne peut pas être vide";
    elseif (strlen($new_login) < 3) $error = "Le login doit contenir au moins 3 caractères";
    elseif (fetchOne("SELECT * FROM utilisateur WHERE login = '$new_login' AND num != $user_id")) $error = "Ce login existe déjà";
    else {
        $stmt = $bdd->prepare("UPDATE utilisateur SET login = ? WHERE num = ?");
        $stmt->bind_param("si", $new_login, $user_id);
        if ($stmt->execute()) {
            $_SESSION['Slogin'] = $new_login;
            $user['login'] = $new_login;
            $message = "✅ Login mis à jour avec succès";
        } else $error = "Erreur lors de la mise à jour";
    }
}

if (isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($old_pass, $user['motdepasse'])) $error = "Ancien mot de passe incorrect";
    elseif (strlen($new_pass) < 8) $error = "Le nouveau mot de passe doit contenir au moins 8 caractères";
    elseif ($new_pass !== $confirm_pass) $error = "Les nouveaux mots de passe ne correspondent pas";
    else {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $bdd->prepare("UPDATE utilisateur SET motdepasse = ? WHERE num = ?");
        $stmt->bind_param("si", $hash, $user_id);
        if ($stmt->execute()) {
            $message = "✅ Mot de passe mis à jour avec succès";
            $user = fetchOne("SELECT * FROM utilisateur WHERE num = $user_id");
        } else $error = "Erreur lors de la mise à jour";
    }
}

if (isset($_POST['update_email'])) {
    $new_email = q(trim($_POST['new_email'] ?? ''));
    
    if (!validerEmail($new_email)) $error = "Email invalide";
    elseif (fetchOne("SELECT * FROM utilisateur WHERE email = '$new_email' AND num != $user_id")) $error = "Cet email existe déjà";
    elseif ($new_email === $user['email']) $error = "Cet email est identique à l'actuel";
    else {
        $code = sprintf("%06d", mt_rand(1, 999999));
        $stmt = $bdd->prepare("UPDATE utilisateur SET email = ?, code_verification = ? WHERE num = ?");
        $stmt->bind_param("ssi", $new_email, $code, $user_id);
        
        if ($stmt->execute()) {
            require __DIR__ . '/phpmailer/Exception.php';
            require __DIR__ . '/phpmailer/PHPMailer.php';
            require __DIR__ . '/phpmailer/SMTP.php';
            
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
                $mail->addAddress($new_email);
                $mail->isHTML(false);
                $mail->Subject = 'Vérification de votre nouvel email';
                $mail->Body = "Bonjour " . $user['prenom'] . ",\n\nVotre code de vérification est : $code\n\nVeuillez entrer ce code pour activer votre nouvel email.\n\nCordialement,\nL'équipe du site";
                $mail->send();
                
                $_SESSION['user_id_verification'] = $user_id;
                $_SESSION['email_verification'] = $new_email;
                header("Location: verifier_email.php");
                exit();
            } catch (Exception $e) {
                $error = "Erreur envoi email";
            }
        } else $error = "Erreur lors de la mise à jour";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="common.css">
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <?php afficherHeaderPage('⚙️', 'Mon Profil', 'Gérez vos informations personnelles et paramètres'); ?>

    <div class="container">
        <?php if ($message): ?>
            <div class="message-box message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message-box message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="profile-section">
            <h3>👤 Informations personnelles</h3>
            <div class="profile-grid-2">
                <div class="field-display">
                    <label>Nom:</label>
                    <span><?php echo htmlspecialchars($user['nom']); ?></span>
                </div>
                <div class="field-display">
                    <label>Prénom:</label>
                    <span><?php echo htmlspecialchars($user['prenom']); ?></span>
                </div>
            </div>
        </div>

        <div class="profile-grid-2">
            <div class="profile-section">
                <h3>✏️ Modifier mon login</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Login actuel:</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['login']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Nouveau login:</label>
                        <input type="text" name="new_login" placeholder="Minimum 3 caractères" required>
                    </div>
                    <button type="submit" name="update_login" class="btn-update">✏️ Mettre à jour</button>
                </form>
            </div>

            <div class="profile-section">
                <h3>✉️ Modifier mon email</h3>
                <div class="field-group" style="margin-bottom: 15px; display: block; border: none; padding-bottom: 0;">
                    <label class="field-label" style="flex: none; display: block; margin-bottom: 8px;">Email actuel:</label>
                    <div style="padding: 10px; background: #f5f5f5; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span class="field-value" style="flex: 1; text-align: left;"><?php echo htmlspecialchars($user['email']); ?></span>
                        <?php if ($user['verified']): ?>
                            <span style="color: #28a745; margin-left: 10px; white-space: nowrap;">✅ Vérifié</span>
                        <?php else: ?>
                            <span style="color: #dc3545; margin-left: 10px; white-space: nowrap;">❌ Non vérifié</span>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Nouvel email:</label>
                        <input type="email" name="new_email" placeholder="exemple@email.com" required>
                    </div>
                    <button type="submit" name="update_email" class="btn-update">✏️ Mettre à jour</button>
                    <p style="font-size: 0.85em; color: #999; margin-top: 8px; font-style: italic;">Vous recevrez un email de confirmation</p>
                </form>
            </div>
        </div>

        <div class="profile-section full-width">
            <h3>🔐 Changer mon mot de passe</h3>
            <form method="POST">
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Ancien mot de passe:</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label>Nouveau mot de passe:</label>
                        <input type="password" name="new_password" placeholder="Minimum 8 caractères" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmer le nouveau mot de passe:</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" name="update_password" class="btn-update btn-security">🔐 Changer le mot de passe</button>
            </form>
        </div>

        <div class="link-group">
            <a href="accueil.php" class="retour-btn">← Retour à l'accueil</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
