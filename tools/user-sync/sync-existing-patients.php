<?php
/**
 * Script pentru sincronizarea pacienților existenți din WordPress cu tabela wp_clinica_patients
 * Rulează acest script pentru a adăuga pacienții existenți în tabela custom
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă scriptul este rulat din admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

global $wpdb;

echo "<h1>🔄 Sincronizare Pacienți Existenți</h1>";

// 1. Găsește toți utilizatorii cu rolul "Pacient"
$patients_query = "
    SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
           um1.meta_value as first_name,
           um2.meta_value as last_name,
           um3.meta_value as phone_primary,
           um4.meta_value as phone_secondary
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone_primary'
    LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone_secondary'
    WHERE u.ID IN (
        SELECT user_id 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = '{$wpdb->prefix}capabilities' 
        AND meta_value LIKE '%clinica_patient%'
    )
    ORDER BY u.user_registered DESC
";

$patients = $wpdb->get_results($patients_query);

echo "<h2>📊 Pacienți găsiți în WordPress: " . count($patients) . "</h2>";

if (empty($patients)) {
    echo "<p>❌ Nu s-au găsit pacienți în WordPress cu rolul 'clinica_patient'</p>";
    exit;
}

// 2. Verifică tabela wp_clinica_patients
$clinica_table = $wpdb->prefix . 'clinica_patients';
$existing_patients = $wpdb->get_results("SELECT user_id FROM $clinica_table");
$existing_user_ids = array_column($existing_patients, 'user_id');

echo "<h2>📋 Pacienți existenți în tabela clinica: " . count($existing_user_ids) . "</h2>";

// 3. Găsește pacienții care trebuie sincronizați
$patients_to_sync = array();
foreach ($patients as $patient) {
    if (!in_array($patient->ID, $existing_user_ids)) {
        $patients_to_sync[] = $patient;
    }
}

echo "<h2>🔄 Pacienți de sincronizat: " . count($patients_to_sync) . "</h2>";

if (empty($patients_to_sync)) {
    echo "<p>✅ Toți pacienții sunt deja sincronizați!</p>";
    exit;
}

// 4. Sincronizează pacienții
$synced_count = 0;
$errors = array();

foreach ($patients_to_sync as $patient) {
    echo "<hr>";
    echo "<h3>🔄 Sincronizare pacient: {$patient->display_name} (ID: {$patient->ID})</h3>";
    
    // Parsează CNP-ul din username (presupunem că username-ul este CNP-ul)
    $cnp = $patient->user_login;
    
    // Verifică dacă CNP-ul este valid
    if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
        echo "<p>⚠️ CNP invalid pentru pacientul {$patient->display_name}: {$cnp}</p>";
        $errors[] = "CNP invalid pentru {$patient->display_name}: {$cnp}";
        continue;
    }
    
    // Parsează CNP-ul pentru a obține informații
    $parser = new Clinica_CNP_Parser();
    $parsed_data = $parser->parse_cnp($cnp);
    
    // Pregătește datele pentru inserare
    $patient_data = array(
        'user_id' => $patient->ID,
        'cnp' => $cnp,
        'cnp_type' => $parsed_data['type'] ?? 'romanian',
        'phone_primary' => $patient->phone_primary ?? '',
        'phone_secondary' => $patient->phone_secondary ?? '',
        'birth_date' => $parsed_data['birth_date'] ?? null,
        'gender' => $parsed_data['gender'] ?? null,
        'age' => $parsed_data['age'] ?? null,
        'address' => '',
        'emergency_contact' => '',
        'blood_type' => '',
        'allergies' => '',
        'medical_history' => '',
        'password_method' => 'cnp',
        'import_source' => 'wordpress_sync',
        'created_by' => get_current_user_id(),
        'created_at' => $patient->user_registered ?: current_time('mysql')
    );
    
    // Inserează în tabela clinica_patients
    $result = $wpdb->insert($clinica_table, $patient_data);
    
    if ($result !== false) {
        echo "<p>✅ Pacient sincronizat cu succes!</p>";
        echo "<ul>";
        echo "<li><strong>Nume:</strong> " . ($patient->first_name ? $patient->first_name . ' ' . $patient->last_name : $patient->display_name) . "</li>";
        echo "<li><strong>CNP:</strong> {$cnp}</li>";
        echo "<li><strong>Email:</strong> {$patient->user_email}</li>";
        echo "<li><strong>Telefon Principal:</strong> " . ($patient->phone_primary ?: 'N/A') . "</li>";
        echo "<li><strong>Telefon Secundar:</strong> " . ($patient->phone_secondary ?: 'N/A') . "</li>";
        echo "<li><strong>Data nașterii:</strong> " . ($parsed_data['birth_date'] ?? 'N/A') . "</li>";
        echo "<li><strong>Sex:</strong> " . ($parsed_data['gender'] ?? 'N/A') . "</li>";
        echo "<li><strong>Vârsta:</strong> " . ($parsed_data['age'] ?? 'N/A') . "</li>";
        echo "</ul>";
        $synced_count++;
    } else {
        echo "<p>❌ Eroare la sincronizare: " . $wpdb->last_error . "</p>";
        $errors[] = "Eroare pentru {$patient->display_name}: " . $wpdb->last_error;
    }
}

// 5. Rezumat final
echo "<hr>";
echo "<h2>📊 Rezumat Sincronizare</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>✅ Pacienți sincronizați cu succes:</strong> {$synced_count}</p>";
echo "<p><strong>❌ Erori:</strong> " . count($errors) . "</p>";

if (!empty($errors)) {
    echo "<h3>Erori întâlnite:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// 6. Verificare finală
$final_count = $wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");
echo "<h2>🎯 Verificare Finală</h2>";
echo "<p><strong>Total pacienți în tabela clinica:</strong> {$final_count}</p>";

if ($final_count > 0) {
    echo "<p>✅ Sincronizarea a fost finalizată cu succes!</p>";
    echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "'>Vezi lista pacienților în admin</a></p>";
} else {
    echo "<p>❌ Tabela clinica_patients este încă goală!</p>";
}

echo "<hr>";
echo "<p><em>Script rulat la: " . current_time('mysql') . "</em></p>";
?> 