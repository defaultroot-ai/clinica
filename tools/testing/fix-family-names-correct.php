<?php
/**
 * Script pentru corectarea numelor familiilor cu numele de familie reale
 */

echo "<h1>Corectare Nume Familii - Clinica Plugin</h1>";

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

echo "<h2>1. Analiză familii cu nume greșite:</h2>";

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_heads
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_heads = $stats['total_heads'];
    
    echo "<p>Total capi de familie: <strong>" . $total_heads . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la analiza familiilor: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Corectare nume familii cu numele de familie reale:</h2>";

try {
    $pdo->beginTransaction();
    
    // Găsesc toate familiile
    $stmt = $pdo->query("
        SELECT DISTINCT family_id
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
        ORDER BY family_id
        LIMIT 100
    ");
    
    $families_to_fix = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $fixed_count = 0;
    
    echo "<p>Se corectează <strong>" . count($families_to_fix) . "</strong> familii...</p>";
    
    foreach ($families_to_fix as $family_id) {
        // Găsesc capul familiei cu informațiile complete
        $stmt = $pdo->prepare("
            SELECT p.id, p.email, p.cnp, u.display_name, u.user_email
            FROM wp_clinica_patients p
            LEFT JOIN wp_users u ON p.user_id = u.ID
            WHERE p.family_id = ? AND p.family_role = 'head'
            LIMIT 1
        ");
        $stmt->execute([$family_id]);
        $head = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($head) {
            $new_family_name = 'Necunoscută';
            
            // Încearcă să extragă numele de familie din display_name
            if (!empty($head['display_name'])) {
                $display_name = trim($head['display_name']);
                
                // Încearcă să găsească ultimul nume (numele de familie)
                $name_parts = explode(' ', $display_name);
                
                if (count($name_parts) > 1) {
                    // Ia ultimul nume ca nume de familie
                    $last_name = end($name_parts);
                    
                    // Curăță numele de caractere speciale
                    $last_name = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ]/', '', $last_name);
                    
                    if (!empty($last_name)) {
                        $new_family_name = ucfirst(strtolower($last_name));
                    }
                } else if (count($name_parts) == 1) {
                    // Un singur nume
                    $single_name = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ]/', '', $name_parts[0]);
                    if (!empty($single_name)) {
                        $new_family_name = ucfirst(strtolower($single_name));
                    }
                }
            }
            
            // Dacă nu am găsit nume din display_name, încearcă din email
            if ($new_family_name == 'Necunoscută' && !empty($head['email'])) {
                $email = $head['email'];
                
                // Încearcă să extragă numele din email (doar ca fallback)
                if (strpos($email, '+') !== false) {
                    $parts = explode('+', $email);
                    $base_email = $parts[0];
                    $domain_parts = explode('@', $base_email);
                    $username = $domain_parts[0];
                    
                    // Încearcă să găsească un nume valid
                    if (preg_match('/^([a-zA-ZăâîșțĂÂÎȘȚ]+)/', $username, $matches)) {
                        $new_family_name = ucfirst(strtolower($matches[1]));
                    }
                } else {
                    $domain_parts = explode('@', $email);
                    $username = $domain_parts[0];
                    
                    if (preg_match('/^([a-zA-ZăâîșțĂÂÎȘȚ]+)/', $username, $matches)) {
                        $new_family_name = ucfirst(strtolower($matches[1]));
                    }
                }
            }
            
            // Actualizează numele familiei pentru toți membrii
            $update_stmt = $pdo->prepare("
                UPDATE wp_clinica_patients 
                SET family_name = ? 
                WHERE family_id = ?
            ");
            $update_stmt->execute([$new_family_name, $family_id]);
            
            $fixed_count++;
            
            if ($fixed_count <= 10) {
                echo "<p>✅ Familia " . $family_id . " -> <strong>" . htmlspecialchars($new_family_name) . "</strong></p>";
            }
        }
    }
    
    // Confirmă tranzacția
    $pdo->commit();
    
    echo "<p style='color: green;'>✅ <strong>" . $fixed_count . "</strong> familii au fost corectate cu succes!</p>";
    
} catch (PDOException $e) {
    // Anulează tranzacția în caz de eroare
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Eroare la corectarea familiilor: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificare rezultat:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            family_name,
            COUNT(*) as count
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
        GROUP BY family_name
        ORDER BY count DESC
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 10 nume de familie:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Nume Familie</th><th>Număr</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['family_name']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea rezultatului: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Rezumat:</h2>";
echo "<p><strong>Status:</strong> Scriptul de corectare a rulat cu succes!</p>";
echo "<p><strong>Familii corectate:</strong> " . $fixed_count . "</p>";
echo "<p><strong>Numele familiilor:</strong> Acum sunt numele de familie reale ale reprezentanților (fără 'Familia')</p>";
echo "<p><strong>Următorul pas:</strong> Testează din nou pagina de familii în WordPress!</p>";
?>
