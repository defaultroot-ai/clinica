<?php
/**
 * Test rapid pentru AJAX handlers
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test AJAX Handlers</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Test 1: Verifică dacă hook-urile AJAX sunt înregistrate
echo "<h2>Test 1: Verificare Hook-uri AJAX</h2>";

$ajax_actions = array(
    'clinica_search_patients_suggestions' => 'Căutare sugestii pacienți',
    'clinica_search_families_suggestions' => 'Căutare sugestii familii'
);

foreach ($ajax_actions as $action => $description) {
    if (has_action("wp_ajax_$action")) {
        echo "<p class='success'>✅ $description - hook înregistrat</p>";
    } else {
        echo "<p class='error'>❌ $description - hook NU înregistrat</p>";
    }
}

// Test 2: Verifică dacă metodele există
echo "<h2>Test 2: Verificare Metode</h2>";

$plugin_instance = Clinica_Plugin::get_instance();
$methods = array(
    'ajax_search_patients_suggestions' => 'Metodă căutare pacienți',
    'ajax_search_families_suggestions' => 'Metodă căutare familii'
);

foreach ($methods as $method => $description) {
    if (method_exists($plugin_instance, $method)) {
        echo "<p class='success'>✅ $description - metodă găsită</p>";
    } else {
        echo "<p class='error'>❌ $description - metodă NU găsită</p>";
    }
}

// Test 3: Test direct al metodelor
echo "<h2>Test 3: Test Direct Metode</h2>";

// Simulează POST data pentru test
$_POST['nonce'] = wp_create_nonce('clinica_search_nonce');
$_POST['search_term'] = 'test';
$_POST['search_type'] = 'search-input';

try {
    // Testează metoda de căutare pacienți
    ob_start();
    $plugin_instance->ajax_search_patients_suggestions();
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p class='success'>✅ Metoda ajax_search_patients_suggestions funcționează</p>";
        echo "<p class='info'>Output: " . htmlspecialchars(substr($output, 0, 200)) . "...</p>";
    } else {
        echo "<p class='error'>❌ Metoda ajax_search_patients_suggestions nu returnează nimic</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la testarea metodei: " . $e->getMessage() . "</p>";
}

// Test 4: Verifică dacă scripturile sunt încărcate
echo "<h2>Test 4: Verificare Scripturi</h2>";

$admin_scripts_hook = 'toplevel_page_clinica-patients';
$admin_scripts_method = new ReflectionMethod('Clinica_Plugin', 'admin_scripts');
$admin_scripts_method->setAccessible(true);

try {
    $admin_scripts_method->invoke($plugin_instance, $admin_scripts_hook);
    echo "<p class='success'>✅ Metoda admin_scripts funcționează</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la admin_scripts: " . $e->getMessage() . "</p>";
}

// Test 5: Verifică dacă fișierele există
echo "<h2>Test 5: Verificare Fișiere</h2>";

$files = array(
    CLINICA_PLUGIN_PATH . 'assets/js/admin.js' => 'admin.js',
    CLINICA_PLUGIN_PATH . 'assets/css/admin.css' => 'admin.css'
);

foreach ($files as $file_path => $file_name) {
    if (file_exists($file_path)) {
        echo "<p class='success'>✅ $file_name există</p>";
    } else {
        echo "<p class='error'>❌ $file_name NU există</p>";
    }
}

// Test 6: Verifică dacă ajaxurl este disponibil
echo "<h2>Test 6: Verificare ajaxurl</h2>";

$ajaxurl = admin_url('admin-ajax.php');
echo "<p class='info'>ajaxurl: $ajaxurl</p>";

// Test 7: Verifică dacă nonce-urile sunt generate corect
echo "<h2>Test 7: Verificare Nonce-uri</h2>";

$nonces = array(
    'clinica_search_nonce' => wp_create_nonce('clinica_search_nonce'),
    'clinica_family_nonce' => wp_create_nonce('clinica_family_nonce')
);

foreach ($nonces as $nonce_name => $nonce_value) {
    if ($nonce_value) {
        echo "<p class='success'>✅ $nonce_name: " . substr($nonce_value, 0, 10) . "...</p>";
    } else {
        echo "<p class='error'>❌ $nonce_name: NU generat</p>";
    }
}

echo "<h2>Rezumat</h2>";
echo "<p class='info'>Toate testele au fost finalizate. Verificați rezultatele de mai sus.</p>";

echo "<h3>Pentru a testa autosuggest:</h3>";
echo "<ol>";
echo "<li>Deschide pagina de pacienți</li>";
echo "<li>Deschide Developer Tools (F12)</li>";
echo "<li>Mergi la tab-ul Console</li>";
echo "<li>Începe să scrii în câmpul de căutare</li>";
echo "<li>Verifică mesajele din console</li>";
echo "</ol>";

echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Deschide Pagina Pacienți</a></p>";
?> 