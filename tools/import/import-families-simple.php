<?php
/**
 * Import Familii Simplu
 * Script rapid pentru importul familiilor din date existente
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Import Familii Simplu</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn:hover { background: #005a87; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    $family_manager = new Clinica_Family_Manager();
    
    switch ($_POST['action']) {
        case 'auto_detect':
            auto_detect_families();
            break;
        case 'create_sample':
            create_sample_families();
            break;
        case 'clear_families':
            clear_all_families();
            break;
    }
}

// Afișează statisticile
display_stats();

// Formulare de acțiune
echo "<div class='section'>";
echo "<h2>Acțiuni Import</h2>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='auto_detect'>";
echo "<button type='submit' class='btn'>🔍 Detectare Automată Familii</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='create_sample'>";
echo "<button type='submit' class='btn'>📝 Creează Familii Exemplu</button>";
echo "</form>";

echo "<form method='post' style='display:inline;' onsubmit='return confirm(\"Sigur vrei să ștergi toate familiile?\")'>";
echo "<input type='hidden' name='action' value='clear_families'>";
echo "<button type='submit' class='btn' style='background:#dc3232;'>🗑️ Șterge Toate Familiile</button>";
echo "</form>";

echo "</div>";

/**
 * Detectează automat familiile din pacienții existenți
 */
function auto_detect_families() {
    global $wpdb;
    $family_manager = new Clinica_Family_Manager();
    
    echo "<div class='section'>";
    echo "<h3>Detectare Automată Familii</h3>";
    
    // Găsește pacienții cu nume de familie
    $patients = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name
        FROM {$wpdb->prefix}clinica_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        WHERE um2.meta_value IS NOT NULL AND um2.meta_value != ''
        ORDER BY um2.meta_value, um1.meta_value
    ");
    
    $families = array();
    $created = 0;
    $errors = array();
    
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
        
        echo "<p class='info'>🏠 Procesare familie: <strong>$family_name</strong> ({$members[0]->cnp})</p>";
        
        // Sortează membrii (primul devine capul familiei)
        usort($members, function($a, $b) {
            return strcmp($a->first_name, $b->first_name);
        });
        
        $head_patient = $members[0];
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $head_patient->user_id);
        
        if (!$family_result['success']) {
            $errors[] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $created++;
        
        echo "<p class='success'>✅ Familia '$family_name' creată cu ID: $family_id</p>";
        
        // Adaugă restul membrilor
        for ($i = 1; $i < count($members); $i++) {
            $role = ($i === 1) ? 'spouse' : 'child';
            $member = $members[$i];
            
            $add_result = $family_manager->add_family_member($member->user_id, $family_id, $role);
            
            if ($add_result['success']) {
                echo "<p class='success'>✅ Membru adăugat: {$member->first_name} {$member->last_name} (rol: $role)</p>";
            } else {
                $errors[] = "Familia '$family_name': " . $add_result['message'];
            }
        }
    }
    
    echo "<h4>Rezumat:</h4>";
    echo "<p class='success'>✅ Familii create: $created</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    echo "</div>";
}

/**
 * Creează familii exemplu pentru testare
 */
function create_sample_families() {
    global $wpdb;
    $family_manager = new Clinica_Family_Manager();
    
    echo "<div class='section'>";
    echo "<h3>Creează Familii Exemplu</h3>";
    
    // Familii exemplu
    $sample_families = array(
        'Familia Popescu' => array(
            array('cnp' => '1800404080170', 'role' => 'head', 'name' => 'Ion Popescu'),
            array('cnp' => '2800404080171', 'role' => 'spouse', 'name' => 'Maria Popescu'),
            array('cnp' => '3800404080172', 'role' => 'child', 'name' => 'Ana Popescu')
        ),
        'Familia Ionescu' => array(
            array('cnp' => '4800404080173', 'role' => 'head', 'name' => 'Vasile Ionescu'),
            array('cnp' => '5800404080174', 'role' => 'spouse', 'name' => 'Elena Ionescu')
        ),
        'Familia Dumitrescu' => array(
            array('cnp' => '6800404080175', 'role' => 'head', 'name' => 'Gheorghe Dumitrescu'),
            array('cnp' => '7800404080176', 'role' => 'spouse', 'name' => 'Ioana Dumitrescu'),
            array('cnp' => '8800404080177', 'role' => 'child', 'name' => 'Mihai Dumitrescu'),
            array('cnp' => '9800404080178', 'role' => 'child', 'name' => 'Andreea Dumitrescu')
        )
    );
    
    $created = 0;
    $errors = array();
    
    foreach ($sample_families as $family_name => $members) {
        echo "<p class='info'>🏠 Creez familia: <strong>$family_name</strong></p>";
        
        $head_member = null;
        foreach ($members as $member) {
            if ($member['role'] === 'head') {
                $head_member = $member;
                break;
            }
        }
        
        if (!$head_member) {
            $head_member = $members[0];
            $head_member['role'] = 'head';
        }
        
        // Verifică dacă pacientul există
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $head_member['cnp']
        ));
        
        if (!$patient) {
            $errors[] = "Pacientul cu CNP {$head_member['cnp']} nu există pentru familia '$family_name'";
            continue;
        }
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $patient->user_id);
        
        if (!$family_result['success']) {
            $errors[] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $created++;
        
        echo "<p class='success'>✅ Familia '$family_name' creată cu ID: $family_id</p>";
        
        // Adaugă membrii
        foreach ($members as $member) {
            if ($member['cnp'] === $head_member['cnp']) continue;
            
            $member_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
                $member['cnp']
            ));
            
            if (!$member_patient) {
                $errors[] = "Pacientul cu CNP {$member['cnp']} nu există";
                continue;
            }
            
            $add_result = $family_manager->add_family_member($member_patient->user_id, $family_id, $member['role']);
            
            if ($add_result['success']) {
                echo "<p class='success'>✅ Membru adăugat: {$member['name']} (rol: {$member['role']})</p>";
            } else {
                $errors[] = "Familia '$family_name': " . $add_result['message'];
            }
        }
    }
    
    echo "<h4>Rezumat:</h4>";
    echo "<p class='success'>✅ Familii create: $created</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    echo "</div>";
}

/**
 * Șterge toate familiile
 */
function clear_all_families() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>Ștergere Familii</h3>";
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    
    $result = $wpdb->query("
        UPDATE $table_patients 
        SET family_id = NULL, family_role = NULL, family_head_id = NULL, family_name = NULL
        WHERE family_id IS NOT NULL
    ");
    
    if ($result !== false) {
        echo "<p class='success'>✅ Toate familiile au fost șterse! ($result pacienți actualizați)</p>";
    } else {
        echo "<p class='error'>❌ Eroare la ștergerea familiilor</p>";
    }
    
    echo "</div>";
}

/**
 * Afișează statisticile curente
 */
function display_stats() {
    global $wpdb;
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    
    // Statistici generale
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
    $patients_with_family = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients WHERE family_id IS NOT NULL");
    $families_count = $wpdb->get_var("SELECT COUNT(DISTINCT family_id) FROM $table_patients WHERE family_id IS NOT NULL");
    
    echo "<div class='section'>";
    echo "<h2>Statistici Curente</h2>";
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
    
    echo "</div>";
}
?> 