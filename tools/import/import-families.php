<?php
/**
 * Import Familii și Alocare Utilizatori
 * Script pentru importul familiilor și alocarea utilizatorilor la familii
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

// Încarcă clasele necesare
$family_manager = new Clinica_Family_Manager();

echo "<h1>Import Familii și Alocare Utilizatori</h1>";
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
</style>";

// Procesare formular
if ($_POST['action'] === 'import_families') {
    echo "<div class='section'>";
    echo "<h2>Rezultate Import</h2>";
    
    $import_method = sanitize_text_field($_POST['import_method']);
    $results = array();
    
    switch ($import_method) {
        case 'csv':
            $results = process_csv_import($_POST['csv_data']);
            break;
        case 'manual':
            $results = process_manual_import($_POST['family_data']);
            break;
        case 'auto_detect':
            $results = process_auto_detect_families();
            break;
        case 'existing_patients':
            $results = process_existing_patients_import($_POST['family_mapping']);
            break;
    }
    
    display_import_results($results);
    echo "</div>";
}

// Formular principal
echo "<div class='section'>";
echo "<h2>Metode de Import Familii</h2>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='import_families'>";

echo "<div class='form-group'>";
echo "<label>Metodă de import:</label>";
echo "<select name='import_method' id='import_method' onchange='toggleImportMethod()'>";
echo "<option value=''>Selectează o metodă...</option>";
echo "<option value='csv'>Import CSV</option>";
echo "<option value='manual'>Import Manual</option>";
echo "<option value='auto_detect'>Detectare Automată</option>";
echo "<option value='existing_patients'>Import din Pacienți Existenți</option>";
echo "</select>";
echo "</div>";

// Import CSV
echo "<div id='csv_import' style='display:none;' class='form-group'>";
echo "<label>Date CSV (format: family_name,patient_cnp,family_role):</label>";
echo "<textarea name='csv_data' rows='10' placeholder='Familia Popescu,1800404080170,head&#10;Familia Popescu,2800404080171,spouse&#10;Familia Popescu,3800404080172,child'></textarea>";
echo "<p class='info'>Format: nume_familie,cnp_pacient,rol_familie</p>";
echo "<p class='info'>Roluri disponibile: head, spouse, child, parent, sibling</p>";
echo "</div>";

// Import Manual
echo "<div id='manual_import' style='display:none;' class='form-group'>";
echo "<label>Date Familie (JSON):</label>";
echo "<textarea name='family_data' rows='10' placeholder='[&#10;  {&#10;    \"family_name\": \"Familia Popescu\",&#10;    \"members\": [&#10;      {\"cnp\": \"1800404080170\", \"role\": \"head\"},&#10;      {\"cnp\": \"2800404080171\", \"role\": \"spouse\"}&#10;    ]&#10;  }&#10;]'></textarea>";
echo "</div>";

// Import din Pacienți Existenți
echo "<div id='existing_patients_import' style='display:none;' class='form-group'>";
echo "<label>Mapare Familii din Pacienți Existenți:</label>";
echo "<textarea name='family_mapping' rows='10' placeholder='Familia Popescu: 1800404080170, 2800404080171&#10;Familia Ionescu: 3800404080172, 4800404080173'></textarea>";
echo "<p class='info'>Format: nume_familie: cnp1, cnp2, cnp3</p>";
echo "</div>";

echo "<button type='submit' class='btn'>Import Familii</button>";
echo "</form>";
echo "</div>";

// Statistici curente
echo "<div class='section'>";
echo "<h2>Statistici Curente</h2>";
display_current_stats();
echo "</div>";

// Script JavaScript
echo "<script>
function toggleImportMethod() {
    const method = document.getElementById('import_method').value;
    
    // Ascunde toate div-urile
    document.getElementById('csv_import').style.display = 'none';
    document.getElementById('manual_import').style.display = 'none';
    document.getElementById('existing_patients_import').style.display = 'none';
    
    // Afișează div-ul corespunzător
    if (method === 'csv') {
        document.getElementById('csv_import').style.display = 'block';
    } else if (method === 'manual') {
        document.getElementById('manual_import').style.display = 'block';
    } else if (method === 'existing_patients') {
        document.getElementById('existing_patients_import').style.display = 'block';
    }
}
</script>";

/**
 * Procesează importul CSV
 */
