<?php
/**
 * Script pentru actualizarea bazei de date cu câmpurile pentru familii
 * Rulează acest script o singură dată pentru a adăuga câmpurile necesare
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

global $wpdb;

echo "<h1>Actualizare Baza de Date - Câmpuri Familie</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

$table_patients = $wpdb->prefix . 'clinica_patients';

// Verifică dacă tabelul există
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_patients'") == $table_patients;

if (!$table_exists) {
    echo "<p class='error'>❌ Tabelul $table_patients nu există!</p>";
    echo "<p>Rulați mai întâi scriptul de creare a tabelelor.</p>";
    exit;
}

echo "<p class='info'>ℹ️ Tabelul $table_patients există. Verific câmpurile pentru familii...</p>";

// Verifică câmpurile existente
$existing_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_patients");
$existing_column_names = array_column($existing_columns, 'Field');

$family_columns = array(
    'family_id' => "INT DEFAULT NULL",
    'family_role' => "ENUM('head', 'spouse', 'child', 'parent', 'sibling') DEFAULT NULL",
    'family_head_id' => "INT DEFAULT NULL",
    'family_name' => "VARCHAR(100) DEFAULT NULL"
);

$columns_to_add = array();

foreach ($family_columns as $column_name => $column_definition) {
    if (!in_array($column_name, $existing_column_names)) {
        $columns_to_add[$column_name] = $column_definition;
    } else {
        echo "<p class='info'>ℹ️ Câmpul $column_name există deja.</p>";
    }
}

if (empty($columns_to_add)) {
    echo "<p class='success'>✅ Toate câmpurile pentru familii există deja!</p>";
    echo "<p>Nu este nevoie de actualizări suplimentare.</p>";
} else {
    echo "<p class='info'>🔧 Adaug câmpurile lipsă...</p>";
    
    foreach ($columns_to_add as $column_name => $column_definition) {
        $sql = "ALTER TABLE $table_patients ADD COLUMN $column_name $column_definition";
        
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p class='success'>✅ Câmpul $column_name a fost adăugat cu succes.</p>";
        } else {
            echo "<p class='error'>❌ Eroare la adăugarea câmpului $column_name: " . $wpdb->last_error . "</p>";
        }
    }
}

// Adaugă indexurile pentru familii
echo "<p class='info'>🔧 Verific indexurile pentru familii...</p>";

$existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_patients");
$existing_index_names = array_column($existing_indexes, 'Key_name');

$family_indexes = array(
    'idx_family_id' => 'family_id',
    'idx_family_head_id' => 'family_head_id',
    'idx_family_name' => 'family_name'
);

foreach ($family_indexes as $index_name => $column_name) {
    if (!in_array($index_name, $existing_index_names)) {
        $sql = "ALTER TABLE $table_patients ADD INDEX $index_name ($column_name)";
        
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p class='success'>✅ Indexul $index_name a fost adăugat cu succes.</p>";
        } else {
            echo "<p class='error'>❌ Eroare la adăugarea indexului $index_name: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Indexul $index_name există deja.</p>";
    }
}

// Verifică structura finală
echo "<h2>Structura Finală a Tabelului</h2>";
$final_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_patients");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Câmp</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($final_columns as $column) {
    echo "<tr>";
    echo "<td>" . esc_html($column->Field) . "</td>";
    echo "<td>" . esc_html($column->Type) . "</td>";
    echo "<td>" . esc_html($column->Null) . "</td>";
    echo "<td>" . esc_html($column->Key) . "</td>";
    echo "<td>" . esc_html($column->Default) . "</td>";
    echo "<td>" . esc_html($column->Extra) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Testează funcționalitatea
echo "<h2>Test Funcționalitate Familie</h2>";

// Verifică dacă clasa Clinica_Family_Manager există
if (class_exists('Clinica_Family_Manager')) {
    echo "<p class='success'>✅ Clasa Clinica_Family_Manager este disponibilă.</p>";
    
    try {
        $family_manager = new Clinica_Family_Manager();
        echo "<p class='success'>✅ Clinica_Family_Manager a fost inițializată cu succes.</p>";
        
        // Testează metoda get_all_families
        $families = $family_manager->get_all_families();
        echo "<p class='info'>ℹ️ Numărul de familii existente: " . count($families) . "</p>";
        
        // Testează metoda get_family_role_label
        $role_labels = array(
            'head' => $family_manager->get_family_role_label('head'),
            'spouse' => $family_manager->get_family_role_label('spouse'),
            'child' => $family_manager->get_family_role_label('child'),
            'parent' => $family_manager->get_family_role_label('parent'),
            'sibling' => $family_manager->get_family_role_label('sibling')
        );
        
        echo "<p class='info'>ℹ️ Etichetele rolurilor:</p>";
        echo "<ul>";
        foreach ($role_labels as $role => $label) {
            echo "<li><strong>$role:</strong> $label</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Eroare la testarea Clinica_Family_Manager: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Clasa Clinica_Family_Manager nu este disponibilă!</p>";
    echo "<p>Verificați că fișierul class-clinica-family-manager.php este încărcat corect.</p>";
}

echo "<h2>Rezumat</h2>";
echo "<p class='success'>✅ Actualizarea bazei de date pentru familii a fost finalizată!</p>";
echo "<p>Acum puteți:</p>";
echo "<ul>";
echo "<li>Accesa pagina 'Familii' din meniul admin</li>";
echo "<li>Crea familii noi</li>";
echo "<li>Adăuga pacienți în familii existente</li>";
echo "<li>Gestiona rolurile în familie</li>";
echo "</ul>";

echo "<p><strong>Notă:</strong> Asigurați-vă că ați actualizat și fișierul clinica.php pentru a include clasa Clinica_Family_Manager.</p>";
?> 