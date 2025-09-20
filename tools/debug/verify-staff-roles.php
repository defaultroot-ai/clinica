<?php
/**
 * Script de verificare extensivă roluri staff
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/verify-staff-roles.php
 */

// Include WordPress
require_once('wp-load.php');

// Include clasele Clinica
require_once('wp-content/plugins/clinica/includes/class-clinica-roles.php');

echo "<h1>Verificare Extensivă Roluri Staff</h1>";

// Obține toți utilizatorii
$users = get_users(array(
    'orderby' => 'display_name',
    'order' => 'ASC'
));

echo "<h2>Analiză Detaliată Roluri</h2>";

// Contoare pentru fiecare rol
$role_counts = array(
    'clinica_administrator' => 0,
    'clinica_manager' => 0,
    'clinica_doctor' => 0,
    'clinica_assistant' => 0,
    'clinica_receptionist' => 0,
    'clinica_patient' => 0
);

// Lista utilizatorilor pentru fiecare rol
$role_users = array(
    'clinica_administrator' => array(),
    'clinica_manager' => array(),
    'clinica_doctor' => array(),
    'clinica_assistant' => array(),
    'clinica_receptionist' => array(),
    'clinica_patient' => array()
);

// Analizează fiecare utilizator
foreach ($users as $user) {
    $user_roles = $user->roles;
    $has_clinica_role = Clinica_Roles::has_clinica_role($user->ID);
    $clinica_role = Clinica_Roles::get_user_role($user->ID);
    
    // Verifică fiecare rol
    foreach ($user_roles as $role) {
        if (strpos($role, 'clinica_') === 0) {
            if (isset($role_counts[$role])) {
                $role_counts[$role]++;
                $role_users[$role][] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'login' => $user->user_login,
                    'all_roles' => $user_roles
                );
            }
        }
    }
}

echo "<h2>Rezultate Roluri Clinica</h2>";

