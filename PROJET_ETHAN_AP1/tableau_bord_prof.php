<?php
session_start();
include '_conf.php';
include 'fonctions.php';
if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) { header("Location: index.php");
exit(); }
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) { die("Erreur connexion BDD"); }
$stats_query = "SELECT
COUNT(*) as total_cr,
SUM(CASE WHEN vu = 1 THEN 1 ELSE 0 END) as cr_vus,
SUM(CASE WHEN vu = 0 THEN 1 ELSE 0 END) as cr_non_vus,
COUNT(DISTINCT cr.num_utilisateur) as nb_eleves_actifs
FROM cr
JOIN utilisateur u ON cr.num_utilisateur = u.num
WHERE u.type = 0";
$stats_result = mysqli_query($bdd, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
$cr_recents_query = "SELECT cr.*, u.nom, u.prenom
FROM cr
JOIN utilisateur u ON cr.num_utilisateur = u.num
ORDER BY cr.datetime DESC
LIMIT 10";
$cr_recents_result = mysqli_query($bdd, $cr_recents_query);
$eleves_actifs_query = "SELECT u.num, u.nom, u.prenom, COUNT(cr.num) as nb_cr,
MAX(cr.datetime) as dernier_cr
FROM utilisateur u
JOIN cr ON u.num = cr.num_utilisateur
WHERE u.type = 0
GROUP BY u.num
ORDER BY nb_cr DESC, dernier_cr DESC
LIMIT 5";
$eleves_actifs_result = mysqli_query($bdd, $eleves_actifs_query);
$cr_non_vus_query = "SELECT cr.*, u.nom, u.prenom
FROM cr
JOIN utilisateur u ON cr.num_utilisateur = u.num
WHERE cr.vu = 0
ORDER BY cr.datetime DESC
LIMIT 5";
$cr_non_vus_result = mysqli_query($bdd, $cr_non_vus_query);
$modeles_query = "SELECT * FROM modeles_cr
WHERE professeur_id = {$_SESSION['Sid']}
ORDER BY date_creation DESC";
$modeles_result = mysqli_query($bdd, $modeles_query);
function formatDateFrench($date) { if (!$date) return "Aucun";
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    $date_str = date('d F Y à H\hi', strtotime($date));
    $date_str = str_replace($english_months, $french_months, $date_str);
return $date_str; }
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Professeur</title>
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
    .stats-container { display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px; }
    .stat-card { flex: 1;
        min-width: 200px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        text-align: center; }
    .stat-value { font-size: 36px;
        font-weight: bold;
        margin: 10px 0; }
    .stat-label { color: #666;
        font-size: 14px; }
    .grid-container { display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 20px; }
    .cr-list { list-style: none;
        padding: 0; }
    .cr-item { padding: 15px;
        border-bottom: 1px solid #eee; }
    .cr-item:last-child { border-bottom: none; }
    .eleve-item { display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee; }
    .eleve-item:last-child { border-bottom: none; }
    .btn { display: inline-block;
        background: #007bff;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold; }
    .btn-secondary { background: #6c757d; }
    .btn-success { background: #28a745; }
    h2 { color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px; }
    .badge { display: inline-block;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: bold; }
    .badge-danger { background-color: #f8d7da;
        color: #721c24; }
    .badge-success { background-color: #d4edda;
        color: #155724; }
    .modele-item { background: #f8f9fa;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 10px; }
    </style>
    </head>
    <body>
        <?php afficherNavigation(); ?>
        <?php afficherMenuFonctionnalites(); ?>
        <div class="container">
            <h1>Tableau de bord Professeur</h1>
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="gestion_modeles.php" class="btn btn-success">Gérer les modèles</a>
            </div>
            <div class="stats-container">
                <div class="stat-card">
 <div class="stat-value"><?php echo $stats['total_cr'] ?: 0; ?></div>
                    <div class="stat-label">Comptes rendus total</div>
                </div>
                <div class="stat-card" style="background-color: #d4edda; color: #155724;">
 <div class="stat-value"><?php echo $stats['cr_vus'] ?: 0; ?></div>
                    <div class="stat-label">Comptes rendus consultés</div>
                </div>
                <div class="stat-card" style="background-color: #f8d7da; color: #721c24;">
 <div class="stat-value"><?php echo $stats['cr_non_vus'] ?: 0; ?></div>
                    <div class="stat-label">Comptes rendus non consultés</div>
                </div>
                <div class="stat-card">
 <div class="stat-value"><?php echo $stats['nb_eleves_actifs'] ?: 0; ?></div>
                    <div class="stat-label">Élèves actifs</div>
                </div>
            </div>
            <div class="grid-container">
                <div class="card">
                    <h2>Comptes rendus récents</h2>
 <?php if (mysqli_num_rows($cr_recents_result) > 0): ?>
                    <ul class="cr-list">
 <?php while ($cr = mysqli_fetch_assoc($cr_recents_result)): ?>
                        <li class="cr-item">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
 <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong>
                                    <div style="margin-top: 5px;">
 <?php echo strip_tags(substr($cr['description'], 0, 80)) . (strlen($cr['description']) > 80 ? '...' : ''); ?>
                                    </div>
                                    <div style="color: #666; font-size: 12px; margin-top: 5px;">
 <?php echo formatDateFrench($cr['datetime']); ?>
                                    </div>
                                </div>
                                <div>
 <span class="badge <?php echo $cr['vu'] ? 'badge-success' : 'badge-danger'; ?>">
 <?php echo $cr['vu'] ? 'Consulté' : 'Non consulté'; ?>
                                    </span>
 <a href="liste_cr_prof.php?view=<?php echo $cr['num']; ?>" class="btn" style="font-size: 12px; margin-left: 10px;">Voir</a>
                                </div>
                            </div>
                        </li>
 <?php endwhile; ?>
                    </ul>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="liste_cr_prof.php" class="btn">Voir tous les comptes rendus</a>
                    </div>
 <?php else: ?>
                    <p>Aucun compte rendu disponible.</p>
 <?php endif; ?>
                </div>
                <div class="card">
                    <h2>Élèves les plus actifs</h2>
 <?php if (mysqli_num_rows($eleves_actifs_result) > 0): ?>
                    <ul class="cr-list">
 <?php while ($eleve = mysqli_fetch_assoc($eleves_actifs_result)): ?>
                        <li class="eleve-item">
                            <div>
 <strong><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></strong>
                                <div style="color: #666; font-size: 14px;">
 Dernier CR: <?php echo formatDateFrench($eleve['dernier_cr']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-success">
 <?php echo $eleve['nb_cr']; ?> CR
                                </span>
 <a href="liste_cr_prof.php?eleve=<?php echo $eleve['num']; ?>" class="btn" style="font-size: 12px; margin-left: 10px;">
                                Voir les CR
                                </a>
                            </div>
                        </li>
 <?php endwhile; ?>
                    </ul>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="liste_eleves.php" class="btn">Voir tous les élèves</a>
                    </div>
 <?php else: ?>
                    <p>Aucun élève actif pour le moment.</p>
 <?php endif; ?>
                </div>
            </div>
            <div class="grid-container">
                <div class="card">
                    <h2>Comptes rendus non consultés</h2>
 <?php if (mysqli_num_rows($cr_non_vus_result) > 0): ?>
                    <ul class="cr-list">
 <?php while ($cr = mysqli_fetch_assoc($cr_non_vus_result)): ?>
                        <li class="cr-item">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
 <strong><?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?></strong>
                                    <div style="margin-top: 5px;">
 <?php echo strip_tags(substr($cr['description'], 0, 80)) . (strlen($cr['description']) > 80 ? '...' : ''); ?>
                                    </div>
                                    <div style="color: #666; font-size: 12px; margin-top: 5px;">
 <?php echo formatDateFrench($cr['datetime']); ?>
                                    </div>
                                </div>
                                <div>
 <a href="liste_cr_prof.php?view=<?php echo $cr['num']; ?>" class="btn" style="font-size: 12px;">
                                    Consulter
                                    </a>
                                </div>
                            </div>
                        </li>
 <?php endwhile; ?>
                    </ul>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="liste_cr_prof.php?sort=non_vu" class="btn">Voir tous les CR non consultés</a>
                    </div>
 <?php else: ?>
                    <p>Tous les comptes rendus ont été consultés. Bravo !</p>
 <?php endif; ?>
                </div>
                <div class="card">
                    <h2>Mes modèles de comptes rendus</h2>
 <?php if (mysqli_num_rows($modeles_result) > 0): ?>
 <?php while ($modele = mysqli_fetch_assoc($modeles_result)): ?>
                    <div class="modele-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
 <strong><?php echo htmlspecialchars($modele['titre']); ?></strong>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">
 Créé le <?php echo formatDateFrench($modele['date_creation']); ?>
                                </div>
                            </div>
                            <div>
 <a href="gestion_modeles.php?edit=<?php echo $modele['id']; ?>" class="btn" style="font-size: 12px;">
                                Modifier
                                </a>
                            </div>
                        </div>
                        <div style="margin-top: 10px; color: #666;">
 <?php echo htmlspecialchars($modele['description']); ?>
                        </div>
                    </div>
 <?php endwhile; ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="gestion_modeles.php" class="btn">Gérer mes modèles</a>
                    </div>
 <?php else: ?>
                    <p>Vous n'avez pas encore créé de modèles de comptes rendus.</p>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="gestion_modeles.php" class="btn">Créer mon premier modèle</a>
                    </div>
 <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
</html>