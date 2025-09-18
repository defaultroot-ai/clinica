<?php
/**
 * Actualizare CNP-uri Joomla
 * Script pentru actualizarea CNP-urilor pentru utilizatorii migrați din Joomla
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Actualizare CNP-uri Joomla</h1>";
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
.form-group { margin: 10px 0; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.cnp-grid { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: center; margin: 10px 0; }
.cnp-grid input { margin: 0; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'scan_temp_cnps':
            scan_temp_cnps();
            break;
        case 'update_cnps':
            update_cnps();
            break;
        case 'validate_cnps':
            validate_cnps();
            break;
        case 'generate_real_cnps':
            generate_real_cnps();
            break;
    }
}

// Afișează statisticile
display_cnp_stats();

// Formulare de acțiune
echo "<div class='section'>";
echo "<h2>Gestionare CNP-uri Joomla</h2>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='scan_temp_cnps'>";
echo "<button type='submit' class='btn'>🔍 Scanează CNP-uri Temporare</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='validate_cnps'>";
echo "<button type='submit' class='btn'>✅ Validează CNP-uri</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='generate_real_cnps'>";
echo "<button type='submit' class='btn'>🎲 Generează CNP-uri Reale</button>";
echo "</form>";

echo "</div>";

/**
 * Scanează CNP-urile temporare
 */
