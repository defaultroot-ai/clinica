<?php
/**
 * Test simplu pentru autosuggest - nu necesită autentificare
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Autosuggest - Status</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

// Test 1: Verifică dacă plugin-ul este activ
echo "<h2>Test 1: Status Plugin</h2>";
if (class_exists('Clinica_Plugin')) {
    echo "<p class='success'>✅ Plugin-ul Clinica este activ</p>";
} else {
    echo "<p class='error'>❌ Plugin-ul Clinica NU este activ</p>";
    exit;
}

// Test 2: Verifică dacă tabelele există
echo "<h2>Test 2: Verificare Tabele</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($table_exists) {
    echo "<p class='success'>✅ Tabela pacienți există</p>";
    
    // Verifică dacă există pacienți
    $patient_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p class='info'>📊 Număr pacienți: $patient_count</p>";
    
    if ($patient_count > 0) {
        // Afișează câțiva pacienți pentru testare
        $patients = $wpdb->get_results("
            SELECT p.user_id, p.cnp, p.family_name,
                   um1.meta_value as first_name, um2.meta_value as last_name
            FROM $table_name p
            LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
            LIMIT 3
        ");
        
        echo "<p class='success'>✅ Pacienți disponibili pentru testare:</p>";
        echo "<ul>";
        foreach ($patients as $patient) {
            $name = trim($patient->first_name . ' ' . $patient->last_name);
            $name = !empty($name) ? $name : 'Necunoscut';
            echo "<li><strong>$name</strong> - CNP: {$patient->cnp}" . 
                 ($patient->family_name ? " - Familia: {$patient->family_name}" : "") . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ Nu există pacienți pentru testare</p>";
    }
} else {
    echo "<p class='error'>❌ Tabela pacienți NU există</p>";
}

// Test 3: Verifică dacă fișierele există
echo "<h2>Test 3: Verificare Fișiere</h2>";
$files = array(
    'admin/views/patients.php' => 'Pagina pacienți',
    'assets/js/admin.js' => 'JavaScript admin',
    'assets/css/admin.css' => 'CSS admin'
);

foreach ($files as $file_path => $file_name) {
    $full_path = CLINICA_PLUGIN_PATH . $file_path;
    if (file_exists($full_path)) {
        echo "<p class='success'>✅ $file_name există</p>";
    } else {
        echo "<p class='error'>❌ $file_name NU există</p>";
    }
}

// Test 4: Verifică dacă AJAX handlers sunt înregistrați
echo "<h2>Test 4: Verificare AJAX Handlers</h2>";
global $wp_filter;

$ajax_handlers = array(
    'wp_ajax_clinica_search_patients_suggestions' => 'Căutare sugestii pacienți',
    'wp_ajax_clinica_search_families_suggestions' => 'Căutare sugestii familii'
);

foreach ($ajax_handlers as $handler => $description) {
    if (isset($wp_filter[$handler])) {
        echo "<p class='success'>✅ $description - handler înregistrat</p>";
    } else {
        echo "<p class='error'>❌ $description - handler NU înregistrat</p>";
    }
}

// Test 5: Verifică dacă scripturile sunt încărcate
echo "<h2>Test 5: Verificare Scripturi</h2>";
$admin_url = admin_url('admin.php?page=clinica-patients');
echo "<p class='info'>🔗 <a href='$admin_url' target='_blank'>Link către pagina de pacienți</a></p>";

// Test 6: Verifică dacă există familii
echo "<h2>Test 6: Verificare Familii</h2>";
if ($table_exists) {
    $families = $wpdb->get_results("
        SELECT family_id, family_name, COUNT(*) as member_count
        FROM $table_name 
        WHERE family_id IS NOT NULL AND family_id > 0 
        GROUP BY family_id, family_name
        LIMIT 3
    ");
    
    if ($families) {
        echo "<p class='success'>✅ Familii disponibile pentru testare:</p>";
        echo "<ul>";
        foreach ($families as $family) {
            echo "<li><strong>{$family->family_name}</strong> - {$family->member_count} membri</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ Nu există familii pentru testare</p>";
    }
}

echo "<h2>Instrucțiuni de Testare</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;'>";
echo "<h3>🎯 Pentru a testa autosuggest:</h3>";
echo "<ol>";
echo "<li><strong>Deschideți pagina de pacienți:</strong> <a href='$admin_url' target='_blank'>Click aici</a></li>";
echo "<li><strong>Deschideți Developer Tools:</strong> Apăsați F12</li>";
echo "<li><strong>Mergeți la tab-ul Console:</strong> Pentru a vedea mesajele de debug</li>";
echo "<li><strong>Începeți să scrieți în câmpul de căutare:</strong> Minim 2 caractere</li>";
echo "<li><strong>Verificați că apar sugestiile:</strong> Fără erori în console</li>";
echo "</ol>";

echo "<h3>🔍 Ce să căutați:</h3>";
echo "<ul>";
echo "<li><strong>Mesaje de debug:</strong> '=== DEBUG AUTOSUGGEST ===' în console</li>";
echo "<li><strong>Variabile disponibile:</strong> clinica_autosuggest, ajaxurl</li>";
echo "<li><strong>Cereri AJAX:</strong> În tab-ul Network</li>";
echo "<li><strong>Sugestii:</strong> Dropdown cu rezultatele căutării</li>";
echo "</ul>";

echo "<h3>🚨 Dacă nu funcționează:</h3>";
echo "<ul>";
echo "<li>Verificați că sunteți autentificat ca admin</li>";
echo "<li>Verificați că nu sunt erori JavaScript în console</li>";
echo "<li>Verificați că scripturile sunt încărcate în tab-ul Sources</li>";
echo "<li>Verificați că există pacienți în baza de date</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Status Final</h2>";
if (class_exists('Clinica_Plugin') && $table_exists && $patient_count > 0) {
    echo "<p class='success'>✅ Toate condițiile sunt îndeplinite pentru testarea autosuggest!</p>";
    echo "<p><strong>Următorul pas:</strong> <a href='$admin_url' target='_blank'>Testează autosuggest-ul</a></p>";
} else {
    echo "<p class='error'>❌ Există probleme care trebuie rezolvate înainte de testare.</p>";
}
?> 