<?php
/**
 * Script pentru verificarea utilizatorilor existenți în tabelele WordPress
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>👥 Verificare Utilizatori Existenți</h1>";

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

// Verifică toți utilizatorii din wp_users
echo "<h3>📊 Toți utilizatorii din wp_users:</h3>";

try {
    $stmt = $pdo->query("
        SELECT ID, user_login, user_email, display_name, user_registered
        FROM wp_users 
        ORDER BY ID
    ");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($all_users) > 0) {
        echo "<p style='color: #28a745;'>📊 Număr total utilizatori: " . count($all_users) . "</p>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Login</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Email</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Display Name</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Data Înregistrare</th>";
        echo "</tr>";
        
        foreach ($all_users as $user) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$user['ID']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$user['user_login']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$user['user_email']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$user['display_name']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$user['user_registered']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există utilizatori în wp_users</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea utilizatorilor: " . $e->getMessage() . "</p>";
}

// Verifică toate meta datele din wp_usermeta
echo "<h3>📋 Toate meta datele din wp_usermeta:</h3>";

try {
    $stmt = $pdo->query("
        SELECT user_id, meta_key, meta_value
        FROM wp_usermeta 
        ORDER BY user_id, meta_key
    ");
    $meta_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($meta_data) > 0) {
        echo "<p style='color: #28a745;'>📊 Număr meta date găsite: " . count($meta_data) . "</p>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>User ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Meta Key</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Meta Value</th>";
        echo "</tr>";
        
        foreach ($meta_data as $meta) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['user_id']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['meta_key']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['meta_value']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există meta date relevante în wp_usermeta</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea meta datelor: " . $e->getMessage() . "</p>";
}

    // Verifică utilizatorii cu date complete pentru identificare
    echo "<h3>🔍 Utilizatori cu date pentru identificare:</h3>";
    
    try {
        $stmt = $pdo->query("
            SELECT u.ID, u.user_login, u.user_email, u.display_name,
                   um_primary_phone.meta_value as primary_phone,
                   um_first_name.meta_value as first_name,
                   um_last_name.meta_value as last_name
            FROM wp_users u
            LEFT JOIN wp_usermeta um_primary_phone ON u.ID = um_primary_phone.user_id AND um_primary_phone.meta_key = 'primary_phone'
            LEFT JOIN wp_usermeta um_first_name ON u.ID = um_first_name.user_id AND um_first_name.meta_key = 'first_name'
            LEFT JOIN wp_usermeta um_last_name ON u.ID = um_last_name.user_id AND um_last_name.meta_key = 'last_name'
            WHERE u.user_login IS NOT NULL 
               OR um_primary_phone.meta_value IS NOT NULL 
               OR u.user_email IS NOT NULL
            ORDER BY u.ID
        ");
        $users_with_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users_with_data) > 0) {
            echo "<p style='color: #28a745;'>📊 Număr utilizatori cu date pentru identificare: " . count($users_with_data) . "</p>";
            
            echo "<h4>👥 Utilizatori disponibili pentru testare:</h4>";
            echo "<ul>";
            foreach ($users_with_data as $user) {
                $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if (empty($full_name)) {
                    $full_name = $user['display_name'] ?? $user['user_login'];
                }
                
                echo "<li><strong>$full_name</strong> (ID: {$user['ID']})";
                
                $identifiers = [];
                if (!empty($user['user_login'])) {
                    $identifiers[] = "CNP/Username: {$user['user_login']}";
                }
                if (!empty($user['primary_phone'])) {
                    $identifiers[] = "Tel: {$user['primary_phone']}";
                }
                if (!empty($user['user_email'])) {
                    $identifiers[] = "Email: {$user['user_email']}";
                }
                
                if (!empty($identifiers)) {
                    echo " - " . implode(", ", $identifiers);
                }
                echo "</li>";
            }
            echo "</ul>";
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Utilizatori găsiți!</h3>";
            echo "<p>Poți testa identificarea cu datele de mai sus.</p>";
            echo "</div>";
            
        } else {
            echo "<p style='color: #ffc107;'>⚠️ Nu există utilizatori cu date pentru identificare</p>";
            echo "<p>Trebuie să adaugi primary_phone în wp_usermeta pentru utilizatorii existenți.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: #dc3545;'>❌ Eroare la verificarea utilizatorilor cu date: " . $e->getMessage() . "</p>";
    }

echo "<h3>📋 Următorii pași:</h3>";
echo "<ol>";
echo "<li>Dacă ai găsit utilizatori cu date, testează identificarea: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
echo "<li>Dacă nu ai date suficiente, adaugă CNP sau telefon pentru utilizatorii existenți</li>";
echo "<li>Verifică că robotul AI funcționează corect</li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h3, h4 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    margin: 10px 0;
    padding-left: 20px;
}
table {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style> 