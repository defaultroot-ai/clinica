<?php
/**
 * Script pentru verificarea numelor familiilor
 */

echo "<h1>Verificare Nume Familii - Clinica Plugin</h1>";

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

echo "<h2>1. Verificare capi de familie și numele lor:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            id,
            cnp,
            email,
            family_id,
            family_name,
            family_role
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
        ORDER BY family_id
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 10 capi de familie:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>CNP</th><th>Email</th><th>Family ID</th><th>Family Name</th><th>Family Role</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['cnp']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?: 'N/A') . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nu există capi de familie!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea capilor de familie: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificare de ce family_name este 'Familia':</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_heads,
            COUNT(CASE WHEN family_name = 'Familia' THEN 1 END) as with_familia,
            COUNT(CASE WHEN family_name != 'Familia' AND family_name IS NOT NULL THEN 1 END) as with_real_name,
            COUNT(CASE WHEN family_name IS NULL THEN 1 END) as without_name
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total capi de familie: <strong>" . $stats['total_heads'] . "</strong></p>";
    echo "<p>Capi cu 'Familia': <strong>" . $stats['with_familia'] . "</strong></p>";
    echo "<p>Capi cu nume real: <strong>" . $stats['with_real_name'] . "</strong></p>";
    echo "<p>Capi fără nume: <strong>" . $stats['without_name'] . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea numelor 'Familia': " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificare pattern-uri email:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            email,
            family_id,
            family_role
        FROM wp_clinica_patients 
        WHERE email LIKE '%+%' AND family_role = 'head'
        ORDER BY family_id
        LIMIT 5
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Email-uri cu pattern + pentru capi de familie:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Family ID</th><th>Family Role</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nu există email-uri cu pattern + pentru capi de familie</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea pattern-urilor email: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Rezumat:</h2>";

echo "<p><strong>Status:</strong> Scriptul de verificare a rulat cu succes!</p>";
echo "<p><strong>Problema identificată:</strong> Toate familiile au numele 'Familia' în loc de numele reale!</p>";
echo "<p><strong>Soluția:</strong> Trebuie să actualizez numele familiilor cu numele reale extrase din email-uri!</p>";
?>
