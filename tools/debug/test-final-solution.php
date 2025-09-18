<?php
/**
 * Script pentru testarea soluției finale
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Test Soluție Finală</h2>";

// Simulează exact condițiile din invalid-emails.php
$search = '';
$email_type_filter = '';
$status_filter = '';

$where_conditions = array();

// Filtru pentru e-mailuri neactualizate - exact ca în invalid-emails.php (complet)
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

// QUERY exact ca în invalid-emails.php (simplificat fără JOIN-uri pentru usermeta) - FĂRĂ phone
$query = "
    SELECT p.id, p.cnp, u.user_email, u.user_login,
           '' as first_name, '' as last_name, '' as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE {$where_clause}
    ORDER BY u.user_email ASC
    LIMIT 1000
";

echo "<p><strong>Where clause:</strong> " . esc_html($where_clause) . "</p>";
echo "<p><strong>Search:</strong> " . esc_html($search) . "</p>";
echo "<p><strong>Email type filter:</strong> " . esc_html($email_type_filter) . "</p>";
echo "<p><strong>Status filter:</strong> " . esc_html($status_filter) . "</p>";

$invalid_email_patients = $wpdb->get_results($query);
$total_invalid_email_patients = count($invalid_email_patients);

echo "<p><strong>Total rezultate:</strong> " . $total_invalid_email_patients . "</p>";

if (!empty($invalid_email_patients)) {
    echo "<h3>Primele 10 rezultate:</h3>";
    echo "<ul>";
    foreach (array_slice($invalid_email_patients, 0, 10) as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nu s-au găsit rezultate!</p>";
}

// Debug: Testează un query simplu pentru fake
$simple_fake_test = "
    SELECT p.id, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE LOWER(u.user_email) LIKE '%fake%'
    LIMIT 5
";
$simple_fake_results = $wpdb->get_results($simple_fake_test);
echo '<br><strong>Test simplu pentru fake:</strong><br>';
if (!empty($simple_fake_results)) {
    foreach ($simple_fake_results as $result) {
        echo '- ID: ' . $result->id . ', Email: ' . $result->user_email . '<br>';
    }
} else {
    echo 'Nu găsește fake!<br>';
}

// Debug: Afișează query-ul și numărul de rezultate (doar pentru admin)
echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
echo '<strong>Debug Info:</strong><br>';
echo '<strong>Query:</strong> ' . esc_html($query) . '<br>';
echo '<strong>Total rezultate:</strong> ' . $total_invalid_email_patients . '<br>';
echo '<strong>Where clause:</strong> ' . esc_html($where_clause) . '<br>';
echo '</div>';

// Debug: Afișează întotdeauna pentru a vedea ce se întâmplă
echo '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;">';
echo '<strong>Debug Info (întotdeauna vizibil):</strong><br>';
echo '<strong>Total rezultate:</strong> ' . $total_invalid_email_patients . '<br>';
echo '<strong>Where clause:</strong> ' . esc_html($where_clause) . '<br>';
echo '<strong>Search:</strong> ' . esc_html($search) . '<br>';
echo '<strong>Email type filter:</strong> ' . esc_html($email_type_filter) . '<br>';
echo '<strong>Status filter:</strong> ' . esc_html($status_filter) . '<br>';
echo '</div>';
?> 