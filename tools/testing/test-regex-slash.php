<?php
echo "=== TESTE REGEX PENTRU SLASH-URI ===\n\n";

$test_phones = array(
    '0740521639/0746527152',   // România cu slash-uri
    '0766488134 / 0743973015', // România cu slash-uri și spații
    '+40 752 840 973',         // România internațional cu spații
);

foreach ($test_phones as $phone) {
    echo "Testez: '{$phone}'\n";
    
    // Test 1: Format românesc cu slash-uri (două telefoane separate)
    $pattern1 = '/^(\+40|\+4|0|4|40|0040)[0-9]{9}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/';
    $match1 = preg_match($pattern1, $phone);
    echo "  Pattern 1 (slash-uri simple): " . ($match1 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Test 2: Format românesc cu slash-uri și spații în primul telefon
    $pattern2 = '/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/';
    $match2 = preg_match($pattern2, $phone);
    echo "  Pattern 2 (slash-uri + spații primul): " . ($match2 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Test 3: Format românesc cu slash-uri și spații în ambele telefoane
    $pattern3 = '/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
    $match3 = preg_match($pattern3, $phone);
    echo "  Pattern 3 (slash-uri + spații ambele): " . ($match3 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    // Test 4: Format românesc cu spații
    $pattern4 = '/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/';
    $match4 = preg_match($pattern4, $phone);
    echo "  Pattern 4 (spații simple): " . ($match4 ? '✅ MATCH' : '❌ NO MATCH') . "\n";
    
    echo "\n";
}

echo "=== ANALIZĂ PROBLEMĂ ===\n";
echo "Văd că regex-urile nu funcționează corect. Să verific de ce...\n";

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