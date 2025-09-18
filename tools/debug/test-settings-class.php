<?php
/**
 * Script pentru testarea clasei Clinica_Settings
 */

require_once '../../../../../wp-load.php';

echo "🧪 Testarea clasei Clinica_Settings...\n\n";

// Testează instanțierea
try {
    $settings = Clinica_Settings::get_instance();
    echo "✅ Clinica_Settings instanțiat cu succes\n";
} catch (Exception $e) {
    echo "❌ Eroare la instanțierea Clinica_Settings: " . $e->getMessage() . "\n";
    exit;
}

// Testează salvarea working_hours
echo "\n=== TEST SALVARE WORKING_HOURS ===\n";

$test_working_hours = array(
    'monday' => array(
        'start' => '08:00',
        'end' => '18:00',
        'active' => true
    ),
    'tuesday' => array(
        'start' => '08:00',
        'end' => '18:00',
        'active' => true
    ),
    'wednesday' => array(
        'start' => '08:00',
        'end' => '18:00',
        'active' => true
    ),
    'thursday' => array(
        'start' => '08:00',
        'end' => '18:00',
        'active' => true
    ),
    'friday' => array(
        'start' => '08:00',
        'end' => '18:00',
        'active' => true
    ),
    'saturday' => array(
        'start' => '08:00',
        'end' => '14:00',
        'active' => true
    ),
    'sunday' => array(
        'start' => '',
        'end' => '',
        'active' => false
    )
);

echo "Test data: " . print_r($test_working_hours, true) . "\n";

// Salvează
$result = $settings->set('working_hours', $test_working_hours);
echo "Save result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Verifică ce s-a salvat
$saved_working_hours = $settings->get('working_hours');
echo "Retrieved working_hours: " . print_r($saved_working_hours, true) . "\n";

// Testează get_group pentru schedule
echo "\n=== TEST GET_GROUP SCHEDULE ===\n";
$schedule_settings = $settings->get_group('schedule');
echo "Schedule settings: " . print_r($schedule_settings, true) . "\n";

// Testează dacă working_hours există în schedule
if (isset($schedule_settings['working_hours'])) {
    echo "✅ working_hours găsit în schedule settings\n";
    echo "working_hours value: " . print_r($schedule_settings['working_hours']['value'], true) . "\n";
} else {
    echo "❌ working_hours NU există în schedule settings\n";
}

// Testează din nou salvarea pentru a vedea dacă se suprascrie
echo "\n=== TEST RE-SALVARE ===\n";
$new_test_data = array(
    'monday' => array(
        'start' => '09:00',
        'end' => '17:00',
        'active' => true
    ),
    'tuesday' => array(
        'start' => '09:00',
        'end' => '17:00',
        'active' => true
    ),
    'wednesday' => array(
        'start' => '09:00',
        'end' => '17:00',
        'active' => true
    ),
    'thursday' => array(
        'start' => '09:00',
        'end' => '17:00',
        'active' => true
    ),
    'friday' => array(
        'start' => '09:00',
        'end' => '17:00',
        'active' => true
    ),
    'saturday' => array(
        'start' => '09:00',
        'end' => '13:00',
        'active' => true
    ),
    'sunday' => array(
        'start' => '',
        'end' => '',
        'active' => false
    )
);

echo "New test data: " . print_r($new_test_data, true) . "\n";

$result2 = $settings->set('working_hours', $new_test_data);
echo "Re-save result: " . ($result2 ? 'SUCCESS' : 'FAILED') . "\n";

$saved_working_hours2 = $settings->get('working_hours');
echo "Retrieved working_hours after re-save: " . print_r($saved_working_hours2, true) . "\n";

echo "\n=== TEST COMPLETAT ===\n";
?> 