<?php
echo "=== DEBUG REGEX FINAL ===\n\n";

$test_phones = array(
    '0740521639/0746527152',   // România cu slash-uri
    '0766488134 / 0743973015', // România cu slash-uri și spații
    '+40 752 840 973',         // România internațional cu spații
);

foreach ($test_phones as $phone) {
    echo "Testez: '{$phone}'\n";
    
    // Test regex pentru slash-uri
    $pattern = '/^(\+40|\+4|0|4|40|0040)[0-9]{9}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/';
    $match = preg_match($pattern, $phone);
    echo "  Regex slash-uri: " . ($match ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Test regex pentru spații
    $pattern2 = '/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
    $match2 = preg_match($pattern2, $phone);
    echo "  Regex spații: " . ($match2 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Test regex pentru internațional cu spații
    $pattern3 = '/^\+40\s[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
    $match3 = preg_match($pattern3, $phone);
    echo "  Regex internațional spații: " . ($match3 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    echo "\n";
}

echo "=== ANALIZĂ PROBLEMĂ ===\n";
echo "Văd că regex-ul pentru slash-uri nu funcționează. Să verific de ce...\n";

// Test specific pentru primul telefon
$phone1 = '0740521639/0746527152';
echo "Test specific pentru: '{$phone1}'\n";

// Verifică dacă începe cu prefix românesc
$prefixes = array('+40', '+4', '0', '4', '40', '0040');
foreach ($prefixes as $prefix) {
    if (strpos($phone1, $prefix) === 0) {
        echo "  Găsit prefix: '{$prefix}'\n";
        break;
    }
}

// Verifică structura
$parts = explode('/', $phone1);
echo "  Parti după /: " . count($parts) . "\n";
foreach ($parts as $i => $part) {
    echo "    Part {$i}: '" . trim($part) . "' (lungime: " . strlen(trim($part)) . ")\n";
}

echo "\n=== SOLUȚIE ===\n";
echo "Problema este că regex-ul este prea strict. Să fac o versiune mai flexibilă.\n";

// Test cu regex mai flexibil
$flexible_pattern = '/^(\+40|\+4|0|4|40|0040)[0-9]{9}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/';
$match = preg_match($flexible_pattern, $phone1);
echo "Regex flexibil pentru '{$phone1}': " . ($match ? '✅ MATCH' : '❌ NO MATCH') . "\n";

// Test cu regex pentru spații
$space_pattern = '/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
$phone2 = '0746 143 029';
$match2 = preg_match($space_pattern, $phone2);
echo "Regex spații pentru '{$phone2}': " . ($match2 ? '✅ MATCH' : '❌ NO MATCH') . "\n";

// Test cu regex pentru internațional cu spații
$int_pattern = '/^\+40\s[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
$phone3 = '+40 752 840 973';
$match3 = preg_match($int_pattern, $phone3);
echo "Regex internațional pentru '{$phone3}': " . ($match3 ? '✅ MATCH' : '❌ NO MATCH') . "\n"; 