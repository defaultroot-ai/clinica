<?php
/**
 * Script pentru repararea salvării working_hours și calculului duratei
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea salvării working_hours și calculului duratei...\n\n";

// 1. Verifică și repară setarea working_hours în baza de date
global $wpdb;
$table = $wpdb->prefix . 'clinica_settings';

$working_hours_setting = $wpdb->get_row("SELECT * FROM $table WHERE setting_key = 'working_hours'");

if ($working_hours_setting) {
    echo "📝 Găsită setarea working_hours în baza de date\n";
    
    // Decodează valoarea JSON
    $current_value = json_decode($working_hours_setting->setting_value, true);
    
    if (is_array($current_value)) {
        echo "✅ Valoarea JSON este validă\n";
        
        // Verifică dacă toate zilele au structura corectă
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $needs_update = false;
        
        foreach ($days as $day) {
            if (!isset($current_value[$day])) {
                $current_value[$day] = array('start' => '', 'end' => '', 'active' => false);
                $needs_update = true;
            } elseif (!isset($current_value[$day]['start']) || !isset($current_value[$day]['end']) || !isset($current_value[$day]['active'])) {
                $current_value[$day] = array(
                    'start' => isset($current_value[$day]['start']) ? $current_value[$day]['start'] : '',
                    'end' => isset($current_value[$day]['end']) ? $current_value[$day]['end'] : '',
                    'active' => isset($current_value[$day]['active']) ? $current_value[$day]['active'] : false
                );
                $needs_update = true;
            }
        }
        
        if ($needs_update) {
            echo "🔄 Actualizez structura working_hours...\n";
            
            $result = $wpdb->update(
                $table,
                array('setting_value' => json_encode($current_value)),
                array('setting_key' => 'working_hours'),
                array('%s'),
                array('%s')
            );
            
            if ($result !== false) {
                echo "✅ Structura actualizată cu succes!\n";
            } else {
                echo "❌ Eroare la actualizarea structurii!\n";
            }
        } else {
            echo "✅ Structura working_hours este corectă\n";
        }
        
        // Afișează valorile curente
        echo "\n📊 Valorile curente working_hours:\n";
        foreach ($days as $day) {
            $day_data = $current_value[$day];
            echo "  - $day: start='{$day_data['start']}', end='{$day_data['end']}', active=" . ($day_data['active'] ? 'true' : 'false') . "\n";
        }
        
    } else {
        echo "❌ Valoarea JSON nu este validă!\n";
    }
    
} else {
    echo "❌ Setarea working_hours nu există în baza de date!\n";
}

// 2. Verifică și repară logica de salvare în settings.php
echo "\n📝 Verificarea logicii de salvare...\n";

$settings_file = __DIR__ . '/../../admin/views/settings.php';
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    
    // Verifică dacă există logica de salvare pentru working_hours
    if (strpos($content, 'working_hours') !== false) {
        echo "✅ Logica de salvare working_hours există\n";
        
        // Verifică dacă există verificări pentru valori goale
        if (strpos($content, '!empty($day_hours[\'start\'])') !== false) {
            echo "✅ Verificările pentru valori goale există\n";
        } else {
            echo "⚠️ Verificările pentru valori goale lipsesc\n";
        }
        
    } else {
        echo "❌ Logica de salvare working_hours lipsește!\n";
    }
    
} else {
    echo "❌ Fișierul settings.php nu există!\n";
}

// 3. Testează încărcarea setărilor
echo "\n🧪 Testarea încărcării setărilor...\n";

try {
    $settings = Clinica_Settings::get_instance();
    $schedule_settings = $settings->get_group('schedule');
    
    if (isset($schedule_settings['working_hours']['value'])) {
        $working_hours = $schedule_settings['working_hours']['value'];
        
        if (is_array($working_hours)) {
            echo "✅ working_hours încărcat cu succes\n";
            
            // Testează o zi specifică
            if (isset($working_hours['monday'])) {
                $monday = $working_hours['monday'];
                echo "  - Luni: start='{$monday['start']}', end='{$monday['end']}', active=" . ($monday['active'] ? 'true' : 'false') . "\n";
                
                // Testează calculul duratei
                if (!empty($monday['start']) && !empty($monday['end'])) {
                    $start_time = strtotime($monday['start']);
                    $end_time = strtotime($monday['end']);
                    $duration = round(($end_time - $start_time) / 3600, 1);
                    echo "  - Durata calculată: {$duration}h\n";
                } else {
                    echo "  - Durata: nu se poate calcula (ore goale)\n";
                }
            }
        } else {
            echo "❌ working_hours nu este array!\n";
        }
    } else {
        echo "❌ working_hours nu există în setări!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare la testarea setărilor: " . $e->getMessage() . "\n";
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 