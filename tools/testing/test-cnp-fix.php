<?php
/**
 * Test pentru validarea CNP după corectări
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Testează validarea CNP
$cnp = '1800404080170';
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);

echo "Test validare CNP: $cnp\n";
echo "Rezultat: " . ($result['valid'] ? 'VALID' : 'INVALID') . "\n";

if ($result['valid']) {
    $parser = new Clinica_CNP_Parser();
    $parsed = $parser->parse_cnp($cnp);
    
    echo "Date parsate:\n";
    echo "- Data nașterii: " . $parsed['birth_date'] . "\n";
    echo "- Sex: " . $parsed['gender'] . "\n";
    echo "- Vârsta: " . $parsed['age'] . "\n";
    
    $password_generator = new Clinica_Password_Generator();
    $password = $password_generator->generate_password_from_cnp($cnp, 'cnp');
    echo "- Parolă generată: $password\n";
} else {
    echo "Eroare: " . $result['error'] . "\n";
}

echo "\nTest completat!\n";
?> 