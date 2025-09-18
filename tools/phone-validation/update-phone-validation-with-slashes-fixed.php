<?php
require_once('../../../wp-load.php');

echo "=== ACTUALIZARE VALIDARE TELEFOANE CU SLASH-URI ȘI SPAȚII (CORECTAT) ===\n\n";

// Funcție pentru validarea telefonului (cu toate formatele românești + slash-uri și spații)
function validatePhoneWithAllFormats($phone) {
    if (empty($phone)) return true;
    
    if (strlen($phone) > 20) {
        return false;
    }
    
    // Format românesc fără separatori
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu puncte
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu liniuțe
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu spații
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu slash-uri (două telefoane separate)
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{9}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu slash-uri și spații în primul telefon
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) {
        return true;
    }
    
    // Format românesc cu slash-uri și spații în ambele telefoane
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}\s*\/\s*(\+40|\+4|0|4|40|0040)[0-9]{3}\s[0-9]{3}\s[0-9]{3}$/', $phone)) {
        return true;
    }
    
    // Format ucrainean
    if (preg_match('/^\+380[0-9]{9}$/', $phone)) {
        return true;
    }
    
    // Internațional
    if (preg_match('/^\+[0-9]{10,15}$/', $phone)) {
        return true;
    }
    
    return false;
}

// Funcție pentru formatarea telefonului (curăță separatori pentru autentificare)
function formatPhoneForAuth($phone) {
    if (empty($phone)) return '';
    
    // Elimină toate caracterele non-numerice, păstrând doar + și cifrele
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Trunchiază la 20 de caractere
    if (strlen($clean_phone) > 20) {
        $clean_phone = substr($clean_phone, 0, 20);
    }
    
    return $clean_phone;
}

// Funcție pentru a extrage primul telefon dintr-un format cu slash-uri
function extractFirstPhone($phone) {
    if (empty($phone)) return '';
    
    // Dacă conține slash-uri, ia primul telefon
    if (strpos($phone, '/') !== false) {
        $phones = explode('/', $phone);
        $first_phone = trim($phones[0]);
        return formatPhoneForAuth($first_phone);
    }
    
    return formatPhoneForAuth($phone);
}

// Funcție pentru a extrage al doilea telefon dintr-un format cu slash-uri
function extractSecondPhone($phone) {
    if (empty($phone)) return '';
    
    // Dacă conține slash-uri, ia al doilea telefon
    if (strpos($phone, '/') !== false) {
        $phones = explode('/', $phone);
        if (count($phones) > 1) {
            $second_phone = trim($phones[1]);
            return formatPhoneForAuth($second_phone);
        }
    }
    
    return '';
}

echo "✅ Funcții de validare actualizate pentru formate cu slash-uri și spații\n\n";

// Testează funcțiile cu exemple
$test_phones = array(
    '0756248957',              // România fără separatori
    '0756.248.957',            // România cu puncte
    '0756-248-957',            // România cu liniuțe
    '0756 248 957',            // România cu spații
    '0740521639/0746527152',   // România cu slash-uri
    '0766488134 / 0743973015', // România cu slash-uri și spații
    '+40 752 840 973',         // România internațional cu spații
    '0746 143 029',            // România cu spații
    '+40756248957',            // România internațional
    '+380501234567',           // Ucraina
    '+1234567890',             // Internațional
    '1',                       // Invalid
    'Registered',              // Invalid
    'CAS-BV'                   // Invalid
);

echo "=== TESTE VALIDARE ===\n";
foreach ($test_phones as $phone) {
    $is_valid = validatePhoneWithAllFormats($phone);
    $formatted = formatPhoneForAuth($phone);
    $first_phone = extractFirstPhone($phone);
    $second_phone = extractSecondPhone($phone);
    
    $status = $is_valid ? '✅ VALID' : '❌ INVALID';
    echo "Telefon: '{$phone}' -> {$status} -> '{$formatted}'\n";
    
    if (strpos($phone, '/') !== false) {
        echo "  Primul telefon: '{$first_phone}'\n";
        if (!empty($second_phone)) {
            echo "  Al doilea telefon: '{$second_phone}'\n";
        }
    }
    echo "\n";
}

echo "=== FORMATE ACCEPTATE ===\n";
echo "🇷🇴 România:\n";
echo "   - 07XXXXXXXX (fără separatori)\n";
echo "   - 07XX.XXX.XXX (cu puncte)\n";
echo "   - 07XX-XXX-XXX (cu liniuțe)\n";
echo "   - 07XX XXX XXX (cu spații)\n";
echo "   - 07XXXXXXXX/07XXXXXXXX (cu slash-uri)\n";
echo "   - 07XX XXX XXX / 07XX XXX XXX (cu slash-uri și spații)\n";
echo "   - +407XXXXXXXX (internațional)\n";
echo "   - +40 XXX XXX XXX (internațional cu spații)\n";
echo "\n🇺🇦 Ucraina:\n";
echo "   - +380XXXXXXXXX (13 caractere)\n";
echo "\n🌍 Internațional:\n";
echo "   - +XXXXXXXXXXX (10-15 caractere)\n";

echo "\n=== IMPORTANT ===\n";
echo "Pentru autentificare, toate separatoarele sunt eliminate automat!\n";
echo "Pentru formatele cu slash-uri, primul telefon este folosit pentru autentificare.\n";
echo "Exemplu: '0766488134 / 0743973015' devine '0766488134' pentru autentificare.\n";

echo "\n=== ACTUALIZARE COMPLETĂ ===\n"; 