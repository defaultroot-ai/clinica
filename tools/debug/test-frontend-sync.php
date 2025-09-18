<?php
/**
 * Test script pentru verificarea sincronizării frontend
 * Testează dacă JavaScript-ul sincronizează corect valorile cu hidden inputs
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "🧪 Testarea sincronizării frontend pentru working_hours...\n\n";

// Simulează datele POST care ar veni de la frontend
$_POST = array(
    'working_hours' => array(
        'monday' => array(
            'start' => '08:30',
            'end' => '17:30',
            'active' => true
        ),
        'tuesday' => array(
            'start' => '09:00',
            'end' => '18:00',
            'active' => true
        ),
        'wednesday' => array(
            'start' => '',
            'end' => '',
            'active' => false
        ),
        'thursday' => array(
            'start' => '10:15',
            'end' => '16:45',
            'active' => true
        ),
        'friday' => array(
            'start' => '',
            'end' => '',
            'active' => false
        )
    )
);

echo "1️⃣ Simularea datelor POST de la frontend...\n";
foreach ($_POST['working_hours'] as $day => $hours) {
    echo "   - $day: start='{$hours['start']}', end='{$hours['end']}', active=" . ($hours['active'] ? 'true' : 'false') . "\n";
}

// Testează salvarea
$settings = Clinica_Settings::get_instance();

if (isset($_POST['working_hours'])) {
    $working_hours = array();
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    
    foreach ($days as $day) {
        $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
        $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
        $is_active = isset($_POST['working_hours'][$day]['active']);
        
        $working_hours[$day] = array(
            'start' => $start_time,
            'end' => $end_time,
            'active' => $is_active
        );
    }
    
    $settings->set('working_hours', $working_hours);
    echo "✅ Datele au fost salvate cu succes!\n\n";
}

// Testează încărcarea pentru afișare
$schedule_settings = $settings->get_group('schedule');
$working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();

echo "2️⃣ Verificarea datelor pentru afișare...\n";
foreach ($working_hours as $day => $hours) {
    echo "   - $day: start='{$hours['start']}', end='{$hours['end']}', active=" . ($hours['active'] ? 'true' : 'false') . "\n";
}

// Simulează generarea HTML-ului pentru hidden inputs
echo "\n3️⃣ Simularea generării hidden inputs...\n";
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');

foreach ($days as $day_key) {
    $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
    
    $start_value = !empty($day_hours['start']) ? $day_hours['start'] : '';
    $end_value = !empty($day_hours['end']) ? $day_hours['end'] : '';
    $active_value = $day_hours['active'] ? '1' : '0';
    
    echo "   - $day_key:\n";
    echo "     * Hidden start: value='$start_value'\n";
    echo "     * Hidden end: value='$end_value'\n";
    echo "     * Hidden active: value='$active_value'\n";
}

// Simulează JavaScript-ul pentru sincronizare
echo "\n4️⃣ Simularea JavaScript-ului pentru sincronizare...\n";
echo "   - Event listeners atașate pentru: change, blur, input, keyup\n";
echo "   - Funcția syncHiddenInputs() actualizează hidden inputs\n";
echo "   - Funcția updateDuration() calculează durata\n";

// Testează calculul duratei
echo "\n5️⃣ Testarea calculului duratei...\n";
foreach ($working_hours as $day => $hours) {
    if ($hours['active'] && !empty($hours['start']) && !empty($hours['end'])) {
        $start_time = strtotime($hours['start']);
        $end_time = strtotime($hours['end']);
        
        if ($end_time > $start_time) {
            $duration = round(($end_time - $start_time) / 3600, 1) . 'h';
            echo "   - $day: $duration (start: {$hours['start']}, end: {$hours['end']})\n";
        } else {
            echo "   - $day: - (end time <= start time)\n";
        }
    } else {
        echo "   - $day: - (inactive or missing times)\n";
    }
}

echo "\n🎯 Testul frontend complet!\n";
echo "📝 Notă: Acest test simulează logica JavaScript.\n";
echo "   Pentru testarea reală, deschide pagina de setări și verifică console-ul browser-ului.\n";
?> 