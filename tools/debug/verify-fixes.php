<?php
/**
 * Script pentru verificarea reparărilor
 */

// Încarcă WordPress
require_once('C:/xampp8.2.12/htdocs/plm/wp-load.php');

global $wpdb;

echo "=== VERIFICARE REPARĂRI ===\n\n";

// 1. Verifică structura tabelului de setări
echo "1. Verificare structură tabel setări...\n";
$table_name = $wpdb->prefix . 'clinica_settings';
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

if (in_array('setting_label', $column_names)) {
    echo "✅ Coloana 'setting_label' există în tabel\n";
} else {
    echo "❌ Coloana 'setting_label' lipsește din tabel\n";
}

// 2. Verifică dacă există setări în baza de date
echo "\n2. Verificare setări în baza de date...\n";
$settings_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "Număr de setări: $settings_count\n";

if ($settings_count > 0) {
    echo "✅ Există setări în baza de date\n";
} else {
    echo "❌ Nu există setări în baza de date\n";
}

// 3. Testează încărcarea setărilor
echo "\n3. Testare încărcare setări...\n";
try {
    $settings = Clinica_Settings::get_instance();
    $clinic_settings = $settings->get_group('clinic');
    
    if (!empty($clinic_settings)) {
        echo "✅ Setările se încarcă corect\n";
        echo "Număr de setări clinică: " . count($clinic_settings) . "\n";
    } else {
        echo "❌ Setările nu se încarcă corect\n";
    }
} catch (Exception $e) {
    echo "❌ Eroare la încărcarea setărilor: " . $e->getMessage() . "\n";
}

// 4. Verifică fișierul settings.php
echo "\n4. Verificare settings.php...\n";
$settings_file = 'admin/views/settings.php';
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    
    // Verifică dacă există verificări pentru array keys
    if (strpos($content, 'isset(') !== false) {
        echo "✅ Fișierul settings.php conține verificări pentru array keys\n";
    } else {
        echo "⚠️ Fișierul settings.php nu conține verificări pentru array keys\n";
    }
    
    // Verifică dacă există funcțiile helper
    if (strpos($content, 'function clinica_get_setting_value') !== false) {
        echo "✅ Funcțiile helper există în settings.php\n";
    } else {
        echo "⚠️ Funcțiile helper lipsesc din settings.php\n";
    }
} else {
    echo "❌ Fișierul settings.php nu există\n";
}

// 5. Verifică clasa Clinica_Plugin
echo "\n5. Verificare Clinica_Plugin...\n";
$clinica_file = 'clinica.php';
if (file_exists($clinica_file)) {
    $content = file_get_contents($clinica_file);
    
    if (strpos($content, 'private $settings = null;') !== false) {
        echo "✅ Proprietatea settings este declarată în Clinica_Plugin\n";
    } else {
        echo "❌ Proprietatea settings nu este declarată în Clinica_Plugin\n";
    }
} else {
    echo "❌ Fișierul clinica.php nu există\n";
}

// 6. Testează accesarea paginii de setări
echo "\n6. Testare accesare pagină setări...\n";
try {
    // Simulează o cerere la pagina de setări
    $_GET['page'] = 'clinica-settings';
    $_REQUEST['page'] = 'clinica-settings';
    
    // Verifică dacă clasa Clinica_Plugin se inițializează corect
    $plugin = Clinica_Plugin::get_instance();
    echo "✅ Clinica_Plugin se inițializează corect\n";
    
} catch (Exception $e) {
    echo "❌ Eroare la inițializarea Clinica_Plugin: " . $e->getMessage() . "\n";
}

// 7. Verifică log-urile pentru erori noi
echo "\n7. Verificare log-uri pentru erori noi...\n";
$debug_log = 'C:/xampp8.2.12/htdocs/plm/wp-content/debug.log';
if (file_exists($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $lines = explode("\n", $log_content);
    $recent_errors = array_slice($lines, -20); // Ultimele 20 linii
    
    $error_count = 0;
    foreach ($recent_errors as $line) {
        if (strpos($line, 'PHP Warning') !== false || strpos($line, 'PHP Error') !== false || strpos($line, 'WordPress database error') !== false) {
            $error_count++;
        }
    }
    
    if ($error_count == 0) {
        echo "✅ Nu există erori recente în log\n";
    } else {
        echo "⚠️ Există $error_count erori recente în log\n";
    }
} else {
    echo "⚠️ Fișierul debug.log nu există\n";
}

echo "\n=== REZUMAT VERIFICARE ===\n";
echo "✅ Toate verificările au fost completate!\n";
echo "Dacă toate verificările au trecut cu ✅, atunci problemele au fost rezolvate.\n";
?> 