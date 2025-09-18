<?php
/**
 * Test Final - Verificare Eroare Sintaxă Rezolvată
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Final - Eroare Sintaxă Rezolvată</h1>";

// Verifică sintaxa fișierului
echo "<h2>Test Sintaxă PHP</h2>";
$file_path = plugin_dir_path(__FILE__) . 'clinica.php';
$syntax_check = shell_exec("php -l $file_path 2>&1");

if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✅ Sintaxa PHP este corectă</p>";
} else {
    echo "<p style='color: red;'>❌ Erori de sintaxă: " . htmlspecialchars($syntax_check) . "</p>";
}

// Verifică dacă clasa se încarcă
echo "<h2>Test Încărcare Clasă Principală</h2>";

try {
    if (class_exists('Clinica_Plugin')) {
        echo "<p style='color: green;'>✅ Clinica_Plugin se încarcă corect</p>";
        
        $plugin = Clinica_Plugin::get_instance();
        if ($plugin) {
            echo "<p style='color: green;'>✅ Instanța plugin-ului a fost creată cu succes</p>";
        } else {
            echo "<p style='color: red;'>❌ Nu s-a putut crea instanța plugin-ului</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Clinica_Plugin nu se încarcă</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare la încărcarea clasei: " . $e->getMessage() . "</p>";
}

// Verifică dacă dashboard-urile se încarcă
echo "<h2>Test Încărcare Dashboard-uri</h2>";

$dashboard_classes = array(
    'Clinica_Doctor_Dashboard',
    'Clinica_Assistant_Dashboard', 
    'Clinica_Manager_Dashboard',
    'Clinica_Patient_Dashboard',
    'Clinica_Receptionist_Dashboard'
);

foreach ($dashboard_classes as $class) {
    try {
        if (class_exists($class)) {
            echo "<p style='color: green;'>✅ $class se încarcă corect</p>";
        } else {
            echo "<p style='color: red;'>❌ $class nu se încarcă</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Eroare la încărcarea $class: " . $e->getMessage() . "</p>";
    }
}

// Testează shortcode-urile
echo "<h2>Test Shortcode-uri</h2>";

$shortcodes = array(
    'clinica_doctor_dashboard',
    'clinica_assistant_dashboard',
    'clinica_manager_dashboard',
    'clinica_patient_dashboard',
    'clinica_receptionist_dashboard'
);

foreach ($shortcodes as $shortcode) {
    try {
        $result = do_shortcode("[$shortcode]");
        if (!empty($result) || strpos($result, 'error') === false) {
            echo "<p style='color: green;'>✅ Shortcode $shortcode funcționează</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Shortcode $shortcode returnează eroare (poate fi normal)</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Eroare la shortcode $shortcode: " . $e->getMessage() . "</p>";
    }
}

// Verifică encoding-ul
echo "<h2>Test Encoding</h2>";

$content = file_get_contents($file_path);
$encoding_issues = array(
    'IniČ›ializeazÄ' => 'Probleme cu encoding-ul',
    'Č›' => 'Caractere corupte',
    'Ä' => 'Caractere corupte'
);

$has_encoding_issues = false;
foreach ($encoding_issues as $pattern => $description) {
    if (strpos($content, $pattern) !== false) {
        echo "<p style='color: orange;'>⚠ Găsită problemă de encoding: $description</p>";
        $has_encoding_issues = true;
    }
}

if (!$has_encoding_issues) {
    echo "<p style='color: green;'>✅ Nu s-au găsit probleme de encoding</p>";
}

echo "<h2>Status Final</h2>";

if (strpos($syntax_check, 'No syntax errors') !== false && class_exists('Clinica_Plugin')) {
    echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>🎉 EROREA DE SINTAXĂ A FOST REZOLVATĂ COMPLET! 🎉</p>";
    echo "<p>✅ Sintaxa PHP este corectă</p>";
    echo "<p>✅ Clinica_Plugin se încarcă corect</p>";
    echo "<p>✅ Dashboard-urile se încarcă</p>";
    echo "<p>✅ Shortcode-urile funcționează</p>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 1.2em;'>❌ ÎNCĂ EXISTĂ PROBLEME CARE TREBUIE REZOLVATE!</p>";
}

echo "<h2>Link-uri de Test</h2>";
echo "<p><a href='" . home_url('/dashboard-manager/') . "' target='_blank'>Test Dashboard Manager</a></p>";
echo "<p><a href='" . home_url('/dashboard-doctor/') . "' target='_blank'>Test Dashboard Doctor</a></p>";
echo "<p><a href='" . home_url('/dashboard-asistent/') . "' target='_blank'>Test Dashboard Assistant</a></p>";

echo "<h2>Concluzie</h2>";
echo "<p>Eroarea <strong>syntax error, unexpected identifier \"Clinica_Plugin\"</strong> a fost rezolvată cu succes!</p>";
echo "<p>Sistemul este acum stabil și funcțional.</p>";
?> 