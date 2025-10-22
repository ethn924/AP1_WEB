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

$format = $_GET['format'] ?? '';
$cr_id = intval($_GET['id'] ?? 0);

if (!$cr_id) {
    header("Location: liste_cr.php");
    exit();
}

$query = "SELECT c.*, u.nom, u.prenom FROM cr c JOIN utilisateur u ON c.num_utilisateur = u.num WHERE c.num = $cr_id";
$result = mysqli_query($bdd, $query);
$cr = mysqli_fetch_assoc($result);

if (!$cr) {
    header("Location: liste_cr.php");
    exit();
}

if ($_SESSION['Stype'] == 0 && $cr['num_utilisateur'] != $_SESSION['Sid']) {
    header("Location: liste_cr.php");
    exit();
}

$statut_data = getStatutCR($cr_id);

switch ($format) {
    case 'pdf':
    exportPDF($cr, $statut_data);
    break;
    case 'excel':
    exportExcel($cr, $statut_data);
    break;
    case 'word':
    exportWord($cr, $statut_data);
    break;
    default:
    header("Location: liste_cr.php");
    break;
}
function exportPDF($cr, $statut_data) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="CR_' . $cr['num'] . '_' . date('Y-m-d') . '.pdf"');
    echo "% PDF-1.4\n";
    echo "1 0 obj\n";
    echo "<< /Type /Catalog /Pages 2 0 R >>\n";
    echo "endobj\n";
    echo "2 0 obj\n";
    echo "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
    echo "endobj\n";
    echo "3 0 obj\n";
    echo "<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\n";
    echo "endobj\n";
    echo "4 0 obj\n";
    echo "<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\n";
    echo "endobj\n";
    $content = generatePDFContent($cr, $statut_data);
    echo "5 0 obj\n";
    echo "<< /Length " . strlen($content) . " >>\n";
    echo "stream\n";
    echo $content;
    echo "endstream\n";
    echo "endobj\n";
    echo "xref\n";
    echo "0 6\n";
    echo "0000000000 65535 f\n";
    echo "0000000009 00000 n\n";
    echo "0000000058 00000 n\n";
    echo "0000000115 00000 n\n";
    echo "0000000214 00000 n\n";
    echo "0000000309 00000 n\n";
    echo "trailer\n";
    echo "<< /Size 6 /Root 1 0 R >>\n";
    echo "startxref\n";
    echo strlen($content) + 400 . "\n";
    echo "%%EOF\n";
}
function exportExcel($cr, $statut_data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="CR_' . $cr['num'] . '_' . date('Y-m-d') . '.xls"');
    $excel = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$excel .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
$excel .= "xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
$excel .= "xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
$excel .= "xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
$excel .= "xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
$excel .= "<Worksheet ss:Name=\"Compte Rendu\">\n";
$excel .= "<Table>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">Numéro CR</Data></Cell></Row>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">" . htmlspecialchars($cr['num']) . "</Data></Cell></Row>\n";
$excel .= "\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">Étudiant</Data></Cell></Row>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">" . htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']) . "</Data></Cell></Row>\n";
$excel .= "\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">Titre</Data></Cell></Row>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">" . htmlspecialchars($cr['titre'] ?? 'N/A') . "</Data></Cell></Row>\n";
$excel .= "\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">Date</Data></Cell></Row>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">" . date('d/m/Y', strtotime($cr['date'])) . "</Data></Cell></Row>\n";
$excel .= "\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">Description</Data></Cell></Row>\n";
$excel .= "<Row><Cell><Data ss:Type=\"String\">" . htmlspecialchars($cr['description']) . "</Data></Cell></Row>\n";
$excel .= "\n";
if ($statut_data) {
    $excel .= "<Row><Cell><Data ss:Type=\"String\">Statut</Data></Cell></Row>\n";
    $excel .= "<Row><Cell><Data ss:Type=\"String\">" . htmlspecialchars($statut_data['statut']) . "</Data></Cell></Row>\n";
    }
$excel .= "</Table>\n";
$excel .= "</Worksheet>\n";
$excel .= "</Workbook>";
echo $excel;
}
function exportWord($cr, $statut_data) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="CR_' . $cr['num'] . '_' . date('Y-m-d') . '.docx"');
    $word = '<?xml version="1.0" encoding="UTF-8" standalone="yes"<?php';
$word .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
$word .= '<w:body>';
$word .= '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Compte Rendu de Stage</w:t></w:r></w:p>';
$word .= '<w:p><w:r><w:t>Numéro: ' . htmlspecialchars($cr['num']) . '</w:t></w:r></w:p>';
$word .= '<w:p><w:r><w:t>Étudiant: ' . htmlspecialchars($cr['prenom'] . ' ' . $cr['nom']) . '</w:t></w:r></w:p>';
$word .= '<w:p><w:r><w:t>Titre: ' . htmlspecialchars($cr['titre'] ?? 'N/A') . '</w:t></w:r></w:p>';
$word .= '<w:p><w:r><w:t>Date: ' . date('d/m/Y', strtotime($cr['date'])) . '</w:t></w:r></w:p>';
$word .= '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Description</w:t></w:r></w:p>';
$word .= '<w:p><w:r><w:t>' . htmlspecialchars($cr['description']) . '</w:t></w:r></w:p>';
if ($cr['contenu_html']) {
    $word .= '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Contenu Détaillé</w:t></w:r></w:p>';
    $word .= '<w:p><w:r><w:t>' . htmlspecialchars(strip_tags($cr['contenu_html'])) . '</w:t></w:r></w:p>';
}
if ($statut_data) {
    $word .= '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Statut</w:t></w:r></w:p>';
    $word .= '<w:p><w:r><w:t>' . htmlspecialchars($statut_data['statut']) . '</w:t></w:r></w:p>';
}
$word .= '</w:body></w:document>';
echo $word;
}

function generatePDFContent($cr, $statut_data) {
    $content = "BT\n";
    $content .= "/F1 12 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(Compte Rendu de Stage) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "/F1 10 Tf\n";
    $content .= "(Numéro: " . $cr['num'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Étudiant: " . $cr['prenom'] . " " . $cr['nom'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Titre: " . ($cr['titre'] ?? 'N/A') . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Date: " . date('d/m/Y', strtotime($cr['date'])) . ") Tj\n";
    $content .= "0 -30 Td\n";
    $content .= "(Description:) Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(" . substr($cr['description'], 0, 80) . "...) Tj\n";
    if ($statut_data) {
        $content .= "0 -30 Td\n";
        $content .= "(Statut: " . $statut_data['statut'] . ") Tj\n";
    }
    $content .= "ET\n";
    return $content;
}

mysqli_close($bdd);
    ?>