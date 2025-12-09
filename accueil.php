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
$user_id = intval($_SESSION['Sid']);

$stats = [];
$stage_data = null;
if ($type == 0) {
    $stats_query = "SELECT COUNT(*) as total_cr, SUM(CASE WHEN vu = 1 THEN 1 ELSE 0 END) as cr_vus FROM cr WHERE num_utilisateur = $user_id";
    $stats_result = mysqli_query($bdd, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    
    $stage_query = "SELECT s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom FROM utilisateur u LEFT JOIN stage s ON u.num_stage = s.num LEFT JOIN tuteur t ON s.num_tuteur = t.num WHERE u.num = $user_id";
    $stage_result = mysqli_query($bdd, $stage_query);
    $stage_data = mysqli_fetch_assoc($stage_result);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏠 Accueil</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    
    <?php afficherHeaderPage(($type == 0 ? '👨‍🎓' : '👨‍🏫'), 'Bienvenue ' . htmlspecialchars(explode(' ', $nom_user)[0]), ($type == 0 ? 'Gérez vos comptes rendus de stage' : 'Suivez les comptes rendus de vos étudiants')); ?>

    <div class="container accueil-container">
        <?php if ($type == 0): ?>

            <section class="content-section">
                <h2>📊 Vos statistiques</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_cr'] ?: 0; ?></div>
                        <div class="stat-label">CRs créés</div>
                    </div>
                    <div class="stat-item stat-active">
                        <div class="stat-number"><?php echo $stats['cr_vus'] ?: 0; ?></div>
                        <div class="stat-label">Consultés</div>
                    </div>
                </div>
            </section>

            <section class="content-section">
                <h2>⚡ Actions rapides</h2>
                <div class="grid">
                    <a href="editer_cr.php" class="card primary" title="Créer un nouveau compte rendu de stage">
                        <div class="card-icon">✏️</div>
                        <div class="card-title">Nouveau CR</div>
                        <div class="card-desc">Démarrer</div>
                    </a>
                    <a href="liste_cr.php" class="card info" title="Consulter tous vos comptes rendus">
                        <div class="card-icon">📋</div>
                        <div class="card-title">Mes CRs</div>
                        <div class="card-desc"><?php echo $stats['total_cr'] ?: 0; ?> CRs</div>
                    </a>
                    <a href="recherche_cr.php" class="card secondary" title="Rechercher des comptes rendus">
                        <div class="card-icon">🔍</div>
                        <div class="card-title">Rechercher</div>
                        <div class="card-desc">Trouver</div>
                    </a>
                </div>
            </section>

            <section class="content-section">
                <h2>🏢 Mon Stage</h2>
                <?php if ($stage_data && $stage_data['nom']): ?>
                    <div class="stage-info">
                        <div class="stage-card">
                            <div class="stage-label">Entreprise</div>
                            <div class="stage-company"><?php echo htmlspecialchars($stage_data['nom']); ?></div>
                            <div class="stage-location"><?php echo htmlspecialchars($stage_data['CP'] ?? '') . ' ' . htmlspecialchars($stage_data['ville'] ?? ''); ?></div>
                        </div>
                        <div class="stage-card stage-tutor">
                            <div class="stage-label">Tuteur</div>
                            <div class="stage-name"><?php echo htmlspecialchars(($stage_data['tuteur_prenom'] ?? '') . ' ' . ($stage_data['tuteur_nom'] ?? '')); ?></div>
                            <div class="stage-info-text">Superviseur</div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="grid grid-2">
                    <a href="mon_stage.php" class="card orange" title="Consulter ou modifier vos informations de stage">
                        <div class="card-icon">🏢</div>
                        <div class="card-title">Infos Stage</div>
                        <div class="card-desc">Détails</div>
                    </a>
                    <a href="perso.php" class="card dark" title="Gérer votre profil personnel">
                        <div class="card-icon">👤</div>
                        <div class="card-title">Mon Profil</div>
                        <div class="card-desc">Compte</div>
                    </a>
                </div>
            </section>

            <section class="content-section">
                <h2>📚 Ressources & Aide</h2>
                <div class="resource-grid">
                    <a href="tutoriel.php" class="resource-link" title="Accéder au guide d'utilisation">
                        <span class="resource-icon">📚</span>
                        <div class="resource-text">
                            <strong>Tutoriel</strong>
                            <p>Guide complet</p>
                        </div>
                    </a>
                    <a href="notifications.php" class="resource-link" title="Consulter vos notifications">
                        <span class="resource-icon">🔔</span>
                        <div class="resource-text">
                            <strong>Notifications</strong>
                            <p>Messages</p>
                        </div>
                    </a>
                </div>
            </section>

        <?php else: ?>

            <section class="content-section">
                <h2>⚡ Actions rapides</h2>
                <div class="grid">
                    <a href="liste_cr_prof.php" class="card danger" title="Consulter et réviser les comptes rendus">
                        <div class="card-icon">📋</div>
                        <div class="card-title">Réviser CRs</div>
                        <div class="card-desc">Examiner</div>
                    </a>
                    <a href="validations_cr.php" class="card success" title="Valider les comptes rendus">
                        <div class="card-icon">✅</div>
                        <div class="card-title">Validations</div>
                        <div class="card-desc">Valider</div>
                    </a>
                    <a href="recherche_cr.php" class="card secondary" title="Rechercher des comptes rendus">
                        <div class="card-icon">🔍</div>
                        <div class="card-title">Rechercher</div>
                        <div class="card-desc">Trouver</div>
                    </a>
                </div>
            </section>

            <section class="content-section">
                <h2>👥 Gestion des étudiants</h2>
                <div class="grid">
                    <a href="gestion_groupes.php" class="card teal" title="Gérer les groupes d'étudiants">
                        <div class="card-icon">👥</div>
                        <div class="card-title">Groupes</div>
                        <div class="card-desc">Gérer</div>
                    </a>
                    <a href="liste_eleves.php" class="card cyan" title="Voir la liste des étudiants">
                        <div class="card-icon">👨‍🎓</div>
                        <div class="card-title">Élèves</div>
                        <div class="card-desc">Voir</div>
                    </a>
                    <a href="gestion_rappels.php" class="card warning" title="Gérer les rappels de deadline">
                        <div class="card-icon">⏰</div>
                        <div class="card-title">Rappels</div>
                        <div class="card-desc">Gérer</div>
                    </a>
                </div>
            </section>

            <section class="content-section">
                <h2>📊 Statistiques</h2>
                <div class="resource-grid">
                    <a href="tableau_bord_prof.php" class="resource-link resource-primary" title="Voir le tableau de bord avec les statistiques">
                        <span class="resource-icon">📊</span>
                        <div class="resource-text">
                            <strong>Tableau de bord</strong>
                            <p>Statistiques détaillées</p>
                        </div>
                    </a>
                </div>
            </section>

            <section class="content-section">
                <h2>📚 Ressources & Aide</h2>
                <div class="resource-grid">
                    <a href="tutoriel.php" class="resource-link" title="Accéder au guide d'utilisation">
                        <span class="resource-icon">📚</span>
                        <div class="resource-text">
                            <strong>Tutoriel</strong>
                            <p>Guide complet</p>
                        </div>
                    </a>
                    <a href="notifications.php" class="resource-link" title="Consulter vos notifications">
                        <span class="resource-icon">🔔</span>
                        <div class="resource-text">
                            <strong>Notifications</strong>
                            <p>Messages</p>
                        </div>
                    </a>
                </div>
            </section>
        <?php endif; ?>

    </div>
    <?php include 'footer.php'; ?>
<?php
mysqli_close($bdd);
?>