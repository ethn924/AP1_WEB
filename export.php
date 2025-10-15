<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid']) || $_SESSION['Stype'] != 1) {
    header("Location: index.php");
    exit();
}

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) {
    die("Erreur connexion BDD");
}

$type_export = $_GET['type'] ?? 'cr';
$format = $_GET['format'] ?? 'csv';

// Fonction pour exporter en CSV
function exporterCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// Fonction pour exporter en PDF (basique)
function exporterPDF($data, $filename, $titre) {
    // Dans une vraie implémentation, on utiliserait une librairie comme TCPDF
    // Pour l'instant, on fait un export HTML simple
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>$titre</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>$titre</h1>
        <p>Export généré le " . date('d/m/Y à H:i') . "</p>";
    
    if (!empty($data)) {
        echo "<table>";
        // En-têtes
        echo "<tr>";
        foreach (array_keys($data[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        // Données
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune donnée à exporter.</p>";
    }
    
    echo "</body></html>";
    exit;
}

switch ($type_export) {
    case 'cr':
        // Export des comptes rendus
        $query = "SELECT 
                    u.prenom AS 'Prénom Élève',
                    u.nom AS 'Nom Élève',
                    cr.date AS 'Date',
                    cr.datetime AS 'Date et heure',
                    cr.description AS 'Description',
                    CASE WHEN cr.vu = 1 THEN 'Oui' ELSE 'Non' END AS 'Vu',
                    s.nom AS 'Entreprise',
                    t.prenom AS 'Prénom Tuteur',
                    t.nom AS 'Nom Tuteur'
                  FROM cr 
                  JOIN utilisateur u ON cr.num_utilisateur = u.num
                  LEFT JOIN stage s ON u.num_stage = s.num
                  LEFT JOIN tuteur t ON s.num_tuteur = t.num
                  ORDER BY cr.datetime DESC";
        
        $result = mysqli_query($bdd, $query);
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        if ($format == 'pdf') {
            exporterPDF($data, 'comptes_rendus', 'Export des comptes rendus');
        } else {
            exporterCSV($data, 'comptes_rendus');
        }
        break;
        
    case 'eleves':
        // Export des élèves avec leurs stages
        $query = "SELECT 
                    u.prenom AS 'Prénom',
                    u.nom AS 'Nom',
                    u.login AS 'Login',
                    u.email AS 'Email',
                    s.nom AS 'Entreprise',
                    s.adresse AS 'Adresse',
                    s.CP AS 'Code postal',
                    s.ville AS 'Ville',
                    s.tel AS 'Téléphone entreprise',
                    s.email AS 'Email entreprise',
                    s.libelleStage AS 'Libellé stage',
                    t.prenom AS 'Prénom tuteur',
                    t.nom AS 'Nom tuteur',
                    t.tel AS 'Téléphone tuteur',
                    t.email AS 'Email tuteur',
                    COUNT(cr.num) AS 'Nombre de CR'
                  FROM utilisateur u
                  LEFT JOIN stage s ON u.num_stage = s.num
                  LEFT JOIN tuteur t ON s.num_tuteur = t.num
                  LEFT JOIN cr ON u.num = cr.num_utilisateur
                  WHERE u.type = 0
                  GROUP BY u.num
                  ORDER BY u.nom, u.prenom";
        
        $result = mysqli_query($bdd, $query);
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        if ($format == 'pdf') {
            exporterPDF($data, 'eleves_stages', 'Export des élèves et stages');
        } else {
            exporterCSV($data, 'eleves_stages');
        }
        break;
        
    case 'statistiques':
        // Export des statistiques
        $query_eleves = "SELECT COUNT(*) as total FROM utilisateur WHERE type = 0";
        $query_cr = "SELECT COUNT(*) as total FROM cr";
        $query_cr_vus = "SELECT COUNT(*) as total FROM cr WHERE vu = 1";
        $query_stages = "SELECT COUNT(*) as total FROM stage";
        
        $result_eleves = mysqli_query($bdd, $query_eleves);
        $result_cr = mysqli_query($bdd, $query_cr);
        $result_cr_vus = mysqli_query($bdd, $query_cr_vus);
        $result_stages = mysqli_query($bdd, $query_stages);
        
        $eleves = mysqli_fetch_assoc($result_eleves);
        $cr = mysqli_fetch_assoc($result_cr);
        $cr_vus = mysqli_fetch_assoc($result_cr_vus);
        $stages = mysqli_fetch_assoc($result_stages);
        
        $data = [
            ['Statistique', 'Valeur'],
            ['Nombre d\'élèves', $eleves['total']],
            ['Nombre de stages', $stages['total']],
            ['Nombre total de CR', $cr['total']],
            ['CR marqués comme vus', $cr_vus['total']],
            ['Taux de consultation', $cr['total'] > 0 ? round(($cr_vus['total'] / $cr['total']) * 100, 2) . '%' : '0%']
        ];
        
        if ($format == 'pdf') {
            exporterPDF($data, 'statistiques', 'Statistiques du système');
        } else {
            exporterCSV($data, 'statistiques');
        }
        break;
        
    default:
        die('Type d\'export non reconnu');
}
?>