<?php
require_once '_conf.php';
require_once 'fonctions.php';

if (!$loggedIn || $userType !== 0) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Corbeille';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cr_id = (int)($_POST['cr_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'restore' && $cr_id > 0) {
        $cr = getCR($cr_id);
        if ($cr && $cr['num_utilisateur'] === $userId) {
            mysqli_query($conn, "UPDATE cr SET supprime=0, date_suppression=NULL WHERE num=$cr_id");
        }
    }
}

$deleted_crs = [];
$result = @mysqli_query($conn, "SELECT cr.*, statuts_cr.statut FROM cr 
                                 LEFT JOIN statuts_cr ON cr.num = statuts_cr.cr_id 
                                 WHERE cr.num_utilisateur=$userId AND cr.supprime=1 
                                 ORDER BY cr.date_suppression DESC");
if ($result) {
    $deleted_crs = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>

<h3 class="mb-4">🗑️ Corbeille</h3>

<?php if (empty($deleted_crs)): ?>
    <div class="alert alert-info">Aucun CR en corbeille</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead class="table-light">
                <tr>
                    <th>Titre</th>
                    <th>Supprimé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deleted_crs as $cr): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cr['titre'] ?? 'Sans titre'); ?></td>
                    <td><?php echo formatDate($cr['date_suppression']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="cr_id" value="<?php echo $cr['num']; ?>">
                            <input type="hidden" name="action" value="restore">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Restaurer ce CR ?')">↩️ Restaurer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
