<?php
/**
 * Import Familii din Emailuri
 * Script pentru detectarea familiilor din emailuri de tip parinte+copil@email.com
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Import Familii din Emailuri</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn:hover { background: #005a87; }
.results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
.email-preview { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; }
.family-group { margin: 10px 0; padding: 10px; border: 1px solid #eee; border-radius: 4px; background: #fafafa; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    $family_manager = new Clinica_Family_Manager();
    
    switch ($_POST['action']) {
        case 'detect_from_emails':
            detect_families_from_emails();
            break;
        case 'preview_detection':
            preview_family_detection();
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
echo "<h2>Detectare Familii din Emailuri</h2>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='preview_detection'>";
echo "<button type='submit' class='btn'>🔍 Previzualizare Detectare</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='detect_from_emails'>";
echo "<button type='submit' class='btn'>🏠 Creează Familiile Detectate</button>";
echo "</form>";

echo "<form method='post' style='display:inline;' onsubmit='return confirm(\"Sigur vrei să ștergi toate familiile?\")'>";
echo "<input type='hidden' name='action' value='clear_families'>";
echo "<button type='submit' class='btn' style='background:#dc3232;'>🗑️ Șterge Toate Familiile</button>";
echo "</form>";

echo "</div>";

/**
 * Detectează familiile din emailurile de tip parinte+copil@email.com
 */
