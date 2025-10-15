<?php
session_start();
include '_conf.php';

// Inclure PHPMailer
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et échappement des données du formulaire
    $nom = mysqli_real_escape_string($bdd, $_POST['nom']);
    $prenom = mysqli_real_escape_string($bdd, $_POST['prenom']);
    $login = mysqli_real_escape_string($bdd, $_POST['login']);
    $email = mysqli_real_escape_string($bdd, $_POST['email']);
    
    $motdepasse = md5($_POST['motdepasse']);
    $confirmation = md5($_POST['confirmation']);
    $type = intval($_POST['type']); // 0 = Étudiant, 1 = Professeur
    
    // Vérification que les mots de passe correspondent
    if ($motdepasse !== $confirmation) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        // Vérification de l'unicité du login
        $check_query = "SELECT * FROM utilisateur WHERE login = '$login'";
        $check_result = mysqli_query($bdd, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Ce login est déjà utilisé";
        } else {
            // Génération du code de vérification
            $code_verification = sprintf("%06d", mt_rand(1, 999999));
            
            // Insertion du nouvel utilisateur
            $insert_query = "INSERT INTO utilisateur (nom, prenom, login, email, motdepasse, type, code_verification) 
                            VALUES ('$nom', '$prenom', '$login', '$email', '$motdepasse', $type, '$code_verification')";
            
            if (mysqli_query($bdd, $insert_query)) {
                $user_id = mysqli_insert_id($bdd);
                
                // Envoi de l'email de vérification avec PHPMailer
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
                    $mail->Subject = 'Vérification de votre adresse email';
                    $mail->Body = "Bonjour $prenom,\n\n";
                    $mail->Body .= "Votre code de vérification est : $code_verification\n\n";
                    $mail->Body .= "Veuillez entrer ce code sur le site pour activer votre compte.\n\n";
                    $mail->Body .= "Cordialement,\nL'équipe du site";

                    $mail->send();
                    
                    $_SESSION['user_id_verification'] = $user_id;
                    $_SESSION['email_verification'] = $email;
                    header("Location: verifier_email.php");
                    exit();
                    
                } catch (Exception $e) {
                    $error = "Compte créé mais erreur lors de l'envoi de l'email de vérification: " . $mail->ErrorInfo;
                    // Supprimer l'utilisateur si l'email ne peut pas être envoyé
                    mysqli_query($bdd, "DELETE FROM utilisateur WHERE num = $user_id");
                }
            } else {
                $error = "Erreur lors de la création du compte: " . mysqli_error($bdd);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
</head>
<body>
    <h2>Créer un compte</h2>
    
    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
        <p><a href="index.php">Se connecter</a></p>
    <?php else: ?>
    
        <?php if ($error): ?>
            <p style="color:red"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="nom">Nom :</label><br>
            <input type="text" id="nom" name="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required><br><br>

            <label for="prenom">Prénom :</label><br>
            <input type="text" id="prenom" name="prenom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required><br><br>

            <label for="login">Login :</label><br>
            <input type="text" id="login" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required><br><br>

            <label for="email">Email :</label><br>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required><br><br>

            <label for="type">Type de compte :</label><br>
            <select id="type" name="type" required>
                <option value="0" <?php echo (isset($_POST['type']) && $_POST['type'] == '0') ? 'selected' : ''; ?>>Étudiant</option>
                <option value="1" <?php echo (isset($_POST['type']) && $_POST['type'] == '1') ? 'selected' : ''; ?>>Professeur</option>
            </select><br><br>

            <label for="motdepasse">Mot de passe :</label><br>
            <input type="password" id="motdepasse" name="motdepasse" required><br><br>

            <label for="confirmation">Confirmer le mot de passe :</label><br>
            <input type="password" id="confirmation" name="confirmation" required><br><br>

            <button type="submit">S'inscrire</button>
        </form>

        <p><a href="index.php">Retour à la connexion</a></p>
    
    <?php endif; ?>
</body>
</html>