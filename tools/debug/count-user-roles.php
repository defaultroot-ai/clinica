<?php
/**
 * Script de numărare roluri utilizatori
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/count-user-roles.php
 */

// Include WordPress
require_once('wp-load.php');

// Include clasele Clinica
require_once('wp-content/plugins/clinica/includes/class-clinica-roles.php');

echo "<h1>Numărare Roluri Utilizatori</h1>";

// Obține toți utilizatorii
$users = get_users(array(
    'orderby' => 'display_name',
    'order' => 'ASC'
));

echo "<h2>Statistici Generale</h2>";
echo "<p><strong>Total utilizatori WordPress:</strong> " . count($users) . "</p>";

// Contoare pentru fiecare rol
$role_counts = array(
    'administrator' => 0,
    'subscriber' => 0,
    'clinica_administrator' => 0,
    'clinica_manager' => 0,
    'clinica_doctor' => 0,
    'clinica_assistant' => 0,
    'clinica_receptionist' => 0,
    'clinica_patient' => 0,
    'other' => 0
);

// Contoare pentru combinații de roluri
$combination_counts = array();

// Analizează fiecare utilizator
foreach ($users as $user) {
    $user_roles = $user->roles;
    $has_clinica_role = Clinica_Roles::has_clinica_role($user->ID);
    $clinica_role = Clinica_Roles::get_user_role($user->ID);
    
    // Numără rolurile individuale
    foreach ($user_roles as $role) {
        if (isset($role_counts[$role])) {
            $role_counts[$role]++;
        } else {
            $role_counts['other']++;
        }
    }
    
    // Numără combinațiile de roluri
    $role_combination = implode(' + ', $user_roles);
    if (!isset($combination_counts[$role_combination])) {
        $combination_counts[$role_combination] = 0;
    }
    $combination_counts[$role_combination]++;
}

echo "<h2>Numărare Roluri Individuale</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol</th><th>Număr</th><th>Procent</th><th>Status</th>";
echo "</tr>";

$total_users = count($users);
foreach ($role_counts as $role => $count) {
    $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 2) : 0;
    
    $status = '';
    $row_color = '';
    
    if ($role === 'administrator') {
        $status = 'Administrator WordPress';
        $row_color = 'background-color: #d1ecf1;';
    } elseif (strpos($role, 'clinica_') === 0) {
        $status = 'Rol Clinica';
        $row_color = 'background-color: #d4edda;';
    } elseif ($role === 'subscriber') {
        $status = 'Utilizator Standard';
        $row_color = 'background-color: #e2e3e5;';
    } else {
        $status = 'Alt rol';
        $row_color = 'background-color: #fff3cd;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "<td>$percentage%</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Roluri Clinica Detaliate</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol Clinica</th><th>Număr</th><th>Procent</th><th>Descriere</th>";
echo "</tr>";

$clinica_roles = array(
    'clinica_administrator' => 'Administrator Clinica',
    'clinica_manager' => 'Manager Clinica',
    'clinica_doctor' => 'Doctor',
    'clinica_assistant' => 'Asistent',
    'clinica_receptionist' => 'Receptionist',
    'clinica_patient' => 'Pacient'
);

foreach ($clinica_roles as $role => $description) {
    $count = $role_counts[$role];
    $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 2) : 0;
    
    echo "<tr style='background-color: #d4edda;'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "<td>$percentage%</td>";
    echo "<td>$description</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Staff vs Pacienți</h2>";
$staff_count = $role_counts['clinica_administrator'] + $role_counts['clinica_manager'] + 
               $role_counts['clinica_doctor'] + $role_counts['clinica_assistant'] + 
               $role_counts['clinica_receptionist'];
$patients_count = $role_counts['clinica_patient'];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Categorie</th><th>Număr</th><th>Procent</th>";
echo "</tr>";
echo "<tr style='background-color: #d1ecf1;'>";
echo "<td><strong>Staff Total</strong></td>";
echo "<td>$staff_count</td>";
echo "<td>" . round(($staff_count / $total_users) * 100, 2) . "%</td>";
echo "</tr>";
echo "<tr style='background-color: #d4edda;'>";
echo "<td><strong>Pacienți</strong></td>";
echo "<td>$patients_count</td>";
echo "<td>" . round(($patients_count / $total_users) * 100, 2) . "%</td>";
echo "</tr>";
echo "<tr style='background-color: #e2e3e5;'>";
echo "<td><strong>Administratori WordPress</strong></td>";
echo "<td>" . $role_counts['administrator'] . "</td>";
echo "<td>" . round(($role_counts['administrator'] / $total_users) * 100, 2) . "%</td>";
echo "</tr>";
echo "<tr style='background-color: #fff3cd;'>";
echo "<td><strong>Alți utilizatori</strong></td>";
echo "<td>" . $role_counts['subscriber'] . "</td>";
echo "<td>" . round(($role_counts['subscriber'] / $total_users) * 100, 2) . "%</td>";
echo "</tr>";
echo "</table>";

echo "<h2>Combinații de Roluri (Top 10)</h2>";
arsort($combination_counts);
$top_combinations = array_slice($combination_counts, 0, 10, true);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Combinație Roluri</th><th>Număr</th><th>Procent</th>";
echo "</tr>";

foreach ($top_combinations as $combination => $count) {
    $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 2) : 0;
    
    echo "<tr>";
    echo "<td><strong>$combination</strong></td>";
    echo "<td>$count</td>";
    echo "<td>$percentage%</td>";
    echo "</tr>";
}
echo "</table>";

// Verificare pacienți în tabelă
global $wpdb;
$patients_in_table = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE user_id > 0");

echo "<h2>Verificare Tabela Pacienți</h2>";
echo "<p><strong>Pacienți în tabela wp_clinica_patients:</strong> $patients_in_table</p>";
echo "<p><strong>Pacienți cu rol clinica_patient:</strong> " . $role_counts['clinica_patient'] . "</p>";

$difference = $role_counts['clinica_patient'] - $patients_in_table;
if ($difference > 0) {
    echo "<p style='color: orange;'>⚠️ Diferență: $difference pacienți cu rol dar fără înregistrare în tabelă</p>";
} elseif ($difference < 0) {
    echo "<p style='color: red;'>❌ Diferență: " . abs($difference) . " pacienți în tabelă dar fără rol</p>";
} else {
    echo "<p style='color: green;'>✅ Sincronizare perfectă între roluri și tabela pacienți</p>";
}

echo "<h2>Rezumat Final</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007cba;'>";
echo "<h3>📊 Statistici Complete</h3>";
echo "<ul>";
echo "<li><strong>Total utilizatori:</strong> $total_users</li>";
echo "<li><strong>Administratori WordPress:</strong> " . $role_counts['administrator'] . "</li>";
echo "<li><strong>Staff Clinica:</strong> $staff_count</li>";
echo "<li><strong>Pacienți:</strong> $patients_count</li>";
echo "<li><strong>Alți utilizatori:</strong> " . $role_counts['subscriber'] . "</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Detalii Staff</h2>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<ul>";
echo "<li><strong>Administratori Clinica:</strong> " . $role_counts['clinica_administrator'] . "</li>";
echo "<li><strong>Manageri:</strong> " . $role_counts['clinica_manager'] . "</li>";
echo "<li><strong>Doctori:</strong> " . $role_counts['clinica_doctor'] . "</li>";
echo "<li><strong>Asistenți:</strong> " . $role_counts['clinica_assistant'] . "</li>";
echo "<li><strong>Receptioneri:</strong> " . $role_counts['clinica_receptionist'] . "</li>";
echo "</ul>";
echo "</div>";
?>
