<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'ap1_ethan_2025');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

$serveurBDD = DB_HOST;
$userBDD = DB_USER;
$mdpBDD = DB_PASS;
$nomBDD = DB_NAME;
$dossier_upload = UPLOAD_DIR;
$taille_max_fichier = MAX_FILE_SIZE;
$types_autorises = ALLOWED_TYPES;

if (!file_exists(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0777, true);
if (!file_exists(dirname(__DIR__ . '/logs/erreurs.log'))) @mkdir(dirname(__DIR__ . '/logs/erreurs.log'), 0777, true);

function logger($msg, $uid = null, $page = null) {
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Inconnue';
    $page = $page ?? $_SERVER['PHP_SELF'] ?? 'N/A';
    $uid = $uid ?? ($_SESSION['Sid'] ?? null);
    @file_put_contents(__DIR__ . '/logs/erreurs.log', 
        "[$ts] [IP: $ip] [Page: $page] [User: $uid] $msg\n", 
        FILE_APPEND | LOCK_EX);
}
?>
