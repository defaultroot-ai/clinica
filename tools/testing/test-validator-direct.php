<?php
/**
 * Test direct clasa validator CNP
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$cnp = '1800404080170';

echo "=== Test Direct Validator CNP ===\n";
echo "CNP: $cnp\n\n";

// Testează direct clasa
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);

echo "Rezultat validare:\n";
echo "- Valid: " . ($result['valid'] ? 'DA' : 'NU') . "\n";
echo "- Mesaj: " . ($result['error'] ?? 'N/A') . "\n";
echo "- Tip: " . ($result['type'] ?? 'N/A') . "\n";

if ($result['valid']) {
    echo "\n✅ CNP-ul este valid!\n";
    
    // Testează și parser-ul
    $parser = new Clinica_CNP_Parser();
    $parsed = $parser->parse_cnp($cnp);
    
    echo "\nDate parsate:\n";
    echo "- Data nașterii: " . $parsed['birth_date'] . "\n";
    echo "- Sex: " . $parsed['gender'] . "\n";
    echo "- Vârsta: " . $parsed['age'] . "\n";
} else {
    echo "\n❌ CNP-ul este invalid!\n";
}

echo "\n=== Test Completat ===\n";
?> 