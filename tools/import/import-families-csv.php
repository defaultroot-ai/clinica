<?php
/**
 * Import Familii din CSV
 * Script pentru importul familiilor din fișiere CSV
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Import Familii din CSV</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.form-group { margin: 10px 0; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn:hover { background: #005a87; }
.results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
.csv-preview { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; }
</style>";

// Procesare formular
if ($_POST['action'] === 'import_csv') {
    echo "<div class='section'>";
    echo "<h2>Rezultate Import CSV</h2>";
    
    $csv_data = $_POST['csv_data'];
    $delimiter = $_POST['delimiter'];
    $has_header = isset($_POST['has_header']);
    
    $results = process_csv_import($csv_data, $delimiter, $has_header);
    display_import_results($results);
    echo "</div>";
}

// Formular principal
echo "<div class='section'>";
echo "<h2>Import Familii din CSV</h2>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='import_csv'>";

echo "<div class='form-group'>";
echo "<label>Delimitator:</label>";
echo "<select name='delimiter'>";
echo "<option value=','>Virgulă (,)</option>";
echo "<option value=';'>Punct și virgulă (;)</option>";
echo "<option value='\t'>Tab</option>";
echo "<option value='|'>Pipe (|)</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label><input type='checkbox' name='has_header' checked> Primul rând conține header-ul</label>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Date CSV:</label>";
echo "<textarea name='csv_data' rows='15' placeholder='family_name,patient_cnp,family_role,first_name,last_name&#10;Familia Popescu,1800404080170,head,Ion,Popescu&#10;Familia Popescu,2800404080171,spouse,Maria,Popescu&#10;Familia Popescu,3800404080172,child,Ana,Popescu'></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<h3>Format CSV Așteptat:</h3>";
echo "<p class='info'>Coloanele necesare:</p>";
echo "<ul>";
echo "<li><strong>family_name</strong> - Numele familiei</li>";
echo "<li><strong>patient_cnp</strong> - CNP-ul pacientului</li>";
echo "<li><strong>family_role</strong> - Rolul în familie (head, spouse, child, parent, sibling)</li>";
echo "<li><strong>first_name</strong> - Prenumele (opțional, pentru verificare)</li>";
echo "<li><strong>last_name</strong> - Numele de familie (opțional, pentru verificare)</li>";
echo "</ul>";
echo "</div>";

echo "<button type='submit' class='btn'>📥 Import CSV</button>";
echo "</form>";
echo "</div>";

// Exemplu CSV
echo "<div class='section'>";
echo "<h2>Exemplu CSV</h2>";
echo "<div class='csv-preview'>";
echo "<pre>family_name,patient_cnp,family_role,first_name,last_name
Familia Popescu,1800404080170,head,Ion,Popescu
Familia Popescu,2800404080171,spouse,Maria,Popescu
Familia Popescu,3800404080172,child,Ana,Popescu
Familia Ionescu,4800404080173,head,Vasile,Ionescu
Familia Ionescu,5800404080174,spouse,Elena,Ionescu
Familia Dumitrescu,6800404080175,head,Gheorghe,Dumitrescu
Familia Dumitrescu,7800404080176,spouse,Ioana,Dumitrescu
Familia Dumitrescu,8800404080177,child,Mihai,Dumitrescu
Familia Dumitrescu,9800404080178,child,Andreea,Dumitrescu</pre>";
echo "</div>";
echo "</div>";

// Statistici curente
echo "<div class='section'>";
echo "<h2>Statistici Curente</h2>";
display_current_stats();
echo "</div>";

/**
 * Procesează importul CSV
 */
