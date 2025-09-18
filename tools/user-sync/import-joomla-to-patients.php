<?php
require_once('../../../wp-load.php');

echo "=== IMPORT UTILIZATORI JOOMLA ÎN TABELUL CLINICA_PATIENTS ===\n\n";

global $wpdb;

// Verifică dacă tabelul clinica_patients există
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}clinica_patients'");
if (!$table_exists) {
    die("❌ Tabelul {$wpdb->prefix}clinica_patients nu există!\n");
}

echo "✅ Tabelul clinica_patients există\n";

// Obține utilizatorii Joomla din WordPress
$joomla_users = $wpdb->get_results("
    SELECT 
        u.ID as wp_user_id,
        u.user_login,
        u.user_email,
        u.user_registered,
        um_joomla_id.meta_value as joomla_id,
        um_joomla_name.meta_value as joomla_name,
        um_joomla_phone.meta_value as joomla_phone,
        um_joomla_fisa.meta_value as joomla_fisa,
        um_joomla_nastere.meta_value as joomla_nastere,
        um_joomla_adresa.meta_value as joomla_adresa,
        um_joomla_localitate.meta_value as joomla_localitate,
        um_joomla_judet.meta_value as joomla_judet,
        um_joomla_telefon.meta_value as joomla_telefon,
        um_joomla_telefon2.meta_value as joomla_telefon2
    FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um_joomla_id ON u.ID = um_joomla_id.user_id AND um_joomla_id.meta_key = 'joomla_id'
    LEFT JOIN {$wpdb->usermeta} um_joomla_name ON u.ID = um_joomla_name.user_id AND um_joomla_name.meta_key = 'joomla_name'
    LEFT JOIN {$wpdb->usermeta} um_joomla_phone ON u.ID = um_joomla_phone.user_id AND um_joomla_phone.meta_key = 'joomla_phone'
    LEFT JOIN {$wpdb->usermeta} um_joomla_fisa ON u.ID = um_joomla_fisa.user_id AND um_joomla_fisa.meta_key = 'joomla_fisa'
    LEFT JOIN {$wpdb->usermeta} um_joomla_nastere ON u.ID = um_joomla_nastere.user_id AND um_joomla_nastere.meta_key = 'joomla_nastere'
    LEFT JOIN {$wpdb->usermeta} um_joomla_adresa ON u.ID = um_joomla_adresa.user_id AND um_joomla_adresa.meta_key = 'joomla_adresa'
    LEFT JOIN {$wpdb->usermeta} um_joomla_localitate ON u.ID = um_joomla_localitate.user_id AND um_joomla_localitate.meta_key = 'joomla_localitate'
    LEFT JOIN {$wpdb->usermeta} um_joomla_judet ON u.ID = um_joomla_judet.user_id AND um_joomla_judet.meta_key = 'joomla_judet'
    LEFT JOIN {$wpdb->usermeta} um_joomla_telefon ON u.ID = um_joomla_telefon.user_id AND um_joomla_telefon.meta_key = 'joomla_telefon'
    LEFT JOIN {$wpdb->usermeta} um_joomla_telefon2 ON u.ID = um_joomla_telefon2.user_id AND um_joomla_telefon2.meta_key = 'joomla_telefon2'
    ORDER BY u.ID
");

echo "Găsiți " . count($joomla_users) . " utilizatori Joomla\n\n";

// Verifică câți pacienți există deja
$existing_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients");
echo "Pacienți existenți în tabel: $existing_patients\n\n";

$imported_count = 0;
$skipped_count = 0;
$error_count = 0;

echo "=== ÎNCEPE IMPORTUL ===\n";

foreach ($joomla_users as $user) {
    // Verifică dacă pacientul există deja
    $existing_patient = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d",
        $user->wp_user_id
    ));
    
    if ($existing_patient) {
        echo "⏭️ Pacientul există deja: {$user->user_login} (ID: {$user->wp_user_id})\n";
        $skipped_count++;
        continue;
    }
    
    // Extrage CNP din username dacă este posibil
    $cnp = null;
    if (ctype_digit($user->user_login) && strlen($user->user_login) == 13) {
        $cnp = $user->user_login;
    }
    
    // Extrage numele din joomla_name
    $first_name = '';
    $last_name = '';
    if ($user->joomla_name) {
        $name_parts = explode(' ', trim($user->joomla_name));
        if (count($name_parts) >= 2) {
            $first_name = $name_parts[0];
            $last_name = implode(' ', array_slice($name_parts, 1));
        } else {
            $first_name = $user->joomla_name;
        }
    }
    
    // Pregătește datele pentru inserare
    $patient_data = array(
        'user_id' => $user->wp_user_id,
        'cnp' => $cnp ?: '0000000000000', // CNP temporar dacă nu există
        'phone_primary' => $user->joomla_telefon ?: $user->joomla_phone,
        'phone_secondary' => $user->joomla_telefon2,
        'address' => $user->joomla_adresa,
        'birth_date' => $user->joomla_nastere ?: null,
        'medical_history' => $user->joomla_fisa,
        'import_source' => 'joomla_migration'
    );
    
    // Inserare în tabelul clinica_patients
    $result = $wpdb->insert(
        $wpdb->prefix . 'clinica_patients',
        $patient_data,
        array(
            '%d', // user_id
            '%s', // cnp
            '%s', // phone_primary
            '%s', // phone_secondary
            '%s', // address
            '%s', // birth_date
            '%s', // medical_history
            '%s'  // import_source
        )
    );
    
            if ($result !== false) {
            echo "✅ Importat: {$user->user_login} (CNP: {$cnp})\n";
            $imported_count++;
        } else {
            echo "❌ Eroare la importul: {$user->user_login} - " . $wpdb->last_error . "\n";
            $error_count++;
        }
}

echo "\n=== REZULTATE IMPORT ===\n";
echo "✅ Importați cu succes: $imported_count pacienți\n";
echo "⏭️ Săriți (existați): $skipped_count pacienți\n";
echo "❌ Erori: $error_count pacienți\n";

// Verifică rezultatul final
$final_patients_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients");
$joomla_patients_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE import_source = %s",
    'joomla_migration'
));

echo "\n=== VERIFICARE FINALĂ ===\n";
echo "Total pacienți în tabel: $final_patients_count\n";
echo "Pacienți Joomla importați: $joomla_patients_count\n";

echo "\n=== IMPORT COMPLET ===\n"; 