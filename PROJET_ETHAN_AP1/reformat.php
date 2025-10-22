<?php
// Reformatteur automatique de fichiers PHP
// Accédez via: http://localhost/PROJET_ETHAN_AP1/reformat.php

function reformatPHP($code) {
    // Normaliser les fins de ligne
    $code = preg_replace('/\r\n|\r/', "\n", $code);
    
    // Séparer les lignes compressées
    $lines = explode("\n", $code);
    $result = [];
    $indent = 0;
    $tabChar = "    ";
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        if (empty($trimmed)) {
            $result[] = "";
            continue;
        }
        
        // Gérer les fermetures de blocs
        if (preg_match('/^}/', $trimmed)) {
            $indent = max(0, $indent - 1);
        }
        
        // Ajouter la ligne avec indentation
        $result[] = str_repeat($tabChar, $indent) . $trimmed;
        
        // Gérer les ouvertures de blocs
        if (preg_match('/[\{\:]$/', $trimmed) && !preg_match('/^\/\//', $trimmed)) {
            $indent++;
        }
    }
    
    return implode("\n", $result);
}

$files_to_format = [
    'editer_cr.php',
    'inscription.php',
    'liste_cr.php',
    'liste_cr_prof.php',
    'analytics_advanced.php',
    'gestion_modeles.php',
    'gestion_modeles_checklists.php',
    'gestion_rappels.php',
    'historique_cr.php',
    'liste_eleves.php',
    'marquer_lue.php',
    'mon_stage.php',
    'notifications.php',
    'perso.php',
    'recherche_cr.php',
    'reset.php',
    'simplify.php',
    'statistiques.php',
    'tableau_bord_eleve.php',
    'tableau_bord_prof.php',
    'telecharger.php',
    'validations_cr.php',
    'verifier_email.php'
];

$dir = __DIR__;
$results = [];

foreach ($files_to_format as $file) {
    $filepath = $dir . '/' . $file;
    if (!file_exists($filepath)) {
        $results[$file] = ['status' => 'NOT_FOUND'];
        continue;
    }
    
    $original = file_get_contents($filepath);
    $formatted = reformatPHP($original);
    
    if (file_put_contents($filepath, $formatted)) {
        $results[$file] = ['status' => 'OK', 'lines' => count(explode("\n", $formatted))];
    } else {
        $results[$file] = ['status' => 'ERROR'];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['results' => $results, 'total' => count($results)], JSON_PRETTY_PRINT);
?>