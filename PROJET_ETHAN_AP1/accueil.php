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

$type = $_SESSION['Stype'];
$nom_user = $_SESSION['Sprenom'] . ' ' . $_SESSION['Snom'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏠 Accueil</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .user-info {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info strong {
            color: #667eea;
        }
        
        .retour-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
        }
        
        .retour-btn:hover {
            background: #c82333;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            font-size: 1.8em;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            opacity: 0.95;
        }
        
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .card-title {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        
        .card-desc {
            font-size: 0.85em;
            opacity: 0.9;
        }
        
        .card.primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        
        .card.success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }
        
        .card.danger {
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
        }
        
        .card.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333;
        }
        
        .card.info {
            background: linear-gradient(135deg, #17a2b8 0%, #0c5460 100%);
        }
        
        .card.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
        }
        
        .card.dark {
            background: linear-gradient(135deg, #343a40 0%, #1d2124 100%);
        }
        
        .card.pink {
            background: linear-gradient(135deg, #e83e8c 0%, #bd2130 100%);
        }
        
        .card.teal {
            background: linear-gradient(135deg, #20c997 0%, #1aa179 100%);
        }
        
        .card.cyan {
            background: linear-gradient(135deg, #0dcaf0 0%, #0aa8cc 100%);
        }
        
        .card.orange {
            background: linear-gradient(135deg, #fd7e14 0%, #e06c00 100%);
        }
        
        .no-link {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .no-link:hover {
            transform: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tooltip {
            font-size: 0.75em;
            opacity: 0.8;
            margin-top: 8px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .section h2 {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🏠 Accueil</h1>
            <p>Accédez à toutes les fonctionnalités de l'application</p>
            <div class="user-info">
                <span><strong>Connecté en tant que:</strong> <?php echo htmlspecialchars($nom_user); ?> 
                    <?php echo ($type == 0) ? '(Étudiant)' : '(Enseignant)'; ?></span>
                <a href="tableau_bord_<?php echo ($type == 0) ? 'eleve' : 'prof'; ?>.php" class="retour-btn">📊 Tableau de bord</a>
            </div>
        </div>
        
        <?php if ($type == 0): ?>
            <!-- ÉTUDIANT -->
            
            <!-- Section Comptes Rendus -->
            <div class="section">
                <h2>📝 Gestion des Comptes Rendus</h2>
                <div class="grid">
                    <a href="editer_cr.php" class="card primary">
                        <div class="card-icon">📝</div>
                        <div class="card-title">Créer un CR</div>
                        <div class="card-desc">Rédiger un nouveau compte rendu</div>
                    </a>
                    <a href="liste_cr.php" class="card info">
                        <div class="card-icon">📋</div>
                        <div class="card-title">Mes CRs</div>
                        <div class="card-desc">Consulter tous vos comptes rendus</div>
                    </a>
                    <a href="export_cr.php" class="card success">
                        <div class="card-icon">📥</div>
                        <div class="card-title">Exporter</div>
                        <div class="card-desc">Télécharger en PDF/Word/Excel</div>
                    </a>
                    <a href="recherche_cr.php" class="card secondary">
                        <div class="card-icon">🔍</div>
                        <div class="card-title">Rechercher</div>
                        <div class="card-desc">Recherche avancée de CRs</div>
                    </a>
                </div>
            </div>
            
            <!-- Section Stage & Profil -->
            <div class="section">
                <h2>🏢 Mon Espace</h2>
                <div class="grid">
                    <a href="mon_stage.php" class="card orange">
                        <div class="card-icon">🏢</div>
                        <div class="card-title">Mon Stage</div>
                        <div class="card-desc">Gérer les infos de stage</div>
                    </a>
                    <a href="perso.php" class="card secondary">
                        <div class="card-icon">⚙️</div>
                        <div class="card-title">Mon Profil</div>
                        <div class="card-desc">Paramètres personnels</div>
                    </a>
                    <a href="notifications.php" class="card pink">
                        <div class="card-icon">🔔</div>
                        <div class="card-title">Notifications</div>
                        <div class="card-desc">Voir vos notifications</div>
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- ENSEIGNANT -->
            
            <!-- Section Révision CRs -->
            <div class="section">
                <h2>📋 Révision des Comptes Rendus</h2>
                <div class="grid">
                    <a href="liste_cr_prof.php" class="card danger">
                        <div class="card-icon">📋</div>
                        <div class="card-title">Réviser CRs</div>
                        <div class="card-desc">Examiner les CRs soumis</div>
                    </a>
                    <a href="validations_cr.php" class="card info">
                        <div class="card-icon">✅</div>
                        <div class="card-title">Validations</div>
                        <div class="card-desc">Valider avec checklist</div>
                    </a>
                    <a href="export_cr.php" class="card success">
                        <div class="card-icon">📥</div>
                        <div class="card-title">Exporter CRs</div>
                        <div class="card-desc">Télécharger les CRs</div>
                    </a>
                    <a href="recherche_cr.php" class="card secondary">
                        <div class="card-icon">🔍</div>
                        <div class="card-title">Rechercher</div>
                        <div class="card-desc">Recherche avancée</div>
                    </a>
                </div>
            </div>
            
            <!-- Section Gestion Groupes -->
            <div class="section">
                <h2>👥 Gestion des Groupes & Élèves</h2>
                <div class="grid">
                    <a href="gestion_groupes.php" class="card teal">
                        <div class="card-icon">👥</div>
                        <div class="card-title">Groupes</div>
                        <div class="card-desc">Créer & gérer les groupes</div>
                    </a>
                    <a href="liste_eleves.php" class="card cyan">
                        <div class="card-icon">👨‍🎓</div>
                        <div class="card-title">Élèves</div>
                        <div class="card-desc">Consulter les élèves</div>
                    </a>
                </div>
            </div>
            
            <!-- Section Modèles & Templates -->
            <div class="section">
                <h2>📄 Modèles & Checklists</h2>
                <div class="grid">
                    <a href="gestion_modeles.php" class="card warning">
                        <div class="card-icon">📄</div>
                        <div class="card-title">Modèles</div>
                        <div class="card-desc">Gérer les modèles CR</div>
                        <div class="tooltip">Cliquez pour gérer aussi les checklists</div>
                    </a>
                </div>
            </div>
            
            <!-- Section Rappels & Stats -->
            <div class="section">
                <h2>📊 Rappels & Statistiques</h2>
                <div class="grid">
                    <a href="gestion_rappels.php" class="card pink">
                        <div class="card-icon">🔔</div>
                        <div class="card-title">Rappels</div>
                        <div class="card-desc">Gérer les rappels</div>
                    </a>
                    <a href="analytics_advanced.php" class="card secondary">
                        <div class="card-icon">📊</div>
                        <div class="card-title">Statistiques</div>
                        <div class="card-desc">Graphiques & analytiques</div>
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
        
        <!-- Section Commune -->
        <div class="section">
            <h2>🔗 Navigation Rapide</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="tableau_bord_<?php echo ($type == 0) ? 'eleve' : 'prof'; ?>.php" class="retour-btn" style="background: #28a745;">
                    📊 Tableau de bord
                </a>
                <a href="notifications.php" class="retour-btn" style="background: #e83e8c;">
                    🔔 Notifications
                </a>
                <a href="index.php" class="retour-btn" style="background: #6c757d;">
                    🚪 Déconnexion
                </a>
            </div>
        </div>
        
    </div>
</body>
</html>
<?php
mysqli_close($bdd);
?>