<?php
/**
 * Script pentru testarea salvării reale din interfață
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testarea salvării reale din interfață...\n\n";

// Simulează datele POST ca ar veni din interfață
$_POST['working_hours'] = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => '1'),
    'tuesday' => array('start' => '09:00', 'end' => '18:00', 'active' => '1'),
    'wednesday' => array('start' => '', 'end' => '', 'active' => '0'),
    'thursday' => array('start' => '10:00', 'end' => '16:00', 'active' => '1'),
    'friday' => array('start' => '', 'end' => '', 'active' => '0'),
    'saturday' => array('start' => '09:00', 'end' => '14:00', 'active' => '1'),
    'sunday' => array('start' => '', 'end' => '', 'active' => '0')
);

echo "📝 Simulez datele POST din interfață:\n";
foreach ($_POST['working_hours'] as $day => $data) {
    echo "  - $day: start='{$data['start']}', end='{$data['end']}', active='{$data['active']}'\n";
}

// Simulează procesarea din settings.php
$settings = Clinica_Settings::get_instance();

if (isset($_POST['working_hours'])) {
    echo "\n📝 Procesarea datelor POST...\n";
    
    $working_hours = array();
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    
    foreach ($days as $day) {
        $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
        $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
        $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
        
        $working_hours[$day] = array(
            'start' => $start_time,
            'end' => $end_time,
            'active' => $is_active
        );
        
        echo "  - $day: start='$start_time', end='$end_time', active=" . ($is_active ? 'true' : 'false') . "\n";
    }
    
    // Salvează în baza de date
    echo "\n💾 Salvarea în baza de date...\n";
    $result = $settings->set('working_hours', $working_hours);
    
    if ($result) {
        echo "✅ Salvarea reușită!\n";
        
        // Verifică salvarea
        $saved_working_hours = $settings->get('working_hours');
        echo "\n🔍 Verificarea salvării:\n";
        
        if (is_array($saved_working_hours)) {
            foreach ($saved_working_hours as $day => $data) {
                echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
                
                // Testează calculul duratei
                if (!empty($data['start']) && !empty($data['end']) && $data['active']) {
                    $start_time = strtotime($data['start']);
                    $end_time = strtotime($data['end']);
                    if ($end_time > $start_time) {
                        $duration = round(($end_time - $start_time) / 3600, 1);
                        echo "    * Durata calculată: {$duration}h\n";
                    } else {
                        echo "    * Durata: invalidă (sfârșit < început)\n";
                    }
                } else {
                    echo "    * Durata: nu se poate calcula\n";
                }
            }
        } else {
            echo "❌ Datele nu s-au salvat corect!\n";
        }
        
        // Testează încărcarea pentru afișare
        echo "\n📊 Testarea încărcării pentru afișare:\n";
        $schedule_settings = $settings->get_group('schedule');
        $display_working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();
        
        if (is_array($display_working_hours)) {
            foreach ($display_working_hours as $day => $data) {
                echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
                
                // Simulează generarea HTML-ului
                $start_value = esc_attr(!empty($data['start']) ? $data['start'] : '');
                $end_value = esc_attr(!empty($data['end']) ? $data['end'] : '');
                echo "    * HTML start: value='$start_value'\n";
                echo "    * HTML end: value='$end_value'\n";
            }
        }
        
    } else {
        echo "❌ Eroare la salvare!\n";
    }
    
} else {
    echo "❌ Datele POST nu există!\n";
}

echo "\n🎯 Testul complet!\n";
?> 