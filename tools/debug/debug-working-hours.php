<?php
/**
 * Script pentru debugarea working_hours în detaliu
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔍 Debugarea working_hours în detaliu...\n\n";

try {
    $settings = Clinica_Settings::get_instance();
    $schedule_settings = $settings->get_group('schedule');
    
    echo "📊 Setările pentru program:\n";
    foreach ($schedule_settings as $key => $value) {
        echo "  - $key: " . gettype($value) . "\n";
    }
    
    if (isset($schedule_settings['working_hours'])) {
        $working_hours = $schedule_settings['working_hours'];
        echo "\n🔍 working_hours detaliat:\n";
        echo "  - Tip: " . gettype($working_hours) . "\n";
        echo "  - Număr elemente: " . count($working_hours) . "\n";
        echo "  - Conținut: " . json_encode($working_hours) . "\n";
        
        if (is_array($working_hours)) {
            echo "\n📋 Cheile din working_hours:\n";
            foreach (array_keys($working_hours) as $key) {
                echo "  - '$key'\n";
            }
        }
    } else {
        echo "\n❌ working_hours nu există în schedule_settings\n";
    }
    
    // Verifică și direct din baza de date
    global $wpdb;
    $table = $wpdb->prefix . 'clinica_settings';
    $db_setting = $wpdb->get_row("SELECT * FROM $table WHERE setting_key = 'working_hours'");
    
    if ($db_setting) {
        echo "\n🗄️ Din baza de date:\n";
        echo "  - Grup: {$db_setting->setting_group}\n";
        echo "  - Tip: {$db_setting->setting_type}\n";
        echo "  - Valoare brută: " . substr($db_setting->setting_value, 0, 200) . "...\n";
        
        if ($db_setting->setting_type === 'json') {
            $decoded = json_decode($db_setting->setting_value, true);
            echo "  - Decodat: " . gettype($decoded) . "\n";
            if (is_array($decoded)) {
                echo "  - Număr elemente: " . count($decoded) . "\n";
                echo "  - Chei: " . implode(', ', array_keys($decoded)) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Eroare fatală: " . $e->getMessage() . "\n";
}

echo "\n🎯 Debug complet!\n";
?> 