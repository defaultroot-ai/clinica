<?php
/**
 * Script pentru testarea exactă a query-ului din invalid-emails.php fără JOIN-uri pentru usermeta
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Exact Query din invalid-emails.php fără JOIN-uri pentru usermeta</h2>";

// Simulează exact condițiile din invalid-emails.php
$search = '';
$email_type_filter = '';
$status_filter = '';

$where_conditions = array();

// Filtru pentru e-mailuri neactualizate - exact ca în invalid-emails.php (simplificat)
$where_conditions[] = "LOWER(u.user_email) LIKE '%fake%'";

if (!empty($search)) {
    $where_conditions[] = $wpdb->prepare(
        "(um1.meta_value LIKE %s OR um2.meta_value LIKE %s OR p.cnp LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)",
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%'
    );
}

// Filtru pentru tipul de e-mail - exact ca în invalid-emails.php
if (!empty($email_type_filter)) {
    switch ($email_type_filter) {
        case 'temp':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%temp%'";
            break;
        case 'demo':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%demo%'";
            break;
        case 'fake':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%fake%'";
            break;
        case 'sx':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%.sx'";
            break;
        case 'test':
            $where_conditions[] = "(LOWER(u.user_email) LIKE '%@test%' OR LOWER(u.user_email) LIKE '%@example%')";
            break;
    }
}

// Filtru pentru status (dacă este selectat) - exact ca în invalid-emails.php
if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $where_conditions[] = "(um_status.meta_value IS NULL OR um_status.meta_value = 'active')";
    } elseif ($status_filter === 'deceased') {
        $where_conditions[] = "um_status.meta_value = 'deceased'";
    }
}

$where_clause = implode(' AND ', $where_conditions);

// QUERY exact ca în invalid-emails.php dar fără JOIN-uri pentru usermeta
$query = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE {$where_clause}
    LIMIT 1000
";

echo "<p><strong>Where clause:</strong> " . esc_html($where_clause) . "</p>";
echo "<p><strong>Search:</strong> " . esc_html($search) . "</p>";
echo "<p><strong>Email type filter:</strong> " . esc_html($email_type_filter) . "</p>";
echo "<p><strong>Status filter:</strong> " . esc_html($status_filter) . "</p>";

$invalid_email_patients = $wpdb->get_results($query);
$total_invalid_email_patients = count($invalid_email_patients);

echo "<p><strong>Total rezultate fără JOIN-uri pentru usermeta:</strong> " . $total_invalid_email_patients . "</p>";

if (!empty($invalid_email_patients)) {
    echo "<h3>Primele 10 rezultate fără JOIN-uri pentru usermeta:</h3>";
    echo "<ul>";
    foreach (array_slice($invalid_email_patients, 0, 10) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate fără JOIN-uri pentru usermeta!</p>";
}

// Testează și cu JOIN-uri pentru usermeta dar fără ORDER BY
echo "<h3>Test cu JOIN-uri pentru usermeta dar fără ORDER BY:</h3>";
$query_with_usermeta_no_order = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$where_clause}
    LIMIT 1000
";

$results_with_usermeta_no_order = $wpdb->get_results($query_with_usermeta_no_order);
echo "<p><strong>Rezultate cu JOIN-uri pentru usermeta dar fără ORDER BY:</strong> " . count($results_with_usermeta_no_order) . "</p>";

if (!empty($results_with_usermeta_no_order)) {
    echo "<h3>Primele 5 rezultate cu JOIN-uri pentru usermeta dar fără ORDER BY:</h3>";
    echo "<ul>";
    foreach (array_slice($results_with_usermeta_no_order, 0, 5) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . " | Nume: " . esc_html($patient->first_name . ' ' . $patient->last_name) . "</li>";
    }
    echo "</ul>";
}

// Testează și cu un WHERE clause simplu
echo "<h3>Test cu WHERE clause simplu:</h3>";
$simple_query = "
    SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE LOWER(u.user_email) LIKE '%fake%'
    LIMIT 10
";

$simple_results = $wpdb->get_results($simple_query);
echo "<p><strong>Rezultate cu WHERE clause simplu:</strong> " . count($simple_results) . "</p>";

if (!empty($simple_results)) {
    echo "<h3>Primele 5 rezultate cu WHERE clause simplu:</h3>";
    echo "<ul>";
    foreach (array_slice($simple_results, 0, 5) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . "</li>";
    }
    echo "</ul>";
}
?> 