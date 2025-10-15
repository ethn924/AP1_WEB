<?php
session_start();
include '_conf.php';

// Fonction pour formater la date et l'heure en français avec majuscules
function formatDateFrench($date) {
    $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    
    $date_str = date('d F Y à H\hi', strtotime($date));
    $date_str = str_replace($english_months, $french_months, $date_str);
    
    return $date_str;
}

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 0) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$user_id = $_SESSION['Sid'];
$query = "SELECT * FROM cr WHERE num_utilisateur = $user_id ORDER BY datetime DESC";
$result = mysqli_query($bdd, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des comptes rendus</title>
</head>
<body>
    <h2>Mes comptes rendus</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1">
            <tr>
                <th>Date et heure</th>
                <th>Description</th>
            </tr>
            <?php while ($cr = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo formatDateFrench($cr['datetime']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($cr['description'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Aucun compte rendu trouvé.</p>
    <?php endif; ?>

    <p><a href="accueil.php">Retour à l'accueil</a></p>
</body>
</html>