<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    die("Accès refusé");
}

$cr_id = intval($_GET['id'] ?? 0);
if (!$cr_id) {
    die("ID invalide");
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$query = "SELECT c.*, u.nom, u.prenom FROM cr c JOIN utilisateur u ON c.num_utilisateur = u.num WHERE c.num = $cr_id";
$result = mysqli_query($bdd, $query);
$cr = mysqli_fetch_assoc($result);

if (!$cr) {
    die("CR non trouvé");
}

if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] != $_SESSION['Sid']) {
    die("Accès refusé");
}

$pieces_jointes = getPiecesJointes($cr_id);
$commentaires = getCommentaires($cr_id);

$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; margin: 30px; color: #333; line-height: 1.6; }
    .header { border-bottom: 3px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
    .header h1 { margin: 0 0 10px 0; color: #667eea; font-size: 24px; }
    .header p { color: #666; margin: 5px 0; }
    .info-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
    .info-table tr { border-bottom: 1px solid #eee; }
    .info-table td { padding: 12px 8px; }
    .info-table .label { font-weight: bold; width: 150px; color: #333; background: #f9f9f9; }
    .content-section { background: #fafafa; padding: 15px; border-left: 4px solid #667eea; margin-bottom: 20px; }
    .section-title { font-weight: bold; color: #667eea; font-size: 15px; margin-bottom: 10px; }
    .attachments, .comments { margin-top: 20px; }
    .attachment-item { background: #f5f5f5; padding: 8px 10px; margin: 5px 0; border-radius: 4px; font-size: 13px; }
    .comment-item { background: #f5f7ff; padding: 10px; margin: 8px 0; border-left: 4px solid #667eea; border-radius: 4px; font-size: 13px; }
    .comment-author { font-weight: bold; color: #333; margin-bottom: 3px; }
    .comment-date { color: #999; font-size: 11px; }
    .comment-text { margin-top: 5px; color: #555; }
    footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px; }
</style>
</head>
<body>
<div class="header">
    <h1>Compte Rendu de Stage</h1>
    <p>Généré le ' . date('d/m/Y à H:i') . '</p>
</div>

<table class="info-table">
    <tr>
        <td class="label">Numéro CR</td>
        <td>' . htmlspecialchars($cr['num']) . '</td>
    </tr>
    <tr>
        <td class="label">Étudiant</td>
        <td>' . htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']) . '</td>
    </tr>
    <tr>
        <td class="label">Titre</td>
        <td>' . htmlspecialchars($cr['titre'] ?? 'Sans titre') . '</td>
    </tr>
    <tr>
        <td class="label">Date de création</td>
        <td>' . formatDateTimeFrench($cr['datetime']) . '</td>
    </tr>
    <tr>
        <td class="label">Statut</td>
        <td>' . ($cr['vu'] == 1 ? "Consulté" : "Non consulté") . '</td>
    </tr>
</table>';

if (!empty($cr['description'])) {
    $html .= '
<div class="content-section">
    <div class="section-title">📝 Description</div>
    <p>' . htmlspecialchars($cr['description']) . '</p>
</div>';
}

$html .= '
<div class="content-section">
    <div class="section-title">📋 Contenu du compte rendu</div>
    ' . $cr['contenu_html'] . '
</div>';

if (!empty($pieces_jointes)) {
    $html .= '
<div class="attachments">
    <div class="section-title">📎 Pièces jointes</div>';
    foreach ($pieces_jointes as $piece) {
        $html .= '<div class="attachment-item">📄 ' . htmlspecialchars($piece['nom_fichier']) . ' (' . formaterTailleFichier($piece['taille']) . ')</div>';
    }
    $html .= '</div>';
}

if (!empty($commentaires)) {
    $html .= '
<div class="comments">
    <div class="section-title">💬 Commentaires (' . count($commentaires) . ')</div>';
    foreach ($commentaires as $comment) {
        $html .= '
    <div class="comment-item">
        <div class="comment-author">' . htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']) . '</div>
        <small style="color: #999;">' . formatDateTimeFrench($comment['date_creation']) . '</small>
        <p style="margin: 8px 0 0 0; white-space: pre-wrap;">' . htmlspecialchars($comment['commentaire']) . '</p>
    </div>';
    }
    $html .= '</div>';
}

$html .= '
<footer>
Généré le ' . formatDateTimeFrench(date('Y-m-d H:i:s')) . ' - Compte rendu n°' . $cr['num'] . '
</footer>
</body>
</html>';

$filename = 'CR_' . $cr['num'] . '_' . $cr['prenom'] . '_' . $cr['nom'] . '_' . date('Y-m-d') . '.pdf';

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $html;
mysqli_close($bdd);
?>
