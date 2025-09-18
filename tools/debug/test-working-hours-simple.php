<?php
/**
 * Test simplu pentru working_hours
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

$settings = Clinica_Settings::get_instance();

echo "<h1>🧪 Test Simplu Working Hours</h1>";

// Test 1: Salvare directă
echo "<h2>Test 1: Salvare directă</h2>";

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
    echo "<p style='color: green;'>✅ Salvarea directă a funcționat!</p>";
} else {
    echo "<p style='color: red;'>❌ Salvarea directă a eșuat!</p>";
}

// Test 2: Citire
echo "<h2>Test 2: Citire</h2>";
$saved_data = $settings->get('working_hours');

if (is_array($saved_data)) {
    echo "<p style='color: green;'>✅ Citirea a funcționat!</p>";
    echo "<pre>" . print_r($saved_data, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Citirea a eșuat!</p>";
}

// Test 3: Simulare POST
echo "<h2>Test 3: Simulare POST</h2>";

$_POST['working_hours'] = array(
    'monday' => array('start' => '09:00', 'end' => '18:00', 'active' => '1'),
    'tuesday' => array('start' => '08:00', 'end' => '17:00', 'active' => '1'),
    'wednesday' => array('start' => '10:00', 'end' => '19:00', 'active' => '1'),
    'thursday' => array('start' => '08:30', 'end' => '16:30', 'active' => '1'),
    'friday' => array('start' => '08:00', 'end' => '15:00', 'active' => '1'),
    'saturday' => array('start' => '', 'end' => '', 'active' => '0'),
    'sunday' => array('start' => '', 'end' => '', 'active' => '0')
);

echo "<p>Datele POST simulate:</p>";
echo "<pre>" . print_r($_POST['working_hours'], true) . "</pre>";

// Procesează datele POST
$working_hours = array();
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

foreach ($days as $day) {
    if (isset($_POST['working_hours'][$day])) {
        $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
        $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
        $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
        
        $working_hours[$day] = array(
            'start' => $start_time,
            'end' => $end_time,
            'active' => $is_active
        );
    } else {
        $working_hours[$day] = array(
            'start' => '',
            'end' => '',
            'active' => false
        );
    }
}

echo "<p>Datele procesate:</p>";
echo "<pre>" . print_r($working_hours, true) . "</pre>";

// Salvează
$result = $settings->set('working_hours', $working_hours);

if ($result) {
    echo "<p style='color: green;'>✅ Salvarea din POST a funcționat!</p>";
} else {
    echo "<p style='color: red;'>❌ Salvarea din POST a eșuat!</p>";
}

// Verifică din nou
$final_data = $settings->get('working_hours');
echo "<p>Datele finale:</p>";
echo "<pre>" . print_r($final_data, true) . "</pre>";

echo "<h2>🎯 Rezultat</h2>";
echo "<p>Dacă toate testele au trecut cu ✅, atunci problema este în JavaScript sau în formular.</p>";
echo "<p>Dacă există ❌, atunci problema este în procesarea PHP.</p>";
?> 