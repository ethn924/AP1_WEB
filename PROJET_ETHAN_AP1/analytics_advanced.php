<?php
session_start();
include '_conf.php';
include 'fonctions.php';

// Vérification que l'utilisateur est un professeur
if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Récupération des groupes du professeur
$query_groupes = "SELECT * FROM groupes WHERE professeur_responsable_id = {$_SESSION['Sid']} ORDER BY nom";
$result_groupes = mysqli_query($bdd, $query_groupes);
$groupes = array();
while ($row = mysqli_fetch_assoc($result_groupes)) {
    $groupes[] = $row;
}

// Sélection du groupe
$groupe_id = isset($_GET['groupe_id']) && !empty($_GET['groupe_id']) ? intval($_GET['groupe_id']) : null;

if ($groupe_id) {
    // Vérifier que le groupe appartient bien au professeur
    $query_check = "SELECT * FROM groupes WHERE id = $groupe_id AND professeur_responsable_id = {$_SESSION['Sid']}";
    $result_check = mysqli_query($bdd, $query_check);
    if (mysqli_num_rows($result_check) == 0) {
        $groupe_id = null;
    }
}

// Calcul des analytics
$analytics = calculerAnalyticsGroupe($groupe_id);

// Récupération des données par statut
if ($groupe_id) {
    $groupe_condition = " AND c.groupe_id = $groupe_id";
} else {
    $groupe_condition = "";
}

$query_stats = "SELECT 
    s.statut,
    COUNT(*) as nombre
FROM cr c 
LEFT JOIN statuts_cr s ON c.num = s.cr_id 
WHERE c.archivé = 0 $groupe_condition
GROUP BY s.statut";

$result_stats = mysqli_query($bdd, $query_stats);
$stats_by_status = array();
$total_crs = 0;
while ($row = mysqli_fetch_assoc($result_stats)) {
    if ($row['statut']) {
        $stats_by_status[$row['statut']] = $row['nombre'];
    }
    $total_crs += $row['nombre'];
}

// Récupération du délai moyen d'évaluation
$query_delai = "SELECT AVG(TIMESTAMPDIFF(DAY, s.date_soumission, s.date_evaluation)) as delai_moyen
FROM cr c 
LEFT JOIN statuts_cr s ON c.num = s.cr_id 
WHERE c.archivé = 0 AND s.date_soumission IS NOT NULL AND s.date_evaluation IS NOT NULL $groupe_condition";

$result_delai = mysqli_query($bdd, $query_delai);
$row_delai = mysqli_fetch_assoc($result_delai);
$delai_moyen = $row_delai['delai_moyen'] ?? 0;

// Récupération des statistiques par mois
$query_monthly = "SELECT 
    DATE_FORMAT(c.date, '%Y-%m') as mois,
    COUNT(*) as total,
    SUM(CASE WHEN s.statut IN ('soumis', 'evalue', 'approuve') THEN 1 ELSE 0 END) as soumis,
    SUM(CASE WHEN s.statut IN ('evalue', 'approuve') THEN 1 ELSE 0 END) as evalues,
    SUM(CASE WHEN s.statut = 'approuve' THEN 1 ELSE 0 END) as approuves
FROM cr c 
LEFT JOIN statuts_cr s ON c.num = s.cr_id 
WHERE c.archivé = 0 $groupe_condition
GROUP BY DATE_FORMAT(c.date, '%Y-%m')
ORDER BY mois DESC
LIMIT 12";

$result_monthly = mysqli_query($bdd, $query_monthly);
$monthly_stats = array();
while ($row = mysqli_fetch_assoc($result_monthly)) {
    $monthly_stats[] = $row;
}

