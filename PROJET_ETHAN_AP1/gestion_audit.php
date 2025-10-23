<?php
session_start();
include '_conf.php';
include 'fonctions.php';
include 'api_audit.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) { die("Erreur BDD"); }

$action = $_GET['action'] ?? 'historique';
$filtres = [
    'utilisateur_id' => $_GET['utilisateur_id'] ?? '',
    'action' => $_GET['action_filter'] ?? '',
    'entite' => $_GET['entite'] ?? '',
    'date_debut' => $_GET['date_debut'] ?? '',
    'date_fin' => $_GET['date_fin'] ?? ''
];

$historique = obtenirHistoriqueAudit($filtres);
$stats = obtenirStatistiquesAudit();

$query_users = "SELECT num, nom, prenom FROM utilisateur ORDER BY nom";
$result_users = mysqli_query($bdd, $query_users);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Audit - Traçabilité</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { margin-bottom: 30px; }
        h1 { color: #333; margin-bottom: 10px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab-btn { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 14px; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab-btn.active { color: #007bff; border-color: #007bff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-value { font-size: 28px; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 12px; margin-top: 5px; }
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; color: #666; margin-bottom: 5px; font-weight: bold; }
        .filter-group input, .filter-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .btn { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .table-container { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; font-weight: bold; color: #333; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:hover { background: #f9f9f9; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 50px; font-size: 11px; font-weight: bold; }
        .badge-create { background: #d4edda; color: #155724; }
        .badge-update { background: #cfe2ff; color: #084298; }
        .badge-delete { background: #f8d7da; color: #842029; }
        .badge-view { background: #e2e3e5; color: #383d41; }
        .chart-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .chart { height: 300px; margin-top: 15px; }
        .json-data { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 11px; max-height: 150px; overflow-y: auto; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .close { float: right; font-size: 24px; cursor: pointer; color: #999; }
        .close:hover { color: #000; }
    </style>
</head>
<body>
<?php afficherNavigation(); ?>
<div class="container">
    <div class="header">
        <h1>🔐 Gestion Audit & Traçabilité</h1>
        <p style="color: #666;">Historique complet de toutes les actions dans le système</p>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('historique')">📋 Historique</button>
        <button class="tab-btn" onclick="switchTab('stats')">📊 Statistiques</button>
        <button class="tab-btn" onclick="switchTab('versions')">📝 Versions CR</button>
        <button class="tab-btn" onclick="switchTab('export')">💾 Exporter</button>
    </div>

    <!-- TAB HISTORIQUE -->
    <div id="historique" class="tab-content active">
        <div class="filters">
            <form method="GET" style="display: grid; gap: 15px;">
                <input type="hidden" name="action" value="historique">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Utilisateur</label>
                        <select name="utilisateur_id">
                            <option value="">Tous les utilisateurs</option>
                            <?php while ($user = mysqli_fetch_assoc($result_users)): ?>
                                <option value="<?php echo $user['num']; ?>" <?php echo ($filtres['utilisateur_id'] == $user['num']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Type d'action</label>
                        <select name="action_filter">
                            <option value="">Toutes les actions</option>
                            <option value="CREATE" <?php echo ($filtres['action'] == 'CREATE') ? 'selected' : ''; ?>>Création</option>
                            <option value="UPDATE" <?php echo ($filtres['action'] == 'UPDATE') ? 'selected' : ''; ?>>Modification</option>
                            <option value="DELETE" <?php echo ($filtres['action'] == 'DELETE') ? 'selected' : ''; ?>>Suppression</option>
                            <option value="VIEW" <?php echo ($filtres['action'] == 'VIEW') ? 'selected' : ''; ?>>Consultation</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Entité</label>
                        <select name="entite">
                            <option value="">Toutes les entités</option>
                            <option value="cr" <?php echo ($filtres['entite'] == 'cr') ? 'selected' : ''; ?>>Compte Rendu</option>
                            <option value="commentaire" <?php echo ($filtres['entite'] == 'commentaire') ? 'selected' : ''; ?>>Commentaire</option>
                            <option value="groupe" <?php echo ($filtres['entite'] == 'groupe') ? 'selected' : ''; ?>>Groupe</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Date début</label>
                        <input type="date" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut']); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin']); ?>">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn" style="margin-bottom: 0;">Filtrer</button>
                        <a href="gestion_audit.php" class="btn" style="margin-left: 5px; text-decoration: none; text-align: center; margin-bottom: 0;">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Entité</th>
                        <th>ID</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($historique) > 0): ?>
                        <?php foreach ($historique as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['date_action'])); ?></td>
                                <td><?php echo htmlspecialchars(($log['prenom'] ?? 'N/A') . ' ' . ($log['nom'] ?? '')); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($log['action']); ?>" style="text-transform: uppercase; font-size: 10px;">
                                        <?php echo $log['action']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($log['entite']); ?></strong></td>
                                <td><?php echo $log['entite_id'] ?? '-'; ?></td>
                                <td><?php echo htmlspecialchars(substr($log['description'] ?? '', 0, 50)); ?></td>
                                <td><?php echo htmlspecialchars($log['adresse_ip']); ?></td>
                                <td>
                                    <button class="btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)" style="font-size: 11px;">Voir</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: #999;">Aucun enregistrement trouvé</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB STATS -->
    <div id="stats" class="tab-content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_actions']); ?></div>
                <div class="stat-label">Total d'actions enregistrées</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['nb_utilisateurs_actifs']; ?></div>
                <div class="stat-label">Utilisateurs actifs</div>
            </div>
        </div>

        <div class="chart-container">
            <h3>📊 Actions par type</h3>
            <table style="width: 100%; margin-top: 15px;">
                <tr style="border-bottom: 2px solid #ddd;">
                    <th style="text-align: left;">Type</th>
                    <th style="text-align: right;">Nombre</th>
                    <th style="text-align: right;">Pourcentage</th>
                </tr>
                <?php 
                    $total = array_sum($stats['actions_par_type'] ?? []);
                    foreach (($stats['actions_par_type'] ?? []) as $type => $count): 
                        $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><span class="badge badge-<?php echo strtolower($type); ?>"><?php echo $type; ?></span></td>
                        <td style="text-align: right;"><strong><?php echo $count; ?></strong></td>
                        <td style="text-align: right;"><?php echo $pct; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="chart-container">
            <h3>📈 Actions des 30 derniers jours</h3>
            <table style="width: 100%; margin-top: 15px;">
                <tr style="border-bottom: 2px solid #ddd;">
                    <th style="text-align: left;">Date</th>
                    <th style="text-align: right;">Nombre d'actions</th>
                </tr>
                <?php foreach (($stats['actions_par_jour'] ?? []) as $date => $count): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($date)); ?></td>
                        <td style="text-align: right;"><strong><?php echo $count; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- TAB VERSIONS -->
    <div id="versions" class="tab-content">
        <div class="filters">
            <form method="GET">
                <input type="hidden" name="action" value="historique">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>ID Compte Rendu</label>
                        <input type="number" name="cr_version_filter" placeholder="Entrez l'ID">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn" style="margin-bottom: 0;">Rechercher</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CR ID</th>
                        <th>Version</th>
                        <th>Auteur</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Caractères</th>
                        <th>Taille</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $query_versions = "SELECT v.*, u.nom, u.prenom FROM versions_cr_audit v
                                         LEFT JOIN utilisateur u ON v.utilisateur_id = u.num
                                         ORDER BY v.date_creation DESC LIMIT 100";
                        $result_versions = mysqli_query($bdd, $query_versions);
                        while ($v = mysqli_fetch_assoc($result_versions)):
                    ?>
                        <tr>
                            <td><?php echo $v['cr_id']; ?></td>
                            <td><strong>#<?php echo $v['numero_version']; ?></strong></td>
                            <td><?php echo htmlspecialchars($v['prenom'] . ' ' . $v['nom']); ?></td>
                            <td><span class="badge badge-update"><?php echo ucfirst($v['type_modification']); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['date_creation'])); ?></td>
                            <td>+<?php echo $v['nb_caracteres_ajoutes']; ?> / -<?php echo $v['nb_caracteres_supprimes']; ?></td>
                            <td><?php echo round($v['taille_fichier'] / 1024, 2); ?> KB</td>
                            <td><?php echo htmlspecialchars(substr($v['note_version'] ?? '', 0, 30)); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB EXPORT -->
    <div id="export" class="tab-content">
        <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
            <h2>💾 Exporter l'audit</h2>
            <p style="color: #666; margin: 20px 0;">Générez un rapport d'audit au format PDF ou Excel</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                <a href="export_audit.php?format=pdf" class="btn" style="padding: 15px; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    📄 Exporter en PDF
                </a>
                <a href="export_audit.php?format=csv" class="btn" style="padding: 15px; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    📊 Exporter en CSV
                </a>
                <form method="POST" action="export_audit.php" style="display: contents;">
                    <button type="submit" name="action" value="cleanup" class="btn btn-danger" onclick="return confirm('Supprimer les audits de plus d\'un an?')" style="padding: 15px; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        🗑️ Nettoyer (>1 an)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="margin-bottom: 20px;">Détails complets</h2>
        <div id="detailsContent"></div>
    </div>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function showDetails(data) {
    let html = `
        <table style="width: 100%; font-size: 12px;">
            <tr><td style="font-weight: bold;">ID Action:</td><td>${data.id}</td></tr>
            <tr><td style="font-weight: bold;">Date:</td><td>${new Date(data.date_action).toLocaleString('fr-FR')}</td></tr>
            <tr><td style="font-weight: bold;">Utilisateur:</td><td>${data.prenom} ${data.nom}</td></tr>
            <tr><td style="font-weight: bold;">Action:</td><td>${data.action}</td></tr>
            <tr><td style="font-weight: bold;">Entité:</td><td>${data.entite}</td></tr>
            <tr><td style="font-weight: bold;">ID Entité:</td><td>${data.entite_id || 'N/A'}</td></tr>
            <tr><td style="font-weight: bold;">IP:</td><td>${data.adresse_ip}</td></tr>
            <tr><td style="font-weight: bold; vertical-align: top;">Description:</td><td>${data.description || 'N/A'}</td></tr>
        </table>
    `;
    
    if (data.anciennes_donnees) {
        html += `<h4 style="margin-top: 20px;">Anciennes données:</h4><div class="json-data">${JSON.stringify(JSON.parse(data.anciennes_donnees), null, 2)}</div>`;
    }
    if (data.nouvelles_donnees) {
        html += `<h4 style="margin-top: 20px;">Nouvelles données:</h4><div class="json-data">${JSON.stringify(JSON.parse(data.nouvelles_donnees), null, 2)}</div>`;
    }
    
    document.getElementById('detailsContent').innerHTML = html;
    document.getElementById('detailsModal').classList.add('show');
}

function closeModal() {
    document.getElementById('detailsModal').classList.remove('show');
}
</script>

</body>
</html>
<?php mysqli_close($bdd); ?>