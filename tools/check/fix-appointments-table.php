<?php
/**
 * Repară structura tabelei wp_clinica_appointments
 */

// Încarcă WordPress
$wp_load_path = 'C:/xampp8.2.12/htdocs/plm/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('Nu s-a găsit wp-load.php');
}

echo "<h1>Reparare Tabelă Programări</h1>\n";

global $wpdb;

$table_name = $wpdb->prefix . 'clinica_appointments';

echo "<h2>1. Verificare structură curentă</h2>\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array();
foreach ($columns as $col) {
    $column_names[] = $col->Field;
}

echo "<p>Coloane existente: " . implode(', ', $column_names) . "</p>\n";

// Verifică dacă lipsește service_id
if (!in_array('service_id', $column_names)) {
    echo "<h2>2. Adăugare coloană service_id</h2>\n";
    
    $sql = "ALTER TABLE $table_name ADD COLUMN service_id INT DEFAULT NULL AFTER type";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "<p style='color: green;'>✓ Coloana service_id a fost adăugată cu succes</p>\n";
        
        // Adaugă index pentru service_id
        $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_service_id (service_id)");
        echo "<p style='color: green;'>✓ Index pentru service_id a fost adăugat</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Eroare la adăugarea coloanei: " . $wpdb->last_error . "</p>\n";
    }
} else {
    echo "<p style='color: green;'>✓ Coloana service_id există deja</p>\n";
}

// Verifică dacă câmpul type poate fi NULL (pentru a permite service_id să fie folosit)
echo "<h2>3. Verificare câmp type</h2>\n";
$type_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'type'");
if ($type_column && $type_column->Null === 'NO') {
    echo "<p>Modificare câmp type să permită NULL...</p>\n";
    $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN type ENUM('consultation','examination','procedure','follow_up') NULL");
    echo "<p style='color: green;'>✓ Câmpul type permite acum NULL</p>\n";
} else {
    echo "<p style='color: green;'>✓ Câmpul type permite deja NULL</p>\n";
}

// Actualizează programările existente să aibă service_id și type corect
echo "<h2>4. Actualizare programări existente</h2>\n";

// Obține serviciile disponibile
$services_table = $wpdb->prefix . 'clinica_services';
$services = $wpdb->get_results("SELECT id, name, duration FROM $services_table WHERE active = 1");

if (!empty($services)) {
    echo "<p>Servicii disponibile:</p>\n";
    echo "<ul>\n";
    foreach ($services as $service) {
        echo "<li>ID: {$service->id}, Nume: {$service->name}, Durată: {$service->duration} min</li>\n";
    }
    echo "</ul>\n";
    
    // Actualizează programările existente
    $appointments = $wpdb->get_results("SELECT id, duration, type FROM $table_name WHERE service_id IS NULL");
    
    if (!empty($appointments)) {
        echo "<p>Actualizare programări fără service_id...</p>\n";
        
        foreach ($appointments as $appointment) {
            // Găsește serviciul după durată
            $service_id = null;
            foreach ($services as $service) {
                if ($service->duration == $appointment->duration) {
                    $service_id = $service->id;
                    break;
                }
            }
            
            if ($service_id) {
                $updated = $wpdb->update(
                    $table_name,
                    array(
                        'service_id' => $service_id,
                        'type' => null // Lasă type NULL, folosește service_id
                    ),
                    array('id' => $appointment->id)
                );
                
                if ($updated !== false) {
                    echo "<p style='color: green;'>✓ Programarea ID {$appointment->id} actualizată cu service_id: {$service_id}</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Eroare la actualizarea programării ID {$appointment->id}</p>\n";
                }
            } else {
                echo "<p style='color: orange;'>⚠ Nu s-a găsit serviciu pentru durata {$appointment->duration} min (programarea ID {$appointment->id})</p>\n";
            }
        }
    } else {
        echo "<p style='color: green;'>✓ Toate programările au deja service_id</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ Nu există servicii în tabela clinica_services</p>\n";
}

// Verifică structura finală
echo "<h2>5. Structura finală</h2>\n";
$final_columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Câmp</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";

foreach ($final_columns as $col) {
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

// Verifică programările actualizate
echo "<h2>6. Programări actualizate</h2>\n";
$updated_appointments = $wpdb->get_results("
    SELECT a.*, s.name as service_name 
    FROM $table_name a 
    LEFT JOIN {$wpdb->prefix}clinica_services s ON a.service_id = s.id 
    ORDER BY a.created_at DESC
");

if (!empty($updated_appointments)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    $first = true;
    foreach ($updated_appointments as $appointment) {
        if ($first) {
            echo "<tr>";
            foreach ($appointment as $key => $value) {
                echo "<th>$key</th>";
            }
            echo "</tr>\n";
            $first = false;
        }
        
        echo "<tr>";
        foreach ($appointment as $key => $value) {
            $display_value = $value;
            if ($key === 'service_id' && empty($value)) {
                $display_value = '<em>NULL</em>';
            }
            echo "<td>" . htmlspecialchars($display_value) . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

echo "<hr>\n";
echo "<p><em>Script rulat la: " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "<p><strong>Următorul pas:</strong> Testează din nou crearea unei programări din dashboard-ul pacientului!</p>\n";
?>
