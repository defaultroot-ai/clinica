<?php
/**
 * Debug pentru eroarea AJAX 500
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== DEBUG AJAX ERROR 500 ===\n\n";

// 1. Verifică dacă clasele există
echo "1. Verificare clase:\n";

if (class_exists('Clinica_CNP_Validator')) {
    echo "   ✓ Clinica_CNP_Validator există\n";
} else {
    echo "   ✗ Clinica_CNP_Validator NU există\n";
}

if (class_exists('Clinica_CNP_Parser')) {
    echo "   ✓ Clinica_CNP_Parser există\n";
} else {
    echo "   ✗ Clinica_CNP_Parser NU există\n";
}

if (class_exists('Clinica_Patient_Creation_Form')) {
    echo "   ✓ Clinica_Patient_Creation_Form există\n";
} else {
    echo "   ✗ Clinica_Patient_Creation_Form NU există\n";
}

echo "\n";

// 2. Testează validarea CNP direct
echo "2. Test validare CNP direct:\n";
try {
    $validator = new Clinica_CNP_Validator();
    $result = $validator->validate_cnp('1800404080170');
    echo "   ✓ Validare CNP funcționează: " . ($result['valid'] ? 'valid' : 'invalid') . "\n";
} catch (Exception $e) {
    echo "   ✗ Eroare la validare CNP: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Testează parsarea CNP direct
echo "3. Test parsare CNP direct:\n";
try {
    $parser = new Clinica_CNP_Parser();
    $parsed = $parser->parse_cnp('1800404080170');
    echo "   ✓ Parsare CNP funcționează:\n";
    echo "     - Data nașterii: {$parsed['birth_date']}\n";
    echo "     - Sex: {$parsed['gender']}\n";
    echo "     - Vârsta: {$parsed['age']}\n";
} catch (Exception $e) {
    echo "   ✗ Eroare la parsare CNP: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Testează AJAX handler-ul cu error reporting activat
echo "4. Test AJAX handler cu error reporting:\n";

// Activează error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulează POST data
$_POST['nonce'] = wp_create_nonce('clinica_nonce');
$_POST['cnp'] = '1800404080170';

// Capturează output-ul și erorile
ob_start();

try {
    $patient_form = new Clinica_Patient_Creation_Form();
    $patient_form->ajax_validate_cnp();
    $output = ob_get_clean();
    
    echo "   ✓ AJAX handler funcționează\n";
    echo "   Output: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la AJAX handler: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// 5. Verifică dacă există erori în output buffer
if (ob_get_level() > 0) {
    $remaining_output = ob_get_clean();
    if (!empty($remaining_output)) {
        echo "5. Output rămas în buffer:\n";
        echo $remaining_output . "\n";
    }
}

echo "\n";

// 6. Testează dacă wp_send_json funcționează
echo "6. Test wp_send_json:\n";
try {
    // Simulează o cerere AJAX
    $_POST['nonce'] = wp_create_nonce('clinica_nonce');
    $_POST['cnp'] = '1800404080170';
    
    // Testează fără a apela wp_send_json
    if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
        echo "   ✓ Nonce valid\n";
        
        $cnp = sanitize_text_field($_POST['cnp']);
        if (!empty($cnp)) {
            echo "   ✓ CNP validat: $cnp\n";
            
            $validator = new Clinica_CNP_Validator();
            $result = $validator->validate_cnp($cnp);
            
            if ($result['valid']) {
                echo "   ✓ CNP este valid\n";
                
                $parser = new Clinica_CNP_Parser();
                $parsed_data = $parser->parse_cnp($cnp);
                echo "   ✓ Date parsate: " . json_encode($parsed_data) . "\n";
                
                $result['parsed_data'] = $parsed_data;
                echo "   ✓ Rezultat final: " . json_encode($result) . "\n";
            } else {
                echo "   ✗ CNP invalid: {$result['error']}\n";
            }
        } else {
            echo "   ✗ CNP gol\n";
        }
    } else {
        echo "   ✗ Nonce invalid\n";
    }
} catch (Exception $e) {
    echo "   ✗ Eroare: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETAT ===\n";
?> 