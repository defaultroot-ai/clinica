<?php
echo "=== TEST REGEX PAS CU PAS PENTRU SEPARATORI ===\n\n";

$phone = '0756.248.957';

echo "Telefon testat: '{$phone}'\n";
echo "Lungime: " . strlen($phone) . "\n\n";

// Testează fiecare parte a regex-ului
echo "1. Test început (+40|0): ";
$start_match = preg_match('/^(\+40|0)/', $phone);
echo ($start_match ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "2. Test început + 4 cifre: ";
$start_4digits = preg_match('/^(\+40|0)[0-9]{4}/', $phone);
echo ($start_4digits ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "3. Test început + 4 cifre + punct: ";
$start_4digits_dot = preg_match('/^(\+40|0)[0-9]{4}\./', $phone);
echo ($start_4digits_dot ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "4. Test început + 4 cifre + punct + 3 cifre: ";
$start_4digits_dot_3digits = preg_match('/^(\+40|0)[0-9]{4}\.[0-9]{3}/', $phone);
echo ($start_4digits_dot_3digits ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "5. Test început + 4 cifre + punct + 3 cifre + punct: ";
$start_4digits_dot_3digits_dot = preg_match('/^(\+40|0)[0-9]{4}\.[0-9]{3}\./', $phone);
echo ($start_4digits_dot_3digits_dot ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "6. Test complet cu puncte: ";
$complete_dots = preg_match('/^(\+40|0)[0-9]{4}\.[0-9]{3}\.[0-9]{3}$/', $phone);
echo ($complete_dots ? '✅ MATCH' : '❌ NO MATCH') . "\n";

echo "\n=== ANALIZĂ CARACTERE ===\n";
echo "Primele 4 caractere: '" . substr($phone, 0, 4) . "'\n";
echo "Caracterul 4: '" . substr($phone, 4, 1) . "' (cod ASCII: " . ord(substr($phone, 4, 1)) . ")\n";
echo "Caracterul 5: '" . substr($phone, 5, 1) . "' (cod ASCII: " . ord(substr($phone, 5, 1)) . ")\n";

echo "\n=== TEST COMPLET ===\n"; 