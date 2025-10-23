<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 0) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = intval($_SESSION['Sid']);
$message = '';
$error = '';

// Récupérer les informations actuelles de stage et tuteur
$query = "SELECT s.*, t.num as tuteur_num, t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.tel as tuteur_tel, t.email as tuteur_email 
          FROM utilisateur u 
          LEFT JOIN stage s ON u.num_stage = s.num 
          LEFT JOIN tuteur t ON s.num_tuteur = t.num 
          WHERE u.num = $user_id";
$result = mysqli_query($bdd, $query);
$current_data = mysqli_fetch_assoc($result);

// Initialiser les variables avec les données actuelles ou des valeurs vides
$stage_nom = $current_data['nom'] ?? '';
$stage_adresse = $current_data['adresse'] ?? '';
$stage_cp = $current_data['CP'] ?? '';
$stage_ville = $current_data['ville'] ?? '';
$stage_tel = $current_data['tel'] ?? '';
$stage_libelle = $current_data['libelleStage'] ?? '';
$stage_email = $current_data['email'] ?? '';
$tuteur_nom = $current_data['tuteur_nom'] ?? '';
$tuteur_prenom = $current_data['tuteur_prenom'] ?? '';
$tuteur_tel = $current_data['tuteur_tel'] ?? '';
$tuteur_email = $current_data['tuteur_email'] ?? '';

// Fonctions de validation
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^0[1-9][0-9]{8}$/', $clean_phone);
}

function validatePostalCode($cp)
{
    return preg_match('/^[0-9]{5}$/', $cp);
}

function validateName($name)
{
    return !empty(trim($name)) && preg_match('/^[a-zA-ZÀ-ÿ\s\-\.\']+$/', $name);
}

function validateAddress($address)
{
    return !empty(trim($address)) && strlen($address) >= 5;
}

