<?php
/**
 * Script pentru actualizarea numerelor de telefon pentru pacienții deja sincronizați
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă scriptul este rulat din admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

global $wpdb;

echo "<h1>📞 Actualizare Numere de Telefon</h1>";

// 1. Găsește pacienții din tabela clinica care nu au numere de telefon
$clinica_table = $wpdb->prefix . 'clinica_patients';
$patients_without_phone = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (p.phone_primary IS NULL OR p.phone_primary = '')
    AND (p.phone_secondary IS NULL OR p.phone_secondary = '')
    LIMIT 10
");

echo "<h2>📊 Pacienți fără numere de telefon: " . count($patients_without_phone) . "</h2>";

if (empty($patients_without_phone)) {
    echo "<p>✅ Toți pacienții au numere de telefon!</p>";
    exit;
}

// 2. Actualizează numerele de telefon
$updated_count = 0;
$errors = array();

foreach ($patients_without_phone as $patient) {
    echo "<hr>";
    echo "<h3>📞 Actualizare pacient: {$patient->display_name} (ID: {$patient->user_id})</h3>";
    
    // Găsește numerele de telefon din meta date
    $phone_primary = get_user_meta($patient->user_id, 'phone_primary', true);
    $phone_secondary = get_user_meta($patient->user_id, 'phone_secondary', true);
    
    // Verifică și alte meta keys posibile pentru telefon
    if (empty($phone_primary)) {
        $phone_primary = get_user_meta($patient->user_id, 'phone', true);
    }
    if (empty($phone_primary)) {
        $phone_primary = get_user_meta($patient->user_id, 'mobile', true);
    }
    if (empty($phone_primary)) {
        $phone_primary = get_user_meta($patient->user_id, 'telefon', true);
    }
    
    echo "<p><strong>Telefon găsit în meta:</strong> " . ($phone_primary ?: 'Nu găsit') . "</p>";
    echo "<p><strong>Telefon secundar găsit:</strong> " . ($phone_secondary ?: 'Nu găsit') . "</p>";
    
    // Actualizează în tabela clinica_patients
    $update_data = array();
    if (!empty($phone_primary)) {
        $update_data['phone_primary'] = $phone_primary;
    }
    if (!empty($phone_secondary)) {
        $update_data['phone_secondary'] = $phone_secondary;
    }
    
    if (!empty($update_data)) {
        $result = $wpdb->update(
            $clinica_table,
            $update_data,
            array('user_id' => $patient->user_id)
        );
        
        if ($result !== false) {
            echo "<p>✅ Numere de telefon actualizate cu succes!</p>";
            echo "<ul>";
            if (!empty($phone_primary)) {
                echo "<li><strong>Telefon Principal:</strong> {$phone_primary}</li>";
            }
            if (!empty($phone_secondary)) {
                echo "<li><strong>Telefon Secundar:</strong> {$phone_secondary}</li>";
            }
            echo "</ul>";
            $updated_count++;
        } else {
            echo "<p>❌ Eroare la actualizare: " . $wpdb->last_error . "</p>";
            $errors[] = "Eroare pentru {$patient->display_name}: " . $wpdb->last_error;
        }
    } else {
        echo "<p>⚠️ Nu s-au găsit numere de telefon pentru acest pacient</p>";
    }
}

// 3. Rezumat final
echo "<hr>";
echo "<h2>📊 Rezumat Actualizare</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>✅ Pacienți actualizați cu succes:</strong> {$updated_count}</p>";
echo "<p><strong>❌ Erori:</strong> " . count($errors) . "</p>";

if (!empty($errors)) {
    echo "<h3>Erori întâlnite:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// 4. Verificare finală
$final_count_without_phone = $wpdb->get_var("
    SELECT COUNT(*) FROM $clinica_table 
    WHERE (phone_primary IS NULL OR phone_primary = '')
    AND (phone_secondary IS NULL OR phone_secondary = '')
");

echo "<h2>🎯 Verificare Finală</h2>";
echo "<p><strong>Pacienți fără numere de telefon rămași:</strong> {$final_count_without_phone}</p>";

if ($final_count_without_phone == 0) {
    echo "<p>✅ Toți pacienții au numere de telefon!</p>";
} else {
    echo "<p>⚠️ Încă sunt {$final_count_without_phone} pacienți fără numere de telefon</p>";
}

echo "<hr>";
echo "<p><em>Actualizare rulată la: " . current_time('mysql') . "</em></p>";
?> 