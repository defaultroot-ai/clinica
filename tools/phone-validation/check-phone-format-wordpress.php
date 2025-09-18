<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE FORMAT TELEFOANE ÎN WORDPRESS ===\n\n";

global $wpdb;

// Verifică meta-keys pentru telefoane
echo "=== META-KEYS TELEFOANE ÎN WORDPRESS ===\n";

$phone_meta_keys = $wpdb->get_results("
    SELECT DISTINCT um.meta_key, COUNT(*) as count
    FROM {$wpdb->usermeta} um
    WHERE um.meta_key LIKE '%telefon%' OR um.meta_key LIKE '%phone%'
    GROUP BY um.meta_key
    ORDER BY count DESC
");

if ($phone_meta_keys) {
    foreach ($phone_meta_keys as $meta) {
        echo "Meta-key: {$meta->meta_key} - Count: {$meta->count}\n";
    }
} else {
    echo "Nu s-au găsit meta-keys pentru telefoane\n";
}

echo "\n=== VERIFICARE TABELUL CLINICA_PATIENTS ===\n";

// Verifică structura tabelului clinica_patients
$table_structure = $wpdb->get_results("
    DESCRIBE {$wpdb->prefix}clinica_patients
");

echo "Structura tabelului clinica_patients:\n";
foreach ($table_structure as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}

echo "\n=== SAMPLE TELEFOANE DIN CLINICA_PATIENTS ===\n";

// Obține câteva exemple de telefoane din tabelul clinica_patients
$sample_phones = $wpdb->get_results("
    SELECT 
        p.id,
        p.user_id,
        p.cnp,
        p.phone_primary,
        p.phone_secondary,
        u.user_login,
        u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE p.phone_primary IS NOT NULL OR p.phone_secondary IS NOT NULL
    ORDER BY p.id
    LIMIT 20
");

if ($sample_phones) {
    echo "Primele 20 telefoane din clinica_patients:\n\n";
    foreach ($sample_phones as $phone) {
        echo "ID: {$phone->id}, User: {$phone->user_login}\n";
        echo "  CNP: {$phone->cnp}\n";
        echo "  Telefon principal: " . ($phone->phone_primary ?: 'NULL') . "\n";
        echo "  Telefon secundar: " . ($phone->phone_secondary ?: 'NULL') . "\n";
        echo "  Email: {$phone->user_email}\n";
        echo "\n";
    }
} else {
    echo "Nu s-au găsit telefoane în tabelul clinica_patients\n";
}

echo "\n=== STATISTICI TELEFOANE ===\n";

// Statistici despre telefoane
$total_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients");
$patients_with_primary = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE phone_primary IS NOT NULL");
$patients_with_secondary = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE phone_secondary IS NOT NULL");
$patients_with_both = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE phone_primary IS NOT NULL AND phone_secondary IS NOT NULL");

echo "Total pacienți: $total_patients\n";
echo "Pacienți cu telefon principal: $patients_with_primary\n";
echo "Pacienți cu telefon secundar: $patients_with_secondary\n";
echo "Pacienți cu ambele telefoane: $patients_with_both\n";

echo "\n=== ANALIZĂ FORMATE TELEFOANE ===\n";

// Analizează formatele de telefon
$phone_formats = $wpdb->get_results("
    SELECT 
        phone_primary,
        COUNT(*) as count
    FROM {$wpdb->prefix}clinica_patients 
    WHERE phone_primary IS NOT NULL
    GROUP BY phone_primary
    ORDER BY count DESC
    LIMIT 10
");

echo "Top 10 formate de telefon principal:\n";
foreach ($phone_formats as $format) {
    $clean_phone = preg_replace('/[^0-9+]/', '', $format->phone_primary);
    $length = strlen($clean_phone);
    $starts_with = substr($clean_phone, 0, 3);
    
    echo "  '{$format->phone_primary}' (Count: {$format->count})\n";
    echo "    - Clean: {$clean_phone}\n";
    echo "    - Length: {$length}\n";
    echo "    - Starts with: {$starts_with}\n";
    echo "\n";
}

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 