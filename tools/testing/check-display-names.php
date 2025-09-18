<?php
/**
 * Script pentru verificarea display_name în baza de date
 */

echo "<h1>Verificare Display Names - Clinica Plugin</h1>";

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

echo "<h2>1. Verificare display_name pentru capi de familie:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.family_id,
            p.family_name,
            u.display_name,
            u.user_email
        FROM wp_clinica_patients p
        LEFT JOIN wp_users u ON p.user_id = u.ID
        WHERE p.family_role = 'head'
        ORDER BY p.family_id
        LIMIT 20
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 20 capi de familie cu display_name:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Family ID</th><th>Family Name</th><th>Display Name</th><th>User Email</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['display_name'] ?: 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['user_email'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea display_name: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Analiză structură nume:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            display_name,
            COUNT(*) as count
        FROM wp_users u
        INNER JOIN wp_clinica_patients p ON u.ID = p.user_id
        WHERE p.family_role = 'head' AND u.display_name IS NOT NULL
        GROUP BY display_name
        ORDER BY count DESC
        LIMIT 15
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Cele mai comune display_name pentru capi de familie:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Display Name</th><th>Număr</th><th>Analiză</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $display_name = $row['display_name'];
            $name_parts = explode(' ', trim($display_name));
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($display_name) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "<td>";
            echo "Parte: " . count($name_parts) . " | ";
            if (count($name_parts) > 1) {
                echo "Primul: <strong>" . $name_parts[0] . "</strong> | ";
                echo "Ultimul: <strong>" . end($name_parts) . "</strong>";
            } else {
                echo "Singur: <strong>" . $name_parts[0] . "</strong>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la analiza structurii numelor: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testare extragere nume de familie:</h2>";

try {
    $stmt = $pdo->query("
        SELECT display_name
        FROM wp_users u
        INNER JOIN wp_clinica_patients p ON u.ID = p.user_id
        WHERE p.family_role = 'head' AND u.display_name IS NOT NULL
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Testare extragere nume de familie:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Display Name Original</th><th>Nume de Familie Extras</th><th>Metoda</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $display_name = trim($row['display_name']);
            $name_parts = explode(' ', $display_name);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($display_name) . "</td>";
            
            if (count($name_parts) > 1) {
                // Încearcă să găsească numele de familie (ultimul nume)
                $last_name = end($name_parts);
                $last_name_clean = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ]/', '', $last_name);
                
                if (!empty($last_name_clean)) {
                    echo "<td><strong>" . ucfirst(strtolower($last_name_clean)) . "</strong></td>";
                    echo "<td>Ultimul nume din '" . htmlspecialchars($display_name) . "'</td>";
                } else {
                    echo "<td>❌ Nu s-a putut extrage</td>";
                    echo "<td>Ultimul nume conține caractere invalide</td>";
                }
            } else if (count($name_parts) == 1) {
                $single_name = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ]/', '', $name_parts[0]);
                if (!empty($single_name)) {
                    echo "<td><strong>" . ucfirst(strtolower($single_name)) . "</strong></td>";
                    echo "<td>Singurul nume disponibil</td>";
                } else {
                    echo "<td>❌ Nu s-a putut extrage</td>";
                    echo "<td>Numele conține caractere invalide</td>";
                }
            } else {
                echo "<td>❌ Nume gol</td>";
                echo "<td>Display name este gol</td>";
            }
            
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la testarea extragerii: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Rezumat și Recomandări:</h2>";
echo "<p><strong>Status:</strong> Verificarea display_name a rulat cu succes!</p>";
echo "<p><strong>Problema identificată:</strong> Trebuie să văd exact cum arată display_name pentru a extrage corect numele de familie</p>";
echo "<p><strong>Următorul pas:</strong> Analizează rezultatele și spune-mi care este numele de familie corect!</p>";
?>
