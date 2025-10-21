<?php
session_start();
include '_conf.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Statistiques générales
$query_eleves = "SELECT COUNT(*) as total FROM utilisateur WHERE type = 0";
$query_cr = "SELECT COUNT(*) as total FROM cr";
$query_cr_vus = "SELECT COUNT(*) as total FROM cr WHERE vu = 1";
$query_stages = "SELECT COUNT(*) as total FROM stage";
$query_commentaires = "SELECT COUNT(*) as total FROM commentaires";
$query_pieces_jointes = "SELECT COUNT(*) as total FROM pieces_jointes";

$result_eleves = mysqli_query($bdd, $query_eleves);
$result_cr = mysqli_query($bdd, $query_cr);
$result_cr_vus = mysqli_query($bdd, $query_cr_vus);
$result_stages = mysqli_query($bdd, $query_stages);
$result_commentaires = mysqli_query($bdd, $query_commentaires);
$result_pieces_jointes = mysqli_query($bdd, $query_pieces_jointes);

$eleves = mysqli_fetch_assoc($result_eleves);
$cr = mysqli_fetch_assoc($result_cr);
$cr_vus = mysqli_fetch_assoc($result_cr_vus);
$stages = mysqli_fetch_assoc($result_stages);
$commentaires = mysqli_fetch_assoc($result_commentaires);
$pieces_jointes = mysqli_fetch_assoc($result_pieces_jointes);

// Statistiques par élève
$query_eleves_cr = "SELECT u.prenom, u.nom, COUNT(cr.num) as nb_cr, 
                    SUM(CASE WHEN cr.vu = 1 THEN 1 ELSE 0 END) as nb_vus
                    FROM utilisateur u 
                    LEFT JOIN cr ON u.num = cr.num_utilisateur 
                    WHERE u.type = 0 
                    GROUP BY u.num 
                    ORDER BY nb_cr DESC";
$result_eleves_cr = mysqli_query($bdd, $query_eleves_cr);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques</title>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <h2>Statistiques du système</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $eleves['total']; ?></div>
            <div class="stat-label">Élèves inscrits</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stages['total']; ?></div>
            <div class="stat-label">Stages renseignés</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $cr['total']; ?></div>
            <div class="stat-label">Comptes rendus</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $cr_vus['total']; ?></div>
            <div class="stat-label">CR consultés</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $commentaires['total']; ?></div>
            <div class="stat-label">Commentaires</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $pieces_jointes['total']; ?></div>
            <div class="stat-label">Pièces jointes</div>
        </div>
    </div>
    
    <h3>Taux de consultation des CR</h3>
    <?php 
    $taux_consultation = $cr['total'] > 0 ? round(($cr_vus['total'] / $cr['total']) * 100, 2) : 0;
    ?>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <div style="background: #007bff; height: 30px; width: <?php echo $taux_consultation; ?>%; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
            <?php echo $taux_consultation; ?>%
        </div>
        <p style="text-align: center; margin-top: 10px;">
            <?php echo $cr_vus['total']; ?> / <?php echo $cr['total']; ?> CR consultés
        </p>
    </div>
    
    <h3>Activité des élèves</h3>
    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Nombre de CR</th>
                <th>CR consultés</th>
                <th>Taux de consultation</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($eleve = mysqli_fetch_assoc($result_eleves_cr)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></td>
                    <td><?php echo $eleve['nb_cr']; ?></td>
                    <td><?php echo $eleve['nb_vus']; ?></td>
                    <td>
                        <?php 
                        $taux = $eleve['nb_cr'] > 0 ? round(($eleve['nb_vus'] / $eleve['nb_cr']) * 100, 2) : 0;
                        echo $taux . '%';
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <p>
        <a href="export.php?type=statistiques&format=csv">📊 Exporter les statistiques (CSV)</a> | 
        <a href="export.php?type=statistiques&format=pdf">📊 Exporter les statistiques (PDF)</a>
    </p>
    
    <p><a href="accueil.php">Retour à l'accueil</a> | <a href="<?php echo $_SESSION['Stype'] == 1 ? 'tableau_bord_prof.php' : 'tableau_bord_eleve.php'; ?>">📊 Tableau de bord</a></p>
</body>
</html>