<?php
require_once('../../../wp-load.php');

echo "=== DEBUG ACTUALIZARE TELEFOANE ===\n\n";

global $wpdb;

// Testează o singură înregistrare
$test_patient = $wpdb->get_row("
    SELECT 
        p.id,
        p.user_id,
        p.cnp,
        um_joomla_id.meta_value as joomla_id
    FROM {$wpdb->prefix}clinica_patients p
    JOIN {$wpdb->usermeta} um_joomla_id ON p.user_id = um_joomla_id.user_id 
    WHERE um_joomla_id.meta_key = 'joomla_id' 
    AND p.import_source = 'joomla_migration'
    LIMIT 1
");

if ($test_patient) {
    echo "Test pacient găsit:\n";
    echo "ID: {$test_patient->id}\n";
    echo "User ID: {$test_patient->user_id}\n";
    echo "CNP: {$test_patient->cnp}\n";
    echo "Joomla ID: {$test_patient->joomla_id}\n\n";
    
    // Conectare la baza de date Joomla
    $joomla_db_host = 'localhost';
    $joomla_db_name = 'cmmf';
    $joomla_db_user = 'root';
    $joomla_db_pass = '';
    
    $joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);
    
    if ($joomla_db->connect_error) {
        die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
    }
    
    echo "✅ Conectat la baza de date Joomla\n\n";
    
    // Testează query-ul Joomla
    $joomla_query = "SELECT cb_telefon, cb_telefon2 FROM bqzce_comprofiler WHERE user_id = {$test_patient->joomla_id}";
    echo "Query Joomla: $joomla_query\n";
    
    $joomla_result = $joomla_db->query($joomla_query);
    
    if (!$joomla_result) {
        echo "❌ Eroare query Joomla: " . $joomla_db->error . "\n";
    } else {
        echo "✅ Query Joomla executat cu succes\n";
        echo "Număr rânduri: " . $joomla_result->num_rows . "\n";
        
        if ($joomla_result->num_rows > 0) {
            $joomla_data = $joomla_result->fetch_assoc();
            echo "Telefon principal: " . ($joomla_data['cb_telefon'] ?: 'NULL') . "\n";
            echo "Telefon secundar: " . ($joomla_data['cb_telefon2'] ?: 'NULL') . "\n";
        }
    }
    
    $joomla_db->close();
} else {
    echo "❌ Nu s-a găsit niciun pacient pentru test\n";
}

echo "\n=== DEBUG COMPLET ===\n"; 