// Récupération des rappels pour ce groupe
$rappels_query = "";
if ($groupe_id) {
    $rappels_query = "SELECT * FROM rappels_soumission WHERE groupe_id = $groupe_id AND actif = 1 ORDER BY date_limite ASC";
    $result_rappels = mysqli_query($bdd, $rappels_query);
    $rappels = array();
    while ($row = mysqli_fetch_assoc($result_rappels)) {
        $rappels[] = $row;
    }
} else {
    $rappels = array();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Avancées</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
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
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box.soumis {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-box.evalues {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-box.approuves {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
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
        .btn:hover {
            opacity: 0.9;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .table-stats {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-stats th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
        }
        .table-stats td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .table-stats tr:hover {
            background-color: #f8f9fa;
        }
        .percentage-bar {
            background-color: #e9ecef;
            height: 20px;
            border-radius: 3px;
            overflow: hidden;
            margin: 5px 0;
        }
        .percentage-fill {
            background-color: #007bff;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        h1, h2 {
            color: #333;
        }
        h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .rappels-list {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
        .rappel-item {
            padding: 8px 0;
            border-bottom: 1px solid #ffe699;
        }
        .rappel-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Analytics Avancées des Comptes Rendus</h1>
            <div>
                <a href="accueil.php" class="btn btn-secondary">← Accueil</a>
                <a href="tableau_bord_prof.php" class="btn btn-secondary">📊 Tableau de bord</a>
            </div>
        </div>
        
        <div class="card">
            <div class="filter-group">
                <label for="groupe_id">Sélectionner un groupe:</label>
                <select id="groupe_id" onchange="window.location.href='analytics_advanced.php?groupe_id=' + this.value;">
                    <option value="">-- Tous les groupes --</option>
                    <?php foreach ($groupes as $groupe): ?>
                        <option value="<?php echo $groupe['id']; ?>" <?php echo ($groupe_id == $groupe['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($groupe['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if ($analytics): ?>
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $analytics['total_cr']; ?></div>
                    <div class="stat-label">Comptes Rendus Totaux</div>
                </div>
                <div class="stat-box soumis">
                    <div class="stat-value"><?php echo $analytics['cr_soumis']; ?></div>
                    <div class="stat-label">Soumis (<?php echo $analytics['taux_soumission']; ?>%)</div>
                </div>
                <div class="stat-box evalues">
                    <div class="stat-value"><?php echo $analytics['cr_evalues']; ?></div>
                    <div class="stat-label">Évalués (<?php echo $analytics['taux_evaluation']; ?>%)</div>
                </div>
                <div class="stat-box approuves">
                    <div class="stat-value"><?php echo $analytics['cr_approuves']; ?></div>
                    <div class="stat-label">Approuvés</div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($rappels)): ?>
            <div class="card">
                <h2>Rappels de Soumission Actifs</h2>
                <div class="rappels-list">
                    <?php foreach ($rappels as $rappel): ?>
                        <div class="rappel-item">
                            <strong><?php echo htmlspecialchars($rappel['titre']); ?></strong><br>
                            <small>Deadline: <?php echo date('d/m/Y à H:i', strtotime($rappel['date_limite'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Répartition par Statut</h2>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <div class="card">
            <h2>Historique Mensuel</h2>
            <table class="table-stats">
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th>Total</th>
                        <th>Soumis</th>
                        <th>Évalués</th>
                        <th>Approuvés</th>
                        <th>Taux Soumission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['mois']); ?></td>
                            <td><?php echo $stat['total']; ?></td>
                            <td><?php echo $stat['soumis']; ?></td>
                            <td><?php echo $stat['evalues']; ?></td>
                            <td><?php echo $stat['approuves']; ?></td>
                            <td>
                                <?php 
                                $taux = ($stat['total'] > 0) ? round(($stat['soumis'] / $stat['total']) * 100, 2) : 0;
                                ?>
                                <div class="percentage-bar">
                                    <div class="percentage-fill" style="width: <?php echo $taux; ?>%">
                                        <?php echo $taux; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>Informations Supplémentaires</h2>
            <p><strong>Délai moyen d'évaluation:</strong> <?php echo round($delai_moyen, 1); ?> jour(s)</p>
        </div>
    </div>
    
    <script>
        // Graphique des statuts
        <?php
        $labels = array();
        $data = array();
        $colors = array();
        $statusColors = array(
            'brouillon' => '#e2e3e5',
            'soumis' => '#ffc107',
            'evalue' => '#17a2b8',
            'approuve' => '#28a745'
        );
        
        foreach ($stats_by_status as $status => $count) {
            $labels[] = ucfirst($status);
            $data[] = $count;
            $colors[] = $statusColors[$status] ?? '#007bff';
        }
        ?>
        
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: <?php echo json_encode($colors); ?>,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php
mysqli_close($bdd);
?>