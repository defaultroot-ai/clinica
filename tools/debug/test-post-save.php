<?php
/**
 * Script pentru testarea salvării prin POST simulat
 */

require_once '../../../../../wp-load.php';

echo "🧪 Testarea salvării prin POST simulat...\n\n";

// Simulează datele POST pentru working_hours
$_POST['working_hours'] = array(
    'monday' => array(
        'start' => '08:30',
        'end' => '17:30',
        'active' => '1'
    ),
    'tuesday' => array(
        'start' => '08:30',
        'end' => '17:30',
        'active' => '1'
    ),
    'wednesday' => array(
        'start' => '08:30',
        'end' => '17:30',
        'active' => '1'
    ),
    'thursday' => array(
        'start' => '08:30',
        'end' => '17:30',
        'active' => '1'
    ),
    'friday' => array(
        'start' => '08:30',
        'end' => '17:30',
        'active' => '1'
    ),
    'saturday' => array(
        'start' => '09:00',
        'end' => '14:00',
        'active' => '1'
    ),
    'sunday' => array(
        'start' => '',
        'end' => '',
        'active' => '0'
    )
);

// Simulează alte date POST necesare
$_POST['submit'] = 'Salvează setările';
$_POST['clinica_settings_nonce'] = wp_create_nonce('clinica_settings');

echo "POST data simulat:\n";
echo "working_hours: " . print_r($_POST['working_hours'], true) . "\n\n";

// Procesează salvarea ca în settings.php
$settings = Clinica_Settings::get_instance();

echo "=== PROCESARE SALVARE ===\n";

$groups = array('clinic', 'schedule', 'email', 'appointments', 'notifications', 'security', 'performance');

foreach ($groups as $group) {
    $group_settings = $settings->get_group($group);
    foreach ($group_settings as $key => $setting_info) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            
            echo "Procesez: $key\n";
            echo "Valoare brută: " . print_r($value, true) . "\n";
            
            // Sanitizează valoarea în funcție de tip
            switch ($setting_info['type']) {
                case 'boolean':
                    $value = (bool) $value;
                    break;
                case 'number':
                    $value = (int) $value;
                    break;
                case 'json':
                    // Pentru programul de funcționare
                    if ($key === 'working_hours') {
                        $working_hours = array();
                        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                        
                        echo "Procesez working_hours pentru zilele: " . implode(', ', $days) . "\n";
                        
                        foreach ($days as $day) {
                            $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
                            $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
                            $is_active = isset($_POST['working_hours'][$day]['active']);
                            
                            echo "Ziua: $day - Start: '$start_time', End: '$end_time', Active: " . ($is_active ? 'true' : 'false') . "\n";
                            
                            $working_hours[$day] = array(
                                'start' => $start_time,
                                'end' => $end_time,
                                'active' => $is_active
                            );
                        }
                        
                        echo "Array final working_hours: " . print_r($working_hours, true) . "\n";
                        $value = $working_hours;
                    }
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            
            echo "Valoare procesată: " . print_r($value, true) . "\n";
            
            $result = $settings->set($key, $value);
            echo "Rezultat salvare: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
            
            // Verifică ce s-a salvat
            $saved_value = $settings->get($key);
            echo "Valoare salvată: " . print_r($saved_value, true) . "\n\n";
        }
    }
}

echo "=== VERIFICARE FINALĂ ===\n";
$final_working_hours = $settings->get('working_hours');
echo "working_hours final din baza de date: " . print_r($final_working_hours, true) . "\n";

$schedule_settings = $settings->get_group('schedule');
echo "Schedule settings după salvare: " . print_r($schedule_settings['working_hours'], true) . "\n";

echo "\n=== TEST COMPLETAT ===\n";
?> 