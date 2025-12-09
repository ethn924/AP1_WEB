<?php
session_start();

// Détruire la session
$_SESSION = array();
session_destroy();

// Rediriger vers la page de connexion
header("Location: index.php");
exit();
?>