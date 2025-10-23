<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$type = $_SESSION['Stype'];
$nom_user = $_SESSION['Sprenom'] . ' ' . $_SESSION['Snom'];

// Marquer qu'on a vu le guide (optionnel)
if (isset($_GET['skip'])) {
    $_SESSION['guide_vu'] = true;
    header("Location: accueil.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Démarrage Rapide - Guide Interactif</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .welcome-box {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .welcome-box h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .welcome-box p {
            color: #333;
            font-size: 1.05em;
        }
        
        .sections {
            display: grid;
            gap: 20px;
        }
        
        .section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .section li {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }
        
        .section a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section a:hover {
            text-decoration: underline;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .quick-link-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .quick-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #dee2e6;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #ccc;
        }
        
        .tip {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #664d03;
        }
        
        .tip strong {
            color: #664d03;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .content {
                padding: 20px;
            }
            
            .section ul {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Bienvenue!</h1>
            <p>Voici votre guide interactif pour accéder à toutes les fonctionnalités</p>
        </div>
        
        <div class="content">
            <div class="welcome-box">
                <h2>Connecté en tant que:</h2>
                <p><?php echo htmlspecialchars($nom_user); ?> 
                   <?php echo ($type == 0) ? '<strong>(Étudiant)</strong>' : '<strong>(Enseignant)</strong>'; ?>
                </p>
            </div>
            
            <div class="sections">
                <?php if ($type == 0): ?>
                    <!-- ÉTUDIANT -->
                    <div class="section">
                        <h3>📝 Comptes Rendus</h3>
                        <p>Gérez vos comptes rendus facilement</p>
                        <div class="quick-links">
                            <a href="editer_cr.php" class="quick-link-btn">📝 Créer</a>
                            <a href="liste_cr.php" class="quick-link-btn">📋 Mes CRs</a>
                            <a href="export_cr.php" class="quick-link-btn">📥 Exporter</a>
                            <a href="recherche_cr.php" class="quick-link-btn">🔍 Rechercher</a>
                        </div>
                        <div class="tip">
                            <strong>💡 Astuce:</strong> Cliquez sur "Créer" pour commencer votre premier CR, ou consultez "Mes CRs" si vous en avez déjà créé.
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>🏢 Mon Espace Personnel</h3>
                        <p>Consultez vos informations et préférences</p>
                        <div class="quick-links">
                            <a href="mon_stage.php" class="quick-link-btn">🏢 Mon Stage</a>
                            <a href="perso.php" class="quick-link-btn">⚙️ Profil</a>
                            <a href="notifications.php" class="quick-link-btn">🔔 Notifications</a>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>🌟 Tout Accéder en Un Clic</h3>
                        <p>Le Hub central vous donne accès à TOUTES les fonctionnalités</p>
                        <div class="quick-links">
                            <a href="accueil.php" class="quick-link-btn" style="grid-column: 1/-1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 1.1em;">🎯 VOIR LE HUB COMPLET</a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- ENSEIGNANT -->
                    <div class="section">
                        <h3>📋 Révision des Comptes Rendus</h3>
                        <p>Examinez, validez et évaluez les CRs des étudiants</p>
                        <div class="quick-links">
                            <a href="liste_cr_prof.php" class="quick-link-btn">📋 Réviser</a>
                            <a href="validations_cr.php" class="quick-link-btn">✅ Valider</a>
                            <a href="export_cr.php" class="quick-link-btn">📥 Exporter</a>
                            <a href="recherche_cr.php" class="quick-link-btn">🔍 Rechercher</a>
                        </div>
                        <div class="tip">
                            <strong>💡 Astuce:</strong> Allez sur "Réviser" pour voir les CRs soumis, puis utilisez "Valider" pour appliquer des checklists.
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>👥 Gestion des Groupes & Élèves</h3>
                        <p>Organisez vos groupes d'étudiants</p>
                        <div class="quick-links">
                            <a href="gestion_groupes.php" class="quick-link-btn">👥 Groupes</a>
                            <a href="liste_eleves.php" class="quick-link-btn">👨‍🎓 Élèves</a>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>📊 Modèles, Rappels & Statistiques</h3>
                        <p>Configurez les templates et consultez les analytics</p>
                        <div class="quick-links">
                            <a href="gestion_modeles.php" class="quick-link-btn">📄 Modèles</a>
                            <a href="gestion_rappels.php" class="quick-link-btn">🔔 Rappels</a>
                            <a href="analytics_advanced.php" class="quick-link-btn">📊 Stats</a>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>🌟 Tout Accéder en Un Clic</h3>
                        <p>Le Hub central vous donne accès à TOUTES les fonctionnalités</p>
                        <div class="quick-links">
                            <a href="accueil.php" class="quick-link-btn" style="grid-column: 1/-1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 1.1em;">🎯 VOIR LE HUB COMPLET</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="section">
                    <h3>📚 Besoin d'Aide?</h3>
                    <p>Consultez le guide complet pour comprendre toutes les fonctionnalités</p>
                    <div class="quick-links">
                        <a href="GUIDE_ACCES_FONCTIONNALITES.md" class="quick-link-btn" style="grid-column: 1/-1;">📖 Voir le Guide Complet</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <a href="accueil.php" class="btn btn-secondary">← Retour à l'Accueil</a>
            <a href="accueil.php" class="btn btn-primary">🎯 Aller au Hub</a>
            <a href="?skip=1" class="btn btn-secondary">Ne plus afficher ce guide</a>
        </div>
    </div>
</body>
</html>