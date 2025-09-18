<?php
/**
 * Testare Setări Clinica - Script Simplu
 */

// Verifică dacă WordPress este încărcat
if (!defined('ABSPATH')) {
    // Încearcă să încarce WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('Nu s-a putut încărca WordPress. Verifică calea către wp-load.php');
    }
}

// Verifică dacă suntem în admin
if (!is_admin()) {
    wp_die('Acces permis doar din admin');
}

echo "<h1>Testare Setări Clinica</h1>";

// Testează clasa de setări
if (class_exists('Clinica_Settings')) {
    echo "<h2>✅ Clasa Clinica_Settings există</h2>";
    
    $settings = Clinica_Settings::get_instance();
    
    // Testează obținerea setărilor
    echo "<h3>Testare obținere setări:</h3>";
    
    $clinic_name = $settings->get('clinic_name', 'Test Clinică');
    echo "<p><strong>Nume clinică:</strong> " . esc_html($clinic_name) . "</p>";
    
    $clinic_email = $settings->get('clinic_email', 'test@clinica.ro');
    echo "<p><strong>Email clinică:</strong> " . esc_html($clinic_email) . "</p>";
    
    $appointment_duration = $settings->get('appointment_duration', 30);
    echo "<p><strong>Durată programări:</strong> " . esc_html($appointment_duration) . " minute</p>";
    
    $notifications_enabled = $settings->get('notifications_enabled', true);
    echo "<p><strong>Notificări activate:</strong> " . ($notifications_enabled ? 'Da' : 'Nu') . "</p>";
    
    // Testează obținerea grupului de setări
    echo "<h3>Testare grup setări clinică:</h3>";
    $clinic_settings = $settings->get_group('clinic');
    
    echo "<ul>";
    foreach ($clinic_settings as $key => $setting) {
        echo "<li><strong>" . esc_html($key) . ":</strong> " . esc_html($setting['label']) . " = " . esc_html($setting['value']) . "</li>";
    }
    echo "</ul>";
    
    // Testează setarea unei valori
    echo "<h3>Testare setare valoare:</h3>";
    $test_value = 'Test Clinică ' . date('Y-m-d H:i:s');
    $result = $settings->set('clinic_name', $test_value);
    
    if ($result) {
        echo "<p>✅ Setare reușită pentru clinic_name</p>";
        
        // Verifică dacă s-a salvat
        $new_value = $settings->get('clinic_name');
        echo "<p><strong>Valoare nouă:</strong> " . esc_html($new_value) . "</p>";
        
        // Restore valoarea originală
        $settings->set('clinic_name', 'Clinica Medicală');
        echo "<p>✅ Valoarea originală a fost restaurată</p>";
    } else {
        echo "<p>❌ Eroare la setarea valorii</p>";
    }
    
    // Testează setările publice
    echo "<h3>Testare setări publice:</h3>";
    $public_settings = $settings->get_public_settings();
    
    if (!empty($public_settings)) {
        echo "<ul>";
        foreach ($public_settings as $key => $value) {
            echo "<li><strong>" . esc_html($key) . ":</strong> " . esc_html($value) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu există setări publice configurate</p>";
    }
    
    // Testează informațiile despre o setare
    echo "<h3>Testare informații setare:</h3>";
    $setting_info = $settings->get_setting_info('clinic_name');
    
    if ($setting_info) {
        echo "<p><strong>Tip:</strong> " . esc_html($setting_info->setting_type) . "</p>";
        echo "<p><strong>Grup:</strong> " . esc_html($setting_info->setting_group) . "</p>";
        echo "<p><strong>Label:</strong> " . esc_html($setting_info->setting_label) . "</p>";
        echo "<p><strong>Descriere:</strong> " . esc_html($setting_info->setting_description) . "</p>";
        echo "<p><strong>Public:</strong> " . ($setting_info->is_public ? 'Da' : 'Nu') . "</p>";
    } else {
        echo "<p>❌ Nu s-au putut obține informațiile despre setare</p>";
    }
    
} else {
    echo "<h2>❌ Clasa Clinica_Settings nu există</h2>";
}

// Testează dacă tabelul de setări există
echo "<h2>Testare baza de date:</h2>";
global $wpdb;

$table_name = $wpdb->prefix . 'clinica_settings';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "<p>✅ Tabelul $table_name există</p>";
    
    // Numără setările
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p><strong>Număr setări în baza de date:</strong> " . esc_html($count) . "</p>";
    
    // Afișează primele 5 setări
    $settings_list = $wpdb->get_results("SELECT setting_key, setting_value, setting_type, setting_group FROM $table_name ORDER BY setting_key LIMIT 5");
    
    if ($settings_list) {
        echo "<h3>Primele 5 setări din baza de date:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Cheie</th><th>Valoare</th><th>Tip</th><th>Grup</th></tr>";
        
        foreach ($settings_list as $setting) {
            echo "<tr>";
            echo "<td>" . esc_html($setting->setting_key) . "</td>";
            echo "<td>" . esc_html(substr($setting->setting_value, 0, 50)) . "</td>";
            echo "<td>" . esc_html($setting->setting_type) . "</td>";
            echo "<td>" . esc_html($setting->setting_group) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p>❌ Tabelul $table_name nu există</p>";
    
    // Încearcă să creeze tabelele
    echo "<h3>Încercare creare tabele:</h3>";
    
    if (class_exists('Clinica_Database')) {
        Clinica_Database::create_tables();
        echo "<p>✅ Tabelele au fost create</p>";
        
        // Verifică din nou
        $table_exists_after = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if ($table_exists_after) {
            echo "<p>✅ Tabelul $table_name a fost creat cu succes</p>";
        } else {
            echo "<p>❌ Tabelul $table_name nu a putut fi creat</p>";
        }
    } else {
        echo "<p>❌ Clasa Clinica_Database nu există</p>";
    }
}

echo "<h2>Testare completă!</h2>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-settings') . "'>Mergi la pagina de setări</a></p>";
?> 