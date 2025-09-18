<?php
echo "=== TEST REGEX PENTRU FORMATE TELEFOANE ===\n\n";

$test_phones = array(
    '0756248957',      // România fără separatori
    '0756.248.957',    // România cu puncte
    '0756-248-957',    // România cu liniuțe
    '+40756248957',    // România internațional
);

echo "=== TEST REGEX CU PUNCTE ===\n";
$regex_puncte = '/^(\+40|0)[0-9]{2}\.[0-9]{3}\.[0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_puncte, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX CU LINIUȚE ===\n";
$regex_linute = '/^(\+40|0)[0-9]{2}-[0-9]{3}-[0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_linute, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX FĂRĂ SEPARATORI ===\n";
$regex_fara = '/^(\+40|0)[0-9]{9}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_fara, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX COMBINAT ===\n";
$regex_combinat = '/^(\+40|0)[0-9]{2}[.\-]?[0-9]{3}[.\-]?[0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_combinat, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX ALTERNATIV ===\n";
$regex_alt = '/^(\+40|0)[0-9]{2}([.\-]?[0-9]{3}){2}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_alt, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST COMPLET ===\n"; 