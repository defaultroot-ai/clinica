<?php
/**
 * Test Final - Verificare Formular în Modal
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Final - Formular în Modal</h1>";

// Verifică CSS-ul pentru modal
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
        
        if (strpos($content, 'clinica-modal-overlay') !== false) {
            echo "<p style='color: green;'>✅ Stilurile clinica-modal-overlay există în $css_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Stilurile clinica-modal-overlay NU există în $css_file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul $css_file nu există</p>";
    }
}

// Verifică JavaScript-ul
echo "<h2>Test JavaScript</h2>";

$js_files = array(
    'assets/js/doctor-dashboard.js',
    'assets/js/assistant-dashboard.js'
);

foreach ($js_files as $js_file) {
    $file_path = plugin_dir_path(__FILE__) . $js_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        if (strpos($content, 'showModal') !== false) {
            echo "<p style='color: green;'>✅ Funcția showModal există în $js_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Funcția showModal NU există în $js_file</p>";
        }
        
        if (strpos($content, 'clinica-modal') !== false) {
            echo "<p style='color: green;'>✅ Codul pentru clinica-modal există în $js_file</p>";
        } else {
            echo "<p style='color: red;'>❌ Codul pentru clinica-modal NU există în $js_file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul $js_file nu există</p>";
    }
}

// Verifică AJAX handlers
echo "<h2>Test AJAX Handlers</h2>";

$ajax_handlers = array(
    'clinica_load_doctor_patient_form',
    'clinica_load_assistant_patient_form'
);

foreach ($ajax_handlers as $handler) {
    if (has_action("wp_ajax_{$handler}")) {
        echo "<p style='color: green;'>✅ AJAX handler $handler este înregistrat</p>";
    } else {
        echo "<p style='color: red;'>❌ AJAX handler $handler NU este înregistrat</p>";
    }
}

// Testează formularul direct
echo "<h2>Test Formular Direct</h2>";

if (class_exists('Clinica_Patient_Creation_Form')) {
    try {
        $form = new Clinica_Patient_Creation_Form();
        $form_html = $form->render_form();
        
        if (!empty($form_html)) {
            echo "<p style='color: green;'>✅ Formularul se renderizează corect</p>";
            echo "<p>Lungimea HTML: " . strlen($form_html) . " caractere</p>";
            
            // Verifică dacă formularul conține elementele necesare
            if (strpos($form_html, 'clinica-patient-form') !== false) {
                echo "<p style='color: green;'>✅ Formularul conține clasa clinica-patient-form</p>";
            } else {
                echo "<p style='color: red;'>❌ Formularul nu conține clasa clinica-patient-form</p>";
            }
            
            if (strpos($form_html, 'cnp') !== false) {
                echo "<p style='color: green;'>✅ Formularul conține câmpul CNP</p>";
            } else {
                echo "<p style='color: red;'>❌ Formularul nu conține câmpul CNP</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Formularul nu returnează conținut</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Eroare la renderizarea formularului: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Clinica_Patient_Creation_Form nu există</p>";
}

// Testează AJAX call-ul pentru doctor
echo "<h2>Test AJAX Call Doctor</h2>";

$_POST['action'] = 'clinica_load_doctor_patient_form';
$_POST['nonce'] = wp_create_nonce('clinica_doctor_nonce');

ob_start();
do_action('wp_ajax_clinica_load_doctor_patient_form');
$response = ob_get_clean();

if (!empty($response)) {
    echo "<p style='color: green;'>✅ AJAX call-ul pentru doctor returnează răspuns</p>";
    
    // Verifică dacă răspunsul conține form_html
    if (strpos($response, 'form_html') !== false) {
        echo "<p style='color: green;'>✅ Răspunsul conține form_html</p>";
    } else {
        echo "<p style='color: red;'>❌ Răspunsul nu conține form_html</p>";
    }
} else {
    echo "<p style='color: red;'>❌ AJAX call-ul pentru doctor nu returnează răspuns</p>";
}

// Testează AJAX call-ul pentru assistant
echo "<h2>Test AJAX Call Assistant</h2>";

$_POST['action'] = 'clinica_load_assistant_patient_form';
$_POST['nonce'] = wp_create_nonce('clinica_assistant_nonce');

ob_start();
do_action('wp_ajax_clinica_load_assistant_patient_form');
$response = ob_get_clean();

if (!empty($response)) {
    echo "<p style='color: green;'>✅ AJAX call-ul pentru assistant returnează răspuns</p>";
    
    // Verifică dacă răspunsul conține form_html
    if (strpos($response, 'form_html') !== false) {
        echo "<p style='color: green;'>✅ Răspunsul conține form_html</p>";
    } else {
        echo "<p style='color: red;'>❌ Răspunsul nu conține form_html</p>";
    }
} else {
    echo "<p style='color: red;'>❌ AJAX call-ul pentru assistant nu returnează răspuns</p>";
}

echo "<h2>Link-uri de Test</h2>";
echo "<p><a href='" . home_url('/dashboard-doctor/') . "' target='_blank'>Test Dashboard Doctor</a></p>";
echo "<p><a href='" . home_url('/dashboard-asistent/') . "' target='_blank'>Test Dashboard Assistant</a></p>";

echo "<h2>Instrucțiuni de Testare Finală</h2>";
echo "<ol>";
echo "<li>Accesează dashboard-ul de doctor sau asistent</li>";
echo "<li>Apasă butonul 'Pacient Nou' sau 'Adaugă Pacient'</li>";
echo "<li>Verifică dacă formularul apare într-un modal/popup centrat</li>";
echo "<li>Verifică dacă modalul are header cu titlu și buton de închidere (X)</li>";
echo "<li>Verifică dacă modalul se închide când apeși X sau în afara lui</li>";
echo "<li>Verifică dacă formularul conține toate câmpurile necesare (CNP, nume, etc.)</li>";
echo "</ol>";

echo "<h2>Status Final</h2>";
echo "<p>Dacă toate testele trec cu ✅, formularul ar trebui să apară în modal.</p>";
echo "<p>Dacă nu funcționează, verifică:</p>";
echo "<ul>";
echo "<li>Consola browser-ului pentru erori JavaScript</li>";
echo "<li>Network tab-ul pentru erori AJAX</li>";
echo "<li>Dacă fișierele CSS și JS sunt încărcate corect</li>";
echo "</ul>";

echo "<h2>Debug JavaScript</h2>";
echo "<p>Adaugă acest cod în consola browser-ului pentru a testa manual:</p>";
echo "<pre>";
echo "// Testează dacă jQuery este disponibil\n";
echo "if (typeof jQuery !== 'undefined') {\n";
echo "    console.log('jQuery este disponibil');\n";
echo "    \n";
echo "    // Testează dacă funcția loadPatientForm există\n";
echo "    if (typeof loadPatientForm === 'function') {\n";
echo "        console.log('loadPatientForm există');\n";
echo "        loadPatientForm();\n";
echo "    } else {\n";
echo "        console.log('loadPatientForm nu există');\n";
echo "    }\n";
echo "} else {\n";
echo "    console.log('jQuery nu este disponibil');\n";
echo "}\n";
echo "</pre>";
?> 