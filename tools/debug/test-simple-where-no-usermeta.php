<?php
/**
 * Script pentru testarea unui WHERE clause simplu fără JOIN-uri pentru usermeta
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test WHERE Clause Simplu fără JOIN-uri pentru usermeta</h2>";

// Test 1: Query cu WHERE simplu
echo "<h3>Test 1: Query cu WHERE simplu</h3>";
$simple_query = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE LOWER(u.user_email) LIKE '%fake%'
    LIMIT 10
";

$simple_results = $wpdb->get_results($simple_query);
echo "<p><strong>Rezultate cu WHERE simplu:</strong> " . count($simple_results) . "</p>";

if (!empty($simple_results)) {
    echo "<h3>Primele 5 rezultate cu WHERE simplu:</h3>";
    echo "<ul>";
    foreach (array_slice($simple_results, 0, 5) as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate cu WHERE simplu!</p>";
}

// Test 2: Query fără WHERE pentru a vedea dacă JOIN-ul funcționează
echo "<h3>Test 2: Query fără WHERE</h3>";
$no_where_query = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 10
";

$no_where_results = $wpdb->get_results($no_where_query);
echo "<p><strong>Rezultate fără WHERE:</strong> " . count($no_where_results) . "</p>";

if (!empty($no_where_results)) {
    echo "<h3>Primele 5 rezultate fără WHERE:</h3>";
    echo "<ul>";
    foreach (array_slice($no_where_results, 0, 5) as $result) {
        echo "<li>ID: " . $result->id . " | CNP: " . $result->cnp . " | Email: " . esc_html($result->user_email) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate fără WHERE!</p>";
}

// Test 3: Verifică dacă există e-mailuri cu 'fake' în baza de date
echo "<h3>Test 3: Verifică e-mailuri cu 'fake'</h3>";
$check_fake_query = "
    SELECT user_email, user_login
    FROM {$wpdb->users}
    WHERE LOWER(user_email) LIKE '%fake%'
    LIMIT 10
";

$fake_emails = $wpdb->get_results($check_fake_query);
echo "<p><strong>E-mailuri cu 'fake' în users:</strong> " . count($fake_emails) . "</p>";

if (!empty($fake_emails)) {
    echo "<h3>Primele 5 e-mailuri cu 'fake':</h3>";
    echo "<ul>";
    foreach (array_slice($fake_emails, 0, 5) as $email) {
        echo "<li>Email: " . esc_html($email->user_email) . " | Login: " . esc_html($email->user_login) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu există e-mailuri cu 'fake' în tabelul users!</p>";
}

// Test 4: Verifică dacă există JOIN-uri între pacienți și utilizatori
echo "<h3>Test 4: Verifică JOIN-uri între pacienți și utilizatori</h3>";
$check_join_query = "
    SELECT COUNT(*) as total_joined
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE u.ID IS NOT NULL
";

$total_joined = $wpdb->get_var($check_join_query);
echo "<p><strong>Total pacienți cu user_id valid:</strong> " . $total_joined . "</p>";

// Test 5: Verifică dacă există pacienți cu e-mailuri cu 'fake'
echo "<h3>Test 5: Verifică pacienți cu e-mailuri cu 'fake'</h3>";
$check_patients_fake_query = "
    SELECT COUNT(*) as total_patients_fake
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE LOWER(u.user_email) LIKE '%fake%'
";

$total_patients_fake = $wpdb->get_var($check_patients_fake_query);
echo "<p><strong>Total pacienți cu e-mailuri cu 'fake':</strong> " . $total_patients_fake . "</p>";
?> 