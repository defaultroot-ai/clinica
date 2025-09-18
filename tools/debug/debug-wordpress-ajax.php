<?php
/**
 * Debug pentru procesarea AJAX în WordPress
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== DEBUG WORDPRESS AJAX ===\n\n";

// 1. Verifică configurația WordPress
echo "1. Configurație WordPress:\n";
echo "   WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF') . "\n";
echo "   WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . "\n";
echo "   WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'ON' : 'OFF') . "\n";

echo "\n";

// 2. Verifică dacă AJAX este activat
echo "2. Verificare AJAX:\n";
if (function_exists('wp_doing_ajax')) {
    echo "   ✓ wp_doing_ajax funcționează\n";
} else {
    echo "   ✗ wp_doing_ajax NU funcționează\n";
}

if (function_exists('wp_ajax_url')) {
    echo "   ✓ wp_ajax_url funcționează\n";
} else {
    echo "   ✓ admin_url('admin-ajax.php') funcționează\n";
}

echo "\n";

// 3. Verifică hook-urile AJAX
echo "3. Verificare hook-uri AJAX:\n";
global $wp_filter;

$ajax_hooks = array(
    'wp_ajax_clinica_validate_cnp',
    'wp_ajax_nopriv_clinica_validate_cnp',
    'wp_ajax_clinica_generate_password',
    'wp_ajax_nopriv_clinica_generate_password'
);

foreach ($ajax_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "   ✓ $hook - ÎNREGISTRAT\n";
    } else {
        echo "   ✗ $hook - NU ESTE ÎNREGISTRAT\n";
    }
}

echo "\n";

// 4. Testează procesarea AJAX
echo "4. Test procesare AJAX:\n";

// Simulează o cerere AJAX
$_POST['action'] = 'clinica_validate_cnp';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

// Activează error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capturează output-ul
ob_start();

try {
    // Simulează procesarea AJAX
    if (isset($_POST['action'])) {
        $action = sanitize_text_field($_POST['action']);
        
        if (has_action("wp_ajax_$action")) {
            echo "   ✓ Acțiunea $action există\n";
            
            // Apelează acțiunea
            do_action("wp_ajax_$action");
            
            $output = ob_get_clean();
            echo "   ✓ Output acțiune: $output\n";
        } else {
            echo "   ✗ Acțiunea $action NU există\n";
            ob_end_clean();
        }
    } else {
        echo "   ✗ Nu există acțiune în POST\n";
        ob_end_clean();
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la procesare: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Verifică dacă există erori în log-uri
echo "5. Verificare log-uri:\n";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -10);
    
    echo "   Ultimele 10 linii din debug.log:\n";
    foreach ($recent_lines as $line) {
        if (!empty(trim($line))) {
            echo "   $line\n";
        }
    }
} else {
    echo "   Nu există debug.log\n";
}

echo "\n";

// 6. Testează cu headers corecte
echo "6. Test cu headers AJAX:\n";

// Simulează headers AJAX
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST['action'] = 'clinica_validate_cnp';
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

ob_start();

try {
    if (isset($_POST['action'])) {
        $action = sanitize_text_field($_POST['action']);
        
        if (has_action("wp_ajax_$action")) {
            do_action("wp_ajax_$action");
            
            $output = ob_get_clean();
            echo "   ✓ Output cu headers: $output\n";
        } else {
            echo "   ✗ Acțiunea cu headers NU există\n";
            ob_end_clean();
        }
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare cu headers: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETAT ===\n";
?> 