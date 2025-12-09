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
$message = '';
$error = '';
$professeur_id = intval($_SESSION['Sid']);
$query_groupes = "SELECT * FROM groupes WHERE professeur_responsable_id = $professeur_id AND actif = 1 ORDER BY nom";
$result_groupes = mysqli_query($bdd, $query_groupes);
$groupes = array();
while ($row = mysqli_fetch_assoc($result_groupes)) {
    $groupes[] = $row;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rappel'])) {
    $groupe_id = intval($_POST['groupe_id']);
    $date_limite = mysqli_real_escape_string($bdd, $_POST['date_limite']);
    $titre = mysqli_real_escape_string($bdd, $_POST['titre']);
    $description = mysqli_real_escape_string($bdd, $_POST['description']);
    if (empty($titre) || empty($date_limite)) {
        $error = "Le titre et la date limite sont obligatoires.";
    } else {
        if (creerRappelSoumission($groupe_id, $date_limite, $titre, $description, $_SESSION['Sid'])) {
            $message = "Le rappel a été créé avec succès.";
        } else {
            $error = "Erreur lors de la création du rappel.";
        }
    }
}
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $rappel_id = intval($_GET['delete']);
    $prof_id = intval($_SESSION['Sid']);
    $query = "DELETE FROM rappels_soumission WHERE id = $rappel_id AND professeur_id = $prof_id";
    if (mysqli_query($bdd, $query)) {
        $message = "Le rappel a été supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression du rappel.";
    }
}
if (isset($_GET['deactivate']) && !empty($_GET['deactivate'])) {
    $rappel_id = intval($_GET['deactivate']);
    $prof_id = intval($_SESSION['Sid']);
    $query = "UPDATE rappels_soumission SET actif = 0 WHERE id = $rappel_id AND professeur_id = $prof_id";
    if (mysqli_query($bdd, $query)) {
        $message = "Le rappel a été désactivé.";
    } else {
        $error = "Erreur lors de la désactivation du rappel.";
    }
}
$prof_id = intval($_SESSION['Sid']);
$query_rappels = "SELECT r.*, g.nom as groupe_nom FROM rappels_soumission r
JOIN groupes g ON r.groupe_id = g.id
WHERE r.professeur_id = $prof_id
ORDER BY r.date_limite DESC";
$result_rappels = mysqli_query($bdd, $query_rappels);
$rappels = array();
while ($row = mysqli_fetch_assoc($result_rappels)) {
    $rappels[] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rappels de Soumission</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="gestion.css">
</head>

<body>
    <?php afficherHeaderNavigation(); ?>
    <div class="container">
        <h1>Gestion des Rappels de Soumission</h1>
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
            <h2>Créer un Nouveau Rappel</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="groupe_id">Groupe *</label>
                        <select id="groupe_id" name="groupe_id" class="form-control" required>
                            <option value="">-- Sélectionner un groupe --</option>
                            <?php foreach ($groupes as $groupe): ?>
                                <option value="<?php echo $groupe['id']; ?>">
                                    <?php echo htmlspecialchars($groupe['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_limite">Date Limite *</label>
                        <input type="datetime-local" id="date_limite" name="date_limite" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="titre">Titre du Rappel *</label>
                    <input type="text" id="titre" name="titre" class="form-control" required
                        placeholder="Ex: Deadline pour les rapports de semaine 1">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control"
                        placeholder="Message détaillé pour les étudiants..."></textarea>
                </div>
                <button type="submit" name="add_rappel" class="btn btn-success">Créer le Rappel</button>
            </form>
        </div>
        <div class="card">
            <h2>Mes Rappels (<?php echo count($rappels); ?>)</h2>
            <?php if (count($rappels) > 0): ?>
                <?php foreach ($rappels as $rappel): ?>
                    <?php
                    $est_expiry = strtotime($rappel['date_limite']) < time();
                    $est_inactif = !$rappel['actif'];
                    $classes = 'rappel-item';
                    if ($est_expiry)
                        $classes .= ' expired';
                    if ($est_inactif)
                        $classes .= ' inactive';
                    ?>
                    <div class="<?php echo $classes; ?>">
                        <div class="rappel-header">
                            <div>
                                <div class="rappel-title"><?php echo htmlspecialchars($rappel['titre']); ?></div>
                                <div class="rappel-meta">
                                    <strong><?php echo htmlspecialchars($rappel['groupe_nom']); ?></strong> •
                                    <?php echo date('d/m/Y à H:i', strtotime($rappel['date_limite'])); ?>
                                </div>
                            </div>
                            <span
                                class="rappel-status <?php echo $est_inactif ? 'inactive' : ($est_expiry ? 'expired' : 'active'); ?>">
                                <?php echo $est_inactif ? 'Inactif' : ($est_expiry ? 'Expiré' : 'Actif'); ?>
                            </span>
                        </div>
                        <?php if (!empty($rappel['description'])): ?>
                            <div class="rappel-description">
                                <?php echo htmlspecialchars($rappel['description']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="rappel-actions">
                            <?php if ($rappel['actif']): ?>
                                <a href="gestion_rappels.php?deactivate=<?php echo $rappel['id']; ?>"
                                    class="btn btn-warning">Désactiver</a>
                            <?php endif; ?>
                            <a href="gestion_rappels.php?delete=<?php echo $rappel['id']; ?>" class="btn btn-danger"
                                onclick="return confirm('Êtes-vous sûr ?');">Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun rappel créé pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
<?php
mysqli_close($bdd);
?>