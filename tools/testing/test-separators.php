<?php
echo "=== TEST REGEX SEPARATORI ===\n\n";

$test_phones = array(
    '0756.248.957',    // România cu puncte
    '0756-248-957',    // România cu liniuțe
);

$regex_separatori = '/^(\+40|0)[0-9]{2}[.\-][0-9]{3}[.\-][0-9]{3}$/';

echo "Regex: " . $regex_separatori . "\n\n";

foreach ($test_phones as $phone) {
    $matches = preg_match($regex_separatori, $phone);
    echo "Telefon: '{$phone}' -> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Testează și cu preg_match_all pentru a vedea ce se întâmplă
    $matches_all = preg_match_all($regex_separatori, $phone, $matches_array);
    echo "  preg_match_all: " . $matches_all . "\n";
    if ($matches_all > 0) {
        print_r($matches_array);
    }
    echo "\n";
}

// Testează regex-ul pas cu pas
echo "=== TEST PAS CU PAS ===\n";
foreach ($test_phones as $phone) {
    echo "Telefon: '{$phone}'\n";
    
    // Testează începutul
    $start_match = preg_match('/^(\+40|0)/', $phone);
    echo "  Început (+40|0): " . ($start_match ? '✅' : '❌') . "\n";
    
    // Testează cu puncte
    $dot_match = preg_match('/^(\+40|0)[0-9]{2}\.[0-9]{3}\.[0-9]{3}$/', $phone);
    echo "  Cu puncte: " . ($dot_match ? '✅' : '❌') . "\n";
    
    // Testează cu liniuțe
    $dash_match = preg_match('/^(\+40|0)[0-9]{2}-[0-9]{3}-[0-9]{3}$/', $phone);
    echo "  Cu liniuțe: " . ($dash_match ? '✅' : '❌') . "\n";
    
    echo "\n";
}

echo "=== TEST COMPLET ===\n"; 