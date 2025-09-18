<?php
/**
 * Test pentru verificarea debug-ului din pagina de setări
 */

echo "<h1>Test Debug Setări</h1>";

// Simulează încărcarea setărilor
$schedule_settings = array(
    'working_hours' => array(
        'value' => array(
            'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
            'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
            'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
            'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
            'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
            'saturday' => array('start' => '', 'end' => '', 'active' => false),
            'sunday' => array('start' => '', 'end' => '', 'active' => false)
        )
    )
);

$working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();

// DEBUG - Log working_hours la încărcarea paginii
error_log('=== DEBUG ÎNCĂRCARE PAGINĂ ===');
error_log('schedule_settings: ' . print_r($schedule_settings, true));
error_log('working_hours loaded: ' . print_r($working_hours, true));

// DEBUG - Verifică structura working_hours
if (is_array($working_hours)) {
    foreach ($working_hours as $day => $hours) {
        error_log("Day $day: " . print_r($hours, true));
    }
} else {
    error_log("EROARE: working_hours nu este array, este: " . gettype($working_hours));
}

// DEBUG - Verifică dacă working_hours este gol
if (empty($working_hours)) {
    error_log("EROARE: working_hours este gol!");
    // Setează date de test dacă este gol
    $working_hours = array(
        'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
        'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
        'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
        'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
        'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
        'saturday' => array('start' => '', 'end' => '', 'active' => false),
        'sunday' => array('start' => '', 'end' => '', 'active' => false)
    );
    error_log("Setat working_hours cu date de test: " . print_r($working_hours, true));
}

echo "<h2>Date încărcate:</h2>";
echo "<pre>";
print_r($working_hours);
echo "</pre>";

echo "<h2>Test afișare HTML:</h2>";
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');

foreach ($days as $day_key) {
    $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
    
    echo "<h3>Ziua: $day_key</h3>";
    echo "<p>Start: " . ($day_hours['start'] ?: '--:--') . "</p>";
    echo "<p>End: " . ($day_hours['end'] ?: '--:--') . "</p>";
    echo "<p>Active: " . ($day_hours['active'] ? 'Da' : 'Nu') . "</p>";
    
    // Simulează log-urile din settings.php
    $display_value = $day_hours['start'] ?: '--:--';
    error_log("Display for $day_key start: '$display_value' (original: '" . $day_hours['start'] . "')");
    
    $input_value = !empty($day_hours['start']) ? $day_hours['start'] : '';
    error_log("Input for $day_key start: '$input_value' (original: '" . $day_hours['start'] . "')");
}

echo "<h2>Mesaj de succes!</h2>";
echo "<p>Dacă vezi acest mesaj, înseamnă că testul funcționează și log-urile sunt generate.</p>";
echo "<p>Verifică log-urile PHP pentru a vedea mesajele de debug.</p>";
?> 