function scan_temp_cnps() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>🔍 Scanare CNP-uri Temporare</h3>";
    
    // Găsește utilizatorii cu CNP-uri temporare
    $temp_cnp_users = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               p.cnp, p.phone_primary, p.address,
               um3.meta_value as joomla_id
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'joomla_id'
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
        WHERE p.cnp IS NOT NULL 
        AND p.cnp != ''
        AND p.import_source = 'joomla_migration'
        ORDER BY u.user_registered DESC
    ");
    
    if (empty($temp_cnp_users)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit utilizatori cu CNP-uri temporare!</p>";
        echo "</div>";
        return;
    }
    
    echo "<p class='info'>📋 S-au găsit <strong>" . count($temp_cnp_users) . "</strong> utilizatori cu CNP-uri temporare.</p>";
    
    // Analizează CNP-urile
    $temp_cnps = 0;
    $valid_cnps = 0;
    $invalid_cnps = 0;
    
    foreach ($temp_cnp_users as $user) {
        if (is_temp_cnp($user->cnp)) {
            $temp_cnps++;
        } elseif (is_valid_cnp($user->cnp)) {
            $valid_cnps++;
        } else {
            $invalid_cnps++;
        }
    }
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$temp_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Temporare</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$valid_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Valide</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$invalid_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Invalide</div>";
    echo "</div>";
    echo "</div>";
    
    // Afișează utilizatorii cu CNP-uri temporare
    if ($temp_cnps > 0) {
        echo "<h4>Utilizatori cu CNP-uri Temporare:</h4>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='update_cnps'>";
        
        echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;'>";
        
        foreach ($temp_cnp_users as $user) {
            if (is_temp_cnp($user->cnp)) {
                $name = trim($user->first_name . ' ' . $user->last_name);
                $name = !empty($name) ? $name : $user->display_name;
                
                echo "<div class='user-preview'>";
                echo "<div class='cnp-grid'>";
                echo "<div>";
                echo "<strong>$name</strong><br>";
                echo "<small>{$user->user_email}</small>";
                echo "</div>";
                echo "<div>";
                echo "<label>CNP Actual:</label>";
                echo "<input type='text' value='{$user->cnp}' readonly style='background:#f0f0f0;'>";
                echo "</div>";
                echo "<div>";
                echo "<label>CNP Nou:</label>";
                echo "<input type='text' name='cnp_new[{$user->ID}]' placeholder='CNP real (13 cifre)' maxlength='13' pattern='[0-9]{13}'>";
                echo "</div>";
                echo "<div>";
                echo "<button type='button' onclick='generateCNP({$user->ID})' class='btn' style='padding:5px 10px;'>🎲 Generează</button>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        
        echo "<button type='submit' class='btn'>💾 Actualizează CNP-urile</button>";
        echo "</form>";
    }
    
    echo "</div>";
}

/**
 * Actualizează CNP-urile
 */
function update_cnps() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>💾 Actualizare CNP-uri</h3>";
    
    if (!isset($_POST['cnp_new']) || empty($_POST['cnp_new'])) {
        echo "<p class='error'>❌ Nu s-au furnizat CNP-uri noi!</p>";
        echo "</div>";
        return;
    }
    
    $updated = 0;
    $errors = array();
    $validations = array();
    
    foreach ($_POST['cnp_new'] as $user_id => $new_cnp) {
        $new_cnp = trim($new_cnp);
        
        if (empty($new_cnp)) {
            continue; // Sărim peste câmpurile goale
        }
        
        // Validări
        if (strlen($new_cnp) !== 13) {
            $validations[] = "Utilizatorul $user_id: CNP-ul trebuie să aibă 13 cifre";
            continue;
        }
        
        if (!ctype_digit($new_cnp)) {
            $validations[] = "Utilizatorul $user_id: CNP-ul trebuie să conțină doar cifre";
            continue;
        }
        
        if (!is_valid_cnp($new_cnp)) {
            $validations[] = "Utilizatorul $user_id: CNP-ul nu este valid";
            continue;
        }
        
        // Verifică dacă CNP-ul există deja
        $existing_cnp = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s AND user_id != %d",
            $new_cnp, $user_id
        ));
        
        if ($existing_cnp) {
            $validations[] = "Utilizatorul $user_id: CNP-ul $new_cnp există deja pentru utilizatorul $existing_cnp";
            continue;
        }
        
        // Actualizează CNP-ul
        $update_result = $wpdb->update(
            $wpdb->prefix . 'clinica_patients',
            array('cnp' => $new_cnp),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            $errors[] = "Eroare la actualizarea CNP-ului pentru utilizatorul $user_id: " . $wpdb->last_error;
        } else {
            $updated++;
            
            // Obține informațiile utilizatorului pentru afișare
            $user_info = $wpdb->get_row($wpdb->prepare("
                SELECT u.display_name, um1.meta_value as first_name, um2.meta_value as last_name
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE u.ID = %d
            ", $user_id));
            
            $name = trim($user_info->first_name . ' ' . $user_info->last_name);
            $name = !empty($name) ? $name : $user_info->display_name;
            
            echo "<div class='user-preview'>";
            echo "<p class='success'>✅ CNP actualizat pentru <strong>$name</strong> (ID: $user_id)</p>";
            echo "<p class='info'>🆔 CNP nou: $new_cnp</p>";
            echo "</div>";
        }
    }
    
    echo "<h4>Rezumat Actualizare:</h4>";
    echo "<p class='success'>✅ CNP-uri actualizate: $updated</p>";
    
    if (!empty($validations)) {
        echo "<h4>Validări eșuate:</h4>";
        foreach ($validations as $validation) {
            echo "<p class='warning'>⚠️ $validation</p>";
        }
    }
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    echo "</div>";
}

/**
 * Validează CNP-urile existente
 */
