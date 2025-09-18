<?php
/**
 * Test rapid pentru AJAX handler generare parolă
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST AJAX GENERARE PAROLĂ ===\n\n";

// 1. Verifică dacă clasa există
echo "1. Verificare clasă:\n";
if (class_exists('Clinica_Patient_Creation_Form')) {
    echo "   ✓ Clinica_Patient_Creation_Form există\n";
} else {
    echo "   ✗ Clinica_Patient_Creation_Form NU există\n";
    exit;
}

// 2. Verifică dacă metoda există
echo "\n2. Verificare metodă:\n";
$patient_form = new Clinica_Patient_Creation_Form();
if (method_exists($patient_form, 'ajax_generate_password')) {
    echo "   ✓ ajax_generate_password există\n";
} else {
    echo "   ✗ ajax_generate_password NU există\n";
    exit;
}

// 3. Testează AJAX handler-ul
echo "\n3. Test AJAX handler:\n";

// Simulează POST data
$_POST['action'] = 'clinica_generate_password';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';
$_POST['method'] = 'cnp';

// Activează error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capturează output-ul
ob_start();

try {
    // Apelează metoda direct
    $patient_form->ajax_generate_password();
    
    $output = ob_get_clean();
    echo "   ✓ Output: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETAT ===\n";
?> 