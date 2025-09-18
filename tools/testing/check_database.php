<?php
require_once('../../../wp-config.php');
global $wpdb;

echo "=== VERIFICARE BAZA DE DATE ===" . PHP_EOL;

// Verifică timeslots pentru doctor 2626 și service 3
$timeslots = $wpdb->get_results($wpdb->prepare(
    'SELECT * FROM ' . $wpdb->prefix . 'clinica_doctor_timeslots WHERE doctor_id = %d AND service_id = %d',
    2626, 3
));

echo "Timeslots pentru doctor 2626, service 3:" . PHP_EOL;
foreach ($timeslots as $slot) {
    echo "Day: " . $slot->day_of_week . ", Start: " . $slot->start_time . ", End: " . $slot->end_time . PHP_EOL;
}

// Verifică toate timeslots pentru doctor 2626
$all_timeslots = $wpdb->get_results($wpdb->prepare(
    'SELECT * FROM ' . $wpdb->prefix . 'clinica_doctor_timeslots WHERE doctor_id = %d',
    2626
));

echo PHP_EOL . "Toate timeslots pentru doctor 2626:" . PHP_EOL;
foreach ($all_timeslots as $slot) {
    echo "Service: " . $slot->service_id . ", Day: " . $slot->day_of_week . ", Start: " . $slot->start_time . ", End: " . $slot->end_time . PHP_EOL;
}

// Verifică setările
$settings = get_option('clinica_settings', array());
echo PHP_EOL . "Setări clinica:" . PHP_EOL;
echo "appointment_advance_days: " . (isset($settings['appointment_advance_days']) ? $settings['appointment_advance_days'] : 'nu este setat') . PHP_EOL;
echo "working_hours: " . (isset($settings['working_hours']) ? 'setat' : 'nu este setat') . PHP_EOL;

// Verifică programul de lucru global
$working_hours = isset($settings['working_hours']) ? $settings['working_hours'] : array();
if (is_string($working_hours)) {
    $working_hours = json_decode($working_hours, true);
}
echo PHP_EOL . "Program de lucru global:" . PHP_EOL;
foreach ($working_hours as $day => $hours) {
    echo $day . ": " . ($hours['active'] ? $hours['start'] . '-' . $hours['end'] : 'inactiv') . PHP_EOL;
}
?>