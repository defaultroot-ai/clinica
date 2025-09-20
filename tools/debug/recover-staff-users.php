<?php
/**
 * Script de recuperare utilizatori staff
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/recover-staff-users.php
 */

// Include WordPress
require_once('wp-load.php');

echo "<h1>Recuperare Utilizatori Staff</h1>";

global $wpdb;

// Caută utilizatori care ar putea fi staff
echo "<h2>Căutare Utilizatori Potențiali Staff</h2>";

// Caută în numele utilizatorilor termeni care sugerează că sunt staff
$potential_staff = $wpdb->get_results("
    SELECT ID, user_login, display_name, user_email 
    FROM wp_users 
    WHERE (
        display_name LIKE '%doctor%' OR 
        display_name LIKE '%asistent%' OR 
        display_name LIKE '%receptionist%' OR 
        display_name LIKE '%manager%' OR
        display_name LIKE '%Dr.%' OR
        display_name LIKE '%medic%' OR
        user_email LIKE '%doctor%' OR
        user_email LIKE '%cabinet%' OR
        user_email LIKE '%clinica%'
    )
    AND ID NOT IN (2626, 1939)
    ORDER BY display_name
");

echo "<p><strong>Potențiali utilizatori staff găsiți:</strong> " . count($potential_staff) . "</p>";

if (!empty($potential_staff)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Login</th><th>Nume</th><th>Email</th><th>Roluri Actuale</th>";
    echo "</tr>";
    
    foreach ($potential_staff as $user) {
        $wp_user = get_user_by('ID', $user->ID);
        $roles = implode(', ', $wp_user->roles);
        
        echo "<tr>";
        echo "<td>" . $user->ID . "</td>";
        echo "<td>" . $user->user_login . "</td>";
        echo "<td>" . $user->display_name . "</td>";
        echo "<td>" . $user->user_email . "</td>";
        echo "<td>" . $roles . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Caută utilizatori care nu sunt în tabela pacienți dar au subscriber
echo "<h2>Utilizatori cu doar rol 'subscriber' (nu sunt pacienți)</h2>";

$non_patients = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.display_name, u.user_email
    FROM wp_users u
    INNER JOIN wp_usermeta um ON u.ID = um.user_id
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value = 'a:1:{s:10:\"subscriber\";b:1;}'
    AND u.ID NOT IN (SELECT user_id FROM wp_clinica_patients WHERE user_id IS NOT NULL)
    AND u.ID NOT IN (1, 2626, 1939)
    ORDER BY u.display_name
    LIMIT 20
");

echo "<p><strong>Utilizatori cu doar rol subscriber (nu pacienți):</strong> " . count($non_patients) . "</p>";

if (!empty($non_patients)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Login</th><th>Nume</th><th>Email</th><th>Sugestie Rol</th>";
    echo "</tr>";
    
    foreach ($non_patients as $user) {
        $suggested_role = '';
        $name = strtolower($user->display_name);
        $email = strtolower($user->user_email);
        
        if (strpos($name, 'doctor') !== false || strpos($name, 'dr.') !== false || strpos($email, 'cabinet') !== false) {
            $suggested_role = 'clinica_doctor';
        } elseif (strpos($name, 'asistent') !== false) {
            $suggested_role = 'clinica_assistant';
        } elseif (strpos($name, 'receptionist') !== false) {
            $suggested_role = 'clinica_receptionist';
        } elseif (strpos($name, 'manager') !== false) {
            $suggested_role = 'clinica_manager';
        } else {
            $suggested_role = 'poate fi staff?';
        }
        
        echo "<tr>";
        echo "<td>" . $user->ID . "</td>";
        echo "<td>" . $user->user_login . "</td>";
        echo "<td>" . $user->display_name . "</td>";
        echo "<td>" . $user->user_email . "</td>";
        echo "<td><strong>" . $suggested_role . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Concluzii</h2>";
echo "<p><strong>Situația actuală:</strong></p>";
echo "<ul>";
echo "<li>1 doctor: Coserea Andreea (ID: 2626)</li>";
echo "<li>1 administrator: Ulieru Ionut-Bogdan (ID: 1939)</li>";
echo "<li>0 asistenți</li>";
echo "<li>0 receptioneri</li>";
echo "<li>0 manageri</li>";
echo "</ul>";

echo "<p style='color: red; font-size: 16px;'><strong>CAUZA PROBLEMEI:</strong> Scriptul fix-assistant-permissions.php a șters rolurile clinica_assistant și clinica_receptionist din sistem!</p>";

echo "<h2>Soluții de Recuperare</h2>";
echo "<p>1. <strong>Recreez rolurile:</strong> Rulez din nou crearea rolurilor</p>";
echo "<p>2. <strong>Identific utilizatorii staff:</strong> Caut manual utilizatorii care erau staff</p>";
echo "<p>3. <strong>Atribui rolurile corecte:</strong> Dau rolurile potrivite utilizatorilor identificați</p>";

?>
