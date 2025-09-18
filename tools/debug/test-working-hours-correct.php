<?php
/**
 * Script pentru testarea corectă a working_hours
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testarea corectă a working_hours...\n\n";

try {
    $settings = Clinica_Settings::get_instance();
    $schedule_settings = $settings->get_group('schedule');
    
    echo "📊 Setările pentru program:\n";
    foreach ($schedule_settings as $key => $setting) {
        echo "  - $key: " . gettype($setting) . "\n";
        if (is_array($setting) && isset($setting['value'])) {
            echo "    * value: " . gettype($setting['value']) . "\n";
            if (is_array($setting['value'])) {
                echo "    * value array cu " . count($setting['value']) . " elemente\n";
            }
        }
    }
    
    if (isset($schedule_settings['working_hours']['value'])) {
        $working_hours = $schedule_settings['working_hours']['value'];
        echo "\n✅ working_hours['value'] găsit: " . gettype($working_hours) . "\n";
        
        if (is_array($working_hours)) {
            echo "✅ working_hours este array cu " . count($working_hours) . " elemente\n";
            
            // Testează accesarea elementelor
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                if (isset($working_hours[$day])) {
                    $day_data = $working_hours[$day];
                    if (is_array($day_data) && isset($day_data['active'])) {
                        echo "✅ $day: active=" . ($day_data['active'] ? 'true' : 'false') . "\n";
                    } else {
                        echo "⚠️ $day: structură neașteptată\n";
                    }
                } else {
                    echo "⚠️ $day: nu există\n";
                }
            }
        } else {
            echo "❌ working_hours nu este array: " . gettype($working_hours) . "\n";
        }
    } else {
        echo "❌ working_hours['value'] nu există\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Eroare fatală: " . $e->getMessage() . "\n";
}

echo "\n🎯 Testul complet!\n";
?> 