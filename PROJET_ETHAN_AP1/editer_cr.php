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

$stage_query = "SELECT s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.tel as tuteur_tel, t.email as tuteur_email 
                FROM utilisateur u 
                LEFT JOIN stage s ON u.num_stage = s.num 
                LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                WHERE u.num = $user_id";
$stage_result = mysqli_query($bdd, $stage_query);
$stage_info = mysqli_fetch_assoc($stage_result);

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
$contenu_html = '';

// Récupération des modèles de CR
$modeles_query = "SELECT * FROM modeles_cr WHERE actif = 1 ORDER BY date_creation DESC";
$modeles_result = mysqli_query($bdd, $modeles_query);

// Récupération du contenu d'un modèle si demandé
if (isset($_GET['modele']) && !empty($_GET['modele'])) {
    $modele_id = intval($_GET['modele']);
    $modele_query = "SELECT * FROM modeles_cr WHERE id = $modele_id AND actif = 1";
    $modele_result = mysqli_query($bdd, $modele_query);
    if (mysqli_num_rows($modele_result) > 0) {
        $modele = mysqli_fetch_assoc($modele_result);
        $contenu_html = $modele['contenu_html'];
        $description = "Compte rendu basé sur le modèle: " . $modele['titre'];
    }
}

if (isset($_POST['show_cr'])) {
    $date_cr = $_POST['date_cr'];
    $show_cr_list = true;
}

if (isset($_POST['hide_cr'])) {
    $date_cr = $_POST['date_cr'];
    $show_cr_list = false;
}

