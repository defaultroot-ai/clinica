<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE FORMATE TELEFOANE DIN JOOMLA COMMUNITY BUILDER ===\n\n";

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

// Funcție pentru validarea telefonului (cu toate formatele românești)
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

// Obține toate telefoanele din baza de date Joomla
$query = "
    SELECT
        u.id as joomla_id,
        u.username,
        u.name,
        u.email,
        cb.cb_telefon,
        cb.cb_telefon2
    FROM bqzce_users u
    LEFT JOIN bqzce_comprofiler cb ON u.id = cb.user_id
    WHERE cb.cb_telefon IS NOT NULL OR cb.cb_telefon2 IS NOT NULL
    ORDER BY u.id
";

$result = $joomla_db->query($query);

$phones_with_slashes = array();
$phones_with_spaces = array();
$phones_with_both = array();
$all_invalid_phones = array();
$total_checked = 0;

echo "=== ANALIZĂ TELEFOANE DIN JOOMLA ===\n";

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_slash = false;
    $has_space = false;
    $has_invalid = false;
    
    $phone_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'name' => $row['name'],
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'probleme' => array()
    );
    
    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $phone_data['telefon_principal'] = $phone;
        
        // Verifică dacă conține slash-uri
        if (strpos($phone, '/') !== false) {
            $has_slash = true;
            $phone_data['probleme'][] = 'Principal: conține slash-uri';
        }
        
        // Verifică dacă conține spații
        if (strpos($phone, ' ') !== false) {
            $has_space = true;
            $phone_data['probleme'][] = 'Principal: conține spații';
        }
        
        // Verifică dacă este invalid
        if (!validatePhoneWithAllFormats($phone)) {
            $has_invalid = true;
            $phone_data['probleme'][] = 'Principal: format invalid';
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $phone_data['telefon_secundar'] = $phone2;
        
        // Verifică dacă conține slash-uri
        if (strpos($phone2, '/') !== false) {
            $has_slash = true;
            $phone_data['probleme'][] = 'Secundar: conține slash-uri';
        }
        
        // Verifică dacă conține spații
        if (strpos($phone2, ' ') !== false) {
            $has_space = true;
            $phone_data['probleme'][] = 'Secundar: conține spații';
        }
        
        // Verifică dacă este invalid
        if (!validatePhoneWithAllFormats($phone2)) {
            $has_invalid = true;
            $phone_data['probleme'][] = 'Secundar: format invalid';
        }
    }
    
    // Categorizează telefoanele
    if ($has_slash && $has_space) {
        $phones_with_both[] = $phone_data;
    } elseif ($has_slash) {
        $phones_with_slashes[] = $phone_data;
    } elseif ($has_space) {
        $phones_with_spaces[] = $phone_data;
    }
    
    if ($has_invalid) {
        $all_invalid_phones[] = $phone_data;
    }
}

$joomla_db->close();

echo "\n=== REZULTATE ANALIZĂ ===\n";
echo "Total utilizatori verificați: $total_checked\n";
echo "Telefoane cu slash-uri (/): " . count($phones_with_slashes) . "\n";
echo "Telefoane cu spații: " . count($phones_with_spaces) . "\n";
echo "Telefoane cu ambele probleme: " . count($phones_with_both) . "\n";
echo "Total telefoane invalide: " . count($all_invalid_phones) . "\n";

// Afișează telefoanele cu slash-uri
if (!empty($phones_with_slashes)) {
    echo "\n=== TELEFOANE CU SLASH-URI (/) ===\n";
    foreach (array_slice($phones_with_slashes, 0, 20) as $index => $data) {
        echo ($index + 1) . ". {$data['name']} ({$data['username']})\n";
        echo "   Email: {$data['email']}\n";
        if ($data['telefon_principal']) {
            echo "   Telefon principal: '{$data['telefon_principal']}'\n";
        }
        if ($data['telefon_secundar']) {
            echo "   Telefon secundar: '{$data['telefon_secundar']}'\n";
        }
        echo "   Probleme: " . implode(', ', $data['probleme']) . "\n";
        echo "\n";
    }
    
    if (count($phones_with_slashes) > 20) {
        echo "... și încă " . (count($phones_with_slashes) - 20) . " utilizatori\n";
    }
}

// Afișează telefoanele cu spații
if (!empty($phones_with_spaces)) {
    echo "\n=== TELEFOANE CU SPAȚII ===\n";
    foreach (array_slice($phones_with_spaces, 0, 20) as $index => $data) {
        echo ($index + 1) . ". {$data['name']} ({$data['username']})\n";
        echo "   Email: {$data['email']}\n";
        if ($data['telefon_principal']) {
            echo "   Telefon principal: '{$data['telefon_principal']}'\n";
        }
        if ($data['telefon_secundar']) {
            echo "   Telefon secundar: '{$data['telefon_secundar']}'\n";
        }
        echo "   Probleme: " . implode(', ', $data['probleme']) . "\n";
        echo "\n";
    }
    
    if (count($phones_with_spaces) > 20) {
        echo "... și încă " . (count($phones_with_spaces) - 20) . " utilizatori\n";
    }
}

// Afișează telefoanele cu ambele probleme
if (!empty($phones_with_both)) {
    echo "\n=== TELEFOANE CU AMBELE PROBLEME (SLASH-URI + SPAȚII) ===\n";
    foreach (array_slice($phones_with_both, 0, 20) as $index => $data) {
        echo ($index + 1) . ". {$data['name']} ({$data['username']})\n";
        echo "   Email: {$data['email']}\n";
        if ($data['telefon_principal']) {
            echo "   Telefon principal: '{$data['telefon_principal']}'\n";
        }
        if ($data['telefon_secundar']) {
            echo "   Telefon secundar: '{$data['telefon_secundar']}'\n";
        }
        echo "   Probleme: " . implode(', ', $data['probleme']) . "\n";
        echo "\n";
    }
    
    if (count($phones_with_both) > 20) {
        echo "... și încă " . (count($phones_with_both) - 20) . " utilizatori\n";
    }
}

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 