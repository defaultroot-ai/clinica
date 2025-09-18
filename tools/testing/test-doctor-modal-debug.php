<?php
/**
 * Test Script pentru Debug Modal Doctor
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h1>Debug Modal Doctor Dashboard</h1>';
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

echo '<h2>1. Test AJAX Handler Direct</h2>';
echo '<ul>';

// Testează handler-ul AJAX direct
if (in_array('clinica_doctor', $user_roles) || in_array('administrator', $user_roles)) {
    echo '<li>✅ Utilizatorul are permisiunea de a testa AJAX-ul</li>';
    
    // Simulează request AJAX
    $_POST['action'] = 'clinica_load_doctor_patient_form';
    $_POST['nonce'] = wp_create_nonce('clinica_doctor_dashboard_nonce');
    
    try {
        ob_start();
        do_action('wp_ajax_clinica_load_doctor_patient_form');
        $output = ob_get_clean();
        
        echo '<li>✅ AJAX handler răspunde</li>';
        
        if (strpos($output, 'form_html') !== false) {
            echo '<li>✅ Răspunsul conține form_html</li>';
        } else {
            echo '<li>❌ Răspunsul NU conține form_html</li>';
            echo '<li>Răspuns: ' . esc_html(substr($output, 0, 200)) . '...</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la testarea AJAX-ului: ' . esc_html($e->getMessage()) . '</li>';
    }
    
} else {
    echo '<li>⚠️ Utilizatorul nu are permisiunea de a testa AJAX-ul</li>';
}

echo '</ul>';

echo '<h2>2. Test Formular Complet</h2>';
echo '<ul>';

// Testează formularul complet
if (class_exists('Clinica_Patient_Creation_Form')) {
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
        
        // Verifică dacă formularul conține jQuery
        if (strpos($form_html, 'jQuery') !== false || strpos($form_html, '$') !== false) {
            echo '<li>✅ Formularul conține referințe jQuery</li>';
        } else {
            echo '<li>❌ Formularul nu conține referințe jQuery</li>';
        }
        
    } catch (Exception $e) {
        echo '<li>❌ Eroare la renderizarea formularului: ' . esc_html($e->getMessage()) . '</li>';
    }
} else {
    echo '<li>❌ Clasa Clinica_Patient_Creation_Form nu există</li>';
}

echo '</ul>';

echo '<h2>3. Test JavaScript Loading</h2>';
echo '<ul>';

// Verifică dacă JavaScript-ul este încărcat corect
$js_file = plugin_dir_path(__FILE__) . 'assets/js/doctor-dashboard.js';
if (file_exists($js_file)) {
    echo '<li>✅ Fișierul JavaScript există</li>';
    
    $js_content = file_get_contents($js_file);
    
    if (strpos($js_content, 'openCreatePatientModal') !== false) {
        echo '<li>✅ Funcția openCreatePatientModal există</li>';
    } else {
        echo '<li>❌ Funcția openCreatePatientModal NU există</li>';
    }
    
    if (strpos($js_content, 'jQuery.noConflict') !== false) {
        echo '<li>✅ jQuery.noConflict este folosit</li>';
    } else {
        echo '<li>❌ jQuery.noConflict NU este folosit</li>';
    }
    
    // Verifică dacă există încă $ în loc de $j
    if (preg_match('/\$\([^)]+\)/', $js_content)) {
        echo '<li>⚠️ Încă există folosiri de $ în loc de $j</li>';
    } else {
        echo '<li>✅ Toate folosirile de $ au fost înlocuite cu $j</li>';
    }
    
} else {
    echo '<li>❌ Fișierul JavaScript NU există</li>';
}

echo '</ul>';

echo '<h2>4. Test Enqueue Scripts</h2>';
echo '<ul>';

// Verifică dacă scripturile sunt încărcate corect
$doctor_dashboard_file = plugin_dir_path(__FILE__) . 'includes/class-clinica-doctor-dashboard.php';
if (file_exists($doctor_dashboard_file)) {
    $doctor_content = file_get_contents($doctor_dashboard_file);
    
    if (strpos($doctor_content, 'wp_enqueue_script') !== false) {
        echo '<li>✅ Scripturile sunt încărcate</li>';
    } else {
        echo '<li>❌ Scripturile NU sunt încărcate</li>';
    }
    
    if (strpos($doctor_content, 'wp_localize_script') !== false) {
        echo '<li>✅ Variabilele AJAX sunt localizate</li>';
    } else {
        echo '<li>❌ Variabilele AJAX NU sunt localizate</li>';
    }
    
} else {
    echo '<li>❌ Fișierul dashboard-ului NU există</li>';
}

echo '</ul>';

echo '<h2>5. Test Modal HTML</h2>';
echo '<ul>';

// Verifică dacă modalul există în HTML
if (strpos($doctor_content, 'create-patient-modal') !== false) {
    echo '<li>✅ Modalul există în HTML</li>';
} else {
    echo '<li>❌ Modalul NU există în HTML</li>';
}

echo '</ul>';

echo '<h2>6. Test jQuery Availability</h2>';
echo '<ul>';

// Verifică dacă jQuery este disponibil
if (wp_script_is('jquery', 'registered')) {
    echo '<li>✅ jQuery este înregistrat în WordPress</li>';
} else {
    echo '<li>❌ jQuery NU este înregistrat în WordPress</li>';
}

if (wp_script_is('jquery', 'enqueued')) {
    echo '<li>✅ jQuery este încărcat</li>';
} else {
    echo '<li>❌ jQuery NU este încărcat</li>';
}

echo '</ul>';

echo '<h2>7. Instrucțiuni de Debug în Browser</h2>';
echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 5px;">';
echo '<h3>Pentru a debug-a problema în browser:</h3>';
echo '<ol>';
echo '<li>Accesați pagina cu <code>[clinica_doctor_dashboard]</code></li>';
echo '<li>Deschideți Developer Tools (F12)</li>';
echo '<li>Mergeți la tab-ul Console</li>';
echo '<li>Adăugați acest cod în console pentru a testa:</li>';
echo '</ol>';

echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ccc;">';
echo '// Test 1: Verifică dacă jQuery este disponibil
console.log("jQuery available:", typeof jQuery !== "undefined");
console.log("$ available:", typeof $ !== "undefined");

// Test 2: Verifică dacă variabila AJAX este disponibilă
console.log("clinicaDoctorAjax available:", typeof clinicaDoctorAjax !== "undefined");

// Test 3: Verifică dacă funcția există
console.log("openCreatePatientModal available:", typeof openCreatePatientModal !== "undefined");

// Test 4: Testează funcția
if (typeof openCreatePatientModal === "function") {
    console.log("Testing openCreatePatientModal...");
    openCreatePatientModal();
} else {
    console.log("openCreatePatientModal is not a function");
}

// Test 5: Verifică dacă modalul există
console.log("Modal exists:", document.getElementById("create-patient-modal") !== null);
</pre>';

echo '<h3>Dacă încă apar probleme:</h3>';
echo '<ul>';
echo '<li>Verificați că nu există conflicte cu alte plugin-uri</li>';
echo '<li>Verificați că tema nu interferează cu jQuery</li>';
echo '<li>Încercați să dezactivați temporar alte plugin-uri</li>';
echo '<li>Verificați versiunea de jQuery în WordPress</li>';
echo '<li>Verificați că nu există erori JavaScript înainte de apăsarea butonului</li>';
echo '</ul>';
echo '</div>';

echo '<h2>8. Rezumat</h2>';
echo '<p><strong>Status:</strong> ';
if (file_exists($js_file) && class_exists('Clinica_Patient_Creation_Form')) {
    echo '<span style="color: green;">✅ Toate componentele sunt prezente!</span>';
} else {
    echo '<span style="color: red;">❌ Există probleme cu componentele</span>';
}
echo '</p>';

echo '<p><strong>Următorul pas:</strong> Rulați testele din browser pentru a identifica exact unde apare problema.</p>';

echo '<hr>';
echo '<p><em>Test generat la: ' . date('Y-m-d H:i:s') . '</em></p>';
?> 