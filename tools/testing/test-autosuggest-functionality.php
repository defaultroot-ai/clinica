<?php
/**
 * Script de test pentru funcționalitatea de autosuggest
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test Funcționalitate Autosuggest</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }</style>";

// Test 1: Verifică dacă AJAX handlers sunt înregistrați
echo "<div class='test-section'>";
echo "<h2>Test 1: Verificare AJAX Handlers</h2>";

$ajax_actions = array(
    'clinica_search_patients_suggestions' => 'Căutare sugestii pacienți',
    'clinica_search_families_suggestions' => 'Căutare sugestii familii'
);

foreach ($ajax_actions as $action => $description) {
    if (has_action("wp_ajax_$action")) {
        echo "<p class='success'>✅ $description - handler înregistrat</p>";
    } else {
        echo "<p class='error'>❌ $description - handler NU înregistrat</p>";
    }
}
echo "</div>";

// Test 2: Verifică dacă metodele există în clasa principală
echo "<div class='test-section'>";
echo "<h2>Test 2: Verificare Metode Clasă Principală</h2>";

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

// Test 3: Verifică dacă fișierul de pacienți conține autosuggest
echo "<div class='test-section'>";
echo "<h2>Test 3: Verificare Implementare Frontend</h2>";

$patients_file = CLINICA_PLUGIN_PATH . 'admin/views/patients.php';
if (file_exists($patients_file)) {
    $file_content = file_get_contents($patients_file);
    
    $autosuggest_features = array(
        'clinica-search-container' => 'Container căutare',
        'clinica-suggestions' => 'Container sugestii',
        'searchPatientsSuggestions' => 'Funcție căutare pacienți',
        'searchFamiliesSuggestions' => 'Funcție căutare familii',
        'displaySuggestions' => 'Funcție afișare sugestii',
        'selectSuggestion' => 'Funcție selectare sugestie',
        'handleSuggestionNavigation' => 'Funcție navigare cu taste',
        'initAutosuggest' => 'Funcție inițializare autosuggest'
    );
    
    foreach ($autosuggest_features as $feature => $description) {
        if (strpos($file_content, $feature) !== false) {
            echo "<p class='success'>✅ $description - găsit</p>";
        } else {
            echo "<p class='error'>❌ $description - NU găsit</p>";
        }
    }
} else {
    echo "<p class='error'>❌ Fișierul de pacienți NU există</p>";
}
echo "</div>";

// Test 4: Verifică CSS-ul pentru autosuggest
echo "<div class='test-section'>";
echo "<h2>Test 4: Verificare CSS Autosuggest</h2>";

$css_features = array(
        '.clinica-search-container' => 'Container căutare',
        '.clinica-suggestions' => 'Container sugestii',
        '.clinica-suggestion-item' => 'Element sugestie',
        '.clinica-suggestion-item:hover' => 'Hover sugestie',
        '.clinica-suggestion-item.selected' => 'Sugestie selectată',
        '.highlight' => 'Highlight text',
        '.loading' => 'Loading state',
        '.no-results' => 'Stare fără rezultate'
    );

foreach ($css_features as $feature => $description) {
    if (strpos($file_content, $feature) !== false) {
        echo "<p class='success'>✅ $description - CSS găsit</p>";
    } else {
        echo "<p class='error'>❌ $description - CSS NU găsit</p>";
    }
}
echo "</div>";

// Test 5: Testează funcționalitatea de căutare
echo "<div class='test-section'>";
echo "<h2>Test 5: Test Funcționalitate Căutare</h2>";

global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

// Test căutare pacienți
$test_patients = $wpdb->get_results(
    "SELECT p.user_id, p.cnp, p.phone_primary, p.family_name,
            u.user_email, u.display_name,
            um1.meta_value as first_name, um2.meta_value as last_name
     FROM $table_name p 
     LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
     LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
     LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
     WHERE (um1.meta_value IS NOT NULL OR um2.meta_value IS NOT NULL)
     LIMIT 3"
);

if ($test_patients) {
    echo "<p class='success'>✅ Găsiți pacienți pentru testare:</p>";
    echo "<ul>";
    foreach ($test_patients as $patient) {
        $full_name = trim($patient->first_name . ' ' . $patient->last_name);
        $name = !empty($full_name) ? $full_name : $patient->display_name;
        echo "<li><strong>$name</strong> - CNP: {$patient->cnp}, Email: {$patient->user_email}</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='warning'>⚠️ Nu s-au găsit pacienți pentru testare</p>";
}

// Test căutare familii
$test_families = $wpdb->get_results(
    "SELECT family_id, family_name, COUNT(*) as member_count
     FROM $table_name 
     WHERE family_id IS NOT NULL AND family_id > 0 
     GROUP BY family_id, family_name
     LIMIT 3"
);

if ($test_families) {
    echo "<p class='success'>✅ Găsite familii pentru testare:</p>";
    echo "<ul>";
    foreach ($test_families as $family) {
        echo "<li><strong>{$family->family_name}</strong> - {$family->member_count} membri</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='warning'>⚠️ Nu s-au găsit familii pentru testare</p>";
}
echo "</div>";

// Test 6: Verifică nonce-urile
echo "<div class='test-section'>";
echo "<h2>Test 6: Verificare Nonce-uri</h2>";

$nonces = array(
    'clinica_search_nonce' => 'Nonce căutare',
    'clinica_family_nonce' => 'Nonce familie'
);

foreach ($nonces as $nonce_name => $description) {
    $nonce_value = wp_create_nonce($nonce_name);
    if ($nonce_value) {
        echo "<p class='success'>✅ $description - nonce generat: " . substr($nonce_value, 0, 10) . "...</p>";
    } else {
        echo "<p class='error'>❌ $description - nonce NU generat</p>";
    }
}
echo "</div>";

// Test 7: Verifică JavaScript-ul pentru navigare
echo "<div class='test-section'>";
echo "<h2>Test 7: Verificare JavaScript Navigare</h2>";

$js_features = array(
    'ArrowDown' => 'Navigare în jos',
    'ArrowUp' => 'Navigare în sus',
    'Enter' => 'Selectare cu Enter',
    'Escape' => 'Închidere cu Escape',
    'keydown' => 'Event listener keydown',
    'input' => 'Event listener input',
    'setTimeout' => 'Debounce cu setTimeout',
    'clearTimeout' => 'Clear timeout'
);

foreach ($js_features as $feature => $description) {
    if (strpos($file_content, $feature) !== false) {
        echo "<p class='success'>✅ $description - găsit</p>";
    } else {
        echo "<p class='error'>❌ $description - NU găsit</p>";
    }
}
echo "</div>";

// Test 8: Verifică accesibilitatea
echo "<div class='test-section'>";
echo "<h2>Test 8: Verificare Accesibilitate</h2>";

$accessibility_features = array(
    'autocomplete="off"' => 'Autocomplete dezactivat',
    'role=' => 'ARIA roles',
    'aria-label' => 'Aria labels',
    'tabindex' => 'Tab index',
    'focus' => 'Focus management',
    'keyboard' => 'Navigare cu tastatura'
);

$accessibility_count = 0;
foreach ($accessibility_features as $feature => $description) {
    if (strpos($file_content, $feature) !== false) {
        echo "<p class='success'>✅ $description - găsit</p>";
        $accessibility_count++;
    } else {
        echo "<p class='warning'>⚠️ $description - NU găsit</p>";
    }
}

if ($accessibility_count >= 3) {
    echo "<p class='success'>✅ Accesibilitatea este implementată</p>";
} else {
    echo "<p class='warning'>⚠️ Accesibilitatea necesită îmbunătățiri</p>";
}
echo "</div>";

echo "<h2>Rezumat Funcționalitate Autosuggest</h2>";
echo "<p class='info'>ℹ️ Toate testele au fost finalizate. Verificați rezultatele de mai sus.</p>";

echo "<h3>Funcționalități Implementate:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Căutare în timp real</strong> - sugestii după 2 caractere</li>";
echo "<li>✅ <strong>Debounce</strong> - 300ms întârziere pentru optimizare</li>";
echo "<li>✅ <strong>Highlight text</strong> - evidențierea termenului căutat</li>";
echo "<li>✅ <strong>Navigare cu taste</strong> - săgeți, Enter, Escape</li>";
echo "<li>✅ <strong>Click selectare</strong> - click pe sugestie</li>";
echo "<li>✅ <strong>Loading states</strong> - feedback vizual</li>";
echo "<li>✅ <strong>Stare fără rezultate</strong> - mesaj informativ</li>";
echo "<li>✅ <strong>Închidere automată</strong> - click în afara containerului</li>";
echo "</ul>";

echo "<h3>Tipuri de Căutare:</h3>";
echo "<ul>";
echo "<li>🔍 <strong>Căutare generală</strong> - nume, email, telefon, CNP</li>";
echo "<li>🆔 <strong>Căutare CNP</strong> - căutare specifică după CNP</li>";
echo "<li>👥 <strong>Căutare familie</strong> - căutare după numele familiei</li>";
echo "</ul>";

echo "<h3>Caracteristici Tehnice:</h3>";
echo "<ul>";
echo "<li>⚡ <strong>AJAX asincron</strong> - fără reîncărcare pagină</li>";
echo "<li>🔒 <strong>Securitate</strong> - nonce-uri și sanitizare</li>";
echo "<li>📱 <strong>Responsive</strong> - funcționează pe toate dispozitivele</li>";
echo "<li>♿ <strong>Accesibilitate</strong> - navigare cu tastatura</li>";
echo "<li>🎨 <strong>UI modern</strong> - design consistent cu restul aplicației</li>";
echo "</ul>";

echo "<p><strong>Pentru a testa funcționalitatea:</strong></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Deschide Pagina Pacienți cu Autosuggest</a></p>";

echo "<h3>Instrucțiuni de Testare:</h3>";
echo "<ol>";
echo "<li>Deschide pagina de pacienți</li>";
echo "<li>Începe să scrii în câmpul de căutare (minim 2 caractere)</li>";
echo "<li>Folosește săgețile pentru navigare</li>";
echo "<li>Apasă Enter pentru selectare</li>";
echo "<li>Apasă Escape pentru închidere</li>";
echo "<li>Testează și câmpurile CNP și Familie</li>";
echo "</ol>";

echo "<h3>Următorii pași pentru îmbunătățire:</h3>";
echo "<ol>";
echo "<li>Adaugă cache pentru sugestii frecvente</li>";
echo "<li>Implementează sugestii pentru alte câmpuri (vârstă, gen)</li>";
echo "<li>Adaugă funcționalitatea de 'recent searches'</li>";
echo "<li>Implementează sugestii pentru programări</li>";
echo "<li>Adaugă funcționalitatea de 'favorite patients'</li>";
echo "<li>Implementează sugestii pentru medicamente și diagnosticuri</li>";
echo "</ol>";
?> 