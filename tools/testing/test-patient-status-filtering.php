<?php
/**
 * Test pentru filtrarea pacienților după status
 * Verifică dacă pacienții inactivi dispar din lista principală
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a accesa această pagină.');
}

global $wpdb;

echo "<h1>Test Filtrare Pacienți după Status</h1>";

// 1. Verifică pacienții din lista principală (ar trebui să fie doar activi)
echo "<h2>1. Pacienți din lista principală (ar trebui să fie doar activi)</h2>";

$table_name = $wpdb->prefix . 'clinica_patients';
$main_query = "SELECT p.user_id, p.cnp, 
               um1.meta_value as first_name, um2.meta_value as last_name,
               um_status.meta_value as patient_status
               FROM $table_name p 
               LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
               LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
               LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
               LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
               WHERE (um_status.meta_value IS NULL OR um_status.meta_value = 'active')
               ORDER BY p.created_at DESC 
               LIMIT 10";

$main_patients = $wpdb->get_results($main_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>CNP</th><th>Nume</th><th>Status</th></tr>";

foreach ($main_patients as $patient) {
    $status = $patient->patient_status ?: 'active (NULL)';
    echo "<tr>";
    echo "<td>{$patient->user_id}</td>";
    echo "<td>{$patient->cnp}</td>";
    echo "<td>{$patient->first_name} {$patient->last_name}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Verifică pacienții inactivi
echo "<h2>2. Pacienți inactivi (ar trebui să fie doar inactivi/blocați)</h2>";

$inactive_query = "SELECT p.user_id, p.cnp, 
                  um1.meta_value as first_name, um2.meta_value as last_name,
                  um_status.meta_value as patient_status
                  FROM $table_name p 
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                  LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                  LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
                  WHERE um_status.meta_value IN ('inactive', 'blocked')
                  ORDER BY p.created_at DESC 
                  LIMIT 10";

$inactive_patients = $wpdb->get_results($inactive_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>CNP</th><th>Nume</th><th>Status</th></tr>";

foreach ($inactive_patients as $patient) {
    echo "<tr>";
    echo "<td>{$patient->user_id}</td>";
    echo "<td>{$patient->cnp}</td>";
    echo "<td>{$patient->first_name} {$patient->last_name}</td>";
    echo "<td>{$patient->patient_status}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Statistici generale
echo "<h2>3. Statistici generale</h2>";

$stats_query = "SELECT 
                COUNT(*) as total_patients,
                SUM(CASE WHEN um_status.meta_value IS NULL OR um_status.meta_value = 'active' THEN 1 ELSE 0 END) as active_patients,
                SUM(CASE WHEN um_status.meta_value = 'inactive' THEN 1 ELSE 0 END) as inactive_patients,
                SUM(CASE WHEN um_status.meta_value = 'blocked' THEN 1 ELSE 0 END) as blocked_patients
                FROM $table_name p 
                LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'";

$stats = $wpdb->get_row($stats_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Total Pacienți</th><th>Activi</th><th>Inactivi</th><th>Blocați</th></tr>";
echo "<tr>";
echo "<td>{$stats->total_patients}</td>";
echo "<td>{$stats->active_patients}</td>";
echo "<td>{$stats->inactive_patients}</td>";
echo "<td>{$stats->blocked_patients}</td>";
echo "</tr>";
echo "</table>";

// 4. Test pentru a seta un pacient ca inactiv
echo "<h2>4. Test - Setare pacient ca inactiv</h2>";

if (isset($_GET['test_inactive']) && isset($_GET['patient_id'])) {
    $test_patient_id = intval($_GET['patient_id']);
    
    // Setează pacientul ca inactiv
    update_user_meta($test_patient_id, 'clinica_patient_status', 'inactive');
    
    echo "<p style='color: green;'>Pacientul cu ID {$test_patient_id} a fost setat ca inactiv.</p>";
    echo "<p><a href='?'>Reîncarcă pagina pentru a vedea modificările</a></p>";
} else {
    echo "<p>Pentru a testa, adaugă ?test_inactive=1&patient_id=ID_PACIENT la URL.</p>";
}

echo "<hr>";
echo "<p><a href='../'>← Înapoi la tools</a></p>";
?> 