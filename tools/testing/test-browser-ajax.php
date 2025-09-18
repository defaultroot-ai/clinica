<?php
/**
 * Test care simulează cererea AJAX din browser
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST BROWSER AJAX ===\n\n";

// 1. Verifică nonce-ul
echo "1. Verificare nonce:\n";
$nonce = wp_create_nonce('clinica_nonce');
echo "   Nonce generat: $nonce\n";

if (wp_verify_nonce($nonce, 'clinica_nonce')) {
    echo "   ✓ Nonce valid\n";
} else {
    echo "   ✗ Nonce invalid\n";
}

echo "\n";

// 2. Simulează cererea AJAX din browser
echo "2. Simulare cerere AJAX:\n";

// Setează headers ca în browser
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simulează POST data exact ca în browser
$_POST['action'] = 'clinica_generate_password';
$_POST['nonce'] = $nonce;
$_POST['cnp'] = '1800404080170';
$_POST['method'] = 'cnp';

echo "   Action: {$_POST['action']}\n";
echo "   Nonce: {$_POST['nonce']}\n";
echo "   CNP: {$_POST['cnp']}\n";
echo "   Method: {$_POST['method']}\n";

echo "\n";

// 3. Testează validarea nonce-ului în handler
echo "3. Test validare nonce în handler:\n";

$patient_form = new Clinica_Patient_Creation_Form();

// Testează validarea nonce-ului direct
if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
    echo "   ✓ Nonce valid pentru clinica_nonce\n";
} else {
    echo "   ✗ Nonce invalid pentru clinica_nonce\n";
}

if (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
    echo "   ✓ Nonce valid pentru clinica_frontend_nonce\n";
} else {
    echo "   ✗ Nonce invalid pentru clinica_frontend_nonce\n";
}

echo "\n";

// 4. Testează handler-ul cu nonce-ul din browser
echo "4. Test handler cu nonce browser:\n";

ob_start();

try {
    $patient_form->ajax_generate_password();
    $output = ob_get_clean();
    echo "   ✓ Output: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Testează cu nonce frontend
echo "5. Test cu nonce frontend:\n";

$_POST['nonce'] = wp_create_nonce('clinica_frontend_nonce');

ob_start();

try {
    $patient_form->ajax_generate_password();
    $output = ob_get_clean();
    echo "   ✓ Output cu frontend nonce: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare cu frontend nonce: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETAT ===\n";
?> 