<?php
/**
 * Test pentru verificarea eliminării metodei duplicată
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Eliminare Metodă Duplicată</h1>";

// Verifică dacă metoda render_manager_dashboard există o singură dată
$file_path = plugin_dir_path(__FILE__) . 'clinica.php';
$content = file_get_contents($file_path);

$function_pattern = '/public function render_manager_dashboard\s*\([^)]*\)\s*\{/';
preg_match_all($function_pattern, $content, $matches);

echo "<h2>Rezultate Verificare</h2>";
echo "<p>Găsite " . count($matches[0]) . " declarații ale metodei render_manager_dashboard</p>";

if (count($matches[0]) == 1) {
    echo "<p style='color: green;'>✅ Metoda render_manager_dashboard există o singură dată - CORECT!</p>";
} elseif (count($matches[0]) == 0) {
    echo "<p style='color: red;'>❌ Metoda render_manager_dashboard nu există - TREBUIE ADĂUGATĂ!</p>";
} else {
    echo "<p style='color: red;'>❌ Metoda render_manager_dashboard există de " . count($matches[0]) . " ori - DUPLICATĂ!</p>";
}

// Verifică dacă shortcode-ul este înregistrat
$shortcode_pattern = '/add_shortcode\s*\(\s*[\'"]clinica_manager_dashboard[\'"]\s*,/';
preg_match_all($shortcode_pattern, $content, $shortcode_matches);

echo "<p>Găsite " . count($shortcode_matches[0]) . " înregistrări ale shortcode-ului clinica_manager_dashboard</p>";

if (count($shortcode_matches[0]) == 1) {
    echo "<p style='color: green;'>✅ Shortcode-ul clinica_manager_dashboard este înregistrat corect</p>";
} else {
    echo "<p style='color: red;'>❌ Problema cu înregistrarea shortcode-ului</p>";
}

// Testează dacă clasa se încarcă fără erori
echo "<h2>Test Încărcare Clasă</h2>";

try {
    if (class_exists('Clinica_Manager_Dashboard')) {
        echo "<p style='color: green;'>✅ Clinica_Manager_Dashboard există</p>";
        
        // Testează dacă metoda statică există
        if (method_exists('Clinica_Manager_Dashboard', 'get_dashboard_html')) {
            echo "<p style='color: green;'>✅ Metoda statică get_dashboard_html există</p>";
        } else {
            echo "<p style='color: red;'>❌ Metoda statică get_dashboard_html nu există</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Clinica_Manager_Dashboard nu există</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare la încărcarea clasei: " . $e->getMessage() . "</p>";
}

// Testează shortcode-ul
echo "<h2>Test Shortcode</h2>";

try {
    $shortcode_result = do_shortcode('[clinica_manager_dashboard]');
    if (!empty($shortcode_result)) {
        echo "<p style='color: green;'>✅ Shortcode-ul funcționează</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Shortcode-ul nu returnează conținut (poate fi normal dacă nu ești autentificat)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare la shortcode: " . $e->getMessage() . "</p>";
}

echo "<h2>Status Final</h2>";

if (count($matches[0]) == 1 && count($shortcode_matches[0]) == 1) {
    echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>🎉 EROREA DE METODĂ DUPLICATĂ A FOST REZOLVATĂ CU SUCCES! 🎉</p>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 1.2em;'>❌ ÎNCĂ EXISTĂ PROBLEME CARE TREBUIE REZOLVATE!</p>";
}

echo "<p><a href='" . home_url('/dashboard-manager/') . "' target='_blank'>Test Dashboard Manager</a></p>";
?> 