<?php
/**
 * Test pentru verificarea formularului de creare pacienți în modal
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Formular Creare Pacienți în Modal</h1>";

// Testează dacă clasa există
echo "<h2>Test Încărcare Clasă</h2>";

if (class_exists('Clinica_Patient_Creation_Form')) {
    echo "<p style='color: green;'>✅ Clinica_Patient_Creation_Form se încarcă corect</p>";
    
    // Testează dacă metoda render_form există
    if (method_exists('Clinica_Patient_Creation_Form', 'render_form')) {
        echo "<p style='color: green;'>✅ Metoda render_form există</p>";
        
        // Testează renderizarea formularului
        try {
            $form = new Clinica_Patient_Creation_Form();
            $form_html = $form->render_form();
            
            if (!empty($form_html)) {
                echo "<p style='color: green;'>✅ Formularul se renderizează corect</p>";
                echo "<p>Lungimea HTML: " . strlen($form_html) . " caractere</p>";
            } else {
                echo "<p style='color: red;'>❌ Formularul nu returnează conținut</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Eroare la renderizarea formularului: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Metoda render_form nu există</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Clinica_Patient_Creation_Form nu se încarcă</p>";
}

// Testează AJAX handler-ul pentru doctor
echo "<h2>Test AJAX Handler Doctor</h2>";

if (has_action('wp_ajax_clinica_load_doctor_patient_form')) {
    echo "<p style='color: green;'>✅ AJAX handler clinica_load_doctor_patient_form este înregistrat</p>";
} else {
    echo "<p style='color: red;'>❌ AJAX handler clinica_load_doctor_patient_form NU este înregistrat</p>";
}

// Testează AJAX handler-ul pentru assistant
echo "<h2>Test AJAX Handler Assistant</h2>";

if (has_action('wp_ajax_clinica_load_assistant_patient_form')) {
    echo "<p style='color: green;'>✅ AJAX handler clinica_load_assistant_patient_form este înregistrat</p>";
} else {
    echo "<p style='color: red;'>❌ AJAX handler clinica_load_assistant_patient_form NU este înregistrat</p>";
}

// Testează dacă dashboard-urile se încarcă
echo "<h2>Test Încărcare Dashboard-uri</h2>";

$dashboard_classes = array(
    'Clinica_Doctor_Dashboard',
    'Clinica_Assistant_Dashboard'
);

foreach ($dashboard_classes as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>✅ $class se încarcă corect</p>";
        
        // Testează dacă metoda ajax_load_patient_form există
        if (method_exists($class, 'ajax_load_patient_form')) {
            echo "<p style='color: green;'>✅ Metoda ajax_load_patient_form există în $class</p>";
        } else {
            echo "<p style='color: red;'>❌ Metoda ajax_load_patient_form NU există în $class</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $class nu se încarcă</p>";
    }
}

// Testează JavaScript-ul
echo "<h2>Test JavaScript</h2>";

$js_files = array(
    'assets/js/doctor-dashboard.js',
    'assets/js/assistant-dashboard.js'
);

foreach ($js_files as $js_file) {
    $file_path = plugin_dir_path(__FILE__) . $js_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        if (strpos($content, 'loadPatientForm') !== false) {
            echo "<p style='color: green;'>✅ Funcția loadPatientForm există în $js_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Funcția loadPatientForm NU există în $js_file</p>";
        }
        
        if (strpos($content, 'showModal') !== false) {
            echo "<p style='color: green;'>✅ Funcția showModal există în $js_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Funcția showModal NU există în $js_file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul $js_file nu există</p>";
    }
}

// Testează CSS-ul pentru modal
echo "<h2>Test CSS Modal</h2>";

$css_files = array(
    'assets/css/doctor-dashboard.css',
    'assets/css/assistant-dashboard.css'
);

foreach ($css_files as $css_file) {
    $file_path = plugin_dir_path(__FILE__) . $css_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        if (strpos($content, 'clinica-modal') !== false) {
            echo "<p style='color: green;'>✅ Stilurile clinica-modal există în $css_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Stilurile clinica-modal NU există în $css_file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul $css_file nu există</p>";
    }
}

// Testează AJAX call-ul direct
echo "<h2>Test AJAX Call Direct</h2>";

// Simulează un AJAX call pentru doctor
$_POST['action'] = 'clinica_load_doctor_patient_form';
$_POST['nonce'] = wp_create_nonce('clinica_doctor_nonce');

ob_start();
do_action('wp_ajax_clinica_load_doctor_patient_form');
$response = ob_get_clean();

if (!empty($response)) {
    echo "<p style='color: green;'>✅ AJAX call-ul returnează răspuns</p>";
    echo "<p>Răspuns: " . substr($response, 0, 100) . "...</p>";
} else {
    echo "<p style='color: red;'>❌ AJAX call-ul nu returnează răspuns</p>";
}

echo "<h2>Link-uri de Test</h2>";
echo "<p><a href='" . home_url('/dashboard-doctor/') . "' target='_blank'>Test Dashboard Doctor</a></p>";
echo "<p><a href='" . home_url('/dashboard-asistent/') . "' target='_blank'>Test Dashboard Assistant</a></p>";

echo "<h2>Instrucțiuni de Testare</h2>";
echo "<ol>";
echo "<li>Accesează dashboard-ul de doctor sau asistent</li>";
echo "<li>Apasă butonul 'Pacient Nou' sau 'Adaugă Pacient'</li>";
echo "<li>Verifică dacă formularul apare într-un modal/popup</li>";
echo "<li>Verifică dacă modalul se închide când apeși X sau în afara lui</li>";
echo "</ol>";

echo "<h2>Status Final</h2>";
echo "<p>Dacă toate testele trec cu ✅, formularul ar trebui să apară în modal.</p>";
echo "<p>Dacă nu funcționează, verifică consola browser-ului pentru erori JavaScript.</p>";
?> 