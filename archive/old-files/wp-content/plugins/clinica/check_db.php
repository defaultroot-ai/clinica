<?php
// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "=== VERIFICARE TABELE CLINICA ===\n";

// Verifică tabelele existente
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}clinica_%'", ARRAY_N);
$table_names = array_column($tables, 0);
echo "Tabele clinica găsite: " . implode(', ', $table_names) . "\n\n";

// Verifică tabela timeslots
$timeslots_table = $wpdb->prefix . 'clinica_doctor_timeslots';
if (in_array($timeslots_table, $table_names)) {
    echo "=== TABELA {$timeslots_table} ===\n";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$timeslots_table}");
    echo "Total timeslots în baza de date: $count\n";
    
    if ($count > 0) {
        echo "\nUltimele 10 timeslots:\n";
        $timeslots = $wpdb->get_results("SELECT * FROM {$timeslots_table} ORDER BY id DESC LIMIT 10", ARRAY_A);
        foreach ($timeslots as $slot) {
            echo "ID: {$slot['id']}, Doctor: {$slot['doctor_id']}, Service: {$slot['service_id']}, Day: {$slot['day_of_week']}, Start: {$slot['start_time']}, End: {$slot['end_time']}\n";
        }
        
        // Verifică timeslots pentru doctorul 2626 și serviciul 3
        echo "\n=== TIMESLOTS PENTRU DOCTOR 2626, SERVICIU 3 ===\n";
        $specific = $wpdb->get_results("SELECT * FROM {$timeslots_table} WHERE doctor_id = 2626 AND service_id = 3 ORDER BY day_of_week, start_time", ARRAY_A);
        echo "Timeslots găsite: " . count($specific) . "\n";
        foreach ($specific as $slot) {
            echo "Day: {$slot['day_of_week']}, Start: {$slot['start_time']}, End: {$slot['end_time']}\n";
        }
    }
}

// Verifică tabela appointments
$appointments_table = $wpdb->prefix . 'clinica_appointments';
if (in_array($appointments_table, $table_names)) {
    echo "\n=== TABELA {$appointments_table} ===\n";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$appointments_table}");
    echo "Total appointments: $count\n";
}
?>
