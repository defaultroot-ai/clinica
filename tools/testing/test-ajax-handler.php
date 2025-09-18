<?php
/**
 * Test AJAX handler pentru a identifica problema
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST AJAX HANDLER ===\n\n";

// 1. Înregistrează un AJAX handler de test
add_action('wp_ajax_test_ajax_handler', 'test_ajax_handler_function');
add_action('wp_ajax_nopriv_test_ajax_handler', 'test_ajax_handler_function');

function test_ajax_handler_function() {
    echo "=== AJAX HANDLER TEST ===\n";
    
    // Verifică nonce-ul
    if (!wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
        wp_send_json_error('Nonce invalid');
        return;
    }
    
    // Verifică CNP-ul
    $cnp = sanitize_text_field($_POST['cnp']);
    if (empty($cnp)) {
        wp_send_json_error('CNP gol');
        return;
    }
    
    // Validează CNP-ul
    $validator = new Clinica_CNP_Validator();
    $result = $validator->validate_cnp($cnp);
    
    if ($result['valid']) {
        // Parsează CNP-ul
        $parser = new Clinica_CNP_Parser();
        $parsed_data = $parser->parse_cnp($cnp);
        
        $result['parsed_data'] = $parsed_data;
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['error']);
    }
}

echo "1. AJAX handler înregistrat\n";

// 2. Testează handler-ul
echo "\n2. Test handler:\n";

$_POST['action'] = 'test_ajax_handler';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

// Capturează output-ul
ob_start();

try {
    // Apelează handler-ul
    do_action('wp_ajax_test_ajax_handler');
    
    $output = ob_get_clean();
    echo "   ✓ Output handler: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare handler: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETAT ===\n";
?> 