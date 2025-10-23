<?php
session_start();
include '_conf.php';
include 'fonctions.php';
if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}
$modele_id = intval($_GET['id'] ?? 0);
if (!$modele_id) {
    header("Location: gestion_modeles.php");
    exit();
}
$query_modele = "SELECT * FROM modeles_cr WHERE id = $modele_id AND professeur_id = {$_SESSION['Sid']}";
$result_modele = mysqli_query($bdd, $query_modele);
$modele = mysqli_fetch_assoc($result_modele);
if (!$modele) {
    header("Location: gestion_modeles.php");
    exit();
}
$message = '';
$error = '';
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $item_id = intval($_GET['delete']);
    $query = "DELETE FROM checklists_modeles WHERE id = $item_id AND modele_id = $modele_id";
    if (mysqli_query($bdd, $query)) {
        $message = "L'élément a été supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_texte = mysqli_real_escape_string($bdd, $_POST['item_texte']);
    $ordre = intval($_POST['ordre']);
    $obligatoire = isset($_POST['obligatoire']) ? 1 : 0;
    $description_aide = mysqli_real_escape_string($bdd, $_POST['description_aide']);
    if (empty($item_texte)) {
        $error = "Le texte de l'élément est obligatoire.";
    } else {
        if (ajouterChecklistModele($modele_id, $item_texte, $ordre, $obligatoire, $description_aide)) {
            $message = "L'élément a été ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout de l'élément.";
        }
    }
}
$checklist = getChecklistModele($modele_id);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de la Checklist du Modèle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1000px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            box-sizing: border-box;
        }

        .form-inline {
            display: grid;
            grid-template-columns: 1fr 100px;
            gap: 10px;
        }

        .form-inline .form-group {
            margin-bottom: 0;
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

        .btn:hover {
            opacity: 0.9;
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

        .checklist-item {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .checklist-item.obligatoire {
            border-left-color: #dc3545;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-title {
            font-weight: bold;
            flex: 1;
        }

        .item-ordre {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 10px;
        }

        .item-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 5px;
        }

        .item-description {
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
        }

        .item-actions a {
            font-size: 12px;
            padding: 5px 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            margin: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        h1,
        h2 {
            color: #333;
        }

        h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <div class="container">
        <h1>Gestion de la Checklist</h1>
        <div class="card">
            <h2>Modèle: <?php echo htmlspecialchars($modele['titre']); ?></h2>
            <p><?php echo htmlspecialchars($modele['description']); ?></p>
        </div>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <div class="card">
            <h2>Ajouter un Élément de Checklist</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="item_texte">Texte de l'élément *</label>
                    <input type="text" id="item_texte" name="item_texte" class="form-control" required
                        placeholder="Ex: Vérifier la grammaire et l'orthographe">
                </div>
                <div class="form-group">
                    <label for="ordre">Ordre d'affichage</label>
                    <input type="number" id="ordre" name="ordre" class="form-control"
                        value="<?php echo count($checklist) + 1; ?>" min="1">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="obligatoire" name="obligatoire" value="1">
                        Cet élément est obligatoire
                    </label>
                </div>
                <div class="form-group">
                    <label for="description_aide">Description d'aide (optionnel)</label>
                    <textarea id="description_aide" name="description_aide" class="form-control"
                        placeholder="Aide pour l'étudiant lors de la vérification..."></textarea>
                </div>
                <button type="submit" name="add_item" class="btn btn-success">Ajouter l'élément</button>
            </form>
        </div>
        <div class="card">
            <h2>Éléments de Checklist (<?php echo count($checklist); ?>)</h2>
            <?php if (count($checklist) > 0): ?>
                <?php foreach ($checklist as $item): ?>
                    <div class="checklist-item <?php echo $item['obligatoire'] ? 'obligatoire' : ''; ?>">
                        <div class="item-header">
                            <div style="flex: 1;">
                                <span class="item-titre"><?php echo htmlspecialchars($item['item_texte']); ?></span>
                                <?php if ($item['obligatoire']): ?>
                                    <span class="item-badge">Obligatoire</span>
                                <?php endif; ?>
                            </div>
                            <span class="item-ordre">#<?php echo $item['ordre']; ?></span>
                        </div>
                        <?php if (!empty($item['description_aide'])): ?>
                            <div class="item-description">
                                <strong>Aide:</strong> <?php echo htmlspecialchars($item['description_aide']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="item-actions">
                            <a href="gestion_modeles_checklists.php?id=<?php echo $modele_id; ?>&delete=<?php echo $item['id']; ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet élément ?');">
                                Supprimer
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun élément de checklist. Ajoutez-en un ci-dessus.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php
mysqli_close($bdd);
?>