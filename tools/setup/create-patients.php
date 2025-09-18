<?php
/**
 * Script pentru crearea tabelului de pacienți și date de test
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>👥 Creare Tabel Pacienți</h1>";

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

// Citește fișierul SQL
$sql_file = __DIR__ . '/create-patients-table.sql';

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

echo "<h3>📊 Crearea tabelului de pacienți...</h3>";

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
    'wp_clinica_patients' => 'Tabel Pacienți'
];

foreach ($tables as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $result = $stmt->fetchAll();
        $exists = count($result) > 0;
        
        if ($exists) {
            echo "<p style='color: #28a745;'>✅ $description ($table): EXISTĂ</p>";
            
            // Verifică numărul de pacienți
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patient_count = $result[0]['count'] ?? 0;
            
            echo "<p style='color: #28a745;'>📊 Număr pacienți în baza de date: $patient_count</p>";
            
            // Afișează primii 3 pacienți pentru verificare
            if ($patient_count > 0) {
                $stmt = $pdo->query("SELECT first_name, last_name, cnp, phone FROM $table LIMIT 3");
                $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h4>👥 Pacienți disponibili pentru testare:</h4>";
                echo "<ul>";
                foreach ($patients as $patient) {
                    echo "<li><strong>{$patient['first_name']} {$patient['last_name']}</strong> - CNP: {$patient['cnp']} - Tel: {$patient['phone']}</li>";
                }
                echo "</ul>";
            }
            
        } else {
            echo "<p style='color: #dc3545;'>❌ $description ($table): NU EXISTĂ</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: #dc3545;'>❌ Eroare la verificarea tabelului $table: " . $e->getMessage() . "</p>";
    }
}

if ($success_count > 0 && $error_count === 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Tabelul de pacienți creat cu succes!</h3>";
    echo "<p>Tabelul și datele de test au fost create cu succes.</p>";
    echo "</div>";
    
    echo "<h3>📋 Următorii pași:</h3>";
    echo "<ol>";
    echo "<li>Testați identificarea pacienților: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
    echo "<li>Încercați să identificați un pacient folosind CNP-ul sau numărul de telefon</li>";
    echo "<li>Verificați că robotul AI funcționează corect</li>";
    echo "</ol>";
    
    echo "<h3>🧪 Date de test disponibile:</h3>";
    echo "<p><strong>CNP-uri de test:</strong></p>";
    echo "<ul>";
    echo "<li>1234567890123 (Ion Popescu)</li>";
    echo "<li>2345678901234 (Maria Ionescu)</li>";
    echo "<li>3456789012345 (Gheorghe Dumitrescu)</li>";
    echo "</ul>";
    
    echo "<p><strong>Numere de telefon de test:</strong></p>";
    echo "<ul>";
    echo "<li>0722123456 (Ion Popescu)</li>";
    echo "<li>0733123456 (Maria Ionescu)</li>";
    echo "<li>0744123456 (Gheorghe Dumitrescu)</li>";
    echo "</ul>";
    
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Creare parțială</h3>";
    echo "<p>Unele operațiuni au fost executate, dar au apărut erori. Verifică log-urile pentru detalii.</p>";
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
</style> 