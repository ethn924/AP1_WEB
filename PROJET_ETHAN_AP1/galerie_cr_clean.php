 la fin<?php
session_start();
require 'fonctions.php';
require '_conf.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!isset($_SESSION['Sid'])) header('Location: index.php');

$utilisateur_id = $_SESSION['Sid'];
$cr_id = intval($_GET['cr_id'] ?? 0);

if ($cr_id === 0) {
    die('CR non spécifié');
}

// Vérifier propriété du CR
$query = "SELECT num FROM cr WHERE num = $cr_id AND num_utilisateur = $utilisateur_id";
$check = mysqli_query($bdd, $query);
if (!$check || mysqli_num_rows($check) === 0) {
    die('Accès refusé');
}

$query = "SELECT * FROM cr WHERE num = $cr_id";
$cr = mysqli_fetch_assoc(mysqli_query($bdd, $query));
$galerie = getGalerieCR($cr_id);

// AJAX actions: ajouter, supprimer
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
    <h1>🖼️ Galerie d'images du CR</h1>
    
    <p style="color: #666;">
        <a href="liste_cr.php" style="color: #007bff; text-decoration: none;">← Retour aux CRs</a>
    </p>
    
    <!-- Formulaire d'ajout -->
    <div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; max-width: 600px;">
        <h3>➕ Ajouter une image</h3>
        <form id="formAjout" style="display: grid; gap: 10px;">
            <input type="url" id="url" placeholder="URL de l'image (http://...)" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px;" required>
            
            <input type="text" id="titre" placeholder="Titre de l'image (optionnel)" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            
            <textarea id="description" placeholder="Description (optionnel)" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px; height: 80px;"></textarea>
            
            <button type="button" onclick="ajouterImage()" style="padding: 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
                ✅ Ajouter l'image
            </button>
        </form>
    </div>
    
    <!-- Galerie d'images -->
    <div>
        <h3>📸 Images (<?php echo count($galerie); ?>)</h3>
        
        <?php if (empty($galerie)): ?>
        <div style="background: white; padding: 30px; text-align: center; border-radius: 5px;">
            <p style="color: #666; font-size: 1.1em;">📭 Aucune image pour le moment</p>
        </div>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($galerie as $img): ?>
            <div style="background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($img['url_image']); ?>" alt="<?php echo htmlspecialchars($img['titre_image']); ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='❌ Erreur chargement image';">
                </div>
                <div style="padding: 10px;">
                    <?php if ($img['titre_image']): ?>
                    <strong><?php echo htmlspecialchars($img['titre_image']); ?></strong>
                    <br>
                    <?php endif; ?>
                    <?php if ($img['description']): ?>
                    <small><?php echo htmlspecialchars($img['description']); ?></small>
                    <br>
                    <?php endif; ?>
                    <small style="color: #999;">Ajoutée le <?php echo date('d/m/Y', strtotime($img['date_ajout'])); ?></small>
                    <div style="margin-top: 10px;">
                        <button onclick="supprimerImage(<?php echo $img['id']; ?>)" style="width: 100%; padding: 6px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.9em;">
                            🗑️ Supprimer
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Info conseils -->
    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; border-radius: 3px; margin-top: 20px; max-width: 600px;">
        <h3 style="margin-top: 0;">ℹ️ Conseils</h3>
        <ul>
            <li>Utilisez des URLs directes vers les images (HTTP/HTTPS)</li>
            <li>Supportez les formats: JPG, PNG, GIF</li>
            <li>Optimisez les tailles pour un chargement rapide</li>
            <li>Ajoutez un titre et une description pour meilleure organisation</li>
        </ul>
    </div>

    <script>
        function ajouterImage() {
            const url = document.getElementById('url').value;
            const titre = document.getElementById('titre').value;
            const description = document.getElementById('description').value;
            
            if (!url) {
                alert('Veuillez entrer une URL');
                return;
            }
            
            fetch('galerie_cr.php?cr_id=<?php echo $cr_id; ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=ajouter&url=' + encodeURIComponent(url) + '&titre=' + encodeURIComponent(titre) + '&description=' + encodeURIComponent(description)
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('✅ Image ajoutée');
                    location.reload();
                }
            });
        }

        function supprimerImage(id) {
            if (!confirm('Êtes-vous sûr?')) return;
            
            fetch('galerie_cr.php?cr_id=<?php echo $cr_id; ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=supprimer&id=' + id
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('✅ Image supprimée');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>