function validateText($text)
{
    return !empty(trim($text)) && strlen($text) >= 10;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $stage_nom = trim($_POST['stage_nom']);
    $stage_adresse = trim($_POST['stage_adresse']);
    $stage_cp = trim($_POST['stage_cp']);
    $stage_ville = trim($_POST['stage_ville']);
    $stage_tel = trim($_POST['stage_tel']);
    $stage_libelle = trim($_POST['stage_libelle']);
    $stage_email = trim($_POST['stage_email']);
    $tuteur_nom = trim($_POST['tuteur_nom']);
    $tuteur_prenom = trim($_POST['tuteur_prenom']);
    $tuteur_tel = trim($_POST['tuteur_tel']);
    $tuteur_email = trim($_POST['tuteur_email']);

    // Validation des champs
    $errors = [];

    if (!validateName($stage_nom)) {
        $errors[] = "Le nom de l'entreprise est invalide";
    }
    if (!validateAddress($stage_adresse)) {
        $errors[] = "L'adresse doit contenir au moins 5 caractères";
    }
    if (!validatePostalCode($stage_cp)) {
        $errors[] = "Le code postal doit contenir exactement 5 chiffres";
    }
    if (!validateName($stage_ville)) {
        $errors[] = "Le nom de la ville est invalide";
    }
    if (!validatePhone($stage_tel)) {
        $errors[] = "Le téléphone de l'entreprise doit être un numéro français valide (10 chiffres)";
    }
    if (!validateEmail($stage_email)) {
        $errors[] = "L'email de l'entreprise est invalide";
    }
    if (!validateText($stage_libelle)) {
        $errors[] = "Le libellé du stage doit contenir au moins 10 caractères";
    }
    if (!validateName($tuteur_nom)) {
        $errors[] = "Le nom du tuteur est invalide";
    }
    if (!validateName($tuteur_prenom)) {
        $errors[] = "Le prénom du tuteur est invalide";
    }
    if (!validatePhone($tuteur_tel)) {
        $errors[] = "Le téléphone du tuteur doit être un numéro français valide (10 chiffres)";
    }
    if (!validateEmail($tuteur_email)) {
        $errors[] = "L'email du tuteur est invalide";
    }

    if (!empty($errors)) {
        $error = "Erreurs de validation :<br>" . implode("<br>", $errors);
    } else {
        // Échappement des données pour la BDD
        $stage_nom = mysqli_real_escape_string($bdd, $stage_nom);
        $stage_adresse = mysqli_real_escape_string($bdd, $stage_adresse);
        $stage_cp = mysqli_real_escape_string($bdd, $stage_cp);
        $stage_ville = mysqli_real_escape_string($bdd, $stage_ville);
        $stage_tel = mysqli_real_escape_string($bdd, $stage_tel);
        $stage_libelle = mysqli_real_escape_string($bdd, $stage_libelle);
        $stage_email = mysqli_real_escape_string($bdd, $stage_email);
        $tuteur_nom = mysqli_real_escape_string($bdd, $tuteur_nom);
        $tuteur_prenom = mysqli_real_escape_string($bdd, $tuteur_prenom);
        $tuteur_tel = mysqli_real_escape_string($bdd, $tuteur_tel);
        $tuteur_email = mysqli_real_escape_string($bdd, $tuteur_email);

        mysqli_begin_transaction($bdd);

        try {
            // Vérifier si l'utilisateur a déjà un stage ET un tuteur
            if ($current_data && isset($current_data['num']) && $current_data['num'] && isset($current_data['tuteur_num']) && $current_data['tuteur_num']) {
                // Mise à jour du tuteur existant
                $tuteur_num = intval($current_data['tuteur_num']);
                $update_tuteur = "UPDATE tuteur SET 
                                 nom = '$tuteur_nom', 
                                 prenom = '$tuteur_prenom', 
                                 tel = '$tuteur_tel', 
                                 email = '$tuteur_email' 
                                 WHERE num = $tuteur_num";

                // Mise à jour du stage existant
                $stage_num = intval($current_data['num']);
                $update_stage = "UPDATE stage SET 
                                nom = '$stage_nom', 
                                adresse = '$stage_adresse', 
                                CP = '$stage_cp', 
                                ville = '$stage_ville', 
                                tel = '$stage_tel', 
                                libelleStage = '$stage_libelle', 
                                email = '$stage_email' 
                                WHERE num = $stage_num";

                if (mysqli_query($bdd, $update_tuteur) && mysqli_query($bdd, $update_stage)) {
                    mysqli_commit($bdd);
                    $message = "Informations de stage mises à jour avec succès !";

                    // Recharger les données
                    $result = mysqli_query($bdd, $query);
                    $current_data = mysqli_fetch_assoc($result);
                    $stage_nom = $current_data['nom'] ?? '';
                    $stage_adresse = $current_data['adresse'] ?? '';
                    $stage_cp = $current_data['CP'] ?? '';
                    $stage_ville = $current_data['ville'] ?? '';
                    $stage_tel = $current_data['tel'] ?? '';
                    $stage_libelle = $current_data['libelleStage'] ?? '';
                    $stage_email = $current_data['email'] ?? '';
                    $tuteur_nom = $current_data['tuteur_nom'] ?? '';
                    $tuteur_prenom = $current_data['tuteur_prenom'] ?? '';
                    $tuteur_tel = $current_data['tuteur_tel'] ?? '';
                    $tuteur_email = $current_data['tuteur_email'] ?? '';
                } else {
                    throw new Exception("Erreur lors de la mise à jour");
                }
            } else {
                // Création d'un nouveau tuteur (avec AUTO_INCREMENT)
                $insert_tuteur = "INSERT INTO tuteur (nom, prenom, tel, email) 
                                 VALUES ('$tuteur_nom', '$tuteur_prenom', '$tuteur_tel', '$tuteur_email')";

                if (mysqli_query($bdd, $insert_tuteur)) {
                    $tuteur_id = mysqli_insert_id($bdd);

                    if ($current_data && isset($current_data['num']) && $current_data['num']) {
                        // Mise à jour du stage existant avec le nouveau tuteur
                        $update_stage = "UPDATE stage SET 
                                        nom = '$stage_nom', 
                                        adresse = '$stage_adresse', 
                                        CP = '$stage_cp', 
                                        ville = '$stage_ville', 
                                        tel = '$stage_tel', 
                                        libelleStage = '$stage_libelle', 
                                        email = '$stage_email',
                                        num_tuteur = $tuteur_id 
                                        WHERE num = " . $current_data['num'];

                        if (mysqli_query($bdd, $update_stage)) {
                            mysqli_commit($bdd);
                            $message = "Informations de stage mises à jour avec succès !";

                            // Recharger les données
                            $result = mysqli_query($bdd, $query);
                            $current_data = mysqli_fetch_assoc($result);
                            $stage_nom = $current_data['nom'] ?? '';
                            $stage_adresse = $current_data['adresse'] ?? '';
                            $stage_cp = $current_data['CP'] ?? '';
                            $stage_ville = $current_data['ville'] ?? '';
                            $stage_tel = $current_data['tel'] ?? '';
                            $stage_libelle = $current_data['libelleStage'] ?? '';
                            $stage_email = $current_data['email'] ?? '';
                            $tuteur_nom = $current_data['tuteur_nom'] ?? '';
                            $tuteur_prenom = $current_data['tuteur_prenom'] ?? '';
                            $tuteur_tel = $current_data['tuteur_tel'] ?? '';
                            $tuteur_email = $current_data['tuteur_email'] ?? '';
                        } else {
                            throw new Exception("Erreur lors de la mise à jour du stage");
                        }
                    } else {
                        // Création d'un nouveau stage (avec AUTO_INCREMENT)
                        $insert_stage = "INSERT INTO stage (nom, adresse, CP, ville, tel, libelleStage, email, num_tuteur) 
                                        VALUES ('$stage_nom', '$stage_adresse', '$stage_cp', '$stage_ville', '$stage_tel', '$stage_libelle', '$stage_email', $tuteur_id)";

                        if (mysqli_query($bdd, $insert_stage)) {
                            $stage_id = mysqli_insert_id($bdd);

                            // Liaison du stage à l'utilisateur
                            $update_user = "UPDATE utilisateur SET num_stage = $stage_id WHERE num = $user_id";

                            if (mysqli_query($bdd, $update_user)) {
                                mysqli_commit($bdd);
                                $message = "Informations de stage enregistrées avec succès !";

                                // Recharger les données
                                $result = mysqli_query($bdd, $query);
                                $current_data = mysqli_fetch_assoc($result);
                                $stage_nom = $current_data['nom'] ?? '';
                                $stage_adresse = $current_data['adresse'] ?? '';
                                $stage_cp = $current_data['CP'] ?? '';
                                $stage_ville = $current_data['ville'] ?? '';
                                $stage_tel = $current_data['tel'] ?? '';
                                $stage_libelle = $current_data['libelleStage'] ?? '';
                                $stage_email = $current_data['email'] ?? '';
                                $tuteur_nom = $current_data['tuteur_nom'] ?? '';
                                $tuteur_prenom = $current_data['tuteur_prenom'] ?? '';
                                $tuteur_tel = $current_data['tuteur_tel'] ?? '';
                                $tuteur_email = $current_data['tuteur_email'] ?? '';
                            } else {
                                throw new Exception("Erreur lors de la liaison du stage");
                            }
                        } else {
                            throw new Exception("Erreur lors de la création du stage");
                        }
                    }
                } else {
                    throw new Exception("Erreur lors de la création du tuteur");
                }
            }
        } catch (Exception $e) {
            mysqli_rollback($bdd);
            $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mes informations de stage</title>
    <script>
        function validateForm() {
            const stageTel = document.getElementById('stage_tel').value;
            const tuteurTel = document.getElementById('tuteur_tel').value;
            const stageCp = document.getElementById('stage_cp').value;
            const stageEmail = document.getElementById('stage_email').value;
            const tuteurEmail = document.getElementById('tuteur_email').value;

            const phoneRegex = /^0[1-9][0-9]{8}$/;
            const cleanStageTel = stageTel.replace(/[^0-9]/g, '');
            const cleanTuteurTel = tuteurTel.replace(/[^0-9]/g, '');

            if (!phoneRegex.test(cleanStageTel)) {
                alert('Le téléphone de l\'entreprise doit être un numéro français valide (10 chiffres commençant par 0)');
                return false;
            }

            if (!phoneRegex.test(cleanTuteurTel)) {
                alert('Le téléphone du tuteur doit être un numéro français valide (10 chiffres commençant par 0)');
                return false;
            }

            const cpRegex = /^[0-9]{5}$/;
            if (!cpRegex.test(stageCp)) {
                alert('Le code postal doit contenir exactement 5 chiffres');
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(stageEmail)) {
                alert('L\'email de l\'entreprise est invalide');
                return false;
            }

            if (!emailRegex.test(tuteurEmail)) {
                alert('L\'email du tuteur est invalide');
                return false;
            }

            return true;
        }

        function formatPhone(input) {
            let value = input.value.replace(/[^0-9]/g, '');

            if (value.length > 10) {
                value = value.substring(0, 10);
            }

            if (value.length > 0) {
                let formatted = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 2 === 0) {
                        formatted += ' ';
                    }
                    formatted += value[i];
                }
                value = formatted;
            }

            input.value = value;
        }

        function restrictPhoneInput(event) {
            const key = event.key;
            if (!/[\d]|Backspace|Delete|Tab|Escape|Enter/.test(key)) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        function limitPostalCode(input) {
            input.value = input.value.replace(/[^0-9]/g, '').substring(0, 5);
        }

        function restrictPostalCodeInput(event) {
            const key = event.key;
            if (!/[\d]|Backspace|Delete|Tab|Escape|Enter/.test(key)) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <h2>Mes informations de stage</h2>

    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <p><span style="color:red">*</span> Tous les champs sont obligatoires pour pouvoir créer des comptes rendus.</p>

    <form method="POST" onsubmit="return validateForm()">
        <!-- Informations du stage -->
        <div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd;">
            <h3>Informations de l'entreprise / du stage</h3>

            <label for="stage_nom">Nom de l'entreprise <span style="color:red">*</span></label><br>
            <input type="text" id="stage_nom" name="stage_nom" value="<?php echo htmlspecialchars($stage_nom); ?>"
                required pattern="[a-zA-ZÀ-ÿ0-9\s\-\.&',]{2,}"
                title="Le nom de l'entreprise doit contenir au moins 2 caractères (lettres, chiffres, espaces, tirets, points, apostrophes, esperluettes et virgules)"
                style="width: 300px;"><br><br>

            <label for="stage_adresse">Adresse <span style="color:red">*</span></label><br>
            <input type="text" id="stage_adresse" name="stage_adresse"
                value="<?php echo htmlspecialchars($stage_adresse); ?>" required minlength="5"
                title="L'adresse doit contenir au moins 5 caractères" style="width: 400px;"><br><br>

            <label for="stage_cp">Code postal <span style="color:red">*</span></label><br>
            <input type="text" id="stage_cp" name="stage_cp" value="<?php echo htmlspecialchars($stage_cp); ?>" required
                pattern="[0-9]{5}" title="Le code postal doit contenir exactement 5 chiffres"
                oninput="limitPostalCode(this)" onkeydown="return restrictPostalCodeInput(event)" maxlength="5"
                style="width: 100px;"><br><br>

            <label for="stage_ville">Ville <span style="color:red">*</span></label><br>
            <input type="text" id="stage_ville" name="stage_ville" value="<?php echo htmlspecialchars($stage_ville); ?>"
                required pattern="[a-zA-ZÀ-ÿ\s\-']{2,}"
                title="Le nom de la ville doit contenir au moins 2 caractères (lettres, espaces, tirets et apostrophes)"
                style="width: 200px;"><br><br>

            <label for="stage_tel">Téléphone de l'entreprise <span style="color:red">*</span></label><br>
            <input type="text" id="stage_tel" name="stage_tel" value="<?php echo htmlspecialchars($stage_tel); ?>"
                required pattern="0[1-9]([ .-]?[0-9]{2}){4}"
                title="Numéro de téléphone français valide (10 chiffres, ex: 01 23 45 67 89)"
                oninput="formatPhone(this)" onkeydown="return restrictPhoneInput(event)" maxlength="14"
                placeholder="01 23 45 67 89" style="width: 150px;"><br><br>

            <label for="stage_email">Email de l'entreprise <span style="color:red">*</span></label><br>
            <input type="email" id="stage_email" name="stage_email"
                value="<?php echo htmlspecialchars($stage_email); ?>" required
                pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                title="Email valide (ex: entreprise@domaine.fr)" style="width: 300px;"><br><br>

            <label for="stage_libelle">Libellé du stage / Mission <span style="color:red">*</span></label><br>
            <textarea id="stage_libelle" name="stage_libelle" required minlength="10"
                title="Le libellé du stage doit contenir au moins 10 caractères"
                style="width: 400px; height: 80px;"><?php echo htmlspecialchars($stage_libelle); ?></textarea>
        </div>

        <!-- Informations du tuteur -->
        <div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd;">
            <h3>Informations du tuteur en entreprise</h3>

            <label for="tuteur_nom">Nom du tuteur <span style="color:red">*</span></label><br>
            <input type="text" id="tuteur_nom" name="tuteur_nom" value="<?php echo htmlspecialchars($tuteur_nom); ?>"
                required pattern="[a-zA-ZÀ-ÿ\s\-']{2,}"
                title="Le nom doit contenir au moins 2 caractères (lettres, espaces, tirets et apostrophes)"
                style="width: 200px;"><br><br>

            <label for="tuteur_prenom">Prénom du tuteur <span style="color:red">*</span></label><br>
            <input type="text" id="tuteur_prenom" name="tuteur_prenom"
                value="<?php echo htmlspecialchars($tuteur_prenom); ?>" required pattern="[a-zA-ZÀ-ÿ\s\-']{2,}"
                title="Le prénom doit contenir au moins 2 caractères (lettres, espaces, tirets et apostrophes)"
                style="width: 200px;"><br><br>

            <label for="tuteur_tel">Téléphone du tuteur <span style="color:red">*</span></label><br>
            <input type="text" id="tuteur_tel" name="tuteur_tel" value="<?php echo htmlspecialchars($tuteur_tel); ?>"
                required pattern="0[1-9]([ .-]?[0-9]{2}){4}"
                title="Numéro de téléphone français valide (10 chiffres, ex: 01 23 45 67 89)"
                oninput="formatPhone(this)" onkeydown="return restrictPhoneInput(event)" maxlength="14"
                placeholder="01 23 45 67 89" style="width: 150px;"><br><br>

            <label for="tuteur_email">Email du tuteur <span style="color:red">*</span></label><br>
            <input type="email" id="tuteur_email" name="tuteur_email"
                value="<?php echo htmlspecialchars($tuteur_email); ?>" required
                pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Email valide (ex: tuteur@domaine.fr)"
                style="width: 300px;">
        </div>

        <button type="submit" style="padding: 10px 20px; font-size: 16px;">Enregistrer les informations</button>
    </form>
</body>

</html>