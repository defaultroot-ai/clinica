<?php
/**
 * Script pentru testarea salvării reale a working_hours
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testarea salvării reale a working_hours...\n\n";

// 1. Curăță valorile existente
echo "1️⃣ Curățarea valorilor existente...\n";
$settings = Clinica_Settings::get_instance();

$empty_working_hours = array(
    'monday' => array('start' => '', 'end' => '', 'active' => false),
    'tuesday' => array('start' => '', 'end' => '', 'active' => false),
    'wednesday' => array('start' => '', 'end' => '', 'active' => false),
    'thursday' => array('start' => '', 'end' => '', 'active' => false),
    'friday' => array('start' => '', 'end' => '', 'active' => false),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

$result = $settings->set('working_hours', $empty_working_hours);
if ($result) {
    echo "✅ Valorile au fost curățate\n";
} else {
    echo "❌ Eroare la curățarea valorilor\n";
}

// 2. Testează salvarea cu ore reale
echo "\n2️⃣ Testarea salvării cu ore reale...\n";

$test_working_hours = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '09:00', 'end' => '18:00', 'active' => true),
    'wednesday' => array('start' => '', 'end' => '', 'active' => false),
    'thursday' => array('start' => '10:00', 'end' => '16:00', 'active' => true),
    'friday' => array('start' => '', 'end' => '', 'active' => false),
    'saturday' => array('start' => '09:00', 'end' => '14:00', 'active' => true),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

$result = $settings->set('working_hours', $test_working_hours);
if ($result) {
    echo "✅ Salvarea reușită!\n";
} else {
    echo "❌ Eroare la salvare!\n";
}

// 3. Verifică salvarea
echo "\n3️⃣ Verificarea salvării...\n";
$saved_working_hours = $settings->get('working_hours');

if (is_array($saved_working_hours)) {
    echo "✅ Datele salvate:\n";
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

// 4. Testează încărcarea pentru afișare
echo "\n4️⃣ Testarea încărcării pentru afișare...\n";
$schedule_settings = $settings->get_group('schedule');
$display_working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();

if (is_array($display_working_hours)) {
    echo "✅ Datele pentru afișare:\n";
    foreach ($display_working_hours as $day => $data) {
        echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
        
        // Simulează generarea HTML-ului pentru input
        $start_value = esc_attr(!empty($data['start']) ? $data['start'] : '');
        $end_value = esc_attr(!empty($data['end']) ? $data['end'] : '');
        echo "    * Input start: value='$start_value'\n";
        echo "    * Input end: value='$end_value'\n";
    }
} else {
    echo "❌ Datele pentru afișare nu s-au încărcat corect!\n";
}

// 5. Testează calculul duratei în contextul real
echo "\n5️⃣ Testarea calculului duratei în contextul real...\n";

foreach ($display_working_hours as $day => $day_hours) {
    $start_time = !empty($day_hours['start']) ? strtotime($day_hours['start']) : 0;
    $end_time = !empty($day_hours['end']) ? strtotime($day_hours['end']) : 0;
    $duration = ($day_hours['active'] && !empty($day_hours['start']) && !empty($day_hours['end']) && $end_time > $start_time) ? round(($end_time - $start_time) / 3600, 1) . 'h' : '-';
    
    echo "  - $day: $duration (start: {$day_hours['start']}, end: {$day_hours['end']}, active: " . ($day_hours['active'] ? 'true' : 'false') . ")\n";
}

echo "\n🎯 Testul complet!\n";
?> 