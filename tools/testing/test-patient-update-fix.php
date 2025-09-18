<?php
/**
 * Test script pentru verificarea fix-ului de actualizare pacient
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Actualizare Pacient - Fix AJAX</h1>";

// Simulează datele AJAX
$_POST = array(
    'action' => 'clinica_update_patient',
    'nonce' => wp_create_nonce('clinica_nonce'),
    'patient_id' => 1, // ID-ul unui pacient existent
    'first_name' => 'Test',
    'last_name' => 'Pacient',
    'email' => 'test@example.com',
    'phone_primary' => '0722123456',
    'phone_secondary' => '0211234567',
    'birth_date' => '1990-01-01',
    'gender' => 'male',
    'password_method' => 'cnp'
);

echo "<h2>Datele simulate:</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Verifică dacă pacientul există
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';
$patient = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $_POST['patient_id']
));

if (!$patient) {
    echo "<p style='color: red;'>Pacientul cu ID {$_POST['patient_id']} nu există în baza de date.</p>";
    echo "<p>Pacienți disponibili:</p>";
    $patients = $wpdb->get_results("SELECT user_id, cnp FROM $table_name LIMIT 5");
    echo "<ul>";
    foreach ($patients as $p) {
        echo "<li>ID: {$p->user_id}, CNP: {$p->cnp}</li>";
    }
    echo "</ul>";
    exit;
}

echo "<h2>Pacient găsit:</h2>";
echo "<pre>" . print_r($patient, true) . "</pre>";

// Verifică datele utilizatorului WordPress
$user = get_user_by('ID', $_POST['patient_id']);
echo "<h2>Date utilizator WordPress:</h2>";
echo "<pre>" . print_r($user, true) . "</pre>";

// Testează actualizarea
echo "<h2>Testare actualizare...</h2>";

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

echo "<h3>Răspuns AJAX:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verifică rezultatul
echo "<h2>Verificare rezultat:</h2>";

// Verifică datele utilizatorului după actualizare
$updated_user = get_user_by('ID', $_POST['patient_id']);
echo "<h3>Date utilizator după actualizare:</h3>";
echo "<pre>" . print_r($updated_user, true) . "</pre>";

// Verifică datele pacientului după actualizare
$updated_patient = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $_POST['patient_id']
));
echo "<h3>Date pacient după actualizare:</h3>";
echo "<pre>" . print_r($updated_patient, true) . "</pre>";

// Verifică user meta
echo "<h3>User meta după actualizare:</h3>";
$phone_primary = get_user_meta($_POST['patient_id'], 'phone_primary', true);
$phone_secondary = get_user_meta($_POST['patient_id'], 'phone_secondary', true);
echo "<p>Phone Primary: " . ($phone_primary ?: 'Nu setat') . "</p>";
echo "<p>Phone Secondary: " . ($phone_secondary ?: 'Nu setat') . "</p>";

echo "<h2>Test complet!</h2>";
echo "<p>Dacă nu apar erori, fix-ul funcționează corect.</p>";
?> 