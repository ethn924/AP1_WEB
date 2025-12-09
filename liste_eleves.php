<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

// Récupérer la liste des élèves avec leurs informations de stage
$query = "SELECT u.*, s.nom as stage_nom, s.adresse, s.CP, s.ville, s.tel as stage_tel, 
                 s.libelleStage, s.email as stage_email,
                 t.nom as tuteur_nom, t.prenom as tuteur_prenom, 
                 t.tel as tuteur_tel, t.email as tuteur_email
          FROM utilisateur u 
          LEFT JOIN stage s ON u.num_stage = s.num 
          LEFT JOIN tuteur t ON s.num_tuteur = t.num 
          WHERE u.type = 0";
$result = mysqli_query($bdd, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des élèves</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="cr.css">
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <h2 style="padding: 20px;">Liste des élèves</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table border="1">
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Login</th>
                <th>Email</th>
                <th>Stage</th>
                <th>Actions</th>
            </tr>
            <?php while ($eleve = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $eleve['nom']; ?></td>
                <td><?php echo $eleve['prenom']; ?></td>
                <td><?php echo $eleve['login']; ?></td>
                <td><?php echo $eleve['email']; ?></td>
                <td>
                    <?php if (!empty($eleve['stage_nom'])): ?>
                        ✅ Stage renseigné
                    <?php else: ?>
                        ❌ Aucun stage
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($eleve['stage_nom'])): ?>
                        <a href="?view_stage=<?php echo $eleve['num']; ?>">Voir le stage</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Aucun élève trouvé.</p>
    <?php endif; ?>

    <!-- Modal pour afficher les détails du stage -->
    <?php if (isset($_GET['view_stage']) && !empty($_GET['view_stage'])): 
        $eleve_id = intval($_GET['view_stage']);
        $stage_query = "SELECT u.prenom, u.nom, s.*, t.nom as tuteur_nom, t.prenom as tuteur_prenom, 
                                t.tel as tuteur_tel, t.email as tuteur_email
                         FROM utilisateur u 
                         LEFT JOIN stage s ON u.num_stage = s.num 
                         LEFT JOIN tuteur t ON s.num_tuteur = t.num 
                         WHERE u.num = " . intval($eleve_id);
        $stage_result = mysqli_query($bdd, $stage_query);
        $stage_info = mysqli_fetch_assoc($stage_result);
    ?>
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="background: white; margin: 50px auto; padding: 20px; width: 80%; max-width: 700px; position: relative;">
                <span style="position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer;" 
                      onclick="window.location.href='liste_eleves.php'">&times;</span>

                <h2>Informations de stage - <?php echo $stage_info['prenom'] . ' ' . $stage_info['nom']; ?></h2>

                <?php if (!empty($stage_info['nom'])): ?>
                    <h3>Entreprise</h3>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($stage_info['nom'] ?? ''); ?></p>
                    <p><strong>Adresse :</strong> <?php echo htmlspecialchars($stage_info['adresse'] ?? ''); ?></p>
                    <p><strong>Code postal :</strong> <?php echo htmlspecialchars($stage_info['CP'] ?? ''); ?></p>
                    <p><strong>Ville :</strong> <?php echo htmlspecialchars($stage_info['ville'] ?? ''); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tel'] ?? ''); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['email'] ?? ''); ?></p>
                    <p><strong>Libellé du stage :</strong><br><?php echo nl2br(htmlspecialchars($stage_info['libelleStage'] ?? '')); ?></p>

                    <h3>Tuteur en entreprise</h3>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars(($stage_info['tuteur_prenom'] ?? '') . ' ' . ($stage_info['tuteur_nom'] ?? '')); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($stage_info['tuteur_tel'] ?? ''); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($stage_info['tuteur_email'] ?? ''); ?></p>
                <?php else: ?>
                    <p>Cet élève n'a pas encore renseigné ses informations de stage.</p>
                <?php endif; ?>

                <p><a href="liste_eleves.php">Fermer</a></p>
            </div>
        </div>
    <?php endif; ?>

    <p><a href="accueil.php">Retour à l'accueil</a></p>
    <?php include 'footer.php'; ?>