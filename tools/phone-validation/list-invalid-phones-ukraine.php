<?php
require_once('../../../wp-load.php');

echo "=== LISTA TELEFOANE NECONFORME CU SUPORT UCRAINA ===\n\n";

// Configurare baza de date Joomla
$joomla_db_host = 'localhost';
$joomla_db_name = 'cmmf';
$joomla_db_user = 'root';
$joomla_db_pass = '';

// Conectare la baza de date Joomla
$joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);

if ($joomla_db->connect_error) {
    die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
}

// Funcție pentru validarea telefonului (cu suport Ucraina)
function validatePhoneWithUkraine($phone) {
    if (empty($phone)) return true;
    
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

// Funcție pentru a determina tipul de eroare
function getPhoneErrorTypeWithUkraine($phone) {
    if (empty($phone)) return 'GOL';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strlen($clean_phone) > 20) {
        return 'PREA LUNG';
    }
    
    // Verifică dacă începe cu formatele valide
    if (preg_match('/^(\+40|0)/', $clean_phone)) {
        if (strlen($clean_phone) !== 10 && strlen($clean_phone) !== 12) {
            return 'LUNGIME INVALIDĂ ROMÂNIA';
        }
        return 'VALID ROMÂNIA';
    }
    
    if (preg_match('/^\+380/', $clean_phone)) {
        if (strlen($clean_phone) !== 13) {
            return 'LUNGIME INVALIDĂ UCRAINA';
        }
        return 'VALID UCRAINA';
    }
    
    if (preg_match('/^\+/', $clean_phone)) {
        if (strlen($clean_phone) < 10 || strlen($clean_phone) > 15) {
            return 'LUNGIME INVALIDĂ INTERNAȚIONAL';
        }
        return 'VALID INTERNAȚIONAL';
    }
    
    return 'FORMAT INVALID';
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
$invalid_phones = array();
$total_checked = 0;
$valid_phones = 0;
$romania_phones = 0;
$ukraine_phones = 0;
$international_phones = 0;

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_invalid = false;
    $invalid_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array(),
        'countries' => array()
    );

    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhoneWithUkraine($phone);
        $error_type = getPhoneErrorTypeWithUkraine($phone);
        $country = getPhoneCountry($phone);

        if ($is_valid) {
            $valid_phones++;
            if ($country === 'ROMANIA') $romania_phones++;
            elseif ($country === 'UKRAINE') $ukraine_phones++;
            elseif ($country === 'INTERNATIONAL') $international_phones++;
        } else {
            $invalid_data['telefon_principal'] = $phone;
            $invalid_data['erori'][] = 'Principal: ' . $error_type;
            $has_invalid = true;
        }
        
        $invalid_data['countries'][] = $country;
    }

    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhoneWithUkraine($phone2);
        $error_type2 = getPhoneErrorTypeWithUkraine($phone2);
        $country2 = getPhoneCountry($phone2);

        if ($is_valid2) {
            $valid_phones++;
            if ($country2 === 'ROMANIA') $romania_phones++;
            elseif ($country2 === 'UKRAINE') $ukraine_phones++;
            elseif ($country2 === 'INTERNATIONAL') $international_phones++;
        } else {
            $invalid_data['telefon_secundar'] = $phone2;
            $invalid_data['erori'][] = 'Secundar: ' . $error_type2;
            $has_invalid = true;
        }
        
        $invalid_data['countries'][] = $country2;
    }

    if ($has_invalid) {
        $invalid_phones[] = $invalid_data;
    }
}

// Analiză tipuri de erori
$error_types = array();
foreach ($invalid_phones as $data) {
    foreach ($data['erori'] as $error) {
        $error_type = str_replace(['Principal: ', 'Secundar: '], '', $error);
        if (!isset($error_types[$error_type])) {
            $error_types[$error_type] = 0;
        }
        $error_types[$error_type]++;
    }
}

$joomla_db->close();

echo "=== STATISTICI GENERALE ===\n";
echo "Total utilizatori verificați: $total_checked\n";
echo "Total telefoane valide: $valid_phones\n";
echo "Utilizatori cu telefoane neconforme: " . count($invalid_phones) . "\n";

echo "\n=== STATISTICI PE ȚĂRI ===\n";
echo "Telefoane România: $romania_phones\n";
echo "Telefoane Ucraina: $ukraine_phones\n";
echo "Telefoane internaționale: $international_phones\n";

echo "\n=== TIPURI DE ERORI ===\n";
foreach ($error_types as $type => $count) {
    echo "$type: $count\n";
}

// Afișează lista telefoanelor neconforme
if (empty($invalid_phones)) {
    echo "\n✅ Nu s-au găsit telefoane neconforme!\n";
} else {
    echo "\nGăsite " . count($invalid_phones) . " utilizatori cu telefoane neconforme:\n\n";
    foreach ($invalid_phones as $index => $data) {
        echo ($index + 1) . ". Username: {$data['username']}\n";
        echo "   Joomla ID: {$data['joomla_id']}\n";
        echo "   Email: {$data['email']}\n";
        if ($data['telefon_principal']) {
            echo "   ❌ Telefon principal: {$data['telefon_principal']}\n";
        }
        if ($data['telefon_secundar']) {
            echo "   ❌ Telefon secundar: {$data['telefon_secundar']}\n";
        }
        echo "   Erori: " . implode(', ', $data['erori']) . "\n";
        echo "   Țări detectate: " . implode(', ', array_unique($data['countries'])) . "\n";
        echo "\n";
    }
}

echo "\n=== FORMATE VALIDE ACCEPTATE ===\n";
echo "🇷🇴 România:\n";
echo "   - 07XXXXXXXX (10 cifre)\n";
echo "   - +407XXXXXXXX (12 caractere)\n";
echo "\n🇺🇦 Ucraina:\n";
echo "   - +380XXXXXXXXX (13 caractere)\n";
echo "\n🌍 Internațional:\n";
echo "   - +XXXXXXXXXXX (10-15 caractere)\n";

echo "\n=== VERIFICARE COMPLETĂ CU SUPORT UCRAINA ===\n"; 