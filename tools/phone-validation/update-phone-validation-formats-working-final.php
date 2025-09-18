<?php
require_once('../../../wp-load.php');

echo "=== ACTUALIZARE VALIDARE TELEFOANE CU FORMATE ROMÂNEȘTI EXTINSE (FINAL FUNCȚIONAL) ===\n\n";

// Funcție pentru validarea telefonului (cu formate românești extinse)
function validatePhoneWithFormats($phone) {
    if (empty($phone)) return true;
    
    // Verifică lungimea (inclusiv caracterele speciale)
    if (strlen($phone) > 20) {
        return false;
    }
    
    // Verifică formatele valide:
    // 1. România: +40 sau 0 urmat de 9 cifre (cu sau fără separatori)
    // 2. Ucraina: +380 urmat de 9 cifre
    // 3. Internațional: + urmat de 10-15 cifre
    
    // Format românesc fără separatori: 07xxxxxxxx
    if (preg_match('/^(\+40|0)[0-9]{9}$/', $phone)) {
        return true; // Format românesc fără separatori
    }
    
    // Format românesc cu puncte: 07xx.xxx.xxx
    if (preg_match('/^(\+40|0)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) {
        return true; // Format românesc cu puncte
    }
    
    // Format românesc cu liniuțe: 07xx-xxx-xxx
    if (preg_match('/^(\+40|0)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) {
        return true; // Format românesc cu liniuțe
    }
    
    // Format ucrainean
    if (preg_match('/^\+380[0-9]{9}$/', $phone)) {
        return true; // Format ucrainean
    }
    
    // Verifică dacă este un telefon internațional valid (alte țări)
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

// Funcție pentru a determina țara telefonului
function getPhoneCountry($phone) {
    if (empty($phone)) return 'UNKNOWN';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (preg_match('/^(\+40|0)/', $clean_phone)) {
        return 'ROMANIA';
    }
    
    if (preg_match('/^\+380/', $clean_phone)) {
        return 'UKRAINE';
    }
    
    if (preg_match('/^\+/', $clean_phone)) {
        return 'INTERNATIONAL';
    }
    
    return 'UNKNOWN';
}

// Funcție pentru a determina tipul de eroare
function getPhoneErrorType($phone) {
    if (empty($phone)) return 'GOL';
    
    if (strlen($phone) > 20) {
        return 'PREA LUNG';
    }
    
    // Verifică dacă începe cu formatele valide
    if (preg_match('/^(\+40|0)/', $phone)) {
        // Verifică dacă are formatul corect cu puncte
        if (preg_match('/^(\+40|0)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) {
            return 'VALID ROMÂNIA CU PUNCTE';
        }
        // Verifică dacă are formatul corect cu liniuțe
        if (preg_match('/^(\+40|0)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) {
            return 'VALID ROMÂNIA CU LINIUȚE';
        }
        // Verifică dacă are formatul corect fără separatori
        if (preg_match('/^(\+40|0)[0-9]{9}$/', $phone)) {
            return 'VALID ROMÂNIA FĂRĂ SEPARATORI';
        }
        return 'FORMAT INVALID ROMÂNIA';
    }
    
    if (preg_match('/^\+380/', $phone)) {
        if (strlen(preg_replace('/[^0-9+]/', '', $phone)) !== 13) {
            return 'LUNGIME INVALIDĂ UCRAINA';
        }
        return 'VALID UCRAINA';
    }
    
    if (preg_match('/^\+/', $phone)) {
        $clean_length = strlen(preg_replace('/[^0-9+]/', '', $phone));
        if ($clean_length < 10 || $clean_length > 15) {
            return 'LUNGIME INVALIDĂ INTERNAȚIONAL';
        }
        return 'VALID INTERNAȚIONAL';
    }
    
    return 'FORMAT INVALID';
}

echo "✅ Funcții de validare actualizate pentru formate românești extinse\n\n";

// Testează funcțiile cu exemple
$test_phones = array(
    '0756248957',      // România fără separatori
    '0756.248.957',    // România cu puncte
    '0756-248-957',    // România cu liniuțe
    '+40756248957',    // România internațional
    '+380501234567',   // Ucraina
    '+1234567890',     // Internațional
    '0756.248.95',     // Prea scurt
    '0756.248.9578',   // Prea lung
    '075624895',       // Prea scurt fără separatori
    '07562489578',     // Prea lung fără separatori
    '1',               // Invalid
    'Registered',      // Invalid
    'CAS-BV'          // Invalid
);

echo "=== TESTE VALIDARE ===\n";
foreach ($test_phones as $phone) {
    $is_valid = validatePhoneWithFormats($phone);
    $country = getPhoneCountry($phone);
    $formatted = formatPhoneForAuth($phone);
    $error_type = getPhoneErrorType($phone);
    
    $status = $is_valid ? '✅ VALID' : '❌ INVALID';
    echo "Telefon: '{$phone}' -> {$status} ({$country}) -> '{$formatted}' -> {$error_type}\n";
}

echo "\n=== FORMATE ACCEPTATE ===\n";
echo "🇷🇴 România:\n";
echo "   - 07XXXXXXXX (fără separatori)\n";
echo "   - 07XX.XXX.XXX (cu puncte)\n";
echo "   - 07XX-XXX-XXX (cu liniuțe)\n";
echo "   - +407XXXXXXXX (internațional)\n";
echo "\n🇺🇦 Ucraina:\n";
echo "   - +380XXXXXXXXX (13 caractere)\n";
echo "\n🌍 Internațional:\n";
echo "   - +XXXXXXXXXXX (10-15 caractere)\n";

echo "\n=== IMPORTANT ===\n";
echo "Pentru autentificare, toate separatoarele sunt eliminate automat!\n";
echo "Exemplu: '0756.248.957' devine '0756248957' pentru autentificare.\n";

echo "\n=== ACTUALIZARE COMPLETĂ ===\n"; 