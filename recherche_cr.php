<?php
session_start();
include '_conf.php';
include 'fonctions.php';
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
$query_groupes = "SELECT * FROM groupes WHERE actif = 1 ORDER BY nom";
$result_groupes = mysqli_query($bdd, $query_groupes);
$groupes = array();
while ($row = mysqli_fetch_assoc($result_groupes)) {
    $groupes[] = $row;
}
$query_profs = "SELECT num, nom, prenom FROM utilisateur WHERE type = 1 ORDER BY nom, prenom";
$result_profs = mysqli_query($bdd, $query_profs);
$professeurs = array();
while ($row = mysqli_fetch_assoc($result_profs)) {
    $professeurs[] = $row;
}
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
    if ($_SESSION['Stype'] == 0) {
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
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="cr.css">
</head>

<body>
    <?php afficherHeaderNavigation(); ?>
    <div class="container">
        <h1>Recherche et Filtrage des Comptes Rendus</h1>
        <div class="card">
            <h2>Critères de Recherche</h2>
            <form method="POST" action="">
                <div class="search-form">
                    <div class="form-group">
                        <label for="titre">Titre du CR</label>
                        <input type="text" id="titre" name="titre" class="form-control"
                            placeholder="Rechercher par titre..."
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
                                <?php echo nl2br(htmlspecialchars(strip_tags(substr($cr['description'], 0, 200)))); ?>
                                <?php if (strlen($cr['description']) > 200): ?>...<?php endif; ?>
                            </div>
                            <div class="result-actions">
                                <a href="liste_cr.php?detail=<?php echo $cr['num']; ?>" class="btn">Voir le CR</a>
                                <?php if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] == $_SESSION['Sid']): ?>
                                    <a href="editer_cr.php?id=<?php echo $cr['num']; ?>" class="btn">Modifier</a>
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
    <?php include 'footer.php'; ?>
<?php
mysqli_close($bdd);
?>