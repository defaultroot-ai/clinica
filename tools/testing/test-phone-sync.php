<?php
/**
 * Test final pentru verificarea sincronizării numerelor de telefon
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>📞 Test Final Sincronizare Telefoane</h1>";

// 1. Verifică pacienții din tabela clinica cu numere de telefon
$clinica_table = $wpdb->prefix . 'clinica_patients';
$patients_with_phone = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email,
           um1.meta_value as first_name,
           um2.meta_value as last_name
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE p.phone_primary IS NOT NULL AND p.phone_primary != ''
    ORDER BY p.created_at DESC
    LIMIT 5
");

echo "<h2>📊 Pacienți cu numere de telefon: " . count($patients_with_phone) . "</h2>";

if (!empty($patients_with_phone)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>CNP</th><th>Email</th><th>Telefon Principal</th><th>Telefon Secundar</th>";
    echo "</tr>";
    
    foreach ($patients_with_phone as $patient) {
        $full_name = $patient->first_name && $patient->last_name 
            ? $patient->first_name . ' ' . $patient->last_name 
            : $patient->display_name;
            
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>{$full_name}</td>";
        echo "<td>{$patient->cnp}</td>";
        echo "<td>{$patient->user_email}</td>";
        echo "<td>{$patient->phone_primary}</td>";
        echo "<td>" . ($patient->phone_secondary ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Nu s-au găsit pacienți cu numere de telefon!</p>";
}

// 2. Verifică pacienții fără numere de telefon
$patients_without_phone = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (p.phone_primary IS NULL OR p.phone_primary = '')
    AND (p.phone_secondary IS NULL OR p.phone_secondary = '')
    ORDER BY p.created_at DESC
    LIMIT 5
");

echo "<h2>📊 Pacienți fără numere de telefon: " . count($patients_without_phone) . "</h2>";

if (!empty($patients_without_phone)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>CNP</th><th>Email</th>";
    echo "</tr>";
    
    foreach ($patients_without_phone as $patient) {
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>{$patient->display_name}</td>";
        echo "<td>{$patient->cnp}</td>";
        echo "<td>{$patient->user_email}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ Toți pacienții au numere de telefon!</p>";
}

// 3. Testează metoda get_recent_patients_html() pentru a vedea dacă afișează telefoanele
echo "<h2>🧪 Test metoda get_recent_patients_html()</h2>";

if (class_exists('Clinica')) {
    $clinica = new Clinica();
    $html = $clinica->get_recent_patients_html();
    
    echo "<h3>HTML generat:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9; max-height: 300px; overflow-y: auto;'>";
    echo $html;
    echo "</div>";
} else {
    echo "<p>❌ Clasa Clinica nu există!</p>";
}

// 4. Verifică query-ul direct pentru admin
echo "<h2>🔍 Test query direct pentru admin</h2>";

$admin_query = "
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

$admin_results = $wpdb->get_results($admin_query);

echo "<p><strong>Query admin:</strong> " . count($admin_results) . " rezultate</p>";

if (!empty($admin_results)) {
    echo "<ul>";
    foreach ($admin_results as $result) {
        $name = $result->first_name && $result->last_name 
            ? $result->first_name . ' ' . $result->last_name 
            : $result->display_name;
        $phone = $result->phone_primary ?: 'N/A';
        echo "<li>{$name} ({$result->cnp}) - {$result->user_email} - Tel: {$phone}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Query-ul nu returnează rezultate!</p>";
}

// 5. Statistici finale
$total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");
$patients_with_phone_count = $wpdb->get_var("
    SELECT COUNT(*) FROM $clinica_table 
    WHERE phone_primary IS NOT NULL AND phone_primary != ''
");
$patients_without_phone_count = $total_patients - $patients_with_phone_count;

echo "<h2>📊 Statistici Finale</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Total pacienți:</strong> {$total_patients}</p>";
echo "<p><strong>Cu numere de telefon:</strong> {$patients_with_phone_count}</p>";
echo "<p><strong>Fără numere de telefon:</strong> {$patients_without_phone_count}</p>";
echo "<p><strong>Procent cu telefon:</strong> " . round(($patients_with_phone_count / $total_patients) * 100, 1) . "%</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test final rulat la: " . current_time('mysql') . "</em></p>";
?> 