function validate_cnps() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>✅ Validare CNP-uri</h3>";
    
    // Găsește toate CNP-urile
    $all_cnps = $wpdb->get_results("
        SELECT u.ID, u.display_name, um1.meta_value as first_name, um2.meta_value as last_name,
               p.cnp, p.import_source
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
        WHERE p.cnp IS NOT NULL AND p.cnp != ''
        ORDER BY p.cnp
    ");
    
    if (empty($all_cnps)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit CNP-uri pentru validare!</p>";
        echo "</div>";
        return;
    }
    
    $valid_cnps = 0;
    $invalid_cnps = 0;
    $temp_cnps = 0;
    $duplicate_cnps = array();
    
    // Verifică duplicatele
    $cnp_counts = array();
    foreach ($all_cnps as $user) {
        if (!isset($cnp_counts[$user->cnp])) {
            $cnp_counts[$user->cnp] = array();
        }
        $cnp_counts[$user->cnp][] = $user->ID;
    }
    
    foreach ($cnp_counts as $cnp => $user_ids) {
        if (count($user_ids) > 1) {
            $duplicate_cnps[] = array('cnp' => $cnp, 'users' => $user_ids);
        }
    }
    
    echo "<p class='info'>📋 S-au găsit <strong>" . count($all_cnps) . "</strong> CNP-uri pentru validare.</p>";
    
    foreach ($all_cnps as $user) {
        if (is_temp_cnp($user->cnp)) {
            $temp_cnps++;
        } elseif (is_valid_cnp($user->cnp)) {
            $valid_cnps++;
        } else {
            $invalid_cnps++;
        }
    }
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$valid_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Valide</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$temp_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Temporare</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$invalid_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Invalide</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>" . count($duplicate_cnps) . "</div>";
    echo "<div class='stat-label'>CNP-uri Duplicate</div>";
    echo "</div>";
    echo "</div>";
    
    // Afișează CNP-urile duplicate
    if (!empty($duplicate_cnps)) {
        echo "<h4>CNP-uri Duplicate:</h4>";
        foreach ($duplicate_cnps as $duplicate) {
            echo "<div class='user-preview'>";
            echo "<p class='error'>❌ CNP <strong>{$duplicate['cnp']}</strong> este folosit de utilizatorii:</p>";
            foreach ($duplicate['users'] as $user_id) {
                $user_info = $wpdb->get_row($wpdb->prepare("
                    SELECT u.display_name, um1.meta_value as first_name, um2.meta_value as last_name
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                    WHERE u.ID = %d
                ", $user_id));
                
                $name = trim($user_info->first_name . ' ' . $user_info->last_name);
                $name = !empty($name) ? $name : $user_info->display_name;
                
                echo "<p class='info'>👤 $name (ID: $user_id)</p>";
            }
            echo "</div>";
        }
    }
    
    echo "</div>";
}

/**
 * Generează CNP-uri reale pentru utilizatorii cu CNP-uri temporare
 */
function generate_real_cnps() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>🎲 Generare CNP-uri Reale</h3>";
    
    // Găsește utilizatorii cu CNP-uri temporare
    $temp_cnp_users = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               p.cnp
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id
        WHERE p.cnp IS NOT NULL 
        AND p.cnp != ''
        AND p.import_source = 'joomla_migration'
        ORDER BY u.user_registered DESC
    ");
    
    if (empty($temp_cnp_users)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit utilizatori cu CNP-uri temporare!</p>";
        echo "</div>";
        return;
    }
    
    $generated = 0;
    $errors = array();
    
    foreach ($temp_cnp_users as $user) {
        if (!is_temp_cnp($user->cnp)) {
            continue; // Sărim peste CNP-urile care nu sunt temporare
        }
        
        // Generează un CNP real
        $real_cnp = generate_real_cnp($user->user_registered, $user->ID);
        
        // Verifică dacă CNP-ul generat există deja
        $existing_cnp = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $real_cnp
        ));
        
        if ($existing_cnp) {
            $errors[] = "CNP-ul generat $real_cnp există deja pentru utilizatorul $existing_cnp";
            continue;
        }
        
        // Actualizează CNP-ul
        $update_result = $wpdb->update(
            $wpdb->prefix . 'clinica_patients',
            array('cnp' => $real_cnp),
            array('user_id' => $user->ID),
            array('%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            $errors[] = "Eroare la actualizarea CNP-ului pentru utilizatorul {$user->ID}";
        } else {
            $generated++;
            
            $name = trim($user->first_name . ' ' . $user->last_name);
            $name = !empty($name) ? $name : $user->display_name;
            
            echo "<div class='user-preview'>";
            echo "<p class='success'>✅ CNP generat pentru <strong>$name</strong> (ID: {$user->ID})</p>";
            echo "<p class='info'>🆔 CNP nou: $real_cnp (fost: {$user->cnp})</p>";
            echo "</div>";
        }
    }
    
    echo "<h4>Rezumat Generare:</h4>";
    echo "<p class='success'>✅ CNP-uri generate: $generated</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    echo "</div>";
}

