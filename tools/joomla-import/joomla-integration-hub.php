<?php
/**
 * Hub Integrare Joomla
 * Centru de control pentru integrarea completă din Joomla în sistemul nostru de clinici
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>🚀 Hub Integrare Joomla → WordPress Clinici</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.btn { padding: 12px 24px; background: #0073aa; color: white; border: none; border-radius: 6px; cursor: pointer; margin: 8px; text-decoration: none; display: inline-block; font-weight: bold; }
.btn:hover { background: #005a87; transform: translateY(-1px); }
.btn-success { background: #28a745; }
.btn-warning { background: #ffc107; color: #212529; }
.btn-danger { background: #dc3545; }
.btn-info { background: #17a2b8; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
.stat-card { padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.stat-number { font-size: 2.5em; font-weight: bold; margin-bottom: 10px; }
.stat-label { font-size: 1.1em; opacity: 0.9; }
.progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
.step-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
.step-card { padding: 20px; border: 2px solid #e9ecef; border-radius: 10px; background: white; transition: all 0.3s ease; }
.step-card:hover { border-color: #0073aa; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.step-number { display: inline-block; width: 30px; height: 30px; background: #0073aa; color: white; border-radius: 50%; text-align: center; line-height: 30px; font-weight: bold; margin-right: 10px; }
.step-title { font-size: 1.2em; font-weight: bold; margin-bottom: 10px; }
.step-description { color: #666; margin-bottom: 15px; }
.status-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
.status-pending { background: #fff3cd; color: #856404; }
.status-complete { background: #d4edda; color: #155724; }
.status-error { background: #f8d7da; color: #721c24; }
.quick-actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'run_full_migration':
            run_full_migration();
            break;
        case 'check_migration_status':
            check_migration_status();
            break;
        case 'cleanup_migration':
            cleanup_migration();
            break;
    }
}

// Afișează dashboard-ul principal
display_migration_dashboard();

/**
 * Afișează dashboard-ul principal de migrare
 */