if (isset($_POST['insérer']) && $show_cr_form) {
    $date_cr = $_POST['date_cr'];
    $description = mysqli_real_escape_string($bdd, $_POST['description']);
    $contenu_html = mysqli_real_escape_string($bdd, $_POST['contenu_html']);

    $today = date('Y-m-d');
    if ($date_cr != $today) {
        $error = "Vous ne pouvez créer des comptes rendus que pour la date d'aujourd'hui (" . formatDateFrench($today) . ").";
    } else {
        $insert_query = "INSERT INTO cr (date, description, contenu_html, vu, datetime, num_utilisateur) 
                        VALUES ('$date_cr', '$description', '$contenu_html', 0, NOW(), $user_id)";
        
        if (mysqli_query($bdd, $insert_query)) {
            $cr_id = mysqli_insert_id($bdd);
            $message = "Nouveau compte rendu créé avec succès !";
            $description = '';
            $contenu_html = '';
            
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
                            logger("Erreur upload fichier: " . $e->getMessage(), $user_id, 'editer_cr.php');
                        }
                    }
                }
                if (!empty($upload_errors)) {
                    $message .= " Mais certaines pièces jointes n'ont pas pu être uploadées: " . implode(', ', $upload_errors);
                }
            }
            
            $show_cr_list = true;
        } else {
            $error = "Erreur lors de la création : " . mysqli_error($bdd);
            logger("Erreur création CR: " . mysqli_error($bdd), $user_id, 'editer_cr.php');
        }
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte rendu</title>
    <!-- Inclusion de TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        /* Masquer l'alerte TinyMCE sur les clés API */
        .tox-notification { display: none !important; }
    </style>
    <script>
        tinymce.init({
            selector: '#contenu_html',
            height: 400,
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | table | code',
            menubar: 'file edit view insert format tools table help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            language: 'fr_FR'
        });
        
        // Types de fichiers autorisés
        const typesAutorises = <?php echo json_encode($types_autorises); ?>;
        const tailleMaxFichier = <?php echo $taille_max_fichier; ?>;
        
        function verifierFichiers() {
            const input = document.getElementById('pieces_jointes');
            const message = document.getElementById('message_validation_fichiers');
            const boutonSubmit = document.querySelector('button[name="insérer"]');
            let fichiersValides = true;
            let messageTexte = '';
            
            if (input.files.length > 0) {
                for (let i = 0; i < input.files.length; i++) {
                    const fichier = input.files[i];
                    
                    // Vérification de la taille
                    if (fichier.size > tailleMaxFichier) {
                        fichiersValides = false;
                        messageTexte += `❌ ${fichier.name} : Trop volumineux (max ${formatTaille(tailleMaxFichier)})<br>`;
                        continue;
                    }
                    
                    // Vérification du type MIME
                    if (!typesAutorises.includes(fichier.type)) {
                        fichiersValides = false;
                        const extensions = getExtensionsFromMimeTypes();
                        messageTexte += `❌ ${fichier.name} : Type non autorisé<br>`;
                        messageTexte += `Types autorisés : ${extensions}<br>`;
                    } else {
                        messageTexte += `✅ ${fichier.name} : Type autorisé<br>`;
                    }
                }
            } else {
                messageTexte = 'Aucun fichier sélectionné';
            }
            
            message.innerHTML = messageTexte;
            
            // Désactiver le bouton si fichiers invalides
            if (!fichiersValides) {
                message.style.color = 'red';
                boutonSubmit.disabled = true;
                boutonSubmit.style.backgroundColor = '#6c757d';
                boutonSubmit.title = 'Corrigez les erreurs de fichiers avant de soumettre';
            } else {
                message.style.color = 'green';
                boutonSubmit.disabled = false;
                boutonSubmit.style.backgroundColor = '#28a745';
                boutonSubmit.title = '';
            }
        }
        
        function formatTaille(octets) {
            const unites = ['o', 'Ko', 'Mo', 'Go'];
            let puissance = 0;
            
            while (octets >= 1024 && puissance < unites.length - 1) {
                octets /= 1024;
                puissance++;
            }
            
            return `${Math.round(octets * 100) / 100} ${unites[puissance]}`;
        }
        
        function getExtensionsFromMimeTypes() {
            const extensions = {
                'image/jpeg': 'JPG, JPEG',
                'image/png': 'PNG',
                'image/gif': 'GIF',
                'application/pdf': 'PDF',
                'application/msword': 'DOC',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX'
            };
            
            return Object.values(extensions).join(', ');
        }
        
        // Vérifier les fichiers au chargement de la page si des fichiers sont déjà sélectionnés
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('pieces_jointes');
            if (input.files.length > 0) {
                verifierFichiers();
            }
        });
        
        // Fonction pour charger un modèle
        function chargerModele(id) {
            window.location.href = 'editer_cr.php?modele=' + id;
        }
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea.form-control {
            min-height: 100px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-success {
            background: #28a745;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .modele-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .modele-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            width: calc(33.333% - 15px);
            min-width: 250px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modele-card:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        .modele-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .modele-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Créer un compte rendu</h1>
        <p><a href="accueil.php">← Retour à l'accueil</a> | <a href="tableau_bord_eleve.php">Tableau de bord</a></p>

        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$show_cr_form): ?>
            <div class="alert alert-warning">
                <h3>⚠️ Informations manquantes</h3>
                <p>Avant de créer un compte rendu, vous devez compléter vos informations de stage et de tuteur.</p>
                <p>Champs manquants :</p>
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
                <p><a href="mon_stage.php" class="btn">
                    ➜ Remplir mes informations de stage
                </a></p>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>✅ Vos informations de stage sont complètes</h3>
                <p><strong>Entreprise :</strong> <?php echo htmlspecialchars($stage_info['nom']); ?></p>
                <p><strong>Tuteur :</strong> <?php echo htmlspecialchars($stage_info['tuteur_prenom'] . ' ' . $stage_info['tuteur_nom']); ?></p>
                <p><a href="mon_stage.php">Modifier mes informations</a></p>
            </div>

            <div class="card">
                <h2>Mes comptes rendus</h2>
                <form method="POST" style="margin-bottom: 15px;">
                    <label for="date_cr">Sélectionner une date :</label>
                    <input type="date" id="date_cr" name="date_cr" value="<?php echo $date_cr; ?>" required style="padding: 8px; margin: 10px 0; margin-right: 10px;">
                    
                    <?php if (!$show_cr_list): ?>
                        <button type="submit" name="show_cr" class="btn">
                            📋 Voir les CR de ce jour
                        </button>
                    <?php else: ?>
                        <button type="submit" name="hide_cr" class="btn btn-secondary">
                            ✕ Masquer
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($show_cr_list && $liste_cr_result): ?>
                <div class="card">
                    <h2>Comptes rendus du <?php echo formatDateFrench($date_cr); ?></h2>
                    <?php if (mysqli_num_rows($liste_cr_result) > 0): ?>
                        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th>Date et heure</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cr = mysqli_fetch_assoc($liste_cr_result)): ?>
                                    <tr>
                                        <td><?php echo formatDateTimeFrench($cr['datetime']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($cr['description'], 0, 100)) . (strlen($cr['description']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo $cr['vu'] ? '✅ Consulté' : '❌ Non consulté'; ?></td>
                                        <td>
                                            <a href="liste_cr.php?detail=<?php echo $cr['num']; ?>" class="btn" style="font-size: 12px;">
                                                Voir détails
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Aucun compte rendu trouvé pour cette date.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>📋 Modèles de comptes rendus disponibles</h2>
                <p style="margin-bottom: 20px; color: #555;">
                    <strong>💡 Conseil :</strong> Utilisez un modèle pour démarrer rapidement votre compte rendu. 
                    Le modèle vous fournira une structure et des indications sur ce que vous devez remplir.
                </p>
                <?php if (mysqli_num_rows($modeles_result) > 0): ?>
                    <div class="modele-list">
                        <?php while ($modele = mysqli_fetch_assoc($modeles_result)): ?>
                            <div class="modele-card" onclick="chargerModele(<?php echo $modele['id']; ?>)">
                                <div class="modele-title">📄 <?php echo htmlspecialchars($modele['titre']); ?></div>
                                <div class="modele-description"><?php echo htmlspecialchars($modele['description']); ?></div>
                                <button class="btn" style="font-size: 12px;">Utiliser ce modèle</button>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>Aucun modèle de compte rendu disponible pour le moment.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>✏️ Créer un nouveau compte rendu</h2>
                <?php if (!empty($contenu_html)): ?>
                    <div class="alert alert-success" style="background-color: #e8f5e9; border: 1px solid #4caf50;">
                        ✅ Modèle chargé ! Complétez le contenu ci-dessous en suivant la structure proposée.
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="date_cr" value="<?php echo $date_cr; ?>">
                    
                    <div class="form-group">
                        <label for="description">Titre du compte rendu *</label>
                        <input type="text" id="description" name="description" class="form-control" required value="<?php echo htmlspecialchars($description); ?>" placeholder="Ex: Compte rendu du 15 janvier 2025">
                        <small style="color: #666;">Donnez un titre descriptif à votre compte rendu.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contenu_html">Contenu du compte rendu *</label>
                        <textarea id="contenu_html" name="contenu_html" class="form-control"><?php echo htmlspecialchars($contenu_html); ?></textarea>
                        <small style="color: #666;">
                            Écrivez le contenu de votre compte rendu. 
                            <?php if (!empty($contenu_html)): ?>
                                Vous pouvez modifier le modèle chargé selon vos activités.
                            <?php else: ?>
                                Vous pouvez aussi charger un modèle ci-dessus pour vous guider.
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="pieces_jointes">Pièces jointes (facultatif)</label>
                        <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple onchange="verifierFichiers()">
                        <div id="message_validation_fichiers" style="margin-top: 10px; font-size: 14px;"></div>
                        <small style="color: #666;">
                            Types de fichiers autorisés : JPG, PNG, GIF, PDF, DOC, DOCX<br>
                            Taille maximale : <?php echo formaterTailleFichier($taille_max_fichier); ?>
                        </small>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="insérer" class="btn btn-success">
                            Créer le compte rendu
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>