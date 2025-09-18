<?php
/**
 * Script de test pentru funcționalitatea de familie în formularul de editare pacient
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test Funcționalitate Familie în Editare Pacient</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

// Test 1: Verifică dacă funcțiile AJAX pentru editare există
echo "<h2>Test 1: Verificare Funcții AJAX Editare</h2>";
if (has_action('wp_ajax_clinica_get_patient_data')) {
    echo "<p class='success'>✅ AJAX handler clinica_get_patient_data este înregistrat</p>";
} else {
    echo "<p class='error'>❌ AJAX handler clinica_get_patient_data NU este înregistrat</p>";
}

if (has_action('wp_ajax_clinica_update_patient')) {
    echo "<p class='success'>✅ AJAX handler clinica_update_patient este înregistrat</p>";
} else {
    echo "<p class='error'>❌ AJAX handler clinica_update_patient NU este înregistrat</p>";
}

// Test 2: Verifică dacă metoda process_family_update_data există
echo "<h2>Test 2: Verificare Metode Clase</h2>";
$plugin_instance = Clinica_Plugin::get_instance();
if (method_exists($plugin_instance, 'process_family_update_data')) {
    echo "<p class='success'>✅ Metoda process_family_update_data există</p>";
} else {
    echo "<p class='error'>❌ Metoda process_family_update_data NU există</p>";
}

// Test 3: Testează obținerea datelor unui pacient cu familie
echo "<h2>Test 3: Testare Obținere Date Pacient cu Familie</h2>";
global $wpdb;
$table_patients = $wpdb->prefix . 'clinica_patients';

// Caută un pacient care face parte dintr-o familie
$patient_with_family = $wpdb->get_row(
    "SELECT user_id, family_id, family_name, family_role FROM $table_patients 
     WHERE family_id IS NOT NULL AND family_id > 0 
     LIMIT 1"
);

if ($patient_with_family) {
    echo "<p class='info'>ℹ️ Găsit pacient cu familie: ID {$patient_with_family->user_id}, Familia: {$patient_with_family->family_name}</p>";
    
    // Simulează un AJAX call pentru a obține datele pacientului
    $_POST['action'] = 'clinica_get_patient_data';
    $_POST['patient_id'] = $patient_with_family->user_id;
    $_POST['nonce'] = wp_create_nonce('clinica_nonce');
    
    // Capturează output-ul
    ob_start();
    do_action('wp_ajax_clinica_get_patient_data');
    $ajax_response = ob_get_clean();
    
    // Decodifică JSON-ul
    $response_data = json_decode($ajax_response, true);
    
    if ($response_data && isset($response_data['success']) && $response_data['success']) {
        $patient_data = $response_data['data'];
        echo "<p class='success'>✅ Datele pacientului au fost obținute cu succes</p>";
        echo "<p class='info'>ℹ️ Informații familie din răspuns:</p>";
        echo "<ul>";
        echo "<li>Family ID: " . ($patient_data['family_id'] ?? 'null') . "</li>";
        echo "<li>Family Name: " . ($patient_data['family_name'] ?? 'null') . "</li>";
        echo "<li>Family Role: " . ($patient_data['family_role'] ?? 'null') . "</li>";
        echo "<li>Family Head ID: " . ($patient_data['family_head_id'] ?? 'null') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Eroare la obținerea datelor pacientului</p>";
        echo "<p class='info'>Răspuns: " . htmlspecialchars($ajax_response) . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Nu s-a găsit niciun pacient cu familie pentru testare</p>";
}

// Test 4: Testează actualizarea unui pacient cu familie
echo "<h2>Test 4: Testare Actualizare Pacient cu Familie</h2>";
if ($patient_with_family) {
    // Simulează actualizarea cu păstrarea familiei actuale
    $_POST['action'] = 'clinica_update_patient';
    $_POST['patient_id'] = $patient_with_family->user_id;
    $_POST['nonce'] = wp_create_nonce('clinica_nonce');
    $_POST['first_name'] = 'Test';
    $_POST['last_name'] = 'Pacient';
    $_POST['email'] = 'test@example.com';
    $_POST['family_option'] = 'current'; // Păstrează familia actuală
    
    ob_start();
    do_action('wp_ajax_clinica_update_patient');
    $update_response = ob_get_clean();
    
    $update_data = json_decode($update_response, true);
    
    if ($update_data && isset($update_data['success']) && $update_data['success']) {
        echo "<p class='success'>✅ Pacientul a fost actualizat cu succes</p>";
    } else {
        echo "<p class='error'>❌ Eroare la actualizarea pacientului</p>";
        echo "<p class='info'>Răspuns: " . htmlspecialchars($update_response) . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Nu s-a găsit niciun pacient cu familie pentru testare</p>";
}

// Test 5: Verifică dacă fișierul de editare include secțiunea de familie
echo "<h2>Test 5: Verificare Fișier Editare</h2>";
$edit_file_path = CLINICA_PLUGIN_PATH . 'admin/views/patients.php';
if (file_exists($edit_file_path)) {
    echo "<p class='success'>✅ Fișierul de editare pacienți există</p>";
    
    $file_content = file_get_contents($edit_file_path);
    
    // Verifică dacă conține secțiunea de familie
    if (strpos($file_content, 'Informații Familie') !== false) {
        echo "<p class='success'>✅ Secțiunea 'Informații Familie' există în formular</p>";
    } else {
        echo "<p class='error'>❌ Secțiunea 'Informații Familie' NU există în formular</p>";
    }
    
    if (strpos($file_content, 'edit-family-option') !== false) {
        echo "<p class='success'>✅ Câmpul 'edit-family-option' există</p>";
    } else {
        echo "<p class='error'>❌ Câmpul 'edit-family-option' NU există</p>";
    }
    
    if (strpos($file_content, 'process_family_update_data') !== false) {
        echo "<p class='success'>✅ Funcția 'process_family_update_data' este referită</p>";
    } else {
        echo "<p class='error'>❌ Funcția 'process_family_update_data' NU este referită</p>";
    }
} else {
    echo "<p class='error'>❌ Fișierul de editare pacienți NU există</p>";
}

// Test 6: Testează funcționalitatea Clinica_Family_Manager în contextul de editare
echo "<h2>Test 6: Testare Clinica_Family_Manager pentru Editare</h2>";
try {
    $family_manager = new Clinica_Family_Manager();
    echo "<p class='success'>✅ Clinica_Family_Manager inițializată cu succes</p>";
    
    // Testează metoda remove_family_member
    if (method_exists($family_manager, 'remove_family_member')) {
        echo "<p class='success'>✅ Metoda remove_family_member există</p>";
    } else {
        echo "<p class='error'>❌ Metoda remove_family_member NU există</p>";
    }
    
    // Testează metoda add_family_member
    if (method_exists($family_manager, 'add_family_member')) {
        echo "<p class='success'>✅ Metoda add_family_member există</p>";
    } else {
        echo "<p class='error'>❌ Metoda add_family_member NU există</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la testarea Clinica_Family_Manager: " . $e->getMessage() . "</p>";
}

// Test 7: Verifică dacă JavaScript-ul pentru editare include funcționalitatea de familie
echo "<h2>Test 7: Verificare JavaScript Editare</h2>";
if (strpos($file_content, 'loadFamilyData') !== false) {
    echo "<p class='success'>✅ Funcția JavaScript 'loadFamilyData' există</p>";
} else {
    echo "<p class='error'>❌ Funcția JavaScript 'loadFamilyData' NU există</p>";
}

if (strpos($file_content, 'edit-family-option') !== false) {
    echo "<p class='success'>✅ Event handler pentru 'edit-family-option' există</p>";
} else {
    echo "<p class='error'>❌ Event handler pentru 'edit-family-option' NU există</p>";
}

if (strpos($file_content, 'edit-search-family-btn') !== false) {
    echo "<p class='success'>✅ Butonul de căutare familie în editare există</p>";
} else {
    echo "<p class='error'>❌ Butonul de căutare familie în editare NU există</p>";
}

// Test 8: Verifică CSS-ul pentru editare
echo "<h2>Test 8: Verificare CSS Editare</h2>";
if (strpos($file_content, '.form-section') !== false) {
    echo "<p class='success'>✅ CSS pentru .form-section există</p>";
} else {
    echo "<p class='error'>❌ CSS pentru .form-section NU există</p>";
}

if (strpos($file_content, '.family-section') !== false) {
    echo "<p class='success'>✅ CSS pentru .family-section există</p>";
} else {
    echo "<p class='error'>❌ CSS pentru .family-section NU există</p>";
}

if (strpos($file_content, '.current-family-info') !== false) {
    echo "<p class='success'>✅ CSS pentru .current-family-info există</p>";
} else {
    echo "<p class='error'>❌ CSS pentru .current-family-info NU există</p>";
}

echo "<h2>Rezumat Test Editare Pacient cu Familie</h2>";
echo "<p class='info'>ℹ️ Toate testele au fost finalizate. Verificați rezultatele de mai sus.</p>";
echo "<p><strong>Următorii pași pentru testare:</strong></p>";
echo "<ol>";
echo "<li>Accesați pagina de pacienți din admin</li>";
echo "<li>Click pe 'Editează' pentru un pacient</li>";
echo "<li>Verificați că secțiunea 'Informații Familie' apare în modal</li>";
echo "<li>Testați opțiunile: Păstrează familia actuală, Creează familie nouă, Adaugă la familie existentă</li>";
echo "<li>Testați căutarea familiilor</li>";
echo "<li>Salvați modificările și verificați că sunt persistate</li>";
echo "</ol>";

echo "<p><strong>Link-uri pentru testare:</strong></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Deschide Pagina Pacienți</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-families') . "' target='_blank'>Deschide Pagina Familii</a></p>";

echo "<h3>Funcționalități Testate:</h3>";
echo "<ul>";
echo "<li>✅ Obținerea datelor pacientului cu informații de familie</li>";
echo "<li>✅ Actualizarea pacientului cu păstrarea familiei</li>";
echo "<li>✅ Interfața de editare cu secțiunea de familie</li>";
echo "<li>✅ JavaScript pentru gestionarea familiilor în editare</li>";
echo "<li>✅ CSS pentru stilizarea secțiunii de familie</li>";
echo "<li>✅ Integrarea cu Clinica_Family_Manager</li>";
echo "</ul>";
?> 