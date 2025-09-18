<?php
/**
 * Script de debug pentru verificarea câmpurilor usermeta
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Debug Usermeta Fields</h2>";

// 1. Verifică câte înregistrări există pentru first_name și last_name
$first_name_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'first_name'");
$last_name_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'last_name'");

echo "<p><strong>Utilizatori cu first_name:</strong> " . $first_name_count . "</p>";
echo "<p><strong>Utilizatori cu last_name:</strong> " . $last_name_count . "</p>";

// 2. Verifică câte utilizatori există în total
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
echo "<p><strong>Total utilizatori:</strong> " . $total_users . "</p>";

// 3. Verifică câte utilizatori au first_name și last_name
$users_with_names = $wpdb->get_var("
    SELECT COUNT(DISTINCT u.ID) 
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    INNER JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
");
echo "<p><strong>Utilizatori cu first_name și last_name:</strong> " . $users_with_names . "</p>";

// 4. Testează query-ul simplificat fără JOIN-uri pentru usermeta
$simple_query = "
    SELECT p.id, p.cnp, u.user_email, u.user_login
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

$simple_results = $wpdb->get_results($simple_query);
echo "<p><strong>Rezultate cu query simplificat:</strong> " . count($simple_results) . "</p>";

if (!empty($simple_results)) {
    echo "<h3>Primele 10 rezultate cu query simplificat:</h3>";
    echo "<ul>";
    foreach ($simple_results as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "</li>";
    }
    echo "</ul>";
}

// 5. Testează query-ul original din invalid-emails.php
$original_query = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name,
           um_status.meta_value as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
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
    ORDER BY um2.meta_value ASC, um1.meta_value ASC
    LIMIT 10
";

$original_results = $wpdb->get_results($original_query);
echo "<p><strong>Rezultate cu query original:</strong> " . count($original_results) . "</p>";

if (!empty($original_results)) {
    echo "<h3>Primele 10 rezultate cu query original:</h3>";
    echo "<ul>";
    foreach ($original_results as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . " | Nume: " . esc_html($result->first_name . ' ' . $result->last_name) . "</li>";
    }
    echo "</ul>";
}

// 6. Verifică dacă problema este cu ORDER BY
$query_without_order = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name,
           um_status.meta_value as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
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

$results_without_order = $wpdb->get_results($query_without_order);
echo "<p><strong>Rezultate fără ORDER BY:</strong> " . count($results_without_order) . "</p>";
?> 