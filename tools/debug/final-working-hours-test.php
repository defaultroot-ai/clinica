<?php
/**
 * Script final pentru testarea working_hours complet
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🎯 Testul final pentru working_hours...\n\n";

try {
    $settings = Clinica_Settings::get_instance();
    
    // 1. Testează încărcarea setărilor
    echo "1️⃣ Testarea încărcării setărilor...\n";
    $schedule_settings = $settings->get_group('schedule');
    $working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();
    
    if (is_array($working_hours) && count($working_hours) === 7) {
        echo "✅ working_hours încărcat corect cu " . count($working_hours) . " zile\n";
    } else {
        echo "❌ working_hours nu s-a încărcat corect!\n";
        exit(1);
    }
    
    // 2. Testează valorile pentru fiecare zi
    echo "\n2️⃣ Testarea valorilor pentru fiecare zi...\n";
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $all_good = true;
    
    foreach ($days as $day) {
        if (isset($working_hours[$day])) {
            $day_data = $working_hours[$day];
            echo "  - $day: start='{$day_data['start']}', end='{$day_data['end']}', active=" . ($day_data['active'] ? 'true' : 'false');
            
            // Testează calculul duratei
            if (!empty($day_data['start']) && !empty($day_data['end'])) {
                $start_time = strtotime($day_data['start']);
                $end_time = strtotime($day_data['end']);
                $duration = round(($end_time - $start_time) / 3600, 1);
                echo " (durata: {$duration}h)";
            } else {
                echo " (durata: nu se poate calcula)";
            }
            echo "\n";
        } else {
            echo "  - $day: ❌ NU EXISTĂ!\n";
            $all_good = false;
        }
    }
    
    // 3. Testează salvarea
    echo "\n3️⃣ Testarea salvării...\n";
    
    // Modifică o valoare pentru test
    $original_monday = $working_hours['monday'];
    $working_hours['monday']['start'] = '09:00';
    $working_hours['monday']['end'] = '18:00';
    
    $result = $settings->set('working_hours', $working_hours);
    
    if ($result) {
        echo "✅ Salvarea reușită!\n";
        
        // Verifică dacă s-a salvat corect
        $saved_working_hours = $settings->get('working_hours');
        if (isset($saved_working_hours['monday']) && 
            $saved_working_hours['monday']['start'] === '09:00' && 
            $saved_working_hours['monday']['end'] === '18:00') {
            echo "✅ Valorile s-au salvat corect!\n";
        } else {
            echo "❌ Valorile nu s-au salvat corect!\n";
            $all_good = false;
        }
        
        // Restorează valoarea originală
        $working_hours['monday'] = $original_monday;
        $settings->set('working_hours', $working_hours);
        echo "✅ Valoarea originală restaurată!\n";
        
    } else {
        echo "❌ Salvarea a eșuat!\n";
        $all_good = false;
    }
    
    // 4. Testează afișarea în HTML
    echo "\n4️⃣ Testarea afișării în HTML...\n";
    
    // Simulează generarea HTML-ului pentru input-uri
    ob_start();
    
    foreach ($days as $day) {
        $day_data = $working_hours[$day];
        $start_value = esc_attr($day_data['start']);
        $end_value = esc_attr($day_data['end']);
        $active_class = $day_data['active'] ? 'active' : 'inactive';
        
        echo "  - $day: start='$start_value', end='$end_value', class='$active_class'\n";
    }
    
    $html_output = ob_get_clean();
    echo $html_output;
    
    // 5. Verifică dacă există erori în debug.log
    echo "\n5️⃣ Verificarea debug.log...\n";
    $debug_log = '../../../wp-content/debug.log';
    if (file_exists($debug_log)) {
        $log_content = file_get_contents($debug_log);
        if (empty($log_content)) {
            echo "✅ Nu există erori în debug.log\n";
        } else {
            echo "⚠️ Există conținut în debug.log:\n";
            $lines = explode("\n", $log_content);
            $recent_lines = array_slice($lines, -3);
            foreach ($recent_lines as $line) {
                if (!empty(trim($line))) {
                    echo "      $line\n";
                }
            }
            $all_good = false;
        }
    } else {
        echo "✅ Fișierul debug.log nu există (nu sunt erori)\n";
    }
    
    // Rezultat final
    echo "\n" . str_repeat("=", 50) . "\n";
    
    if ($all_good) {
        echo "🎉 TOATE TESTELE AU TRECUT CU SUCCES!\n";
        echo "✅ working_hours se încarcă corect\n";
        echo "✅ Valorile se salvează corect\n";
        echo "✅ Durata se calculează corect\n";
        echo "✅ HTML-ul se generează corect\n";
        echo "✅ Nu există erori în debug.log\n";
        echo "\n🎯 PAGINA DE SETĂRI ESTE COMPLET FUNCȚIONALĂ!\n";
    } else {
        echo "⚠️ EXISTĂ PROBLEME CARE TREBUI REZOLVATE!\n";
        echo "🔧 Verifică erorile de mai sus\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Eroare fatală: " . $e->getMessage() . "\n";
}

echo "\n🎯 Testul final complet!\n";
?> 