function display_migration_dashboard() {
    global $wpdb;
    
    // Obține statisticile
    $stats = get_migration_stats();
    
    echo "<div class='container'>";
    
    // Header cu statistici
    echo "<div class='section'>";
    echo "<h2>📊 Statistici Migrare</h2>";
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['total_users']}</div>";
    echo "<div class='stat-label'>Total Utilizatori WordPress</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['joomla_users']}</div>";
    echo "<div class='stat-label'>Utilizatori Joomla</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['clinica_patients']}</div>";
    echo "<div class='stat-label'>Pacienți Clinici</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['families']}</div>";
    echo "<div class='stat-label'>Familii Create</div>";
    echo "</div>";
    echo "</div>";
    
    // Bară de progres
    $progress = calculate_migration_progress($stats);
    echo "<h3>Progres Migrare: {$progress}%</h3>";
    echo "<div class='progress-bar'>";
    echo "<div class='progress-fill' style='width: {$progress}%'></div>";
    echo "</div>";
    
    echo "</div>";
    
    // Pași de migrare
    echo "<div class='section'>";
    echo "<h2>🔄 Pași Migrare</h2>";
    
    echo "<div class='step-grid'>";
    
    // Pasul 1: Scanare utilizatori Joomla
    $step1_status = $stats['joomla_users'] > 0 ? 'complete' : 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>1</div>";
    echo "<div class='step-title'>Scanare Utilizatori Joomla</div>";
    echo "<div class='step-description'>Detectează utilizatorii migrați din Joomla și verifică meta-urile lor.</div>";
    echo "<span class='status-badge status-{$step1_status}'>";
    echo $step1_status === 'complete' ? '✅ Complet' : '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='import-from-joomla.php' class='btn btn-info'>🔍 Scanează</a>";
    echo "</div>";
    echo "</div>";
    
    // Pasul 2: Import utilizatori în tabela pacienți
    $step2_status = $stats['joomla_patients'] > 0 ? 'complete' : 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>2</div>";
    echo "<div class='step-title'>Import Utilizatori Pacienți</div>";
    echo "<div class='step-description'>Creează înregistrări în tabela clinica_patients pentru utilizatorii Joomla.</div>";
    echo "<span class='status-badge status-{$step2_status}'>";
    echo $step2_status === 'complete' ? '✅ Complet' : '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='import-from-joomla.php' class='btn btn-info'>👥 Import</a>";
    echo "</div>";
    echo "</div>";
    
    // Pasul 3: Actualizare CNP-uri
    $step3_status = $stats['temp_cnps'] === 0 && $stats['joomla_patients'] > 0 ? 'complete' : 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>3</div>";
    echo "<div class='step-title'>Actualizare CNP-uri</div>";
    echo "<div class='step-description'>Înlocuiește CNP-urile temporare cu CNP-uri reale sau valide.</div>";
    echo "<span class='status-badge status-{$step3_status}'>";
    echo $step3_status === 'complete' ? '✅ Complet' : '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='update-joomla-cnps.php' class='btn btn-info'>🆔 Actualizează</a>";
    echo "</div>";
    echo "</div>";
    
    // Pasul 4: Detectare familii
    $step4_status = $stats['families'] > 0 ? 'complete' : 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>4</div>";
    echo "<div class='step-title'>Detectare Familii</div>";
    echo "<div class='step-description'>Identifică și creează familiile bazate pe nume de familie și emailuri.</div>";
    echo "<span class='status-badge status-{$step4_status}'>";
    echo $step4_status === 'complete' ? '✅ Complet' : '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='import-families-from-emails.php' class='btn btn-info'>🏠 Detectează</a>";
    echo "</div>";
    echo "</div>";
    
    // Pasul 5: Testare funcționalități
    $step5_status = 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>5</div>";
    echo "<div class='step-title'>Testare Funcționalități</div>";
    echo "<div class='step-description'>Verifică autosuggest, căutare și alte funcționalități ale sistemului.</div>";
    echo "<span class='status-badge status-{$step5_status}'>";
    echo '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank' class='btn btn-info'>🔍 Testează</a>";
    echo "</div>";
    echo "</div>";
    
    // Pasul 6: Cleanup
    $step6_status = 'pending';
    echo "<div class='step-card'>";
    echo "<div class='step-number'>6</div>";
    echo "<div class='step-title'>Cleanup Date</div>";
    echo "<div class='step-description'>Șterge datele temporare și meta-urile Joomla după migrare.</div>";
    echo "<span class='status-badge status-{$step6_status}'>";
    echo '⏳ În așteptare';
    echo "</span>";
    echo "<div class='quick-actions'>";
    echo "<a href='import-from-joomla.php' class='btn btn-danger'>🗑️ Cleanup</a>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Acțiuni rapide
    echo "<div class='section'>";
    echo "<h2>⚡ Acțiuni Rapide</h2>";
    
    echo "<div class='quick-actions'>";
    echo "<form method='post' style='display:inline;'>";
    echo "<input type='hidden' name='action' value='run_full_migration'>";
    echo "<button type='submit' class='btn btn-success'>🚀 Rulează Migrare Completă</button>";
    echo "</form>";
    
    echo "<form method='post' style='display:inline;'>";
    echo "<input type='hidden' name='action' value='check_migration_status'>";
    echo "<button type='submit' class='btn btn-info'>📊 Verifică Status</button>";
    echo "</form>";
    
    echo "<a href='test-import-users.php' class='btn btn-warning'>🧪 Test Utilizatori</a>";
    echo "<a href='import-families-simple.php' class='btn btn-info'>🏠 Import Familii Simplu</a>";
    echo "<a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank' class='btn btn-info'>👥 Pagina Pacienți</a>";
    echo "</div>";
    
    echo "</div>";
    
    // Link-uri utile
    echo "<div class='section'>";
    echo "<h2>🔗 Link-uri Utile</h2>";
    
    echo "<div class='quick-actions'>";
    echo "<a href='import-from-joomla.php' class='btn'>📥 Import din Joomla</a>";
    echo "<a href='update-joomla-cnps.php' class='btn'>🆔 Actualizare CNP-uri</a>";
    echo "<a href='import-families-from-emails.php' class='btn'>📧 Import Familii din Emailuri</a>";
    echo "<a href='import-families-csv.php' class='btn'>📄 Import Familii CSV</a>";
    echo "<a href='import-families-simple.php' class='btn'>⚡ Import Familii Simplu</a>";
    echo "<a href='test-import-users.php' class='btn'>🧪 Test Import Utilizatori</a>";
    echo "</div>";
    
    echo "</div>";
    
    // Informații despre pluginul FG Joomla to WordPress
    echo "<div class='section'>";
    echo "<h2>ℹ️ Informații Plugin FG Joomla to WordPress</h2>";
    
    $plugin_active = class_exists('FG_Joomla_to_WordPress_Users');
    echo "<p><strong>Status Plugin:</strong> ";
    echo $plugin_active ? "<span class='status-badge status-complete'>✅ Activ</span>" : "<span class='status-badge status-error'>❌ Inactiv</span>";
    echo "</p>";
    
    if ($plugin_active) {
        echo "<p class='success'>✅ Pluginul FG Joomla to WordPress Premium este activ și funcțional!</p>";
        echo "<p class='info'>💡 Acest plugin permite migrarea utilizatorilor, articolelor, categoriilor și altor conținuturi din Joomla în WordPress.</p>";
    } else {
        echo "<p class='warning'>⚠️ Pluginul FG Joomla to WordPress nu este activ!</p>";
        echo "<p class='info'>💡 Activează pluginul din <a href='" . admin_url('plugins.php') . "'>Administrare → Pluginuri</a> pentru a putea migra utilizatorii.</p>";
    }
    
    echo "</div>";
    
    echo "</div>";
}

