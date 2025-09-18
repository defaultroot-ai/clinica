<?php
/**
 * Script pentru testarea unui query minimal fără JOIN-uri pentru usermeta
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Query Minimal</h2>";

// Query minimal fără JOIN-uri pentru usermeta
$minimal_query = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (
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
    )
    LIMIT 10
";

$minimal_results = $wpdb->get_results($minimal_query);
echo "<p><strong>Rezultate query minimal:</strong> " . count($minimal_results) . "</p>";

if (!empty($minimal_results)) {
    echo "<h3>Primele 10 rezultate cu query minimal:</h3>";
    echo "<ul>";
    foreach ($minimal_results as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate cu query minimal!</p>";
}

// Testează și cu un singur JOIN pentru first_name
echo "<h3>Test cu un singur JOIN pentru first_name:</h3>";
$query_with_first_name = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    WHERE (
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
    )
    LIMIT 10
";

$results_with_first_name = $wpdb->get_results($query_with_first_name);
echo "<p><strong>Rezultate cu first_name JOIN:</strong> " . count($results_with_first_name) . "</p>";

if (!empty($results_with_first_name)) {
    echo "<h3>Primele 5 rezultate cu first_name JOIN:</h3>";
    echo "<ul>";
    foreach (array_slice($results_with_first_name, 0, 5) as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | First Name: " . esc_html($result->first_name) . "</li>";
    }
    echo "</ul>";
}
?> 