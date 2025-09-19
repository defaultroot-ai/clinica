<?php
/**
 * Script de verificare roluri în baza de date și WordPress
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/verify-roles-database.php
 */

// Include WordPress
require_once('wp-load.php');

// Include clasele Clinica
require_once('wp-content/plugins/clinica/includes/class-clinica-roles.php');

echo "<h1>Verificare Roluri în Baza de Date și WordPress</h1>";

global $wpdb;

echo "<h2>1. Verificare în Tabela wp_usermeta</h2>";

// Verifică toate rolurile în wp_usermeta
$roles_query = "
    SELECT um.user_id, u.display_name, u.user_email, um.meta_value as capabilities
    FROM {$wpdb->usermeta} um
    INNER JOIN {$wpdb->users} u ON um.user_id = u.ID
    WHERE um.meta_key = 'wp_capabilities'
    ORDER BY u.display_name
";

$all_users_roles = $wpdb->get_results($roles_query);

echo "<p><strong>Total utilizatori cu roluri în wp_usermeta:</strong> " . count($all_users_roles) . "</p>";

// Analizează rolurile
$role_analysis = array();
foreach ($all_users_roles as $user_role) {
    $capabilities = maybe_unserialize($user_role->capabilities);
    if (is_array($capabilities)) {
        foreach ($capabilities as $role => $has_role) {
            if ($has_role) {
                if (!isset($role_analysis[$role])) {
                    $role_analysis[$role] = array();
                }
                $role_analysis[$role][] = array(
                    'id' => $user_role->user_id,
                    'name' => $user_role->display_name,
                    'email' => $user_role->user_email
                );
            }
        }
    }
}

echo "<h3>Roluri găsite în wp_usermeta:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol</th><th>Număr</th><th>Utilizatori</th>";
echo "</tr>";

