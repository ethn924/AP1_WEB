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
if (isset($_GET['restore']) && !empty($_GET['restore'])) { $version_id = intval($_GET['restore']);
    if (restaurerVersionCR($version_id, $cr_id, $_SESSION['Sid'])) { $message = "La version a été restaurée avec succès.";
        $result_cr = mysqli_query($bdd, $query_cr);
$cr = mysqli_fetch_assoc($result_cr); } else { $error = "Erreur lors de la restauration de la version."; } }
$versions = getVersionsCR($cr_id);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique du Compte Rendu</title>
    <style>
    body { font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 20px;
        background-color: #f5f5f5; }
    .container { max-width: 1200px;
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
    .btn-danger { background: #dc3545; }
    .btn-info { background: #17a2b8; }
    .btn:hover { opacity: 0.9; }
    .version-item { background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px; }
    .version-item.current { border-left-color: #28a745;
        background: #f0f8f5; }
    .version-header { display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px; }
    .version-title { font-weight: bold;
        font-size: 16px;
        color: #333; }
    .version-badge { display: inline-block;
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold; }
    .version-badge.current { background: #28a745; }
    .version-meta { color: #666;
        font-size: 12px;
        margin-bottom: 10px; }
    .version-note { background: #fff9e6;
        padding: 10px;
        border-radius: 3px;
        margin-bottom: 10px;
        color: #444;
        font-size: 13px; }
    .version-actions { display: flex;
        gap: 10px;
        margin-top: 10px; }
    .version-actions a { font-size: 12px;
        padding: 6px 12px; }
    h1 { color: #333; }
    h2 { color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px; }
    .current-info { background: #d4edda;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
        color: #155724;
        border: 1px solid #c3e6cb; }
    </style>
    </head>
    <body>
        <?php afficherNavigation(); ?>
        <?php afficherMenuFonctionnalites(); ?>
        <div class="container">
            <h1>Historique du Compte Rendu</h1>
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
                <h2>Version Actuelle</h2>
                <div class="current-info">
 <strong>Titre:</strong> <?php echo htmlspecialchars($cr['titre'] ?? 'Compte rendu');?><br>
 <strong>Dernière modification:</strong> <?php echo date('d/m/Y à H:i', strtotime($cr['datetime'])); ?><br>
 <strong>Version numéro:</strong> <?php echo $cr['num_version']; ?>
                </div>
            </div>
            <div class="card">
 <h2>Historique des Versions (<?php echo count($versions); ?>)</h2>
 <?php if (count($versions) > 0): ?>
 <?php foreach ($versions as $version): ?>
 <div class="version-item <?php echo $version['numero_version'] == $cr['num_version'] ? 'current' : ''; ?>">
                <div class="version-header">
                    <div>
 <div class="version-title">Version <?php echo $version['numero_version']; ?></div>
                        <div class="version-meta">
 par <strong><?php echo htmlspecialchars($version['prenom'] . ' ' . $version['nom']); ?></strong> 
 le <?php echo date('d/m/Y à H:i', strtotime($version['date_creation'])); ?>
                        </div>
                    </div>
 <span class="version-badge <?php echo $version['numero_version'] == $cr['num_version'] ? 'current' : ''; ?>">
 <?php echo $version['numero_version'] == $cr['num_version'] ? 'Actuelle' : 'Ancienne'; ?>
                    </span>
                </div>
 <?php if (!empty($version['note_version'])): ?>
                <div class="version-note">
 <strong>Note de version:</strong> <?php echo htmlspecialchars($version['note_version']); ?>
                </div>
 <?php endif; ?>
                <div class="version-meta">
 <strong>Titre:</strong> <?php echo htmlspecialchars($version['titre'] ?? 'N/A'); ?><br>
 <strong>Description:</strong> <?php echo strip_tags(substr($version['description'], 0, 100)); ?>...
                </div>
                <div class="version-actions">
 <?php if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] == $_SESSION['Sid']): ?>
 <a href="historique_cr.php?id=<?php echo $cr_id; ?>&restore=<?php echo $version['id']; ?>" 
                    class="btn btn-info"
                    onclick="return confirm('Êtes-vous sûr de vouloir restaurer cette version ?');">
                    Restaurer cette version
                    </a>
 <?php endif; ?>
 <a href="?id=<?php echo $cr_id; ?>&view=<?php echo $version['id']; ?>" class="btn">Consulter</a>
                </div>
            </div>
 <?php endforeach; ?>
 <?php else: ?>
            <p>Aucune version antérieure trouvée. Ceci est la première version du CR.</p>
 <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($bdd);
?>