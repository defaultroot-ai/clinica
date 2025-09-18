<?php
/**
 * Test pentru submit-ul formularului
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

$settings = Clinica_Settings::get_instance();

echo "<h1>🧪 Test Submit Formular</h1>";

// Simulează datele POST
$_POST['clinica_settings_nonce'] = wp_create_nonce('clinica_settings');
$_POST['submit'] = '1';
$_POST['working_hours'] = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => '1'),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => '1'),
    'wednesday' => array('start' => '09:00', 'end' => '18:00', 'active' => '1'),
    'thursday' => array('start' => '08:00', 'end' => '15:00', 'active' => '1'),
    'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => '1'),
    'saturday' => array('start' => '', 'end' => '', 'active' => '0'),
    'sunday' => array('start' => '', 'end' => '', 'active' => '0')
);

echo "<h2>Datele POST simulate:</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Procesează datele
if (isset($_POST['submit']) && wp_verify_nonce($_POST['clinica_settings_nonce'], 'clinica_settings')) {
    echo "<h2>Procesarea datelor:</h2>";
    
    $groups = array('clinic', 'schedule', 'email', 'appointments', 'notifications', 'security', 'performance');
    
    foreach ($groups as $group) {
        $group_settings = $settings->get_group($group);
        foreach ($group_settings as $key => $setting_info) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                echo "<p>Procesez: $key</p>";
                
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
                            
                            echo "<p>Procesez working_hours pentru zilele: " . implode(', ', $days) . "</p>";
                            
                            foreach ($days as $day) {
                                // Verifică dacă există datele pentru această zi
                                if (isset($_POST['working_hours'][$day])) {
                                    $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
                                    $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
                                    $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
                                    
                                    echo "<p>Zi: $day - Start: '$start_time', End: '$end_time', Active: " . ($is_active ? 'true' : 'false') . "</p>";
                                    
                                    $working_hours[$day] = array(
                                        'start' => $start_time,
                                        'end' => $end_time,
                                        'active' => $is_active
                                    );
                                } else {
                                    // Dacă nu există date pentru această zi, setează valori implicite
                                    $working_hours[$day] = array(
                                        'start' => '',
                                        'end' => '',
                                        'active' => false
                                    );
                                    echo "<p>Zi: $day - Nu s-au găsit date, folosesc valorile implicite</p>";
                                }
                            }
                            
                            echo "<p>Array final working_hours:</p>";
                            echo "<pre>" . print_r($working_hours, true) . "</pre>";
                            $value = $working_hours;
                        }
                        break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                
                echo "<p>Înainte de salvare pentru $key:</p>";
                echo "<pre>" . print_r($value, true) . "</pre>";
                
                $result = $settings->set($key, $value);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Salvarea pentru $key a funcționat!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Salvarea pentru $key a eșuat!</p>";
                }
                
                // Verifică ce s-a salvat
                $saved_value = $settings->get($key);
                echo "<p>Valoarea salvată pentru $key:</p>";
                echo "<pre>" . print_r($saved_value, true) . "</pre>";
            }
        }
    }
    
    echo "<h2>✅ Procesarea completă!</h2>";
} else {
    echo "<p style='color: red;'>❌ Eroare la verificarea nonce-ului!</p>";
}

// Verifică datele finale
echo "<h2>Datele finale salvate:</h2>";
$final_working_hours = $settings->get('working_hours');
echo "<pre>" . print_r($final_working_hours, true) . "</pre>";

echo "<h2>🎯 Rezultat</h2>";
echo "<p>Dacă toate salvările au trecut cu ✅, atunci formularul funcționează corect.</p>";
echo "<p>Dacă există ❌, atunci există probleme în procesarea datelor.</p>";
?> 