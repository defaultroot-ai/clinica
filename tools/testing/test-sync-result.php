<?php
/**
 * Test script pentru verificarea rezultatului sincronizării
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>🧪 Test Rezultat Sincronizare</h1>";

// 1. Verifică tabela wp_clinica_patients
$clinica_table = $wpdb->prefix . 'clinica_patients';
$patient_count = $wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");

echo "<h2>📊 Pacienți în tabela clinica: {$patient_count}</h2>";

if ($patient_count > 0) {
    // Afișează primii 5 pacienți
    $patients = $wpdb->get_results("
        SELECT p.*, u.display_name, u.user_email, 
               um1.meta_value as first_name,
               um2.meta_value as last_name
        FROM $clinica_table p
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    
    echo "<h3>👥 Ultimii pacienți sincronizați:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>CNP</th><th>Email</th><th>Data nașterii</th><th>Sex</th><th>Vârsta</th>";
    echo "</tr>";
    
    foreach ($patients as $patient) {
        $full_name = $patient->first_name && $patient->last_name 
            ? $patient->first_name . ' ' . $patient->last_name 
            : $patient->display_name;
            
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>{$full_name}</td>";
        echo "<td>{$patient->cnp}</td>";
        echo "<td>{$patient->user_email}</td>";
        echo "<td>" . ($patient->birth_date ?: 'N/A') . "</td>";
        echo "<td>" . ($patient->gender ?: 'N/A') . "</td>";
        echo "<td>" . ($patient->age ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Tabela clinica_patients este încă goală!</p>";
}

// 2. Testează metoda get_recent_patients_html()
echo "<h2>🧪 Test metoda get_recent_patients_html()</h2>";

if (class_exists('Clinica')) {
    $clinica = new Clinica();
    $html = $clinica->get_recent_patients_html();
    
    echo "<h3>HTML generat:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    echo $html;
    echo "</div>";
} else {
    echo "<p>❌ Clasa Clinica nu există!</p>";
}

// 3. Verifică query-ul direct
echo "<h2>🔍 Test query direct</h2>";

$test_query = "
    SELECT p.*, u.display_name, u.user_email,
           um1.meta_value as first_name,
           um2.meta_value as last_name
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    ORDER BY p.created_at DESC
    LIMIT 3
";

$test_results = $wpdb->get_results($test_query);

echo "<p><strong>Query test:</strong> " . count($test_results) . " rezultate</p>";

if (!empty($test_results)) {
    echo "<ul>";
    foreach ($test_results as $result) {
        $name = $result->first_name && $result->last_name 
            ? $result->first_name . ' ' . $result->last_name 
            : $result->display_name;
        echo "<li>{$name} ({$result->cnp}) - {$result->user_email}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Query-ul nu returnează rezultate!</p>";
}

echo "<hr>";
echo "<p><em>Test rulat la: " . current_time('mysql') . "</em></p>";
?> 