foreach ($role_counts as $role => $count) {
    $role_name = str_replace('clinica_', '', $role);
    $role_name = ucfirst($role_name);
    
    echo "<h3>$role_name: $count utilizatori</h3>";
    
    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Nume</th><th>Email</th><th>Login</th><th>Toate Rolurile</th>";
        echo "</tr>";
        
        foreach ($role_users[$role] as $user_info) {
            echo "<tr>";
            echo "<td>" . $user_info['id'] . "</td>";
            echo "<td>" . $user_info['name'] . "</td>";
            echo "<td>" . $user_info['email'] . "</td>";
            echo "<td>" . $user_info['login'] . "</td>";
            echo "<td>" . implode(', ', $user_info['all_roles']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nu există utilizatori cu rolul $role</p>";
    }
}

// Verificare suplimentară - căutare în toate rolurile
echo "<h2>Verificare Suplimentară - Toate Rolurile</h2>";

$all_roles_found = array();
foreach ($users as $user) {
    foreach ($user->roles as $role) {
        if (!isset($all_roles_found[$role])) {
            $all_roles_found[$role] = 0;
        }
        $all_roles_found[$role]++;
    }
}

echo "<h3>Toate rolurile găsite în sistem:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol</th><th>Număr</th><th>Tip</th>";
echo "</tr>";

foreach ($all_roles_found as $role => $count) {
    $type = '';
    $row_color = '';
    
    if (strpos($role, 'clinica_') === 0) {
        $type = 'Rol Clinica';
        $row_color = 'background-color: #d4edda;';
    } elseif ($role === 'administrator') {
        $type = 'Administrator WordPress';
        $row_color = 'background-color: #d1ecf1;';
    } elseif ($role === 'subscriber') {
        $type = 'Utilizator Standard';
        $row_color = 'background-color: #e2e3e5;';
    } else {
        $type = 'Alt rol';
        $row_color = 'background-color: #fff3cd;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$count</td>";
    echo "<td>$type</td>";
    echo "</tr>";
}
echo "</table>";

// Verificare specifică pentru rolurile așteptate
echo "<h2>Verificare Roluri Așteptate</h2>";

$expected_roles = array(
    'clinica_doctor' => 4,
    'clinica_assistant' => 2,
    'clinica_receptionist' => 1,
    'clinica_manager' => 1
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Rol Așteptat</th><th>Număr Așteptat</th><th>Număr Găsit</th><th>Status</th>";
echo "</tr>";

foreach ($expected_roles as $role => $expected_count) {
    $found_count = $role_counts[$role];
    $status = '';
    $row_color = '';
    
    if ($found_count == $expected_count) {
        $status = '✅ CORECT';
        $row_color = 'background-color: #d4edda;';
    } elseif ($found_count > $expected_count) {
        $status = '⚠️ PREA MULȚI (' . ($found_count - $expected_count) . ' în plus)';
        $row_color = 'background-color: #fff3cd;';
    } else {
        $status = '❌ LIPSĂ (' . ($expected_count - $found_count) . ' lipsesc)';
        $row_color = 'background-color: #f8d7da;';
    }
    
    echo "<tr style='$row_color'>";
    echo "<td><strong>$role</strong></td>";
    echo "<td>$expected_count</td>";
    echo "<td>$found_count</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Verificare utilizatori cu roluri multiple
echo "<h2>Verificare Utilizatori cu Roluri Multiple</h2>";

$users_with_multiple_clinica_roles = array();
foreach ($users as $user) {
    $clinica_roles = array();
    foreach ($user->roles as $role) {
        if (strpos($role, 'clinica_') === 0) {
            $clinica_roles[] = $role;
        }
    }
    
    if (count($clinica_roles) > 1) {
        $users_with_multiple_clinica_roles[] = array(
            'user' => $user,
            'roles' => $clinica_roles
        );
    }
}

if (!empty($users_with_multiple_clinica_roles)) {
    echo "<p><strong>Utilizatori cu roluri Clinica multiple:</strong> " . count($users_with_multiple_clinica_roles) . "</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>Email</th><th>Roluri Clinica</th>";
    echo "</tr>";
    
    foreach ($users_with_multiple_clinica_roles as $item) {
        echo "<tr>";
        echo "<td>" . $item['user']->ID . "</td>";
        echo "<td>" . $item['user']->display_name . "</td>";
        echo "<td>" . $item['user']->user_email . "</td>";
        echo "<td>" . implode(', ', $item['roles']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Nu există utilizatori cu roluri Clinica multiple</p>";
}

// Verificare în tabela pacienți pentru staff
echo "<h2>Verificare Staff în Tabela Pacienți</h2>";

global $wpdb;
$staff_in_patients_table = $wpdb->get_results("
    SELECT p.user_id, p.cnp, p.email, p.first_name, p.last_name, u.display_name, u.user_email
    FROM {$wpdb->prefix}clinica_patients p
    INNER JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE p.user_id IN (
        SELECT user_id FROM {$wpdb->usermeta} 
        WHERE meta_key = 'wp_capabilities' 
        AND (meta_value LIKE '%clinica_doctor%' 
             OR meta_value LIKE '%clinica_assistant%' 
             OR meta_value LIKE '%clinica_receptionist%' 
             OR meta_value LIKE '%clinica_manager%' 
             OR meta_value LIKE '%clinica_administrator%')
    )
");

echo "<p><strong>Staff găsit în tabela pacienți:</strong> " . count($staff_in_patients_table) . "</p>";

if (!empty($staff_in_patients_table)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Nume</th><th>Email</th><th>CNP</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($staff_in_patients_table as $staff) {
        $user = get_userdata($staff->user_id);
        $clinica_role = Clinica_Roles::get_user_role($staff->user_id);
        
        echo "<tr style='background-color: #fff3cd;'>";
        echo "<td>" . $staff->user_id . "</td>";
        echo "<td>" . $staff->display_name . "</td>";
        echo "<td>" . $staff->user_email . "</td>";
        echo "<td>" . $staff->cnp . "</td>";
        echo "<td>PROBLEMĂ - Staff în tabela pacienți (rol: $clinica_role)</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Niciun staff în tabela pacienți</p>";
}

echo "<h2>Concluzie</h2>";
echo "<p><strong>Roluri găsite vs așteptate:</strong></p>";
echo "<ul>";
foreach ($expected_roles as $role => $expected_count) {
    $found_count = $role_counts[$role];
    $role_name = str_replace('clinica_', '', $role);
    $role_name = ucfirst($role_name);
    
    if ($found_count == $expected_count) {
        echo "<li style='color: green;'>✅ $role_name: $found_count/$expected_count - CORECT</li>";
    } else {
        echo "<li style='color: red;'>❌ $role_name: $found_count/$expected_count - PROBLEMĂ</li>";
    }
}
echo "</ul>";
?>
