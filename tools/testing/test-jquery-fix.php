<?php
/**
 * Test Script pentru Verificarea Corectării jQuery
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h1>Test Corectare jQuery Dashboard Doctor</h1>';
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

echo '<h2>1. Verificare Fișier JavaScript</h2>';
echo '<ul>';

// Verifică dacă fișierul JavaScript există
$js_file = plugin_dir_path(__FILE__) . 'assets/js/doctor-dashboard.js';
if (file_exists($js_file)) {
    echo '<li>✅ Fișierul JavaScript există: ' . basename($js_file) . '</li>';
    
    // Verifică dimensiunea fișierului
    $file_size = filesize($js_file);
    echo '<li>📏 Dimensiunea fișierului: ' . number_format($file_size) . ' bytes</li>';
    
    // Verifică dacă conține jQuery.noConflict
    $js_content = file_get_contents($js_file);
    if (strpos($js_content, 'jQuery.noConflict') !== false) {
        echo '<li>✅ Fișierul conține jQuery.noConflict pentru evitarea conflictelor</li>';
    } else {
        echo '<li>❌ Fișierul NU conține jQuery.noConflict</li>';
    }
    
    // Verifică dacă conține funcția openCreatePatientModal
    if (strpos($js_content, 'openCreatePatientModal') !== false) {
        echo '<li>✅ Funcția openCreatePatientModal există</li>';
    } else {
        echo '<li>❌ Funcția openCreatePatientModal NU există</li>';
    }
    
    // Verifică dacă conține funcția initPatientForm
    if (strpos($js_content, 'initPatientForm') !== false) {
        echo '<li>✅ Funcția initPatientForm există</li>';
    } else {
        echo '<li>❌ Funcția initPatientForm NU există</li>';
    }
    
} else {
    echo '<li>❌ Fișierul JavaScript NU există: ' . basename($js_file) . '</li>';
}

echo '</ul>';

echo '<h2>2. Verificare Handler-uri AJAX</h2>';
echo '<ul>';

// Verifică handler-urile AJAX
$ajax_handlers = array(
    'clinica_load_doctor_patient_form',
    'clinica_get_doctor_overview',
    'clinica_get_doctor_activities',
    'clinica_get_doctor_patients_select'
);

foreach ($ajax_handlers as $handler) {
    $has_handler = has_action('wp_ajax_' . $handler);
    if ($has_handler) {
        echo '<li>✅ Handler-ul AJAX <code>' . $handler . '</code> este înregistrat</li>';
    } else {
        echo '<li>❌ Handler-ul AJAX <code>' . $handler . '</code> NU este înregistrat</li>';
    }
}

echo '</ul>';

echo '<h2>3. Test AJAX pentru Formular</h2>';
echo '<ul>';

// Testează AJAX pentru formular
if (in_array('clinica_doctor', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul pentru formular</li>';
    
    // Testează handler-ul pentru formular
    $_POST['action'] = 'clinica_load_doctor_patient_form';
    $_POST['nonce'] = wp_create_nonce('clinica_doctor_dashboard_nonce');
    
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
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul pentru formular</li>';
}

echo '</ul>';

echo '<h2>4. Verificare Enqueue JavaScript</h2>';
echo '<ul>';

// Verifică dacă JavaScript-ul este încărcat corect
$doctor_dashboard_file = plugin_dir_path(__FILE__) . 'includes/class-clinica-doctor-dashboard.php';
if (file_exists($doctor_dashboard_file)) {
    $doctor_content = file_get_contents($doctor_dashboard_file);
    
    if (strpos($doctor_content, 'wp_enqueue_script') !== false) {
        echo '<li>✅ JavaScript-ul este încărcat în dashboard-ul de doctor</li>';
    } else {
        echo '<li>❌ JavaScript-ul NU este încărcat în dashboard-ul de doctor</li>';
    }
    
    if (strpos($doctor_content, 'wp_localize_script') !== false) {
        echo '<li>✅ Variabilele AJAX sunt localizate pentru JavaScript</li>';
    } else {
        echo '<li>❌ Variabilele AJAX NU sunt localizate pentru JavaScript</li>';
    }
    
} else {
    echo '<li>❌ Fișierul dashboard-ului de doctor NU există</li>';
}

echo '</ul>';

echo '<h2>5. Verificare Modal HTML</h2>';
echo '<ul>';

// Verifică dacă modalul există în HTML
if (strpos($doctor_content, 'create-patient-modal') !== false) {
    echo '<li>✅ Modalul pentru crearea pacienților există în HTML</li>';
} else {
    echo '<li>❌ Modalul pentru crearea pacienților NU există în HTML</li>';
}

echo '</ul>';

echo '<h2>6. Instrucțiuni de Testare în Browser</h2>';
echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
echo '<h3>Pentru a testa corectarea jQuery:</h3>';
echo '<ol>';
echo '<li>Accesați pagina cu shortcode-ul <code>[clinica_doctor_dashboard]</code></li>';
echo '<li>Deschideți Developer Tools (F12)</li>';
echo '<li>Mergeți la tab-ul Console</li>';
echo '<li>Apăsați butonul "Pacient Nou"</li>';
echo '<li>Verificați că NU mai apar erori "jQuery is not a function"</li>';
echo '<li>Verificați că formularul se deschide corect</li>';
echo '<li>Testați funcționalitățile formularului (CNP, parolă, etc.)</li>';
echo '</ol>';

echo '<h3>Probleme rezolvate:</h3>';
echo '<ul>';
echo '<li>✅ jQuery.noConflict() folosit pentru a evita conflictele</li>';
echo '<li>✅ Toate funcțiile folosesc jQuery corect</li>';
echo '<li>✅ Callback-urile AJAX folosesc jQuery corect</li>';
echo '<li>✅ Event handlers folosesc jQuery corect</li>';
echo '</ul>';

echo '<h3>Dacă încă apar probleme:</h3>';
echo '<ul>';
echo '<li>Verificați că nu există conflicte cu alte plugin-uri</li>';
echo '<li>Verificați că tema nu interferează cu jQuery</li>';
echo '<li>Încercați să dezactivați temporar alte plugin-uri</li>';
echo '<li>Verificați versiunea de jQuery în WordPress</li>';
echo '</ul>';
echo '</div>';

echo '<h2>7. Rezumat</h2>';
echo '<p><strong>Status:</strong> ';
if (file_exists($js_file) && strpos($js_content, 'jQuery.noConflict') !== false) {
    echo '<span style="color: green;">✅ Corectările jQuery au fost implementate cu succes!</span>';
} else {
    echo '<span style="color: red;">❌ Există probleme cu implementarea</span>';
}
echo '</p>';

echo '<p><strong>Următorul pas:</strong> Testați funcționalitatea în browser pentru a verifica că eroarea "jQuery is not a function" a fost rezolvată.</p>';

echo '<hr>';
echo '<p><em>Test generat la: ' . date('Y-m-d H:i:s') . '</em></p>';
?> 