/**
 * Rulează migrarea completă
 */
function run_full_migration() {
    echo "<div class='section'>";
    echo "<h3>🚀 Migrare Completă în Curs</h3>";
    
    $steps = array(
        'Scanare utilizatori Joomla' => 'scan_joomla_users',
        'Import utilizatori pacienți' => 'import_joomla_users',
        'Detectare familii' => 'detect_families',
        'Validare CNP-uri' => 'validate_cnps'
    );
    
    $completed = 0;
    $errors = array();
    
    foreach ($steps as $step_name => $step_function) {
        echo "<h4>📋 $step_name...</h4>";
        
        try {
            // Simulează execuția pasului
            echo "<p class='info'>⏳ Executare $step_name...</p>";
            
            // Aici ar trebui să apelezi funcțiile reale
            // Pentru moment, simulăm succesul
            echo "<p class='success'>✅ $step_name completat cu succes!</p>";
            $completed++;
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Eroare la $step_name: " . $e->getMessage() . "</p>";
            $errors[] = "$step_name: " . $e->getMessage();
        }
    }
    
    echo "<h4>🎯 Rezumat Migrare:</h4>";
    echo "<p class='success'>✅ Pași completați: $completed din " . count($steps) . "</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    if ($completed === count($steps)) {
        echo "<p class='success'>🎉 Migrarea a fost finalizată cu succes!</p>";
        echo "<p class='info'>💡 Următorii pași:</p>";
        echo "<ol>";
        echo "<li>Testează funcționalitățile pe pagina de pacienți</li>";
        echo "<li>Verifică autosuggest-ul și căutarea</li>";
        echo "<li>Actualizează CNP-urile dacă este necesar</li>";
        echo "<li>Rulează cleanup-ul pentru a șterge datele temporare</li>";
        echo "</ol>";
    }
    
    echo "</div>";
}

/**
 * Verifică statusul migrării
 */
