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

// Fonction pour formater la date en français avec majuscules (sans heure)
function formatDateFrench($date) {
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
    
    $date_str = date('l d F Y', strtotime($date));
    $date_str = str_replace($english_days, $french_days, $date_str);
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}

// Fonction pour formater la date et l'heure en français (pour les datetime)
function formatDateTimeFrench($datetime) {
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
    
    $date_str = date('l d F Y à H\hi', strtotime($datetime));
    $date_str = str_replace($english_days, $french_days, $date_str);
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}

$user_id = $_SESSION['Sid'];
$message = '';
$error = '';
$show_cr_form = false;
$show_cr_list = false;

// Vérifier si l'utilisateur a un stage et un tuteur associé
$stage_query = "SELECT s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.tel as tuteur_tel, t.email as tuteur_email 
                FROM utilisateur u 
                LEFT JOIN stage s ON u.num_stage = s.num 
                LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                WHERE u.num = $user_id";
$stage_result = mysqli_query($bdd, $stage_query);
$stage_info = mysqli_fetch_assoc($stage_result);

// Vérifier si tous les champs obligatoires sont remplis
if ($stage_info && 
    !empty($stage_info['nom']) && 
    !empty($stage_info['adresse']) && 
    !empty($stage_info['CP']) && 
    !empty($stage_info['ville']) && 
    !empty($stage_info['tel']) && 
    !empty($stage_info['libelleStage']) && 
    !empty($stage_info['email']) && 
    !empty($stage_info['tuteur_nom']) && 
    !empty($stage_info['tuteur_prenom']) && 
    !empty($stage_info['tuteur_tel']) && 
    !empty($stage_info['tuteur_email'])) {
    $show_cr_form = true;
}

$date_cr = date('Y-m-d');
$description = '';

// Chargement des CR existants pour la date sélectionnée
if (isset($_POST['show_cr'])) {
    $date_cr = $_POST['date_cr'];
    $show_cr_list = true;
}

// Masquer les CR
if (isset($_POST['hide_cr'])) {
    $date_cr = $_POST['date_cr'];
    $show_cr_list = false;
}

// Création d'un nouveau CR (uniquement si les infos stage/tuteur sont complètes)
if (isset($_POST['insérer']) && $show_cr_form) {
    $date_cr = $_POST['date_cr'];
    $description = mysqli_real_escape_string($bdd, $_POST['description']);

    $insert_query = "INSERT INTO cr (date, description, vu, datetime, num_utilisateur) 
                    VALUES ('$date_cr', '$description', 0, NOW(), $user_id)";
    
    if (mysqli_query($bdd, $insert_query)) {
        $cr_id = mysqli_insert_id($bdd);
        $message = "Nouveau compte rendu créé avec succès !";
        $description = '';
        
        // Gestion des pièces jointes
        if (!empty($_FILES['pieces_jointes']['name'][0])) {
            $upload_errors = [];
            foreach ($_FILES['pieces_jointes']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['pieces_jointes']['error'][$key] === UPLOAD_ERR_OK) {
                    try {
                        $fichier = [
                            'name' => $_FILES['pieces_jointes']['name'][$key],
                            'type' => $_FILES['pieces_jointes']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['pieces_jointes']['error'][$key],
                            'size' => $_FILES['pieces_jointes']['size'][$key]
                        ];
                        sauvegarderFichier($fichier, $cr_id);
                    } catch (Exception $e) {
                        $upload_errors[] = $fichier['name'] . ': ' . $e->getMessage();
                    }
                }
            }
            if (!empty($upload_errors)) {
                $message .= " Mais certaines pièces jointes n'ont pas pu être uploadées: " . implode(', ', $upload_errors);
            }
        }
        
        // Après insertion, on affiche les CR de cette date
        $show_cr_list = true;
    } else {
        $error = "Erreur lors de la création : " . mysqli_error($bdd);
        logger("Erreur création CR: " . mysqli_error($bdd), $user_id, 'editer_cr.php');
    }
}

