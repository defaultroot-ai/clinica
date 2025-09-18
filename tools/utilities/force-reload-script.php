<?php
/**
 * Script pentru a forța reîncărcarea fișierelor și verificarea modificărilor
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== FORȚARE REÎNCĂRCARE ȘI VERIFICARE ===\n\n";

// 1. Forțează reîncărcarea cache-ului WordPress
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✓ Cache WordPress curățat\n";
}

// 2. Curăță cache-ul de opțiuni
wp_cache_delete('alloptions', 'options');
echo "✓ Cache opțiuni curățat\n";

// 3. Verifică dacă fișierele există și au conținutul corect
echo "\n=== VERIFICARE FIȘIERE ===\n";

$js_file = CLINICA_PLUGIN_PATH . 'assets/js/frontend.js';
$css_file = CLINICA_PLUGIN_PATH . 'assets/css/frontend.css';

if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    $js_time = filemtime($js_file);
    echo "✓ frontend.js există (modificat la: " . date('Y-m-d H:i:s', $js_time) . ")\n";
    
    // Verifică conținutul
    if (strpos($js_content, 'cnp.length !== 13') !== false) {
        echo "  ✓ Conține logica de validare la 13 cifre\n";
    } else {
        echo "  ✗ NU conține logica de validare la 13 cifre\n";
    }
    
    if (strpos($js_content, 'Introduceți toate cele 13 cifre') !== false) {
        echo "  ✓ Conține mesajul de progres\n";
    } else {
        echo "  ✗ NU conține mesajul de progres\n";
    }
    
    if (strpos($js_content, 'cnpValidationRequest') !== false) {
        echo "  ✓ Conține anularea cererilor anterioare\n";
    } else {
        echo "  ✗ NU conține anularea cererilor anterioare\n";
    }
    
    if (strpos($js_content, 'action: \'clinica_validate_cnp\'') !== false) {
        echo "  ✓ Conține acțiunea AJAX corectă\n";
    } else {
        echo "  ✗ NU conține acțiunea AJAX corectă\n";
    }
} else {
    echo "✗ frontend.js NU EXISTĂ\n";
}

if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    $css_time = filemtime($css_file);
    echo "✓ frontend.css există (modificat la: " . date('Y-m-d H:i:s', $css_time) . ")\n";
    
    if (strpos($css_content, 'cnp-feedback') !== false) {
        echo "  ✓ Conține stilurile pentru feedback CNP\n";
    } else {
        echo "  ✗ NU conține stilurile pentru feedback CNP\n";
    }
} else {
    echo "✗ frontend.css NU EXISTĂ\n";
}

// 4. Verifică hook-urile AJAX
echo "\n=== VERIFICARE HOOK-URI AJAX ===\n";
global $wp_filter;

$ajax_hooks = [
    'wp_ajax_clinica_validate_cnp',
    'wp_ajax_nopriv_clinica_validate_cnp'
];

foreach ($ajax_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "✓ $hook - ÎNREGISTRAT\n";
    } else {
        echo "✗ $hook - NU ESTE ÎNREGISTRAT\n";
    }
}

// 5. Verifică versiunea dinamică în clinica.php
echo "\n=== VERIFICARE VERSIUNE DINAMICĂ ===\n";
$clinica_file = CLINICA_PLUGIN_PATH . 'clinica.php';
if (file_exists($clinica_file)) {
    $clinica_content = file_get_contents($clinica_file);
    if (strpos($clinica_content, 'filemtime') !== false) {
        echo "✓ Versiunea dinamică este activată\n";
    } else {
        echo "✗ Versiunea dinamică NU este activată\n";
    }
} else {
    echo "✗ clinica.php NU EXISTĂ\n";
}

// 6. Generează URL-uri pentru testare
echo "\n=== URL-URI PENTRU TESTARE ===\n";
$site_url = get_site_url();
echo "Site URL: $site_url\n";
echo "Test AJAX: $site_url/wp-content/plugins/clinica/test-ajax-working.php\n";
echo "Debug CNP: $site_url/wp-content/plugins/clinica/debug-cnp-validation.php\n";
echo "Verificare modificări: $site_url/wp-content/plugins/clinica/verify-changes.php\n";

// 7. Instrucțiuni pentru browser
echo "\n=== INSTRUCȚIUNI PENTRU BROWSER ===\n";
echo "1. Deschide Developer Tools (F12)\n";
echo "2. Mergi la tab-ul Network\n";
echo "3. Bifează 'Disable cache'\n";
echo "4. Apasă Ctrl+Shift+R pentru hard refresh\n";
echo "5. Verifică că fișierele .js și .css se încarcă cu timestamp nou\n";
echo "6. Testează formularul de creare pacient\n";

// 8. Verifică dacă există conflicte cu alte plugin-uri
echo "\n=== VERIFICARE CONFLICTE ===\n";
$active_plugins = get_option('active_plugins');
echo "Plugin-uri active: " . count($active_plugins) . "\n";

foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'clinica') !== false) {
        echo "✓ Plugin Clinica activ: $plugin\n";
    }
}

echo "\n=== REZUMAT ===\n";
echo "Dacă toate verificările de mai sus sunt ✓, atunci problema este:\n";
echo "1. Cache-ul browser-ului (forțează reîncărcarea)\n";
echo "2. Fișierele nu se încarcă corect (verifică Network tab)\n";
echo "3. Există un conflict cu alt plugin\n";
echo "\nPentru a testa imediat, accesează:\n";
echo "http://localhost/plm/wp-content/plugins/clinica/debug-cnp-validation.php\n";

echo "\n=== Test Completat ===\n";
?> 