<?php
require_once('../../../wp-load.php');

echo "=== LISTA TELEFOANE NECONFORME ===\n\n";

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

echo "✅ Conectat la baza de date Joomla: $joomla_db_name\n\n";

// Funcție pentru validarea telefonului
function validatePhone($phone) {
    if (empty($phone)) return true; // Telefonul gol este valid
    
    // Elimină spațiile și caracterele speciale
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Verifică lungimea
    if (strlen($clean_phone) > 20) {
        return false;
    }
    
    // Verifică dacă începe cu + sau 0
    if (!preg_match('/^(\+40|0)/', $clean_phone)) {
        return false;
    }
    
    return true;
}

// Funcție pentru a determina tipul de eroare
function getPhoneErrorType($phone) {
    if (empty($phone)) return 'GOL';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strlen($clean_phone) > 20) {
        return 'PREA LUNG';
    }
    
    if (!preg_match('/^(\+40|0)/', $clean_phone)) {
        return 'FORMAT INVALID';
    }
    
    return 'ALT TIP';
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

if (!$result) {
    die("Eroare la query: " . $joomla_db->error);
}

$invalid_phones = array();
$total_checked = 0;

echo "=== ANALIZĂ TELEFOANE NECONFORME ===\n\n";

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_invalid = false;
    $invalid_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array()
    );
    
    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhone($phone);
        
        if (!$is_valid) {
            $invalid_data['telefon_principal'] = $phone;
            $invalid_data['erori'][] = 'Principal: ' . getPhoneErrorType($phone);
            $has_invalid = true;
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhone($phone2);
        
        if (!$is_valid2) {
            $invalid_data['telefon_secundar'] = $phone2;
            $invalid_data['erori'][] = 'Secundar: ' . getPhoneErrorType($phone2);
            $has_invalid = true;
        }
    }
    
    if ($has_invalid) {
        $invalid_phones[] = $invalid_data;
    }
}

// Afișează lista telefoanelor neconforme
echo "=== LISTA COMPLETĂ TELEFOANE NECONFORME ===\n\n";

if (empty($invalid_phones)) {
    echo "✅ Nu s-au găsit telefoane neconforme!\n";
} else {
    echo "Găsite " . count($invalid_phones) . " utilizatori cu telefoane neconforme:\n\n";
    
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
        echo "\n";
    }
}

// Statistici
echo "=== STATISTICI ===\n";
echo "Total utilizatori verificați: $total_checked\n";
echo "Utilizatori cu telefoane neconforme: " . count($invalid_phones) . "\n";

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

echo "\n=== TIPURI DE ERORI ===\n";
foreach ($error_types as $type => $count) {
    echo "$type: $count\n";
}

// Închide conexiunea
$joomla_db->close();

echo "\n=== ANALIZĂ COMPLETĂ ===\n"; 