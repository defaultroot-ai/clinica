<?php
/**
 * Script pentru corectarea TOATE familiilor cu numele de familie reale
 * În română: NUMELE DE FAMILIE ESTE PRIMUL, nu ultimul!
 */

echo "<h1>Corectare TOATE Familiile - Convenție Românească</h1>";

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

echo "<h2>2. Corectare TOATE familiile cu numele de familie reale:</h2>";

try {
    $pdo->beginTransaction();
    
    // Găsesc TOATE familiile (fără LIMIT!)
    $stmt = $pdo->query("
        SELECT DISTINCT family_id
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
        ORDER BY family_id
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
            
            // Încearcă să extragă numele de familie din display_name (CONVENȚIA ROMÂNEASCĂ!)
            if (!empty($head['display_name'])) {
                $display_name = trim($head['display_name']);
                
                // Încearcă să găsească primul nume (numele de familie în română!)
                $name_parts = explode(' ', $display_name);
                
                if (count($name_parts) > 1) {
                    // În română: PRIMUL nume este numele de familie!
                    $first_name = $name_parts[0];
                    
                    // Curăță numele de caractere speciale, dar PĂSTREAZĂ FORMATUL ORIGINAL!
                    $first_name_clean = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ\-]/', '', $first_name);
                    
                    if (!empty($first_name_clean)) {
                        // NU face lowercase! Păstrează formatul original!
                        $new_family_name = $first_name_clean;
                    }
                } else if (count($name_parts) == 1) {
                    // Un singur nume
                    $single_name = preg_replace('/[^a-zA-ZăâîșțĂÂÎȘȚ\-]/', '', $name_parts[0]);
                    if (!empty($single_name)) {
                        // NU face lowercase! Păstrează formatul original!
                        $new_family_name = $single_name;
                    }
                }
            }
            
            // Dacă nu am găsit nume din display_name, încearcă din email (doar ca fallback)
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
                        $new_family_name = $matches[1]; // Păstrează formatul original!
                    }
                } else {
                    $domain_parts = explode('@', $email);
                    $username = $domain_parts[0];
                    
                    if (preg_match('/^([a-zA-ZăâîșțĂÂÎȘȚ]+)/', $username, $matches)) {
                        $new_family_name = $matches[1]; // Păstrează formatul original!
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
            
            if ($fixed_count <= 20) {
                echo "<p>✅ Familia " . $family_id . " -> <strong>" . htmlspecialchars($new_family_name) . "</strong></p>";
            } else if ($fixed_count % 50 == 0) {
                echo "<p>📊 Progres: " . $fixed_count . " familii corectate...</p>";
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

echo "<h2>3. Verificare rezultat final:</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            family_name,
            COUNT(*) as count
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
        GROUP BY family_name
        ORDER BY count DESC
        LIMIT 20
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>Primele 20 nume de familie (corectate):</h3>";
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

echo "<h2>4. Verificare familii cu 'Familia':</h2>";

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as with_familia
        FROM wp_clinica_patients
        WHERE family_role = 'head' AND family_name LIKE 'Familia %'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $with_familia = $stats['with_familia'];
    
    if ($with_familia == 0) {
        echo "<p style='color: green;'>✅ <strong>SUCCES!</strong> Nu mai există familii cu numele 'Familia'!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Încă sunt " . $with_familia . " familii cu numele 'Familia'</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea familiilor cu 'Familia': " . $e->getMessage() . "</p>";
}

echo "<h2>5. Rezumat final:</h2>";
echo "<p><strong>Status:</strong> Scriptul de corectare a rulat cu succes!</p>";
echo "<p><strong>Familii corectate:</strong> " . $fixed_count . "</p>";
echo "<p><strong>Convenția românească:</strong> Numele de familie este PRIMUL nume din display_name</p>";
echo "<p><strong>IMPORTANT:</strong> Numele de familie PĂSTREAZĂ formatul original (nu se face lowercase!)</p>";
echo "<p><strong>Exemple corecte:</strong></p>";
echo "<ul>";
echo "<li>\"Szilagyi Mihaela\" → Numele de familie: <strong>Szilagyi</strong></li>";
echo "<li>\"Plopeanu-Achimescu Iuliana\" → Numele de familie: <strong>Plopeanu-Achimescu</strong></li>";
echo "<li>\"Cacior-Salistean Adriana-Monica\" → Numele de familie: <strong>Cacior-Salistean</strong></li>";
echo "</ul>";
echo "<p><strong>Următorul pas:</strong> Testează pagina de familii în WordPress!</p>";
?>
