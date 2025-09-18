<?php
/**
 * Test rapid pentru programul de lucru
 */

// Simulează WordPress - corectează calea
define('ABSPATH', dirname(__FILE__) . '/../../../');
require_once ABSPATH . 'wp-config.php';

// Include plugin files
require_once ABSPATH . 'wp-content/plugins/clinica/includes/class-clinica-settings.php';

// Inițializează setările
$settings = Clinica_Settings::get_instance();

echo "<h1>Test Rapid Program de Lucru</h1>";

// 1. Verifică dacă setările se încarcă
echo "<h2>1. Verificare Setări</h2>";
$schedule_settings = $settings->get_group('schedule');
echo "<pre>";
print_r($schedule_settings);
echo "</pre>";

// 2. Verifică working_hours
echo "<h2>2. Verificare Working Hours</h2>";
if (isset($schedule_settings['working_hours'])) {
    $working_hours = $schedule_settings['working_hours']['value'];
    echo "<pre>";
    print_r($working_hours);
    echo "</pre>";
} else {
    echo "<p><strong>EROARE:</strong> working_hours nu există!</p>";
}

// 3. Setează niște date de test
echo "<h2>3. Setare Date de Test</h2>";
$test_data = array(
    'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
    'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
    'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
    'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

$result = $settings->set('working_hours', $test_data);
echo "<p>Setare rezultat: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";

// 4. Salvează în baza de date
echo "<h2>4. Salvare în Baza de Date</h2>";
$settings->save();
echo "<p>Salvare completă!</p>";

// 5. Verifică din nou
echo "<h2>5. Verificare După Salvare</h2>";
$updated_settings = $settings->get_group('schedule');
$updated_working_hours = $updated_settings['working_hours']['value'];
echo "<pre>";
print_r($updated_working_hours);
echo "</pre>";

// 6. Testează afișarea
echo "<h2>6. Test Afișare</h2>";
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');

foreach ($days as $day_key) {
    $day_hours = isset($updated_working_hours[$day_key]) ? $updated_working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
    
    echo "<h3>$day_key</h3>";
    echo "<p>Active: " . ($day_hours['active'] ? 'true' : 'false') . "</p>";
    echo "<p>Start: '" . $day_hours['start'] . "'</p>";
    echo "<p>End: '" . $day_hours['end'] . "'</p>";
    echo "<p>Display start: '" . ($day_hours['start'] ?: '--:--') . "'</p>";
    echo "<p>Display end: '" . ($day_hours['end'] ?: '--:--') . "'</p>";
}

echo "<h2>7. Link-uri de Test</h2>";
echo "<p><a href='test-working-hours-display.php' target='_blank'>Test Afișare</a></p>";
echo "<p><a href='test-settings-loading.php' target='_blank'>Test Încărcare Setări</a></p>";
echo "<p><a href='test-hidden-inputs-fix.php' target='_blank'>Test Hidden Inputs</a></p>";

echo "<h2>8. Instrucțiuni</h2>";
echo "<ol>";
echo "<li>Verifică dacă toate testele de mai sus funcționează</li>";
echo "<li>Dacă working_hours se încarcă corect, problema este în afișare</li>";
echo "<li>Dacă working_hours nu se încarcă, problema este în baza de date</li>";
echo "<li>Deschide link-urile de test pentru a verifica funcționalitatea</li>";
echo "<li>Verifică consola browser-ului pentru erori JavaScript</li>";
echo "</ol>";
?> 