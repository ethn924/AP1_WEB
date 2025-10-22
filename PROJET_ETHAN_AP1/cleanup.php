<?php
// Améliorer le formatage du code simplifié
$dir = __DIR__;
$files = glob("$dir/*.php");

foreach ($files as $file) {
    if (in_array(basename($file), ['simplify.php', 'cleanup.php'])) continue;
    
    $content = file_get_contents($file);
    
    // Améliorer le formatage
    $content = preg_replace('/\}\s+function/', "}\n\nfunction", $content);
    $content = preg_replace('/\{\s+global/', "{ global", $content);
    $content = preg_replace('/(\$[a-z_]+)\s*=\s*/', "$1 = ", $content);
    $content = preg_replace('/\)\s*\{/', ") {", $content);
    $content = preg_replace('/}\s+\}/', "}\n}", $content);
    
    file_put_contents($file, $content);
}

echo "Nettoyage formatage terminé!";
?>