foreach ($role_analysis as $role => $users) {
    $count = count($users);
    $row_color = '';
    
    if (strpos($role, 'clinica_') === 0) {
        $row_color = 'background-color: #d4edda;';
    } elseif ($role === 'administrator') {
        $row_color = 'background-color: #d1ecf1;';
    } elseif ($role === 'subscriber') {
        $row_color = 'background-color: #e2e3e5;';
    } else {
        $row_color = 'background-color: #fff3cd;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "<td>";
    
    $user_names = array();
    foreach ($users as $user) {
        $user_names[] = $user['name'] . " (ID: " . $user['id'] . ")";
    }
    echo implode('<br>', $user_names);
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>2. Verificare Specifică Roluri Clinica</h2>";

$clinica_roles = array(
    'clinica_administrator' => 'Administrator Clinica',
    'clinica_manager' => 'Manager Clinica',
    'clinica_doctor' => 'Doctor',
    'clinica_assistant' => 'Asistent',
    'clinica_receptionist' => 'Receptionist',
    'clinica_patient' => 'Pacient'
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol Clinica</th><th>Descriere</th><th>Număr în DB</th><th>Utilizatori</th>";
echo "</tr>";

foreach ($clinica_roles as $role => $description) {
    $users_with_role = isset($role_analysis[$role]) ? $role_analysis[$role] : array();
    $count = count($users_with_role);
    
    $row_color = $count > 0 ? 'background-color: #d4edda;' : 'background-color: #f8d7da;';
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$description</td>";
    echo "<td>$count</td>";
    echo "<td>";
    
    if ($count > 0) {
        $user_names = array();
        foreach ($users_with_role as $user) {
            $user_names[] = $user['name'] . " (ID: " . $user['id'] . ")";
        }
        echo implode('<br>', $user_names);
    } else {
        echo "<span style='color: red;'>❌ NICIUN UTILIZATOR</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. Verificare prin Funcții WordPress</h2>";

// Verifică prin funcțiile WordPress
$users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));

echo "<p><strong>Total utilizatori prin get_users():</strong> " . count($users) . "</p>";

$wp_role_counts = array();
foreach ($users as $user) {
    foreach ($user->roles as $role) {
        if (!isset($wp_role_counts[$role])) {
            $wp_role_counts[$role] = 0;
        }
        $wp_role_counts[$role]++;
    }
}

echo "<h3>Roluri prin get_users():</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol</th><th>Număr</th>";
echo "</tr>";

foreach ($wp_role_counts as $role => $count) {
    $row_color = '';
    
    if (strpos($role, 'clinica_') === 0) {
        $row_color = 'background-color: #d4edda;';
    } elseif ($role === 'administrator') {
        $row_color = 'background-color: #d1ecf1;';
    } elseif ($role === 'subscriber') {
        $row_color = 'background-color: #e2e3e5;';
    } else {
        $row_color = 'background-color: #fff3cd;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. Verificare Funcții Clinica</h2>";

// Testează funcțiile Clinica
$clinica_users = array();
foreach ($users as $user) {
    if (Clinica_Roles::has_clinica_role($user->ID)) {
        $clinica_role = Clinica_Roles::get_user_role($user->ID);
        if (!isset($clinica_users[$clinica_role])) {
            $clinica_users[$clinica_role] = array();
        }
        $clinica_users[$clinica_role][] = $user;
    }
}

echo "<h3>Utilizatori cu roluri Clinica (prin funcții):</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol Clinica</th><th>Număr</th><th>Utilizatori</th>";
echo "</tr>";

foreach ($clinica_roles as $role => $description) {
    $users_with_role = isset($clinica_users[$role]) ? $clinica_users[$role] : array();
    $count = count($users_with_role);
    
    $row_color = $count > 0 ? 'background-color: #d4edda;' : 'background-color: #f8d7da;';
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "<td>";
    
    if ($count > 0) {
        $user_names = array();
        foreach ($users_with_role as $user) {
            $user_names[] = $user->display_name . " (ID: " . $user->ID . ")";
        }
        echo implode('<br>', $user_names);
    } else {
        echo "<span style='color: red;'>❌ NICIUN UTILIZATOR</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. Comparație Metode de Verificare</h2>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol</th><th>wp_usermeta</th><th>get_users()</th><th>Clinica Functions</th><th>Status</th>";
echo "</tr>";

foreach ($clinica_roles as $role => $description) {
    $db_count = isset($role_analysis[$role]) ? count($role_analysis[$role]) : 0;
    $wp_count = isset($wp_role_counts[$role]) ? $wp_role_counts[$role] : 0;
    $clinica_count = isset($clinica_users[$role]) ? count($clinica_users[$role]) : 0;
    
    $status = '';
    $row_color = '';
    
    if ($db_count == $wp_count && $wp_count == $clinica_count) {
        $status = '✅ CONSISTENT';
        $row_color = 'background-color: #d4edda;';
    } else {
        $status = '❌ INCONSISTENT';
        $row_color = 'background-color: #f8d7da;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$db_count</td>";
    echo "<td>$wp_count</td>";
    echo "<td>$clinica_count</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>6. Verificare Raw Data wp_usermeta</h2>";

// Verifică raw data pentru roluri Clinica
$clinica_raw_query = "
    SELECT um.user_id, u.display_name, u.user_email, um.meta_value
    FROM {$wpdb->usermeta} um
    INNER JOIN {$wpdb->users} u ON um.user_id = u.ID
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%clinica_%'
    ORDER BY u.display_name
";

$clinica_raw_data = $wpdb->get_results($clinica_raw_query);

echo "<p><strong>Utilizatori cu roluri Clinica în wp_usermeta:</strong> " . count($clinica_raw_data) . "</p>";

if (!empty($clinica_raw_data)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>Email</th><th>Raw Capabilities</th>";
    echo "</tr>";
    
    foreach ($clinica_raw_data as $user_data) {
        echo "<tr>";
        echo "<td>" . $user_data->user_id . "</td>";
        echo "<td>" . $user_data->display_name . "</td>";
        echo "<td>" . $user_data->user_email . "</td>";
        echo "<td style='font-family: monospace; font-size: 12px;'>" . htmlspecialchars($user_data->meta_value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Nu s-au găsit utilizatori cu roluri Clinica în wp_usermeta!</p>";
}

echo "<h2>7. Concluzie</h2>";

$total_clinica_users = 0;
foreach ($clinica_roles as $role => $description) {
    $count = isset($role_analysis[$role]) ? count($role_analysis[$role]) : 0;
    $total_clinica_users += $count;
}

echo "<p><strong>Total utilizatori cu roluri Clinica:</strong> $total_clinica_users</p>";

if ($total_clinica_users < 7) { // 4 doctori + 2 asistenți + 1 receptionist + 1 manager
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ PROBLEMĂ CONFIRMATĂ: Lipsesc roluri staff din baza de date!</p>";
} else {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ Toate rolurile sunt prezente în baza de date!</p>";
}
?>
