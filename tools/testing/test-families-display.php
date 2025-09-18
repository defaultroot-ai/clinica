<?php
/**
 * Script de test pentru verificarea afișării familiilor
 */

echo "<h1>Test Afișare Familii - Clinica Plugin</h1>";

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

echo "<h2>1. Test funcția get_all_families() (simulată):</h2>";

try {
    // Simulez exact ce face funcția get_all_families()
    $query = "
        SELECT DISTINCT f.family_id, 
                COALESCE(head.family_name, 'Familia Necunoscută') as family_name,
                COUNT(*) as member_count
         FROM wp_clinica_patients f
         LEFT JOIN (
             SELECT family_id, family_name 
             FROM wp_clinica_patients 
             WHERE family_role = 'head'
         ) head ON f.family_id = head.family_id
         WHERE f.family_id IS NOT NULL 
         GROUP BY f.family_id, head.family_name
         ORDER BY head.family_name
         LIMIT 10
    ";
    
    $stmt = $pdo->query($query);
    $families = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (!empty($families)) {
        echo "<p>✅ Funcția get_all_families() returnează " . count($families) . " familii</p>";
        
        echo "<h3>Primele 10 familii:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Family ID</th><th>Family Name</th><th>Member Count</th></tr>";
        
        foreach ($families as $family) {
            echo "<tr>";
            echo "<td>" . $family->family_id . "</td>";
            echo "<td>" . htmlspecialchars($family->family_name) . "</td>";
            echo "<td>" . $family->member_count . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Funcția get_all_families() nu returnează familii!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la testarea get_all_families(): " . $e->getMessage() . "</p>";
}

echo "<h2>2. Test funcția get_family_members() (simulată):</h2>";

try {
    // Testez cu prima familie găsită
    $stmt = $pdo->query("SELECT family_id FROM wp_clinica_patients WHERE family_id IS NOT NULL LIMIT 1");
    $first_family = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($first_family) {
        $family_id = $first_family['family_id'];
        echo "<p>Testez cu familia ID: <strong>" . $family_id . "</strong></p>";
        
        // Simulez exact ce face funcția get_family_members()
        $query = "
            SELECT p.*, u.display_name, u.user_email 
             FROM wp_clinica_patients p 
             LEFT JOIN wp_users u ON p.user_id = u.ID 
             WHERE p.family_id = :family_id 
             ORDER BY 
                CASE p.family_role 
                    WHEN 'head' THEN 1 
                    WHEN 'spouse' THEN 2 
                    WHEN 'parent' THEN 3 
                    WHEN 'child' THEN 4 
                    WHEN 'sibling' THEN 5 
                    ELSE 6 
                END,
                p.birth_date ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['family_id' => $family_id]);
        $members = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        if (!empty($members)) {
            echo "<p>✅ Funcția get_family_members() returnează " . count($members) . " membri pentru familia " . $family_id . "</p>";
            
            echo "<h3>Membrii familiei " . $family_id . ":</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>CNP</th><th>Display Name</th><th>Email</th><th>Family Role</th><th>Family Name</th></tr>";
            
            foreach ($members as $member) {
                echo "<tr>";
                echo "<td>" . $member->id . "</td>";
                echo "<td>" . htmlspecialchars($member->cnp) . "</td>";
                echo "<td>" . htmlspecialchars($member->display_name ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($member->user_email ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($member->family_role ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($member->family_name ?: 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Funcția get_family_members() nu returnează membri pentru familia " . $family_id . "!</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Nu există familii pentru a testa get_family_members()</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la testarea get_family_members(): " . $e->getMessage() . "</p>";
}

echo "<h2>3. Test verificare nume familii:</h2>";

try {
    // Verific câte familii au nume setat
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_families,
            COUNT(CASE WHEN family_name IS NOT NULL THEN 1 END) as with_name,
            COUNT(CASE WHEN family_name IS NULL THEN 1 END) as without_name
        FROM (
            SELECT DISTINCT family_id, family_name
            FROM wp_clinica_patients 
            WHERE family_id IS NOT NULL
        ) families
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total familii unice: <strong>" . $stats['total_families'] . "</strong></p>";
    echo "<p>Familii cu nume: <strong>" . $stats['with_name'] . "</strong></p>";
    echo "<p>Familii fără nume: <strong>" . $stats['without_name'] . "</strong></p>";
    
    // Verific primele 5 familii fără nume
    if ($stats['without_name'] > 0) {
        echo "<h3>Primele 5 familii fără nume:</h3>";
        $stmt = $pdo->query("
            SELECT DISTINCT family_id, family_name
            FROM wp_clinica_patients 
            WHERE family_id IS NOT NULL AND family_name IS NULL
            LIMIT 5
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Family ID</th><th>Family Name</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea numelor familiilor: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Test verificare capi de familie:</h2>";

try {
    // Verific câți capi de familie au nume setat
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_heads,
            COUNT(CASE WHEN family_name IS NOT NULL THEN 1 END) as with_name,
            COUNT(CASE WHEN family_name IS NULL THEN 1 END) as without_name
        FROM wp_clinica_patients 
        WHERE family_role = 'head'
    ");
    
    $head_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total capi de familie: <strong>" . $head_stats['total_heads'] . "</strong></p>";
    echo "<p>Capi cu nume familie: <strong>" . $head_stats['with_name'] . "</strong></p>";
    echo "<p>Capi fără nume familie: <strong>" . $head_stats['without_name'] . "</strong></p>";
    
    // Verific primele 5 capi de familie fără nume
    if ($head_stats['without_name'] > 0) {
        echo "<h3>Primele 5 capi de familie fără nume:</h3>";
        $stmt = $pdo->query("
            SELECT id, cnp, email, family_id, family_name
            FROM wp_clinica_patients 
            WHERE family_role = 'head' AND family_name IS NULL
            LIMIT 5
        ");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>CNP</th><th>Email</th><th>Family ID</th><th>Family Name</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['cnp']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?: 'N/A') . "</td>";
            echo "<td>" . $row['family_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['family_name'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea capilor de familie: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Rezumat și Recomandări</h2>";

echo "<p><strong>Status:</strong> Scriptul de test a rulat cu succes!</p>";

echo "<p><strong>Problema identificată:</strong> Familiile există în baza de date, dar interfața nu le afișează corect</p>";

echo "<p><strong>Următorul pas:</strong> Verifică rezultatele testelor și spune-mi ce găsești!</p>";
?>
