<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$format = $_GET['format'] ?? '';
$cr_id = intval($_GET['id'] ?? 0);
$redirect_url = ($_SESSION['Stype'] == 0) ? 'liste_cr.php' : 'liste_cr_prof.php';

if (!$cr_id || !in_array($format, ['pdf', 'excel', 'word'])) {
    header("Location: " . $redirect_url);
    exit();
}

header("Location: export_pdf_cr.php?id=$cr_id");
exit();
?>