function detect_families_from_emails() {
    global $wpdb;
    $family_manager = new Clinica_Family_Manager();
    
    echo "<div class='section'>";
    echo "<h3>Detectare Familii din Emailuri</h3>";
    
    // Găsește pacienții cu emailuri
    $patients = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as email
        FROM {$wpdb->prefix}clinica_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON p.user_id = um3.user_id AND um3.meta_key = 'email'
        WHERE um3.meta_value IS NOT NULL AND um3.meta_value != ''
        ORDER BY um3.meta_value
    ");
    
    $email_families = array();
    $created = 0;
    $errors = array();
    
    // Analizează emailurile pentru a găsi familiile
    foreach ($patients as $patient) {
        $email = trim($patient->email);
        if (empty($email)) continue;
        
        $family_info = parse_family_email($email);
        if ($family_info) {
            $family_key = $family_info['domain'];
            if (!isset($email_families[$family_key])) {
                $email_families[$family_key] = array();
            }
            
            $email_families[$family_key][] = array(
                'patient' => $patient,
                'family_info' => $family_info
            );
        }
    }
    
    // Creează familiile pentru grupurile cu mai mulți membri
    foreach ($email_families as $domain => $members) {
        if (count($members) < 2) continue; // Doar familiile cu mai mulți membri
        
        echo "<div class='family-group'>";
        echo "<h4>🏠 Familia din domeniul: <strong>$domain</strong></h4>";
        
        // Sortează membrii (părinții primii, apoi copiii)
        usort($members, function($a, $b) {
            $a_has_parent = !empty($a['family_info']['parent']);
            $b_has_parent = !empty($b['family_info']['parent']);
            
            if ($a_has_parent && !$b_has_parent) return -1;
            if (!$a_has_parent && $b_has_parent) return 1;
            
            return strcmp($a['patient']->first_name, $b['patient']->first_name);
        });
        
        // Găsește capul familiei (primul părinte sau primul membru)
        $head_member = null;
        foreach ($members as $member) {
            if (!empty($member['family_info']['parent'])) {
                $head_member = $member;
                break;
            }
        }
        
        if (!$head_member && !empty($members)) {
            $head_member = $members[0];
        }
        
        if (!$head_member) {
            $errors[] = "Nu s-a găsit cap de familie pentru domeniul $domain";
            continue;
        }
        
        // Creează numele familiei din domeniu
        $family_name = generate_family_name_from_domain($domain, $members);
        
        // Creează familia
        $family_result = $family_manager->create_family($family_name, $head_member['patient']->user_id);
        
        if (!$family_result['success']) {
            $errors[] = "Familia '$family_name': " . $family_result['message'];
            continue;
        }
        
        $family_id = $family_result['data']['family_id'];
        $created++;
        
        echo "<p class='success'>✅ Familia '$family_name' creată cu ID: $family_id</p>";
        
        // Adaugă membrii
        foreach ($members as $member) {
            if ($member['patient']->user_id === $head_member['patient']->user_id) continue;
            
            // Determină rolul
            $role = determine_family_role($member, $head_member, $members);
            
            $add_result = $family_manager->add_family_member($member['patient']->user_id, $family_id, $role);
            
            if ($add_result['success']) {
                $name = trim($member['patient']->first_name . ' ' . $member['patient']->last_name);
                $name = !empty($name) ? $name : $member['patient']->cnp;
                echo "<p class='success'>✅ Membru adăugat: $name (rol: $role) - Email: {$member['patient']->email}</p>";
            } else {
                $errors[] = "Familia '$family_name': " . $add_result['message'];
            }
        }
        
        echo "</div>";
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
 * Previzualizează detectarea familiilor
 */
function preview_family_detection() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>Previzualizare Detectare Familii</h3>";
    
    // Găsește pacienții cu emailuri
    $patients = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as email
        FROM {$wpdb->prefix}clinica_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON p.user_id = um3.user_id AND um3.meta_key = 'email'
        WHERE um3.meta_value IS NOT NULL AND um3.meta_value != ''
        ORDER BY um3.meta_value
    ");
    
    $email_families = array();
    $total_families = 0;
    $total_members = 0;
    
    // Analizează emailurile pentru a găsi familiile
    foreach ($patients as $patient) {
        $email = trim($patient->email);
        if (empty($email)) continue;
        
        $family_info = parse_family_email($email);
        if ($family_info) {
            $family_key = $family_info['domain'];
            if (!isset($email_families[$family_key])) {
                $email_families[$family_key] = array();
            }
            
            $email_families[$family_key][] = array(
                'patient' => $patient,
                'family_info' => $family_info
            );
        }
    }
    
    // Afișează previzualizarea
    foreach ($email_families as $domain => $members) {
        if (count($members) < 2) continue; // Doar familiile cu mai mulți membri
        
        $total_families++;
        $total_members += count($members);
        
        echo "<div class='family-group'>";
        echo "<h4>🏠 Familia din domeniul: <strong>$domain</strong> (" . count($members) . " membri)</h4>";
        
        // Sortează membrii
        usort($members, function($a, $b) {
            $a_has_parent = !empty($a['family_info']['parent']);
            $b_has_parent = !empty($b['family_info']['parent']);
            
            if ($a_has_parent && !$b_has_parent) return -1;
            if (!$a_has_parent && $b_has_parent) return 1;
            
            return strcmp($a['patient']->first_name, $b['patient']->first_name);
        });
        
        echo "<ul>";
        foreach ($members as $member) {
            $name = trim($member['patient']->first_name . ' ' . $member['patient']->last_name);
            $name = !empty($name) ? $name : $member['patient']->cnp;
            
            $role_info = "";
            if (!empty($member['family_info']['parent'])) {
                $role_info = " (Părinte: {$member['family_info']['parent']})";
            } elseif (!empty($member['family_info']['child'])) {
                $role_info = " (Copil: {$member['family_info']['child']})";
            }
            
            echo "<li><strong>$name</strong> - {$member['patient']->email}$role_info</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h4>Rezumat Previzualizare:</h4>";
    echo "<p class='info'>📊 Familii detectate: <strong>$total_families</strong></p>";
    echo "<p class='info'>👨‍👩‍👧‍👦 Membri totali: <strong>$total_members</strong></p>";
    
    if ($total_families > 0) {
        echo "<p class='success'>✅ Gata pentru creare! Click pe 'Creează Familiile Detectate' pentru a continua.</p>";
    } else {
        echo "<p class='warning'>⚠️ Nu s-au detectat familii cu emailuri de tip parinte+copil@email.com</p>";
    }
    
    echo "</div>";
}

/**
 * Parsează emailul pentru a extrage informații despre familie
 */
function parse_family_email($email) {
    // Pattern pentru emailuri de tip parinte+copil@domain.com
    $patterns = array(
        // parinte+copil@domain.com
        '/^([^+]+)\+([^@]+)@(.+)$/',
        // parinte.copil@domain.com
        '/^([^.]+)\.([^@]+)@(.+)$/',
        // parinte_copil@domain.com
        '/^([^_]+)_([^@]+)@(.+)$/',
        // parinte-copil@domain.com
        '/^([^-]+)-([^@]+)@(.+)$/'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $email, $matches)) {
            return array(
                'parent' => trim($matches[1]),
                'child' => trim($matches[2]),
                'domain' => trim($matches[3]),
                'full_email' => $email
            );
        }
    }
    
    return null;
}

/**
 * Generează numele familiei din domeniu și membri
 */
function generate_family_name_from_domain($domain, $members) {
    // Încearcă să găsești un nume de familie comun
    $last_names = array();
    foreach ($members as $member) {
        if (!empty($member['patient']->last_name)) {
            $last_names[] = trim($member['patient']->last_name);
        }
    }
    
    if (!empty($last_names)) {
        // Găsește cel mai comun nume de familie
        $last_name_counts = array_count_values($last_names);
        arsort($last_name_counts);
        $most_common_last_name = array_keys($last_name_counts)[0];
        
        return "Familia $most_common_last_name";
    }
    
    // Dacă nu găsești nume de familie, folosește domeniul
    $domain_parts = explode('.', $domain);
    $domain_name = ucfirst($domain_parts[0]);
    
    return "Familia $domain_name";
}

/**
 * Determină rolul în familie
 */
function determine_family_role($member, $head_member, $all_members) {
    // Dacă membru are informații despre părinte, este copil
    if (!empty($member['family_info']['parent'])) {
        return 'child';
    }
    
    // Dacă membru are informații despre copil, este părinte
    if (!empty($member['family_info']['child'])) {
        // Verifică dacă este primul părinte (capul familiei)
        if ($member['patient']->user_id === $head_member['patient']->user_id) {
            return 'head';
        }
        
        // Verifică dacă există alt părinte cu același copil
        foreach ($all_members as $other_member) {
            if ($other_member['patient']->user_id !== $member['patient']->user_id &&
                !empty($other_member['family_info']['child']) &&
                $other_member['family_info']['child'] === $member['family_info']['child']) {
                return 'spouse';
            }
        }
        
        return 'head';
    }
    
    // Dacă nu are informații specifice, determină după poziție
    $member_index = array_search($member, $all_members);
    if ($member_index === 0) {
        return 'head';
    } elseif ($member_index === 1) {
        return 'spouse';
    } else {
        return 'child';
    }
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
    
    // Statistici emailuri
    $patients_with_email = $wpdb->get_var("
        SELECT COUNT(*) FROM $table_patients p
        JOIN {$wpdb->usermeta} um ON p.user_id = um.user_id 
        WHERE um.meta_key = 'email' AND um.meta_value IS NOT NULL AND um.meta_value != ''
    ");
    
    echo "<div class='section'>";
    echo "<h2>Statistici Curente</h2>";
    echo "<p class='info'>📊 Total pacienți: <strong>$total_patients</strong></p>";
    echo "<p class='info'>📧 Pacienți cu email: <strong>$patients_with_email</strong></p>";
    echo "<p class='info'>👨‍👩‍👧‍👦 Pacienți cu familie: <strong>$patients_with_family</strong></p>";
    echo "<p class='info'>🏠 Total familii: <strong>$families_count</strong></p>";
    
    // Exemple de emailuri detectate
    $sample_emails = $wpdb->get_results("
        SELECT um.meta_value as email
        FROM $table_patients p
        JOIN {$wpdb->usermeta} um ON p.user_id = um.user_id 
        WHERE um.meta_key = 'email' AND um.meta_value IS NOT NULL AND um.meta_value != ''
        AND (um.meta_value LIKE '%+%' OR um.meta_value LIKE '%.%' OR um.meta_value LIKE '%_%' OR um.meta_value LIKE '%-%')
        LIMIT 10
    ");
    
    if ($sample_emails) {
        echo "<h4>Exemple Emailuri Detectate:</h4>";
        echo "<div class='email-preview'>";
        foreach ($sample_emails as $email) {
            $family_info = parse_family_email($email->email);
            if ($family_info) {
                echo "<p class='success'>✅ {$email->email} → Părinte: {$family_info['parent']}, Copil: {$family_info['child']}</p>";
            } else {
                echo "<p class='info'>ℹ️ {$email->email} → Nu se potrivește cu pattern-ul</p>";
            }
        }
        echo "</div>";
    }
    
    echo "</div>";
}
?> 