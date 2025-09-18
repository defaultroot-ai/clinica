<?php
/**
 * Test pentru logica de validare CNP
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== Test Logica Validare CNP ===\n\n";

// Testează CNP-uri cu lungimi diferite
$test_cnps = [
    '1' => 'O cifră',
    '12' => 'Două cifre',
    '123' => 'Trei cifre',
    '123456789012' => '12 cifre',
    '1234567890123' => '13 cifre (invalid)',
    '1800404080170' => '13 cifre (valid)',
    '180040408017' => '12 cifre (incomplet)',
    '18004040801700' => '14 cifre (prea mult)',
    '180040408017a' => '12 cifre + literă',
    '1800404080170a' => '13 cifre + literă'
];

foreach ($test_cnps as $cnp => $description) {
    echo "Test: $description\n";
    echo "CNP: $cnp\n";
    echo "Lungime: " . strlen($cnp) . "\n";
    
    // Verifică lungimea
    if (strlen($cnp) !== 13) {
        echo "Status: Nu se validează (lungime != 13)\n";
    } else {
        // Verifică dacă conține doar cifre
        if (!preg_match('/^\d{13}$/', $cnp)) {
            echo "Status: Invalid (conține caractere non-numerice)\n";
        } else {
            // Validează CNP-ul
            $validator = new Clinica_CNP_Validator();
            $result = $validator->validate_cnp($cnp);
            echo "Status: " . ($result['valid'] ? 'VALID' : 'INVALID') . "\n";
            
            if ($result['valid']) {
                $parser = new Clinica_CNP_Parser();
                $parsed = $parser->parse_cnp($cnp);
                echo "  - Data nașterii: " . $parsed['birth_date'] . "\n";
                echo "  - Sex: " . $parsed['gender'] . "\n";
                echo "  - Vârsta: " . $parsed['age'] . "\n";
            } else {
                echo "  - Eroare: " . $result['error'] . "\n";
            }
        }
    }
    echo "\n";
}

echo "=== Test Completat ===\n";
?> 