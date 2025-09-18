<?php
/**
 * Script de test pentru API-ul de identificare
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>🧪 Test API Identificare Pacienți</h1>";

// Conectare la baza de date
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "<p style='color: #28a745;'>✅ Conectare la baza de date: OK</p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Eroare la conectare!</h3>";
    echo "<p>Eroare: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// Test 1: Verifică utilizatorii din wp_users
echo "<h3>📊 Test 1: Utilizatori din wp_users</h3>";
try {
    $stmt = $pdo->query("SELECT ID, user_login, user_email, display_name FROM wp_users ORDER BY ID LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p style='color: #28a745;'>✅ Găsiți " . count($users) . " utilizatori</p>";
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li><strong>{$user['user_login']}</strong> (ID: {$user['ID']}, Email: {$user['user_email']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există utilizatori în wp_users</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare: " . $e->getMessage() . "</p>";
}

// Test 2: Verifică primary_phone în wp_usermeta
echo "<h3>📱 Test 2: Primary Phone în wp_usermeta</h3>";
try {
    $stmt = $pdo->query("
        SELECT user_id, meta_value 
        FROM wp_usermeta 
        WHERE meta_key = 'primary_phone'
        ORDER BY user_id
    ");
    $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($phones) > 0) {
        echo "<p style='color: #28a745;'>✅ Găsite " . count($phones) . " numere de telefon</p>";
        echo "<ul>";
        foreach ($phones as $phone) {
            echo "<li>User ID: {$phone['user_id']} - Tel: {$phone['meta_value']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există primary_phone în wp_usermeta</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare: " . $e->getMessage() . "</p>";
}

// Test 3: Simulează query-ul din API
echo "<h3>🔍 Test 3: Simulare Query API</h3>";
try {
    // Test cu primul user_login
    $stmt = $pdo->query("SELECT ID, user_login FROM wp_users ORDER BY ID LIMIT 1");
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_user) {
        $test_login = $test_user['user_login'];
        echo "<p>Test cu user_login: <strong>$test_login</strong></p>";
        
        // Simulează query-ul din API
        $stmt = $pdo->prepare("
            SELECT u.ID, u.user_login, u.user_email, u.display_name,
                   um_primary_phone.meta_value as primary_phone,
                   um_first_name.meta_value as first_name,
                   um_last_name.meta_value as last_name,
                   um_nickname.meta_value as nickname
            FROM wp_users u
            LEFT JOIN wp_usermeta um_primary_phone ON u.ID = um_primary_phone.user_id AND um_primary_phone.meta_key = 'primary_phone'
            LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
            LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
            LEFT JOIN wp_usermeta um_nickname ON u.ID = um_nickname.user_id AND um_nickname.meta_key = 'nickname'
            WHERE u.user_login = ?
        ");
        $stmt->execute([$test_login]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($result) > 0) {
            echo "<p style='color: #28a745;'>✅ Query funcționează! Găsit utilizator:</p>";
            echo "<pre>" . print_r($result[0], true) . "</pre>";
        } else {
            echo "<p style='color: #ffc107;'>⚠️ Query nu returnează rezultate pentru $test_login</p>";
        }
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există utilizatori pentru test</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la simulare: " . $e->getMessage() . "</p>";
}

// Test 4: Test direct API
echo "<h3>🌐 Test 4: Test Direct API</h3>";
try {
    // Simulează un request POST către API
    $test_data = ['identifier' => $test_user['user_login'] ?? 'test'];
    
    // Include API-ul direct
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $test_data;
    
    // Capturează output-ul
    ob_start();
    include '../../api/identify-patient-simple.php';
    $api_output = ob_get_clean();
    
    echo "<p>API Response:</p>";
    echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la test API: " . $e->getMessage() . "</p>";
}

echo "<h3>📋 Următorii pași:</h3>";
echo "<ol>";
echo "<li>Verifică rezultatele testelor de mai sus</li>";
echo "<li>Dacă API-ul nu funcționează, verifică log-urile</li>";
echo "<li>Testează cu date reale din baza ta de date</li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    margin: 10px 0;
    padding-left: 20px;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style> 