<?php
/**
 * Script pentru verificarea setărilor din baza de date
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔍 Verificarea setărilor din baza de date...\n\n";

global $wpdb;

// Verifică structura tabelului
$table = $wpdb->prefix . 'clinica_settings';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;

if ($table_exists) {
    echo "✅ Tabelul $table există\n";
    
    // Verifică coloanele
    $columns = $wpdb->get_results("DESCRIBE $table");
    echo "📋 Coloanele tabelului:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    // Verifică setările existente
    $settings = $wpdb->get_results("SELECT * FROM $table ORDER BY setting_group, setting_key");
    
    if ($settings) {
        echo "\n📊 Setările existente:\n";
        $groups = array();
        foreach ($settings as $setting) {
            $groups[$setting->setting_group][] = $setting;
        }
        
        foreach ($groups as $group => $group_settings) {
            echo "\n📁 Grupul: $group\n";
            foreach ($group_settings as $setting) {
                echo "  - {$setting->setting_key}: {$setting->setting_type}";
                if ($setting->setting_type === 'json') {
                    $decoded = json_decode($setting->setting_value, true);
                    if (is_array($decoded)) {
                        echo " (array cu " . count($decoded) . " elemente)";
                    } else {
                        echo " (JSON invalid)";
                    }
                }
                echo "\n";
            }
        }
        
        // Verifică specific working_hours
        $working_hours = $wpdb->get_row("SELECT * FROM $table WHERE setting_key = 'working_hours'");
        if ($working_hours) {
            echo "\n🔍 Setarea working_hours:\n";
            echo "  - Tip: {$working_hours->setting_type}\n";
            echo "  - Grup: {$working_hours->setting_group}\n";
            echo "  - Valoare: " . substr($working_hours->setting_value, 0, 100) . "...\n";
            
            if ($working_hours->setting_type === 'json') {
                $decoded = json_decode($working_hours->setting_value, true);
                if (is_array($decoded)) {
                    echo "  - Decodat: array cu " . count($decoded) . " elemente\n";
                    foreach ($decoded as $day => $data) {
                        echo "    * $day: " . json_encode($data) . "\n";
                    }
                } else {
                    echo "  - Decodat: JSON invalid\n";
                }
            }
        } else {
            echo "\n❌ Setarea working_hours nu există în baza de date!\n";
        }
        
    } else {
        echo "❌ Nu există setări în baza de date\n";
    }
    
} else {
    echo "❌ Tabelul $table nu există!\n";
}

echo "\n🎯 Verificarea completă!\n";
?> 