<?php
/**
 * Test script pentru verificarea corectării erorilor de bază de date
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă suntem în admin
if (!current_user_can('manage_options')) {
    wp_die('Acces interzis');
}

echo '<h1>Test Corectări Bază de Date</h1>';

// Testează metoda get_recent_appointments_html()
echo '<h2>Test get_recent_appointments_html()</h2>';

try {
    $plugin = Clinica_Plugin::get_instance();
    
    if (method_exists($plugin, 'get_recent_appointments_html')) {
        $appointments_html = $plugin->get_recent_appointments_html();
        echo '<div style="color: green;">✅ Metoda get_recent_appointments_html() funcționează fără erori</div>';
        echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc;">';
        echo '<strong>Rezultat:</strong><br>';
        echo $appointments_html;
        echo '</div>';
    } else {
        echo '<div style="color: red;">❌ Metoda get_recent_appointments_html() nu există</div>';
    }
} catch (Exception $e) {
    echo '<div style="color: red;">❌ Eroare: ' . esc_html($e->getMessage()) . '</div>';
}

// Testează metoda get_recent_patients_html()
echo '<h2>Test get_recent_patients_html()</h2>';

try {
    if (method_exists($plugin, 'get_recent_patients_html')) {
        $patients_html = $plugin->get_recent_patients_html();
        echo '<div style="color: green;">✅ Metoda get_recent_patients_html() funcționează fără erori</div>';
        echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc;">';
        echo '<strong>Rezultat:</strong><br>';
        echo $patients_html;
        echo '</div>';
    } else {
        echo '<div style="color: red;">❌ Metoda get_recent_patients_html() nu există</div>';
    }
} catch (Exception $e) {
    echo '<div style="color: red;">❌ Eroare: ' . esc_html($e->getMessage()) . '</div>';
}

// Verifică structura bazei de date
echo '<h2>Verificare Structură Bază de Date</h2>';

global $wpdb;

$tables_to_check = array(
    'clinica_patients',
    'clinica_appointments',
    'clinica_medical_records',
    'clinica_login_logs',
    'clinica_imports',
    'clinica_notifications'
);

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    
    if ($table_exists) {
        echo '<div style="color: green;">✅ Tabela ' . esc_html($full_table_name) . ' există</div>';
    } else {
        echo '<div style="color: red;">❌ Tabela ' . esc_html($full_table_name) . ' NU există</div>';
    }
}

// Verifică dacă tabela clinica_doctors NU există (ar trebui să nu existe)
$doctors_table = $wpdb->prefix . 'clinica_doctors';
$doctors_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$doctors_table'") === $doctors_table;

if (!$doctors_table_exists) {
    echo '<div style="color: green;">✅ Tabela ' . esc_html($doctors_table) . ' NU există (corect)</div>';
} else {
    echo '<div style="color: orange;">⚠️ Tabela ' . esc_html($doctors_table) . ' există (ar trebui să nu existe)</div>';
}

echo '<h2>Test Query Direct</h2>';

// Testează query-ul direct
try {
    $test_query = "
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.type,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            dm1.meta_value as doctor_first_name,
            dm2.meta_value as doctor_last_name
        FROM {$wpdb->prefix}clinica_appointments a
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON a.patient_id = p.id
        LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
        LEFT JOIN {$wpdb->usermeta} dm1 ON d.ID = dm1.user_id AND dm1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} dm2 ON d.ID = dm2.user_id AND dm2.meta_key = 'last_name'
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ";
    
    $results = $wpdb->get_results($test_query);
    
    if ($results !== false) {
        echo '<div style="color: green;">✅ Query-ul funcționează fără erori</div>';
        echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc;">';
        echo '<strong>Rezultate găsite:</strong> ' . count($results) . '<br>';
        if (!empty($results)) {
            echo '<strong>Primul rezultat:</strong><br>';
            echo '<pre>' . esc_html(print_r($results[0], true)) . '</pre>';
        }
        echo '</div>';
    } else {
        echo '<div style="color: red;">❌ Query-ul a eșuat</div>';
        echo '<div style="color: red;">Eroare: ' . esc_html($wpdb->last_error) . '</div>';
    }
} catch (Exception $e) {
    echo '<div style="color: red;">❌ Eroare la testarea query-ului: ' . esc_html($e->getMessage()) . '</div>';
}

echo '<h2>Rezumat</h2>';
echo '<p>Dacă toate testele de mai sus afișează ✅ verde, atunci corectările au fost aplicate cu succes.</p>';
echo '<p>Erorile din log-urile WordPress ar trebui să dispară după aceste corectări.</p>';
?> 