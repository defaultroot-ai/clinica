<?php
require_once('../../../wp-load.php');

echo "=== VERIFICARE CONFORMITATE TELEFOANE JOOMLA ===\n\n";

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

// Funcție pentru formatarea telefonului
function formatPhone($phone) {
    if (empty($phone)) return '';
    
    // Elimină spațiile și caracterele speciale
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Trunchiază la 20 de caractere
    if (strlen($clean_phone) > 20) {
        $clean_phone = substr($clean_phone, 0, 20);
    }
    
    return $clean_phone;
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

$total_phones = 0;
$invalid_phones = 0;
$valid_phones = 0;
$too_long_phones = 0;
$invalid_format_phones = 0;

echo "=== ANALIZĂ TELEFOANE ===\n";

while ($row = $result->fetch_assoc()) {
    $total_phones++;
    $has_invalid = false;
    
    echo "\nUtilizator: {$row['username']} (ID: {$row['joomla_id']})\n";
    
    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhone($phone);
        $formatted = formatPhone($phone);
        
        if ($is_valid) {
            echo "  ✅ Telefon principal: {$phone} -> {$formatted}\n";
            $valid_phones++;
        } else {
            echo "  ❌ Telefon principal: {$phone} (INVALID)\n";
            $invalid_phones++;
            $has_invalid = true;
            
            if (strlen(preg_replace('/[^0-9+]/', '', $phone)) > 20) {
                $too_long_phones++;
            } else {
                $invalid_format_phones++;
            }
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhone($phone2);
        $formatted2 = formatPhone($phone2);
        
        if ($is_valid2) {
            echo "  ✅ Telefon secundar: {$phone2} -> {$formatted2}\n";
            $valid_phones++;
        } else {
            echo "  ❌ Telefon secundar: {$phone2} (INVALID)\n";
            $invalid_phones++;
            $has_invalid = true;
            
            if (strlen(preg_replace('/[^0-9+]/', '', $phone2)) > 20) {
                $too_long_phones++;
            } else {
                $invalid_format_phones++;
            }
        }
    }
    
    if (!$has_invalid) {
        echo "  ✅ Toate telefoanele sunt conforme\n";
    }
}

echo "\n=== STATISTICI ===\n";
echo "Total telefoane verificate: $total_phones\n";
echo "Telefoane valide: $valid_phones\n";
echo "Telefoane invalide: $invalid_phones\n";
echo "Telefoane prea lungi (>20 caractere): $too_long_phones\n";
echo "Telefoane cu format invalid: $invalid_format_phones\n";

// Închide conexiunea
$joomla_db->close();

echo "\n=== VERIFICARE COMPLETĂ ===\n"; 