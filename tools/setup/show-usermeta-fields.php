<?php
/**
 * Script pentru afișarea câmpurilor din wp_usermeta
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>🔍 Câmpuri în wp_usermeta</h1>";

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

// Afișează toate câmpurile meta_key din wp_usermeta
echo "<h3>📋 Toate câmpurile meta_key din wp_usermeta:</h3>";

try {
    $stmt = $pdo->query("
        SELECT DISTINCT meta_key, COUNT(*) as count
        FROM wp_usermeta 
        GROUP BY meta_key
        ORDER BY meta_key
    ");
    $meta_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($meta_keys) > 0) {
        echo "<p style='color: #28a745;'>📊 Număr total câmpuri unice: " . count($meta_keys) . "</p>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Meta Key</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Număr Înregistrări</th>";
        echo "</tr>";
        
        foreach ($meta_keys as $meta) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>{$meta['meta_key']}</strong></td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există câmpuri în wp_usermeta</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea câmpurilor: " . $e->getMessage() . "</p>";
}

// Afișează toate înregistrările din wp_usermeta
echo "<h3>📋 Toate înregistrările din wp_usermeta:</h3>";

try {
    $stmt = $pdo->query("
        SELECT user_id, meta_key, meta_value
        FROM wp_usermeta 
        ORDER BY user_id, meta_key
    ");
    $all_meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($all_meta) > 0) {
        echo "<p style='color: #28a745;'>📊 Număr total înregistrări: " . count($all_meta) . "</p>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>User ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Meta Key</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Meta Value</th>";
        echo "</tr>";
        
        foreach ($all_meta as $meta) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['user_id']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>{$meta['meta_key']}</strong></td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$meta['meta_value']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: #ffc107;'>⚠️ Nu există înregistrări în wp_usermeta</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea înregistrărilor: " . $e->getMessage() . "</p>";
}

echo "<h3>📋 Următorii pași:</h3>";
echo "<ol>";
echo "<li>Verifică ce câmpuri ai în wp_usermeta</li>";
echo "<li>Spune-mi ce câmpuri vrei să folosesc pentru identificare</li>";
echo "<li>Voi actualiza API-ul pentru a folosi câmpurile tale reale</li>";
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
table {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style> 