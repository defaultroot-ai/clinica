<?php
/**
 * Script de verificare utilizatori care nu sunt în tabela pacienților
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/check-missing-patients.php
 */

// Include WordPress
require_once('wp-load.php');

// Include clasele Clinica
require_once('wp-content/plugins/clinica/includes/class-clinica-roles.php');

echo "<h1>Verificare Utilizatori Lipsă din Tabela Pacienți</h1>";

// Obține toți utilizatorii (excluzând administratorii WordPress)
$users = get_users(array(
    'exclude' => array(1), // Exclude administratorul principal WordPress
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Verifică utilizatorii din tabela pacienți
global $wpdb;
$patients_in_table = $wpdb->get_results("
    SELECT user_id, cnp, email, family_role, family_name 
    FROM {$wpdb->prefix}clinica_patients 
    WHERE user_id > 0 
    ORDER BY family_name, cnp
");

// Creează array cu pacienții din tabelă
$patients_user_ids = array();
foreach ($patients_in_table as $patient) {
    $patients_user_ids[] = $patient->user_id;
}

echo "<h2>Statistici</h2>";
echo "<p><strong>Total utilizatori WordPress:</strong> " . count($users) . "</p>";
echo "<p><strong>Total pacienți în tabelă:</strong> " . count($patients_in_table) . "</p>";
echo "<p><strong>Diferență:</strong> " . (count($users) - count($patients_in_table)) . "</p>";

// Găsește utilizatorii care nu sunt în tabela pacienți
$users_not_in_patients = array();
foreach ($users as $user) {
    if (!in_array($user->ID, $patients_user_ids)) {
        $users_not_in_patients[] = $user;
    }
}

echo "<h2>Utilizatori care NU sunt în tabela pacienți</h2>";
echo "<p><strong>Număr găsit:</strong> " . count($users_not_in_patients) . "</p>";

if (!empty($users_not_in_patients)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>Email</th><th>Roluri WordPress</th><th>Are rol Clinica</th><th>Rol Clinica</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($users_not_in_patients as $user) {
        $has_clinica_role = Clinica_Roles::has_clinica_role($user->ID);
        $clinica_role = Clinica_Roles::get_user_role($user->ID);
        
        // Verifică dacă este staff
        $is_staff = false;
        if ($has_clinica_role) {
            $is_staff = in_array($clinica_role, array('clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_manager', 'clinica_administrator'));
        }
        
        $status = '';
        $row_color = '';
        
        if ($is_staff) {
            $status = 'Staff - OK';
            $row_color = 'background-color: #d4edda;';
        } elseif ($has_clinica_role && $clinica_role === 'clinica_patient') {
            $status = 'PROBLEMĂ - Pacient fără înregistrare în tabelă';
            $row_color = 'background-color: #f8d7da;';
        } elseif ($has_clinica_role) {
            $status = 'PROBLEMĂ - Rol Clinica necunoscut: ' . $clinica_role;
            $row_color = 'background-color: #fff3cd;';
        } else {
            $status = 'Utilizator normal WordPress - OK';
            $row_color = 'background-color: #e2e3e5;';
        }
        
        echo "<tr style='$row_color'>";
        echo "<td>" . $user->ID . "</td>";
        echo "<td>" . $user->display_name . "</td>";
        echo "<td>" . $user->user_email . "</td>";
        echo "<td>" . implode(', ', $user->roles) . "</td>";
        echo "<td>" . ($has_clinica_role ? 'DA' : 'NU') . "</td>";
        echo "<td>" . ($clinica_role ? $clinica_role : 'NICIUNUL') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Toți utilizatorii sunt în tabela pacienți!</p>";
}

// Verifică dacă există pacienți în tabelă care nu au utilizatori WordPress
echo "<h2>Verificare Pacienți fără Utilizatori WordPress</h2>";

$all_user_ids = array();
foreach ($users as $user) {
    $all_user_ids[] = $user->ID;
}

$patients_without_users = array();
foreach ($patients_in_table as $patient) {
    if (!in_array($patient->user_id, $all_user_ids)) {
        $patients_without_users[] = $patient;
    }
}

echo "<p><strong>Pacienți fără utilizatori WordPress:</strong> " . count($patients_without_users) . "</p>";

if (!empty($patients_without_users)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>User ID</th><th>CNP</th><th>Email</th><th>Family Role</th><th>Family Name</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($patients_without_users as $patient) {
        echo "<tr style='background-color: #f8d7da;'>";
        echo "<td>" . $patient->user_id . "</td>";
        echo "<td>" . $patient->cnp . "</td>";
        echo "<td>" . $patient->email . "</td>";
        echo "<td>" . $patient->family_role . "</td>";
        echo "<td>" . $patient->family_name . "</td>";
        echo "<td>PROBLEMĂ - Utilizator WordPress șters</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Toți pacienții au utilizatori WordPress!</p>";
}

// Concluzie
echo "<h2>Concluzie</h2>";
echo "<p><strong>Utilizatori WordPress fără înregistrare în tabela pacienți:</strong> " . count($users_not_in_patients) . "</p>";
echo "<p><strong>Pacienți fără utilizatori WordPress:</strong> " . count($patients_without_users) . "</p>";

$total_issues = count($users_not_in_patients) + count($patients_without_users);
echo "<p><strong>Total probleme de sincronizare:</strong> $total_issues</p>";

if ($total_issues === 0) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 SINCRONIZAREA ESTE PERFECTĂ!</p>";
} else {
    echo "<p style='color: orange; font-size: 18px; font-weight: bold;'>⚠️ EXISTĂ PROBLEME DE SINCRONIZARE!</p>";
}
?>
