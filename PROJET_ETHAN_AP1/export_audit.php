<?php
session_start();
include '_conf.php';
include 'fonctions.php';
include 'api_audit.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) { die("Erreur BDD"); }

$format = $_GET['format'] ?? 'pdf';
$action = $_POST['action'] ?? '';

if ($action === 'cleanup') {
    supprimerAuditAncien(365);
    header("Location: gestion_audit.php?success=Audit nettoyé");
    exit();
}

$filtres = [
    'date_debut' => $_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days')),
    'date_fin' => $_GET['date_fin'] ?? date('Y-m-d')
];

$historique = obtenirHistoriqueAudit($filtres);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fputcsv($output, ['Date', 'Utilisateur', 'Action', 'Entité', 'ID Entité', 'Description', 'IP', 'User Agent']);
    
    foreach ($historique as $log) {
        fputcsv($output, [
            $log['date_action'],
            ($log['prenom'] ?? '') . ' ' . ($log['nom'] ?? ''),
            $log['action'],
            $log['entite'],
            $log['entite_id'] ?? '',
            $log['description'] ?? '',
            $log['adresse_ip'],
            $log['user_agent'] ?? ''
        ]);
    }
    fclose($output);
    exit();
}

if ($format === 'pdf') {
    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { text-align: center; margin-bottom: 10px; }
            .meta { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #f0f0f0; border: 1px solid #ddd; padding: 5px; font-weight: bold; }
            td { border: 1px solid #ddd; padding: 5px; }
            tr:nth-child(even) { background: #f9f9f9; }
            .page-break { page-break-after: always; }
        </style>
    </head>
    <body>
        <h1>📋 Rapport Audit Système</h1>
        <div class="meta">
            <p>Généré le ' . date('d/m/Y à H:i:s') . '</p>
            <p>Période: ' . $filtres['date_debut'] . ' au ' . $filtres['date_fin'] . '</p>
            <p>Total: ' . count($historique) . ' actions</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Entité</th>
                    <th>ID</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($historique as $log) {
        $html .= '<tr>
            <td>' . date('d/m/Y H:i', strtotime($log['date_action'])) . '</td>
            <td>' . htmlspecialchars(($log['prenom'] ?? '') . ' ' . ($log['nom'] ?? '')) . '</td>
            <td>' . htmlspecialchars($log['action']) . '</td>
            <td>' . htmlspecialchars($log['entite']) . '</td>
            <td>' . ($log['entite_id'] ?? '-') . '</td>
            <td>' . htmlspecialchars(substr($log['description'] ?? '', 0, 50)) . '</td>
            <td>' . htmlspecialchars($log['adresse_ip']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table></body></html>';
    
    $filename = 'audit_' . date('Y-m-d_H-i-s') . '.pdf';
    $filepath = sys_get_temp_dir() . '/' . $filename;
    file_put_contents($filepath, $html);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filepath);
    unlink($filepath);
    exit();
}

mysqli_close($bdd);
?>