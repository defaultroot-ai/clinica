<?php
/**
 * Verificare modificări implementate
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== Verificare Modificări Implementate ===\n\n";

// 1. Verifică hook-urile AJAX
echo "1. Hook-uri AJAX:\n";
global $wp_filter;

$ajax_hooks = [
    'wp_ajax_clinica_validate_cnp',
    'wp_ajax_nopriv_clinica_validate_cnp',
    'wp_ajax_clinica_create_patient',
    'wp_ajax_nopriv_clinica_create_patient',
    'wp_ajax_clinica_generate_password',
    'wp_ajax_nopriv_clinica_generate_password'
];

$all_hooks_ok = true;
foreach ($ajax_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "   ✓ $hook\n";
    } else {
        echo "   ✗ $hook - LIPSESC\n";
        $all_hooks_ok = false;
    }
}

echo "\n";

// 2. Verifică fișierele modificate
echo "2. Fișiere modificate:\n";

$files_to_check = [
    'assets/js/frontend.js' => [
        'cnp.length !== 13',
        'Introduceți toate cele 13 cifre',
        'cnpValidationRequest',
        'action: \'clinica_validate_cnp\''
    ],
    'assets/css/frontend.css' => [
        'cnp-feedback',
        'valid-feedback',
        'invalid-feedback',
        'info-feedback'
    ],
    'includes/class-clinica-patient-creation-form.php' => [
        'clinica_frontend_nonce',
        'check_ajax_referer'
    ],
    'clinica.php' => [
        'filemtime',
        'CLINICA_VERSION'
    ]
];

$all_files_ok = true;
foreach ($files_to_check as $file => $checks) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $file_ok = true;
        echo "   ✓ $file:\n";
        
        foreach ($checks as $check) {
            if (strpos($content, $check) !== false) {
                echo "      ✓ $check\n";
            } else {
                echo "      ✗ $check - LIPSESC\n";
                $file_ok = false;
            }
        }
        
        if (!$file_ok) {
            $all_files_ok = false;
        }
    } else {
        echo "   ✗ $file - NU EXISTĂ\n";
        $all_files_ok = false;
    }
    echo "\n";
}

// 3. Testează validarea CNP
echo "3. Test validare CNP:\n";
$cnp = '1800404080170';
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);

if ($result['valid']) {
    echo "   ✓ CNP $cnp este valid\n";
    
    $parser = new Clinica_CNP_Parser();
    $parsed = $parser->parse_cnp($cnp);
    echo "   ✓ Parsare CNP funcționează:\n";
    echo "     - Data nașterii: {$parsed['birth_date']}\n";
    echo "     - Sex: {$parsed['gender']}\n";
    echo "     - Vârsta: {$parsed['age']}\n";
} else {
    echo "   ✗ CNP $cnp este invalid: {$result['error']}\n";
}

echo "\n";

// 4. Verifică versiunea fișierelor
echo "4. Versiune fișiere:\n";
$js_file = CLINICA_PLUGIN_PATH . 'assets/js/frontend.js';
$css_file = CLINICA_PLUGIN_PATH . 'assets/css/frontend.css';

if (file_exists($js_file)) {
    $js_time = filemtime($js_file);
    echo "   ✓ frontend.js - modificat la: " . date('Y-m-d H:i:s', $js_time) . "\n";
} else {
    echo "   ✗ frontend.js - NU EXISTĂ\n";
}

if (file_exists($css_file)) {
    $css_time = filemtime($css_file);
    echo "   ✓ frontend.css - modificat la: " . date('Y-m-d H:i:s', $css_time) . "\n";
} else {
    echo "   ✗ frontend.css - NU EXISTĂ\n";
}

echo "\n";

// 5. Rezumat
echo "=== REZUMAT ===\n";
if ($all_hooks_ok && $all_files_ok && $result['valid']) {
    echo "✅ TOATE MODIFICĂRILE SUNT IMPLEMENTATE CORECT!\n";
    echo "\nPentru a testa:\n";
    echo "1. Forțează reîncărcarea cache-ului browser-ului (Ctrl+Shift+R)\n";
    echo "2. Deschide formularul de creare pacient\n";
    echo "3. Introduce CNP-ul '1800404080170' cifră cu cifră\n";
    echo "4. Verifică că nu apar mesaje de eroare multiple\n";
} else {
    echo "❌ EXISTĂ PROBLEME CARE TREBUIE REZOLVATE:\n";
    if (!$all_hooks_ok) echo "- Hook-uri AJAX lipsesc\n";
    if (!$all_files_ok) echo "- Fișiere nu sunt modificate corect\n";
    if (!$result['valid']) echo "- Validarea CNP nu funcționează\n";
}

echo "\n=== Test Completat ===\n";
?> 