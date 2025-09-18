<?php
echo "=== TEST REGEX FINAL PENTRU SEPARATORI ===\n\n";

$test_phones = array(
    '0756.248.957',    // România cu puncte
    '0756-248-957',    // România cu liniuțe
);

echo "=== TEST REGEX CU PUNCTE (4 cifre) ===\n";
$regex_puncte = '/^(\+40|0)[0-9]{4}\.[0-9]{3}\.[0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_puncte, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX CU LINIUȚE (4 cifre) ===\n";
$regex_linute = '/^(\+40|0)[0-9]{4}-[0-9]{3}-[0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_linute, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX COMBINAT (4 cifre) ===\n";
$regex_combinat = '/^(\+40|0)[0-9]{4}[.\-][0-9]{3}[.\-][0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_combinat, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST COMPLET ===\n"; 