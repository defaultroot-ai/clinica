<?php
/**
 * Quick Check Pacienți
 * 
 * Script simplu pentru a verifica rapid pacienții din baza de date
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in WordPress context
if (!function_exists('wp_enqueue_script')) {
    die('WordPress nu este încărcat corect.');
}

global $wpdb;

echo "<h1>🔍 Quick Check Pacienți</h1>";

// 1. Verifică tabelele
echo "<h2>1. Tabele clinica</h2>";
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}clinica_%'");
if ($tables) {
    echo "✅ Tabele clinica găsite:<br>";
    foreach ($tables as $table) {
        $table_name = array_values((array)$table)[0];
        echo "- $table_name<br>";
    }
} else {
    echo "❌ Nu există tabele clinica!<br>";
}

// 2. Verifică tabelul pacienți
echo "<h2>2. Tabelul pacienți</h2>";
$patients_table = $wpdb->prefix . 'clinica_patients';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$patients_table'");

if ($table_exists) {
    echo "✅ Tabelul $patients_table există<br>";
    
    // 3. Verifică numărul de pacienți
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $patients_table");
    echo "📊 Total pacienți: $total_patients<br>";
    
    if ($total_patients > 0) {
        echo "✅ Există pacienți în tabel<br>";
        
        // 4. Afișează primii 5 pacienți
        echo "<h2>3. Primii 5 pacienți</h2>";
        $patients = $wpdb->get_results("SELECT * FROM $patients_table ORDER BY created_at DESC LIMIT 5");
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>CNP</th><th>Phone Primary</th><th>Birth Date</th><th>Created At</th></tr>";
        foreach ($patients as $patient) {
            echo "<tr>";
            echo "<td>" . $patient->id . "</td>";
            echo "<td>" . $patient->user_id . "</td>";
            echo "<td>" . $patient->cnp . "</td>";
            echo "<td>" . $patient->phone_primary . "</td>";
            echo "<td>" . $patient->birth_date . "</td>";
            echo "<td>" . $patient->created_at . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 5. Testează query-ul original (care nu funcționa)
        echo "<h2>4. Test Query Original</h2>";
        $original_query = "SELECT 
            p.id,
            p.first_name,
            p.last_name,
            p.cnp,
            p.email,
            p.phone,
            p.created_at
        FROM $patients_table p
        ORDER BY p.created_at DESC
        LIMIT 3";
        
        $original_results = $wpdb->get_results($original_query);
        echo "Query original returnează: " . count($original_results) . " rezultate<br>";
        
        if ($original_results) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>CNP</th><th>Email</th><th>Phone</th></tr>";
            foreach ($original_results as $patient) {
                echo "<tr>";
                echo "<td>" . $patient->id . "</td>";
                echo "<td>" . ($patient->first_name ?: 'NULL') . "</td>";
                echo "<td>" . ($patient->last_name ?: 'NULL') . "</td>";
                echo "<td>" . $patient->cnp . "</td>";
                echo "<td>" . ($patient->email ?: 'NULL') . "</td>";
                echo "<td>" . ($patient->phone ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // 6. Testează query-ul fix (care ar trebui să funcționeze)
        echo "<h2>5. Test Query Fix</h2>";
        $fixed_query = "SELECT p.*, u.user_email, u.display_name,
                       um1.meta_value as first_name, um2.meta_value as last_name
                       FROM $patients_table p 
                       LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                       LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                       LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                       ORDER BY p.created_at DESC
                       LIMIT 3";
        
        $fixed_results = $wpdb->get_results($fixed_query);
        echo "Query fix returnează: " . count($fixed_results) . " rezultate<br>";
        
        if ($fixed_results) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Display Name</th><th>CNP</th><th>Email</th><th>Phone</th></tr>";
            foreach ($fixed_results as $patient) {
                echo "<tr>";
                echo "<td>" . $patient->id . "</td>";
                echo "<td>" . ($patient->first_name ?: 'NULL') . "</td>";
                echo "<td>" . ($patient->last_name ?: 'NULL') . "</td>";
                echo "<td>" . ($patient->display_name ?: 'NULL') . "</td>";
                echo "<td>" . $patient->cnp . "</td>";
                echo "<td>" . ($patient->user_email ?: 'NULL') . "</td>";
                echo "<td>" . ($patient->phone_primary ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // 7. Verifică utilizatorii WordPress
        echo "<h2>6. Utilizatori WordPress</h2>";
        $users = $wpdb->get_results("SELECT ID, user_login, user_email, display_name FROM {$wpdb->users} ORDER BY ID DESC LIMIT 5");
        echo "Ultimii 5 utilizatori WordPress:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Login</th><th>Email</th><th>Display Name</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user->ID . "</td>";
            echo "<td>" . $user->user_login . "</td>";
            echo "<td>" . $user->user_email . "</td>";
            echo "<td>" . $user->display_name . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 8. Verifică user meta pentru primul pacient
        if ($fixed_results) {
            $first_patient = $fixed_results[0];
            echo "<h2>7. User Meta pentru primul pacient (ID: {$first_patient->user_id})</h2>";
            
            $user_meta = $wpdb->get_results($wpdb->prepare("
                SELECT meta_key, meta_value 
                FROM {$wpdb->usermeta} 
                WHERE user_id = %d 
                AND meta_key IN ('first_name', 'last_name', 'nickname')
            ", $first_patient->user_id));
            
            if ($user_meta) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Meta Key</th><th>Meta Value</th></tr>";
                foreach ($user_meta as $meta) {
                    echo "<tr>";
                    echo "<td>" . $meta->meta_key . "</td>";
                    echo "<td>" . $meta->meta_value . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "❌ Nu există user meta pentru acest utilizator<br>";
            }
        }
        
    } else {
        echo "❌ Tabelul este gol - nu există pacienți<br>";
    }
} else {
    echo "❌ Tabelul $patients_table NU există!<br>";
}

// 9. Testează metoda din plugin
echo "<h2>8. Test Metoda Plugin</h2>";
if (class_exists('Clinica_Plugin')) {
    $plugin = Clinica_Plugin::get_instance();
    if (method_exists($plugin, 'get_recent_patients_html')) {
        echo "✅ Metoda get_recent_patients_html() există<br>";
        
        try {
            $html = $plugin->get_recent_patients_html();
            echo "✅ Metoda returnează HTML<br>";
            echo "<h3>HTML generat:</h3>";
            echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
            echo $html;
            echo "</div>";
        } catch (Exception $e) {
            echo "❌ Eroare la executarea metodei: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Metoda get_recent_patients_html() NU există<br>";
    }
} else {
    echo "❌ Clasa Clinica_Plugin NU există<br>";
}

echo "<h2>9. Informații Sistem</h2>";
echo "Prefix WordPress: " . $wpdb->prefix . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "Host: " . DB_HOST . "<br>";
echo "Utilizator: " . DB_USER . "<br>";
echo "Timp curent: " . current_time('mysql') . "<br>";
?> 