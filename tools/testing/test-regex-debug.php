<?php
echo "=== DEBUG REGEX PAS CU PAS ===\n\n";

$phone = '0756.248.957';

echo "Telefon testat: '{$phone}'\n\n";

// Testează fiecare parte a regex-ului
echo "1. Test început (+40|0): ";
$start_match = preg_match('/^(\+40|0)/', $phone);
echo ($start_match ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "2. Test început + 2 cifre: ";
$start_2digits = preg_match('/^(\+40|0)[0-9]{2}/', $phone);
echo ($start_2digits ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "3. Test început + 2 cifre + punct: ";
$start_2digits_dot = preg_match('/^(\+40|0)[0-9]{2}\./', $phone);
echo ($start_2digits_dot ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "4. Test început + 2 cifre + punct + 3 cifre: ";
$start_2digits_dot_3digits = preg_match('/^(\+40|0)[0-9]{2}\.[0-9]{3}/', $phone);
echo ($start_2digits_dot_3digits ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "5. Test început + 2 cifre + punct + 3 cifre + punct: ";
$start_2digits_dot_3digits_dot = preg_match('/^(\+40|0)[0-9]{2}\.[0-9]{3}\./', $phone);
echo ($start_2digits_dot_3digits_dot ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "6. Test complet cu puncte: ";
$complete_dots = preg_match('/^(\+40|0)[0-9]{2}\.[0-9]{3}\.[0-9]{3}$/', $phone);
echo ($complete_dots ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "\n=== TEST CU SUBSTR ===\n";
echo "Lungime telefon: " . strlen($phone) . "\n";
echo "Primele 4 caractere: '" . substr($phone, 0, 4) . "'\n";
echo "Caracterul 4: '" . substr($phone, 4, 1) . "'\n";
echo "Caracterul 5: '" . substr($phone, 5, 1) . "'\n";

echo "\n=== TEST COMPLET ===\n"; 