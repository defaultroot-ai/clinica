<?php
/**
 * Script pentru testarea JOIN-urilor unul câte unul
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test JOIN-uri Unul câte Unul</h2>";

// Test 1: Doar clinica_patients și users
echo "<h3>Test 1: Doar clinica_patients și users</h3>";
$query1 = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$results1 = $wpdb->get_results($query1);
echo "<p><strong>Rezultate doar clinica_patients și users:</strong> " . count($results1) . "</p>";
if (!empty($results1)) {
    foreach ($results1 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "<br>";
    }
}

// Test 2: Adaugă first_name JOIN
echo "<h3>Test 2: Adaugă first_name JOIN</h3>";
$query2 = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 5
";
$results2 = $wpdb->get_results($query2);
echo "<p><strong>Rezultate cu first_name JOIN:</strong> " . count($results2) . "</p>";
if (!empty($results2)) {
    foreach ($results2 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | First Name: " . esc_html($result->first_name) . "<br>";
    }
}

// Test 3: Adaugă last_name JOIN
echo "<h3>Test 3: Adaugă last_name JOIN</h3>";
$query3 = "
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
$results3 = $wpdb->get_results($query3);
echo "<p><strong>Rezultate cu last_name JOIN:</strong> " . count($results3) . "</p>";
if (!empty($results3)) {
    foreach ($results3 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}

// Test 4: Adaugă ORDER BY
echo "<h3>Test 4: Adaugă ORDER BY</h3>";
$query4 = "
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
$results4 = $wpdb->get_results($query4);
echo "<p><strong>Rezultate cu ORDER BY:</strong> " . count($results4) . "</p>";
if (!empty($results4)) {
    foreach ($results4 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}

// Test 5: Verifică dacă problema este cu WHERE clause-ul complex
echo "<h3>Test 5: Verifică WHERE clause-ul complex</h3>";
$complex_where = "(
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

$query5 = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$complex_where}
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT 5
";
$results5 = $wpdb->get_results($query5);
echo "<p><strong>Rezultate cu WHERE clause complex:</strong> " . count($results5) . "</p>";
if (!empty($results5)) {
    foreach ($results5 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}

// Test 6: Verifică dacă problema este cu parantezele în WHERE
echo "<h3>Test 6: Verifică parantezele în WHERE</h3>";
$query6 = "
    SELECT p.id, p.cnp, u.user_email, um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE LOWER(u.user_email) LIKE '%fake%'
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT 5
";
$results6 = $wpdb->get_results($query6);
echo "<p><strong>Rezultate cu WHERE simplu:</strong> " . count($results6) . "</p>";
if (!empty($results6)) {
    foreach ($results6 as $result) {
        echo "- ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "<br>";
    }
}
?> 