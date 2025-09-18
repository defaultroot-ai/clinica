<?php
/**
 * Script de verificare rapidă pentru tabelele Robot AI
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>🔍 Verificare Tabele Robot AI</h1>";

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

// Lista tabelelor AI
$tables = [
    'wp_clinica_ai_identifications' => 'Log identificări pacienți',
    'wp_clinica_webrtc_calls' => 'Apeluri WebRTC',
    'wp_clinica_webrtc_conversations' => 'Conversații în timpul apelurilor',
    'wp_clinica_ai_conversations' => 'Conversații AI avansate',
    'wp_clinica_ai_routing' => 'Decizii de routing',
    'wp_clinica_ai_appointments' => 'Programări sugerate de AI',
    'wp_clinica_ai_statistics' => 'Statistici robot AI',
    'wp_clinica_ai_config' => 'Configurări AI',
    'wp_clinica_ai_logs' => 'Log-uri AI'
];

echo "<h3>📊 Status Tabele:</h3>";

$existing_tables = 0;
$missing_tables = 0;

foreach ($tables as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $result = $stmt->fetchAll();
        $exists = count($result) > 0;
        
        if ($exists) {
            echo "<p style='color: #28a745;'>✅ <strong>$table</strong> - $description</p>";
            $existing_tables++;
        } else {
            echo "<p style='color: #dc3545;'>❌ <strong>$table</strong> - $description</p>";
            $missing_tables++;
        }
    } catch (PDOException $e) {
        echo "<p style='color: #dc3545;'>❌ <strong>$table</strong> - Eroare: " . $e->getMessage() . "</p>";
        $missing_tables++;
    }
}

echo "<hr>";
echo "<h3>📈 Rezumat:</h3>";
echo "<p><strong>Tabele existente:</strong> $existing_tables / " . count($tables) . "</p>";
echo "<p><strong>Tabele lipsă:</strong> $missing_tables / " . count($tables) . "</p>";

if ($existing_tables === count($tables)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Toate tabelele sunt create cu succes!</h3>";
    echo "<p>Robotul AI este gata de utilizare.</p>";
    echo "</div>";
    
    echo "<h3>📋 Următorii pași:</h3>";
    echo "<ol>";
    echo "<li>Testați aparatura audio: <a href='/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html' target='_blank'>Test Aparatură</a></li>";
    echo "<li>Accesați robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
    echo "<li>Configurați setările în dashboard</li>";
    echo "</ol>";
    
} elseif ($existing_tables > 0) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Instalare parțială</h3>";
    echo "<p>Unele tabele au fost create, dar lipsesc $missing_tables tabele.</p>";
    echo "<p>Rulați din nou scriptul de instalare SQL.</p>";
    echo "</div>";
    
    echo "<p><a href='/plm/wp-content/plugins/clinica/tools/setup/run-sql-manual.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Rulare SQL din nou</a></p>";
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Instalare eșuată</h3>";
    echo "<p>Niciun tabel nu a fost creat. Verifică configurările bazei de date.</p>";
    echo "</div>";
    
    echo "<p><a href='/plm/wp-content/plugins/clinica/tools/setup/run-sql-manual.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚨 Rulare SQL din nou</a></p>";
}

// Verifică și fișierele
echo "<hr>";
echo "<h3>📁 Verificare Fișiere:</h3>";

$files = [
    'public/phone-call.html' => 'Robotul AI',
    'api/identify-patient.php' => 'API Identificare Pacienți',
    'api/webrtc-offer.php' => 'API WebRTC',
    'tools/testing/test-audio-setup.html' => 'Test Aparatură'
];

$existing_files = 0;
$missing_files = 0;

foreach ($files as $file => $description) {
    $full_path = __DIR__ . '/../../' . $file;
    if (file_exists($full_path)) {
        echo "<p style='color: #28a745;'>✅ <strong>$file</strong> - $description</p>";
        $existing_files++;
    } else {
        echo "<p style='color: #dc3545;'>❌ <strong>$file</strong> - $description</p>";
        $missing_files++;
    }
}

echo "<p><strong>Fișiere existente:</strong> $existing_files / " . count($files) . "</p>";
echo "<p><strong>Fișiere lipsă:</strong> $missing_files / " . count($files) . "</p>";

if ($missing_files > 0) {
    echo "<p><a href='/plm/wp-content/plugins/clinica/tools/setup/install-ai-robot-simple.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📁 Instalează Fișierele</a></p>";
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