<?php
session_start();
include '_conf.php';
include 'fonctions.php';

isset($_SESSION['Sid']) && $_SESSION['Stype'] == 0 or die(header("Location: index.php"));
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD) or die("Erreur connexion BDD");
$uid = intval($_SESSION['Sid']);
$msg = $err = '';
$date_cr = date('Y-m-d');
$titre = $desc = $html = '';
$show_form = $show_list = false;
$mode_edition = false;
$cr_id = 0;

// Vérifier si on est en mode édition
if (isset($_GET['id'])) {
    $cr_id = intval($_GET['id']);
    $mode_edition = true;
    
    // Vérifier que le CR appartient à l'utilisateur
    $cr_query = mysqli_query($bdd, "SELECT * FROM cr WHERE num = $cr_id AND num_utilisateur = $uid");
    if (mysqli_num_rows($cr_query) > 0) {
        $cr_data = mysqli_fetch_assoc($cr_query);
        $date_cr = $cr_data['date'];
        $titre = $cr_data['titre'];
        $desc = $cr_data['description'];
        $html = $cr_data['contenu_html'];
    } else {
        $mode_edition = false;
        $err = "Ce compte rendu n'existe pas ou ne vous appartient pas.";
    }
}

$s = mysqli_fetch_assoc(mysqli_query($bdd, "SELECT s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.tel as tuteur_tel, t.email as tuteur_email FROM utilisateur u LEFT JOIN stage s ON u.num_stage = s.num LEFT JOIN tuteur t ON s.num_tuteur = t.num WHERE u.num = $uid"));
$req = ['nom', 'adresse', 'CP', 'ville', 'tel', 'libelleStage', 'email', 'tuteur_nom', 'tuteur_prenom', 'tuteur_tel', 'tuteur_email'];
$show_form = $s && !array_diff_key(array_flip($req), array_filter($s));

if (isset($_POST['show_cr'])) {
    $date_cr = q($_POST['date_cr']);
    $show_list = true;
}

if (isset($_POST['hide_cr'])) {
    $date_cr = q($_POST['date_cr']);
    $show_list = false;
}

if (isset($_POST['insérer']) && $show_form) {
    $date_cr = q($_POST['date_cr']);
    $titre = q($_POST['titre']);
    $desc = q($_POST['description']);
    $html = q($_POST['contenu_html']);
    
    if (empty($titre)) {
        $err = "Le titre du compte rendu est obligatoire.";
    } elseif (empty($html)) {
        $err = "Le contenu du compte rendu est obligatoire.";
    } else {
        if ($mode_edition) {
            // Mise à jour du CR existant
            if (mysqli_query($bdd, "UPDATE cr SET date = '$date_cr', titre = '$titre', description = '$desc', contenu_html = '$html', datetime = NOW() WHERE num = $cr_id AND num_utilisateur = $uid")) {
                $msg = "Compte rendu modifié avec succès !";
                
                // Gestion des nouvelles pièces jointes
                if (!empty($_FILES['pieces_jointes']['name'][0])) {
                    $errs = [];
                    foreach ($_FILES['pieces_jointes']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['pieces_jointes']['error'][$k] === UPLOAD_ERR_OK) {
                            try {
                                sauvegarderFichier(['name' => $_FILES['pieces_jointes']['name'][$k], 'type' => $_FILES['pieces_jointes']['type'][$k], 'tmp_name' => $tmp, 'error' => $_FILES['pieces_jointes']['error'][$k], 'size' => $_FILES['pieces_jointes']['size'][$k]], $cr_id);
                            } catch (Exception $e) {
                                $errs[] = $_FILES['pieces_jointes']['name'][$k] . ': ' . $e->getMessage();
                                logger("Erreur upload fichier: " . $e->getMessage(), $uid, 'editer_cr.php');
                            }
                        }
                    }
                    if (!empty($errs)) $msg .= " Mais certaines pièces jointes n'ont pas pu être uploadées: " . implode(', ', $errs);
                }
                $show_list = true;
            } else {
                $err = "Erreur lors de la modification : " . mysqli_error($bdd);
                logger("Erreur modification CR: " . mysqli_error($bdd), $uid, 'editer_cr.php');
            }
        } else {
            // Création d'un nouveau CR
            if (mysqli_query($bdd, "INSERT INTO cr (date, titre, description, contenu_html, vu, datetime, num_utilisateur) VALUES ('$date_cr', '$titre', '$desc', '$html', 0, NOW(), $uid)")) {
                $crid = mysqli_insert_id($bdd);
                $msg = "Nouveau compte rendu créé avec succès !";
                $titre = $desc = $html = '';
                
                if (!empty($_FILES['pieces_jointes']['name'][0])) {
                    $errs = [];
                    foreach ($_FILES['pieces_jointes']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['pieces_jointes']['error'][$k] === UPLOAD_ERR_OK) {
                            try {
                                sauvegarderFichier(['name' => $_FILES['pieces_jointes']['name'][$k], 'type' => $_FILES['pieces_jointes']['type'][$k], 'tmp_name' => $tmp, 'error' => $_FILES['pieces_jointes']['error'][$k], 'size' => $_FILES['pieces_jointes']['size'][$k]], $crid);
                            } catch (Exception $e) {
                                $errs[] = $_FILES['pieces_jointes']['name'][$k] . ': ' . $e->getMessage();
                                logger("Erreur upload fichier: " . $e->getMessage(), $uid, 'editer_cr.php');
                            }
                        }
                    }
                    if (!empty($errs)) $msg .= " Mais certaines pièces jointes n'ont pas pu être uploadées: " . implode(', ', $errs);
                }
                $show_list = true;
            } else {
                $err = "Erreur lors de la création : " . mysqli_error($bdd);
                logger("Erreur création CR: " . mysqli_error($bdd), $uid, 'editer_cr.php');
            }
        }
    }
}

