<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE UTILIZATORI JOOMLA ===\n\n";

// Total utilizatori WordPress
$total_users = count_users()['total_users'];
echo "Total utilizatori WordPress: $total_users\n";

// Utilizatori Joomla migrați
global $wpdb;
$joomla_users = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
    WHERE um.meta_key = 'joomla_id' AND um.meta_value IS NOT NULL
") ?: 0;

echo "Utilizatori Joomla migrați: $joomla_users\n";

// Utilizatori cu alte meta-keys Joomla
$joomla_username_users = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
    WHERE um.meta_key = 'joomla_username' AND um.meta_value IS NOT NULL
") ?: 0;

echo "Utilizatori cu joomla_username: $joomla_username_users\n";

$joomla_params_users = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
    WHERE um.meta_key = 'joomla_params' AND um.meta_value IS NOT NULL
") ?: 0;

echo "Utilizatori cu joomla_params: $joomla_params_users\n";

// Pacienți în tabelul clinica_patients
$clinica_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients") ?: 0;
echo "Pacienți în tabelul clinica_patients: $clinica_patients\n";

// Pacienți Joomla în tabelul clinica_patients
$joomla_patients = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE import_source = %s",
    'joomla_migration'
)) ?: 0;

echo "Pacienți Joomla în clinica_patients: $joomla_patients\n";

// Câteva exemple de utilizatori Joomla
echo "\n=== EXEMPLE UTILIZATORI JOOMLA ===\n";
$sample_users = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.user_email, um.meta_value as joomla_id
    FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
    WHERE um.meta_key = 'joomla_id' AND um.meta_value IS NOT NULL
    LIMIT 5
");

foreach ($sample_users as $user) {
    echo "ID: {$user->ID}, Login: {$user->user_login}, Email: {$user->user_email}, Joomla ID: {$user->joomla_id}\n";
}

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 