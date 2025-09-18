<?php
echo "=== TEST REGEX SEPARATORI CORECTAT ===\n\n";

$test_phones = array(
    '0756.248.957',    // România cu puncte
    '0756-248-957',    // România cu liniuțe
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

echo "\n=== TEST REGEX COMBINAT SIMPLU ===\n";
$regex_combinat = '/^(\+40|0)[0-9]{2}[.\-][0-9]{3}[.\-][0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_combinat, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX ALTERNATIV ===\n";
$regex_alt = '/^(\+40|0)[0-9]{2}[.\-][0-9]{3}[.\-][0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_alt, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST REGEX CU ESCAPE ===\n";
$regex_escape = '/^(\+40|0)[0-9]{2}[\.\-][0-9]{3}[\.\-][0-9]{3}$/';
foreach ($test_phones as $phone) {
    $matches = preg_match($regex_escape, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
}

echo "\n=== TEST COMPLET ===\n"; 