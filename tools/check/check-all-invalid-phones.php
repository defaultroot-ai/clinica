<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE COMPLETĂ TOȚI UTILIZATORII CU TELEFOANE NECONFORME ===\n\n";

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
$invalid_phones = array();
$total_checked = 0;
$valid_phones = 0;

echo "=== ANALIZĂ COMPLETĂ TOȚI UTILIZATORII ===\n";

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_invalid = false;
    $invalid_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'name' => $row['name'],
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array()
    );

    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhoneWithUkraine($phone);

        if ($is_valid) {
            $valid_phones++;
        } else {
            $invalid_data['telefon_principal'] = $phone;
            $invalid_data['erori'][] = 'Principal: FORMAT INVALID';
            $has_invalid = true;
        }
    }

    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhoneWithUkraine($phone2);

        if ($is_valid2) {
            $valid_phones++;
        } else {
            $invalid_data['telefon_secundar'] = $phone2;
            $invalid_data['erori'][] = 'Secundar: FORMAT INVALID';
            $has_invalid = true;
        }
    }

    if ($has_invalid) {
        $invalid_phones[] = $invalid_data;
    }
}

$joomla_db->close();

echo "\n=== REZULTATE COMPLETE ===\n";
echo "Total utilizatori verificați: $total_checked\n";
echo "Total telefoane valide: $valid_phones\n";
echo "Utilizatori cu telefoane neconforme: " . count($invalid_phones) . "\n";

// Afișează primii 20 utilizatori cu telefoane neconforme
if (!empty($invalid_phones)) {
    echo "\n=== PRIMII 20 UTILIZATORI CU TELEFOANE NECONFORME ===\n";
    $display_count = min(20, count($invalid_phones));
    
    for ($i = 0; $i < $display_count; $i++) {
        $data = $invalid_phones[$i];
        echo ($i + 1) . ". Username: {$data['username']}\n";
        echo "   Nume: {$data['name']}\n";
        echo "   Email: {$data['email']}\n";
        if ($data['telefon_principal']) {
            echo "   ❌ Telefon principal: {$data['telefon_principal']}\n";
        }
        if ($data['telefon_secundar']) {
            echo "   ❌ Telefon secundar: {$data['telefon_secundar']}\n";
        }
        echo "   Erori: " . implode(', ', $data['erori']) . "\n";
        echo "\n";
    }
    
    if (count($invalid_phones) > 20) {
        echo "... și încă " . (count($invalid_phones) - 20) . " utilizatori\n";
    }
}

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 