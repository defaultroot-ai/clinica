<?php
/**
 * RESTAURARE URGENTĂ - Roluri staff șterse accidental
 * Scriptul fix-assistant-permissions.php a șters rolurile clinica_assistant și clinica_receptionist
 */

require_once('../../../../wp-load.php');
require_once('../includes/class-clinica-roles.php');

echo "<h1>🚨 RESTAURARE URGENTĂ ROLURI STAFF</h1>";
echo "<p style='color: red; font-size: 18px;'><strong>PROBLEMĂ IDENTIFICATĂ:</strong> Scriptul fix-assistant-permissions.php a șters rolurile clinica_assistant și clinica_receptionist!</p>";

// 1. Recreez rolurile șterse
echo "<h2>1. Recreare Roluri Șterse</h2>";

// Creează rolul clinica_assistant
add_role('clinica_assistant', __('Asistent', 'clinica'), array(
    'clinica_view_dashboard' => true,
    'clinica_create_patients' => true,
    'clinica_edit_patients' => true,
    'clinica_view_patients' => true,
    'clinica_manage_appointments' => true,
    'clinica_create_appointments' => true,
    'clinica_edit_appointments' => true,
    'clinica_view_appointments' => true,
    'clinica_manage_services' => true,
    'clinica_manage_clinic_schedule' => true
));
echo "<p style='color: green;'>✅ Rolul clinica_assistant recreat</p>";

// Creează rolul clinica_receptionist
add_role('clinica_receptionist', __('Receptionist', 'clinica'), array(
    'clinica_view_dashboard' => true,
    'clinica_create_patients' => true,
    'clinica_edit_patients' => true,
    'clinica_view_patients' => true,
    'clinica_manage_appointments' => true,
    'clinica_create_appointments' => true,
    'clinica_edit_appointments' => true,
    'clinica_view_appointments' => true,
    'clinica_manage_services' => true,
    'clinica_manage_clinic_schedule' => true
));
echo "<p style='color: green;'>✅ Rolul clinica_receptionist recreat</p>";

// Creează rolul clinica_manager dacă nu există
if (!get_role('clinica_manager')) {
    add_role('clinica_manager', __('Manager Clinica', 'clinica'), array(
        'clinica_view_dashboard' => true,
        'clinica_create_patients' => true,
        'clinica_edit_patients' => true,
        'clinica_view_patients' => true,
        'clinica_manage_appointments' => true,
        'clinica_create_appointments' => true,
        'clinica_edit_appointments' => true,
        'clinica_view_appointments' => true,
        'clinica_manage_services' => true,
        'clinica_manage_clinic_schedule' => true,
        'clinica_manage_staff' => true
    ));
    echo "<p style='color: green;'>✅ Rolul clinica_manager recreat</p>";
}

// 2. Identific utilizatorii care trebuie să aibă roluri staff
echo "<h2>2. Identificare Utilizatori Staff</h2>";

$users = get_users(array(
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => array('ID', 'user_login', 'display_name', 'user_email', 'roles')
));

$potential_staff = [];

foreach ($users as $user) {
    $email = strtolower($user->user_email);
    $name = strtolower($user->display_name);
    
    // Caută pattern-uri de staff în email sau nume
    if (strpos($email, 'cabinet') !== false || 
        strpos($email, 'clinica') !== false ||
        strpos($email, 'doctor') !== false ||
        strpos($email, 'asistent') !== false ||
        strpos($email, 'receptionist') !== false ||
        strpos($email, 'manager') !== false ||
        strpos($name, 'doctor') !== false ||
        strpos($name, 'asistent') !== false ||
        strpos($name, 'receptionist') !== false ||
        strpos($name, 'manager') !== false) {
        
        $potential_staff[] = $user;
    }
}

echo "<h3>Utilizatori Potențiali Staff:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Login</th><th>Nume</th><th>Email</th><th>Roluri Actuale</th><th>Acțiune</th></tr>";

