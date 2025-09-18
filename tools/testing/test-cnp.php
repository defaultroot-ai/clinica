<?php
/**
 * Script pentru testarea CNP-ului
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

// Încarcă clasele
require_once('includes/class-clinica-cnp-validator.php');
require_once('includes/class-clinica-cnp-parser.php');

$cnp = '1800404080170';

echo '<h1>Test CNP: ' . $cnp . '</h1>';

// Testează validarea
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);

echo '<h2>Rezultat Validare:</h2>';
echo '<pre>' . print_r($result, true) . '</pre>';

// Testează parsarea
$parser = new Clinica_CNP_Parser();
$parsed = $parser->parse_cnp($cnp);

echo '<h2>Rezultat Parsare:</h2>';
echo '<pre>' . print_r($parsed, true) . '</pre>';

// Testează algoritmul de control manual
echo '<h2>Test Algoritm Control:</h2>';
$control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
$sum = 0;

for ($i = 0; $i < 12; $i++) {
    $sum += $cnp[$i] * $control_digits[$i];
    echo "Cifra $i: {$cnp[$i]} × {$control_digits[$i]} = " . ($cnp[$i] * $control_digits[$i]) . '<br>';
}

echo "Suma totală: $sum<br>";
$control_digit = $sum % 11;
if ($control_digit == 10) {
    $control_digit = 1;
}
echo "Cifra de control calculată: $control_digit<br>";
echo "Cifra de control din CNP: {$cnp[12]}<br>";
echo "Valid: " . ($control_digit == $cnp[12] ? 'DA' : 'NU') . '<br>';

echo '<p><a href="' . admin_url() . '">← Înapoi la Admin</a></p>';
?> 