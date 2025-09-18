<?php
/**
 * Script pentru testarea query-ului fără JOIN-ul pentru status
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Query fără Status JOIN</h2>";

// Simulează exact condițiile din invalid-emails.php
$search = '';
$email_type_filter = '';
$status_filter = '';

$where_conditions = array();

// Filtru pentru e-mailuri neactualizate - exact ca în invalid-emails.php
$where_conditions[] = "(
    LOWER(u.user_email) LIKE '%temp%' OR 
    LOWER(u.user_email) LIKE '%demo%' OR 
    LOWER(u.user_email) LIKE '%fake%' OR 
    LOWER(u.user_email) LIKE '%.sx' OR
    LOWER(u.user_email) LIKE '%@temp%' OR
    LOWER(u.user_email) LIKE '%@demo%' OR
    LOWER(u.user_email) LIKE '%@fake%' OR
    LOWER(u.user_email) LIKE '%@test%' OR
    LOWER(u.user_email) LIKE '%@example%' OR
    LOWER(u.user_email) LIKE '%@localhost%' OR
    LOWER(u.user_email) LIKE '%temporary%' OR
    LOWER(u.user_email) LIKE '%dummy%' OR
    LOWER(u.user_email) LIKE '%placeholder%'
)";

$where_clause = implode(' AND ', $where_conditions);

// QUERY fără JOIN-ul pentru status
$query = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$where_clause}
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT 1000
";

echo "<p><strong>Where clause:</strong> " . esc_html($where_clause) . "</p>";

$invalid_email_patients = $wpdb->get_results($query);
$total_invalid_email_patients = count($invalid_email_patients);

echo "<p><strong>Total rezultate fără status JOIN:</strong> " . $total_invalid_email_patients . "</p>";

if (!empty($invalid_email_patients)) {
    echo "<h3>Primele 10 rezultate:</h3>";
    echo "<ul>";
    foreach (array_slice($invalid_email_patients, 0, 10) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . " | Nume: " . esc_html($patient->first_name . ' ' . $patient->last_name) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate!</p>";
}

// Testează și cu JOIN-ul pentru status dar fără ORDER BY
echo "<h3>Test cu status JOIN dar fără ORDER BY:</h3>";
$query_with_status_no_order = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name,
           um_status.meta_value as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
    WHERE {$where_clause}
    LIMIT 1000
";

$results_with_status_no_order = $wpdb->get_results($query_with_status_no_order);
echo "<p><strong>Total rezultate cu status JOIN dar fără ORDER BY:</strong> " . count($results_with_status_no_order) . "</p>";

if (!empty($results_with_status_no_order)) {
    echo "<h3>Primele 5 rezultate cu status JOIN dar fără ORDER BY:</h3>";
    echo "<ul>";
    foreach (array_slice($results_with_status_no_order, 0, 5) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . " | Nume: " . esc_html($patient->first_name . ' ' . $patient->last_name) . " | Status: " . esc_html($patient->status) . "</li>";
    }
    echo "</ul>";
}
?> 