// Récupération de tous les CR de l'utilisateur pour la date sélectionnée (seulement si on doit les afficher)
$liste_cr_result = null;
if ($show_cr_list) {
    $liste_cr_query = "SELECT * FROM cr WHERE num_utilisateur = $user_id AND date = '$date_cr' ORDER BY datetime DESC";
    $liste_cr_result = mysqli_query($bdd, $liste_cr_query);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte rendu</title>
    <style>
        .error-message {
            background-color: #ffe6e6;
            border: 1px solid #ff9999;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .stage-info {
            background-color: #e6f7ff;
            border: 1px solid #99ccff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .cr-list {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .button-group {
            margin: 10px 0;
        }
        .button-group button {
            margin-right: 10px;
            padding: 8px 15px;
            cursor: pointer;
        }
        .piece-jointe {
            background: #f0f0f0;
            padding: 5px 10px;
            margin: 5px 0;
            border-radius: 3px;
            display: inline-block;
        }
        .commentaires {
            background: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #007bff;
        }
        .commentaire {
            background: white;
            padding: 8px;
            margin: 5px 0;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h2>Créer un compte rendu</h2>

    <?php if ($message): ?>
        <p style="color:green"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (!$show_cr_form): ?>
        <div class="error-message">
            <h3>❌ Informations manquantes</h3>
            <p>Avant de pouvoir créer un compte rendu, vous devez compléter vos informations de stage et de tuteur.</p>
            <p>Veuillez remplir tous les champs suivants :</p>
            <ul>
                <?php if (!$stage_info || empty($stage_info['nom'])) echo "<li>Nom de l'entreprise</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['adresse'])) echo "<li>Adresse du stage</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['CP'])) echo "<li>Code postal</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['ville'])) echo "<li>Ville</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['tel'])) echo "<li>Téléphone de l'entreprise</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['libelleStage'])) echo "<li>Libellé du stage</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['email'])) echo "<li>Email de l'entreprise</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['tuteur_nom'])) echo "<li>Nom du tuteur</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['tuteur_prenom'])) echo "<li>Prénom du tuteur</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['tuteur_tel'])) echo "<li>Téléphone du tuteur</li>"; ?>
                <?php if (!$stage_info || empty($stage_info['tuteur_email'])) echo "<li>Email du tuteur</li>"; ?>
            </ul>
            <p><a href="mon_stage.php" style="font-weight: bold; color: #0066cc;">➡ Remplir mes informations de stage</a></p>
        </div>
    <?php else: ?>
        <div class="stage-info">
            <h3>✅ Vos informations de stage sont complètes</h3>
            <p><strong>Entreprise :</strong> <?php echo htmlspecialchars($stage_info['nom']); ?></p>
            <p><strong>Tuteur :</strong> <?php echo htmlspecialchars($stage_info['tuteur_prenom'] . ' ' . $stage_info['tuteur_nom']); ?></p>
            <p><a href="mon_stage.php">Modifier mes informations de stage</a></p>
        </div>

        <!-- Formulaire pour sélectionner une date -->
        <form method="POST">
            <label for="date_cr">Date :</label><br>
            <input type="date" id="date_cr" name="date_cr" value="<?php echo $date_cr; ?>" required>
            
            <div class="button-group">
                <?php if (!$show_cr_list): ?>
                    <button type="submit" name="show_cr">Voir les comptes rendus de ce jour</button>
                <?php else: ?>
                    <button type="submit" name="hide_cr">Masquer les comptes rendus</button>
                <?php endif; ?>
            </div>
        </form>

        <br>

        <!-- Affichage des CR existants pour cette date (uniquement si demandé) -->
        <?php if ($show_cr_list): ?>
            <div class="cr-list">
                <?php if (mysqli_num_rows($liste_cr_result) > 0): ?>
                    <h3>Comptes rendus existants pour le <?php echo formatDateFrench($date_cr); ?> :</h3>
                    <?php while ($cr = mysqli_fetch_assoc($liste_cr_result)): ?>
                        <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
                            <p><strong>Créé le :</strong> <?php echo formatDateTimeFrench($cr['datetime']); ?></p>
                            <p><strong>Description :</strong><br><?php echo nl2br(htmlspecialchars($cr['description'])); ?></p>
                            
                            <!-- Affichage des pièces jointes -->
                            <?php 
                            $pieces_jointes = getPiecesJointes($cr['num']);
                            if (!empty($pieces_jointes)): ?>
                                <p><strong>Pièces jointes :</strong></p>
                                <?php foreach ($pieces_jointes as $piece): ?>
                                    <div class="piece-jointe">
                                        <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank">
                                            📎 <?php echo htmlspecialchars($piece['nom_fichier']); ?>
                                        </a>
                                        (<?php echo formaterTailleFichier($piece['taille']); ?>)
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Affichage des commentaires -->
                            <?php 
                            $commentaires = getCommentaires($cr['num']);
                            if (!empty($commentaires)): ?>
                                <div class="commentaires">
                                    <strong>Commentaires des professeurs :</strong>
                                    <?php foreach ($commentaires as $commentaire): ?>
                                        <div class="commentaire">
                                            <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                            (<?php echo formatDateTimeFrench($commentaire['date_creation']); ?>):<br>
                                            <?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Aucun compte rendu pour le <?php echo formatDateFrench($date_cr); ?>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>

        <!-- Formulaire pour créer un nouveau CR -->
        <h3>Créer un nouveau compte rendu :</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="date_cr" value="<?php echo $date_cr; ?>">

            <label for="description">Descriptif :</label><br>
            <textarea id="description" name="description" rows="10" cols="50" required><?php echo $description; ?></textarea><br><br>

            <label for="pieces_jointes">Pièces jointes (max 10MB par fichier) :</label><br>
            <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx"><br>
            <small>Types autorisés: JPG, PNG, GIF, PDF, DOC, DOCX</small><br><br>

            <button type="submit" name="insérer">Créer un nouveau CR</button>
        </form>
    <?php endif; ?>

    <p><a href="accueil.php">Retour à l'accueil</a></p>
</body>
</html>