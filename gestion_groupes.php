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

$message = '';
$error = '';
$groupe = null;

// Récupération d'un groupe pour modification
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $groupe_id = intval($_GET['edit']);
    $professeur_id = intval($_SESSION['Sid']);
    $query = "SELECT * FROM groupes WHERE id = $groupe_id AND professeur_responsable_id = $professeur_id";
    $result = mysqli_query($bdd, $query);
    if (mysqli_num_rows($result) > 0) {
        $groupe = mysqli_fetch_assoc($result);
    }
}

// Suppression d'un groupe
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $groupe_id = intval($_GET['delete']);
    $professeur_id = intval($_SESSION['Sid']);
    $query = "DELETE FROM groupes WHERE id = $groupe_id AND professeur_responsable_id = $professeur_id";
    if (mysqli_query($bdd, $query)) {
        $message = "Le groupe a été supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression du groupe.";
    }
}

// Traitement de l'ajout ou de la modification d'un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_groupe'])) {
    $nom = mysqli_real_escape_string($bdd, $_POST['nom']);
    $description = mysqli_real_escape_string($bdd, $_POST['description']);

    if (empty($nom)) {
        $error = "Le nom du groupe est obligatoire.";
    } else {
        if (isset($_POST['groupe_id']) && !empty($_POST['groupe_id'])) {
            // Modification
            $groupe_id = intval($_POST['groupe_id']);
            $professeur_id = intval($_SESSION['Sid']);
            $update_query = "UPDATE groupes SET nom = '$nom', description = '$description' 
                            WHERE id = $groupe_id AND professeur_responsable_id = $professeur_id";

            if (mysqli_query($bdd, $update_query)) {
                $message = "Le groupe a été mis à jour avec succès.";
                header("Location: gestion_groupes.php?edit=$groupe_id&success=1");
                exit();
            } else {
                $error = "Erreur lors de la mise à jour du groupe: " . mysqli_error($bdd);
            }
        } else {
            // Création
            $professeur_id = intval($_SESSION['Sid']);
            $insert_query = "INSERT INTO groupes (nom, description, professeur_responsable_id) 
                            VALUES ('$nom', '$description', $professeur_id)";

            if (mysqli_query($bdd, $insert_query)) {
                $message = "Le groupe a été créé avec succès.";
                $new_groupe_id = mysqli_insert_id($bdd);
                header("Location: gestion_groupes.php?edit=$new_groupe_id&success=1");
                exit();
            } else {
                $error = "Erreur lors de la création du groupe: " . mysqli_error($bdd);
            }
        }
    }
}

// Ajout/suppression d'un membre
if (isset($_POST['add_membre']) || isset($_POST['remove_membre'])) {
    $groupe_id = intval($_POST['groupe_id']);
    $utilisateur_id = intval($_POST['utilisateur_id']);

    if (isset($_POST['add_membre'])) {
        $query = "INSERT INTO membres_groupe (groupe_id, utilisateur_id) VALUES ($groupe_id, $utilisateur_id)";
        if (mysqli_query($bdd, $query)) {
            $message = "Membre ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout du membre.";
        }
    } elseif (isset($_POST['remove_membre'])) {
        $query = "DELETE FROM membres_groupe WHERE groupe_id = $groupe_id AND utilisateur_id = $utilisateur_id";
        if (mysqli_query($bdd, $query)) {
            $message = "Membre supprimé avec succès.";
        } else {
            $error = "Erreur lors de la suppression du membre.";
        }
    }
}

// Récupération de tous les groupes du professeur
$query_groupes = "SELECT * FROM groupes WHERE professeur_responsable_id = {$_SESSION['Sid']} ORDER BY date_creation DESC";
$result_groupes = mysqli_query($bdd, $query_groupes);

// Récupération de tous les étudiants
$query_students = "SELECT num, nom, prenom FROM utilisateur WHERE type = 0 ORDER BY nom, prenom";
$result_students = mysqli_query($bdd, $query_students);
$all_students = [];
while ($row = mysqli_fetch_assoc($result_students)) {
    $all_students[] = $row;
}

// Message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = isset($_GET['edit']) ? "Le groupe a été mis à jour avec succès." : "Le groupe a été créé avec succès.";
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Groupes d'Étudiants</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="gestion.css">
</head>