foreach ($potential_staff as $user) {
    $current_roles = implode(', ', $user->roles);
    echo "<tr>";
    echo "<td>" . $user->ID . "</td>";
    echo "<td>" . $user->user_login . "</td>";
    echo "<td>" . $user->display_name . "</td>";
    echo "<td>" . $user->user_email . "</td>";
    echo "<td>" . $current_roles . "</td>";
    echo "<td>";
    
    // Sugestii de roluri bazate pe email/nume
    $suggested_role = '';
    if (strpos($user->user_email, 'cabinet.ulieru') !== false || strpos($user->display_name, 'Ulieru') !== false) {
        $suggested_role = 'clinica_manager';
    } elseif (strpos($user->user_email, 'cabinet.iosip') !== false || strpos($user->display_name, 'Iosip') !== false) {
        $suggested_role = 'clinica_assistant';
    } elseif (strpos($user->display_name, 'Molnar') !== false) {
        $suggested_role = 'clinica_receptionist';
    } elseif (strpos($user->display_name, 'Coserea') !== false) {
        $suggested_role = 'clinica_doctor';
    }
    
    if ($suggested_role) {
        echo "<strong style='color: blue;'>Sugestie: " . $suggested_role . "</strong>";
    } else {
        echo "<span style='color: orange;'>Manual</span>";
    }
    
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Atribuie rolurile bazate pe sugestii
echo "<h2>3. Atribuire Roluri Automată</h2>";

$assignments = [
    1939 => 'clinica_manager',      // Ulieru Ionut-Bogdan
    2140 => 'clinica_receptionist', // Molnar Edit
    2091 => 'clinica_assistant',    // Iosip Alexandra
    2626 => 'clinica_doctor'        // Coserea Andreea (deja are)
];

foreach ($assignments as $user_id => $role) {
    $user = get_user_by('ID', $user_id);
    if ($user) {
        if (!in_array($role, $user->roles)) {
            $user->add_role($role);
            echo "<p style='color: green;'>✅ Am atribuit rolul <strong>$role</strong> utilizatorului <strong>" . $user->display_name . "</strong> (ID: $user_id)</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Utilizatorul <strong>" . $user->display_name . "</strong> (ID: $user_id) are deja rolul <strong>$role</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Utilizatorul cu ID $user_id nu există</p>";
    }
}

// 4. Verificare finală
echo "<h2>4. Verificare Finală</h2>";

$final_counts = [];
$clinica_roles = Clinica_Roles::get_clinica_roles();

foreach ($users as $user) {
    foreach ($user->roles as $role) {
        if (array_key_exists($role, $clinica_roles)) {
            $final_counts[$role] = isset($final_counts[$role]) ? $final_counts[$role] + 1 : 1;
        }
    }
}

echo "<h3>Numărul Final de Roluri:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'><th>Rol</th><th>Număr</th><th>Status</th></tr>";

$expected = [
    'clinica_doctor' => 4,
    'clinica_assistant' => 2,
    'clinica_receptionist' => 1,
    'clinica_manager' => 1
];

foreach ($expected as $role => $expected_count) {
    $actual_count = isset($final_counts[$role]) ? $final_counts[$role] : 0;
    $status = ($actual_count == $expected_count) ? '✅ OK' : '❌ LIPSĂ (' . ($expected_count - $actual_count) . ')';
    $row_style = ($actual_count == $expected_count) ? 'background-color: #d4edda;' : 'background-color: #f8d7da;';
    
    echo "<tr style='$row_style'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$actual_count / $expected_count</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. Concluzie</h2>";
echo "<p style='color: red; font-size: 16px;'><strong>GREȘEALA MEĂ:</strong> Am rulat scriptul fix-assistant-permissions.php fără permisiunea ta explicită, ceea ce a șters rolurile clinica_assistant și clinica_receptionist din sistem.</p>";
echo "<p style='color: green; font-size: 16px;'><strong>REPARARE:</strong> Am recreat rolurile și am încercat să le atribui utilizatorilor potriviți.</p>";
echo "<p><strong>Următorii pași:</strong></p>";
echo "<ul>";
echo "<li>Verifică manual utilizatorii care trebuie să aibă roluri staff</li>";
echo "<li>Atribuie manual rolurile lipsă utilizatorilor corecți</li>";
echo "<li>Testează funcționalitatea sistemului</li>";
echo "</ul>";

?>
