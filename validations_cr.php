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
$cr_id = intval($_GET['id'] ?? 0);
if (!$cr_id) {
    header("Location: liste_cr_prof.php");
    exit();
}
$query_cr = "SELECT c.*, u.nom, u.prenom FROM cr c LEFT JOIN utilisateur u ON c.num_utilisateur = u.num WHERE c.num = $cr_id";
$result_cr = mysqli_query($bdd, $query_cr);
$cr = mysqli_fetch_assoc($result_cr);
if (!$cr) {
    header("Location: liste_cr_prof.php");
    exit();
}
$message = '';
$error = '';
$validation_existante = getValidationCR($cr_id);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_cr'])) {
    $valide = isset($_POST['approuver']) ? 1 : 0;
    $commentaire = trim($_POST['commentaire_validation'] ?? '');
    if (validerCR($cr_id, $_SESSION['Sid'], $valide, $commentaire)) {
        $message = $valide ? "Compte rendu approuvé." : "Compte rendu rejeté.";
        $validation_existante = getValidationCR($cr_id);
        creerNotification($cr['num_utilisateur'], 'validation', 'Validation du CR', $message, 'liste_cr.php');
    } else {
        $error = "Erreur lors de la validation.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Compte Rendu</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="gestion.css">
    <style>
        .validation-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .validation-status { font-size: 14px; color: #666; margin: 10px 0; }
        .validation-status.approuve { color: #28a745; font-weight: bold; }
        .validation-status.rejete { color: #dc3545; font-weight: bold; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-approuver { background: #28a745; color: white; }
        .btn-approuver:hover { background: #218838; }
        .btn-rejeter { background: #dc3545; color: white; }
        .btn-rejeter:hover { background: #c82333; }
        .btn-annuler { background: #6c757d; color: white; }
        .btn-annuler:hover { background: #5a6268; }
        .cr-preview { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0; }
        .cr-info { margin: 10px 0; }
    </style>
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <div class="container">
        <div class="cr-preview">
            <h1>✅ Validation du Compte Rendu</h1>
            <div class="cr-info">
                <strong>Étudiant:</strong> <?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?>
            </div>
            <div class="cr-info">
                <strong>Titre:</strong> <?php echo htmlspecialchars($cr['titre'] ?? 'Sans titre'); ?>
            </div>
            <div class="cr-info">
                <strong>Date:</strong> <?php echo $cr['date'] ? date('d/m/Y', strtotime($cr['date'])) : 'N/A'; ?>
            </div>
            <div class="cr-info">
                <strong>Statut:</strong> <?php $stat = getStatutCR($cr_id); echo $stat ? ucfirst($stat['statut']) : 'Brouillon'; ?>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($validation_existante): ?>
            <div class="validation-form">
                <h2>État actuel de la validation</h2>
                <div class="validation-status <?php echo $validation_existante['valide'] ? 'approuve' : 'rejete'; ?>">
                    <?php echo $validation_existante['valide'] ? '✅ APPROUVÉ' : '❌ REJETÉ'; ?>
                </div>
                <?php if ($validation_existante['commentaire_validation']): ?>
                    <p><strong>Commentaire:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($validation_existante['commentaire_validation'])); ?></p>
                <?php endif; ?>
                <p style="font-size: 12px; color: #999;">
                    Validé le <?php echo date('d/m/Y à H:i', strtotime($validation_existante['date_validation'])); ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="POST" class="validation-form">
            <h2>Validation</h2>
            <div class="form-group">
                <label for="commentaire">Commentaire de validation (optionnel):</label>
                <textarea id="commentaire" name="commentaire_validation" rows="5" placeholder="Écrivez vos remarques..."></textarea>
            </div>
            <div class="form-group">
                <button type="submit" name="valider_cr" value="approuver" class="btn btn-approuver" onclick="document.querySelector('input[name=approuver]').value = '1';">
                    ✅ Approuver
                </button>
                <button type="submit" name="valider_cr" value="rejeter" class="btn btn-rejeter" onclick="document.querySelector('input[name=approuver]').value = '0';">
                    ❌ Rejeter
                </button>
                <input type="hidden" name="approuver" value="">
                <a href="liste_cr_prof.php" class="btn btn-annuler">← Retour</a>
            </div>
        </form>
    </div>
    <?php include 'footer.php'; ?>
<?php
mysqli_close($bdd);
?>
