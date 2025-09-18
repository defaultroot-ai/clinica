<?php
require_once('../../../wp-load.php');

echo "=== SINCRONIZARE UTILIZATORI CU LISTA DE PACIENȚI ===\n\n";

global $wpdb;

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

// Funcție pentru formatarea telefonului
function formatPhoneForAuth($phone) {
    if (empty($phone)) return '';
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($clean_phone) > 20) {
        $clean_phone = substr($clean_phone, 0, 20);
    }
    return $clean_phone;
}

// Obține toți utilizatorii WordPress
$users = $wpdb->get_results("
    SELECT 
        u.ID,
        u.user_login,
        u.user_email,
        u.display_name,
        u.user_registered,
        um_phone.meta_value as phone_primary,
        um_phone2.meta_value as phone_secondary
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->usermeta} um_phone ON u.ID = um_phone.user_id AND um_phone.meta_key = 'telefon_principal'
    LEFT JOIN {$wpdb->usermeta} um_phone2 ON u.ID = um_phone2.user_id AND um_phone2.meta_key = 'telefon_secundar'
    ORDER BY u.ID
");

echo "Total utilizatori WordPress: " . count($users) . "\n\n";

// Verifică care utilizatori sunt în tabelul clinica_patients
$patients = $wpdb->get_results("
    SELECT user_id, cnp, phone_primary, phone_secondary
    FROM {$wpdb->prefix}clinica_patients
");

$patient_user_ids = array();
foreach ($patients as $patient) {
    $patient_user_ids[] = $patient->user_id;
}

echo "Total pacienți în clinica_patients: " . count($patients) . "\n\n";

// Analizează utilizatorii
$users_to_sync = array();
$users_with_invalid_phones = array();
$users_already_patients = array();

foreach ($users as $user) {
    $has_invalid_phone = false;
    $phone_issues = array();
    
    // Verifică telefonul principal
    if (!empty($user->phone_primary)) {
        if (!validatePhoneWithAllFormats($user->phone_primary)) {
            $has_invalid_phone = true;
            $phone_issues[] = 'Principal: ' . $user->phone_primary;
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($user->phone_secondary)) {
        if (!validatePhoneWithAllFormats($user->phone_secondary)) {
            $has_invalid_phone = true;
            $phone_issues[] = 'Secundar: ' . $user->phone_secondary;
        }
    }
    
    if (in_array($user->ID, $patient_user_ids)) {
        $users_already_patients[] = $user;
        if ($has_invalid_phone) {
            $users_with_invalid_phones[] = array(
                'user' => $user,
                'issues' => $phone_issues,
                'type' => 'existing_patient'
            );
        }
    } else {
        $users_to_sync[] = $user;
        if ($has_invalid_phone) {
            $users_with_invalid_phones[] = array(
                'user' => $user,
                'issues' => $phone_issues,
                'type' => 'to_sync'
            );
        }
    }
}

echo "=== REZULTATE ANALIZĂ ===\n";
echo "Utilizatori de sincronizat: " . count($users_to_sync) . "\n";
echo "Utilizatori cu telefoane invalide: " . count($users_with_invalid_phones) . "\n";
echo "Utilizatori deja pacienți: " . count($users_already_patients) . "\n\n";

// Afișează utilizatorii cu telefoane invalide
if (!empty($users_with_invalid_phones)) {
    echo "=== UTILIZATORI CU TELEFOANE INVALIDE ===\n";
    foreach ($users_with_invalid_phones as $index => $data) {
        $user = $data['user'];
        $type = $data['type'] === 'existing_patient' ? 'PACIENT EXISTENT' : 'DE SINCRONIZAT';
        
        echo ($index + 1) . ". {$type} - {$user->display_name} ({$user->user_login})\n";
        echo "   Email: {$user->user_email}\n";
        echo "   Telefon principal: " . ($user->phone_primary ?: 'NULL') . "\n";
        echo "   Telefon secundar: " . ($user->phone_secondary ?: 'NULL') . "\n";
        echo "   Probleme: " . implode(', ', $data['issues']) . "\n";
        echo "\n";
    }
}

// Interfață pentru editare
if (!empty($users_with_invalid_phones)) {
    echo "=== INTERFAȚĂ EDITARE ===\n";
    echo "Pentru a edita telefoanele, folosește formularul de mai jos:\n\n";
    
    echo "<form method='post' action=''>\n";
    echo "<input type='hidden' name='action' value='edit_phones'>\n";
    
    foreach ($users_with_invalid_phones as $index => $data) {
        $user = $data['user'];
        $type = $data['type'] === 'existing_patient' ? 'PACIENT EXISTENT' : 'DE SINCRONIZAT';
        
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
        echo "<h3>{$type} - {$user->display_name} ({$user->user_login})</h3>\n";
        echo "<p>Email: {$user->user_email}</p>\n";
        echo "<p>Probleme: " . implode(', ', $data['issues']) . "</p>\n";
        
        echo "<label>Telefon principal:</label><br>\n";
        echo "<input type='text' name='phone_primary[{$user->ID}]' value='" . htmlspecialchars($user->phone_primary) . "' style='width: 200px;'><br>\n";
        
        echo "<label>Telefon secundar:</label><br>\n";
        echo "<input type='text' name='phone_secondary[{$user->ID}]' value='" . htmlspecialchars($user->phone_secondary) . "' style='width: 200px;'><br>\n";
        
        echo "</div>\n";
    }
    
    echo "<input type='submit' value='Actualizează telefoanele' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;'>\n";
    echo "</form>\n";
}

// Procesează actualizările
if ($_POST['action'] === 'edit_phones') {
    echo "<h2>Actualizare telefoane...</h2>\n";
    
    $updated_count = 0;
    $errors = array();
    
    foreach ($_POST['phone_primary'] as $user_id => $phone) {
        $phone = trim($phone);
        $phone2 = trim($_POST['phone_secondary'][$user_id]);
        
        // Validează telefoanele
        $valid_primary = empty($phone) || validatePhoneWithAllFormats($phone);
        $valid_secondary = empty($phone2) || validatePhoneWithAllFormats($phone2);
        
        if ($valid_primary && $valid_secondary) {
            // Actualizează în usermeta
            update_user_meta($user_id, 'telefon_principal', $phone);
            update_user_meta($user_id, 'telefon_secundar', $phone2);
            
            // Actualizează în clinica_patients dacă există
            $existing_patient = $wpdb->get_row($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d
            ", $user_id));
            
            if ($existing_patient) {
                $wpdb->update(
                    $wpdb->prefix . 'clinica_patients',
                    array(
                        'phone_primary' => $phone,
                        'phone_secondary' => $phone2
                    ),
                    array('user_id' => $user_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
            
            $updated_count++;
            echo "✅ Actualizat utilizatorul ID: {$user_id}\n";
        } else {
            $errors[] = "Utilizatorul ID: {$user_id} - Telefoane invalide";
        }
    }
    
    echo "\n=== REZULTATE ACTUALIZARE ===\n";
    echo "Actualizați cu succes: {$updated_count}\n";
    if (!empty($errors)) {
        echo "Erori:\n";
        foreach ($errors as $error) {
            echo "- {$error}\n";
        }
    }
}

echo "\n=== SINCRONIZARE COMPLETĂ ===\n";
?> 