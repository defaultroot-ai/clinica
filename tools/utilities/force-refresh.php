<?php
/**
 * Script pentru a forța reîncărcarea fișierelor
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== Forțare Reîncărcare Fișiere ===\n\n";

// 1. Curăță cache-ul WordPress
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✓ Cache WordPress curățat\n";
}

// 2. Curăță cache-ul de opțiuni
wp_cache_delete('alloptions', 'options');
echo "✓ Cache opțiuni curățat\n";

// 3. Verifică dacă fișierele există și au conținutul corect
$files_to_check = [
    'assets/js/frontend.js' => 'cnp.length !== 13',
    'assets/css/frontend.css' => 'cnp-feedback',
    'includes/class-clinica-patient-creation-form.php' => 'clinica_frontend_nonce'
];

foreach ($files_to_check as $file => $content) {
    if (file_exists($file)) {
        $file_content = file_get_contents($file);
        if (strpos($file_content, $content) !== false) {
            echo "✓ $file - Conține modificările\n";
        } else {
            echo "✗ $file - NU conține modificările\n";
        }
    } else {
        echo "✗ $file - NU EXISTĂ\n";
    }
}

echo "\n=== Instrucțiuni pentru Browser ===\n";
echo "1. Deschide Developer Tools (F12)\n";
echo "2. Mergi la tab-ul Network\n";
echo "3. Bifează 'Disable cache'\n";
echo "4. Apasă Ctrl+F5 pentru hard refresh\n";
echo "5. Verifică dacă fișierele .js și .css se încarcă cu timestamp nou\n";

echo "\n=== Test AJAX Direct ===\n";
echo "Pentru a testa AJAX-ul direct, deschide:\n";
echo "http://localhost/plm/wp-content/plugins/clinica/test-ajax.php\n";

echo "\n=== Test Completat ===\n";
?> 