<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>DIAGNOSTIC</h2>";

try {
    require_once '_conf.php';
    echo "✓ Config loaded<br>";
    
    require_once 'fonctions.php';
    echo "✓ Functions loaded<br>";
    
    require_once 'header.php';
    echo "✓ Header loaded<br>";
    
} catch (Exception $e) {
    echo "<strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Test functions
$functions_to_test = [
    'getAllCRsForTeacher',
    'getStatusBadge',
    'formatDate',
    'getCR',
    'getComments',
    'getUnreadNotificationsCount',
    'getUserNotifications'
];

echo "<h3>Function checks:</h3>";
foreach ($functions_to_test as $f) {
    echo "$f: " . (function_exists($f) ? "✓" : "✗") . "<br>";
}

require_once 'footer.php';
?>
