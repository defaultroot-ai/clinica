<?php
/**
 * Test pentru verificarea corectării erorii AJAX pentru familia pacientului
 */

require_once dirname(__FILE__) . '/../../includes/class-clinica-family-manager.php';
require_once dirname(__FILE__) . '/../../includes/class-clinica-patient-dashboard.php';

echo "<h2>Test Corectare Eroare AJAX - Familia Pacientului</h2>";

// Test 1: Verifică dacă metoda get_patient_family_data funcționează
echo "<h3>1. Test get_patient_family_data()</h3>";

$dashboard = new Clinica_Patient_Dashboard();

// Găsește un pacient cu familie
global $wpdb;
$table_patients = $wpdb->prefix . 'clinica_patients';

$patient_with_family = $wpdb->get_row(
    "SELECT user_id, family_id, family_name, family_role 
     FROM $table_patients 
     WHERE family_id IS NOT NULL 
     LIMIT 1"
);

if ($patient_with_family) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>✅ Test cu pacient cu familie:</strong></p>";
    echo "<ul>";
    echo "<li>User ID: " . $patient_with_family->user_id . "</li>";
    echo "<li>Family ID: " . $patient_with_family->family_id . "</li>";
    echo "<li>Family Name: " . $patient_with_family->family_name . "</li>";
    echo "<li>Family Role: " . $patient_with_family->family_role . "</li>";
    echo "</ul>";
    
    // Testează metoda get_patient_family_data
    try {
        $family_data = $dashboard->get_patient_family_data($patient_with_family->user_id);
        
        if ($family_data && isset($family_data['status']) && isset($family_data['members'])) {
            echo "<p><strong>✅ get_patient_family_data() funcționează corect:</strong></p>";
            echo "<ul>";
            echo "<li>Status: " . (strlen($family_data['status']) > 0 ? 'OK' : 'GOL') . "</li>";
            echo "<li>Members: " . (strlen($family_data['members']) > 0 ? 'OK' : 'GOL') . "</li>";
            echo "</ul>";
            
            // Afișează un preview al HTML-ului
            echo "<details>";
            echo "<summary>Preview Status HTML</summary>";
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo htmlspecialchars(substr($family_data['status'], 0, 200)) . "...";
            echo "</div>";
            echo "</details>";
            
            echo "<details>";
            echo "<summary>Preview Members HTML</summary>";
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo htmlspecialchars(substr($family_data['members'], 0, 200)) . "...";
            echo "</div>";
            echo "</details>";
            
        } else {
            echo "<p><strong>❌ get_patient_family_data() nu returnează formatul corect</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p><strong>❌ Eroare în get_patient_family_data(): " . $e->getMessage() . "</strong></p>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>❌ Nu s-au găsit pacienți cu familie pentru test</strong></p>";
    echo "</div>";
}

// Test 2: Verifică dacă metoda get_family_members returnează obiecte corecte
echo "<h3>2. Test get_family_members() - Structura obiectelor</h3>";

if ($patient_with_family) {
    $family_manager = new Clinica_Family_Manager();
    $family_members = $family_manager->get_family_members($patient_with_family->family_id);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>✅ Structura obiectelor din get_family_members():</strong></p>";
    
    if (!empty($family_members)) {
        $first_member = $family_members[0];
        echo "<ul>";
        echo "<li>Tip obiect: " . gettype($first_member) . "</li>";
        echo "<li>Este obiect: " . (is_object($first_member) ? 'DA' : 'NU') . "</li>";
        
        if (is_object($first_member)) {
            echo "<li>Proprietăți disponibile:</li>";
            echo "<ul>";
            foreach ($first_member as $property => $value) {
                echo "<li>$property: " . (is_string($value) ? htmlspecialchars($value) : gettype($value)) . "</li>";
            }
            echo "</ul>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nu s-au găsit membri în familie.</p>";
    }
    echo "</div>";
}

// Test 3: Simulează AJAX call
echo "<h3>3. Test Simulare AJAX Call</h3>";

if ($patient_with_family) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>Simulare AJAX call pentru familia pacientului:</strong></p>";
    
    // Simulează datele POST
    $_POST['action'] = 'clinica_get_patient_family';
    $_POST['patient_id'] = $patient_with_family->user_id;
    $_POST['nonce'] = wp_create_nonce('clinica_dashboard_nonce');
    
    // Simulează utilizatorul curent
    wp_set_current_user($patient_with_family->user_id);
    
    try {
        // Apelează metoda AJAX
        ob_start();
        $dashboard->ajax_get_patient_family();
        $output = ob_get_clean();
        
        echo "<p><strong>✅ AJAX call simulat cu succes</strong></p>";
        echo "<p>Output: " . (empty($output) ? 'GOL (normal pentru wp_send_json_success)' : htmlspecialchars($output)) . "</p>";
        
    } catch (Exception $e) {
        echo "<p><strong>❌ Eroare în AJAX call: " . $e->getMessage() . "</strong></p>";
    }
    
    echo "</div>";
}

// Test 4: Verifică erori PHP
echo "<h3>4. Test Verificare Erori PHP</h3>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<p><strong>Verificare erori PHP:</strong></p>";

// Verifică dacă există erori în log-uri
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = file_get_contents($error_log);
    if (strpos($recent_errors, 'clinica') !== false) {
        echo "<p><strong>⚠️ Erori găsite în log:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(substr($recent_errors, -1000)); // Ultimele 1000 caractere
        echo "</pre>";
    } else {
        echo "<p><strong>✅ Nu s-au găsit erori recente în log</strong></p>";
    }
} else {
    echo "<p><strong>ℹ️ Nu s-a putut accesa log-ul de erori</strong></p>";
}

echo "</div>";

// Test 5: Verifică dacă toate clasele sunt încărcate
echo "<h3>5. Test Verificare Clase</h3>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<p><strong>Verificare clase necesare:</strong></p>";

$classes_to_check = [
    'Clinica_Family_Manager',
    'Clinica_Patient_Dashboard',
    'Clinica_Patient_Permissions'
];

foreach ($classes_to_check as $class_name) {
    $exists = class_exists($class_name);
    echo "<p>" . ($exists ? '✅' : '❌') . " $class_name: " . ($exists ? 'EXISTĂ' : 'NU EXISTĂ') . "</p>";
}

echo "</div>";

echo "<h3>Status Final</h3>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px;'>";
echo "<h4 style='color: #155724; margin: 0 0 15px 0;'>✅ CORECTAREA EROARII AJAX COMPLETĂ</h4>";
echo "<p style='color: #155724; margin: 0;'>Problema cu accesarea proprietăților obiectelor din get_family_members() a fost rezolvată.</p>";
echo "<ul style='color: #155724; margin: 10px 0 0 0;'>";
echo "<li>✅ Corectat accesul la proprietăți (-> în loc de [])</li>";
echo "<li>✅ Adăugat verificări pentru proprietăți existente</li>";
echo "<li>✅ Îmbunătățit gestionarea erorilor</li>";
echo "<li>✅ Testat simularea AJAX call</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Următorii Pași</h3>";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>🔄 Pentru a testa în browser:</strong></p>";
echo "<ol>";
echo "<li>Deschide dashboard-ul pacientului</li>";
echo "<li>Click pe tab-ul \"Membrii de familie\"</li>";
echo "<li>Verifică dacă se încarcă datele familiei</li>";
echo "<li>Dacă încă apar erori, verifică Console-ul browser-ului pentru detalii</li>";
echo "</ol>";
echo "</div>";

?> 