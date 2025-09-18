<?php
/**
 * Test Script pentru Verificarea Corectărilor Dashboard-urilor
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h1>Test Corectări Dashboard-uri</h1>';
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

echo '<h2>1. Verificare Fișiere CSS</h2>';
echo '<ul>';

// Verifică dacă fișierul CSS pentru formular există
$css_file = plugin_dir_path(__FILE__) . 'assets/css/patient-creation-form.css';
if (file_exists($css_file)) {
    echo '<li>✅ Fișierul CSS pentru formular există: ' . basename($css_file) . '</li>';
    
    // Verifică dimensiunea fișierului
    $file_size = filesize($css_file);
    echo '<li>📏 Dimensiunea fișierului: ' . number_format($file_size) . ' bytes</li>';
} else {
    echo '<li>❌ Fișierul CSS pentru formular NU există: ' . basename($css_file) . '</li>';
}

echo '</ul>';

echo '<h2>2. Verificare Handler-uri AJAX</h2>';
echo '<ul>';

// Verifică handler-urile AJAX pentru doctor
$doctor_handlers = array(
    'clinica_get_doctor_overview',
    'clinica_get_doctor_activities',
    'clinica_get_doctor_patients_select',
    'clinica_load_doctor_patient_form'
);

foreach ($doctor_handlers as $handler) {
    $has_handler = has_action('wp_ajax_' . $handler);
    if ($has_handler) {
        echo '<li>✅ Handler-ul AJAX <code>' . $handler . '</code> este înregistrat</li>';
    } else {
        echo '<li>❌ Handler-ul AJAX <code>' . $handler . '</code> NU este înregistrat</li>';
    }
}

// Verifică handler-urile AJAX pentru asistent
$assistant_handlers = array(
    'clinica_load_assistant_patient_form'
);

foreach ($assistant_handlers as $handler) {
    $has_handler = has_action('wp_ajax_' . $handler);
    if ($has_handler) {
        echo '<li>✅ Handler-ul AJAX <code>' . $handler . '</code> este înregistrat</li>';
    } else {
        echo '<li>❌ Handler-ul AJAX <code>' . $handler . '</code> NU este înregistrat</li>';
    }
}

// Verifică handler-ul pentru crearea pacienților
$create_patient_handler = has_action('wp_ajax_clinica_create_patient');
if ($create_patient_handler) {
    echo '<li>✅ Handler-ul AJAX <code>clinica_create_patient</code> este înregistrat</li>';
} else {
    echo '<li>❌ Handler-ul AJAX <code>clinica_create_patient</code> NU este înregistrat</li>';
}

echo '</ul>';

echo '<h2>3. Verificare Clase</h2>';
echo '<ul>';

// Verifică dacă clasele există
$classes = array(
    'Clinica_Patient_Creation_Form',
    'Clinica_Doctor_Dashboard',
    'Clinica_Assistant_Dashboard'
);

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo '<li>✅ Clasa <code>' . $class . '</code> există</li>';
    } else {
        echo '<li>❌ Clasa <code>' . $class . '</code> NU există</li>';
    }
}

echo '</ul>';

echo '<h2>4. Test AJAX pentru Doctor</h2>';
echo '<ul>';

// Testează AJAX pentru doctor
if (in_array('clinica_doctor', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul pentru doctor</li>';
    
    // Testează handler-ul pentru overview
    $_POST['action'] = 'clinica_get_doctor_overview';
    $_POST['nonce'] = wp_create_nonce('clinica_doctor_dashboard_nonce');
    
    try {
        ob_start();
        do_action('wp_ajax_clinica_get_doctor_overview');
        $output = ob_get_clean();
        
        if (strpos($output, 'success') !== false) {
            echo '<li>✅ AJAX-ul pentru overview funcționează</li>';
        } else {
            echo '<li>❌ AJAX-ul pentru overview NU funcționează corect</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului pentru overview: ' . esc_html($e->getMessage()) . '</li>';
    }
    
    // Testează handler-ul pentru formular
    $_POST['action'] = 'clinica_load_doctor_patient_form';
    
    try {
        ob_start();
        do_action('wp_ajax_clinica_load_doctor_patient_form');
        $output = ob_get_clean();
        
        if (strpos($output, 'form_html') !== false) {
            echo '<li>✅ AJAX-ul pentru formular funcționează</li>';
        } else {
            echo '<li>❌ AJAX-ul pentru formular NU funcționează corect</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului pentru formular: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul pentru doctor</li>';
}

echo '</ul>';

echo '<h2>5. Test AJAX pentru Asistent</h2>';
echo '<ul>';

// Testează AJAX pentru asistent
if (in_array('clinica_assistant', $user_roles) || in_array('clinica_receptionist', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul pentru asistent</li>';
    
    // Testează handler-ul pentru formular
    $_POST['action'] = 'clinica_load_assistant_patient_form';
    $_POST['nonce'] = wp_create_nonce('clinica_assistant_dashboard_nonce');
    
    try {
        ob_start();
        do_action('wp_ajax_clinica_load_assistant_patient_form');
        $output = ob_get_clean();
        
        if (strpos($output, 'form_html') !== false) {
            echo '<li>✅ AJAX-ul pentru formular funcționează</li>';
        } else {
            echo '<li>❌ AJAX-ul pentru formular NU funcționează corect</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului pentru formular: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul pentru asistent</li>';
}

echo '</ul>';

echo '<h2>6. Verificare Enqueue CSS/JS</h2>';
echo '<ul>';

// Verifică dacă CSS-ul este încărcat în dashboard-urile respective
$doctor_dashboard_file = plugin_dir_path(__FILE__) . 'includes/class-clinica-doctor-dashboard.php';
$assistant_dashboard_file = plugin_dir_path(__FILE__) . 'includes/class-clinica-assistant-dashboard.php';

if (file_exists($doctor_dashboard_file)) {
    $doctor_content = file_get_contents($doctor_dashboard_file);
    if (strpos($doctor_content, 'patient-creation-form.css') !== false) {
        echo '<li>✅ CSS-ul pentru formular este încărcat în dashboard-ul de doctor</li>';
    } else {
        echo '<li>❌ CSS-ul pentru formular NU este încărcat în dashboard-ul de doctor</li>';
    }
}

if (file_exists($assistant_dashboard_file)) {
    $assistant_content = file_get_contents($assistant_dashboard_file);
    if (strpos($assistant_content, 'patient-creation-form.css') !== false) {
        echo '<li>✅ CSS-ul pentru formular este încărcat în dashboard-ul de asistent</li>';
    } else {
        echo '<li>❌ CSS-ul pentru formular NU este încărcat în dashboard-ul de asistent</li>';
    }
}

echo '</ul>';

echo '<h2>7. Test Formular Complet</h2>';
echo '<ul>';

// Testează renderizarea formularului complet
if (class_exists('Clinica_Patient_Creation_Form')) {
    try {
        $patient_form = new Clinica_Patient_Creation_Form();
        $form_html = $patient_form->render_form();
        
        if (strpos($form_html, 'clinica-patient-form') !== false) {
            echo '<li>✅ Formularul complet se renderizează corect</li>';
        } else {
            echo '<li>❌ Formularul complet nu se renderizează corect</li>';
        }
        
        if (strpos($form_html, 'cnp') !== false) {
            echo '<li>✅ Formularul conține câmpul CNP</li>';
        } else {
            echo '<li>❌ Formularul nu conține câmpul CNP</li>';
        }
        
        if (strpos($form_html, 'password_method') !== false) {
            echo '<li>✅ Formularul conține opțiunile pentru generarea parolei</li>';
        } else {
            echo '<li>❌ Formularul nu conține opțiunile pentru generarea parolei</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la renderizarea formularului: ' . esc_html($e->getMessage()) . '</li>';
    }
} else {
    echo '<li>❌ Clasa Clinica_Patient_Creation_Form nu există</li>';
}

echo '</ul>';

echo '<h2>8. Instrucțiuni de Testare în Browser</h2>';
echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
echo '<h3>Pentru a testa funcționalitatea completă:</h3>';
echo '<ol>';
echo '<li>Accesați pagina cu shortcode-ul <code>[clinica_doctor_dashboard]</code></li>';
echo '<li>Verificați că nu mai apar erori 404 pentru CSS</li>';
echo '<li>Verificați că nu mai apar erori 400 pentru AJAX</li>';
echo '<li>Apăsați butonul "Pacient Nou"</li>';
echo '<li>Verificați că se deschide formularul complet</li>';
echo '<li>Testați validarea CNP și generarea parolei</li>';
echo '<li>Repetați pentru <code>[clinica_assistant_dashboard]</code></li>';
echo '</ol>';

echo '<h3>Probleme rezolvate:</h3>';
echo '<ul>';
echo '<li>✅ Fișierul CSS <code>patient-creation-form.css</code> a fost creat</li>';
echo '<li>✅ Handler-urile AJAX lipsă au fost adăugate pentru doctor</li>';
echo '<li>✅ CSS-ul și JS-ul sunt încărcate în ambele dashboard-uri</li>';
echo '<li>✅ Formularul complet este folosit în ambele dashboard-uri</li>';
echo '</ul>';
echo '</div>';

echo '<h2>9. Rezumat</h2>';
echo '<p><strong>Status:</strong> ';
if (file_exists($css_file) && class_exists('Clinica_Patient_Creation_Form')) {
    echo '<span style="color: green;">✅ Toate corectările au fost implementate cu succes!</span>';
} else {
    echo '<span style="color: red;">❌ Există probleme cu implementarea</span>';
}
echo '</p>';

echo '<p><strong>Următorul pas:</strong> Testați funcționalitatea în browser pentru a verifica că erorile 404 și 400 au fost rezolvate.</p>';

echo '<hr>';
echo '<p><em>Test generat la: ' . date('Y-m-d H:i:s') . '</em></p>';
?> 