function process_csv_import($csv_data) {
    global $wpdb, $family_manager;
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'families_created' => 0,
        'members_added' => 0
    );
    
    $lines = explode("\n", trim($csv_data));
    $families = array();
    
    // Parsează CSV-ul
    foreach ($lines as $line_num => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = explode(',', $line);
        if (count($parts) < 3) {
            $results['errors'][] = "Linia " . ($line_num + 1) . ": Format invalid (necesită 3 coloane)";
            continue;
        }
        
        $family_name = trim($parts[0]);
        $patient_cnp = trim($parts[1]);
        $family_role = trim($parts[2]);
        
        if (!in_array($family_role, array('head', 'spouse', 'child', 'parent', 'sibling'))) {
            $results['errors'][] = "Linia " . ($line_num + 1) . ": Rol invalid '$family_role'";
            continue;
        }
        
        if (!isset($families[$family_name])) {
            $families[$family_name] = array();
        }
        
        $families[$family_name][] = array(
            'cnp' => $patient_cnp,
            'role' => $family_role
        );
    }
    
    // Creează familiile
    foreach ($families as $family_name => $members) {
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
        }
        
        if (!$head_member) {
            $results['errors'][] = "Familia '$family_name': Nu s-a găsit niciun membru";
            continue;
        }
        
        // Găsește pacientul după CNP
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $head_member['cnp']
        ));
        
        if (!$patient) {
            $results['errors'][] = "Familia '$family_name': Pacientul cu CNP {$head_member['cnp']} nu există";
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
        
        // Adaugă membrii
        foreach ($members as $member) {
            if ($member['cnp'] === $head_member['cnp']) continue; // Capul familiei este deja adăugat
            
            $member_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
                $member['cnp']
            ));
            
            if (!$member_patient) {
                $results['errors'][] = "Familia '$family_name': Pacientul cu CNP {$member['cnp']} nu există";
                continue;
            }
            
            $add_result = $family_manager->add_family_member($member_patient->user_id, $family_id, $member['role']);
            
            if ($add_result['success']) {
                $results['members_added']++;
            } else {
                $results['errors'][] = "Familia '$family_name': " . $add_result['message'];
            }
        }
    }
    
    $results['success'] = 1;
    return $results;
}

/**
 * Procesează importul manual (JSON)
 */
function process_manual_import($json_data) {
    global $wpdb, $family_manager;
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'families_created' => 0,
        'members_added' => 0
    );
    
    $families = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $results['errors'][] = "JSON invalid: " . json_last_error_msg();
        return $results;
    }
    
    foreach ($families as $family) {
        if (empty($family['family_name']) || empty($family['members'])) {
            $results['errors'][] = "Familie invalidă: nume sau membri lipsă";
            continue;
        }
        
        $head_member = null;
        
        // Găsește capul familiei
        foreach ($family['members'] as $member) {
            if ($member['role'] === 'head') {
                $head_member = $member;
                break;
            }
        }
        
        // Dacă nu există cap, folosește primul membru
        if (!$head_member && !empty($family['members'])) {
            $head_member = $family['members'][0];
            $head_member['role'] = 'head';
        }
        
        if (!$head_member) {
            $results['errors'][] = "Familia '{$family['family_name']}': Nu s-a găsit niciun membru";
            continue;
        }
        
        // Găsește pacientul după CNP
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $head_member['cnp']
        ));
        
        if (!$patient) {
            $results['errors'][] = "Familia '{$family['family_name']}': Pacientul cu CNP {$head_member['cnp']} nu există";
            continue;
        }
        
        // Creează familia
        $family_result = $family_manager->create_family($family['family_name'], $patient->user_id);
        
        if (!$family_result['success']) {
            $results['errors'][] = "Familia '{$family['family_name']}': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $results['families_created']++;
        
        // Adaugă membrii
        foreach ($family['members'] as $member) {
            if ($member['cnp'] === $head_member['cnp']) continue;
            
            $member_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
                $member['cnp']
            ));
            
            if (!$member_patient) {
                $results['errors'][] = "Familia '{$family['family_name']}': Pacientul cu CNP {$member['cnp']} nu există";
                continue;
            }
            
            $add_result = $family_manager->add_family_member($member_patient->user_id, $family_id, $member['role']);
            
            if ($add_result['success']) {
                $results['members_added']++;
            } else {
                $results['errors'][] = "Familia '{$family['family_name']}': " . $add_result['message'];
            }
        }
    }
    
    $results['success'] = 1;
    return $results;
}

/**
 * Detectează automat familiile din pacienții existenți
 */
