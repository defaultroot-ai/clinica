<?php
/**
 * Test care simulează exact ce se întâmplă în browser
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST EXACT BROWSER ===\n\n";

// 1. Simulează prima cerere AJAX (validare CNP)
echo "1. Prima cerere AJAX - Validare CNP:\n";

$_POST['action'] = 'clinica_validate_cnp';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

$patient_form = new Clinica_Patient_Creation_Form();

ob_start();
$patient_form->ajax_validate_cnp();
$output1 = ob_get_clean();

echo "   Output validare CNP: $output1\n";

echo "\n";

// 2. Simulează a doua cerere AJAX (generare parolă) - EXACT ca în browser
echo "2. A doua cerere AJAX - Generare parolă:\n";

// Curăță POST data
$_POST = array();

// Setează datele pentru generare parolă
$_POST['action'] = 'clinica_generate_password';
$_POST['nonce'] = wp_create_nonce('clinica_nonce'); // Același nonce ca mai sus
$_POST['cnp'] = '1800404080170';
$_POST['method'] = 'cnp';

echo "   Action: {$_POST['action']}\n";
echo "   Nonce: {$_POST['nonce']}\n";
echo "   CNP: {$_POST['cnp']}\n";
echo "   Method: {$_POST['method']}\n";

ob_start();
$patient_form->ajax_generate_password();
$output2 = ob_get_clean();

echo "   Output generare parolă: $output2\n";

echo "\n";

// 3. Testează cu nonce diferit
echo "3. Test cu nonce nou:\n";

$_POST['nonce'] = wp_create_nonce('clinica_nonce'); // Nonce nou

ob_start();
$patient_form->ajax_generate_password();
$output3 = ob_get_clean();

echo "   Output cu nonce nou: $output3\n";

echo "\n";

// 4. Testează validarea nonce-ului direct
echo "4. Test validare nonce direct:\n";

$nonce1 = wp_create_nonce('clinica_nonce');
$nonce2 = wp_create_nonce('clinica_nonce');

echo "   Nonce 1: $nonce1\n";
echo "   Nonce 2: $nonce2\n";
echo "   Nonce 1 valid pentru clinica_nonce: " . (wp_verify_nonce($nonce1, 'clinica_nonce') ? 'DA' : 'NU') . "\n";
echo "   Nonce 2 valid pentru clinica_nonce: " . (wp_verify_nonce($nonce2, 'clinica_nonce') ? 'DA' : 'NU') . "\n";

echo "\n=== TEST COMPLETAT ===\n";
?> 