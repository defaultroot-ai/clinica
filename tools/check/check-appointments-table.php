<?php
/**
 * Verifică dacă tabela wp_clinica_appointments există și are structura corectă
 */

// Încarcă WordPress - încearcă mai multe căi posibile
$possible_paths = array(
    dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php', // 5 nivele în sus
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php', // 4 nivele în sus
    dirname(dirname(dirname(__FILE__))) . '/wp-load.php', // 3 nivele în sus
    dirname(dirname(__FILE__)) . '/wp-load.php', // 2 nivele în sus
    dirname(__FILE__) . '/wp-load.php', // 1 nivel în sus
    'C:/xampp8.2.12/htdocs/plm/wp-load.php', // Cale absolută
    'C:/xampp/htdocs/plm/wp-load.php', // Cale alternativă XAMPP
    'C:/xampp/htdocs/wordpress/wp-load.php' // Cale alternativă WordPress
);

$wp_load_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $wp_load_path = $path;
        break;
    }
}

if ($wp_load_path) {
    require_once($wp_load_path);
    echo "<p style='color: green;'>✓ WordPress încărcat din: $wp_load_path</p>\n";
} else {
    echo "<h1>Eroare: Nu s-a găsit wp-load.php</h1>\n";
    echo "<p>Căile încercate:</p>\n";
    echo "<ul>\n";
    foreach ($possible_paths as $path) {
        echo "<li>$path</li>\n";
    }
    echo "</ul>\n";
    echo "<p>Verifică calea către WordPress și ajustează scriptul.</p>\n";
    die();
}

echo "<h1>Verificare Tabelă Programări</h1>\n";

global $wpdb;

// Verifică dacă tabela există
$table_name = $wpdb->prefix . 'clinica_appointments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

echo "<h2>1. Existența tabelei</h2>\n";
if ($table_exists) {
    echo "<p style='color: green;'>✓ Tabela <strong>$table_name</strong> există</p>\n";
} else {
    echo "<p style='color: red;'>✗ Tabela <strong>$table_name</strong> NU există</p>\n";
}

if ($table_exists) {
    // Verifică structura
    echo "<h2>2. Structura tabelei</h2>\n";
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Câmp</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col->Field}</td>";
        echo "<td>{$col->Type}</td>";
        echo "<td>{$col->Null}</td>";
        echo "<td>{$col->Key}</td>";
        echo "<td>{$col->Default}</td>";
        echo "<td>{$col->Extra}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Verifică numărul de înregistrări
    echo "<h2>3. Numărul de înregistrări</h2>\n";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Total înregistrări: <strong>$count</strong></p>\n";
    
    if ($count > 0) {
        echo "<h2>4. Primele 5 înregistrări</h2>\n";
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 5");
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        if (!empty($records)) {
            $first = true;
            foreach ($records as $record) {
                if ($first) {
                    echo "<tr>";
                    foreach ($record as $key => $value) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>\n";
                    $first = false;
                }
                
                echo "<tr>";
                foreach ($record as $key => $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>\n";
            }
        }
        echo "</table>\n";
    }
} else {
    echo "<h2>2. Creează tabela</h2>\n";
    echo "<p>Pentru a crea tabela, rulează:</p>\n";
    echo "<pre>Clinica_Database::create_tables();</pre>\n";
    
    // Încearcă să creezi tabela
    echo "<h3>Încercare de creare automată</h3>\n";
    if (class_exists('Clinica_Database')) {
        try {
            Clinica_Database::create_tables();
            echo "<p style='color: green;'>✓ Tabela a fost creată cu succes!</p>\n";
            
            // Verifică din nou
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            if ($table_exists) {
                echo "<p style='color: green;'>✓ Verificare confirmă că tabela există acum</p>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Eroare la crearea tabelei: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ Clasa Clinica_Database nu există</p>\n";
    }
}

// Verifică și alte tabele importante
echo "<h2>5. Alte tabele Clinica</h2>\n";
$tables = array(
    'clinica_services',
    'clinica_patients',
    'clinica_settings'
);

foreach ($tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
    $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
    
    $status = $exists ? "✓" : "✗";
    $color = $exists ? "green" : "red";
    
    echo "<p style='color: $color;'>$status Tabela <strong>$full_table</strong>: " . 
         ($exists ? "$count înregistrări" : "NU există") . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Script rulat la: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