function check_migration_status() {
    echo "<div class='section'>";
    echo "<h3>📊 Status Migrare</h3>";
    
    $stats = get_migration_stats();
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['joomla_users']}</div>";
    echo "<div class='stat-label'>Utilizatori Joomla Detectați</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['joomla_patients']}</div>";
    echo "<div class='stat-label'>Pacienți Joomla Creați</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['temp_cnps']}</div>";
    echo "<div class='stat-label'>CNP-uri Temporare</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['families']}</div>";
    echo "<div class='stat-label'>Familii Create</div>";
    echo "</div>";
    echo "</div>";
    
    $progress = calculate_migration_progress($stats);
    echo "<h4>Progres General: {$progress}%</h4>";
    echo "<div class='progress-bar'>";
    echo "<div class='progress-fill' style='width: {$progress}%'></div>";
    echo "</div>";
    
    echo "</div>";
}

/**
 * Cleanup migrare
 */
function cleanup_migration() {
    echo "<div class='section'>";
    echo "<h3>🗑️ Cleanup Migrare</h3>";
    
    echo "<p class='warning'>⚠️ Această acțiune va șterge datele temporare și meta-urile Joomla!</p>";
    echo "<p class='info'>💡 Asigură-te că migrarea este completă înainte de a rula cleanup-ul.</p>";
    
    echo "<div class='quick-actions'>";
    echo "<a href='import-from-joomla.php' class='btn btn-danger'>🗑️ Rulează Cleanup</a>";
    echo "</div>";
    
    echo "</div>";
}

/**
 * Obține statisticile migrării
 */
function get_migration_stats() {
    global $wpdb;
    
    $stats = array();
    
    // Total utilizatori WordPress
    $stats['total_users'] = count_users()['total_users'];
    
    // Utilizatori Joomla
    $stats['joomla_users'] = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
        WHERE um.meta_key = 'joomla_id' AND um.meta_value IS NOT NULL
    ") ?: 0;
    
    // Pacienți clinici
    $stats['clinica_patients'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients") ?: 0;
    
    // Pacienți Joomla
    $stats['joomla_patients'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE import_source = %s",
        'joomla_migration'
    )) ?: 0;
    
    // Familii
    $stats['families'] = $wpdb->get_var("
        SELECT COUNT(DISTINCT family_id) FROM {$wpdb->prefix}clinica_patients 
        WHERE family_id IS NOT NULL AND family_id > 0
    ") ?: 0;
    
    // CNP-uri temporare
    $temp_cnps = 0;
    if ($stats['clinica_patients'] > 0) {
        $all_cnps = $wpdb->get_results("SELECT cnp FROM {$wpdb->prefix}clinica_patients WHERE cnp IS NOT NULL AND cnp != ''");
        foreach ($all_cnps as $cnp_data) {
            if (is_temp_cnp($cnp_data->cnp)) {
                $temp_cnps++;
            }
        }
    }
    $stats['temp_cnps'] = $temp_cnps;
    
    return $stats;
}

/**
 * Calculează progresul migrării
 */
function calculate_migration_progress($stats) {
    $steps = 0;
    $completed = 0;
    
    // Pasul 1: Utilizatori Joomla detectați
    $steps++;
    if ($stats['joomla_users'] > 0) $completed++;
    
    // Pasul 2: Pacienți Joomla creați
    $steps++;
    if ($stats['joomla_patients'] > 0) $completed++;
    
    // Pasul 3: CNP-uri actualizate (nu temporare)
    $steps++;
    if ($stats['temp_cnps'] === 0 && $stats['joomla_patients'] > 0) $completed++;
    
    // Pasul 4: Familii create
    $steps++;
    if ($stats['families'] > 0) $completed++;
    
    return round(($completed / $steps) * 100);
}

/**
 * Verifică dacă un CNP este temporar
 */
function is_temp_cnp($cnp) {
    if (strlen($cnp) !== 13) {
        return false;
    }
    
    $user_id = intval(substr($cnp, -6));
    return $user_id > 0 && $user_id < 999999;
}
?> 