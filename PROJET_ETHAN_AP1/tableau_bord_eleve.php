<?php
session_start();
include '_conf.php';
include 'fonctions.php';

// Vérification que l'utilisateur est connecté et est un élève
if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 0) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = $_SESSION['Sid'];

// Récupération des statistiques des CR
$stats_query = "SELECT 
                COUNT(*) as total_cr,
                SUM(CASE WHEN vu = 1 THEN 1 ELSE 0 END) as cr_vus,
                SUM(CASE WHEN vu = 0 THEN 1 ELSE 0 END) as cr_non_vus,
                MAX(datetime) as dernier_cr
                FROM cr 
                WHERE num_utilisateur = $user_id";
$stats_result = mysqli_query($bdd, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Récupération des derniers commentaires reçus
$commentaires_query = "SELECT c.*, cr.description as cr_description, u.nom, u.prenom 
                      FROM commentaires c 
                      JOIN cr ON c.cr_id = cr.num 
                      JOIN utilisateur u ON c.professeur_id = u.num 
                      WHERE cr.num_utilisateur = $user_id 
                      ORDER BY c.date_creation DESC 
                      LIMIT 5";
$commentaires_result = mysqli_query($bdd, $commentaires_query);

// Récupération des derniers CR
$derniers_cr_query = "SELECT * FROM cr 
                     WHERE num_utilisateur = $user_id 
                     ORDER BY datetime DESC 
                     LIMIT 5";
$derniers_cr_result = mysqli_query($bdd, $derniers_cr_query);

// Fonction pour formater les dates
function formatDateFrench($date) {
    if (!$date) return "Aucun";
    
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $date_str = date('d F Y à H\hi', strtotime($date));
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Élève</title>
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
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .cr-list {
            list-style: none;
            padding: 0;
        }
        .cr-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .cr-item:last-child {
            border-bottom: none;
        }
        .comment-item {
            background: #f9f9f9;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn-secondary {
            background: #6c757d;
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
            <h1>Tableau de bord</h1>
            <div>
                <a href="accueil.php" class="btn btn-secondary">Retour à l'accueil</a>
                <a href="editer_cr.php" class="btn">Nouveau compte rendu</a>
            </div>
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
                <div class="stat-label">Dernier compte rendu</div>
                <div style="font-size: 16px; margin-top: 10px;">
                    <?php echo formatDateFrench($stats['dernier_cr']); ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Mes derniers comptes rendus</h2>
            <?php if (mysqli_num_rows($derniers_cr_result) > 0): ?>
                <ul class="cr-list">
                    <?php while ($cr = mysqli_fetch_assoc($derniers_cr_result)): ?>
                        <li class="cr-item">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong><?php echo htmlspecialchars(substr($cr['description'], 0, 100)) . (strlen($cr['description']) > 100 ? '...' : ''); ?></strong>
                                    <div style="color: #666; font-size: 14px;">
                                        <?php echo formatDateFrench($cr['datetime']); ?>
                                    </div>
                                </div>
                                <div>
                                    <span style="margin-right: 15px; <?php echo $cr['vu'] ? 'color: green;' : 'color: red;'; ?>">
                                        <?php echo $cr['vu'] ? '✅ Consulté' : '❌ Non consulté'; ?>
                                    </span>
                                    <a href="liste_cr.php?detail=<?php echo $cr['num']; ?>" class="btn" style="font-size: 12px;">Voir détails</a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="liste_cr.php" class="btn">Voir tous mes comptes rendus</a>
                </div>
            <?php else: ?>
                <p>Vous n'avez pas encore créé de compte rendu.</p>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="editer_cr.php" class="btn">Créer mon premier compte rendu</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Derniers commentaires des professeurs</h2>
            <?php if (mysqli_num_rows($commentaires_result) > 0): ?>
                <?php while ($commentaire = mysqli_fetch_assoc($commentaires_result)): ?>
                    <div class="comment-item">
                        <div style="margin-bottom: 10px;">
                            <strong><?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?></strong>
                            <span style="color: #666; font-size: 12px; margin-left: 10px;">
                                <?php echo formatDateFrench($commentaire['date_creation']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 10px; font-style: italic; color: #666;">
                            Sur le CR: <?php echo htmlspecialchars(substr($commentaire['cr_description'], 0, 50)) . '...'; ?>
                        </div>
                        <div>
                            <?php echo htmlspecialchars($commentaire['commentaire']); ?>
                        </div>
                        <div style="text-align: right; margin-top: 10px;">
                            <a href="liste_cr.php?detail=<?php echo $commentaire['cr_id']; ?>" style="color: #007bff; text-decoration: none;">
                                Voir le compte rendu complet →
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Vous n'avez pas encore reçu de commentaires.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>