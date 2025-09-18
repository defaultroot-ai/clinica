<?php
/**
 * Script pentru actualizarea meta datelor cu numerele de telefon pentru pacienții existenți
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă scriptul este rulat din admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

global $wpdb;

echo "<h1>📞 Actualizare Meta Date Telefon pentru Pacienți Existenți</h1>";

// 1. Găsește pacienții din tabela clinica care au numere de telefon
$clinica_table = $wpdb->prefix . 'clinica_patients';
$patients_with_phone = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE (p.phone_primary IS NOT NULL AND p.phone_primary != '')
    OR (p.phone_secondary IS NOT NULL AND p.phone_secondary != '')
    ORDER BY p.created_at DESC
");

echo "<h2>📊 Pacienți cu numere de telefon în tabela clinica: " . count($patients_with_phone) . "</h2>";

if (empty($patients_with_phone)) {
    echo "<p>❌ Nu s-au găsit pacienți cu numere de telefon!</p>";
    exit;
}

// 2. Actualizează meta datele pentru fiecare pacient
$updated_count = 0;
$errors = array();

foreach ($patients_with_phone as $patient) {
    echo "<hr>";
    echo "<h3>📞 Actualizare pacient: {$patient->display_name} (ID: {$patient->user_id})</h3>";
    
    $updates_made = false;
    
    // Verifică dacă telefonul principal există și nu este deja în meta
    if (!empty($patient->phone_primary)) {
        $existing_phone_primary = get_user_meta($patient->user_id, 'phone_primary', true);
        
        if (empty($existing_phone_primary)) {
            update_user_meta($patient->user_id, 'phone_primary', $patient->phone_primary);
            echo "<p>✅ Telefon principal salvat ca meta: {$patient->phone_primary}</p>";
            $updates_made = true;
        } else {
            echo "<p>ℹ️ Telefon principal deja există în meta: {$existing_phone_primary}</p>";
        }
    }
    
    // Verifică dacă telefonul secundar există și nu este deja în meta
    if (!empty($patient->phone_secondary)) {
        $existing_phone_secondary = get_user_meta($patient->user_id, 'phone_secondary', true);
        
        if (empty($existing_phone_secondary)) {
            update_user_meta($patient->user_id, 'phone_secondary', $patient->phone_secondary);
            echo "<p>✅ Telefon secundar salvat ca meta: {$patient->phone_secondary}</p>";
            $updates_made = true;
        } else {
            echo "<p>ℹ️ Telefon secundar deja există în meta: {$existing_phone_secondary}</p>";
        }
    }
    
    if ($updates_made) {
        $updated_count++;
    } else {
        echo "<p>ℹ️ Nu au fost necesare actualizări pentru acest pacient</p>";
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
echo "<h2>🎯 Verificare Finală</h2>";

// Verifică câți pacienți au numere de telefon în meta date
$patients_with_phone_meta = $wpdb->get_results("
    SELECT COUNT(*) as count
    FROM {$wpdb->usermeta} um
    WHERE um.meta_key IN ('phone_primary', 'phone_secondary')
    AND um.meta_value IS NOT NULL 
    AND um.meta_value != ''
");

$total_with_phone_meta = 0;
foreach ($patients_with_phone_meta as $result) {
    $total_with_phone_meta += $result->count;
}

echo "<p><strong>Total meta date telefon în WordPress:</strong> {$total_with_phone_meta}</p>";

// Verifică câți pacienți au numere de telefon în tabela clinica
$total_with_phone_clinica = $wpdb->get_var("
    SELECT COUNT(*) FROM $clinica_table 
    WHERE (phone_primary IS NOT NULL AND phone_primary != '')
    OR (phone_secondary IS NOT NULL AND phone_secondary != '')
");

echo "<p><strong>Total numere telefon în tabela clinica:</strong> {$total_with_phone_clinica}</p>";

if ($total_with_phone_meta > 0) {
    echo "<p>✅ Numerele de telefon sunt acum salvate ca meta date!</p>";
} else {
    echo "<p>❌ Nu s-au găsit numere de telefon în meta date!</p>";
}

echo "<hr>";
echo "<p><em>Actualizare rulată la: " . current_time('mysql') . "</em></p>";
?> 