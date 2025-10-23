<?php
session_start();
require 'fonctions.php';
require '_conf.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);

if (!isset($_SESSION['Sid'])) {
    header('Location: index.php');
}

$utilisateur_id = $_SESSION['Sid'];
$cr_id = intval($_GET['cr_id'] ?? 0);

if ($cr_id === 0) {
    die('CR non spécifié');
}

$query = "SELECT num FROM cr WHERE num = $cr_id AND num_utilisateur = $utilisateur_id";
$check = mysqli_query($bdd, $query);

if (!$check || mysqli_num_rows($check) === 0) {
    die('Accès refusé');
}

$query = "SELECT * FROM cr WHERE num = $cr_id";
$cr = mysqli_fetch_assoc(mysqli_query($bdd, $query));
$galerie = getGalerieCR($cr_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $url = $_POST['url'] ?? '';
        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';

        if (!empty($url)) {
            $success = ajouterImageGalerie($cr_id, $utilisateur_id, $url, $titre, $description);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false]);
        }
    } elseif ($action === 'supprimer') {
        $id = intval($_POST['id']);
        $success = supprimerImageGalerie($id);
        echo json_encode(['success' => $success]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>🖼️ Galerie - <?php echo htmlspecialchars(substr($cr['description'], 0, 30)); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9;">
    <?php afficherNavigation(); ?>
    <?php afficherMenuFonctionnalites(); ?>
    <h1>🖼️ Galerie d'images du CR</h1>

    <!-- Formulaire d'ajout -->
    <div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; max-width: 600px;">
        <h2>Ajouter une image</h2>
        <form id="addImageForm" method="POST">
            <input type="hidden" name="action" value="ajouter">
            <input type="url" name="url" placeholder="URL de l'image" required
                style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">
            <input type="text" name="titre" placeholder="Titre (optionnel)"
                style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">
            <textarea name="description" placeholder="Description (optionnel)"
                style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; height: 100px;"></textarea>
            <button type="submit"
                style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Ajouter</button>
        </form>
    </div>

    <!-- Galerie -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
        <?php foreach ($galerie as $image): ?>
            <div style="background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <img src="<?php echo htmlspecialchars($image['url']); ?>"
                    alt="<?php echo htmlspecialchars($image['titre'] ?? 'Image'); ?>"
                    style="width: 100%; height: 150px; object-fit: cover;">
                <div style="padding: 10px;">
                    <h3 style="margin: 5px 0; font-size: 14px;">
                        <?php echo htmlspecialchars($image['titre'] ?? 'Sans titre'); ?></h3>
                    <p style="margin: 5px 0; font-size: 12px; color: #666;">
                        <?php echo htmlspecialchars(substr($image['description'] ?? '', 0, 50)); ?></p>
                    <button onclick="supprimerImage(<?php echo $image['id']; ?>)"
                        style="background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; width: 100%;">Supprimer</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.getElementById('addImageForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de l\'ajout');
                    }
                });
        });

        function supprimerImage(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    });
            }
        }
    </script>
</body>

</html>