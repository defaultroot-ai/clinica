<?php
/**
 * Test pentru verificarea încărcării setărilor
 * 
 * Acest test verifică dacă setările se încarcă corect din baza de date
 */

// Simulează WordPress - corectează calea
define('ABSPATH', dirname(__FILE__) . '/../../../');
require_once ABSPATH . 'wp-config.php';

// Include plugin files
require_once ABSPATH . 'wp-content/plugins/clinica/includes/class-clinica-settings.php';
require_once ABSPATH . 'wp-content/plugins/clinica/includes/class-clinica-patient-permissions.php';

// Verifică dacă clasele există
if (!class_exists('Clinica_Settings')) {
    echo "<h2>Eroare: Clinica_Settings nu există</h2>";
    exit;
}

if (!class_exists('Clinica_Patient_Permissions')) {
    echo "<h2>Eroare: Clinica_Patient_Permissions nu există</h2>";
    exit;
}

// Inițializează setările
$settings = Clinica_Settings::get_instance();

echo "<h2>Test Încărcare Setări</h2>";

// Testează încărcarea setărilor
echo "<h3>1. Test Setări Grupuri</h3>";
$groups = array('clinic', 'schedule', 'email', 'appointments', 'notifications', 'security', 'performance');

foreach ($groups as $group) {
    echo "<h4>Grup: $group</h4>";
    $group_settings = $settings->get_group($group);
    echo "<pre>";
    print_r($group_settings);
    echo "</pre>";
}

// Testează specific working_hours
echo "<h3>2. Test Working Hours</h3>";
$schedule_settings = $settings->get_group('schedule');
echo "<h4>Schedule Settings:</h4>";
echo "<pre>";
print_r($schedule_settings);
echo "</pre>";

if (isset($schedule_settings['working_hours'])) {
    echo "<h4>Working Hours Value:</h4>";
    $working_hours = $schedule_settings['working_hours']['value'];
    echo "<pre>";
    print_r($working_hours);
    echo "</pre>";
    
    // Testează structura
    if (is_array($working_hours)) {
        echo "<h4>Structura Working Hours:</h4>";
        foreach ($working_hours as $day => $hours) {
            echo "<p><strong>$day:</strong> ";
            if (is_array($hours)) {
                echo "start: '" . (isset($hours['start']) ? $hours['start'] : 'NULL') . "', ";
                echo "end: '" . (isset($hours['end']) ? $hours['end'] : 'NULL') . "', ";
                echo "active: " . (isset($hours['active']) ? ($hours['active'] ? 'true' : 'false') : 'NULL');
            } else {
                echo "NU ESTE ARRAY: " . gettype($hours);
            }
            echo "</p>";
        }
    } else {
        echo "<p><strong>EROARE:</strong> working_hours nu este array, este: " . gettype($working_hours) . "</p>";
    }
} else {
    echo "<p><strong>EROARE:</strong> working_hours nu există în schedule_settings</p>";
}

// Testează setarea unei valori
echo "<h3>3. Test Setare Working Hours</h3>";
$test_working_hours = array(
    'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
    'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
    'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
    'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

echo "<h4>Încerc să setez working_hours cu:</h4>";
echo "<pre>";
print_r($test_working_hours);
echo "</pre>";

$result = $settings->set('working_hours', $test_working_hours);
echo "<p><strong>Rezultat setare:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";

// Verifică din nou după setare
$updated_working_hours = $settings->get('working_hours');
echo "<h4>Working Hours după setare:</h4>";
echo "<pre>";
print_r($updated_working_hours);
echo "</pre>";

// Testează dacă se salvează în baza de date
echo "<h3>4. Test Salvare în Baza de Date</h3>";
$settings->save();

// Verifică din nou după salvare
$final_working_hours = $settings->get('working_hours');
echo "<h4>Working Hours după salvare:</h4>";
echo "<pre>";
print_r($final_working_hours);
echo "</pre>";

echo "<h3>5. Test Final</h3>";
echo "<p>Dacă toate testele de mai sus funcționează, problema este în afișare, nu în încărcare.</p>";
echo "<p>Verifică:</p>";
echo "<ul>";
echo "<li>Dacă working_hours se încarcă corect din baza de date</li>";
echo "<li>Dacă structura array-ului este corectă</li>";
echo "<li>Dacă valorile pentru start, end și active sunt prezente</li>";
echo "<li>Dacă PHP-ul afișează corect valorile în HTML</li>";
echo "<li>Dacă JavaScript-ul funcționează pentru editare</li>";
echo "</ul>";

echo "<h3>6. Link-uri de Test</h3>";
echo "<p><a href='test-working-hours-display.php' target='_blank'>Test Afișare Working Hours</a></p>";
echo "<p><a href='test-hidden-inputs-fix.php' target='_blank'>Test Hidden Inputs Fix</a></p>";
?> 