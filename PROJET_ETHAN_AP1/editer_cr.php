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
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/5ze3f1mnixciatxizi78vap897rh0ctes5rqe20nbgof2t73/tinymce/7/tinymce.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .back-btn:hover {
            background: #5a6268;
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
            border: 1px solid #ffeaa7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.5);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        /* TinyMCE Custom Styling */
        .tox-tinymce {
            border-radius: 4px !important;
            border: 1px solid #ddd !important;
        }
        
        .tox .tox-toolbar {
            background: #f8f9fa !important;
        }
        
        /* Boutons d'action */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Fichiers */
        .file-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .file-input-wrapper input[type="file"] {
            display: none;
        }
        
        .file-input-label {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .file-input-label:hover {
            background: #0056b3;
        }
        
        #message_validation_fichiers {
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .list-modeles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .modele-card {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modele-card:hover {
            background: #e9ecef;
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0,123,255,0.2);
        }
        
        .modele-card h4 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .modele-card p {
            font-size: 12px;
            color: #666;
        }
        
        .no-stage-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
    </style>
    <script>
        // TinyMCE Configuration
        tinymce.init({
            selector: '#contenu_html',
            language: 'fr_FR',
            menubar: 'file edit view insert format tools',
            toolbar: 'undo redo | formatselect fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat help',
            plugins: 'lists link image table code help',
            font_sizes: '10px 12px 14px 16px 18px 20px 24px 28px',
            height: 400,
            min_height: 300,
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
            branding: false,
            promotion: false,
            auto_focus: false,
            setup: function(editor) {
                editor.on('change', function() {
                    // Sync textarea when content changes
                    document.getElementById('contenu_html_input').value = editor.getContent();
                });
            }
        });
        
        function chargerModele(id) {
            window.location.href = 'editer_cr.php?modele=' + id;
        }
        
        function verifierFichiers() {
            const input = document.getElementById('pieces_jointes');
            const message = document.getElementById('message_validation_fichiers');
            const boutonSubmit = document.querySelector('button[name="insérer"]');
            
            const typesAutorises = <?php echo json_encode($types_autorises); ?>;
            const tailleMaxFichier = <?php echo $taille_max_fichier; ?>;
            
            let fichiersValides = true;
            let messageTexte = '';
            
            if (input.files.length > 0) {
                for (let i = 0; i < input.files.length; i++) {
                    const fichier = input.files[i];
                    
                    if (fichier.size > tailleMaxFichier) {
                        fichiersValides = false;
                        messageTexte += `❌ ${fichier.name} : Trop volumineux<br>`;
                    } else if (!typesAutorises.includes(fichier.type)) {
                        fichiersValides = false;
                        messageTexte += `❌ ${fichier.name} : Type non autorisé<br>`;
                    } else {
                        messageTexte += `✅ ${fichier.name}<br>`;
                    }
                }
            } else {
                messageTexte = 'Aucun fichier sélectionné';
            }
            
            message.innerHTML = messageTexte;
            
            if (!fichiersValides) {
                message.style.color = 'red';
                boutonSubmit.disabled = true;
                boutonSubmit.style.backgroundColor = '#ccc';
            } else {
                message.style.color = 'green';
                boutonSubmit.disabled = false;
                boutonSubmit.style.backgroundColor = '#007bff';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Sync hidden input before form submission
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (tinymce.get('contenu_html')) {
                        document.getElementById('contenu_html_input').value = tinymce.get('contenu_html').getContent();
                    }
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Créer un compte rendu</h1>
            <a href="accueil.php" class="back-btn">← Retour</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$show_cr_form): ?>
            <div class="card no-stage-warning">
                <strong>⚠️ Information incomplète</strong><br>
                Veuillez d'abord compléter votre profil (stage, tuteur, etc.) avant de créer un compte rendu.
                <br><a href="perso.php">Compléter mon profil →</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Sélectionner un modèle (optionnel)</h2>
                <?php if (mysqli_num_rows($modeles_result) > 0): ?>
                    <div class="list-modeles">
                        <?php while ($modele = mysqli_fetch_assoc($modeles_result)): ?>
                            <div class="modele-card" onclick="chargerModele(<?php echo $modele['id']; ?>)">
                                <h4><?php echo htmlspecialchars($modele['titre']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($modele['description'], 0, 100)) . '...'; ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>Aucun modèle disponible</p>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="card">
                    <div class="form-group">
                        <label for="date_cr">Date du compte rendu</label>
                        <input type="date" id="date_cr" name="date_cr" class="form-control" value="<?php echo $date_cr; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="description">Titre/Description rapide</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Résumé rapide du CR..."><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contenu_html">Contenu détaillé</label>
                        <textarea id="contenu_html" name="contenu_html"><?php echo htmlspecialchars($contenu_html); ?></textarea>
                        <input type="hidden" name="contenu_html_input" id="contenu_html_input" value="<?php echo htmlspecialchars($contenu_html); ?>">
                    </div>

                    <div class="file-section">
                        <label>Ajouter des pièces jointes</label>
                        <div class="file-input-wrapper">
                            <label for="pieces_jointes" class="file-input-label">📎 Sélectionner des fichiers</label>
                            <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple onchange="verifierFichiers()">
                        </div>
                        <div id="message_validation_fichiers"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="insérer" class="btn btn-primary">
                            ✅ Enregistrer le compte rendu
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='accueil.php';">
                            ❌ Annuler
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($show_cr_list && $liste_cr_result): ?>
            <div class="card">
                <h2>📋 Comptes rendus du <?php echo formatDateFrench($date_cr); ?></h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px; text-align: left;">Titre</th>
                            <th style="padding: 10px; text-align: left;">Heure</th>
                            <th style="padding: 10px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cr = mysqli_fetch_assoc($liste_cr_result)): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($cr['description']); ?></td>
                                <td style="padding: 10px;"><?php echo date('H:i', strtotime($cr['datetime'])); ?></td>
                                <td style="padding: 10px; text-align: center;">
                                    <a href="liste_cr.php?action=voir&id=<?php echo $cr['num']; ?>" style="color: #007bff; text-decoration: none;">Voir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>