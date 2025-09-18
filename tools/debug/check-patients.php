<?php
/**
 * Script pentru verificarea pacienților din baza de date
 */

// Include WordPress - CALE CORECTĂ
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificare Pacienți - Clinica</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        h1, h2 { color: #333; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>🔍 Verificare Pacienți</h1>

    <?php
    // 1. Verifică tabelele
    echo "<div class='section'>";
    echo "<h2>1. Verificare tabele</h2>";
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    $table_users = $wpdb->users;
    
    $patients_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_patients'");
    $users_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_users'");
    
    echo "<p class='" . ($patients_exists ? 'success' : 'error') . "'>";
    echo ($patients_exists ? "✅" : "❌") . " Tabela clinica_patients: " . ($patients_exists ? "EXISTĂ" : "NU EXISTĂ");
    echo "</p>";
    
    echo "<p class='" . ($users_exists ? 'success' : 'error') . "'>";
    echo ($users_exists ? "✅" : "❌") . " Tabela wp_users: " . ($users_exists ? "EXISTĂ" : "NU EXISTĂ");
    echo "</p>";
    echo "</div>";
    
    // 2. Verifică pacienții în clinica_patients
    echo "<div class='section'>";
    echo "<h2>2. Pacienți în clinica_patients</h2>";
    
    if ($patients_exists) {
        $patients_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
        echo "<p class='info'>Total pacienți în clinica_patients: $patients_count</p>";
        
        if ($patients_count > 0) {
            $patients = $wpdb->get_results("SELECT * FROM $table_patients LIMIT 10");
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>CNP</th><th>Phone</th><th>Created</th></tr>";
            foreach ($patients as $patient) {
                echo "<tr>";
                echo "<td>{$patient->id}</td>";
                echo "<td>{$patient->user_id}</td>";
                echo "<td>{$patient->cnp}</td>";
                echo "<td>{$patient->phone_primary}</td>";
                echo "<td>{$patient->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ Nu există pacienți în tabela clinica_patients!</p>";
        }
    }
    echo "</div>";
    
    // 3. Verifică utilizatorii din wp_users
    echo "<div class='section'>";
    echo "<h2>3. Utilizatori în wp_users</h2>";
    
    $users_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_users");
    echo "<p class='info'>Total utilizatori în wp_users: $users_count</p>";
    
    $users = $wpdb->get_results("SELECT ID, user_login, display_name, user_email FROM $table_users LIMIT 10");
    echo "<table>";
    echo "<tr><th>ID</th><th>Login</th><th>Display Name</th><th>Email</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user->ID}</td>";
        echo "<td>{$user->user_login}</td>";
        echo "<td>{$user->display_name}</td>";
        echo "<td>{$user->user_email}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 4. Verifică query-ul din appointments.php
    echo "<div class='section'>";
    echo "<h2>4. Test query din appointments.php</h2>";
    
    $test_query = "
        SELECT u.ID, 
               COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), u.display_name) as display_name
        FROM {$wpdb->users} u 
        INNER JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id 
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        ORDER BY display_name
    ";
    
    $test_patients = $wpdb->get_results($test_query);
    echo "<p class='info'>Rezultate din query-ul appointments.php: " . count($test_patients) . "</p>";
    
    if (count($test_patients) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Display Name</th></tr>";
        foreach ($test_patients as $patient) {
            echo "<tr>";
            echo "<td>{$patient->ID}</td>";
            echo "<td>{$patient->display_name}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Query-ul nu returnează rezultate!</p>";
    }
    echo "</div>";
    
    // 5. Test query alternativ (fără INNER JOIN)
    echo "<div class='section'>";
    echo "<h2>5. Test query alternativ (fără INNER JOIN)</h2>";
    
    $test_query2 = "
        SELECT u.ID, 
               COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), u.display_name) as display_name
        FROM {$wpdb->users} u 
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id 
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        WHERE u.ID > 1
        ORDER BY display_name
    ";
    
    $test_patients2 = $wpdb->get_results($test_query2);
    echo "<p class='info'>Rezultate din query alternativ: " . count($test_patients2) . "</p>";
    
    if (count($test_patients2) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Display Name</th><th>Has Patient Record</th></tr>";
        foreach ($test_patients2 as $patient) {
            $has_patient = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d", $patient->ID));
            echo "<tr>";
            echo "<td>{$patient->ID}</td>";
            echo "<td>{$patient->display_name}</td>";
            echo "<td>" . ($has_patient ? "✅ DA" : "❌ NU") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    ?>
</body>
</html> 