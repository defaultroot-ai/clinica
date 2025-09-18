<?php
/**
 * Script simplu pentru corectarea numelor familiilor
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

echo "<h2>1. Analiză familii cu nume 'Familia':</h2>";

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as with_familia
        FROM wp_clinica_patients 
        WHERE family_role = 'head' AND family_name = 'Familia'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $families_to_fix = $stats['with_familia'];
    
    echo "<p>Capi cu 'Familia': <strong>" . $families_to_fix . "</strong></p>";
    
    if ($families_to_fix == 0) {
        echo "<p style='color: green;'>✅ Toate familiile au nume corecte!</p>";
        exit;
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la analiza familiilor: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Corectare nume familii:</h2>";

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->query("
        SELECT DISTINCT family_id
        FROM wp_clinica_patients 
        WHERE family_role = 'head' AND family_name = 'Familia'
        ORDER BY family_id
        LIMIT 50
    ");
    
    $families_to_fix = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $fixed_count = 0;
    
    echo "<p>Se corectează <strong>" . count($families_to_fix) . "</strong> familii...</p>";
    
    foreach ($families_to_fix as $family_id) {
        $stmt = $pdo->prepare("
            SELECT email
            FROM wp_clinica_patients 
            WHERE family_id = ? AND family_role = 'head'
            LIMIT 1
        ");
        $stmt->execute([$family_id]);
        $head = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($head && !empty($head['email'])) {
            $email = $head['email'];
            $new_family_name = 'Familia Necunoscută';
            
            if (strpos($email, '+') !== false) {
                $parts = explode('+', $email);
                $base_email = $parts[0];
                $domain_parts = explode('@', $base_email);
                $username = $domain_parts[0];
                
                if (preg_match('/^([a-zA-Z]+)/', $username, $matches)) {
                    $first_name = ucfirst(strtolower($matches[1]));
                    $new_family_name = "Familia " . $first_name;
                } else {
                    $new_family_name = "Familia " . ucfirst($username);
                }
            } else {
                $domain_parts = explode('@', $email);
                $username = $domain_parts[0];
                
                if (preg_match('/^([a-zA-Z]+)/', $username, $matches)) {
                    $first_name = ucfirst(strtolower($matches[1]));
                    $new_family_name = "Familia " . $first_name;
                } else {
                    $new_family_name = "Familia " . ucfirst($username);
                }
            }
            
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
    
    $pdo->commit();
    
    echo "<p style='color: green;'>✅ <strong>" . $fixed_count . "</strong> familii au fost corectate cu succes!</p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Eroare la corectarea familiilor: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificare rezultat:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN family_name = 'Familia' THEN 1 END) as with_familia,
            COUNT(CASE WHEN family_name != 'Familia' AND family_name IS NOT NULL THEN 1 END) as with_real_name
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Capi cu 'Familia': <strong>" . $stats['with_familia'] . "</strong></p>";
    echo "<p>Capi cu nume real: <strong>" . $stats['with_real_name'] . "</strong></p>";
    
    if ($stats['with_familia'] == 0) {
        echo "<p style='color: green;'>🎉 <strong>SUCCES!</strong> Toate familiile au nume corecte acum!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Încă sunt " . $stats['with_familia'] . " familii cu nume 'Familia'</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea rezultatului: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Rezumat:</h2>";
echo "<p><strong>Status:</strong> Scriptul de corectare a rulat cu succes!</p>";
echo "<p><strong>Familii corectate:</strong> " . $fixed_count . "</p>";
echo "<p><strong>Următorul pas:</strong> Testează din nou pagina de familii în WordPress!</p>";
?>
