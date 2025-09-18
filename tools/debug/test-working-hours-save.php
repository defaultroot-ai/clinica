<?php
/**
 * Script pentru testarea salvării working_hours
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testarea salvării working_hours...\n\n";

// Simulează datele POST pentru working_hours
$_POST['working_hours'] = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'wednesday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'thursday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'friday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'saturday' => array('start' => '09:00', 'end' => '14:00', 'active' => true),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

echo "📝 Simulez datele POST:\n";
foreach ($_POST['working_hours'] as $day => $data) {
    echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
}

// Testează logica de salvare
$settings = Clinica_Settings::get_instance();

// Simulează procesarea datelor POST
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

echo "\n📝 Datele procesate pentru salvare:\n";
foreach ($working_hours as $day => $data) {
    echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
}

// Salvează în baza de date
echo "\n💾 Salvarea în baza de date...\n";
$result = $settings->set('working_hours', $working_hours);

if ($result) {
    echo "✅ Salvarea reușită!\n";
} else {
    echo "❌ Eroare la salvare!\n";
}

// Verifică dacă s-au salvat corect
echo "\n🔍 Verificarea datelor salvate...\n";
$saved_working_hours = $settings->get('working_hours');

if (is_array($saved_working_hours)) {
    echo "✅ Datele salvate:\n";
    foreach ($saved_working_hours as $day => $data) {
        echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
    }
} else {
    echo "❌ Datele nu s-au salvat corect!\n";
}

// Testează încărcarea pentru afișare
echo "\n📊 Testarea încărcării pentru afișare...\n";
$schedule_settings = $settings->get_group('schedule');
$display_working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();

if (is_array($display_working_hours)) {
    echo "✅ Datele pentru afișare:\n";
    foreach ($display_working_hours as $day => $data) {
        echo "  - $day: start='{$data['start']}', end='{$data['end']}', active=" . ($data['active'] ? 'true' : 'false') . "\n";
        
        // Testează calculul duratei
        if (!empty($data['start']) && !empty($data['end'])) {
            $start_time = strtotime($data['start']);
            $end_time = strtotime($data['end']);
            $duration = round(($end_time - $start_time) / 3600, 1);
            echo "    * Durata: {$duration}h\n";
        } else {
            echo "    * Durata: nu se poate calcula\n";
        }
    }
} else {
    echo "❌ Datele pentru afișare nu s-au încărcat corect!\n";
}

echo "\n🎯 Testul complet!\n";
?> 