$liste_cr_result = $show_list ? mysqli_query($bdd, "SELECT * FROM cr WHERE num_utilisateur = $uid AND date = '" . q($date_cr) . "' ORDER BY datetime DESC") : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode_edition ? 'Modifier' : 'Créer'; ?> un compte rendu</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="cr.css">
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
            images_upload_url: '/PROJET_IDRISS_AP1/api_upload_image.php',
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
                    if (fichier.size > tailleMaxFichier) {
                        fichiersValides = false;
                        messageTexte += `❌ ${fichier.name} : Trop volumineux (max ${formatTaille(tailleMaxFichier)})<br>`;
                        continue;
                    }
                    if (!typesAutorises.includes(fichier.type)) {
                        fichiersValides = false;
                        messageTexte += `❌ ${fichier.name} : Type non autorisé<br>`;
                        messageTexte += `Types autorisés : JPG, PNG, GIF, PDF, DOC, DOCX<br>`;
                    } else {
                        messageTexte += `✅ ${fichier.name} : OK<br>`;
                    }
                }
            } else {
                messageTexte = 'Aucun fichier sélectionné';
            }
            
            message.innerHTML = messageTexte;
            boutonSubmit.disabled = !fichiersValides;
            message.style.color = fichiersValides ? 'green' : 'red';
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('pieces_jointes');
            if (input && input.files.length > 0) verifierFichiers();
        });
    </script>
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <?php afficherHeaderPage('📝', $mode_edition ? 'Modifier un compte rendu' : 'Créer un compte rendu', $mode_edition ? 'Modifiez votre compte rendu existant' : 'Rédigez et enregistrez un nouveau compte rendu'); ?>

    <?php if ($msg): ?><div class="message-box message-success">✅ <?php echo $msg; ?></div><?php endif; ?>
    <?php if ($err): ?><div class="message-box message-error">❌ <?php echo $err; ?></div><?php endif; ?>
    
    <?php if (!$show_form): ?>
        <div class="info-section warning">
            <h3>⚠️ Informations manquantes</h3>
            <p>Avant de <?php echo $mode_edition ? 'modifier' : 'créer'; ?> un compte rendu, vous devez compléter vos informations de stage et de tuteur.</p>
            <p>Champs manquants :</p>
            <ul>
                <?php foreach (['nom' => "Nom de l'entreprise", 'adresse' => "Adresse du stage", 'CP' => "Code postal", 'ville' => "Ville", 'tel' => "Téléphone de l'entreprise", 'libelleStage' => "Libellé du stage", 'email' => "Email de l'entreprise", 'tuteur_nom' => "Nom du tuteur", 'tuteur_prenom' => "Prénom du tuteur", 'tuteur_tel' => "Téléphone du tuteur", 'tuteur_email' => "Email du tuteur"] as $k => $v) if (!$s || empty($s[$k])) echo "<li>$v</li>"; ?>
            </ul>
            <a href="mon_stage.php">➜ Remplir mes informations de stage</a>
        </div>
    <?php else: ?>
        <div class="info-section success">
            <h3>✅ Informations de stage</h3>
            <div class="stage-info-grid">
                <div>
                    <p style="margin: 0; color: #666; font-size: 0.9em;">Entreprise</p>
                    <p style="margin: 5px 0 0 0; font-weight: 600; color: #333;"><?php echo htmlspecialchars($s['nom']); ?></p>
                </div>
                <div>
                    <p style="margin: 0; color: #666; font-size: 0.9em;">Tuteur</p>
                    <p style="margin: 5px 0 0 0; font-weight: 600; color: #333;"><?php echo htmlspecialchars($s['tuteur_prenom'] . ' ' . $s['tuteur_nom']); ?></p>
                </div>
                <div style="grid-column: 1 / -1;">
                    <a href="mon_stage.php" style="display: inline-block; padding: 8px 16px; background: #667eea; color: white; border-radius: 5px; text-decoration: none; font-weight: 500; margin-top: 10px;">Modifier mes informations</a>
                </div>
            </div>
        </div>

        <?php if ($mode_edition): ?>
            <div class="info-section">
                <h2>Modifier le compte rendu</h2>
                <p><strong>Attention :</strong> Vous modifiez un compte rendu existant. Les modifications seront enregistrées immédiatement.</p>
            </div>
        <?php endif; ?>

        <div class="info-section">
            <h2>Mes comptes rendus</h2>
            <form method="POST" class="form-group">
                <label for="date_cr">Sélectionner une date :</label>
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <input type="date" id="date_cr" name="date_cr" value="<?php echo $date_cr; ?>" required>
                    <button type="submit" name="<?php echo $show_list ? 'hide_cr' : 'show_cr'; ?>" class="btn <?php echo $show_list ? 'btn-secondary' : 'btn-primary'; ?>" style="max-width: 200px;">
                        <?php echo $show_list ? '✕ Masquer' : '📋 Voir'; ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($show_list && $liste_cr_result): ?>
            <div class="info-section">
                <?php if (mysqli_num_rows($liste_cr_result) > 0): ?>
                    <h3>Comptes rendus du <?php echo formatDateFrench($date_cr); ?></h3>
                    <?php while ($cr = mysqli_fetch_assoc($liste_cr_result)): ?>
                        <div class="cr-list-item">
                            <div style="margin-bottom: 10px;">
                                <?php if (!empty($cr['titre'])): ?><h3>📝 <?php echo htmlspecialchars($cr['titre']); ?></h3><?php endif; ?>
                                <p><strong>Créé le :</strong> <?php echo formatDateTimeFrench($cr['datetime']); ?></p>
                                <p><strong>Statut :</strong> <?php echo $cr['vu'] == 1 ? "✅ Consulté" : "❌ Non consulté"; ?></p>
                                <div style="margin-top: 10px;">
                                    <a href="editer_cr.php?id=<?php echo $cr['num']; ?>" class="btn btn-warning btn-sm">✏️ Modifier</a>
                                    <a href="liste_cr.php?detail=<?php echo $cr['num']; ?>" class="btn btn-primary btn-sm">📄 Voir détails</a>
                                </div>
                            </div>
                            
                            <?php if (!empty($cr['description'])): ?>
                                <p><strong>Description courte :</strong></p>
                                <div class="cr-content"><?php echo htmlspecialchars($cr['description']); ?></div>
                            <?php endif; ?>
                            
                            <p><strong>Contenu :</strong></p>
                            <div class="cr-content"><?php echo $cr['contenu_html']; ?></div>
                            
                            <?php 
                            $pieces_jointes = getPiecesJointes($cr['num']);
                            if (!empty($pieces_jointes)): ?>
                                <p><strong>📎 Pièces jointes :</strong></p>
                                <?php foreach ($pieces_jointes as $piece): ?>
                                    <div class="file-item">
                                        <span>📄 <?php echo htmlspecialchars($piece['nom_fichier']); ?> (<?php echo formaterTailleFichier($piece['taille']); ?>)</span>
                                        <a href="telecharger.php?id=<?php echo $piece['id']; ?>" target="_blank">⬇️ Télécharger</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            $commentaires = getCommentaires($cr['num']);
                            if (!empty($commentaires)): ?>
                                <p style="margin-top: 15px;"><strong>💬 Commentaires (<?php echo count($commentaires); ?>) :</strong></p>
                                <?php foreach ($commentaires as $commentaire): ?>
                                    <div style="background: linear-gradient(135deg, #f5f7ff 0%, #f0f4ff 100%); padding: 12px; margin: 10px 0; border-left: 3px solid #667eea; border-radius: 4px;">
                                        <p style="margin: 0 0 5px 0;"><strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong> <span style="color: #999; font-size: 12px;">– <?php echo formatDateTimeFrench($commentaire['date_creation']); ?></span></p>
                                        <p style="margin: 0; white-space: pre-wrap; line-height: 1.4;"><?php echo htmlspecialchars($commentaire['commentaire']); ?></p>
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

        <div class="info-section">
            <h2><?php echo $mode_edition ? 'Modifier le compte rendu' : 'Créer un nouveau compte rendu'; ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="date_cr">Date <span style="color: red;">*</span></label>
                        <input type="date" id="date_cr" name="date_cr" value="<?php echo $date_cr; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="titre">Titre <span style="color: red;">*</span></label>
                        <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre); ?>" required placeholder="Ex : Journée de formation">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description courte (optionnel)</label>
                    <textarea id="description" name="description" placeholder="Un petit résumé ou aperçu du compte rendu"><?php echo htmlspecialchars($desc); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="contenu_html">Contenu du compte rendu <span style="color: red;">*</span></label>
                    <textarea id="contenu_html" name="contenu_html" required><?php echo htmlspecialchars($html); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="pieces_jointes">Pièces jointes (max 10MB par fichier)</label>
                    <input type="file" id="pieces_jointes" name="pieces_jointes[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="verifierFichiers()">
                    <div id="message_validation_fichiers"></div>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">Types autorisés : JPG, PNG, GIF, PDF, DOC, DOCX</p>
                </div>

                <button type="submit" name="insérer" class="btn btn-success">
                    <?php echo $mode_edition ? '💾 Enregistrer les modifications' : '✅ Créer le compte rendu'; ?>
                </button>
                
                <?php if ($mode_edition): ?>
                    <a href="editer_cr.php" class="btn btn-secondary">❌ Annuler</a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
    <?php include 'footer.php'; ?>