function process_auto_detect_families() {
    global $wpdb, $family_manager;
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'families_created' => 0,
        'members_added' => 0
    );
    
    // Găsește pacienții care au același nume de familie
    $patients = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name
        FROM {$wpdb->prefix}clinica_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        WHERE um2.meta_value IS NOT NULL AND um2.meta_value != ''
        ORDER BY um2.meta_value, um1.meta_value
    ");
    
    $families = array();
    
    // Grupează pacienții după numele de familie
    foreach ($patients as $patient) {
        $last_name = trim($patient->last_name);
        if (empty($last_name)) continue;
        
        if (!isset($families[$last_name])) {
            $families[$last_name] = array();
        }
        
        $families[$last_name][] = $patient;
    }
    
    // Creează familiile pentru grupurile cu mai mulți membri
    foreach ($families as $family_name => $members) {
        if (count($members) < 2) continue; // Doar familiile cu mai mulți membri
        
        // Sortează membrii (primul devine capul familiei)
        usort($members, function($a, $b) {
            return strcmp($a->first_name, $b->first_name);
        });
        
        $head_patient = $members[0];
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $head_patient->user_id);
        
        if (!$family_result['success']) {
            $results['errors'][] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $results['families_created']++;
        
        // Adaugă restul membrilor
        for ($i = 1; $i < count($members); $i++) {
            $role = ($i === 1) ? 'spouse' : 'child'; // Al doilea membru = soț, restul = copii
            
            $add_result = $family_manager->add_family_member($members[$i]->user_id, $family_id, $role);
            
            if ($add_result['success']) {
                $results['members_added']++;
            } else {
                $results['errors'][] = "Familia '$family_name': " . $add_result['message'];
            }
        }
    }
    
    $results['success'] = 1;
    return $results;
}

/**
 * Import din pacienții existenți cu mapare manuală
 */
function process_existing_patients_import($mapping_data) {
    global $wpdb, $family_manager;
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'families_created' => 0,
        'members_added' => 0
    );
    
    $lines = explode("\n", trim($mapping_data));
    
    foreach ($lines as $line_num => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = explode(':', $line, 2);
        if (count($parts) < 2) {
            $results['errors'][] = "Linia " . ($line_num + 1) . ": Format invalid (necesită 'nume: cnp1, cnp2')";
            continue;
        }
        
        $family_name = trim($parts[0]);
        $cnp_list = array_map('trim', explode(',', $parts[1]));
        
        if (empty($cnp_list)) {
            $results['errors'][] = "Familia '$family_name': Nu s-au specificat CNP-uri";
            continue;
        }
        
        $head_cnp = $cnp_list[0];
        
        // Găsește capul familiei
        $head_patient = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $head_cnp
        ));
        
        if (!$head_patient) {
            $results['errors'][] = "Familia '$family_name': Pacientul cu CNP $head_cnp nu există";
            continue;
        }
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $head_patient->user_id);
        
        if (!$family_result['success']) {
            $results['errors'][] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $results['families_created']++;
        
        // Adaugă restul membrilor
        for ($i = 1; $i < count($cnp_list); $i++) {
            $cnp = $cnp_list[$i];
            
            $member_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
                $cnp
            ));
            
            if (!$member_patient) {
                $results['errors'][] = "Familia '$family_name': Pacientul cu CNP $cnp nu există";
                continue;
            }
            
            $role = ($i === 1) ? 'spouse' : 'child';
            
            $add_result = $family_manager->add_family_member($member_patient->user_id, $family_id, $role);
            
            if ($add_result['success']) {
                $results['members_added']++;
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
        echo "<h3>Import Finalizat cu Succes!</h3>";
        echo "<p class='success'>✅ Familii create: {$results['families_created']}</p>";
        echo "<p class='success'>✅ Membri adăugați: {$results['members_added']}</p>";
        
        if (!empty($results['errors'])) {
            echo "<h4>Erori întâlnite:</h4>";
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
    
    echo "<p class='info'>📊 Total pacienți: $total_patients</p>";
    echo "<p class='info'>👨‍👩‍👧‍👦 Pacienți cu familie: $patients_with_family</p>";
    echo "<p class='info'>🏠 Total familii: $families_count</p>";
    
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
        echo "<h4>Top 5 Familii:</h4>";
        echo "<ul>";
        foreach ($top_families as $family) {
            echo "<li><strong>{$family->family_name}</strong> - {$family->member_count} membri</li>";
        }
        echo "</ul>";
    }
    
    // Pacienți fără familie
    $patients_without_family = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name
        FROM $table_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        WHERE p.family_id IS NULL
        LIMIT 10
    ");
    
    if ($patients_without_family) {
        echo "<h4>Pacienți fără familie (primele 10):</h4>";
        echo "<ul>";
        foreach ($patients_without_family as $patient) {
            $name = trim($patient->first_name . ' ' . $patient->last_name);
            $name = !empty($name) ? $name : 'Necunoscut';
            echo "<li>$name - CNP: {$patient->cnp}</li>";
        }
        echo "</ul>";
    }
}
?> 