<?php
/**
 * Import din Joomla
 * Script pentru integrarea utilizatorilor migrați din Joomla în sistemul nostru de clinici
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Import din Joomla</h1>";
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
.user-preview { margin: 5px 0; padding: 5px; border: 1px solid #eee; border-radius: 3px; background: #fafafa; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
.stat-card { padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: center; }
.stat-number { font-size: 2em; font-weight: bold; color: #0073aa; }
.stat-label { color: #666; margin-top: 5px; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'scan_joomla_users':
            scan_joomla_users();
            break;
        case 'import_joomla_users':
            import_joomla_users();
            break;
        case 'detect_families':
            detect_families_from_joomla();
            break;
        case 'cleanup_joomla_data':
            cleanup_joomla_data();
            break;
    }
}

// Afișează statisticile
display_joomla_stats();

// Formulare de acțiune
echo "<div class='section'>";
echo "<h2>Integrare Joomla → WordPress Clinici</h2>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='scan_joomla_users'>";
echo "<button type='submit' class='btn'>🔍 Scanează Utilizatori Joomla</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='import_joomla_users'>";
echo "<button type='submit' class='btn'>👥 Import Utilizatori Joomla</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='detect_families'>";
echo "<button type='submit' class='btn'>🏠 Detectează Familii</button>";
echo "</form>";

echo "<form method='post' style='display:inline;' onsubmit='return confirm(\"Sigur vrei să ștergi datele Joomla?\")'>";
echo "<input type='hidden' name='action' value='cleanup_joomla_data'>";
echo "<button type='submit' class='btn' style='background:#dc3232;'>🗑️ Șterge Date Joomla</button>";
echo "</form>";

echo "</div>";

/**
 * Scanează utilizatorii migrați din Joomla
 */
function scan_joomla_users() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>🔍 Scanare Utilizatori Joomla</h3>";
    
    // Verifică dacă pluginul FG Joomla to WordPress este activ
    if (!class_exists('FG_Joomla_to_WordPress_Users')) {
        echo "<p class='error'>❌ Pluginul FG Joomla to WordPress nu este activ!</p>";
        echo "<p class='info'>💡 Activează pluginul din <a href='" . admin_url('plugins.php') . "'>Administrare → Pluginuri</a></p>";
        echo "</div>";
        return;
    }
    
    // Caută utilizatori cu meta-uri Joomla
    $joomla_users = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               um3.meta_value as joomla_id,
               um4.meta_value as joomla_username,
               um5.meta_value as joomla_block,
               um6.meta_value as joomla_activation,
               um7.meta_value as joomla_params
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'joomla_id'
        LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'joomla_username'
        LEFT JOIN {$wpdb->usermeta} um5 ON u.ID = um5.user_id AND um5.meta_key = 'joomla_block'
        LEFT JOIN {$wpdb->usermeta} um6 ON u.ID = um6.user_id AND um6.meta_key = 'joomla_activation'
        LEFT JOIN {$wpdb->usermeta} um7 ON u.ID = um7.user_id AND um7.meta_key = 'joomla_params'
        WHERE um3.meta_value IS NOT NULL OR um4.meta_value IS NOT NULL
        ORDER BY u.user_registered DESC
    ");
    
    if (empty($joomla_users)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit utilizatori migrați din Joomla!</p>";
        echo "<p class='info'>💡 Asigură-te că:</p>";
        echo "<ul>";
        echo "<li>Pluginul FG Joomla to WordPress este activ</li>";
        echo "<li>Migrarea din Joomla a fost finalizată</li>";
        echo "<li>Utilizatorii au fost importați cu succes</li>";
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ S-au găsit <strong>" . count($joomla_users) . "</strong> utilizatori migrați din Joomla!</p>";
        
        echo "<h4>Utilizatori Joomla Găsiți:</h4>";
        echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;'>";
        
        foreach ($joomla_users as $user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            $name = !empty($name) ? $name : $user->display_name;
            
            echo "<div class='user-preview'>";
            echo "<p><strong>$name</strong> (ID: {$user->ID})</p>";
            echo "<p class='info'>📧 Email: {$user->user_email} | 👤 Username: {$user->user_login}</p>";
            
            if ($user->joomla_id) {
                echo "<p class='info'>🆔 Joomla ID: {$user->joomla_id}</p>";
            }
            
            if ($user->joomla_username) {
                echo "<p class='info'>👤 Joomla Username: {$user->joomla_username}</p>";
            }
            
            // Verifică dacă utilizatorul este deja în tabela pacienți
            $existing_patient = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d",
                $user->ID
            ));
            
            if ($existing_patient) {
                echo "<p class='success'>✅ Deja în tabela pacienți</p>";
            } else {
                echo "<p class='warning'>⚠️ Nu este în tabela pacienți</p>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
        
        // Statistici
        $users_with_patients = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} u
            JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'joomla_id'
        ");
        
        $users_without_patients = count($joomla_users) - $users_with_patients;
        
        echo "<div class='stats-grid'>";
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>" . count($joomla_users) . "</div>";
        echo "<div class='stat-label'>Total Utilizatori Joomla</div>";
        echo "</div>";
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>$users_with_patients</div>";
        echo "<div class='stat-label'>Cu Înregistrare Pacient</div>";
        echo "</div>";
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>$users_without_patients</div>";
        echo "<div class='stat-label'>Fără Înregistrare Pacient</div>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
}

