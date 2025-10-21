<?php
session_start();
include '_conf.php';
include 'fonctions.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$resultats = array();
$filtres_appliques = false;
$message_recherche = '';

// Récupération des groupes pour le filtre
$query_groupes = "SELECT * FROM groupes WHERE actif = 1 ORDER BY nom";
$result_groupes = mysqli_query($bdd, $query_groupes);
$groupes = array();
while ($row = mysqli_fetch_assoc($result_groupes)) {
    $groupes[] = $row;
}

// Récupération des professeurs pour le filtre
$query_profs = "SELECT num, nom, prenom FROM utilisateur WHERE type = 1 ORDER BY nom, prenom";
$result_profs = mysqli_query($bdd, $query_profs);
$professeurs = array();
while ($row = mysqli_fetch_assoc($result_profs)) {
    $professeurs[] = $row;
}

// Traitement de la recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['recherche'])) {
    $filtres = array();
    
    if (!empty($_POST['titre'] ?? $_GET['titre'] ?? '')) {
        $filtres['titre'] = $_POST['titre'] ?? $_GET['titre'];
    }
    
    if (!empty($_POST['statut'] ?? $_GET['statut'] ?? '')) {
        $filtres['statut'] = $_POST['statut'] ?? $_GET['statut'];
    }
    
    if (!empty($_POST['date_debut'] ?? $_GET['date_debut'] ?? '')) {
        $filtres['date_debut'] = $_POST['date_debut'] ?? $_GET['date_debut'];
    }
    
    if (!empty($_POST['date_fin'] ?? $_GET['date_fin'] ?? '')) {
        $filtres['date_fin'] = $_POST['date_fin'] ?? $_GET['date_fin'];
    }
    
    if (!empty($_POST['groupe_id'] ?? $_GET['groupe_id'] ?? '')) {
        $filtres['groupe_id'] = $_POST['groupe_id'] ?? $_GET['groupe_id'];
    }
    
    if (!empty($_POST['professeur_id'] ?? $_GET['professeur_id'] ?? '')) {
        $filtres['professeur_id'] = $_POST['professeur_id'] ?? $_GET['professeur_id'];
    }
    
    if ($_SESSION['Stype'] == 0) { // Si c'est un étudiant
        $filtres['utilisateur_id'] = $_SESSION['Sid'];
    }
    
    if (!empty($filtres)) {
        $resultats = rechercherCR($filtres);
        $filtres_appliques = true;
        
        if (count($resultats) > 0) {
            $message_recherche = count($resultats) . ' résultat(s) trouvé(s)';
        } else {
            $message_recherche = 'Aucun résultat ne correspond à votre recherche.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche et Filtrage des Comptes Rendus</title>
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
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .btn-search-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .btn-search-group button {
            flex: 1;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        .result-item {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .result-title {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }
        .result-meta {
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .result-description {
            color: #444;
            margin-bottom: 10px;
        }
        .result-actions {
            display: flex;
            gap: 10px;
        }
        .result-actions a {
            font-size: 12px;
            padding: 5px 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-brouillon {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .status-soumis {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-evalue {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-approuve {
            background-color: #d4edda;
            color: #155724;
        }
        h1 {
            color: #333;
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
            <h1>Recherche et Filtrage des Comptes Rendus</h1>
            <div>
                <a href="accueil.php" class="btn btn-secondary">← Accueil</a>
                <a href="<?php echo $_SESSION['Stype'] == 1 ? 'tableau_bord_prof.php' : 'tableau_bord_eleve.php'; ?>" class="btn btn-secondary">📊 Tableau de bord</a>
            </div>
        </div>
        
        <div class="card">
            <h2>Critères de Recherche</h2>
            <form method="POST" action="">
                <div class="search-form">
                    <div class="form-group">
                        <label for="titre">Titre du CR</label>
                        <input type="text" id="titre" name="titre" class="form-control" placeholder="Rechercher par titre..." 
                               value="<?php echo isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="">-- Tous les statuts --</option>
                            <option value="brouillon" <?php echo (isset($_POST['statut']) && $_POST['statut'] == 'brouillon') ? 'selected' : ''; ?>>Brouillon</option>
                            <option value="soumis" <?php echo (isset($_POST['statut']) && $_POST['statut'] == 'soumis') ? 'selected' : ''; ?>>Soumis</option>
                            <option value="evalue" <?php echo (isset($_POST['statut']) && $_POST['statut'] == 'evalue') ? 'selected' : ''; ?>>Évalué</option>
                            <option value="approuve" <?php echo (isset($_POST['statut']) && $_POST['statut'] == 'approuve') ? 'selected' : ''; ?>>Approuvé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_debut">Date Début</label>
                        <input type="date" id="date_debut" name="date_debut" class="form-control"
                               value="<?php echo isset($_POST['date_debut']) ? htmlspecialchars($_POST['date_debut']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin">Date Fin</label>
                        <input type="date" id="date_fin" name="date_fin" class="form-control"
                               value="<?php echo isset($_POST['date_fin']) ? htmlspecialchars($_POST['date_fin']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="groupe_id">Groupe</label>
                        <select id="groupe_id" name="groupe_id" class="form-control">
                            <option value="">-- Tous les groupes --</option>
                            <?php foreach ($groupes as $groupe): ?>
                                <option value="<?php echo $groupe['id']; ?>" <?php echo (isset($_POST['groupe_id']) && $_POST['groupe_id'] == $groupe['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($groupe['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($_SESSION['Stype'] == 1): ?>
                    <div class="form-group">
                        <label for="professeur_id">Professeur Évaluateur</label>
                        <select id="professeur_id" name="professeur_id" class="form-control">
                            <option value="">-- Tous les professeurs --</option>
                            <?php foreach ($professeurs as $prof): ?>
                                <option value="<?php echo $prof['num']; ?>" <?php echo (isset($_POST['professeur_id']) && $_POST['professeur_id'] == $prof['num']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-search-group">
                    <button type="submit" class="btn">Rechercher</button>
                    <a href="recherche_cr.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
        
        <?php if ($filtres_appliques): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message_recherche); ?>
            </div>
            
            <?php if (count($resultats) > 0): ?>
                <div class="card">
                    <h2>Résultats (<?php echo count($resultats); ?>)</h2>
                    <?php foreach ($resultats as $cr): ?>
                        <div class="result-item">
                            <div class="result-header">
                                <div>
                                    <div class="result-title"><?php echo htmlspecialchars($cr['titre'] ?? 'Compte rendu'); ?></div>
                                    <?php if (isset($cr['statut'])): ?>
                                        <span class="status-badge status-<?php echo strtolower($cr['statut']); ?>">
                                            <?php echo ucfirst($cr['statut']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="result-meta">
                                <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong> • 
                                <?php echo date('d/m/Y à H:i', strtotime($cr['datetime'])); ?>
                            </div>
                            <div class="result-description">
                                <?php echo nl2br(htmlspecialchars(substr($cr['description'], 0, 200))); ?>
                                <?php if (strlen($cr['description']) > 200): ?>...<?php endif; ?>
                            </div>
                            <div class="result-actions">
                                <a href="liste_cr.php?id=<?php echo $cr['num']; ?>" class="btn">Voir le CR</a>
                                <?php if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] == $_SESSION['Sid']): ?>
                                    <a href="editer_cr_new.php?id=<?php echo $cr['num']; ?>" class="btn">Modifier</a>
                                <?php endif; ?>
                                <?php if ($_SESSION['Stype'] == 1): ?>
                                    <a href="liste_cr_prof.php?id=<?php echo $cr['num']; ?>" class="btn">Évaluer</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
mysqli_close($bdd);
?>