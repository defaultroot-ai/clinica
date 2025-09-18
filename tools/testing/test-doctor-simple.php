<?php
/**
 * Test Simplu pentru Dashboard Doctor
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h1>Test Simplu Dashboard Doctor</h1>';
echo '<p><strong>Utilizator:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

// Verifică permisiunile
if (!in_array('clinica_doctor', $user_roles) && !in_array('administrator', $user_roles)) {
    echo '<p style="color: red;">❌ Nu aveți permisiunea de a accesa dashboard-ul de doctor</p>';
    exit;
}

echo '<p style="color: green;">✅ Aveți permisiunea de a accesa dashboard-ul de doctor</p>';

// Testează AJAX handler-ul
echo '<h2>Test AJAX Handler</h2>';

$_POST['action'] = 'clinica_load_doctor_patient_form';
$_POST['nonce'] = wp_create_nonce('clinica_doctor_dashboard_nonce');

try {
    ob_start();
    do_action('wp_ajax_clinica_load_doctor_patient_form');
    $output = ob_get_clean();
    
    if (strpos($output, 'form_html') !== false) {
        echo '<p style="color: green;">✅ AJAX handler funcționează</p>';
    } else {
        echo '<p style="color: red;">❌ AJAX handler nu funcționează corect</p>';
        echo '<pre>' . esc_html($output) . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Eroare la testarea AJAX-ului: ' . esc_html($e->getMessage()) . '</p>';
}

// Testează formularul
echo '<h2>Test Formular</h2>';

if (class_exists('Clinica_Patient_Creation_Form')) {
    try {
        $patient_form = new Clinica_Patient_Creation_Form();
        $form_html = $patient_form->render_form();
        
        if (strpos($form_html, 'clinica-patient-form') !== false) {
            echo '<p style="color: green;">✅ Formularul se renderizează corect</p>';
        } else {
            echo '<p style="color: red;">❌ Formularul nu se renderizează corect</p>';
        }
        
    } catch (Exception $e) {
        echo '<p style="color: red;">❌ Eroare la renderizarea formularului: ' . esc_html($e->getMessage()) . '</p>';
    }
} else {
    echo '<p style="color: red;">❌ Clasa Clinica_Patient_Creation_Form nu există</p>';
}

// Testează JavaScript-ul
echo '<h2>Test JavaScript</h2>';

$js_file = plugin_dir_path(__FILE__) . 'assets/js/doctor-dashboard.js';
if (file_exists($js_file)) {
    echo '<p style="color: green;">✅ Fișierul JavaScript există</p>';
    
    $js_content = file_get_contents($js_file);
    
    if (strpos($js_content, 'openCreatePatientModal') !== false) {
        echo '<p style="color: green;">✅ Funcția openCreatePatientModal există</p>';
    } else {
        echo '<p style="color: red;">❌ Funcția openCreatePatientModal NU există</p>';
    }
    
    if (strpos($js_content, 'jQuery.noConflict') !== false) {
        echo '<p style="color: green;">✅ jQuery.noConflict este folosit</p>';
    } else {
        echo '<p style="color: red;">❌ jQuery.noConflict NU este folosit</p>';
    }
    
} else {
    echo '<p style="color: red;">❌ Fișierul JavaScript NU există</p>';
}

// Testează CSS-ul
echo '<h2>Test CSS</h2>';

$css_file = plugin_dir_path(__FILE__) . 'assets/css/patient-creation-form.css';
if (file_exists($css_file)) {
    echo '<p style="color: green;">✅ Fișierul CSS există</p>';
} else {
    echo '<p style="color: red;">❌ Fișierul CSS NU există</p>';
}

echo '<h2>Instrucțiuni de Testare</h2>';
echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
echo '<p><strong>Pentru a testa în browser:</strong></p>';
echo '<ol>';
echo '<li>Accesați pagina cu <code>[clinica_doctor_dashboard]</code></li>';
echo '<li>Deschideți Developer Tools (F12)</li>';
echo '<li>Mergeți la tab-ul Console</li>';
echo '<li>Rulați acest cod în console:</li>';
echo '</ol>';

echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ccc;">';
echo '// Test 1: Verifică jQuery
console.log("jQuery:", typeof jQuery);
console.log("$:", typeof $);

// Test 2: Verifică variabila AJAX
console.log("clinicaDoctorAjax:", typeof clinicaDoctorAjax);

// Test 3: Verifică funcția
console.log("openCreatePatientModal:", typeof openCreatePatientModal);

// Test 4: Verifică modalul
console.log("Modal:", document.getElementById("create-patient-modal"));

// Test 5: Testează funcția
if (typeof openCreatePatientModal === "function") {
    console.log("Testing modal...");
    openCreatePatientModal();
} else {
    console.log("openCreatePatientModal is not a function");
}
</pre>';

echo '<p><strong>Dacă încă apar probleme:</strong></p>';
echo '<ul>';
echo '<li>Verificați că nu există conflicte cu alte plugin-uri</li>';
echo '<li>Verificați că tema nu interferează cu jQuery</li>';
echo '<li>Încercați să dezactivați temporar alte plugin-uri</li>';
echo '<li>Verificați versiunea de jQuery în WordPress</li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p><em>Test generat la: ' . date('Y-m-d H:i:s') . '</em></p>';
?> 