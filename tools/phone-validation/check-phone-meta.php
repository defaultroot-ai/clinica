<?php
/**
 * Script pentru verificarea meta datelor utilizatorilor pentru numere de telefon
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>📞 Verificare Meta Date Telefon</h1>";

// 1. Găsește toți utilizatorii cu rolul "Pacient"
$patients_query = "
    SELECT u.ID, u.user_login, u.user_email, u.display_name
    FROM {$wpdb->users} u
    WHERE u.ID IN (
        SELECT user_id 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = '{$wpdb->prefix}capabilities' 
        AND meta_value LIKE '%clinica_patient%'
    )
    ORDER BY u.user_registered DESC
    LIMIT 5
";

$patients = $wpdb->get_results($patients_query);

echo "<h2>📊 Verificare pentru primii " . count($patients) . " pacienți</h2>";

foreach ($patients as $patient) {
    echo "<hr>";
    echo "<h3>👤 Pacient: {$patient->display_name} (ID: {$patient->ID})</h3>";
    echo "<p><strong>Username:</strong> {$patient->user_login}</p>";
    echo "<p><strong>Email:</strong> {$patient->user_email}</p>";
    
    // Găsește toate meta datele pentru acest utilizator
    $meta_query = $wpdb->prepare("
        SELECT meta_key, meta_value 
        FROM {$wpdb->usermeta} 
        WHERE user_id = %d 
        AND meta_key LIKE '%phone%'
        ORDER BY meta_key
    ", $patient->ID);
    
    $phone_meta = $wpdb->get_results($meta_query);
    
    if (!empty($phone_meta)) {
        echo "<h4>📞 Meta date telefon găsite:</h4>";
        echo "<ul>";
        foreach ($phone_meta as $meta) {
            echo "<li><strong>{$meta->meta_key}:</strong> {$meta->meta_value}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Nu s-au găsit meta date pentru telefon</p>";
    }
    
    // Verifică și alte meta date care ar putea conține telefon
    $all_meta_query = $wpdb->prepare("
        SELECT meta_key, meta_value 
        FROM {$wpdb->usermeta} 
        WHERE user_id = %d 
        ORDER BY meta_key
    ", $patient->ID);
    
    $all_meta = $wpdb->get_results($all_meta_query);
    
    echo "<h4>📋 Toate meta datele:</h4>";
    echo "<ul>";
    foreach ($all_meta as $meta) {
        // Skip capabilities and session tokens
        if (in_array($meta->meta_key, ['wp_capabilities', 'session_tokens'])) {
            continue;
        }
        echo "<li><strong>{$meta->meta_key}:</strong> {$meta->meta_value}</li>";
    }
    echo "</ul>";
}

// 2. Verifică dacă există meta keys specifice pentru telefon
echo "<h2>🔍 Căutare meta keys pentru telefon în toată baza de date</h2>";

$phone_keys_query = "
    SELECT DISTINCT meta_key 
    FROM {$wpdb->usermeta} 
    WHERE meta_key LIKE '%phone%' 
    OR meta_key LIKE '%tel%'
    OR meta_key LIKE '%mobile%'
    ORDER BY meta_key
";

$phone_keys = $wpdb->get_results($phone_keys_query);

if (!empty($phone_keys)) {
    echo "<h3>📞 Meta keys găsite pentru telefon:</h3>";
    echo "<ul>";
    foreach ($phone_keys as $key) {
        echo "<li>{$key->meta_key}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Nu s-au găsit meta keys pentru telefon</p>";
}

// 3. Verifică dacă numărul de telefon este în tabela clinica_patients
echo "<h2>🏥 Verificare tabela clinica_patients</h2>";

$clinica_table = $wpdb->prefix . 'clinica_patients';
$clinica_patients = $wpdb->get_results("
    SELECT user_id, phone_primary, phone_secondary 
    FROM $clinica_table 
    LIMIT 5
");

if (!empty($clinica_patients)) {
    echo "<h3>📊 Pacienți din tabela clinica:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>User ID</th><th>Telefon Principal</th><th>Telefon Secundar</th>";
    echo "</tr>";
    
    foreach ($clinica_patients as $patient) {
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>" . ($patient->phone_primary ?: 'Gol') . "</td>";
        echo "<td>" . ($patient->phone_secondary ?: 'Gol') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Tabela clinica_patients este goală!</p>";
}

echo "<hr>";
echo "<p><em>Verificare rulată la: " . current_time('mysql') . "</em></p>";
?> 