<?php
/**
 * Test simplu pentru AJAX handlers
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test AJAX Handlers - Funcționalitate</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }</style>";

// Test 1: Verifică dacă plugin-ul este activ
echo "<div class='test-section'>";
echo "<h2>Test 1: Status Plugin</h2>";
if (class_exists('Clinica_Plugin')) {
    echo "<p class='success'>✅ Plugin-ul Clinica este activ</p>";
} else {
    echo "<p class='error'>❌ Plugin-ul Clinica NU este activ</p>";
    exit;
}
echo "</div>";

// Test 2: Verifică dacă AJAX handlers sunt înregistrați
echo "<div class='test-section'>";
echo "<h2>Test 2: Verificare AJAX Handlers</h2>";
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
echo "</div>";

// Test 3: Verifică dacă metodele există
echo "<div class='test-section'>";
echo "<h2>Test 3: Verificare Metode</h2>";
$plugin_instance = Clinica_Plugin::get_instance();
$methods = array(
    'ajax_search_patients_suggestions' => 'Metodă căutare pacienți',
    'ajax_search_families_suggestions' => 'Metodă căutare familii'
);

foreach ($methods as $method => $description) {
    if (method_exists($plugin_instance, $method)) {
        echo "<p class='success'>✅ $description - metodă găsită</p>";
    } else {
        echo "<p class='error'>❌ $description - metodă NU găsită</p>";
    }
}
echo "</div>";

// Test 4: Test direct al metodelor
echo "<div class='test-section'>";
echo "<h2>Test 4: Test Direct Metode</h2>";

// Simulează POST data pentru test
$_POST['nonce'] = wp_create_nonce('clinica_search_nonce');
$_POST['search_term'] = 'test';
$_POST['search_type'] = 'search-input';

try {
    // Testează metoda de căutare pacienți
    ob_start();
    $plugin_instance->ajax_search_patients_suggestions();
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p class='success'>✅ Metoda ajax_search_patients_suggestions funcționează</p>";
        
        // Încearcă să decodezi JSON
        $json_start = strpos($output, '{');
        if ($json_start !== false) {
            $json_part = substr($output, $json_start);
            $response = json_decode($json_part, true);
            
            if ($response) {
                echo "<p class='success'>✅ Răspuns JSON valid</p>";
                echo "<p class='info'>Success: " . ($response['success'] ? 'true' : 'false') . "</p>";
                if (isset($response['data']['suggestions'])) {
                    echo "<p class='info'>Sugestii găsite: " . count($response['data']['suggestions']) . "</p>";
                }
            } else {
                echo "<p class='warning'>⚠️ Răspunsul nu este JSON valid</p>";
            }
        }
        
        echo "<p class='info'>Output: " . htmlspecialchars(substr($output, 0, 200)) . "...</p>";
    } else {
        echo "<p class='error'>❌ Metoda ajax_search_patients_suggestions nu returnează nimic</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare la testarea metodei: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Verifică dacă există pacienți pentru testare
echo "<div class='test-section'>";
echo "<h2>Test 5: Verificare Pacienți pentru Testare</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patient_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "<p class='info'>📊 Număr total pacienți: $patient_count</p>";

if ($patient_count > 0) {
    // Afișează câțiva pacienți pentru testare
    $patients = $wpdb->get_results("
        SELECT p.user_id, p.cnp, p.family_name,
               um1.meta_value as first_name, um2.meta_value as last_name
        FROM $table_name p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        WHERE (um1.meta_value IS NOT NULL OR um2.meta_value IS NOT NULL)
        LIMIT 3
    ");
    
    if ($patients) {
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
        echo "<p class='warning'>⚠️ Nu s-au găsit pacienți cu nume pentru testare</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Nu există pacienți în baza de date</p>";
}
echo "</div>";

// Test 6: Verifică dacă există familii pentru testare
echo "<div class='test-section'>";
echo "<h2>Test 6: Verificare Familii pentru Testare</h2>";

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
echo "</div>";

// Test 7: Test AJAX cu date reale
echo "<div class='test-section'>";
echo "<h2>Test 7: Test AJAX cu Date Reale</h2>";

if ($patient_count > 0) {
    // Găsește un pacient pentru testare
    $test_patient = $wpdb->get_row("
        SELECT um1.meta_value as first_name, um2.meta_value as last_name
        FROM $table_name p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        WHERE um1.meta_value IS NOT NULL
        LIMIT 1
    ");
    
    if ($test_patient) {
        $test_name = $test_patient->first_name;
        echo "<p class='info'>🧪 Testare cu numele: <strong>$test_name</strong></p>";
        
        // Simulează căutarea cu numele real
        $_POST['search_term'] = $test_name;
        $_POST['search_type'] = 'search-input';
        
        try {
            ob_start();
            $plugin_instance->ajax_search_patients_suggestions();
            $output = ob_get_clean();
            
            $json_start = strpos($output, '{');
            if ($json_start !== false) {
                $json_part = substr($output, $json_start);
                $response = json_decode($json_part, true);
                
                if ($response && $response['success']) {
                    echo "<p class='success'>✅ AJAX funcționează cu date reale!</p>";
                    echo "<p class='info'>Sugestii găsite: " . count($response['data']['suggestions']) . "</p>";
                } else {
                    echo "<p class='warning'>⚠️ AJAX nu returnează succes</p>";
                }
            } else {
                echo "<p class='error'>❌ AJAX nu returnează JSON valid</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Eroare la testarea AJAX: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Nu s-au găsit pacienți cu nume pentru testare</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Nu există pacienți pentru testare</p>";
}
echo "</div>";

echo "<h2>Rezumat Testare</h2>";
echo "<p class='info'>ℹ️ Toate testele au fost finalizate. Verificați rezultatele de mai sus.</p>";

echo "<h3>🎯 Pentru a testa autosuggest în browser:</h3>";
echo "<ol>";
echo "<li><strong>Deschideți pagina de pacienți:</strong> <a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Click aici</a></li>";
echo "<li><strong>Deschideți Developer Tools:</strong> Apăsați F12</li>";
echo "<li><strong>Mergeți la tab-ul Console:</strong> Pentru a vedea mesajele de debug</li>";
echo "<li><strong>Începeți să scrieți în câmpul de căutare:</strong> Minim 2 caractere</li>";
echo "<li><strong>Verificați că apar sugestiile:</strong> Fără erori în console</li>";
echo "</ol>";

echo "<h3>🔍 Ce să căutați în console:</h3>";
echo "<ul>";
echo "<li><strong>Mesaje de debug:</strong> '=== DEBUG AUTOSUGGEST ==='</li>";
echo "<li><strong>Executare căutare:</strong> 'Executare căutare pentru...'</li>";
echo "<li><strong>AJAX URL:</strong> URL-ul pentru cereri AJAX</li>";
echo "<li><strong>Răspuns AJAX:</strong> Datele returnate de server</li>";
echo "</ul>";

echo "<h3>🚨 Dacă nu funcționează:</h3>";
echo "<ul>";
echo "<li>Verificați că sunteți autentificat ca admin</li>";
echo "<li>Verificați că nu sunt erori JavaScript în console</li>";
echo "<li>Verificați că există pacienți în baza de date</li>";
echo "<li>Verificați că AJAX handlers sunt înregistrați</li>";
echo "</ul>";

$admin_url = admin_url('admin.php?page=clinica-patients');
echo "<p><strong>Link pentru testare:</strong> <a href='$admin_url' target='_blank'>Pagina Pacienți</a></p>";
?> 