<?php
require_once '_conf.php';
require_once 'fonctions.php';

if (!$loggedIn) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Voir CR';
require_once 'header.php';

$crId = (int)($_GET['id'] ?? 0);
$cr = getCR($crId);

if (!$cr) {
    header('Location: liste_cr.php');
    exit;
}

if ($userType === 0 && $cr['num_utilisateur'] !== $userId) {
    header('Location: liste_cr.php');
    exit;
}

$status = getCRStatus($crId);
$comments = getComments($crId);
?>

<a href="<?php echo $userType === 0 ? 'liste_cr.php' : 'liste_cr_prof.php'; ?>" class="btn btn-outline-secondary btn-sm mb-3">← Retour</a>

<div class="form-section">
    <h3><?php echo htmlspecialchars($cr['titre'] ?? 'Sans titre'); ?></h3>
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Auteur:</strong> <?php echo htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']); ?><br>
            <strong>Date:</strong> <?php echo formatDate($cr['datetime']); ?><br>
            <strong>Statut:</strong> <?php echo getStatusBadge($status['statut'] ?? 'brouillon'); ?>
        </div>
        <div class="col-md-6 text-md-end">
            <?php if ($userType === 0 && $cr['num_utilisateur'] === $userId): ?>
                <a href="editer_cr.php?id=<?php echo $crId; ?>" class="btn btn-sm btn-primary">✎ Éditer</a>
            <?php endif; ?>
        </div>
    </div>
    
    <hr>
    
    <div class="content">
        <?php echo $cr['contenu_html'] ?? '<em>Pas de contenu</em>'; ?>
    </div>
</div>

<?php if ($userType === 1 || ($userType === 0 && $cr['num_utilisateur'] === $userId)): ?>
<div class="form-section mt-4">
    <h5>💬 Commentaires</h5>
    
    <?php if ($userType === 1): ?>
    <form method="POST" action="ajouter_commentaire.php" class="mb-3">
        <input type="hidden" name="cr_id" value="<?php echo $crId; ?>">
        <div class="mb-2">
            <textarea name="commentaire" class="form-control" rows="3" placeholder="Votre commentaire..." required></textarea>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Ajouter commentaire</button>
    </form>
    
    <hr>
    
    <div class="mb-3">
        <label for="status" class="form-label">Changer le statut:</label>
        <div class="input-group">
            <select class="form-select form-select-sm" id="status" onchange="changeStatus(this, <?php echo $crId; ?>)">
                <option value="">-- Sélectionner --</option>
                <option value="en_evaluation">En évaluation</option>
                <option value="approuve">Approuvé</option>
            </select>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($comments)): ?>
        <p class="text-muted">Aucun commentaire</p>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
        <div class="comment-box">
            <div class="author"><?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?></div>
            <div class="date"><?php echo formatDate($comment['date_creation']); ?></div>
            <p class="mt-2"><?php echo htmlspecialchars($comment['commentaire']); ?></p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function changeStatus(select, crId) {
    const status = select.value;
    if (!status) return;
    
    fetch('changer_statut.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'cr_id=' + crId + '&statut=' + status
    }).then(() => location.reload());
}
</script>

<?php require_once 'footer.php'; ?>
