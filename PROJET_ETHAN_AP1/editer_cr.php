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
$titre = '';
$description = '';
$contenu_html = '';

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
    $titre = mysqli_real_escape_string($bdd, $_POST['titre']);
    $description = mysqli_real_escape_string($bdd, $_POST['description']);
    $contenu_html = mysqli_real_escape_string($bdd, $_POST['contenu_html']);

    $today = date('Y-m-d');
    if ($date_cr != $today) {
        $error = "Vous ne pouvez créer des comptes rendus que pour la date d'aujourd'hui (" . formatDateFrench($today) . ").";
    } else if (empty($titre)) {
        $error = "Le titre du compte rendu est obligatoire.";
    } else if (empty($contenu_html)) {
        $error = "Le contenu du compte rendu est obligatoire.";
    } else {
        $insert_query = "INSERT INTO cr (date, titre, description, contenu_html, vu, datetime, num_utilisateur) 
                        VALUES ('$date_cr', '$titre', '$description', '$contenu_html', 0, NOW(), $user_id)";
        
        if (mysqli_query($bdd, $insert_query)) {
            $cr_id = mysqli_insert_id($bdd);
            $message = "Nouveau compte rendu créé avec succès !";
            $titre = '';
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
    <script src="https://cdn.tiny.cloud/1/5ze3f1mnixciatxizi78vap897rh0ctes5rqe20nbgof2t73/tinymce/6/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '#contenu_html',
            language: 'fr_FR',
            height: 400,
            menubar: true,
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | help',
            branding: false,
            promotion: false,
            automatic_uploads: true,
            file_picker_types: 'image',
            paste_data_images: true,
            images_upload_url: '/PROJET_ETHAN_AP1/api_upload_image.php',
            relative_urls: false,
            convert_urls: true,
            body_class: 'mce-content-body',
            content_css: 'default',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });
    </script>
    <script>
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
    </script>
