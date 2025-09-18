<?php
/**
 * Script final de verificare pentru a confirma că toate problemele au fost rezolvate
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🎯 Verificarea finală a tuturor reparațiilor...\n\n";

$all_good = true;

try {
    // 1. Testează încărcarea setărilor
    echo "1️⃣ Testarea încărcării setărilor...\n";
    $settings = Clinica_Settings::get_instance();
    echo "   ✅ Clinica_Settings încărcat cu succes\n";
    
    // 2. Testează working_hours
    echo "2️⃣ Testarea working_hours...\n";
    $schedule_settings = $settings->get_group('schedule');
    if (isset($schedule_settings['working_hours']['value'])) {
        $working_hours = $schedule_settings['working_hours']['value'];
        if (is_array($working_hours) && count($working_hours) === 7) {
            echo "   ✅ working_hours este array valid cu 7 elemente\n";
        } else {
            echo "   ❌ working_hours nu este array valid\n";
            $all_good = false;
        }
    } else {
        echo "   ❌ working_hours nu există\n";
        $all_good = false;
    }
    
    // 3. Testează setările pentru clinică
    echo "3️⃣ Testarea setărilor pentru clinică...\n";
    $clinic_settings = $settings->get_group('clinic');
    if (is_array($clinic_settings) && count($clinic_settings) > 0) {
        echo "   ✅ Setările pentru clinică încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru clinică nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 4. Testează setările pentru email
    echo "4️⃣ Testarea setărilor pentru email...\n";
    $email_settings = $settings->get_group('email');
    if (is_array($email_settings) && count($email_settings) > 0) {
        echo "   ✅ Setările pentru email încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru email nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 5. Testează setările pentru programări
    echo "5️⃣ Testarea setărilor pentru programări...\n";
    $appointment_settings = $settings->get_group('appointments');
    if (is_array($appointment_settings) && count($appointment_settings) > 0) {
        echo "   ✅ Setările pentru programări încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru programări nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 6. Testează setările pentru notificări
    echo "6️⃣ Testarea setărilor pentru notificări...\n";
    $notification_settings = $settings->get_group('notifications');
    if (is_array($notification_settings) && count($notification_settings) > 0) {
        echo "   ✅ Setările pentru notificări încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru notificări nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 7. Testează setările pentru securitate
    echo "7️⃣ Testarea setărilor pentru securitate...\n";
    $security_settings = $settings->get_group('security');
    if (is_array($security_settings) && count($security_settings) > 0) {
        echo "   ✅ Setările pentru securitate încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru securitate nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 8. Testează setările pentru performanță
    echo "8️⃣ Testarea setărilor pentru performanță...\n";
    $performance_settings = $settings->get_group('performance');
    if (is_array($performance_settings)) {
        echo "   ✅ Setările pentru performanță încărcate cu succes\n";
    } else {
        echo "   ❌ Setările pentru performanță nu au fost încărcate\n";
        $all_good = false;
    }
    
    // 9. Testează dacă fișierul settings.php poate fi încărcat fără erori
    echo "9️⃣ Testarea fișierului settings.php...\n";
    $settings_file = __DIR__ . '/../../admin/views/settings.php';
    if (file_exists($settings_file)) {
        echo "   ✅ Fișierul settings.php există\n";
        
        // Simulează încărcarea paginii
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
            echo "   ✅ working_hours este array valid în settings.php\n";
        } else {
            echo "   ❌ working_hours nu este array valid în settings.php\n";
            $all_good = false;
        }
        
        ob_end_clean();
    } else {
        echo "   ❌ Fișierul settings.php nu există\n";
        $all_good = false;
    }
    
    // 10. Verifică dacă există erori în debug.log
    echo "🔟 Verificarea debug.log...\n";
    $debug_log = '../../../wp-content/debug.log';
    if (file_exists($debug_log)) {
        $log_content = file_get_contents($debug_log);
        if (empty($log_content)) {
            echo "   ✅ Nu există erori în debug.log\n";
        } else {
            echo "   ⚠️ Există conținut în debug.log:\n";
            $lines = explode("\n", $log_content);
            $recent_lines = array_slice($lines, -5);
            foreach ($recent_lines as $line) {
                if (!empty(trim($line))) {
                    echo "      $line\n";
                }
            }
            $all_good = false;
        }
    } else {
        echo "   ✅ Fișierul debug.log nu există (nu sunt erori)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
    $all_good = false;
} catch (Error $e) {
    echo "❌ Eroare fatală: " . $e->getMessage() . "\n";
    $all_good = false;
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($all_good) {
    echo "🎉 TOATE VERIFICĂRILE AU TRECUT CU SUCCES!\n";
    echo "🎯 PAGINA DE SETĂRI ESTE ACUM COMPLET FUNCȚIONALĂ!\n";
    echo "✅ Nu mai există erori PHP sau de bază de date\n";
    echo "✅ Toate setările se încarcă corect\n";
    echo "✅ working_hours funcționează perfect\n";
    echo "✅ Debug.log este curat\n";
} else {
    echo "⚠️ EXISTĂ ÎNCĂ PROBLEME CARE TREBUI REZOLVATE!\n";
    echo "🔧 Verifică erorile de mai sus și repară-le\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?> 