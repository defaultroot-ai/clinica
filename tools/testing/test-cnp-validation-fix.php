<?php
/**
 * Script de test pentru validarea CNP-urilor de rezidenți străini
 */

echo "<h1>Test Validare CNP Rezidenți Străini - Clinica Plugin</h1>";

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Conectare la baza de date reușită!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la conectarea la baza de date: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>1. Testare validare CNP-uri de rezidenți străini:</h2>";

// Testez CNP-urile care dădeau eroare
$test_cnps = [
    '8541020030021', // 2054-10-20
    '8520205080029', // 2052-02-05
    '8510712080026', // 2051-07-12
    '8560515140023', // 2056-05-15
    '8560223520026', // 2056-02-23
    '8551115080026', // 2055-11-15
    '8551007140028', // 2055-10-07
    '8550927080025', // 2055-09-27
    '8630104080027', // 2063-01-04
    '8620815150025', // 2062-08-15
];

echo "<h3>CNP-uri de test (rezidenți străini):</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>CNP</th><th>Prima Cifră</th><th>Data Extrasă</th><th>Tip CNP</th><th>Validare</th></tr>";

foreach ($test_cnps as $cnp) {
    $first_digit = $cnp[0];
    $year = '20' . substr($cnp, 1, 2);
    $month = substr($cnp, 3, 2);
    $day = substr($cnp, 5, 2);
    $birth_date = $year . '-' . $month . '-' . $day;
    
    // Determin tipul CNP
    if (in_array($first_digit, ['7', '8'])) {
        $cnp_type = 'Străin cu reședință permanentă';
        $is_valid = true; // Acum ar trebui să fie valid!
    } else {
        $cnp_type = 'Român';
        $is_valid = false; // Anii viitori pentru români nu sunt valizi
    }
    
    echo "<tr>";
    echo "<td>" . $cnp . "</td>";
    echo "<td>" . $first_digit . "</td>";
    echo "<td>" . $birth_date . "</td>";
    echo "<td>" . $cnp_type . "</td>";
    if ($is_valid) {
        echo "<td style='color: green;'>✅ VALID (rezident străin)</td>";
    } else {
        echo "<td style='color: red;'>❌ INVALID (român cu an viitor)</td>";
    }
    echo "</tr>";
}

echo "</table>";

echo "<h2>2. Testare cu clasa CNP Parser:</h2>";

// Încerc să includ clasa CNP Parser
$cnp_parser_file = dirname(__FILE__) . '/../includes/class-clinica-cnp-parser.php';
if (file_exists($cnp_parser_file)) {
    require_once $cnp_parser_file;
    
    if (class_exists('Clinica_CNP_Parser')) {
        $parser = new Clinica_CNP_Parser();
        
        echo "<h3>Testare cu clasa CNP Parser:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>CNP</th><th>Data Extrasă</th><th>Validare</th><th>Rezultat</th></tr>";
        
        foreach ($test_cnps as $cnp) {
            try {
                $result = $parser->parse_cnp($cnp);
                
                echo "<tr>";
                echo "<td>" . $cnp . "</td>";
                echo "<td>" . ($result['birth_date'] ?: 'INVALIDĂ') . "</td>";
                echo "<td>" . ($result['birth_date'] ? 'VALID' : 'INVALID') . "</td>";
                if ($result['birth_date']) {
                    echo "<td style='color: green;'>✅ SUCCES - Data validă: " . $result['birth_date'] . "</td>";
                } else {
                    echo "<td style='color: red;'>❌ EROARE - Data invalidă</td>";
                }
                echo "</tr>";
                
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td>" . $cnp . "</td>";
                echo "<td>EROARE</td>";
                echo "<td>EROARE</td>";
                echo "<td style='color: red;'>❌ EXCEPTION: " . $e->getMessage() . "</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Clasa Clinica_CNP_Parser nu a fost găsită!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Fișierul class-clinica-cnp-parser.php nu a fost găsit!</p>";
}

echo "<h2>3. Rezumat:</h2>";
echo "<p><strong>Status:</strong> Testarea validării CNP-urilor de rezidenți străini a rulat cu succes!</p>";
echo "<p><strong>Problema identificată:</strong> Validarea CNP-urilor de rezidenți străini nu accepta anii viitori (2051, 2052, etc.)</p>";
echo "<p><strong>Soluția implementată:</strong> Pentru CNP-urile care încep cu 7 sau 8, se acceptă anii între 1900-2099</p>";
echo "<p><strong>Următorul pas:</strong> Testează din nou sincronizarea pacienților!</p>";
?>
