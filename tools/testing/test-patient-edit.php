<?php
/**
 * Test script pentru funcționalitatea de editare pacienți
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>🧪 Test Funcționalitate Editare Pacienți</h1>";

// 1. Verifică dacă AJAX handlers sunt înregistrați
echo "<h2>🔍 Verificare AJAX Handlers</h2>";

$ajax_actions = array(
    'clinica_get_patient_data',
    'clinica_update_patient'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "<p>✅ AJAX handler '$action' este înregistrat</p>";
    } else {
        echo "<p>❌ AJAX handler '$action' NU este înregistrat</p>";
    }
}

// 2. Verifică dacă există pacienți pentru test
echo "<h2>👥 Verificare Pacienți Disponibili</h2>";

$clinica_table = $wpdb->prefix . 'clinica_patients';
$patients = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email
    FROM $clinica_table p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 3
");

if (!empty($patients)) {
    echo "<p>✅ S-au găsit " . count($patients) . " pacienți pentru test</p>";
    
    echo "<h3>Pacienți disponibili:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>CNP</th><th>Email</th><th>Telefon Principal</th><th>Telefon Secundar</th>";
    echo "</tr>";
    
    foreach ($patients as $patient) {
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>{$patient->display_name}</td>";
        echo "<td>{$patient->cnp}</td>";
        echo "<td>{$patient->user_email}</td>";
        echo "<td>" . ($patient->phone_primary ?: 'N/A') . "</td>";
        echo "<td>" . ($patient->phone_secondary ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Nu s-au găsit pacienți pentru test</p>";
}

// 3. Test AJAX handler pentru obținerea datelor
echo "<h2>🧪 Test AJAX Get Patient Data</h2>";

if (!empty($patients)) {
    $test_patient = $patients[0];
    
    // Simulează AJAX request
    $_POST['action'] = 'clinica_get_patient_data';
    $_POST['patient_id'] = $test_patient->user_id;
    $_POST['nonce'] = wp_create_nonce('clinica_nonce');
    
    // Capturează output-ul
    ob_start();
    
    // Apelează handler-ul
    do_action('wp_ajax_clinica_get_patient_data');
    
    $output = ob_get_clean();
    
    echo "<p><strong>Test pentru pacientul:</strong> {$test_patient->display_name} (ID: {$test_patient->user_id})</p>";
    
    if (!empty($output)) {
        echo "<p>✅ AJAX handler a returnat date</p>";
        echo "<pre style='background: #f9f9f9; padding: 10px; border: 1px solid #ccc;'>";
        echo htmlspecialchars($output);
        echo "</pre>";
    } else {
        echo "<p>❌ AJAX handler nu a returnat date</p>";
    }
}

// 4. Verifică nonce-urile
echo "<h2>🔐 Verificare Nonce-uri</h2>";

$nonce_names = array(
    'clinica_nonce',
    'clinica_get_patient_data',
    'clinica_update_patient'
);

foreach ($nonce_names as $nonce_name) {
    $nonce = wp_create_nonce($nonce_name);
    echo "<p><strong>$nonce_name:</strong> $nonce</p>";
}

// 5. Verifică permisiunile
echo "<h2>🔑 Verificare Permisiuni</h2>";

$current_user = wp_get_current_user();
echo "<p><strong>Utilizator curent:</strong> {$current_user->display_name} (ID: {$current_user->ID})</p>";
echo "<p><strong>Roluri:</strong> " . implode(', ', $current_user->roles) . "</p>";
echo "<p><strong>Can manage options:</strong> " . (current_user_can('manage_options') ? '✅ Da' : '❌ Nu') . "</p>";

// 6. Test direct al metodei din clasa principală
echo "<h2>🧪 Test Direct Metodă Clasă Principală</h2>";

if (class_exists('Clinica_Plugin')) {
    $clinica = Clinica_Plugin::get_instance();
    
    if (method_exists($clinica, 'ajax_get_patient_data')) {
        echo "<p>✅ Metoda ajax_get_patient_data există</p>";
        
        if (!empty($patients)) {
            $test_patient = $patients[0];
            
            // Test direct
            $_POST['patient_id'] = $test_patient->user_id;
            $_POST['nonce'] = wp_create_nonce('clinica_nonce');
            
            ob_start();
            $clinica->ajax_get_patient_data();
            $output = ob_get_clean();
            
            if (!empty($output)) {
                echo "<p>✅ Metoda directă funcționează</p>";
            } else {
                echo "<p>❌ Metoda directă nu returnează date</p>";
            }
        }
    } else {
        echo "<p>❌ Metoda ajax_get_patient_data nu există</p>";
    }
} else {
    echo "<p>❌ Clasa Clinica_Plugin nu există</p>";
}

// 7. Verifică dacă pagina de pacienți există
echo "<h2>📄 Verificare Pagină Pacienți</echo>";

$patients_page_url = admin_url('admin.php?page=clinica-patients');
echo "<p><strong>URL pagină pacienți:</strong> <a href='$patients_page_url' target='_blank'>$patients_page_url</a></p>";

echo "<hr>";
echo "<p><em>Test rulat la: " . current_time('mysql') . "</em></p>";
?> 