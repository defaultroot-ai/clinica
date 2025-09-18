<?php
require_once('../../../wp-load.php');

echo "=== ACTUALIZARE VALIDARE TELEFOANE PENTRU UCRAINA ===\n\n";

// Funcție pentru validarea telefonului (actualizată pentru Ucraina)
function validatePhoneWithUkraine($phone) {
    if (empty($phone)) return true;
    
    // Elimină spațiile și caracterele speciale
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Verifică lungimea
    if (strlen($clean_phone) > 20) {
        return false;
    }
    
    // Verifică formatele valide:
    // 1. România: +40 sau 0 urmat de 9 cifre
    // 2. Ucraina: +380 urmat de 9 cifre
    if (preg_match('/^(\+40|0)[0-9]{9}$/', $clean_phone)) {
        return true; // Format românesc
    }
    
    if (preg_match('/^\+380[0-9]{9}$/', $clean_phone)) {
        return true; // Format ucrainean
    }
    
    // Verifică dacă este un telefon internațional valid (alte țări)
    if (preg_match('/^\+[0-9]{10,15}$/', $clean_phone)) {
        return true;
    }
    
    return false;
}

// Funcție pentru formatarea telefonului
function formatPhoneWithUkraine($phone) {
    if (empty($phone)) return '';
    
    // Elimină spațiile și caracterele speciale
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

echo "✅ Funcții de validare actualizate pentru Ucraina\n\n";

// Testează funcțiile cu exemple
$test_phones = array(
    '0756248957',      // România
    '+40756248957',    // România internațional
    '+380501234567',   // Ucraina
    '+380671234567',   // Ucraina
    '+380991234567',   // Ucraina
    '+1234567890',     // Internațional
    '1',               // Invalid
    'Registered',      // Invalid
    'CAS-BV'          // Invalid
);

echo "=== TESTE VALIDARE ===\n";
foreach ($test_phones as $phone) {
    $is_valid = validatePhoneWithUkraine($phone);
    $country = getPhoneCountry($phone);
    $formatted = formatPhoneWithUkraine($phone);
    
    $status = $is_valid ? '✅ VALID' : '❌ INVALID';
    echo "Telefon: '{$phone}' -> {$status} ({$country}) -> '{$formatted}'\n";
}

echo "\n=== ACTUALIZARE TELEFOANE EXISTENTE ===\n";

global $wpdb;

// Conectare la baza de date Joomla
$joomla_db_host = 'localhost';
$joomla_db_name = 'cmmf';
$joomla_db_user = 'root';
$joomla_db_pass = '';

$joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);

if ($joomla_db->connect_error) {
    die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
}

// Obține toate telefoanele din baza de date Joomla
$query = "
    SELECT 
        u.id as joomla_id,
        u.username,
        u.email,
        cb.cb_telefon,
        cb.cb_telefon2
    FROM bqzce_users u
    LEFT JOIN bqzce_comprofiler cb ON u.id = cb.user_id
    WHERE cb.cb_telefon IS NOT NULL OR cb.cb_telefon2 IS NOT NULL
    ORDER BY u.id
";

$result = $joomla_db->query($query);

$total_phones = 0;
$valid_phones = 0;
$invalid_phones = 0;
$romania_phones = 0;
$ukraine_phones = 0;
$international_phones = 0;

echo "Analiză telefoane cu noua validare:\n";

while ($row = $result->fetch_assoc()) {
    $total_phones++;
    $has_valid = false;
    
    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhoneWithUkraine($phone);
        $country = getPhoneCountry($phone);
        
        if ($is_valid) {
            $valid_phones++;
            $has_valid = true;
            
            if ($country === 'ROMANIA') $romania_phones++;
            elseif ($country === 'UKRAINE') $ukraine_phones++;
            elseif ($country === 'INTERNATIONAL') $international_phones++;
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhoneWithUkraine($phone2);
        $country2 = getPhoneCountry($phone2);
        
        if ($is_valid2) {
            $valid_phones++;
            $has_valid = true;
            
            if ($country2 === 'ROMANIA') $romania_phones++;
            elseif ($country2 === 'UKRAINE') $ukraine_phones++;
            elseif ($country2 === 'INTERNATIONAL') $international_phones++;
        }
    }
    
    if (!$has_valid) {
        $invalid_phones++;
    }
}

$joomla_db->close();

echo "\n=== STATISTICI NOI ===\n";
echo "Total telefoane verificate: $total_phones\n";
echo "Telefoane valide: $valid_phones\n";
echo "Telefoane invalide: $invalid_phones\n";
echo "Telefoane România: $romania_phones\n";
echo "Telefoane Ucraina: $ukraine_phones\n";
echo "Telefoane internaționale: $international_phones\n";

echo "\n=== RECOMANDĂRI ===\n";
echo "1. Actualizează funcția de validare în toate scripturile\n";
echo "2. Adaugă suport pentru formate ucrainene (+380)\n";
echo "3. Testează cu numere reale din Ucraina\n";
echo "4. Actualizează documentația\n";

echo "\n=== ACTUALIZARE COMPLETĂ ===\n"; 