</head>
<body>
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <h1>Créer un compte rendu</h1>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #28a745;">
            ✅ <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #dc3545;">
            ❌ <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!$show_cr_form): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #ffc107;">
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
            <p><a href="mon_stage.php" style="background: #ffc107; color: #333; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; margin-top: 10px;">
                ➜ Remplir mes informations de stage
            </a></p>
        </div>
    <?php else: ?>
        <div style="background: #d4edda; border: 1px solid #28a745; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <h3>✅ Vos informations de stage sont complètes</h3>
            <p><strong>Entreprise :</strong> <?php echo htmlspecialchars($stage_info['nom']); ?></p>
            <p><strong>Tuteur :</strong> <?php echo htmlspecialchars($stage_info['tuteur_prenom'] . ' ' . $stage_info['tuteur_nom']); ?></p>
            <p><a href="mon_stage.php">Modifier mes informations</a></p>
        </div>

        <div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #dee2e6;">
            <h2>Mes comptes rendus</h2>
            <form method="POST" style="margin-bottom: 15px;">
                <label for="date_cr">Sélectionner une date :</label>
                <input type="date" id="date_cr" name="date_cr" value="<?php echo $date_cr; ?>" required style="padding: 8px; margin: 10px 0; margin-right: 10px;">
                
                <?php if (!$show_cr_list): ?>
                    <button type="submit" name="show_cr" style="background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                        📋 Voir les CR de ce jour
                    </button>
                <?php else: ?>
                    <button type="submit" name="hide_cr" style="background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                        ✕ Masquer
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($show_cr_list && $liste_cr_result): ?>
            <div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #dee2e6;">
                <?php if (mysqli_num_rows($liste_cr_result) > 0): ?>
                    <h3>Comptes rendus du <?php echo formatDateFrench($date_cr); ?></h3>
                    
                    <?php while ($cr = mysqli_fetch_assoc($liste_cr_result)): ?>
                        <div style="background: white; border: 1px solid #e0e0e0; padding: 15px; margin: 12px 0; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <?php if (!empty($cr['titre'])): ?>
                                        <h3 style="margin: 0 0 10px 0; color: #333;">📝 <?php echo htmlspecialchars($cr['titre']); ?></h3>
                                    <?php endif; ?>
                                    <p><strong>Créé le :</strong> <?php echo formatDateTimeFrench($cr['datetime']); ?></p>
                                    <p><strong>Statut :</strong> <?php echo $cr['vu'] == 1 ? "✅ Consulté" : "❌ Non consulté"; ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($cr['description'])): ?>
                                <p><strong>Description courte :</strong></p>
                                <div style="line-height: 1.5; background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; color: #666; font-size: 14px;">
                                    <?php echo htmlspecialchars($cr['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <p><strong>Contenu :</strong></p>
                            <div style="line-height: 1.6; background: #f8f9fa; padding: 12px; border-radius: 4px; margin: 10px 0; border-left: 3px solid #007bff;">
                                <?php echo $cr['contenu_html']; ?>
                            </div>
                            
                            <?php 
                            $pieces_jointes = getPiecesJointes($cr['num']);
                            if (!empty($pieces_jointes)): ?>
                                <p><strong>📎 Pièces jointes :</strong></p>
                                <?php foreach ($pieces_jointes as $piece): ?>
                                    <div style="background: #f0f0f0; padding: 8px; margin: 5px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span>📄 <?php echo htmlspecialchars($piece['nom_fichier']); ?> (<?php echo formaterTailleFichier($piece['taille']); ?>)</span>
                                        <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank" style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                                            ⬇️ Télécharger
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            $commentaires = getCommentaires($cr['num']);
                            if (!empty($commentaires)): ?>
                                <p style="margin-top: 15px;"><strong>💬 Commentaires (<?php echo count($commentaires); ?>) :</strong></p>
                                <?php foreach ($commentaires as $commentaire): ?>
                                    <div style="background: #e7f3ff; padding: 10px; margin: 8px 0; border-left: 3px solid #007bff; border-radius: 4px;">
                                        <p style="margin: 0 0 5px 0;">
                                            <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                                            <span style="color: #999; font-size: 12px;">– <?php echo formatDateTimeFrench($commentaire['date_creation']); ?></span>
                                        </p>
                                        <p style="margin: 0; white-space: pre-wrap; line-height: 1.4;">
                                            <?php echo htmlspecialchars($commentaire['commentaire']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #999; font-style: italic;">Aucun compte rendu pour le <?php echo formatDateFrench($date_cr); ?>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6;">
            <h2>Créer un nouveau compte rendu</h2>
            
            <div style="background: #d1ecf1; color: #0c5460; padding: 12px; margin-bottom: 15px; border-radius: 4px; border-left: 4px solid #17a2b8;">
                <strong>ℹ️ Information :</strong> Vous ne pouvez créer des comptes rendus que pour la date d'aujourd'hui (<?php echo formatDateFrench(date('Y-m-d')); ?>).
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="date_cr" value="<?php echo date('Y-m-d'); ?>">
                <input type="date" id="date_cr_display" value="<?php echo date('Y-m-d'); ?>" disabled style="padding: 8px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; background-color: #f8f9fa;">
                <span style="color: #666; font-style: italic;">(Date d'aujourd'hui - non modifiable)</span><br><br>

                <label for="titre">Titre du compte rendu <span style="color: red;">*</span></label><br>
                <input type="text" id="titre" name="titre" required placeholder="Ex: Journée de formation" style="width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;"><br><br>

                <label for="description">Description courte (optionnel)</label><br>
                <textarea id="description" name="description" placeholder="Un petit résumé ou aperçu du compte rendu" style="width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px; min-height: 80px; resize: vertical;"></textarea><br><br>

                <label for="contenu_html">Contenu du compte rendu <span style="color: red;">*</span></label><br>
                <textarea id="contenu_html" name="contenu_html" required></textarea><br><br>

                <label for="pieces_jointes">Pièces jointes (max 10MB par fichier)</label><br>
                <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" 
                       onchange="verifierFichiers()" style="margin: 8px 0;">
                
                <div id="message_validation_fichiers" style="font-size: 14px; margin: 10px 0; padding: 10px; border-radius: 4px; background: #f8f9fa;"></div>
                
                <p style="font-size: 12px; color: #666;">Types autorisés : JPG, PNG, GIF, PDF, DOC, DOCX</p><br>

                <button type="submit" name="insérer" style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">
                    ✅ Créer le compte rendu
                </button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>