<?php
require_once('../../../wp-load.php');

echo "=== ACTUALIZARE IMPORT TELEFOANE CU SUPORT UCRAINA ===\n\n";

global $wpdb;

// Funcție pentru validarea și formatarea telefonului (cu suport Ucraina)
function validateAndFormatPhoneWithUkraine($phone) {
    if (empty($phone)) return '';
    
    // Elimină spațiile și caracterele speciale
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Verifică dacă este un telefon valid românesc
    if (preg_match('/^(\+40|0)[0-9]{9}$/', $clean_phone)) {
        return $clean_phone;
    }
    
    // Verifică dacă este un telefon valid ucrainean
    if (preg_match('/^\+380[0-9]{9}$/', $clean_phone)) {
        return $clean_phone;
    }
    
    // Verifică dacă este un telefon internațional valid
    if (preg_match('/^\+[0-9]{10,15}$/', $clean_phone)) {
        return $clean_phone;
    }
    
    // Dacă nu este valid, returnează gol
    return '';
}

// Funcție pentru a determina țara telefonului
function getPhoneCountry($phone) {
    if (empty($phone)) return 'UNKNOWN';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (preg_match('/^(\+40|0)/', $clean_phone)) {
        return 'ROMANIA';
    }
    
    if (preg_match('/^\+380/', $clean_phone)) {
        return 'UKRAINE';
    }
    
    if (preg_match('/^\+/', $clean_phone)) {
        return 'INTERNATIONAL';
    }
    
    return 'UNKNOWN';
}

// Obține toți pacienții Joomla importați
$patients = $wpdb->get_results("
    SELECT 
        p.id,
        p.user_id,
        p.cnp,
        um_joomla_id.meta_value as joomla_id
    FROM {$wpdb->prefix}clinica_patients p
    JOIN {$wpdb->usermeta} um_joomla_id ON p.user_id = um_joomla_id.user_id 
    WHERE um_joomla_id.meta_key = 'joomla_id' 
    AND p.import_source = 'joomla_migration'
    ORDER BY p.id
");

echo "Găsiți " . count($patients) . " pacienți Joomla pentru actualizare\n\n";

// Conectare la baza de date Joomla
$joomla_db_host = 'localhost';
$joomla_db_name = 'cmmf';
$joomla_db_user = 'root';
$joomla_db_pass = '';

$joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);

if ($joomla_db->connect_error) {
    die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
}

$updated_count = 0;
$skipped_count = 0;
$error_count = 0;
$romania_count = 0;
$ukraine_count = 0;
$international_count = 0;

echo "=== ÎNCEPE ACTUALIZAREA CU SUPORT UCRAINA ===\n";

foreach ($patients as $patient) {
    // Obține telefoanele din baza de date Joomla
    $joomla_query = "SELECT cb_telefon, cb_telefon2 FROM bqzce_comprofiler WHERE user_id = {$patient->joomla_id}";
    $joomla_result = $joomla_db->query($joomla_query);
    
    if (!$joomla_result || $joomla_result->num_rows == 0) {
        $skipped_count++;
        continue;
    }
    
    $joomla_data = $joomla_result->fetch_assoc();
    
    // Validează și formatează telefoanele
    $phone_primary = validateAndFormatPhoneWithUkraine($joomla_data['cb_telefon']);
    $phone_secondary = validateAndFormatPhoneWithUkraine($joomla_data['cb_telefon2']);
    
    // Actualizează în baza de date WordPress
    $update_data = array();
    $update_format = array();
    
    if (!empty($phone_primary)) {
        $update_data['phone_primary'] = $phone_primary;
        $update_format[] = '%s';
    }
    
    if (!empty($phone_secondary)) {
        $update_data['phone_secondary'] = $phone_secondary;
        $update_format[] = '%s';
    }
    
    if (empty($update_data)) {
        $skipped_count++;
        continue;
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'clinica_patients',
        $update_data,
        array('id' => $patient->id),
        $update_format,
        array('%d')
    );
    
    if ($result !== false) {
        echo "✅ Actualizat pacientul {$patient->cnp}: ";
        
        if (!empty($phone_primary)) {
            $country_primary = getPhoneCountry($phone_primary);
            echo "Primar: {$phone_primary} ({$country_primary}) ";
            
            if ($country_primary === 'ROMANIA') $romania_count++;
            elseif ($country_primary === 'UKRAINE') $ukraine_count++;
            elseif ($country_primary === 'INTERNATIONAL') $international_count++;
        }
        
        if (!empty($phone_secondary)) {
            $country_secondary = getPhoneCountry($phone_secondary);
            echo "Secundar: {$phone_secondary} ({$country_secondary})";
            
            if ($country_secondary === 'ROMANIA') $romania_count++;
            elseif ($country_secondary === 'UKRAINE') $ukraine_count++;
            elseif ($country_secondary === 'INTERNATIONAL') $international_count++;
        }
        
        echo "\n";
        $updated_count++;
    } else {
        echo "❌ Eroare la actualizarea pacientului {$patient->cnp}: " . $wpdb->last_error . "\n";
        $error_count++;
    }
}

// Închide conexiunea Joomla
$joomla_db->close();

echo "\n=== REZULTATE ACTUALIZARE CU SUPORT UCRAINA ===\n";
echo "✅ Actualizați cu succes: $updated_count pacienți\n";
echo "⏭️ Săriți (fără telefoane valide): $skipped_count pacienți\n";
echo "❌ Erori: $error_count pacienți\n";

echo "\n=== STATISTICI PE ȚĂRI ===\n";
echo "Telefoane România: $romania_count\n";
echo "Telefoane Ucraina: $ukraine_count\n";
echo "Telefoane internaționale: $international_count\n";

// Verifică rezultatul final
$final_phones_count = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients 
    WHERE import_source = 'joomla_migration' 
    AND (phone_primary IS NOT NULL OR phone_secondary IS NOT NULL)
");

echo "\n=== VERIFICARE FINALĂ ===\n";
echo "Pacienți Joomla cu telefoane: $final_phones_count\n";

// Verifică dacă există telefoane ucrainene în baza de date
$ukraine_phones_in_db = $wpdb->get_results("
    SELECT 
        p.id,
        p.cnp,
        p.phone_primary,
        p.phone_secondary,
        u.user_login
    FROM {$wpdb->prefix}clinica_patients p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (p.phone_primary LIKE '+380%' OR p.phone_secondary LIKE '+380%')
    AND p.import_source = 'joomla_migration'
    LIMIT 10
");

if ($ukraine_phones_in_db) {
    echo "\n=== TELEFOANE UCRAINENE GĂSITE ===\n";
    foreach ($ukraine_phones_in_db as $phone) {
        echo "Pacient: {$phone->user_login} (CNP: {$phone->cnp})\n";
        if ($phone->phone_primary && strpos($phone->phone_primary, '+380') === 0) {
            echo "  Telefon principal: {$phone->phone_primary}\n";
        }
        if ($phone->phone_secondary && strpos($phone->phone_secondary, '+380') === 0) {
            echo "  Telefon secundar: {$phone->phone_secondary}\n";
        }
        echo "\n";
    }
} else {
    echo "\nℹ️ Nu s-au găsit telefoane ucrainene în baza de date (normal, dacă nu există în datele Joomla)\n";
}

echo "\n=== ACTUALIZARE COMPLETĂ CU SUPORT UCRAINA ===\n"; 