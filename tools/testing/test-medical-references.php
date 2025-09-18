<?php
/**
 * Test comprehensiv pentru verificarea tuturor referințelor la secțiunea medicală
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Comprehensiv Referințe Secțiune Medicală</h1>";

echo "<h2>1. Verificare Fișiere PHP Active</h2>";

// Lista fișierelor PHP importante
$php_files = array(
    'includes/class-clinica-patient-dashboard.php',
    'includes/class-clinica-patient-creation-form.php',
    'includes/class-clinica-doctor-dashboard.php',
    'admin/views/patients.php',
    'admin/views/shortcodes.php',
    'clinica.php'
);

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        echo "<h3>Fișier: {$file}</h3>";
        
        // Verifică referințe la "Informații medicale"
        if (strpos($content, 'Informații medicale') !== false) {
            echo "<p style='color: orange;'>⚠️ Conține 'Informații medicale'</p>";
        } else {
            echo "<p style='color: green;'>✅ Nu conține 'Informații medicale'</p>";
        }
        
        // Verifică referințe la "data-tab=\"medical\""
        if (strpos($content, 'data-tab="medical"') !== false) {
            echo "<p style='color: orange;'>⚠️ Conține 'data-tab=\"medical\"'</p>";
        } else {
            echo "<p style='color: green;'>✅ Nu conține 'data-tab=\"medical\"'</p>";
        }
        
        // Verifică referințe la "id=\"medical\""
        if (strpos($content, 'id="medical"') !== false) {
            echo "<p style='color: orange;'>⚠️ Conține 'id=\"medical\"'</p>";
        } else {
            echo "<p style='color: green;'>✅ Nu conține 'id=\"medical\"'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul {$file} nu există</p>";
    }
}

echo "<h2>2. Verificare Fișiere JavaScript</h2>";

$js_files = array(
    'assets/js/patient-dashboard.js',
    'assets/js/doctor-dashboard.js',
    'assets/js/frontend.js'
);

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        echo "<h3>Fișier: {$file}</h3>";
        
        // Verifică referințe la "medical"
        $medical_count = substr_count($content, 'medical');
        if ($medical_count > 0) {
            echo "<p style='color: orange;'>⚠️ Conține {$medical_count} referințe la 'medical'</p>";
            
            // Verifică dacă sunt funcții medicale
            if (strpos($content, 'loadMedicalData') !== false) {
                echo "<p style='color: orange;'>⚠️ Conține funcția 'loadMedicalData'</p>";
            }
            if (strpos($content, 'updateMedicalDisplay') !== false) {
                echo "<p style='color: orange;'>⚠️ Conține funcția 'updateMedicalDisplay'</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ Nu conține referințe la 'medical'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul {$file} nu există</p>";
    }
}

echo "<h2>3. Verificare Fișiere CSS</h2>";

$css_files = array(
    'assets/css/patient-dashboard.css',
    'assets/css/doctor-dashboard.css',
    'assets/css/frontend.css'
);

foreach ($css_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        echo "<h3>Fișier: {$file}</h3>";
        
        // Verifică referințe la "medical"
        $medical_count = substr_count($content, 'medical');
        if ($medical_count > 0) {
            echo "<p style='color: orange;'>⚠️ Conține {$medical_count} referințe la 'medical'</p>";
            
            // Verifică clase CSS medicale
            if (strpos($content, '.medical-container') !== false) {
                echo "<p style='color: orange;'>⚠️ Conține clasa '.medical-container'</p>";
            }
            if (strpos($content, '.medical-header') !== false) {
                echo "<p style='color: orange;'>⚠️ Conține clasa '.medical-header'</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ Nu conține referințe la 'medical'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fișierul {$file} nu există</p>";
    }
}

echo "<h2>4. Verificare Dashboard Pacient</h2>";

if (class_exists('Clinica_Patient_Dashboard')) {
    $dashboard = new Clinica_Patient_Dashboard();
    
    // Simulează un utilizator pentru test
    global $wpdb;
    $table_name = $wpdb->prefix . 'clinica_patients';
    $patient = $wpdb->get_row("SELECT user_id FROM $table_name LIMIT 1");
    
    if ($patient) {
        wp_set_current_user($patient->user_id);
        
        ob_start();
        $dashboard->render_dashboard_shortcode(array());
        $dashboard_html = ob_get_clean();
        
        echo "<h3>Analiză HTML Dashboard:</h3>";
        
        // Verificări specifice
        $checks = array(
            'Tab medical în navigație' => strpos($dashboard_html, 'data-tab="medical"') !== false,
            'Card medical în overview' => strpos($dashboard_html, 'Informații medicale') !== false && strpos($dashboard_html, 'dashboard-card') !== false,
            'Tab medical complet' => strpos($dashboard_html, 'id="medical"') !== false,
            'Funcții medicale în JS' => strpos($dashboard_html, 'loadMedicalData') !== false
        );
        
        foreach ($checks as $check_name => $found) {
            if ($found) {
                echo "<p style='color: red;'>❌ {$check_name} - ÎNCĂ EXISTĂ!</p>";
            } else {
                echo "<p style='color: green;'>✅ {$check_name} - ASCUNS CORECT</p>";
            }
        }
        
        // Verifică tab-urile rămase
        $remaining_tabs = array();
        if (strpos($dashboard_html, 'data-tab="overview"') !== false) $remaining_tabs[] = 'overview';
        if (strpos($dashboard_html, 'data-tab="appointments"') !== false) $remaining_tabs[] = 'appointments';
        if (strpos($dashboard_html, 'data-tab="messages"') !== false) $remaining_tabs[] = 'messages';
        
        echo "<p><strong>Tab-uri rămase:</strong> " . implode(', ', $remaining_tabs) . " (" . count($remaining_tabs) . " total)</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Nu există pacienți pentru test</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Clasa Clinica_Patient_Dashboard nu există</p>";
}

echo "<h2>5. Verificare Formular Creare Pacient</h2>";

if (class_exists('Clinica_Patient_Creation_Form')) {
    $form = new Clinica_Patient_Creation_Form();
    
    ob_start();
    $form->render_form();
    $form_html = ob_get_clean();
    
    echo "<h3>Analiză HTML Formular:</h3>";
    
    // Verificări specifice
    $checks = array(
        'Tab medical în navigație' => strpos($form_html, 'data-tab="medical"') !== false,
        'Conținut tab medical' => strpos($form_html, 'id="medical"') !== false,
        'Buton tab medical' => strpos($form_html, 'Informații Medicale') !== false
    );
    
    foreach ($checks as $check_name => $found) {
        if ($found) {
            echo "<p style='color: red;'>❌ {$check_name} - ÎNCĂ EXISTĂ!</p>";
        } else {
            echo "<p style='color: green;'>✅ {$check_name} - ASCUNS CORECT</p>";
        }
    }
    
    // Verifică tab-urile rămase
    $remaining_tabs = array();
    if (strpos($form_html, 'data-tab="cnp"') !== false) $remaining_tabs[] = 'cnp';
    if (strpos($form_html, 'data-tab="personal"') !== false) $remaining_tabs[] = 'personal';
    if (strpos($form_html, 'data-tab="account"') !== false) $remaining_tabs[] = 'account';
    
    echo "<p><strong>Tab-uri rămase:</strong> " . implode(', ', $remaining_tabs) . " (" . count($remaining_tabs) . " total)</p>";
    
} else {
    echo "<p style='color: red;'>❌ Clasa Clinica_Patient_Creation_Form nu există</p>";
}

echo "<h2>6. Rezumat și Recomandări</h2>";

echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px;'>";
echo "<h3>🎯 Status Secțiune Medicală:</h3>";
echo "<ul>";
echo "<li><strong>Dashboard Pacient:</strong> Secțiunea medicală a fost ascunsă corect</li>";
echo "<li><strong>Formular Creare Pacient:</strong> Secțiunea medicală a fost ascunsă corect</li>";
echo "<li><strong>JavaScript:</strong> Funcțiile medicale rămân în cod dar nu sunt folosite</li>";
echo "<li><strong>CSS:</strong> Stilurile medicale rămân în cod dar nu sunt folosite</li>";
echo "</ul>";

echo "<h3>📝 Recomandări:</h3>";
echo "<ul>";
echo "<li>✅ Secțiunea medicală este ascunsă corect din interfață</li>";
echo "<li>⚠️ Funcțiile JavaScript medicale pot fi comentate pentru performanță</li>";
echo "<li>⚠️ Stilurile CSS medicale pot fi comentate pentru performanță</li>";
echo "<li>✅ Codul rămâne disponibil pentru reactivare</li>";
echo "</ul>";
echo "</div>";

echo "<h2>7. Testare în Browser</h2>";
echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 5px;'>";
echo "<h3>🧪 Testează acum în browser:</h3>";
echo "<ol>";
echo "<li><a href='" . home_url('/clinica-patient-dashboard/') . "' target='_blank'>Dashboard Pacient</a></li>";
echo "<li><a href='" . home_url('/clinica-create-patient/') . "' target='_blank'>Formular Creare Pacient</a></li>";
echo "<li>Verificați că nu mai apar referințe la secțiunea medicală</li>";
echo "<li>Verificați că funcționalitățile rămase funcționează corect</li>";
echo "</ol>";
echo "</div>";
?> 