<?php
/**
 * Script de debug pentru verificarea email-urilor invalide
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>Debug Email-uri Invalide</h2>";

// 1. Verifică total pacienți
$total_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients");
echo "<p><strong>Total pacienți în baza de date:</strong> " . $total_patients . "</p>";

// 2. Verifică total utilizatori cu email-uri
$total_users_with_email = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_email IS NOT NULL AND user_email != ''");
echo "<p><strong>Total utilizatori cu email-uri:</strong> " . $total_users_with_email . "</p>";

// 3. Verifică pacienți cu email-uri invalide
$invalid_query = "
    SELECT COUNT(*) as total
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
";

$invalid_count = $wpdb->get_var($invalid_query);
echo "<p><strong>Total pacienți cu email-uri invalide:</strong> " . $invalid_count . "</p>";

// 4. Afișează exemple de email-uri din baza de date
echo "<h3>Exemple de email-uri din baza de date:</h3>";
$sample_emails = $wpdb->get_results("SELECT user_email FROM {$wpdb->users} LIMIT 20");
echo "<ul>";
foreach ($sample_emails as $email) {
    echo "<li>" . esc_html($email->user_email) . "</li>";
}
echo "</ul>";

// 5. Verifică dacă există pacienți cu email-uri invalide
$invalid_emails = $wpdb->get_results("
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
");

echo "<h3>Pacienți cu email-uri invalide găsiți:</h3>";
if (empty($invalid_emails)) {
    echo "<p>Nu s-au găsit pacienți cu email-uri invalide.</p>";
} else {
    echo "<ul>";
    foreach ($invalid_emails as $patient) {
        echo "<li>ID: " . $patient->id . " | CNP: " . $patient->cnp . " | Email: " . esc_html($patient->user_email) . "</li>";
    }
    echo "</ul>";
}

// 6. Verifică structura tabelelor
echo "<h3>Structura tabelelor:</h3>";
$tables = array(
    'clinica_patients' => $wpdb->prefix . 'clinica_patients',
    'users' => $wpdb->users,
    'usermeta' => $wpdb->usermeta
);

foreach ($tables as $table_name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "<p><strong>$table_name:</strong> $count înregistrări</p>";
}

// 7. Verifică relația între pacienți și utilizatori
$patients_with_users = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->prefix}clinica_patients p
    INNER JOIN {$wpdb->users} u ON p.user_id = u.ID
");
echo "<p><strong>Pacienți cu utilizatori asociați:</strong> " . $patients_with_users . "</p>";

$patients_without_users = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE u.ID IS NULL
");
echo "<p><strong>Pacienți fără utilizatori asociați:</strong> " . $patients_without_users . "</p>";
?> 