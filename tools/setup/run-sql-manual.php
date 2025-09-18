<?php
/**
 * Script pentru rularea manuală a SQL-ului pentru Robotul AI
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm'; // sau numele bazei tale de date
$db_user = 'root';
$db_pass = '';

// Conectare la baza de date
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "<h1>🤖 Instalare Tabele Robot AI</h1>";
    echo "<p>Se conectează la baza de date...</p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Eroare la conectare!</h3>";
    echo "<p>Eroare: " . $e->getMessage() . "</p>";
    echo "<p>Verifică configurările bazei de date:</p>";
    echo "<ul>";
    echo "<li>Host: $db_host</li>";
    echo "<li>Database: $db_name</li>";
    echo "<li>User: $db_user</li>";
    echo "</ul>";
    echo "</div>";
    exit;
}

// Citește fișierul SQL
$sql_file = __DIR__ . '/create-ai-tables.sql';

if (!file_exists($sql_file)) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Fișier SQL nu găsit!</h3>";
    echo "<p>Fișierul: $sql_file nu există.</p>";
    echo "</div>";
    exit;
}

$sql = file_get_contents($sql_file);

// Împarte SQL-ul în comenzi separate
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

echo "<h3>📊 Crearea tabelelor în baza de date...</h3>";

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    try {
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute();
        $success_count++;
        echo "<p style='color: #28a745;'>✅ Query executat cu succes</p>";
    } catch (PDOException $e) {
        $error_count++;
        echo "<p style='color: #dc3545;'>❌ Eroare: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p>✅ $success_count comenzi executate cu succes</p>";
if ($error_count > 0) {
    echo "<p style='color: #dc3545;'>❌ $error_count erori</p>";
}

// Verifică tabelele create
echo "<h3>📋 Verificare tabele create:</h3>";

$tables = [
    'wp_clinica_ai_identifications',
    'wp_clinica_webrtc_calls',
    'wp_clinica_webrtc_conversations',
    'wp_clinica_ai_conversations',
    'wp_clinica_ai_routing',
    'wp_clinica_ai_appointments',
    'wp_clinica_ai_statistics',
    'wp_clinica_ai_config',
    'wp_clinica_ai_logs'
];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $result = $stmt->fetchAll();
        $exists = count($result) > 0;
        
        if ($exists) {
            echo "<p style='color: #28a745;'>✅ Tabel $table: EXISTĂ</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ Tabel $table: NU EXISTĂ</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: #dc3545;'>❌ Eroare la verificarea tabelului $table: " . $e->getMessage() . "</p>";
    }
}

if ($success_count > 0 && $error_count === 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Instalare completă cu succes!</h3>";
    echo "<p>Toate tabelele pentru robotul AI au fost create cu succes.</p>";
    echo "</div>";
    
    echo "<h3>📋 Următorii pași:</h3>";
    echo "<ol>";
    echo "<li>Testați aparatura audio: <a href='/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html' target='_blank'>Test Aparatură</a></li>";
    echo "<li>Accesați robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
    echo "<li>Configurați setările în dashboard</li>";
    echo "</ol>";
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Instalare parțială</h3>";
    echo "<p>Unele tabele au fost create, dar au apărut erori. Verifică log-urile pentru detalii.</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
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
</style> 