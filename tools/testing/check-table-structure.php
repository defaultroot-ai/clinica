<?php
/**
 * Verifică structura tabelului wp_clinica_patients
 */

// Conectare la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Eroare conexiune: " . $e->getMessage());
}

echo "<h1>🔍 Verificare Structură Tabel wp_clinica_patients</h1>";

// Verifică structura tabelului
$stmt = $pdo->query("DESCRIBE wp_clinica_patients");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Coloanele tabelului:</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Coloană</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Tip</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Null</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Key</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Default</th>";
echo "</tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $column['Field'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $column['Type'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $column['Null'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $column['Key'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($column['Default'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Verifică primii 3 pacienți
echo "<h2>Primii 3 pacienți:</h2>";
$stmt = $pdo->query("SELECT * FROM wp_clinica_patients LIMIT 3");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($patients) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    foreach (array_keys($patients[0]) as $column) {
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>" . $column . "</th>";
    }
    echo "</tr>";
    
    foreach ($patients as $patient) {
        echo "<tr>";
        foreach ($patient as $value) {
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($value ?: 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nu există pacienți în tabel.</p>";
} 