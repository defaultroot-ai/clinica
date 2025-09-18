<?php
require_once('../../../wp-load.php');

echo "=== ACTUALIZARE IMPORT TELEFOANE ===\n\n";

global $wpdb;

// Funcție pentru validarea și formatarea telefonului
function validateAndFormatPhone($phone) {
    if (empty($phone)) return '';
    
    // Elimină spațiile și caracterele speciale
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Verifică dacă este un telefon valid românesc
    if (preg_match('/^(\+40|0)[0-9]{9}$/', $clean_phone)) {
        return $clean_phone;
    }
    
    // Verifică dacă este un telefon internațional valid
    if (preg_match('/^\+[0-9]{10,15}$/', $clean_phone)) {
        return $clean_phone;
    }
    
    // Dacă nu este valid, returnează gol
    return '';
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

echo "=== ÎNCEPE ACTUALIZAREA ===\n";

foreach ($patients as $patient) {
    // Obține telefoanele din baza de date Joomla
    $joomla_query = "SELECT cb_telefon, cb_telefon2 FROM bqzce_comprofiler WHERE user_id = {$patient->joomla_id}";
    $joomla_result = $joomla_db->query($joomla_query);
    
    if (!$joomla_result || $joomla_result->num_rows == 0) {
        echo "⏭️ Nu s-au găsit telefoane pentru pacientul {$patient->cnp} (Joomla ID: {$patient->joomla_id})\n";
        $skipped_count++;
        continue;
    }
    
    $joomla_data = $joomla_result->fetch_assoc();
    
    // Validează și formatează telefoanele
    $phone_primary = validateAndFormatPhone($joomla_data['cb_telefon']);
    $phone_secondary = validateAndFormatPhone($joomla_data['cb_telefon2']);
    
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
        echo "⏭️ Nu s-au găsit telefoane valide pentru pacientul {$patient->cnp}\n";
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
        if (!empty($phone_primary)) echo "Primar: {$phone_primary} ";
        if (!empty($phone_secondary)) echo "Secundar: {$phone_secondary}";
        echo "\n";
        $updated_count++;
    } else {
        echo "❌ Eroare la actualizarea pacientului {$patient->cnp}: " . $wpdb->last_error . "\n";
        $error_count++;
    }
}

// Închide conexiunea Joomla
$joomla_db->close();

echo "\n=== REZULTATE ACTUALIZARE ===\n";
echo "✅ Actualizați cu succes: $updated_count pacienți\n";
echo "⏭️ Săriți (fără telefoane valide): $skipped_count pacienți\n";
echo "❌ Erori: $error_count pacienți\n";

// Verifică rezultatul final
$final_phones_count = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients 
    WHERE import_source = 'joomla_migration' 
    AND (phone_primary IS NOT NULL OR phone_secondary IS NOT NULL)
");

echo "\n=== VERIFICARE FINALĂ ===\n";
echo "Pacienți Joomla cu telefoane: $final_phones_count\n";

echo "\n=== ACTUALIZARE COMPLETĂ ===\n"; 