/**
 * Import utilizatori Joomla în tabela pacienți
 */
function import_joomla_users() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>👥 Import Utilizatori Joomla</h3>";
    
    // Găsește utilizatorii Joomla fără înregistrare în tabela pacienți
    $joomla_users_without_patients = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               um3.meta_value as joomla_id,
               um4.meta_value as joomla_username,
               um5.meta_value as joomla_block,
               um6.meta_value as joomla_activation,
               um7.meta_value as joomla_params
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'joomla_id'
        LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'joomla_username'
        LEFT JOIN {$wpdb->usermeta} um5 ON u.ID = um5.user_id AND um5.meta_key = 'joomla_block'
        LEFT JOIN {$wpdb->usermeta} um6 ON u.ID = um6.user_id AND um6.meta_key = 'joomla_activation'
        LEFT JOIN {$wpdb->usermeta} um7 ON u.ID = um7.user_id AND um7.meta_key = 'joomla_params'
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
        WHERE (um3.meta_value IS NOT NULL OR um4.meta_value IS NOT NULL)
        AND p.user_id IS NULL
        ORDER BY u.user_registered DESC
    ");
    
    if (empty($joomla_users_without_patients)) {
        echo "<p class='info'>ℹ️ Toți utilizatorii Joomla au deja înregistrări în tabela pacienți!</p>";
        echo "</div>";
        return;
    }
    
    echo "<p class='info'>📋 S-au găsit <strong>" . count($joomla_users_without_patients) . "</strong> utilizatori Joomla fără înregistrare în tabela pacienți.</p>";
    
    $imported = 0;
    $errors = array();
    
    foreach ($joomla_users_without_patients as $user) {
        $name = trim($user->first_name . ' ' . $user->last_name);
        $name = !empty($name) ? $name : $user->display_name;
        
        // Generează CNP temporar dacă nu există
        $cnp = generate_temp_cnp($user->ID, $user->user_registered);
        
        // Extrage informații din joomla_params dacă există
        $joomla_data = array();
        if ($user->joomla_params) {
            $joomla_data = json_decode($user->joomla_params, true);
        }
        
        // Creează înregistrarea în tabela pacienți
        $patient_data = array(
            'user_id' => $user->ID,
            'cnp' => $cnp,
            'phone_primary' => isset($joomla_data['phone']) ? $joomla_data['phone'] : '',
            'address' => isset($joomla_data['address']) ? $joomla_data['address'] : '',
            'birth_date' => isset($joomla_data['birth_date']) ? $joomla_data['birth_date'] : null,
            'gender' => isset($joomla_data['gender']) ? $joomla_data['gender'] : null,
            'created_by' => get_current_user_id(),
            'import_source' => 'joomla_migration',
            'joomla_id' => $user->joomla_id,
            'joomla_username' => $user->joomla_username
        );
        
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'clinica_patients',
            $patient_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($insert_result === false) {
            $errors[] = "Eroare la crearea înregistrării pacient pentru {$user->user_email}: " . $wpdb->last_error;
        } else {
            $imported++;
            echo "<div class='user-preview'>";
            echo "<p class='success'>✅ Pacient creat: <strong>$name</strong> (ID: {$user->ID})</p>";
            echo "<p class='info'>📧 Email: {$user->user_email} | 🆔 CNP: $cnp</p>";
            if ($user->joomla_id) {
                echo "<p class='info'>🆔 Joomla ID: {$user->joomla_id}</p>";
            }
            echo "</div>";
        }
    }
    
    echo "<h4>Rezumat Import:</h4>";
    echo "<p class='success'>✅ Pacienți creați: $imported</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    if ($imported > 0) {
        echo "<h4>🎯 Următorii Pași:</h4>";
        echo "<ol>";
        echo "<li><strong>Detectează familiile:</strong> Click pe 'Detectează Familii' pentru a găsi relațiile familiale</li>";
        echo "<li><strong>Verifică autosuggest:</strong> Testează funcționalitatea de căutare pe pagina de pacienți</li>";
        echo "<li><strong>Actualizează CNP-urile:</strong> Dacă ai CNP-urile reale, actualizează-le manual</li>";
        echo "</ol>";
    }
    
    echo "</div>";
}

/**
 * Detectează familiile din utilizatorii Joomla
 */
