<?php
/**
 * Script pentru a verifica statusul pacienților și familiilor
 */

echo "<h1>Verificare Status Pacienți și Familii - Clinica Plugin</h1>";

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

echo "<h2>1. Statistici generale pacienți:</h2>";

try {
    // Total pacienți
    $stmt = $pdo->query("SELECT COUNT(*) as total_patients FROM wp_clinica_patients");
    $total_patients = $stmt->fetch(PDO::FETCH_ASSOC)['total_patients'];
    echo "<p>Total pacienți în tabel: <strong>" . $total_patients . "</strong></p>";
    
    if ($total_patients > 0) {
        // Pacienți cu familie
        $stmt = $pdo->query("SELECT COUNT(*) as with_family FROM wp_clinica_patients WHERE family_id IS NOT NULL");
        $with_family = $stmt->fetch(PDO::FETCH_ASSOC)['with_family'];
        echo "<p>Pacienți cu familie: <strong>" . $with_family . "</strong></p>";
        
        // Pacienți fără familie
        $without_family = $total_patients - $with_family;
        echo "<p>Pacienți fără familie: <strong>" . $without_family . "</strong></p>";
        
        // Pacienți cu rol de familie
        $stmt = $pdo->query("SELECT COUNT(*) as with_role FROM wp_clinica_patients WHERE family_role IS NOT NULL");
        $with_role = $stmt->fetch(PDO::FETCH_ASSOC)['with_role'];
        echo "<p>Pacienți cu rol de familie: <strong>" . $with_role . "</strong></p>";
        
        // Pacienți cu nume de familie
        $stmt = $pdo->query("SELECT COUNT(*) as with_family_name FROM wp_clinica_patients WHERE family_name IS NOT NULL");
        $with_family_name = $stmt->fetch(PDO::FETCH_ASSOC)['with_family_name'];
        echo "<p>Pacienți cu nume de familie: <strong>" . $with_family_name . "</strong></p>";
        
        echo "<h3>Distribuția rolurilor de familie:</h3>";
        $stmt = $pdo->query("
            SELECT family_role, COUNT(*) as count 
            FROM wp_clinica_patients 
            WHERE family_role IS NOT NULL 
            GROUP BY family_role
            ORDER BY count DESC
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Rol</th><th>Număr</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['family_role']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Primele 10 pacienți (cu detalii familie):</h3>";
        $stmt = $pdo->query("
            SELECT 
                id,
                cnp,
                email,
                family_id,
                family_role,
                family_name,
                created_at
            FROM wp_clinica_patients 
            ORDER BY id 
            LIMIT 10
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>CNP</th><th>Email</th><th>Family ID</th><th>Family Role</th><th>Family Name</th><th>Created</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['cnp']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?: 'N/A') . "</td>";
            echo "<td>" . ($row['family_id'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_role'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verifică dacă există pattern-uri de email pentru familii
        echo "<h3>Pattern-uri de email pentru familii:</h3>";
        $stmt = $pdo->query("
            SELECT 
                email,
                COUNT(*) as count
            FROM wp_clinica_patients 
            WHERE email LIKE '%+%' 
            GROUP BY email 
            ORDER BY count DESC 
            LIMIT 10
        ");
        
        if ($stmt->rowCount() > 0) {
            echo "<p>Email-uri cu pattern + (pentru familii):</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Email Pattern</th><th>Număr</th></tr>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . $row['count'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Nu există email-uri cu pattern + pentru familii</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nu există pacienți în tabel!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea pacienților: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Rezumat și Recomandări</h2>";

if ($total_patients > 0) {
    if ($with_family == 0) {
        echo "<p style='color: red;'>🚨 PROBLEMĂ IDENTIFICATĂ: Nu există familii create în baza de date!</p>";
        echo "<p><strong>Posibile cauze:</strong></p>";
        echo "<ul>";
        echo "<li>Funcția de creare automată a familiilor nu a fost rulată</li>";
        echo "<li>Funcția a rulat dar a eșuat la salvarea în baza de date</li>";
        echo "<li>Probleme cu permisiunile de scriere în baza de date</li>";
        echo "<li>Erori în procesul de creare automată</li>";
        echo "</ul>";
        
        echo "<p><strong>Recomandare:</strong></p>";
        echo "<ol>";
        echo "<li>Rulează din nou funcția de creare automată a familiilor</li>";
        echo "<li>Verifică log-urile pentru erori</li>";
        echo "<li>Verifică consola browser-ului pentru erori JavaScript</li>";
        echo "<li>Verifică log-urile PHP pentru erori</li>";
        echo "</ol>";
    } else {
        echo "<p style='color: green;'>✅ Familiile există în baza de date</p>";
        echo "<p>Problema poate fi în interfață sau AJAX</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Nu există pacienți în baza de date!</p>";
    echo "<p>Trebuie să creezi pacienți mai întâi</p>";
}

echo "<p><strong>Status:</strong> Scriptul de verificare a rulat cu succes!</p>";
?>
