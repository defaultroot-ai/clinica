<?php
/**
 * Test script complet pentru verificarea fix-ului de actualizare pacient
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Complet Actualizare Pacient - Fix AJAX</h1>";

// Verifică structura tabelei
echo "<h2>1. Verificare structură tabelă clinica_patients</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<h3>Coloanele tabelei:</h3>";
echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column->Field}</strong> - {$column->Type}";
    if ($column->Null === 'NO') echo " (NOT NULL)";
    if ($column->Key === 'PRI') echo " (PRIMARY KEY)";
    echo "</li>";
}
echo "</ul>";

// Verifică pacienții existenți
echo "<h2>2. Pacienți existenți</h2>";
$patients = $wpdb->get_results("SELECT user_id, cnp, first_name, last_name FROM $table_name LIMIT 5");
if ($patients) {
    echo "<ul>";
    foreach ($patients as $p) {
        $user = get_user_by('ID', $p->user_id);
        echo "<li>ID: {$p->user_id}, CNP: {$p->cnp}, Nume: " . ($user ? $user->display_name : 'N/A') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Nu există pacienți în baza de date.</p>";
    exit;
}

// Alege primul pacient pentru test
$test_patient = $patients[0];
echo "<h2>3. Test cu pacientul ID: {$test_patient->user_id}</h2>";

// Simulează datele AJAX cu toate câmpurile
$_POST = array(
    'action' => 'clinica_update_patient',
    'nonce' => wp_create_nonce('clinica_nonce'),
    'patient_id' => $test_patient->user_id,
    'first_name' => 'Test',
    'last_name' => 'Pacient',
    'email' => 'test@example.com',
    'phone_primary' => '0722123456',
    'phone_secondary' => '0211234567',
    'birth_date' => '1990-01-01',
    'gender' => 'male',
    'password_method' => 'cnp'
);

echo "<h3>Datele simulate:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Verifică datele înainte de actualizare
echo "<h3>Date înainte de actualizare:</h3>";
$before_user = get_user_by('ID', $test_patient->user_id);
$before_patient = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $test_patient->user_id
));

echo "<h4>User WordPress:</h4>";
echo "<pre>" . print_r($before_user, true) . "</pre>";

echo "<h4>Pacient clinica_patients:</h4>";
echo "<pre>" . print_r($before_patient, true) . "</pre>";

// Testează actualizarea
echo "<h3>Testare actualizare...</h3>";

// Simulează AJAX call
ob_start();
try {
    // Apelează handler-ul AJAX
    $plugin = Clinica_Plugin::get_instance();
    $plugin->ajax_update_patient();
} catch (Exception $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
$output = ob_get_clean();

echo "<h4>Răspuns AJAX:</h4>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verifică rezultatul
echo "<h3>Verificare rezultat:</h3>";

// Verifică datele utilizatorului după actualizare
$after_user = get_user_by('ID', $test_patient->user_id);
echo "<h4>User WordPress după actualizare:</h4>";
echo "<pre>" . print_r($after_user, true) . "</pre>";

// Verifică datele pacientului după actualizare
$after_patient = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $test_patient->user_id
));
echo "<h4>Pacient clinica_patients după actualizare:</h4>";
echo "<pre>" . print_r($after_patient, true) . "</pre>";

// Verifică user meta
echo "<h4>User meta după actualizare:</h4>";
$phone_primary = get_user_meta($test_patient->user_id, 'phone_primary', true);
$phone_secondary = get_user_meta($test_patient->user_id, 'phone_secondary', true);
echo "<p>Phone Primary: " . ($phone_primary ?: 'Nu setat') . "</p>";
echo "<p>Phone Secondary: " . ($phone_secondary ?: 'Nu setat') . "</p>";

// Verifică modificările
echo "<h3>Comparație modificări:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Câmp</th><th>Înainte</th><th>După</th><th>Status</th></tr>";

$fields_to_check = array(
    'first_name' => array($before_user->first_name, $after_user->first_name),
    'last_name' => array($before_user->last_name, $after_user->last_name),
    'user_email' => array($before_user->user_email, $after_user->user_email),
    'phone_primary' => array($before_patient->phone_primary, $after_patient->phone_primary),
    'phone_secondary' => array($before_patient->phone_secondary, $after_patient->phone_secondary),
    'birth_date' => array($before_patient->birth_date, $after_patient->birth_date),
    'gender' => array($before_patient->gender, $after_patient->gender),
    'password_method' => array($before_patient->password_method, $after_patient->password_method)
);

foreach ($fields_to_check as $field => $values) {
    $before = $values[0] ?: 'NULL';
    $after = $values[1] ?: 'NULL';
    $status = ($before === $after) ? 'Neschimbat' : 'Actualizat';
    $color = ($before === $after) ? 'orange' : 'green';
    
    echo "<tr>";
    echo "<td><strong>$field</strong></td>";
    echo "<td>$before</td>";
    echo "<td>$after</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>4. Test complet!</h2>";
echo "<p>Dacă nu apar erori și modificările sunt vizibile în tabel, fix-ul funcționează corect.</p>";

// Testează și handler-ul de obținere date
echo "<h2>5. Test handler obținere date pacient</h2>";

$_POST = array(
    'action' => 'clinica_get_patient_data',
    'nonce' => wp_create_nonce('clinica_nonce'),
    'patient_id' => $test_patient->user_id
);

ob_start();
try {
    $plugin->ajax_get_patient_data();
} catch (Exception $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
$get_output = ob_get_clean();

echo "<h3>Răspuns AJAX get_patient_data:</h3>";
echo "<pre>" . htmlspecialchars($get_output) . "</pre>";
?> 