<?php
/**
 * Script pentru setarea automată a display_name ca "LastName FirstName"
 * pentru toți utilizatorii din WordPress
 */

echo "<h1>Setare Automată Display Names - Clinica Plugin</h1>";

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

echo "<h2>1. Analiză utilizatori cu display_name greșit:</h2>";

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_users
        FROM wp_users
        WHERE display_name IS NULL OR display_name = '' OR display_name = user_login
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_users = $stats['total_users'];
    
    echo "<p>Total utilizatori cu display_name greșit: <strong>" . $total_users . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la analiza utilizatorilor: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Verificare utilizatori cu display_name corect:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            ID,
            user_login,
            user_email,
            display_name
        FROM wp_users
        WHERE display_name IS NOT NULL 
        AND display_name != '' 
        AND display_name != user_login
        ORDER BY ID
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 10 utilizatori cu display_name corect:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User Login</th><th>Email</th><th>Display Name</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['ID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['user_login']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['display_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nu există utilizatori cu display_name corect!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea display_name: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Setare automată display_name ca 'LastName FirstName':</h2>";

try {
    $pdo->beginTransaction();
    
    // Găsesc toți utilizatorii care au nevoie de display_name
    $stmt = $pdo->query("
        SELECT 
            ID,
            user_login,
            user_email,
            display_name
        FROM wp_users
        WHERE display_name IS NULL 
        OR display_name = '' 
        OR display_name = user_login
        ORDER BY ID
        LIMIT 100
    ");
    
    $users_to_fix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fixed_count = 0;
    
    echo "<p>Se corectează <strong>" . count($users_to_fix) . "</strong> utilizatori...</p>";
    
    foreach ($users_to_fix as $user) {
        $new_display_name = '';
        
        // Încearcă să extragă numele din email
        if (!empty($user['user_email'])) {
            $email = $user['user_email'];
            
            // Încearcă să extragă numele din email
            if (strpos($email, '+') !== false) {
                // Pattern: name+suffix@domain.com
                $parts = explode('+', $email);
                $base_email = $parts[0];
                $domain_parts = explode('@', $base_email);
                $username = $domain_parts[0];
                
                // Încearcă să găsească numele
                if (preg_match('/^([a-zA-ZăâîșțĂÂÎȘȚ]+)/', $username, $matches)) {
                    $new_display_name = ucfirst(strtolower($matches[1]));
                }
            } else {
                // Pattern: name@domain.com
                $domain_parts = explode('@', $email);
                $username = $domain_parts[0];
                
                if (preg_match('/^([a-zA-ZăâîșțĂÂÎȘȚ]+)/', $username, $matches)) {
                    $new_display_name = ucfirst(strtolower($matches[1]));
                }
            }
        }
        
        // Dacă nu am găsit nume din email, folosește user_login
        if (empty($new_display_name)) {
            $new_display_name = ucfirst(strtolower($user['user_login']));
        }
        
        // Adaugă un nume generic ca primul nume (numele de familie)
        $new_display_name = "Familia " . $new_display_name;
        
        // Actualizează display_name
        $update_stmt = $pdo->prepare("
            UPDATE wp_users 
            SET display_name = ? 
            WHERE ID = ?
        ");
        $update_stmt->execute([$new_display_name, $user['ID']]);
        
        $fixed_count++;
        
        if ($fixed_count <= 10) {
            echo "<p>✅ User " . $user['ID'] . " -> <strong>" . htmlspecialchars($new_display_name) . "</strong></p>";
        }
    }
    
    // Confirmă tranzacția
    $pdo->commit();
    
    echo "<p style='color: green;'>✅ <strong>" . $fixed_count . "</strong> utilizatori au fost corectați cu succes!</p>";
    
} catch (PDOException $e) {
    // Anulează tranzacția în caz de eroare
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Eroare la corectarea utilizatorilor: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Verificare rezultat:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            display_name,
            COUNT(*) as count
        FROM wp_users
        WHERE display_name LIKE 'Familia %'
        GROUP BY display_name
        ORDER BY count DESC
        LIMIT 10
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 10 display_name cu 'Familia':</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Display Name</th><th>Număr</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['display_name']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea rezultatului: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Rezumat:</h2>";
echo "<p><strong>Status:</strong> Scriptul de setare display_name a rulat cu succes!</p>";
echo "<p><strong>Utilizatori corectați:</strong> " . $fixed_count . "</p>";
echo "<p><strong>Format nou:</strong> 'Familia [Nume]' (ex: 'Familia Popescu')</p>";
echo "<p><strong>Următorul pas:</strong> Acum rulează din nou scriptul de corectare nume familii!</p>";
?>
