<?php
/**
 * Test simplu pentru CNP
 */

$cnp = '1800404080170';

echo "Test CNP: $cnp\n\n";

// Testează algoritmul de control
$control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
$sum = 0;

echo "Calculul cifrei de control:\n";
for ($i = 0; $i < 12; $i++) {
    $product = $cnp[$i] * $control_digits[$i];
    $sum += $product;
    echo "Cifra $i: {$cnp[$i]} × {$control_digits[$i]} = $product\n";
}

echo "\nSuma totală: $sum\n";
$control_digit = $sum % 11;
if ($control_digit == 10) {
    $control_digit = 1;
}
echo "Cifra de control calculată: $control_digit\n";
echo "Cifra de control din CNP: {$cnp[12]}\n";
echo "Valid: " . ($control_digit == $cnp[12] ? 'DA' : 'NU') . "\n";

// Testează data nașterii
$year = substr($cnp, 1, 2);
$month = substr($cnp, 3, 2);
$day = substr($cnp, 5, 2);
$first_digit = $cnp[0];

echo "\nData nașterii:\n";
echo "An: $year\n";
echo "Lună: $month\n";
echo "Zi: $day\n";
echo "Prima cifră: $first_digit\n";

// Determină secolul
$century = '';
switch ($first_digit) {
    case '1':
    case '2':
        $century = '19';
        break;
    case '3':
    case '4':
        $century = '18';
        break;
    case '5':
    case '6':
        $century = '20';
        break;
    case '7':
    case '8':
        $century = '19';
        break;
    case '9':
        $century = '19';
        break;
    case '0':
        $century = '20';
        break;
    default:
        $century = '20';
}

$full_year = $century . $year;
echo "Secol: $century\n";
echo "An complet: $full_year\n";
echo "Data completă: $full_year-$month-$day\n";

// Testează sexul
$gender = '';
if (in_array($first_digit, ['1', '3', '5', '7', '9'])) {
    $gender = 'male';
} elseif (in_array($first_digit, ['2', '4', '6', '8'])) {
    $gender = 'female';
}

echo "\nSex: $gender\n";
?> 