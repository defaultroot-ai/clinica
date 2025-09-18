<?php
/**
 * Script de debug simplu pentru familii - fără dependențe WordPress
 */

echo "<h1>Debug Familii - Clinica Plugin (Versiune Simplă)</h1>";

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm'; // Numele bazei de date
$username = 'root'; // Utilizatorul default XAMPP
$password = ''; // Fără parolă pentru XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Conectare la baza de date reușită!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la conectarea la baza de date: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>1. Verificare structură tabel wp_clinica_patients</h2>";

try {
    // Verifică dacă tabelul există
    $stmt = $pdo->query("SHOW TABLES LIKE 'wp_clinica_patients'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabelul wp_clinica_patients există</p>";
        
        // Verifică structura tabelului
        $stmt = $pdo->query("DESCRIBE wp_clinica_patients");
        echo "<h3>Structura tabelului:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Tabelul wp_clinica_patients NU există!</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea tabelului: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Verificare familii în baza de date</h2>";

try {
    // Verifică câte familii există
    $stmt = $pdo->query("SELECT COUNT(DISTINCT family_id) as total_families FROM wp_clinica_patients WHERE family_id IS NOT NULL");
    $family_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_families'];
    echo "<p>Numărul total de familii: <strong>" . $family_count . "</strong></p>";
    
    if ($family_count > 0) {
        // Verifică câți membri au familii
        $stmt = $pdo->query("SELECT COUNT(*) as total_members FROM wp_clinica_patients WHERE family_id IS NOT NULL");
        $member_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_members'];
        echo "<p>Numărul total de membri cu familie: <strong>" . $member_count . "</strong></p>";
        
        // Verifică câți capi de familie există
        $stmt = $pdo->query("SELECT COUNT(*) as head_count FROM wp_clinica_patients WHERE family_role = 'head'");
        $head_count = $stmt->fetch(PDO::FETCH_ASSOC)['head_count'];
        echo "<p>Numărul de capi de familie: <strong>" . $head_count . "</strong></p>";
        
        echo "<h3>Primele 10 familii cu detalii:</h3>";
        $stmt = $pdo->query("
            SELECT 
                family_id,
                family_name,
                family_role,
                COUNT(*) as member_count
            FROM wp_clinica_patients 
            WHERE family_id IS NOT NULL 
            GROUP BY family_id, family_name, family_role
            ORDER BY family_id 
            LIMIT 10
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Family ID</th><th>Family Name</th><th>Role</th><th>Member Count</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role'] ?: 'NULL') . "</td>";
            echo "<td>" . $row['member_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Exemplu de familie completă:</h3>";
        $stmt = $pdo->query("
            SELECT 
                p.*,
                u.display_name,
                u.user_email
            FROM wp_clinica_patients p
            LEFT JOIN wp_users u ON p.user_id = u.ID
            WHERE p.family_id = (
                SELECT family_id 
                FROM wp_clinica_patients 
                WHERE family_role = 'head' 
                LIMIT 1
            )
            ORDER BY 
                CASE p.family_role 
                    WHEN 'head' THEN 1 
                    WHEN 'spouse' THEN 2 
                    WHEN 'parent' THEN 3 
                    WHEN 'child' THEN 4 
                    WHEN 'sibling' THEN 5 
                    ELSE 6 
                END
            LIMIT 5
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Family ID</th><th>Family Name</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['display_name'] ?: 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role'] ?: 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['user_email'] ?: 'N/A') . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ Nu există familii în baza de date</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea familiilor: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Rezumat și Recomandări</h2>";

if ($family_count > 0) {
    echo "<p style='color: green;'>✅ Familiile există în baza de date (" . $family_count . " familii)</p>";
    echo "<p>Problema cu afișarea în interfață poate fi:</p>";
    echo "<ul>";
    echo "<li>JavaScript-ul AJAX nu se execută</li>";
    echo "<li>Există o eroare în consola browser-ului</li>";
    echo "<li>Funcția AJAX returnează o eroare</li>";
    echo "<li>Probleme cu nonce-ul sau permisiunile</li>";
    echo "</ul>";
    
    echo "<p><strong>Recomandare:</strong></p>";
    echo "<ol>";
    echo "<li>Verifică consola browser-ului pentru erori JavaScript</li>";
    echo "<li>Verifică log-urile PHP pentru erori</li>";
    echo "<li>Testează funcția AJAX direct</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red;'>❌ Nu există familii în baza de date!</p>";
    echo "<p>Trebuie să creezi familii mai întâi folosind funcția de creare automată.</p>";
}

echo "<p><strong>Status:</strong> Scriptul de debug a rulat cu succes!</p>";
?>
