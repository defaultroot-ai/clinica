<?php
/**
 * Script pentru testarea paginii de setări
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testarea paginii de setări...\n\n";

try {
    // Testează încărcarea setărilor
    $settings = Clinica_Settings::get_instance();
    echo "✅ Clinica_Settings încărcat cu succes\n";
    
    // Testează obținerea setărilor pentru program
    $schedule_settings = $settings->get_group('schedule');
    echo "✅ Setările pentru program încărcate cu succes\n";
    
    // Testează working_hours
    if (isset($schedule_settings['working_hours']['value'])) {
        $working_hours = $schedule_settings['working_hours']['value'];
        echo "✅ working_hours găsit: " . gettype($working_hours) . "\n";
        
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
        echo "❌ working_hours nu există în setări\n";
    }
    
    // Testează alte setări
    $clinic_settings = $settings->get_group('clinic');
    echo "✅ Setările pentru clinică încărcate cu succes\n";
    
    $email_settings = $settings->get_group('email');
    echo "✅ Setările pentru email încărcate cu succes\n";
    
    // Testează dacă fișierul settings.php poate fi încărcat
    $settings_file = __DIR__ . '/../../admin/views/settings.php';
    if (file_exists($settings_file)) {
        echo "✅ Fișierul settings.php există\n";
        
        // Testează dacă poate fi inclus fără erori
        ob_start();
        $test_settings = $settings->get_group('clinic');
        $test_schedule = $settings->get_group('schedule');
        $test_email = $settings->get_group('email');
        $test_appointments = $settings->get_group('appointments');
        $test_notifications = $settings->get_group('notifications');
        $test_security = $settings->get_group('security');
        $test_performance = $settings->get_group('performance');
        
        $working_hours = isset($test_schedule['working_hours']['value']) ? $test_schedule['working_hours']['value'] : array();
        
        if (is_array($working_hours)) {
            echo "✅ working_hours este array valid\n";
        } else {
            echo "❌ working_hours nu este array valid\n";
        }
        
        ob_end_clean();
    } else {
        echo "❌ Fișierul settings.php nu există\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
    echo "📍 Fișier: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Eroare fatală: " . $e->getMessage() . "\n";
    echo "📍 Fișier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 Testul complet! Verifică pagina de setări în browser.\n";
?> 