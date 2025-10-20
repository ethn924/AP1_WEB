<?php
session_start();
include '_conf.php';
include 'fonctions.php';

// Vérification que l'utilisateur est connecté et est un professeur
if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$message = '';
$error = '';
$modele = null;

// Traitement de la suppression d'un modèle
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM modeles_cr WHERE id = $id AND professeur_id = {$_SESSION['Sid']}";
    if (mysqli_query($bdd, $delete_query)) {
        $message = "Le modèle a été supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression du modèle.";
    }
}

// Récupération d'un modèle pour modification
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM modeles_cr WHERE id = $id AND professeur_id = {$_SESSION['Sid']}";
    $edit_result = mysqli_query($bdd, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $modele = mysqli_fetch_assoc($edit_result);
    }
}

// Traitement de l'ajout ou de la modification d'un modèle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_modele'])) {
    $titre = mysqli_real_escape_string($bdd, $_POST['titre']);
    $description = mysqli_real_escape_string($bdd, $_POST['description']);
    $contenu_html = mysqli_real_escape_string($bdd, $_POST['contenu_html']);
    $professeur_id = $_SESSION['Sid'];
    
    if (empty($titre)) {
        $error = "Le titre du modèle est obligatoire.";
    } else {
        if (isset($_POST['modele_id']) && !empty($_POST['modele_id'])) {
            // Modification d'un modèle existant
            $modele_id = intval($_POST['modele_id']);
            $update_query = "UPDATE modeles_cr 
                            SET titre = '$titre', 
                                description = '$description', 
                                contenu_html = '$contenu_html' 
                            WHERE id = $modele_id AND professeur_id = $professeur_id";
            
            if (mysqli_query($bdd, $update_query)) {
                $message = "Le modèle a été mis à jour avec succès.";
                // Redirection pour éviter la soumission multiple du formulaire
                header("Location: gestion_modeles.php?edit=$modele_id&success=1");
                exit();
            } else {
                $error = "Erreur lors de la mise à jour du modèle: " . mysqli_error($bdd);
            }
        } else {
            // Ajout d'un nouveau modèle
            $insert_query = "INSERT INTO modeles_cr (titre, description, contenu_html, professeur_id) 
                            VALUES ('$titre', '$description', '$contenu_html', $professeur_id)";
            
            if (mysqli_query($bdd, $insert_query)) {
                $message = "Le modèle a été créé avec succès.";
                // Redirection pour éviter la soumission multiple du formulaire
                header("Location: gestion_modeles.php?success=1");
                exit();
            } else {
                $error = "Erreur lors de la création du modèle: " . mysqli_error($bdd);
            }
        }
    }
}

// Récupération de tous les modèles du professeur
$modeles_query = "SELECT * FROM modeles_cr WHERE professeur_id = {$_SESSION['Sid']} ORDER BY date_creation DESC";
$modeles_result = mysqli_query($bdd, $modeles_query);

// Affichage du message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = isset($_GET['edit']) ? "Le modèle a été mis à jour avec succès." : "Le modèle a été créé avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des modèles de comptes rendus</title>
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        .btn-danger {
            background: #dc3545;
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
        .modele-item {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .modele-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des modèles de comptes rendus</h1>
            <div>
                <a href="tableau_bord_prof.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php echo $modele ? 'Modifier le modèle' : 'Créer un nouveau modèle'; ?></h2>
            <form method="POST" action="">
                <?php if ($modele): ?>
                    <input type="hidden" name="modele_id" value="<?php echo $modele['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="titre">Titre du modèle *</label>
                    <input type="text" id="titre" name="titre" class="form-control" required 
                           value="<?php echo $modele ? htmlspecialchars($modele['titre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description et objectif *</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo $modele ? htmlspecialchars($modele['description']) : ''; ?></textarea>
                    <small style="color: #666;">
                        Expliquez aux élèves l'objectif de ce modèle et comment l'utiliser.<br>
                        Exemples : "Ce modèle sert à décrire les tâches réalisées cette semaine.", "Remplissez chaque section avec vos observations."
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="contenu_html">Contenu du modèle (structure et guide) *</label>
                    <textarea id="contenu_html" name="contenu_html" class="form-control" required style="min-height: 300px;"><?php echo $modele ? htmlspecialchars($modele['contenu_html']) : ''; ?></textarea>
                    <small style="color: #666;">
                        Écrivez le contenu du modèle en <strong>texte simple</strong> (sans balises HTML).<br>
                        Les élèves verront exactement ce que vous écrivez ici.<br>
                        <strong>Exemple :</strong><br>
                        Activités réalisées<br>
                        Décrivez les activités que vous avez réalisées cette semaine.<br>
                        <br>
                        Compétences développées<br>
                        Listez les compétences que vous avez développées ou mises en pratique.
                    </small>
                </div>
                
                <div style="text-align: right;">
                    <?php if ($modele): ?>
                        <a href="gestion_modeles.php" class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                    <button type="submit" name="submit_modele" class="btn btn-success">
                        <?php echo $modele ? 'Mettre à jour le modèle' : 'Créer le modèle'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>Mes modèles de comptes rendus</h2>
            <?php if (mysqli_num_rows($modeles_result) > 0): ?>
                <?php while ($modele_item = mysqli_fetch_assoc($modeles_result)): ?>
                    <div class="modele-item">
                        <h3><?php echo htmlspecialchars($modele_item['titre']); ?></h3>
                        <p><?php echo htmlspecialchars($modele_item['description']); ?></p>
                        <div style="color: #666; font-size: 12px;">
                            Créé le <?php echo date('d/m/Y à H:i', strtotime($modele_item['date_creation'])); ?>
                        </div>
                        <div class="modele-actions">
                            <a href="gestion_modeles.php?edit=<?php echo $modele_item['id']; ?>" class="btn">Modifier</a>
                            <a href="gestion_modeles.php?delete=<?php echo $modele_item['id']; ?>" class="btn btn-danger" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?');">Supprimer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Vous n'avez pas encore créé de modèles de comptes rendus.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>