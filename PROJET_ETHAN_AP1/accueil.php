<?php
session_start();
include '_conf.php';
include 'fonctions.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
global $bdd;
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Gestion de la connexion
if (isset($_POST['send_con'])) {
    $login = mysqli_real_escape_string($bdd, $_POST['login']);
    $motdepasse = $_POST['motdepasse'] ?? '';

    $query = "SELECT * FROM utilisateur WHERE login = '$login'";
    $result = mysqli_query($bdd, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Vérifier le mot de passe avec MD5
        $password_valid = isset($user['motdepasse']) && ($user['motdepasse'] === md5($motdepasse));
        
        if ($password_valid) {
            // Vérifier si l'email est vérifié
            if ($user['email_valide'] == 1) {
                $_SESSION['Sid'] = $user['num'];
                $_SESSION['Slogin'] = $user['login'];
                $_SESSION['Sprenom'] = $user['prenom'];
                $_SESSION['Snom'] = $user['nom'];
                $_SESSION['Stype'] = $user['type'];
            } else {
                // Email non vérifié
                $_SESSION['user_id_verification'] = $user['num'];
                $_SESSION['email_verification'] = $user['email'];
                header("Location: verifier_email.php");
                exit();
            }
        } else {
            $error = "Identifiants incorrects";
        }
    } else {
        $error = "Identifiants incorrects";
    }
}

// Gestion de la déconnexion
if (isset($_POST['deco'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Si l'utilisateur est connecté, charger les notifications
if (isset($_SESSION['Sid'])) {
    $notifications = getNotificationsNonLues($_SESSION['Sid']);
}

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
    <meta name="description" content="Accueil - Plateforme de Suivi de Stages">
    <title>Accueil - Plateforme de Suivi de Stages</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-container h2 {
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5568d3;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .error {
            color: #e74c3c;
            padding: 12px;
            background: #fadbd8;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .dashboard {
            width: 100%;
            background: white;
            min-height: 100vh;
        }
        
        .dashboard header {
            background: #667eea;
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content h1 {
            font-size: 28px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            background: white;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            width: auto;
        }
        
        .dashboard main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .section h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .list-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .list-item-title {
            font-weight: 600;
            color: #333;
        }
        
        .list-item-meta {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .menu-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
        }
        
        .menu-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .menu-section h4 {
            color: #555;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .menu-section ul {
            list-style: none;
            padding: 0;
        }
        
        .menu-section li {
            margin: 10px 0;
        }
        
        .menu-section a {
            display: inline-block;
            padding: 10px 15px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .menu-section a:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['Sid'])): ?>
    <!-- Page de connexion -->
    <div class="login-container">
        <h2>Plateforme de Suivi de Stages</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="accueil.php" method="POST">
            <div class="form-group">
                <label for="login">Login :</label>
                <input type="text" id="login" name="login" required>
            </div>
            
            <div class="form-group">
                <label for="motdepasse">Mot de passe :</label>
                <input type="password" id="motdepasse" name="motdepasse" required>
            </div>
            
            <button type="submit" name="send_con">Se connecter</button>
        </form>
        
        <div class="links">
            <a href="sendmail.php">Mot de passe oublié ?</a> | 
            <a href="inscription.php">Créer un compte</a>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Dashboard utilisateur connecté -->
    <div class="dashboard">
        <header>
            <div class="header-content">
                <h1>Plateforme de Suivi de Stages</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['Sprenom'] . " " . $_SESSION['Snom']); ?></span>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="deco" class="logout-btn">Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>
        
        <main>
            <h2>Bienvenue <?php echo htmlspecialchars($_SESSION['Sprenom']); ?> ! 👋</h2>
            
            <!-- Menu de Navigation -->
            <div class="menu-section">
                <?php if ($_SESSION['Stype'] == 0): ?>
                    <h3>Menu Élève</h3>
                    <ul>
                        <li><a href="tableau_bord_eleve.php">📊 Tableau de bord</a></li>
                        <li><a href="liste_cr.php">Liste des comptes rendus</a></li>
                        <li><a href="editer_cr.php">Créer/modifier un compte rendu</a></li>
                        <li><a href="mon_stage.php">Informations de stage</a></li>
                        <li><a href="perso.php">Informations personnelles</a></li>
                    </ul>
                <?php else: ?>
                    <h3>Menu Professeur</h3>
                    <ul>
                        <li><a href="tableau_bord_prof.php">📊 Tableau de bord</a></li>
                        <li><a href="liste_eleves.php">Liste des élèves</a></li>
                        <li><a href="liste_cr_prof.php">Liste des comptes rendus</a></li>
                        <li><a href="gestion_modeles.php">Gestion des modèles</a></li>
                        <li><a href="statistiques.php">Statistiques</a></li>
                    </ul>
                    <h4>Export des données :</h4>
                    <ul>
                        <li><a href="export.php?type=cr&format=csv">Export CR (CSV)</a></li>
                        <li><a href="export.php?type=cr&format=pdf">Export CR (PDF)</a></li>
                        <li><a href="export.php?type=eleves&format=csv">Export Élèves (CSV)</a></li>
                        <li><a href="export.php?type=statistiques&format=csv">Export Statistiques (CSV)</a></li>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php endif; ?>
</body>
</html>