<body>
    <?php afficherHeaderNavigation(); ?>
    <div class="container">
        <h1>Gestion des Groupes d'Étudiants</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php echo $groupe ? 'Modifier le groupe' : 'Créer un nouveau groupe'; ?></h2>
            <form method="POST" action="">
                <?php if ($groupe): ?>
                    <input type="hidden" name="groupe_id" value="<?php echo $groupe['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom">Nom du groupe *</label>
                    <input type="text" id="nom" name="nom" class="form-control" required
                        value="<?php echo $groupe ? htmlspecialchars($groupe['nom']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                        class="form-control"><?php echo $groupe ? htmlspecialchars($groupe['description']) : ''; ?></textarea>
                </div>

                <div style="text-align: right;">
                    <?php if ($groupe): ?>
                        <a href="gestion_groupes.php" class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                    <button type="submit" name="submit_groupe" class="btn btn-success">
                        <?php echo $groupe ? 'Mettre à jour le groupe' : 'Créer le groupe'; ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($groupe): ?>
            <div class="card">
                <h2>Gestion des Membres</h2>

                <?php $membres = getMembresGroupe($groupe['id']); ?>

                <?php if (count($membres) > 0): ?>
                    <div class="groupe-members">
                        <ul class="membre-list">
                            <?php foreach ($membres as $membre): ?>
                                <li class="membre-item">
                                    <span><?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?></span>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="groupe_id" value="<?php echo $groupe['id']; ?>">
                                        <input type="hidden" name="utilisateur_id" value="<?php echo $membre['num']; ?>">
                                        <button type="submit" name="remove_membre" class="btn btn-danger"
                                            onclick="return confirm('Retirer ce membre ?');"
                                            style="padding: 3px 8px; font-size: 11px;">
                                            Retirer
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p style="margin-top: 10px; color: #666;">Nombre de membres: <?php echo count($membres); ?></p>
                <?php else: ?>
                    <p>Aucun membre dans ce groupe.</p>
                <?php endif; ?>

                <div class="add-membre-form">
                    <form method="POST" action="" style="display: flex; gap: 10px; width: 100%;">
                        <input type="hidden" name="groupe_id" value="<?php echo $groupe['id']; ?>">
                        <select name="utilisateur_id" required style="flex: 1;">
                            <option value="">-- Sélectionner un étudiant --</option>
                            <?php foreach ($all_students as $student): ?>
                                <?php
                                // Vérifier si l'étudiant est déjà dans le groupe
                                $is_member = false;
                                foreach ($membres as $membre) {
                                    if ($membre['num'] == $student['num']) {
                                        $is_member = true;
                                        break;
                                    }
                                }

                                if (!$is_member):
                                    ?>
                                    <option value="<?php echo $student['num']; ?>">
                                        <?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="add_membre" class="btn btn-success">Ajouter</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Mes Groupes</h2>
            <?php if (mysqli_num_rows($result_groupes) > 0): ?>
                <?php while ($groupe_item = mysqli_fetch_assoc($result_groupes)): ?>
                    <div class="groupe-item">
                        <div class="groupe-header">
                            <div>
                                <div class="groupe-title"><?php echo htmlspecialchars($groupe_item['nom']); ?></div>
                                <div class="groupe-info">
                                    Créé le <?php echo date('d/m/Y à H:i', strtotime($groupe_item['date_creation'])); ?>
                                </div>
                                <p style="margin: 5px 0; color: #555;">
                                    <?php echo htmlspecialchars($groupe_item['description']); ?>
                                </p>
                            </div>
                        </div>

                        <?php
                        $membres_groupe = getMembresGroupe($groupe_item['id']);
                        ?>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            <strong><?php echo count($membres_groupe); ?> membre(s)</strong>
                        </div>

                        <div class="groupe-actions">
                            <a href="gestion_groupes.php?edit=<?php echo $groupe_item['id']; ?>" class="btn">Gérer</a>
                            <a href="gestion_groupes.php?delete=<?php echo $groupe_item['id']; ?>" class="btn btn-danger"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?');">Supprimer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Vous n'avez pas encore créé de groupes.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
<?php
mysqli_close($bdd);
?>