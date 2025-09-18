<?php
/**
 * Script de test pentru funcționalitatea tab-ului de familie
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test Funcționalitate Tab Familie</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Test 1: Verifică dacă clasa Clinica_Family_Manager există
echo "<h2>Test 1: Verificare Clase</h2>";
if (class_exists('Clinica_Family_Manager')) {
    echo "<p class='success'>✅ Clasa Clinica_Family_Manager există</p>";
} else {
    echo "<p class='error'>❌ Clasa Clinica_Family_Manager NU există</p>";
}

if (class_exists('Clinica_Patient_Creation_Form')) {
    echo "<p class='success'>✅ Clasa Clinica_Patient_Creation_Form există</p>";
} else {
    echo "<p class='error'>❌ Clasa Clinica_Patient_Creation_Form NU există</p>";
}

// Test 2: Verifică dacă câmpurile pentru familie există în baza de date
echo "<h2>Test 2: Verificare Câmpuri Baza de Date</h2>";
global $wpdb;
$table_patients = $wpdb->prefix . 'clinica_patients';

$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_patients");
$column_names = array_column($columns, 'Field');

$family_columns = ['family_id', 'family_role', 'family_head_id', 'family_name'];

foreach ($family_columns as $column) {
    if (in_array($column, $column_names)) {
        echo "<p class='success'>✅ Câmpul $column există</p>";
    } else {
        echo "<p class='error'>❌ Câmpul $column NU există</p>";
    }
}

// Test 3: Verifică dacă indexurile pentru familie există
echo "<h2>Test 3: Verificare Indexuri</h2>";
$indexes = $wpdb->get_results("SHOW INDEX FROM $table_patients");
$index_names = array_column($indexes, 'Key_name');

$family_indexes = ['idx_family_id', 'idx_family_head_id', 'idx_family_name'];

foreach ($family_indexes as $index) {
    if (in_array($index, $index_names)) {
        echo "<p class='success'>✅ Indexul $index există</p>";
    } else {
        echo "<p class='error'>❌ Indexul $index NU există</p>";
    }
}

// Test 4: Testează funcționalitatea Clinica_Family_Manager
echo "<h2>Test 4: Testare Clinica_Family_Manager</h2>";
try {
    $family_manager = new Clinica_Family_Manager();
    echo "<p class='success'>✅ Clinica_Family_Manager inițializată cu succes</p>";
    
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
    
    echo "<p class='info'>ℹ️ Etichetele rolurilor funcționează:</p>";
    foreach ($role_labels as $role => $label) {
        echo "<p class='success'>✅ $role → $label</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la testarea Clinica_Family_Manager: " . $e->getMessage() . "</p>";
}

// Test 5: Testează formularul de creare pacienți
echo "<h2>Test 5: Testare Formular Creare Pacienți</h2>";
try {
    $patient_form = new Clinica_Patient_Creation_Form();
    echo "<p class='success'>✅ Clinica_Patient_Creation_Form inițializată cu succes</p>";
    
    // Testează dacă metoda render_form există
    if (method_exists($patient_form, 'render_form')) {
        echo "<p class='success'>✅ Metoda render_form există</p>";
    } else {
        echo "<p class='error'>❌ Metoda render_form NU există</p>";
    }
    
    // Testează dacă metoda process_family_data există
    if (method_exists($patient_form, 'process_family_data')) {
        echo "<p class='success'>✅ Metoda process_family_data există</p>";
    } else {
        echo "<p class='error'>❌ Metoda process_family_data NU există</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la testarea Clinica_Patient_Creation_Form: " . $e->getMessage() . "</p>";
}

// Test 6: Verifică dacă AJAX handlers sunt înregistrați
echo "<h2>Test 6: Verificare AJAX Handlers</h2>";
$ajax_actions = array(
    'clinica_create_family',
    'clinica_add_family_member',
    'clinica_get_family_members',
    'clinica_remove_family_member',
    'clinica_search_families'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "<p class='success'>✅ AJAX handler pentru $action este înregistrat</p>";
    } else {
        echo "<p class='error'>❌ AJAX handler pentru $action NU este înregistrat</p>";
    }
}

// Test 7: Verifică dacă meniul admin include pagina de familii
echo "<h2>Test 7: Verificare Meniu Admin</h2>";
$admin_menu_items = array(
    'clinica-families' => 'Familii'
);

foreach ($admin_menu_items as $slug => $title) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo "<p class='success'>✅ Pagina $title ($slug) există</p>";
    } else {
        echo "<p class='info'>ℹ️ Pagina $title ($slug) nu există (normal pentru meniuri admin)</p>";
    }
}

// Test 8: Testează nonce-urile
echo "<h2>Test 8: Verificare Nonce-uri</h2>";
$nonces = array(
    'clinica_family_nonce' => wp_create_nonce('clinica_family_nonce'),
    'clinica_create_patient' => wp_create_nonce('clinica_create_patient'),
    'clinica_validate_cnp' => wp_create_nonce('clinica_validate_cnp')
);

foreach ($nonces as $nonce_name => $nonce_value) {
    if (!empty($nonce_value)) {
        echo "<p class='success'>✅ Nonce pentru $nonce_name generat cu succes</p>";
    } else {
        echo "<p class='error'>❌ Eroare la generarea nonce pentru $nonce_name</p>";
    }
}

// Test 9: Verifică dacă fișierele necesare există
echo "<h2>Test 9: Verificare Fișiere</h2>";
$required_files = array(
    'includes/class-clinica-family-manager.php',
    'admin/views/families.php',
    'includes/class-clinica-patient-creation-form.php'
);

foreach ($required_files as $file) {
    $file_path = CLINICA_PLUGIN_PATH . $file;
    if (file_exists($file_path)) {
        echo "<p class='success'>✅ Fișierul $file există</p>";
    } else {
        echo "<p class='error'>❌ Fișierul $file NU există</p>";
    }
}

echo "<h2>Rezumat Test</h2>";
echo "<p class='info'>ℹ️ Toate testele au fost finalizate. Verificați rezultatele de mai sus.</p>";
echo "<p><strong>Următorii pași:</strong></p>";
echo "<ol>";
echo "<li>Dacă toate testele sunt verzi ✅, tab-ul de familie ar trebui să funcționeze</li>";
echo "<li>Accesați formularul de creare pacienți și testați tab-ul 'Familie'</li>";
echo "<li>Verificați că puteți crea familii noi și adăuga pacienți în familii existente</li>";
echo "<li>Testați căutarea familiilor</li>";
echo "</ol>";

echo "<p><strong>Pentru a testa formularul:</strong></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-create-patient') . "' target='_blank'>Deschide Formularul de Creare Pacienți</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-families') . "' target='_blank'>Deschide Pagina de Gestionare Familii</a></p>";
?> 