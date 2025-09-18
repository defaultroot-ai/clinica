<?php
/**
 * Script pentru testarea exactă a condițiilor din invalid-emails.php
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Exact Condiții din invalid-emails.php</h2>";

// Simulează exact condițiile din invalid-emails.php
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$email_type_filter = isset($_GET['email_type']) ? sanitize_text_field($_GET['email_type']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

echo "<p><strong>Search:</strong> '" . esc_html($search) . "'</p>";
echo "<p><strong>Email type filter:</strong> '" . esc_html($email_type_filter) . "'</p>";
echo "<p><strong>Status filter:</strong> '" . esc_html($status_filter) . "'</p>";

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

echo "<p><strong>Where conditions count:</strong> " . count($where_conditions) . "</p>";
echo "<p><strong>Where clause:</strong> " . esc_html($where_clause) . "</p>";

            // QUERY exact ca în invalid-emails.php (simplificat fără JOIN-uri pentru usermeta)
            $query = "
                SELECT p.id, p.cnp, p.phone, u.user_email, u.user_login,
                       '' as first_name, '' as last_name, '' as status
                FROM {$wpdb->prefix}clinica_patients p
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                WHERE {$where_clause}
                ORDER BY u.user_email ASC
                LIMIT 1000
            ";

echo "<p><strong>Query:</strong> " . esc_html($query) . "</p>";

$invalid_email_patients = $wpdb->get_results($query);
$total_invalid_email_patients = count($invalid_email_patients);

echo "<p><strong>Total rezultate:</strong> " . $total_invalid_email_patients . "</p>";

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

// Testează și query-ul simplificat pentru comparație
echo "<h3>Test query simplificat pentru comparație:</h3>";
$simple_query = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (
        LOWER(u.user_email) LIKE '%fake%'
    )
    LIMIT 10
";
$simple_results = $wpdb->get_results($simple_query);
echo "<p><strong>Rezultate query simplificat:</strong> " . count($simple_results) . "</p>";

// Testează și query-ul cu WHERE clause-ul complex dar fără JOIN-uri multiple
echo "<h3>Test query cu WHERE complex dar fără JOIN-uri multiple:</h3>";
$complex_query = "
    SELECT p.id, p.cnp, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE {$where_clause}
    LIMIT 10
";
$complex_results = $wpdb->get_results($complex_query);
echo "<p><strong>Rezultate query cu WHERE complex:</strong> " . count($complex_results) . "</p>";
?> 