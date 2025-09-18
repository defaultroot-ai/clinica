<?php
/**
 * Test pentru formularul de setări
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

$settings = Clinica_Settings::get_instance();

echo "<h1>🧪 Test Formular Setări</h1>";

// Test 1: Verifică dacă working_hours există
echo "<h2>Test 1: Verificare working_hours</h2>";
$working_hours = $settings->get('working_hours');

if (is_array($working_hours)) {
    echo "<p style='color: green;'>✅ working_hours există și este array</p>";
    echo "<pre>" . print_r($working_hours, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ working_hours nu există sau nu este array</p>";
    echo "<p>Tip: " . gettype($working_hours) . "</p>";
}

// Test 2: Simulează salvarea
echo "<h2>Test 2: Simulare salvare</h2>";

$test_data = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
    'wednesday' => array('start' => '09:00', 'end' => '18:00', 'active' => true),
    'thursday' => array('start' => '08:00', 'end' => '15:00', 'active' => true),
    'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

$result = $settings->set('working_hours', $test_data);

if ($result) {
    echo "<p style='color: green;'>✅ Salvarea a funcționat!</p>";
} else {
    echo "<p style='color: red;'>❌ Salvarea a eșuat!</p>";
}

// Test 3: Verifică din nou
echo "<h2>Test 3: Verificare după salvare</h2>";
$saved_data = $settings->get('working_hours');

if (is_array($saved_data)) {
    echo "<p style='color: green;'>✅ Datele au fost salvate corect!</p>";
    echo "<pre>" . print_r($saved_data, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Datele nu au fost salvate corect!</p>";
}

echo "<h2>🎯 Instrucțiuni pentru testare</h2>";
echo "<p>1. Mergi la pagina de setări în admin</p>";
echo "<p>2. Click pe tab-ul 'Program'</p>";
echo "<p>3. Activează o zi (click pe status)</p>";
echo "<p>4. Click pe ora de început pentru a edita</p>";
echo "<p>5. Introdu o oră (ex: 08:00)</p>";
echo "<p>6. Apasă Enter sau click în afară</p>";
echo "<p>7. Verifică dacă ora rămâne salvată</p>";
echo "<p>8. Salvează formularul</p>";
echo "<p>9. Reîncarcă pagina pentru a verifica dacă datele au fost salvate</p>";
?> 