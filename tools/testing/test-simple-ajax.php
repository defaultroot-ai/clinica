<?php
/**
 * Test AJAX simplu pentru a identifica problema
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST AJAX SIMPLU ===\n\n";

// 1. Testează dacă WordPress funcționează
echo "1. Test WordPress:\n";
if (function_exists('wp_create_nonce')) {
    echo "   ✓ wp_create_nonce funcționează\n";
} else {
    echo "   ✗ wp_create_nonce NU funcționează\n";
}

if (function_exists('wp_send_json_success')) {
    echo "   ✓ wp_send_json_success funcționează\n";
} else {
    echo "   ✗ wp_send_json_success NU funcționează\n";
}

echo "\n";

// 2. Testează o cerere AJAX simplă
echo "2. Test cerere AJAX simplă:\n";

// Simulează o cerere AJAX
$_POST['action'] = 'test_simple_ajax';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['test_data'] = 'test';

// Capturează output-ul
ob_start();

try {
    // Simulează un AJAX handler simplu
    if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
        wp_send_json_success(array('message' => 'Test reușit'));
    } else {
        wp_send_json_error('Nonce invalid');
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la test AJAX: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo "   ✓ Output AJAX: $output\n";

echo "\n";

// 3. Testează cu headers corecte
echo "3. Test cu headers corecte:\n";

// Simulează headers AJAX
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST['action'] = 'test_headers_ajax';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');

ob_start();

try {
    if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
        wp_send_json_success(array('headers' => 'OK'));
    } else {
        wp_send_json_error('Headers invalid');
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la test headers: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo "   ✓ Output headers: $output\n";

echo "\n";

// 4. Testează AJAX handler-ul real cu debugging
echo "4. Test AJAX handler real cu debugging:\n";

// Activează debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_POST['action'] = 'clinica_validate_cnp';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

ob_start();

try {
    // Verifică dacă hook-ul există
    global $wp_filter;
    
    if (isset($wp_filter['wp_ajax_clinica_validate_cnp'])) {
        echo "   ✓ Hook wp_ajax_clinica_validate_cnp există\n";
        
        // Apelează hook-ul
        do_action('wp_ajax_clinica_validate_cnp');
        
        $output = ob_get_clean();
        echo "   ✓ Output hook: $output\n";
    } else {
        echo "   ✗ Hook wp_ajax_clinica_validate_cnp NU există\n";
        ob_end_clean();
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la hook: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Testează direct metoda
echo "5. Test direct metoda:\n";

$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

ob_start();

try {
    $patient_form = new Clinica_Patient_Creation_Form();
    
    // Verifică dacă metoda există
    if (method_exists($patient_form, 'ajax_validate_cnp')) {
        echo "   ✓ Metoda ajax_validate_cnp există\n";
        
        // Apelează metoda
        $patient_form->ajax_validate_cnp();
        
        $output = ob_get_clean();
        echo "   ✓ Output metodă: $output\n";
    } else {
        echo "   ✗ Metoda ajax_validate_cnp NU există\n";
        ob_end_clean();
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la metodă: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETAT ===\n";
?> 