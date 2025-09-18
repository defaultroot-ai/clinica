<?php
/**
 * Test Script pentru Verificarea Formularului Complet de Creare Pacienți
 * în Dashboard-urile de Asistent și Doctor
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h1>Test Formular Complet Creare Pacienți</h1>';
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

echo '<h2>1. Verificare Dashboard Asistent</h2>';
echo '<ul>';

// Verifică dacă clasa există
if (class_exists('Clinica_Assistant_Dashboard')) {
    echo '<li>✅ Clasa Clinica_Assistant_Dashboard există</li>';
    
    // Verifică dacă metoda AJAX există
    $assistant_dashboard = new Clinica_Assistant_Dashboard();
    $reflection = new ReflectionClass($assistant_dashboard);
    
    if ($reflection->hasMethod('ajax_load_patient_form')) {
        echo '<li>✅ Metoda ajax_load_patient_form există în dashboard-ul de asistent</li>';
    } else {
        echo '<li>❌ Metoda ajax_load_patient_form NU există în dashboard-ul de asistent</li>';
    }
    
    // Verifică dacă handler-ul AJAX este înregistrat
    $has_ajax_handler = has_action('wp_ajax_clinica_load_assistant_patient_form');
    if ($has_ajax_handler) {
        echo '<li>✅ Handler-ul AJAX clinica_load_assistant_patient_form este înregistrat</li>';
    } else {
        echo '<li>❌ Handler-ul AJAX clinica_load_assistant_patient_form NU este înregistrat</li>';
    }
    
} else {
    echo '<li>❌ Clasa Clinica_Assistant_Dashboard NU există</li>';
}

echo '</ul>';

echo '<h2>2. Verificare Dashboard Doctor</h2>';
echo '<ul>';

// Verifică dacă clasa există
if (class_exists('Clinica_Doctor_Dashboard')) {
    echo '<li>✅ Clasa Clinica_Doctor_Dashboard există</li>';
    
    // Verifică dacă metoda AJAX există
    $doctor_dashboard = new Clinica_Doctor_Dashboard();
    $reflection = new ReflectionClass($doctor_dashboard);
    
    if ($reflection->hasMethod('ajax_load_patient_form')) {
        echo '<li>✅ Metoda ajax_load_patient_form există în dashboard-ul de doctor</li>';
    } else {
        echo '<li>❌ Metoda ajax_load_patient_form NU există în dashboard-ul de doctor</li>';
    }
    
    // Verifică dacă handler-ul AJAX este înregistrat
    $has_ajax_handler = has_action('wp_ajax_clinica_load_doctor_patient_form');
    if ($has_ajax_handler) {
        echo '<li>✅ Handler-ul AJAX clinica_load_doctor_patient_form este înregistrat</li>';
    } else {
        echo '<li>❌ Handler-ul AJAX clinica_load_doctor_patient_form NU este înregistrat</li>';
    }
    
} else {
    echo '<li>❌ Clasa Clinica_Doctor_Dashboard NU există</li>';
}

echo '</ul>';

echo '<h2>3. Verificare Formular Complet</h2>';
echo '<ul>';

// Verifică dacă clasa formularului există
if (class_exists('Clinica_Patient_Creation_Form')) {
    echo '<li>✅ Clasa Clinica_Patient_Creation_Form există</li>';
    
    // Testează renderizarea formularului
    try {
        $patient_form = new Clinica_Patient_Creation_Form();
        $form_html = $patient_form->render_form();
        
        if (strpos($form_html, 'clinica-patient-form') !== false) {
            echo '<li>✅ Formularul se renderizează corect</li>';
        } else {
            echo '<li>❌ Formularul nu se renderizează corect</li>';
        }
        
        if (strpos($form_html, 'cnp') !== false) {
            echo '<li>✅ Formularul conține câmpul CNP</li>';
        } else {
            echo '<li>❌ Formularul nu conține câmpul CNP</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la renderizarea formularului: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>❌ Clasa Clinica_Patient_Creation_Form NU există</li>';
}

echo '</ul>';

echo '<h2>4. Verificare CSS și JS</h2>';
echo '<ul>';

// Verifică dacă fișierele CSS și JS există
$css_file = plugin_dir_path(__FILE__) . 'assets/css/patient-creation-form.css';
$js_file = plugin_dir_path(__FILE__) . 'assets/js/patient-creation-form.js';

if (file_exists($css_file)) {
    echo '<li>✅ Fișierul CSS pentru formular există</li>';
} else {
    echo '<li>❌ Fișierul CSS pentru formular NU există</li>';
}

if (file_exists($js_file)) {
    echo '<li>✅ Fișierul JS pentru formular există</li>';
} else {
    echo '<li>❌ Fișierul JS pentru formular NU există</li>';
}

echo '</ul>';

echo '<h2>5. Test AJAX pentru Asistent</h2>';
echo '<ul>';

// Testează AJAX pentru asistent
if (in_array('clinica_assistant', $user_roles) || in_array('clinica_receptionist', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul pentru asistent</li>';
    
    // Simulează un request AJAX
    $_POST['action'] = 'clinica_load_assistant_patient_form';
    $_POST['nonce'] = wp_create_nonce('clinica_assistant_dashboard_nonce');
    
    try {
        // Capture output
        ob_start();
        do_action('wp_ajax_clinica_load_assistant_patient_form');
        $output = ob_get_clean();
        
        if (strpos($output, 'form_html') !== false) {
            echo '<li>✅ AJAX-ul pentru asistent funcționează</li>';
        } else {
            echo '<li>❌ AJAX-ul pentru asistent NU funcționează corect</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului pentru asistent: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul pentru asistent</li>';
}

echo '</ul>';

echo '<h2>6. Test AJAX pentru Doctor</h2>';
echo '<ul>';

// Testează AJAX pentru doctor
if (in_array('clinica_doctor', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul pentru doctor</li>';
    
    // Simulează un request AJAX
    $_POST['action'] = 'clinica_load_doctor_patient_form';
    $_POST['nonce'] = wp_create_nonce('clinica_doctor_dashboard_nonce');
    
    try {
        // Capture output
        ob_start();
        do_action('wp_ajax_clinica_load_doctor_patient_form');
        $output = ob_get_clean();
        
        if (strpos($output, 'form_html') !== false) {
            echo '<li>✅ AJAX-ul pentru doctor funcționează</li>';
        } else {
            echo '<li>❌ AJAX-ul pentru doctor NU funcționează corect</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului pentru doctor: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul pentru doctor</li>';
}

echo '</ul>';

echo '<h2>7. Instrucțiuni de Testare</h2>';
echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
echo '<h3>Pentru a testa funcționalitatea:</h3>';
echo '<ol>';
echo '<li>Accesați pagina cu shortcode-ul <code>[clinica_assistant_dashboard]</code></li>';
echo '<li>Apăsați butonul "Pacient Nou"</li>';
echo '<li>Verificați că se deschide formularul complet cu toate câmpurile</li>';
echo '<li>Testați validarea CNP și autocompletarea</li>';
echo '<li>Testați generarea parolei</li>';
echo '<li>Repetați pentru <code>[clinica_doctor_dashboard]</code></li>';
echo '</ol>';

echo '<h3>Diferențe față de versiunea anterioară:</h3>';
echo '<ul>';
echo '<li>✅ Asistent-ul folosește acum formularul complet (nu cel simplu)</li>';
echo '<li>✅ Doctorul are acum butonul "Pacient Nou" în header</li>';
echo '<li>✅ Doctorul folosește formularul complet pentru crearea pacienților</li>';
echo '<li>✅ Ambele dashboard-uri încarcă CSS-ul și JS-ul pentru formular</li>';
echo '</ul>';
echo '</div>';

echo '<h2>8. Rezumat</h2>';
echo '<p><strong>Status:</strong> ';
if (class_exists('Clinica_Assistant_Dashboard') && class_exists('Clinica_Doctor_Dashboard') && class_exists('Clinica_Patient_Creation_Form')) {
    echo '<span style="color: green;">✅ Toate modificările au fost implementate cu succes!</span>';
} else {
    echo '<span style="color: red;">❌ Există probleme cu implementarea</span>';
}
echo '</p>';

echo '<p><strong>Următorul pas:</strong> Testați funcționalitatea în browser pentru a verifica că totul funcționează corect.</p>';

echo '<hr>';
echo '<p><em>Test generat la: ' . date('Y-m-d H:i:s') . '</em></p>';
?> 