/**
 * Verifică dacă un CNP este temporar
 */
function is_temp_cnp($cnp) {
    // CNP-urile temporare sunt generate din ID-ul utilizatorului și data înregistrării
    // Format: AAMMDDCCCCCC (unde CCCCCC este ID-ul utilizatorului)
    if (strlen($cnp) !== 13) {
        return false;
    }
    
    // Verifică dacă ultimele 6 cifre sunt un ID de utilizator valid
    $user_id = intval(substr($cnp, -6));
    return $user_id > 0 && $user_id < 999999;
}

/**
 * Verifică dacă un CNP este valid
 */
function is_valid_cnp($cnp) {
    if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
        return false;
    }
    
    // Algoritm de validare CNP
    $control_digits = array(2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9);
    $sum = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($cnp[$i]) * $control_digits[$i];
    }
    
    $control = $sum % 11;
    if ($control == 10) {
        $control = 1;
    }
    
    return $control == intval($cnp[12]);
}

/**
 * Generează un CNP real
 */
function generate_real_cnp($registration_date, $user_id) {
    // Folosește data înregistrării pentru a determina anul, luna și ziua
    $year = date('y', strtotime($registration_date));
    $month = date('m', strtotime($registration_date));
    $day = date('d', strtotime($registration_date));
    
    // Generează un număr unic pentru județ și număr de ordine
    $unique_number = str_pad($user_id, 6, '0', STR_PAD_LEFT);
    
    // Construiește CNP-ul (format: AAMMDDCCCCCC)
    $cnp = $year . $month . $day . $unique_number;
    
    // Calculează cifra de control
    $control_digits = array(2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9);
    $sum = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($cnp[$i]) * $control_digits[$i];
    }
    
    $control = $sum % 11;
    if ($control == 10) {
        $control = 1;
    }
    
    return $cnp . $control;
}

/**
 * Afișează statisticile CNP
 */
function display_cnp_stats() {
    global $wpdb;
    
    // Statistici CNP
    $total_cnps = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE cnp IS NOT NULL AND cnp != ''");
    $joomla_cnps = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients WHERE import_source = %s AND cnp IS NOT NULL AND cnp != ''",
        'joomla_migration'
    ));
    
    // CNP-uri temporare
    $temp_cnps = 0;
    if ($total_cnps > 0) {
        $all_cnps = $wpdb->get_results("SELECT cnp FROM {$wpdb->prefix}clinica_patients WHERE cnp IS NOT NULL AND cnp != ''");
        foreach ($all_cnps as $cnp_data) {
            if (is_temp_cnp($cnp_data->cnp)) {
                $temp_cnps++;
            }
        }
    }
    
    echo "<div class='section'>";
    echo "<h2>Statistici CNP</h2>";
    
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$total_cnps</div>";
    echo "<div class='stat-label'>Total CNP-uri</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$joomla_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Joomla</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$temp_cnps</div>";
    echo "<div class='stat-label'>CNP-uri Temporare</div>";
    echo "</div>";
    echo "</div>";
    
    if ($temp_cnps > 0) {
        echo "<h4>🔗 Link-uri Utile:</h4>";
        echo "<ul>";
        echo "<li><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Pagina Pacienți</a></li>";
        echo "<li><a href='import-from-joomla.php'>Import din Joomla</a></li>";
        echo "<li><a href='import-families-from-emails.php'>Import Familii</a></li>";
        echo "</ul>";
    }
    
    echo "</div>";
}
?>

<script>
function generateCNP(userId) {
    // Generează un CNP temporar pentru testare
    const now = new Date();
    const year = now.getFullYear().toString().slice(-2);
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const unique = String(userId).padStart(6, '0');
    
    const cnp = year + month + day + unique;
    const input = document.querySelector(`input[name="cnp_new[${userId}]"]`);
    if (input) {
        input.value = cnp;
    }
}
</script> 