function process_csv_import($csv_data, $delimiter = ',', $has_header = true) {
    global $wpdb;
    $family_manager = new Clinica_Family_Manager();
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'warnings' => array(),
        'families_created' => 0,
        'members_added' => 0,
        'rows_processed' => 0
    );
    
    $lines = explode("\n", trim($csv_data));
    $families = array();
    $start_line = $has_header ? 1 : 0;
    
    // Parsează CSV-ul
    for ($i = $start_line; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        
        $parts = str_getcsv($line, $delimiter);
        
        if (count($parts) < 3) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Format invalid (necesită minim 3 coloane)";
            continue;
        }
        
        $family_name = trim($parts[0]);
        $patient_cnp = trim($parts[1]);
        $family_role = trim($parts[2]);
        $first_name = isset($parts[3]) ? trim($parts[3]) : '';
        $last_name = isset($parts[4]) ? trim($parts[4]) : '';
        
        // Validări
        if (empty($family_name)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Numele familiei este gol";
            continue;
        }
        
        if (empty($patient_cnp)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": CNP-ul pacientului este gol";
            continue;
        }
        
        if (!in_array($family_role, array('head', 'spouse', 'child', 'parent', 'sibling'))) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Rol invalid '$family_role'";
            continue;
        }
        
        if (!isset($families[$family_name])) {
            $families[$family_name] = array();
        }
        
        $families[$family_name][] = array(
            'cnp' => $patient_cnp,
            'role' => $family_role,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'line' => $i + 1
        );
        
        $results['rows_processed']++;
    }
    
    // Creează familiile
    foreach ($families as $family_name => $members) {
        echo "<p class='info'>🏠 Procesare familie: <strong>$family_name</strong></p>";
        
        $head_member = null;
        
        // Găsește capul familiei
        foreach ($members as $member) {
            if ($member['role'] === 'head') {
                $head_member = $member;
                break;
            }
        }
        
        // Dacă nu există cap, folosește primul membru
        if (!$head_member && !empty($members)) {
            $head_member = $members[0];
            $head_member['role'] = 'head';
            $results['warnings'][] = "Familia '$family_name': Nu s-a găsit cap de familie, folosesc primul membru";
        }
        
        if (!$head_member) {
            $results['errors'][] = "Familia '$family_name': Nu s-a găsit niciun membru";
            continue;
        }
        
        // Găsește pacientul după CNP
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, cnp FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $head_member['cnp']
        ));
        
        if (!$patient) {
            $results['errors'][] = "Familia '$family_name': Pacientul cu CNP {$head_member['cnp']} nu există (linia {$head_member['line']})";
            continue;
        }
        
        // Verifică dacă pacientul este deja într-o familie
        $existing_family = $wpdb->get_row($wpdb->prepare(
            "SELECT family_id, family_name FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d AND family_id IS NOT NULL",
            $patient->user_id
        ));
        
        if ($existing_family) {
            $results['warnings'][] = "Pacientul cu CNP {$head_member['cnp']} este deja în familia '{$existing_family->family_name}'";
            continue;
        }
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $patient->user_id);
        
        if (!$family_result['success']) {
            $results['errors'][] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $results['families_created']++;
        
        echo "<p class='success'>✅ Familia '$family_name' creată cu ID: $family_id</p>";
        
        // Adaugă membrii
        foreach ($members as $member) {
            if ($member['cnp'] === $head_member['cnp']) continue; // Capul familiei este deja adăugat
            
            $member_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id, cnp FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
                $member['cnp']
            ));
            
            if (!$member_patient) {
                $results['errors'][] = "Familia '$family_name': Pacientul cu CNP {$member['cnp']} nu există (linia {$member['line']})";
                continue;
            }
            
            // Verifică dacă pacientul este deja într-o familie
            $existing_family = $wpdb->get_row($wpdb->prepare(
                "SELECT family_id, family_name FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d AND family_id IS NOT NULL",
                $member_patient->user_id
            ));
            
            if ($existing_family) {
                $results['warnings'][] = "Pacientul cu CNP {$member['cnp']} este deja în familia '{$existing_family->family_name}'";
                continue;
            }
            
            $add_result = $family_manager->add_family_member($member_patient->user_id, $family_id, $member['role']);
            
            if ($add_result['success']) {
                $results['members_added']++;
                $name = trim($member['first_name'] . ' ' . $member['last_name']);
                $name = !empty($name) ? $name : $member['cnp'];
                echo "<p class='success'>✅ Membru adăugat: $name (rol: {$member['role']})</p>";
            } else {
                $results['errors'][] = "Familia '$family_name': " . $add_result['message'];
            }
        }
    }
    
    $results['success'] = 1;
    return $results;
}

/**
 * Afișează rezultatele importului
 */
function display_import_results($results) {
    if ($results['success']) {
        echo "<div class='results'>";
        echo "<h3>Import Finalizat!</h3>";
        echo "<p class='success'>✅ Rânduri procesate: {$results['rows_processed']}</p>";
        echo "<p class='success'>✅ Familii create: {$results['families_created']}</p>";
        echo "<p class='success'>✅ Membri adăugați: {$results['members_added']}</p>";
        
        if (!empty($results['warnings'])) {
            echo "<h4>Avertismente:</h4>";
            echo "<ul>";
            foreach ($results['warnings'] as $warning) {
                echo "<li class='warning'>⚠️ $warning</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($results['errors'])) {
            echo "<h4>Erori:</h4>";
            echo "<ul>";
            foreach ($results['errors'] as $error) {
                echo "<li class='error'>❌ $error</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
    } else {
        echo "<div class='results'>";
        echo "<h3>Eroare la Import</h3>";
        echo "<ul>";
        foreach ($results['errors'] as $error) {
            echo "<li class='error'>❌ $error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

/**
 * Afișează statisticile curente
 */
function display_current_stats() {
    global $wpdb;
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    
    // Statistici generale
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
    $patients_with_family = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients WHERE family_id IS NOT NULL");
    $families_count = $wpdb->get_var("SELECT COUNT(DISTINCT family_id) FROM $table_patients WHERE family_id IS NOT NULL");
    
    echo "<p class='info'>📊 Total pacienți: <strong>$total_patients</strong></p>";
    echo "<p class='info'>👨‍👩‍👧‍👦 Pacienți cu familie: <strong>$patients_with_family</strong></p>";
    echo "<p class='info'>🏠 Total familii: <strong>$families_count</strong></p>";
    
    // Top familii
    $top_families = $wpdb->get_results("
        SELECT family_name, COUNT(*) as member_count
        FROM $table_patients 
        WHERE family_id IS NOT NULL AND family_name IS NOT NULL
        GROUP BY family_id, family_name
        ORDER BY member_count DESC
        LIMIT 5
    ");
    
    if ($top_families) {
        echo "<h4>Top Familii:</h4>";
        echo "<ul>";
        foreach ($top_families as $family) {
            echo "<li><strong>{$family->family_name}</strong> - {$family->member_count} membri</li>";
        }
        echo "</ul>";
    }
    
    // Pacienți fără familie
    $patients_without_family = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients WHERE family_id IS NULL");
    echo "<p class='info'>👤 Pacienți fără familie: <strong>$patients_without_family</strong></p>";
}
?> 