function detect_families_from_joomla() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>🏠 Detectare Familii din Joomla</h3>";
    
    // Găsește utilizatorii Joomla cu emailuri
    $joomla_users = $wpdb->get_results("
        SELECT u.ID, u.user_email, u.display_name,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               p.cnp, p.phone_primary, p.address
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
        WHERE (um1.meta_value IS NOT NULL OR um2.meta_value IS NOT NULL)
        AND u.user_email IS NOT NULL AND u.user_email != ''
        ORDER BY um2.meta_value, um1.meta_value
    ");
    
    if (empty($joomla_users)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit utilizatori Joomla cu emailuri!</p>";
        echo "</div>";
        return;
    }
    
    echo "<p class='info'>📋 S-au găsit <strong>" . count($joomla_users) . "</strong> utilizatori Joomla cu emailuri.</p>";
    
    // Grupează după nume de familie
    $families = array();
    foreach ($joomla_users as $user) {
        $last_name = $user->last_name ?: 'Necunoscut';
        if (!isset($families[$last_name])) {
            $families[$last_name] = array();
        }
        $families[$last_name][] = $user;
    }
    
    // Filtrează familiile cu mai mulți membri
    $families_with_multiple = array_filter($families, function($members) {
        return count($members) > 1;
    });
    
    if (empty($families_with_multiple)) {
        echo "<p class='info'>ℹ️ Nu s-au găsit familii cu mai mulți membri!</p>";
        echo "</div>";
        return;
    }
    
    echo "<p class='success'>✅ S-au găsit <strong>" . count($families_with_multiple) . "</strong> familii cu mai mulți membri!</p>";
    
    // Afișează familiile detectate
    echo "<h4>Familii Detectate:</h4>";
    foreach ($families_with_multiple as $family_name => $members) {
        echo "<div class='user-preview'>";
        echo "<h5>🏠 Familia <strong>$family_name</strong> (" . count($members) . " membri)</h5>";
        
        foreach ($members as $member) {
            $name = trim($member->first_name . ' ' . $member->last_name);
            $name = !empty($name) ? $name : $member->display_name;
            
            echo "<p class='info'>👤 <strong>$name</strong> - {$member->user_email}";
            if ($member->cnp) {
                echo " (CNP: {$member->cnp})";
            }
            echo "</p>";
        }
        echo "</div>";
    }
    
    // Oferă opțiunea de a crea familiile
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='create_joomla_families'>";
    echo "<button type='submit' class='btn'>🏠 Creează Familiile Detectate</button>";
    echo "</form>";
    
    echo "</div>";
}

/**
 * Generează un CNP temporar
 */
function generate_temp_cnp($user_id, $registration_date) {
    // Folosește ID-ul utilizatorului și data înregistrării pentru a genera un CNP temporar
    $year = date('y', strtotime($registration_date));
    $month = date('m', strtotime($registration_date));
    $day = date('d', strtotime($registration_date));
    
    // Generează un număr unic bazat pe ID-ul utilizatorului
    $unique_number = str_pad($user_id, 6, '0', STR_PAD_LEFT);
    
    // Construiește CNP-ul temporar (format: AAMMDDCCCCCC)
    $temp_cnp = $year . $month . $day . $unique_number;
    
    return $temp_cnp;
}

/**
 * Șterge datele Joomla
 */
function cleanup_joomla_data() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>🗑️ Ștergere Date Joomla</h3>";
    
    // Șterge meta-urile Joomla
    $deleted_meta = $wpdb->query("
        DELETE FROM {$wpdb->usermeta} 
        WHERE meta_key IN ('joomla_id', 'joomla_username', 'joomla_block', 'joomla_activation', 'joomla_params')
    ");
    
    // Șterge înregistrările din tabela pacienți cu sursa joomla_migration
    $deleted_patients = $wpdb->delete(
        $wpdb->prefix . 'clinica_patients',
        array('import_source' => 'joomla_migration'),
        array('%s')
    );
    
    echo "<p class='success'>✅ Meta-uri Joomla șterse: $deleted_meta</p>";
    echo "<p class='success'>✅ Înregistrări pacienți șterse: $deleted_patients</p>";
    
    echo "</div>";
}

/**
 * Afișează statisticile Joomla
 */
function display_joomla_stats() {
    global $wpdb;
    
    // Statistici generale
    $total_users = count_users()['total_users'];
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients");
    
    // Utilizatori Joomla
    $joomla_users = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
        WHERE um.meta_key = 'joomla_id' AND um.meta_value IS NOT NULL
    ");
    
    $joomla_patients = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE import_source = %s",
        'joomla_migration'
    ));
    
    echo "<div class='section'>";
    echo "<h2>Statistici Joomla</h2>";
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$total_users</div>";
    echo "<div class='stat-label'>Total Utilizatori WordPress</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$total_patients</div>";
    echo "<div class='stat-label'>Total Pacienți</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$joomla_users</div>";
    echo "<div class='stat-label'>Utilizatori Joomla</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$joomla_patients</div>";
    echo "<div class='stat-label'>Pacienți Joomla</div>";
    echo "</div>";
    echo "</div>";
    
    if ($joomla_users > 0) {
        echo "<h4>🔗 Link-uri Utile:</h4>";
        echo "<ul>";
        echo "<li><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Pagina Pacienți</a></li>";
        echo "<li><a href='import-families-from-emails.php'>Import Familii din Emailuri</a></li>";
        echo "<li><a href='test-import-users.php'>Test Import Utilizatori</a></li>";
        echo "</ul>";
    }
    
    echo "</div>";
}
?> 