<?php
// Verificare simplă pacienți
require_once('../../../wp-load.php');

global $wpdb;

echo "=== VERIFICARE SIMPLĂ PACIENTI ===\n\n";

// 1. Verifică tabelele
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}clinica_%'");
echo "Tabele clinica:\n";
foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    echo "- $table_name\n";
}

// 2. Verifică pacienții
$patients_table = $wpdb->prefix . 'clinica_patients';
$total = $wpdb->get_var("SELECT COUNT(*) FROM $patients_table");
echo "\nTotal pacienți: $total\n";

if ($total > 0) {
    $patients = $wpdb->get_results("SELECT * FROM $patients_table LIMIT 3");
    echo "\nPrimii 3 pacienți:\n";
    foreach ($patients as $p) {
        echo "- ID: {$p->id}, User ID: {$p->user_id}, CNP: {$p->cnp}\n";
    }
    
    // Testează query-ul fix
    $fixed_query = "SELECT p.*, u.user_email, u.display_name,
                   um1.meta_value as first_name, um2.meta_value as last_name
                   FROM $patients_table p 
                   LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                   LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                   LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                   ORDER BY p.created_at DESC
                   LIMIT 3";
    
    $fixed_results = $wpdb->get_results($fixed_query);
    echo "\nQuery fix returnează: " . count($fixed_results) . " rezultate\n";
    
    if ($fixed_results) {
        foreach ($fixed_results as $p) {
            $name = trim($p->first_name . ' ' . $p->last_name);
            if (empty($name)) $name = $p->display_name;
            echo "- {$name} (CNP: {$p->cnp}, Email: {$p->user_email})\n";
        }
    }
} else {
    echo "\nNu există pacienți în tabel!\n";
}

echo "\n=== FINALIZAT ===\n";
?> 