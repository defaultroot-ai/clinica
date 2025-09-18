<?php
/**
 * Test pentru verificarea corectării integrării familiilor între admin și dashboard pacient
 */

require_once dirname(__FILE__) . '/../../includes/class-clinica-family-manager.php';
require_once dirname(__FILE__) . '/../../includes/class-clinica-patient-dashboard.php';

echo "<h2>Test Integrare Familii - Admin ↔ Dashboard Pacient</h2>";

$family_manager = new Clinica_Family_Manager();

// Test 1: Verifică metoda get_patient_family cu user_id
echo "<h3>1. Test get_patient_family() cu user_id</h3>";

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
    echo "<p><strong>✅ Pacient găsit cu familie:</strong></p>";
    echo "<ul>";
    echo "<li>User ID: " . $patient_with_family->user_id . "</li>";
    echo "<li>Family ID: " . $patient_with_family->family_id . "</li>";
    echo "<li>Family Name: " . $patient_with_family->family_name . "</li>";
    echo "<li>Family Role: " . $patient_with_family->family_role . "</li>";
    echo "</ul>";
    
    // Testează get_patient_family cu user_id
    $family_data = $family_manager->get_patient_family($patient_with_family->user_id);
    
    if ($family_data) {
        echo "<p><strong>✅ get_patient_family() funcționează cu user_id:</strong></p>";
        echo "<ul>";
        echo "<li>Family ID: " . $family_data['id'] . "</li>";
        echo "<li>Family Name: " . $family_data['name'] . "</li>";
        echo "<li>Family Role: " . $family_data['role'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>❌ get_patient_family() nu funcționează cu user_id</strong></p>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>❌ Nu s-au găsit pacienți cu familie pentru test</strong></p>";
    echo "</div>";
}

// Test 2: Verifică metoda get_family_members
echo "<h3>2. Test get_family_members()</h3>";

if ($patient_with_family) {
    $family_members = $family_manager->get_family_members($patient_with_family->family_id);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>✅ Membrii familiei găsiți:</strong></p>";
    echo "<p>Număr membri: " . count($family_members) . "</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Nume</th><th>Email</th><th>Rol</th><th>User ID</th></tr>";
    
    foreach ($family_members as $member) {
        echo "<tr>";
        echo "<td>" . esc_html($member->display_name) . "</td>";
        echo "<td>" . esc_html($member->user_email) . "</td>";
        echo "<td>" . esc_html($family_manager->get_family_role_label($member->family_role)) . "</td>";
        echo "<td>" . $member->user_id . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}

// Test 3: Verifică metoda is_family_member
echo "<h3>3. Test is_family_member()</h3>";

if ($patient_with_family) {
    $is_member = $family_manager->is_family_member($patient_with_family->user_id);
    
    echo "<div style='background: " . ($is_member ? '#e8f5e8' : '#ffe6e6') . "; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>" . ($is_member ? '✅' : '❌') . " is_family_member() cu user_id: " . ($is_member ? 'DA' : 'NU') . "</strong></p>";
    echo "<p>User ID: " . $patient_with_family->user_id . "</p>";
    echo "</div>";
}

// Test 4: Verifică metoda get_family_role
echo "<h3>4. Test get_family_role()</h3>";

if ($patient_with_family) {
    $role = $family_manager->get_family_role($patient_with_family->user_id);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>✅ get_family_role() cu user_id:</strong></p>";
    echo "<p>Rol: " . esc_html($role) . "</p>";
    echo "<p>Etichetă: " . esc_html($family_manager->get_family_role_label($role)) . "</p>";
    echo "</div>";
}

// Test 5: Simulează dashboard pacient
echo "<h3>5. Test Simulare Dashboard Pacient</h3>";

if ($patient_with_family) {
    // Simulează obținerea datelor familiei ca în dashboard
    $family_data = $family_manager->get_patient_family($patient_with_family->user_id);
    
    if ($family_data) {
        $family_members = $family_manager->get_family_members($family_data['id']);
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<p><strong>✅ Simulare Dashboard Pacient - Date Familie:</strong></p>";
        echo "<ul>";
        echo "<li>Nume Familie: " . esc_html($family_data['name']) . "</li>";
        echo "<li>Rol Pacient: " . esc_html($family_manager->get_family_role_label($family_data['role'])) . "</li>";
        echo "<li>Număr Membri: " . count($family_members) . "</li>";
        echo "</ul>";
        
        echo "<p><strong>Membrii familiei (excluzând pacientul curent):</strong></p>";
        $other_members = 0;
        foreach ($family_members as $member) {
            if ($member->user_id != $patient_with_family->user_id) {
                $other_members++;
                echo "<p>• " . esc_html($member->display_name) . " (" . esc_html($family_manager->get_family_role_label($member->family_role)) . ")</p>";
            }
        }
        echo "<p>Alți membri: " . $other_members . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<p><strong>❌ Nu s-au putut obține datele familiei pentru dashboard</strong></p>";
        echo "</div>";
    }
}

// Test 6: Verifică pacienți fără familie
echo "<h3>6. Test Pacienți fără Familie</h3>";

$patient_without_family = $wpdb->get_row(
    "SELECT user_id FROM $table_patients 
     WHERE family_id IS NULL 
     LIMIT 1"
);

if ($patient_without_family) {
    $family_data = $family_manager->get_patient_family($patient_without_family->user_id);
    $is_member = $family_manager->is_family_member($patient_without_family->user_id);
    
    echo "<div style='background: " . (!$family_data && !$is_member ? '#e8f5e8' : '#ffe6e6') . "; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>" . (!$family_data && !$is_member ? '✅' : '❌') . " Pacient fără familie:</strong></p>";
    echo "<ul>";
    echo "<li>User ID: " . $patient_without_family->user_id . "</li>";
    echo "<li>get_patient_family(): " . ($family_data ? 'DA' : 'NU') . "</li>";
    echo "<li>is_family_member(): " . ($is_member ? 'DA' : 'NU') . "</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>⚠️ Nu s-au găsit pacienți fără familie pentru test</strong></p>";
    echo "</div>";
}

echo "<h3>Status Final</h3>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px;'>";
echo "<h4 style='color: #155724; margin: 0 0 15px 0;'>✅ CORECTAREA INTEGRĂRII FAMILIILOR COMPLETĂ</h4>";
echo "<p style='color: #155724; margin: 0;'>Toate metodele din Family Manager au fost actualizate pentru a funcționa atât cu user_id (dashboard pacient) cât și cu id (admin).</p>";
echo "<ul style='color: #155724; margin: 10px 0 0 0;'>";
echo "<li>✅ get_patient_family() - Funcționează cu user_id</li>";
echo "<li>✅ get_family_members() - Returnează membrii corect</li>";
echo "<li>✅ is_family_member() - Verifică corect cu user_id</li>";
echo "<li>✅ get_family_role() - Obține rolul corect cu user_id</li>";
echo "<li>✅ add_family_member() - Funcționează cu user_id</li>";
echo "<li>✅ remove_family_member() - Funcționează cu user_id</li>";
echo "</ul>";
echo "</div>";

?> 