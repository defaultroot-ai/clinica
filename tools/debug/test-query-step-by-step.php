<?php
/**
 * Script pentru testarea query-ului pas cu pas
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Query Pas cu Pas</h2>";

// Test 1: Query simplu fără JOIN-uri
echo "<h3>Test 1: Query simplu</h3>";
$simple_query = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$simple_results = $wpdb->get_results($simple_query);
echo "<p><strong>Rezultate query simplu:</strong> " . count($simple_results) . "</p>";
if (!empty($simple_results)) {
    foreach ($simple_results as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "<br>";
    }
}

// Test 2: Adaugă un JOIN pentru first_name
echo "<h3>Test 2: Cu JOIN pentru first_name</h3>";
$query_with_first_name = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$results_with_first_name = $wpdb->get_results($query_with_first_name);
echo "<p><strong>Rezultate cu first_name JOIN:</strong> " . count($results_with_first_name) . "</p>";
if (!empty($results_with_first_name)) {
    foreach ($results_with_first_name as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | First Name: " . esc_html($result->first_name) . "<br>";
    }
}

// Test 3: Adaugă și last_name JOIN
echo "<h3>Test 3: Cu JOIN pentru first_name și last_name</h3>";
$query_with_names = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$results_with_names = $wpdb->get_results($query_with_names);
echo "<p><strong>Rezultate cu first_name și last_name JOIN:</strong> " . count($results_with_names) . "</p>";
if (!empty($results_with_names)) {
    foreach ($results_with_names as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}

// Test 4: Adaugă și status JOIN
echo "<h3>Test 4: Cu toate JOIN-urile</h3>";
$query_with_all_joins = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name, um_status.meta_value as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$results_with_all_joins = $wpdb->get_results($query_with_all_joins);
echo "<p><strong>Rezultate cu toate JOIN-urile:</strong> " . count($results_with_all_joins) . "</p>";
if (!empty($results_with_all_joins)) {
    foreach ($results_with_all_joins as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . " | Status: " . esc_html($result->status) . "<br>";
    }
}

// Test 5: Adaugă ORDER BY
echo "<h3>Test 5: Cu ORDER BY</h3>";
$query_with_order = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name, um_status.meta_value as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT 5
";
$results_with_order = $wpdb->get_results($query_with_order);
echo "<p><strong>Rezultate cu ORDER BY:</strong> " . count($results_with_order) . "</p>";
if (!empty($results_with_order)) {
    foreach ($results_with_order as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . " | Status: " . esc_html($result->status) . "<br>";
    }
}

// Test 6: Verifică dacă problema este cu meta_key 'clinica_patient_status'
echo "<h3>Test 6: Verifică meta_key 'clinica_patient_status'</h3>";
$status_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'clinica_patient_status'");
echo "<p><strong>Înregistrări cu meta_key 'clinica_patient_status':</strong> " . $status_count . "</p>";

// Test 7: Query fără status JOIN
echo "<h3>Test 7: Fără status JOIN</h3>";
$query_without_status = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT 5
";
$results_without_status = $wpdb->get_results($query_without_status);
echo "<p><strong>Rezultate fără status JOIN:</strong> " . count($results_without_status) . "</p>";
if (!empty($results_without_status)) {
    foreach ($results_without_status as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}
?> 