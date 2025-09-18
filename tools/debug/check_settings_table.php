<?php
require_once('../../../wp-config.php');

global $wpdb;
$table = $wpdb->prefix . 'clinica_settings';

echo "=== VERIFICARE TABEL SETĂRI ===\n";
echo "Căutând tabelul: $table\n\n";

// Verifică dacă tabelul există
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($exists) {
    echo "✅ Tabelul $table EXISTĂ\n\n";
    
    // Verifică numărul de setări
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "📊 Numărul de setări: $count\n\n";
    
    // Afișează primele 5 setări
    echo "📋 Primele 5 setări:\n";
    $settings = $wpdb->get_results("SELECT setting_key, setting_value, setting_type FROM $table LIMIT 5");
    foreach ($settings as $setting) {
        echo "- {$setting->setting_key}: {$setting->setting_value} (tip: {$setting->setting_type})\n";
    }
    
    // Verifică structura tabelului
    echo "\n🔍 Structura tabelului:\n";
    $columns = $wpdb->get_results("DESCRIBE $table");
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} {$column->Null} {$column->Key}\n";
    }
    
} else {
    echo "❌ Tabelul $table NU EXISTĂ\n\n";
    
    // Verifică dacă există alte tabele clinica
    echo "🔍 Căutând alte tabele clinica:\n";
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}clinica%'");
    foreach ($tables as $table_obj) {
        $table_name = array_values((array)$table_obj)[0];
        echo "- $table_name\n";
    }
}

echo "\n=== FINALIZAT ===\n";
?>



