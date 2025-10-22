<?php
session_start();
include '_conf.php';
include 'fonctions.php';
if (!isset($_SESSION['Sid'])) { header("Location: index.php");
exit(); }
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) { die("Erreur connexion BDD"); }
$cr_id = intval($_GET['id'] ?? 0);
if (!$cr_id) { header("Location: liste_cr.php");
exit(); }
$query_cr = "SELECT * FROM cr WHERE num = $cr_id";
$result_cr = mysqli_query($bdd, $query_cr);
$cr = mysqli_fetch_assoc($result_cr);
if (!$cr) { header("Location: liste_cr.php");
exit(); }
if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] != $_SESSION['Sid']) { header("Location: liste_cr.php");
exit(); }
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_validation'])) { $checklist_item_id = intval($_POST['checklist_item_id']);
if (isset($_POST['complete'])) { if (marquerChecklistComplete($cr_id, $checklist_item_id)) { $message = "Élément de checklist marqué comme complété."; } else { $error = "Erreur lors de la mise à jour."; } } }
$validations = getValidationsCR($cr_id);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Compte Rendu</title>
    <style>
    body { font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 20px;
        background-color: #f5f5f5; }
    .container { max-width: 1000px;
        margin: 0 auto; }
    .header { display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px; }
    .card { background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px; }
    .alert { padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border-left: 4px solid; }
    .alert-success { background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb; }
    .alert-danger { background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb; }
    .alert-info { background-color: #d1ecf1;
        color: #0c5460;
        border-color: #bee5eb; }
    .btn { display: inline-block;
        background: #007bff;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        border: none;
        cursor: pointer; }
    .btn-secondary { background: #6c757d; }
    .btn:hover { opacity: 0.9; }
    .checklist-item { background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px; }
    .checklist-item.complete { border-left-color: #28a745;
        background: #f0f8f5; }
    .checklist-item.obligatoire { border-left-color: #dc3545; }
    .checklist-header { display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px; }
    .checklist-checkbox { width: 20px;
        height: 20px;
        cursor: pointer; }
    .checklist-label { flex: 1;
        font-weight: bold;
        margin: 0; }
    .checklist-badge { display: inline-block;
        background: #dc3545;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold; }
    .checklist-description { color: #666;
        font-size: 13px;
        margin-top: 8px;
        padding-left: 30px; }
    h1, h2 { color: #333; }
    h2 { border-bottom: 2px solid #eee;
        padding-bottom: 10px; }
    .validation-summary { background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px; }
    .validation-summary-item { display: flex;
        justify-content: space-between;
        margin-bottom: 8px; }
    .progress-bar { background-color: #e9ecef;
        height: 20px;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 10px; }
    .progress-fill { background-color: #28a745;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: bold; }
    </style>
    </head>
    <body>
        <?php afficherNavigation(); ?>
        <?php afficherMenuFonctionnalites(); ?>
        <div class="container">
            <h1>Validation du Compte Rendu</h1>
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
                <h2>Informations du Compte Rendu</h2>
 <p><strong>Titre:</strong> <?php echo htmlspecialchars($cr['titre'] ?? 'N/A'); ?></p>
 <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($cr['date'])); ?></p>
 <p><strong>Description:</strong> <?php echo strip_tags(substr($cr['description'], 0, 200)); ?></p>
            </div>
 <?php if (count($validations) > 0):?>
 <?php
$completed_count = 0;
$total_count = count($validations);
foreach ($validations as $validation) { if ($validation['complete']) { $completed_count++; } }
$percentage = ($total_count > 0) ? round(($completed_count / $total_count) * 100, 0) : 0;
            ?>
            <div class="card">
                <h2>Checklist de Validation</h2>
                <div class="validation-summary">
                    <div class="validation-summary-item">
                        <span>Éléments complétés:</span>
 <strong><?php echo $completed_count; ?>/<?php echo $total_count; ?></strong>
                    </div>
                    <div class="progress-bar">
 <div class="progress-fill" style="width: <?php echo $percentage; ?>%">
 <?php echo $percentage; ?>%
                    </div>
                </div>
            </div>
            <form method="POST" action="">
 <?php foreach ($validations as $validation): ?>
 <div class="checklist-item <?php echo $validation['complete'] ? 'complete' : ''; ?> <?php echo $validation['obligatoire'] ? 'obligatoire' : ''; ?>">
                <div class="checklist-header">
 <input type="checkbox" class="checklist-checkbox" id="item_<?php echo $validation['checklist_item_id']; ?>"
 <?php echo $validation['complete'] ? 'checked' : ''; ?>
 onchange="this.form.checklist_item_id.value = <?php echo $validation['checklist_item_id']; ?>; this.form.complete.value = this.checked ? 1 : 0; this.form.update_validation.click();">
 <label for="item_<?php echo $validation['checklist_item_id']; ?>" class="checklist-label">
 <?php echo htmlspecialchars($validation['item_texte']); ?>
                    </label>
 <?php if ($validation['obligatoire']): ?>
                    <span class="checklist-badge">Obligatoire</span>
 <?php endif; ?>
                </div>
            </div>
 <?php endforeach; ?>
            <input type="hidden" name="checklist_item_id" value="">
            <input type="hidden" name="complete" value="">
            <button type="submit" name="update_validation" style="display: none;"></button>
        </form>
    </div>
 <?php else: ?>
    <div class="alert alert-info">
        Aucune checklist n'est associée à ce compte rendu pour le moment.
    </div>
 <?php endif; ?>
</div>
</body>
</html>
<?php
mysqli_close($bdd);
?>