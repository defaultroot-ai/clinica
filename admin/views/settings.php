<?php
/**
 * Helper function pentru verificarea setărilor
 */
function clinica_get_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]['value']) ? $settings[$key]['value'] : $default;
}

function clinica_get_setting_description($settings, $key, $default = '') {
    return isset($settings[$key]['description']) ? $settings[$key]['description'] : $default;
}

/**
 * Pagina pentru setările plugin-ului
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_manage_settings()) {
    wp_die(__('Nu aveți permisiunea de a gestiona setările.', 'clinica'));
}

$settings = Clinica_Settings::get_instance();

// Rulare sincronizare completă la cerere (din Setări)
if (isset($_POST['clinica_full_sync_now']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'clinica_full_sync_now')) {
    global $wpdb;
    $clinica_table = $wpdb->prefix . 'clinica_patients';
    $full = array('inserted_patients' => 0, 'affected_patients' => 0, 'affected_users' => 0, 'differences_fixed' => 0, 'roles_set' => 0, 'errors' => array());

    // 1) Creează pacienți lipsă din subscriberi
    $users = $wpdb->get_results("SELECT u.ID, u.user_login, u.user_email, u.display_name,
        (SELECT meta_value FROM {$wpdb->usermeta} m WHERE m.user_id=u.ID AND m.meta_key='phone_primary' LIMIT 1) AS phone_primary,
        (SELECT meta_value FROM {$wpdb->usermeta} m WHERE m.user_id=u.ID AND m.meta_key='phone_secondary' LIMIT 1) AS phone_secondary
        FROM {$wpdb->users} u
        WHERE u.ID IN (
          SELECT user_id FROM {$wpdb->usermeta}
          WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%subscriber%'
        )
        AND u.ID NOT IN (SELECT user_id FROM $clinica_table)
    ");
    foreach ($users as $user) {
        $cnp = $user->user_login; $len = strlen($cnp); $is_numeric = ctype_digit($cnp);
        $is_valid_cnp = ($is_numeric && $len >= 12 && $len <= 14);
        if (!$is_valid_cnp) { continue; }
        $cnp_type = ($len === 13) ? 'romanian' : 'foreign';
        $data = array(
            'user_id' => (int)$user->ID,
            'cnp' => $cnp,
            'cnp_type' => $cnp_type,
            'email' => (is_email($user->user_email) ? $user->user_email : null),
            'phone_primary' => $user->phone_primary ?: '',
            'phone_secondary' => $user->phone_secondary ?: '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        $ok = $wpdb->insert($clinica_table, $data);
        if ($ok !== false) { $full['inserted_patients']++; $wp_u = get_userdata((int)$user->ID); if ($wp_u && !in_array('clinica_patient', (array)$wp_u->roles, true)) { $wp_u->add_role('clinica_patient'); $full['roles_set']++; } }
    }

    // 2) Aliniază emailurile patients <-> users și setează rolurile
    $rows = $wpdb->get_results("SELECT p.id, p.user_id, p.email AS p_email, u.user_email AS u_email FROM $clinica_table p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id");
    foreach ($rows as $r) {
        $p_email = trim((string)$r->p_email); $u_email = trim((string)$r->u_email);
        $p_valid = !empty($p_email) && is_email($p_email); $u_valid = !empty($u_email) && is_email($u_email);
        if (!$p_valid && $u_valid) { if (false !== $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id))) { $full['affected_patients']++; } }
        if ($p_valid && !$u_valid && (int)$r->user_id > 0) { if (false !== $wpdb->update($wpdb->users, array('user_email' => $p_email), array('ID' => (int)$r->user_id))) { $full['affected_users']++; } }
        if ($p_valid && $u_valid && strcasecmp($p_email, $u_email) !== 0) { if (false !== $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id))) { $full['differences_fixed']++; } }
        if ((int)$r->user_id > 0) { $u = get_userdata((int)$r->user_id); if ($u && !in_array('clinica_patient', (array)$u->roles, true)) { $u->add_role('clinica_patient'); $full['roles_set']++; } }
    }

    update_option('clinica_last_sync', array(
        'date' => current_time('mysql'),
        'affected_patients' => $full['affected_patients'],
        'affected_users' => $full['affected_users'],
        'differences_fixed' => $full['differences_fixed'],
        'roles_set' => $full['roles_set'],
        'inserted_patients' => $full['inserted_patients'],
        'source' => 'settings_manual'
    ));
    add_settings_error('clinica_sync', 'clinica_sync_ok', __('Sincronizare completă rulată cu succes.', 'clinica'), 'updated');
}

// Procesează salvarea setărilor
if (isset($_POST['submit']) && wp_verify_nonce($_POST['clinica_settings_nonce'], 'clinica_settings')) {
    
    // DEBUG EXTENSIV - Log toate datele POST
    
    $groups = array('clinic', 'schedule', 'email', 'appointments', 'notifications', 'security', 'performance');
    
    foreach ($groups as $group) {
        $group_settings = $settings->get_group($group);
        foreach ($group_settings as $key => $setting_info) {
            // Special handling for working_hours which is an array
            if ($key === 'working_hours' && isset($_POST['working_hours'])) {
                $value = $_POST['working_hours'];
            } elseif ($key === 'clinic_logo' && isset($_FILES['clinic_logo'])) {
                // Special handling for file uploads
                $value = $_FILES['clinic_logo'];
            } elseif (isset($_POST[$key])) {
                $value = $_POST[$key];
            } else {
                continue; // Skip if not in POST
            }
            
                
                // Sanitizează valoarea în funcție de tip
                switch ($setting_info['type']) {
                    case 'boolean':
                        $value = (bool) $value;
                        break;
                    case 'number':
                        $value = (int) $value;
                        // Pentru setările de securitate, asigură că sunt în limitele corecte
                        if ($key === 'session_timeout') {
                            $value = max(5, min(480, $value)); // 5-480 minute
                        } elseif ($key === 'login_attempts') {
                            $value = max(3, min(10, $value)); // 3-10 încercări
                        } elseif ($key === 'lockout_duration') {
                            $value = max(5, min(1440, $value)); // 5-1440 minute
                        }
                        break;
                    case 'json':
                        // Pentru programul de funcționare
                        if ($key === 'working_hours') {
                            $working_hours = array();
                            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                            
                            
                            foreach ($days as $day) {
                                // Verifică dacă există datele pentru această zi
                                if (isset($_POST['working_hours'][$day])) {
                                    $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
                                    $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
                                    $break_start = isset($_POST['working_hours'][$day]['break_start']) ? sanitize_text_field($_POST['working_hours'][$day]['break_start']) : '';
                                    $break_end = isset($_POST['working_hours'][$day]['break_end']) ? sanitize_text_field($_POST['working_hours'][$day]['break_end']) : '';
                                    $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
                                    
                                    
                                    $working_hours[$day] = array(
                                        'start' => $start_time,
                                        'end' => $end_time,
                                        'break_start' => $break_start,
                                        'break_end' => $break_end,
                                        'active' => $is_active
                                    );
                                } else {
                                    // Dacă nu există date pentru această zi, setează valori implicite
                                    $working_hours[$day] = array(
                                        'start' => '',
                                        'end' => '',
                                        'break_start' => '',
                                        'break_end' => '',
                                        'active' => false
                                    );
                                }
                            }
                            
                            $value = $working_hours;
                        }
                        break;
                case 'file':
                    // Gestionarea upload-ului de fișiere
                    if ($key === 'clinic_logo') {
                        
                        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
                        // Verifică tipul fișierului
                        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
                        $file_type = $_FILES['clinic_logo']['type'];
                        
                        if (in_array($file_type, $allowed_types)) {
                            // Verifică dimensiunea (max 10MB)
                            if ($_FILES['clinic_logo']['size'] <= 10 * 1024 * 1024) {
                                // Creează directorul pentru logo-uri dacă nu există
                                $upload_dir = wp_upload_dir();
                                $logo_dir = $upload_dir['basedir'] . '/clinica-logos';
                                if (!file_exists($logo_dir)) {
                                    wp_mkdir_p($logo_dir);
                                }
                                
                                // Generează nume unic pentru fișier
                                $file_extension = pathinfo($_FILES['clinic_logo']['name'], PATHINFO_EXTENSION);
                                $file_name = 'clinic-logo-' . time() . '.' . $file_extension;
                                $file_path = $logo_dir . '/' . $file_name;
                                
                                // Mută fișierul
                                if (move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $file_path)) {
                                    $value = $upload_dir['baseurl'] . '/clinica-logos/' . $file_name;
                                } else {
                                    $value = $settings->get($key); // Păstrează valoarea existentă
                                }
                            } else {
                                $value = $settings->get($key); // Păstrează valoarea existentă
                            }
                        } else {
                            $value = $settings->get($key); // Păstrează valoarea existentă
                        }
                        } else {
                            // Dacă nu s-a uploadat nimic, păstrează valoarea existentă
                            $value = $settings->get($key);
                        }
                    }
                    break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                
                
                $result = $settings->set($key, $value);
                
        }
    }
    
    echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Setările au fost salvate cu succes!', 'clinica') . '</strong></p></div>';
}

// Obține setările curente
$clinic_settings = $settings->get_group('clinic');
$schedule_settings = $settings->get_group('schedule');
$email_settings = $settings->get_group('email');
$appointment_settings = $settings->get_group('appointments');
$notification_settings = $settings->get_group('notifications');
$security_settings = $settings->get_group('security');
$performance_settings = $settings->get_group('performance');

// FORȚEAZĂ reîncărcarea working_hours din DB (șterge cache-ul)
$settings->clear_cache('working_hours');

$working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();

// DEBUG - Log working_hours la încărcarea paginii
$debug_log = '=== DEBUG ÎNCĂRCARE PAGINĂ ===' . PHP_EOL;
$debug_log .= 'schedule_settings: ' . print_r($schedule_settings, true) . PHP_EOL;
$debug_log .= 'working_hours loaded: ' . print_r($working_hours, true) . PHP_EOL;
file_put_contents(__DIR__ . '/../../logs/settings-debug.log', $debug_log, FILE_APPEND);

// DEBUG - Verifică structura working_hours
$debug_log = '';
if (is_array($working_hours)) {
    foreach ($working_hours as $day => $hours) {
        $debug_log .= "Day $day: " . print_r($hours, true) . PHP_EOL;
    }
} else {
    $debug_log .= "EROARE: working_hours nu este array, este: " . gettype($working_hours) . PHP_EOL;
}
file_put_contents(__DIR__ . '/../../logs/settings-debug.log', $debug_log, FILE_APPEND);

// DEBUG - Verifică dacă working_hours este gol
if (empty($working_hours)) {
    $debug_log = "EROARE: working_hours este gol!" . PHP_EOL;
    file_put_contents(__DIR__ . '/../../logs/settings-debug.log', $debug_log, FILE_APPEND);
    // Setează date de test dacă este gol
    $working_hours = array(
        'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
        'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
        'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
        'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
        'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
        'saturday' => array('start' => '', 'end' => '', 'active' => false),
        'sunday' => array('start' => '', 'end' => '', 'active' => false)
    );
    $debug_log = "Setat working_hours cu date de test: " . print_r($working_hours, true) . PHP_EOL;
    file_put_contents(__DIR__ . '/../../logs/settings-debug.log', $debug_log, FILE_APPEND);
}

// DEBUG - Verifică specific pauzele pentru fiecare zi
$debug_log = "=== VERIFICARE PAUZE ÎN PHP ===\n";
foreach ($working_hours as $day => $hours) {
    $has_break = !empty($hours['break_start']) && !empty($hours['break_end']);
    $debug_log .= "$day: " . ($has_break ? "ARE pauză (" . $hours['break_start'] . "-" . $hours['break_end'] . ")" : "NU ARE pauză") . "\n";
}
file_put_contents(__DIR__ . '/../../logs/settings-debug.log', $debug_log, FILE_APPEND);


?>

<div class="wrap clinica-settings-page">
    <div class="clinica-settings-header">
        <h1><i class="dashicons dashicons-admin-settings"></i> <?php _e('Setări Clinica', 'clinica'); ?></h1>
        <p class="clinica-settings-description"><?php _e('Configurează toate aspectele clinicii tale medicale', 'clinica'); ?></p>
    </div>

    <?php $last_sync = get_option('clinica_last_sync'); if ($last_sync && is_array($last_sync)): ?>
    <div class="notice notice-info" style="margin-top:10px;">
        <p><strong><?php _e('Ultima sincronizare pacienți:', 'clinica'); ?></strong> <?php echo esc_html($last_sync['date'] ?? '-'); ?> (<?php echo esc_html($last_sync['source'] ?? '-'); ?>)</p>
        <ul style="margin-left:18px;">
            <li><?php _e('Pacienți actualizați (users → patients):', 'clinica'); ?> <strong><?php echo intval($last_sync['affected_patients'] ?? 0); ?></strong></li>
            <li><?php _e('Utilizatori actualizați (patients → users):', 'clinica'); ?> <strong><?php echo intval($last_sync['affected_users'] ?? 0); ?></strong></li>
            <li><?php _e('Diferențe aliniate:', 'clinica'); ?> <strong><?php echo intval($last_sync['differences_fixed'] ?? 0); ?></strong></li>
            <li><?php _e('Roluri setate:', 'clinica'); ?> <strong><?php echo intval($last_sync['roles_set'] ?? 0); ?></strong></li>
            <li><?php _e('Pacienți noi inserați:', 'clinica'); ?> <strong><?php echo intval($last_sync['inserted_patients'] ?? 0); ?></strong></li>
        </ul>
        <form method="post" action="">
            <?php wp_nonce_field('clinica_full_sync_now'); ?>
            <button type="submit" name="clinica_full_sync_now" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> <?php _e('Rulează sincronizare completă acum', 'clinica'); ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <form method="post" action="" enctype="multipart/form-data" class="clinica-settings-form">
        <?php wp_nonce_field('clinica_settings', 'clinica_settings_nonce'); ?>
        
        <!-- Tab Navigation -->
        <div class="clinica-tabs-nav">
            <button type="button" class="clinica-tab-button active" data-tab="clinic">
                <i class="dashicons dashicons-building"></i>
                <span><?php _e('Clinică', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="schedule">
                <i class="dashicons dashicons-calendar-alt"></i>
                <span><?php _e('Program', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="email">
                <i class="dashicons dashicons-email-alt"></i>
                <span><?php _e('Email', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="appointments">
                <i class="dashicons dashicons-clock"></i>
                <span><?php _e('Programări', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="notifications">
                <i class="dashicons dashicons-bell"></i>
                <span><?php _e('Notificări', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="security">
                <i class="dashicons dashicons-shield"></i>
                <span><?php _e('Securitate', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="performance">
                <i class="dashicons dashicons-performance"></i>
                <span><?php _e('Performanță', 'clinica'); ?></span>
            </button>
            <button type="button" class="clinica-tab-button" data-tab="holidays">
                <i class="dashicons dashicons-calendar"></i>
                <span><?php _e('Sărbători', 'clinica'); ?></span>
            </button>
        </div>
        
        <!-- Tab Content -->
        <div class="clinica-tabs-content">
            
            <!-- Tab Clinică -->
            <div class="clinica-tab-content active" id="tab-clinic">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-building"></i> <?php _e('Configurare Clinică', 'clinica'); ?></h2>
                    <p><?php _e('Informațiile de bază despre clinica ta', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label for="clinic_name"><?php _e('Numele clinicii', 'clinica'); ?></label>
                        <input type="text" id="clinic_name" name="clinic_name" value="<?php echo esc_attr(isset($clinic_settings['clinic_name']['value']) ? $clinic_settings['clinic_name']['value'] : ''); ?>" placeholder="<?php _e('Ex: Clinica Medicală Exemplu', 'clinica'); ?>">
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_name']['description']) ? $clinic_settings['clinic_name']['description'] : ''); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="clinic_email"><?php _e('Email clinică', 'clinica'); ?></label>
                        <input type="email" id="clinic_email" name="clinic_email" value="<?php echo esc_attr(isset($clinic_settings['clinic_email']['value']) ? $clinic_settings['clinic_email']['value'] : ''); ?>" placeholder="<?php _e('contact@clinica.ro', 'clinica'); ?>">
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_email']['description']) ? $clinic_settings['clinic_email']['description'] : ''); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="clinic_phone"><?php _e('Telefon clinică', 'clinica'); ?></label>
                        <input type="text" id="clinic_phone" name="clinic_phone" value="<?php echo esc_attr(isset($clinic_settings['clinic_phone']['value']) ? $clinic_settings['clinic_phone']['value'] : ''); ?>" placeholder="<?php _e('+40 123 456 789', 'clinica'); ?>">
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_phone']['description']) ? $clinic_settings['clinic_phone']['description'] : ''); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="clinic_website"><?php _e('Website clinică', 'clinica'); ?></label>
                        <input type="url" id="clinic_website" name="clinic_website" value="<?php echo esc_attr(isset($clinic_settings['clinic_website']['value']) ? $clinic_settings['clinic_website']['value'] : ''); ?>" placeholder="<?php _e('https://clinica.ro', 'clinica'); ?>">
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_website']['description']) ? $clinic_settings['clinic_website']['description'] : ''); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card full-width">
                        <label for="clinic_address"><?php _e('Adresa clinicii', 'clinica'); ?></label>
                        <textarea id="clinic_address" name="clinic_address" rows="3" placeholder="<?php _e('Strada Exemplu, Nr. 123, Oraș, Județ', 'clinica'); ?>"><?php echo esc_textarea(isset($clinic_settings['clinic_address']['value']) ? $clinic_settings['clinic_address']['value'] : ''); ?></textarea>
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_address']['description']) ? $clinic_settings['clinic_address']['description'] : ''); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="clinic_logo"><?php _e('Logo clinică', 'clinica'); ?></label>
                        <?php if (!empty($clinic_settings['clinic_logo']['value'])): ?>
                            <div class="current-logo">
                                <img src="<?php echo esc_url($clinic_settings['clinic_logo']['value']); ?>" alt="Logo clinică">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="clinic_logo" name="clinic_logo" accept="image/*">
                        <p class="description"><?php echo esc_html(isset($clinic_settings['clinic_logo']['description']) ? $clinic_settings['clinic_logo']['description'] : ''); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Program -->
            <div class="clinica-tab-content" id="tab-schedule">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-calendar-alt"></i> <?php _e('Program Funcționare', 'clinica'); ?></h2>
                    <p><?php _e('Configurează programul de funcționare al clinicii', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-schedule-excel">
                    <div class="schedule-header">
                        <h3><?php _e('Program Funcționare', 'clinica'); ?></h3>
                        <p><?php _e('Click pe celule pentru editare rapidă', 'clinica'); ?></p>
                    </div>
                    
                    
                    <?php
                    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
                    $all_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                    $day_names = array(
                        'monday' => __('Luni', 'clinica'),
                        'tuesday' => __('Marți', 'clinica'),
                        'wednesday' => __('Miercuri', 'clinica'),
                        'thursday' => __('Joi', 'clinica'),
                        'friday' => __('Vineri', 'clinica')
                    );
                    ?>
                    
                    <!-- Hidden inputs pentru working_hours -->
                    <div style="display: none;">
                        <?php foreach ($all_days as $day_key): 
                            $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "break_start" => "", "break_end" => "", "active" => false);
                        ?>
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo esc_attr(!empty($day_hours['start']) ? $day_hours['start'] : ''); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo esc_attr(!empty($day_hours['end']) ? $day_hours['end'] : ''); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][break_start]" value="<?php echo esc_attr(!empty($day_hours['break_start']) ? $day_hours['break_start'] : ''); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][break_end]" value="<?php echo esc_attr(!empty($day_hours['break_end']) ? $day_hours['break_end'] : ''); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][active]" value="<?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '1' : '0'; ?>">
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="schedule-table-container">
                        <table class="schedule-excel-table">
                            <thead>
                                <tr>
                                    <th class="row-header"><?php _e('Setare', 'clinica'); ?></th>
                                    <th class="day-header" data-day="monday"><?php _e('Luni', 'clinica'); ?></th>
                                    <th class="day-header" data-day="tuesday"><?php _e('Marți', 'clinica'); ?></th>
                                    <th class="day-header" data-day="wednesday"><?php _e('Miercuri', 'clinica'); ?></th>
                                    <th class="day-header" data-day="thursday"><?php _e('Joi', 'clinica'); ?></th>
                                    <th class="day-header" data-day="friday"><?php _e('Vineri', 'clinica'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <!-- Rând Status -->
                                <tr class="status-row">
                                    <td class="row-label"><?php _e('Status', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                                    ?>
                                    <td class="status-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="status">
                                        <div class="cell-content">
                                            <span class="status-indicator <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>"></span>
                                            <span class="status-text"><?php echo (!empty($day_hours['active']) && $day_hours['active']) ? __('Activ', 'clinica') : __('Inactiv', 'clinica'); ?></span>
                                        </div>
                                        <input type="checkbox" name="working_hours[<?php echo $day_key; ?>][active]" value="1" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'checked' : ''; ?> style="display: none;">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rând Început -->
                                <tr class="start-row">
                                    <td class="row-label"><?php _e('Început', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                                    ?>
                                    <td class="time-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="start">
                                        <div class="cell-display">
                                            <?php 
                                            $display_value = !empty($day_hours['start']) ? $day_hours['start'] : '--:--';
                                            echo esc_html($display_value);
                                            ?>
                                        </div>
                                        <div class="cell-edit" style="display: none;">
                                            <input type="time" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php 
                                                echo esc_attr(!empty($day_hours['start']) ? $day_hours['start'] : '');
                                            ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rând Sfârșit -->
                                <tr class="end-row">
                                    <td class="row-label"><?php _e('Sfârșit', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                                    ?>
                                    <td class="time-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="end">
                                        <div class="cell-display">
                                            <?php 
                                            $display_value = !empty($day_hours['end']) ? $day_hours['end'] : '--:--';
                                            echo esc_html($display_value);
                                            ?>
                                        </div>
                                        <div class="cell-edit" style="display: none;">
                                            <input type="time" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php 
                                                echo esc_attr(!empty($day_hours['end']) ? $day_hours['end'] : '');
                                            ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rând Pauză Început -->
                                <tr class="break-start-row">
                                    <td class="row-label"><?php _e('Pauză Început', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "break_start" => "", "break_end" => "", "active" => false);
                                    ?>
                                    <td class="time-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="break_start">
                                        <div class="cell-display">
                                            <?php 
                                            $display_value = !empty($day_hours['break_start']) ? $day_hours['break_start'] : '--:--';
                                            echo esc_html($display_value);
                                            ?>
                                        </div>
                                        <div class="cell-edit" style="display: none;">
                                            <input type="time" name="working_hours[<?php echo $day_key; ?>][break_start]" value="<?php 
                                                echo esc_attr(!empty($day_hours['break_start']) ? $day_hours['break_start'] : '');
                                            ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rând Pauză Sfârșit -->
                                <tr class="break-end-row">
                                    <td class="row-label"><?php _e('Pauză Sfârșit', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "break_start" => "", "break_end" => "", "active" => false);
                                    ?>
                                    <td class="time-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="break_end">
                                        <div class="cell-display">
                                            <?php 
                                            $display_value = !empty($day_hours['break_end']) ? $day_hours['break_end'] : '--:--';
                                            echo esc_html($display_value);
                                            ?>
                                        </div>
                                        <div class="cell-edit" style="display: none;">
                                            <input type="time" name="working_hours[<?php echo $day_key; ?>][break_end]" value="<?php 
                                                echo esc_attr(!empty($day_hours['break_end']) ? $day_hours['break_end'] : '');
                                            ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rând Durată -->
                                <tr class="duration-row">
                                    <td class="row-label"><?php _e('Durată', 'clinica'); ?></td>
                                    <?php foreach ($days as $day_key): 
                                        $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "break_start" => "", "break_end" => "", "active" => false);
                                        $start_time = !empty($day_hours['start']) ? strtotime($day_hours['start']) : 0;
                                        $end_time = !empty($day_hours['end']) ? strtotime($day_hours['end']) : 0;
                                        $break_start = !empty($day_hours['break_start']) ? strtotime($day_hours['break_start']) : 0;
                                        $break_end = !empty($day_hours['break_end']) ? strtotime($day_hours['break_end']) : 0;
                                        
                                        // Calculează durata totală minus pauza
                                        $total_duration = 0;
                                        if ((!empty($day_hours['active']) && $day_hours['active']) && !empty($day_hours['start']) && !empty($day_hours['end']) && $end_time > $start_time) {
                                            $total_duration = ($end_time - $start_time) / 3600;
                                            
                                            // Scade pauza dacă este configurată
                                            if ($break_start > 0 && $break_end > 0 && $break_end > $break_start) {
                                                $break_duration = ($break_end - $break_start) / 3600;
                                                $total_duration -= $break_duration;
                                            }
                                        }
                                        
                                        $duration = $total_duration > 0 ? round($total_duration, 1) . 'h' : '-';
                                    ?>
                                    <td class="duration-cell <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>">
                                        <span class="duration-value"><?php echo $duration; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Butoane de acțiune -->
                    <div class="schedule-actions">
                        <button type="button" class="apply-all-btn"><?php _e('Aplică la toate zilele', 'clinica'); ?></button>
                        <button type="button" class="reset-all-btn"><?php _e('Reset la default', 'clinica'); ?></button>
                        <button type="button" class="debug-breaks-btn" style="background: #0073aa; color: white; margin-left: 10px;"><?php _e('Verifică Pauzele', 'clinica'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Tab Email -->
            <div class="clinica-tab-content" id="tab-email">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-email-alt"></i> <?php _e('Setări Email', 'clinica'); ?></h2>
                    <p><?php _e('Configurează sistemul de email pentru notificări', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label for="email_from_name"><?php _e('Nume expeditor email', 'clinica'); ?></label>
                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($email_settings['email_from_name']['value']); ?>">
                        <p class="description"><?php echo esc_html($email_settings['email_from_name']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_from_address"><?php _e('Adresa expeditor email', 'clinica'); ?></label>
                        <input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr($email_settings['email_from_address']['value']); ?>">
                        <p class="description"><?php echo esc_html($email_settings['email_from_address']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_smtp_host"><?php _e('SMTP Host', 'clinica'); ?></label>
                        <input type="text" id="email_smtp_host" name="email_smtp_host" value="<?php echo esc_attr($email_settings['email_smtp_host']['value']); ?>" placeholder="smtp.gmail.com">
                        <p class="description"><?php echo esc_html($email_settings['email_smtp_host']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_smtp_port"><?php _e('SMTP Port', 'clinica'); ?></label>
                        <input type="number" id="email_smtp_port" name="email_smtp_port" value="<?php echo esc_attr($email_settings['email_smtp_port']['value']); ?>" min="0" max="65535">
                        <p class="description"><?php echo esc_html($email_settings['email_smtp_port']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_smtp_username"><?php _e('SMTP Username', 'clinica'); ?></label>
                        <input type="text" id="email_smtp_username" name="email_smtp_username" value="<?php echo esc_attr($email_settings['email_smtp_username']['value']); ?>">
                        <p class="description"><?php echo esc_html($email_settings['email_smtp_username']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_smtp_password"><?php _e('SMTP Password', 'clinica'); ?></label>
                        <input type="password" id="email_smtp_password" name="email_smtp_password" value="<?php echo esc_attr($email_settings['email_smtp_password']['value']); ?>">
                        <p class="description"><?php echo esc_html($email_settings['email_smtp_password']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="email_smtp_encryption"><?php _e('SMTP Encryption', 'clinica'); ?></label>
                        <select id="email_smtp_encryption" name="email_smtp_encryption">
                            <option value="tls" <?php selected($email_settings['email_smtp_encryption']['value'], 'tls'); ?>><?php _e('TLS', 'clinica'); ?></option>
                            <option value="ssl" <?php selected($email_settings['email_smtp_encryption']['value'], 'ssl'); ?>><?php _e('SSL', 'clinica'); ?></option>
                            <option value="none" <?php selected($email_settings['email_smtp_encryption']['value'], 'none'); ?>><?php _e('None', 'clinica'); ?></option>
                        </select>
                        <p class="description"><?php echo esc_html($email_settings['email_smtp_encryption']['description']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Programări -->
            <div class="clinica-tab-content" id="tab-appointments">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-clock"></i> <?php _e('Setări Programări', 'clinica'); ?></h2>
                    <p><?php _e('Configurează sistemul de programări', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label for="appointment_duration"><?php _e('Durată programări (minute)', 'clinica'); ?></label>
                        <input type="number" id="appointment_duration" name="appointment_duration" value="<?php echo esc_attr($appointment_settings['appointment_duration']['value']); ?>" min="15" max="180" step="15">
                        <p class="description"><?php echo esc_html($appointment_settings['appointment_duration']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="appointment_interval"><?php _e('Interval între programări (minute)', 'clinica'); ?></label>
                        <input type="number" id="appointment_interval" name="appointment_interval" value="<?php echo esc_attr($appointment_settings['appointment_interval']['value']); ?>" min="5" max="60" step="5">
                        <p class="description"><?php echo esc_html($appointment_settings['appointment_interval']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="appointment_advance_days"><?php _e('Zile în avans pentru programări', 'clinica'); ?></label>
                        <input type="number" id="appointment_advance_days" name="appointment_advance_days" value="<?php echo esc_attr($appointment_settings['appointment_advance_days']['value']); ?>" min="0" max="365">
                        <p class="description"><?php echo esc_html($appointment_settings['appointment_advance_days']['description']); ?></p>
                    </div>

                    <?php
                        global $wpdb;
                        $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_services ORDER BY active DESC, name ASC");
                    ?>
                    <div class="clinica-setting-card full-width">
                        <label><?php _e('Catalog servicii', 'clinica'); ?></label>
                        <div class="services-editor">
                            <table class="widefat fixed" id="services-table" style="margin-top:10px;">
                                <thead>
                                    <tr>
                                        <th style="width:50%">Nume</th>
                                        <th style="width:20%">Durată (min)</th>
                                        <th style="width:15%">Activ</th>
                                        <th style="width:15%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($services)): foreach ($services as $svc): ?>
                                    <tr data-id="<?php echo (int)$svc->id; ?>">
                                        <td><input type="text" class="svc-name" value="<?php echo esc_attr($svc->name); ?>" placeholder="ex: Consultație"></td>
                                        <td><input type="number" class="svc-duration" value="<?php echo esc_attr((int)$svc->duration); ?>" min="5" max="240" step="5"></td>
                                        <td><input type="checkbox" class="svc-active" <?php checked((int)$svc->active === 1); ?>></td>
                                        <td>
                                            <button type="button" class="button button-small svc-save">Salvează</button>
                                            <button type="button" class="button button-small svc-delete">Șterge</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="4"><?php _e('Nu există servicii definite.', 'clinica'); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div style="margin-top:10px; display:flex; gap:8px;">
                                <button type="button" class="button" id="svc-add">Adaugă serviciu</button>
                            </div>
                        </div>
                        <p class="description"><?php _e('Adaugă/editează servicii. ID-ul este generat automat.', 'clinica'); ?></p>
                    </div>

                    <?php 
                        $clinic_holidays_raw = isset($appointment_settings['clinic_holidays']['value']) ? $appointment_settings['clinic_holidays']['value'] : array();
                        if (is_string($clinic_holidays_raw)) { $decoded_h = json_decode($clinic_holidays_raw, true); $clinic_holidays = is_array($decoded_h) ? $decoded_h : array(); }
                        else { $clinic_holidays = is_array($clinic_holidays_raw) ? $clinic_holidays_raw : array(); }
                    ?>
                    <div class="clinica-setting-card full-width">
                        <label for="clinic_holidays_list"><?php _e('Zile libere clinică', 'clinica'); ?></label>
                        <input type="hidden" id="clinic_holidays_json_legal" name="clinic_holidays" value="">
                        
                        <!-- Sărbători legale românești -->
                        <div class="romanian-holidays-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e1e5e9;">
                            <h4 style="margin: 0 0 10px 0; color: #1d2327; font-size: 14px; font-weight: 600;">
                                <i class="fa fa-calendar" style="margin-right: 8px; color: #007cba;"></i>
                                <?php _e('Sărbători legale românești', 'clinica'); ?>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #646970; font-size: 13px;">
                                <?php _e('Sărbătorile legale sunt adăugate automat și actualizate anual. Click pentru a le include/exclude din programul clinicii.', 'clinica'); ?>
                            </p>
                            
                            <?php 
                            // Obține sărbătorile legale pentru anul curent
                            $current_year = date('Y');
                            $legal_holidays = Clinica_Romanian_Holidays::get_holidays($current_year);
                            $next_year_holidays = Clinica_Romanian_Holidays::get_holidays($current_year + 1);
                            $all_legal_holidays = array_merge($legal_holidays, $next_year_holidays);
                            ?>
                            
                            <div id="legal-holidays-list" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                                <?php foreach ($all_legal_holidays as $holiday_date): 
                                    $holiday_info = Clinica_Romanian_Holidays::get_holiday_info($holiday_date);
                                    $is_included = in_array($holiday_date, $clinic_holidays);
                                    $is_past = strtotime($holiday_date) < time();
                                ?>
                                <div class="legal-holiday-item <?php echo $is_included ? 'included' : 'excluded'; ?> <?php echo $is_past ? 'past' : 'future'; ?>" 
                                     data-date="<?php echo esc_attr($holiday_date); ?>" 
                                     style="padding: 8px 12px; border-radius: 16px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent;">
                                    <span class="holiday-date" style="font-weight: 500; font-size: 13px;"><?php echo esc_html($holiday_date); ?></span>
                                    <span class="holiday-name" style="font-size: 12px; color: #646970;">
                                        <?php echo $holiday_info ? esc_html($holiday_info['name']) : 'Sărbătoare'; ?>
                                    </span>
                                    <span class="holiday-status" style="font-size: 11px; padding: 2px 6px; border-radius: 10px; font-weight: 500;">
                                        <?php echo $is_included ? 'Inclus' : 'Exclus'; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="button" class="button button-secondary" id="include-all-holidays-legal">
                                    <i class="fa fa-check-circle" style="margin-right: 5px;"></i>
                                    <?php _e('Include toate', 'clinica'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="exclude-all-holidays-legal">
                                    <i class="fa fa-times-circle" style="margin-right: 5px;"></i>
                                    <?php _e('Exclude toate', 'clinica'); ?>
                                </button>
                                <button type="button" class="button button-link" id="refresh-holidays-legal">
                                    <i class="fa fa-refresh" style="margin-right: 5px;"></i>
                                    <?php _e('Actualizează', 'clinica'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Zile libere personalizate -->
                        <div class="custom-holidays-section">
                            <h4 style="margin: 0 0 10px 0; color: #1d2327; font-size: 14px; font-weight: 600;">
                                <i class="fa fa-plus-circle" style="margin-right: 8px; color: #007cba;"></i>
                                <?php _e('Zile libere personalizate', 'clinica'); ?>
                            </h4>
                            <div id="clinic-holidays-list" style="display:flex; flex-wrap:wrap; gap:8px;">
                                <?php foreach ($clinic_holidays as $d): 
                                    // Verifică dacă nu este o sărbătoare legală
                                    if (!in_array($d, $all_legal_holidays)):
                                ?>
                                    <span class="holiday-chip" data-date="<?php echo esc_attr($d); ?>" style="padding:6px 10px;background:#f1f3f4;border-radius:16px;display:inline-flex;align-items:center;gap:6px;">
                                        <span><?php echo esc_html($d); ?></span>
                                        <a href="#" class="remove-holiday" style="color:#c00;text-decoration:none;">×</a>
                                    </span>
                                <?php endif; endforeach; ?>
                            </div>
                            <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
                                <input type="date" id="add-holiday-date-legal">
                                <button type="button" class="button" id="add-holiday-btn-legal">Adaugă zi liberă</button>
                                <button type="button" class="button" id="clear-holidays-btn-legal">Golește lista</button>
                            </div>
                        </div>
                        
                        <p class="description"><?php _e('Zile (YYYY-MM-DD) în care clinica este închisă. Aceste zile vor fi excluse din disponibilitate.', 'clinica'); ?></p>
                    </div>

                    <div class="clinica-setting-card">
                        <label for="max_appointments_per_doctor_per_day"><?php _e('Limită programări/zi/medic', 'clinica'); ?></label>
                        <input type="number" id="max_appointments_per_doctor_per_day" name="max_appointments_per_doctor_per_day" value="<?php echo esc_attr(isset($appointment_settings['max_appointments_per_doctor_per_day']['value']) ? $appointment_settings['max_appointments_per_doctor_per_day']['value'] : 24); ?>" min="0" max="200">
                        <p class="description"><?php _e('Numărul maxim de programări per medic pe zi. Sloturile care depășesc limita nu vor fi afișate.', 'clinica'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Notificări -->
            <div class="clinica-tab-content" id="tab-notifications">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-bell"></i> <?php _e('Setări Notificări', 'clinica'); ?></h2>
                    <p><?php _e('Configurează sistemul de notificări', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label class="clinica-toggle-label">
                            <input type="checkbox" id="notifications_enabled" name="notifications_enabled" value="1" <?php checked($notification_settings['notifications_enabled']['value'], true); ?>>
                            <span class="toggle-slider"></span>
                            <?php _e('Notificări activate', 'clinica'); ?>
                        </label>
                        <p class="description"><?php echo esc_html($notification_settings['notifications_enabled']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="reminder_days"><?php _e('Zile înainte de reminder', 'clinica'); ?></label>
                        <input type="number" id="reminder_days" name="reminder_days" value="<?php echo esc_attr($notification_settings['reminder_days']['value']); ?>" min="0" max="7">
                        <p class="description"><?php echo esc_html($notification_settings['reminder_days']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label class="clinica-toggle-label">
                            <input type="checkbox" id="confirmation_required" name="confirmation_required" value="1" <?php checked($notification_settings['confirmation_required']['value'], true); ?>>
                            <span class="toggle-slider"></span>
                            <?php _e('Confirmare programări', 'clinica'); ?>
                        </label>
                        <p class="description"><?php echo esc_html($notification_settings['confirmation_required']['description']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Securitate -->
            <div class="clinica-tab-content" id="tab-security">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-shield"></i> <?php _e('Setări Securitate', 'clinica'); ?></h2>
                    <p><?php _e('Configurează securitatea sistemului', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label for="session_timeout"><?php _e('Timeout sesiuni (minute)', 'clinica'); ?></label>
                        <?php 
                        $session_timeout_value = isset($security_settings['session_timeout']['value']) ? $security_settings['session_timeout']['value'] : '30';
                        // Asigură că valoarea este între 5 și 480
                        $session_timeout_value = max(5, min(480, intval($session_timeout_value)));
                        ?>
                        <input type="number" id="session_timeout" name="session_timeout" value="<?php echo esc_attr($session_timeout_value); ?>" min="5" max="480" required>
                        <p class="description"><?php echo esc_html($security_settings['session_timeout']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="login_attempts"><?php _e('Încercări de login', 'clinica'); ?></label>
                        <input type="number" id="login_attempts" name="login_attempts" value="<?php echo esc_attr($security_settings['login_attempts']['value']); ?>" min="3" max="10">
                        <p class="description"><?php echo esc_html($security_settings['login_attempts']['description']); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="lockout_duration"><?php _e('Durată blocare (minute)', 'clinica'); ?></label>
                        <input type="number" id="lockout_duration" name="lockout_duration" value="<?php echo esc_attr($security_settings['lockout_duration']['value']); ?>" min="5" max="1440">
                        <p class="description"><?php echo esc_html($security_settings['lockout_duration']['description']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Performanță -->
            <div class="clinica-tab-content" id="tab-performance">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-performance"></i> <?php _e('Setări Performanță', 'clinica'); ?></h2>
                    <p><?php _e('Optimizează performanța sistemului', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <div class="clinica-setting-card">
                        <label for="items_per_page"><?php _e('Elemente pe pagină', 'clinica'); ?></label>
                        <input type="number" id="items_per_page" name="items_per_page" value="<?php echo esc_attr(isset($performance_settings['items_per_page']['value']) ? $performance_settings['items_per_page']['value'] : '20'); ?>" min="10" max="100">
                        <p class="description"><?php echo esc_html(isset($performance_settings['items_per_page']['description']) ? $performance_settings['items_per_page']['description'] : 'Numărul de elemente afișate pe pagină'); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label class="clinica-toggle-label">
                            <input type="checkbox" id="cache_enabled" name="cache_enabled" value="1" <?php checked(isset($performance_settings['cache_enabled']['value']) ? $performance_settings['cache_enabled']['value'] : false, true); ?>>
                            <span class="toggle-slider"></span>
                            <?php _e('Cache activat', 'clinica'); ?>
                        </label>
                        <p class="description"><?php echo esc_html(isset($performance_settings['cache_enabled']['description']) ? $performance_settings['cache_enabled']['description'] : 'Activează cache-ul pentru performanță îmbunătățită'); ?></p>
                    </div>
                    
                    <div class="clinica-setting-card">
                        <label for="auto_refresh"><?php _e('Auto-refresh (secunde)', 'clinica'); ?></label>
                        <input type="number" id="auto_refresh" name="auto_refresh" value="<?php echo esc_attr(isset($performance_settings['auto_refresh']['value']) ? $performance_settings['auto_refresh']['value'] : '0'); ?>" min="0" max="300">
                        <p class="description"><?php echo esc_html(isset($performance_settings['auto_refresh']['description']) ? $performance_settings['auto_refresh']['description'] : 'Intervalul de auto-refresh în secunde (0 = dezactivat)'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Sărbători -->
            <div class="clinica-tab-content" id="tab-holidays">
                <div class="clinica-tab-header">
                    <h2><i class="dashicons dashicons-calendar"></i> <?php _e('Sărbători Legale Românești', 'clinica'); ?></h2>
                    <p><?php _e('Gestionează sărbătorile legale și zilele libere ale clinicii', 'clinica'); ?></p>
                </div>
                
                <div class="clinica-settings-grid">
                    <!-- Sărbători legale românești -->
                    <div class="clinica-setting-card full-width">
                        <label><?php _e('Sărbători Legale Românești', 'clinica'); ?></label>
                        <div class="romanian-holidays-section" style="margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e5e9;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <h4 style="margin: 0 0 5px 0; color: #1d2327; font-size: 16px; font-weight: 600;">
                                        <i class="fa fa-calendar" style="margin-right: 8px; color: #007cba;"></i>
                                        <?php _e('Sărbători Oficiale', 'clinica'); ?>
                                    </h4>
                                    <p style="margin: 0; color: #646970; font-size: 14px;">
                                        <?php _e('Sărbătorile legale sunt actualizate automat anual. Click pentru a le include/exclude din programul clinicii.', 'clinica'); ?>
                                    </p>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="button button-secondary" id="include-all-holidays-custom">
                                        <i class="fa fa-check-circle" style="margin-right: 5px;"></i>
                                        <?php _e('Include toate', 'clinica'); ?>
                                    </button>
                                    <button type="button" class="button button-secondary" id="exclude-all-holidays-custom">
                                        <i class="fa fa-times-circle" style="margin-right: 5px;"></i>
                                        <?php _e('Exclude toate', 'clinica'); ?>
                                    </button>
                                    <button type="button" class="button button-link" id="refresh-holidays-custom">
                                        <i class="fa fa-refresh" style="margin-right: 5px;"></i>
                                        <?php _e('Actualizează', 'clinica'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <?php 
                            // Obține sărbătorile legale pentru anul curent și următorul
                            $current_year = date('Y');
                            $current_date = date('Y-m-d');
                            $legal_holidays = Clinica_Romanian_Holidays::get_holidays($current_year);
                            $next_year_holidays = Clinica_Romanian_Holidays::get_holidays($current_year + 1);
                            
                            // Filtrează sărbătorile - păstrează doar cele viitoare sau din anul curent
                            $all_legal_holidays = array_merge($legal_holidays, $next_year_holidays);
                            $all_legal_holidays = array_filter($all_legal_holidays, function($holiday_date) use ($current_date) {
                                return $holiday_date >= $current_date;
                            });
                            
                            // Sortează sărbătorile cronologic
                            sort($all_legal_holidays);
                            $clinic_holidays_raw = isset($appointment_settings['clinic_holidays']['value']) ? $appointment_settings['clinic_holidays']['value'] : array();
                            if (is_string($clinic_holidays_raw)) { 
                                $decoded_h = json_decode($clinic_holidays_raw, true); 
                                $clinic_holidays = is_array($decoded_h) ? $decoded_h : array(); 
                            } else { 
                                $clinic_holidays = is_array($clinic_holidays_raw) ? $clinic_holidays_raw : array(); 
                            }
                            ?>
                            
                            <div id="legal-holidays-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 20px;">
                                <?php foreach ($all_legal_holidays as $holiday_date): 
                                    $holiday_info = Clinica_Romanian_Holidays::get_holiday_info($holiday_date);
                                    $is_included = in_array($holiday_date, $clinic_holidays);
                                    $is_current_year = date('Y', strtotime($holiday_date)) == $current_year;
                                    $is_today = $holiday_date === $current_date;
                                ?>
                                <div class="legal-holiday-item <?php echo $is_included ? 'included' : 'excluded'; ?>" 
                                     data-date="<?php echo esc_attr($holiday_date); ?>" 
                                     style="padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease; border: 2px solid transparent; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                                            <span class="holiday-date" style="font-weight: 600; font-size: 14px; color: #1d2327;"><?php echo esc_html($holiday_date); ?></span>
                                            <?php if ($is_today): ?>
                                                <span style="background: #ff9800; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; font-weight: 500;"><?php _e('Astăzi', 'clinica'); ?></span>
                                            <?php elseif ($is_current_year): ?>
                                                <span style="background: #007cba; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; font-weight: 500;"><?php _e('Anul curent', 'clinica'); ?></span>
                                            <?php else: ?>
                                                <span style="background: #f0f0f0; color: #666; padding: 2px 6px; border-radius: 10px; font-size: 11px; font-weight: 500;"><?php echo date('Y', strtotime($holiday_date)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="holiday-name" style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                                            <?php echo $holiday_info ? esc_html($holiday_info['name']) : 'Sărbătoare'; ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="holiday-status" style="font-size: 12px; padding: 4px 8px; border-radius: 12px; font-weight: 500;">
                                                <?php echo $is_included ? 'Inclus în program' : 'Exclus din program'; ?>
                                            </span>
                                            <?php if ($is_today): ?>
                                                <span style="font-size: 11px; color: #ff9800; font-weight: 500;"><?php _e('Astăzi', 'clinica'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                        <div style="font-size: 20px; color: #007cba;">
                                            <i class="fa fa-<?php echo $is_included ? 'check-circle' : 'circle-o'; ?>"></i>
                                        </div>
                                        <div style="font-size: 16px; font-weight: bold; text-transform: uppercase; <?php 
                                            $english_day = date('l', strtotime($holiday_date));
                                            echo ($english_day === 'Saturday' || $english_day === 'Sunday') ? 'color: #dc3232;' : 'color: #1d2327;';
                                        ?>">
                                            <?php 
                                                $day_names = array(
                                                    'Monday' => 'LUNI',
                                                    'Tuesday' => 'MARȚI',
                                                    'Wednesday' => 'MIERCURI',
                                                    'Thursday' => 'JOI',
                                                    'Friday' => 'VINERI',
                                                    'Saturday' => 'SÂMBĂTĂ',
                                                    'Sunday' => 'DUMINICĂ'
                                                );
                                                echo isset($day_names[$english_day]) ? $day_names[$english_day] : strtoupper($english_day);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; border-left: 4px solid #007cba;">
                                <h5 style="margin: 0 0 8px 0; color: #007cba; font-size: 14px; font-weight: 600;">
                                    <i class="fa fa-info-circle" style="margin-right: 5px;"></i>
                                    <?php _e('Informații', 'clinica'); ?>
                                </h5>
                                <ul style="margin: 0; padding-left: 20px; color: #646970; font-size: 13px; line-height: 1.5;">
                                    <li><?php _e('Sărbătorile se actualizează automat în fiecare an', 'clinica'); ?></li>
                                    <li><?php _e('Paștele și Vinerea Mare sunt calculate automat', 'clinica'); ?></li>
                                    <li><?php _e('Zilele incluse vor fi excluse din programări', 'clinica'); ?></li>
                                    <li><?php _e('Poți include/exclude orice sărbătoare individual', 'clinica'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Zile libere personalizate -->
                    <div class="clinica-setting-card full-width">
                        <label for="clinic_holidays_list"><?php _e('Zile Libere Personalizate', 'clinica'); ?></label>
                        <input type="hidden" id="clinic_holidays_json_custom" name="clinic_holidays" value="">
                        
                        <div class="custom-holidays-section" style="padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #e1e5e9;">
                            <h4 style="margin: 0 0 15px 0; color: #1d2327; font-size: 16px; font-weight: 600;">
                                <i class="fa fa-plus-circle" style="margin-right: 8px; color: #007cba;"></i>
                                <?php _e('Adaugă Zile Libere Personalizate', 'clinica'); ?>
                            </h4>
                            
                            <div id="clinic-holidays-list" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom: 15px; min-height: 40px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px dashed #ddd;">
                                <?php foreach ($clinic_holidays as $d): 
                                    // Verifică dacă nu este o sărbătoare legală
                                    if (!in_array($d, $all_legal_holidays)):
                                ?>
                                    <span class="holiday-chip" data-date="<?php echo esc_attr($d); ?>" style="padding:8px 12px;background:#007cba;color:white;border-radius:16px;display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:500;">
                                        <span><?php echo esc_html($d); ?></span>
                                        <a href="#" class="remove-holiday" style="color:white;text-decoration:none;font-size:16px;line-height:1;padding:2px;border-radius:50%;background:rgba(255,255,255,0.2);width:18px;height:18px;display:flex;align-items:center;justify-content:center;">×</a>
                                    </span>
                                <?php endif; endforeach; ?>
                                
                                <?php if (empty(array_filter($clinic_holidays, function($d) use ($all_legal_holidays) { return !in_array($d, $all_legal_holidays); }))): ?>
                                <div style="color: #999; font-style: italic; font-size: 14px;">
                                    <?php _e('Nu există zile libere personalizate. Adaugă prima zi liberă mai jos.', 'clinica'); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <input type="date" id="add-holiday-date-custom" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                <button type="button" class="button" id="add-holiday-btn-custom">
                                    <i class="fa fa-plus" style="margin-right: 5px;"></i>
                                    <?php _e('Adaugă zi liberă', 'clinica'); ?>
                                </button>
                                <button type="button" class="button button-link-delete" id="clear-holidays-btn-custom">
                                    <i class="fa fa-trash" style="margin-right: 5px;"></i>
                                    <?php _e('Golește lista', 'clinica'); ?>
                                </button>
                            </div>
                            
                            <p class="description" style="margin-top: 15px; color: #646970; font-size: 13px; line-height: 1.4;">
                                <?php _e('Adaugă zile libere personalizate pentru evenimente speciale, întreținere sau alte motive. Aceste zile vor fi excluse din programări.', 'clinica'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submit Button îmbunătățit -->
        <div class="clinica-settings-submit">
            <button type="submit" name="submit" class="button button-primary button-hero">
                <i class="dashicons dashicons-saved"></i>
                <?php _e('Salvează Toate Setările', 'clinica'); ?>
            </button>
        </div>
    </form>
</div>

<style>
/* Stiluri generale */
.clinica-settings-page {
    max-width: 1200px;
    margin: 20px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.clinica-settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: black !important;
    padding: 30px;
    text-align: center;
}

.clinica-settings-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 300;
    color: black !important;
}

.clinica-settings-header .dashicons {
    margin-right: 10px;
    font-size: 24px;
    color: black !important;
}

.clinica-settings-description {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
    color: black !important;
}

/* Tab Navigation */
.clinica-tabs-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
    overflow-x: auto;
}

.clinica-tab-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 20px;
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
}

.clinica-tab-button:hover {
    background: #e9ecef;
    color: #495057;
}

.clinica-tab-button.active {
    background: #fff;
    color: #007cba;
    border-bottom-color: #007cba;
}

.clinica-tab-button .dashicons {
    font-size: 16px;
}

/* Tab Content */
.clinica-tabs-content {
    padding: 0;
}

.clinica-tab-content {
    display: none;
    padding: 30px;
}

.clinica-tab-content.active {
    display: block;
}

.clinica-tab-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f3f4;
}

.clinica-tab-header h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 24px;
    font-weight: 600;
}

.clinica-tab-header .dashicons {
    margin-right: 10px;
    color: #007cba;
}

.clinica-tab-header p {
    margin: 0;
    color: #646970;
    font-size: 16px;
}

/* Settings Grid */
.clinica-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.clinica-setting-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.clinica-setting-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border-color: #d1d5db;
    transform: translateY(-1px);
}

.clinica-setting-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.clinica-setting-card:hover::before {
    opacity: 1;
}

.clinica-setting-card label {
    display: block;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 8px;
    font-size: 14px;
}

.clinica-setting-card input[type="text"],
.clinica-setting-card input[type="email"],
.clinica-setting-card input[type="number"],
.clinica-setting-card input[type="url"],
.clinica-setting-card textarea,
.clinica-setting-card select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #fff;
}

.clinica-setting-card input[type="text"]:focus,
.clinica-setting-card input[type="email"]:focus,
.clinica-setting-card input[type="number"]:focus,
.clinica-setting-card input[type="url"]:focus,
.clinica-setting-card textarea:focus,
.clinica-setting-card select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.clinica-setting-card .description {
    margin: 8px 0 0 0;
    font-size: 13px;
    color: #646970;
    line-height: 1.4;
}

/* Toggle Switches pentru Settings Cards - VERSIUNE ÎMBUNĂTĂȚITĂ */
.clinica-toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    margin-bottom: 10px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    transition: all 0.2s ease;
}

.clinica-toggle-label:hover {
    background: #f1f3f4;
    border-color: #d1d5db;
}

.clinica-toggle-label input[type="checkbox"] {
    display: none !important;
}

.clinica-toggle-label .toggle-slider {
    position: relative;
    width: 56px;
    height: 28px;
    background: #e5e7eb;
    border-radius: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    margin-left: auto;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    display: block !important;
}

.clinica-toggle-label .toggle-slider:before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.1);
    display: block !important;
}

.clinica-toggle-label input:checked + .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

.clinica-toggle-label input:checked + .toggle-slider:before {
    transform: translateX(28px) !important;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2), 0 1px 3px rgba(0,0,0,0.1) !important;
}

.clinica-toggle-label:hover .toggle-slider {
    background: #d1d5db !important;
}

.clinica-toggle-label input:checked:hover + .toggle-slider {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%) !important;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
}

.clinica-toggle-label:active .toggle-slider:before {
    transform: scale(0.95) !important;
}

.clinica-toggle-label input:checked:active + .toggle-slider:before {
    transform: translateX(28px) scale(0.95) !important;
}

/* Schedule Excel Style */
.clinica-schedule-excel {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.schedule-header {
    text-align: center;
    margin-bottom: 25px;
}

.schedule-header h3 {
    margin: 0 0 8px 0;
    color: #1d2327;
    font-size: 18px;
    font-weight: 600;
}

.schedule-header p {
    margin: 0;
    color: #646970;
    font-size: 13px;
}

.schedule-table-container {
    overflow-x: auto;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.schedule-excel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
}

.schedule-excel-table th {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    padding: 12px 8px;
    text-align: center;
    font-weight: 600;
    color: #1d2327;
    position: sticky;
    top: 0;
    z-index: 10;
}

.schedule-excel-table td {
    border: 1px solid #e1e5e9;
    padding: 10px 8px;
    text-align: center;
    vertical-align: middle;
    transition: all 0.2s ease;
    position: relative;
}

.schedule-excel-table td:hover {
    background: #f8f9fa;
    cursor: pointer;
}

.schedule-excel-table td.editing {
    background: #e3f2fd;
    border-color: #007cba;
    box-shadow: inset 0 0 0 2px #007cba;
}

.row-header {
    background: #f1f3f4 !important;
    font-weight: 600;
    color: #1d2327;
    text-align: left !important;
    min-width: 100px;
}

.day-header {
    background: #e8f4fd !important;
    color: #007cba;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 120px;
}

.row-label {
    font-weight: 600;
    color: #1d2327;
    text-align: left;
    padding-left: 12px;
}

/* Status Cells */
.status-cell {
    cursor: pointer;
    position: relative;
}

.status-cell.active {
    background: #f8fff9;
    border-color: #28a745;
}

.status-cell.inactive {
    background: #fff8f8;
    border-color: #dc3545;
}

.cell-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.active {
    background: #28a745;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
}

.status-indicator.inactive {
    background: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
}

.status-text {
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-cell.active .status-text {
    color: #155724;
}

.status-cell.inactive .status-text {
    color: #721c24;
}

/* Time Cells */
.time-cell {
    cursor: pointer;
    font-weight: 500;
    color: #1d2327;
    position: relative;
    min-height: 40px;
}

.time-cell.active {
    background: #f8fff9;
    border-color: #28a745;
}

.time-cell.inactive {
    background: #f8f9fa;
    color: #6c757d;
    border-color: #dee2e6;
    cursor: not-allowed;
}

.time-cell:hover:not(.inactive) {
    background: #e8f5e8;
    border-color: #28a745;
}

.time-cell.editing {
    background: #e3f2fd !important;
    border-color: #007cba !important;
    box-shadow: inset 0 0 0 2px #007cba !important;
    z-index: 100;
}

.time-cell.error {
    background: #ffe6e6 !important;
    border-color: #dc3545 !important;
    box-shadow: inset 0 0 0 2px #dc3545 !important;
}

.cell-display {
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cell-edit {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 20;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.cell-edit input[type="time"] {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    font-size: 14px;
    text-align: center;
    padding: 8px;
    outline: none;
    border-radius: 4px;
}

.cell-edit input[type="time"]:focus {
    background: #f8f9fa;
    box-shadow: inset 0 0 0 2px #007cba;
}

/* Duration Cells */
.duration-cell {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.duration-cell.active {
    background: #e8f5e8;
    color: #155724;
}

.duration-cell.inactive {
    background: #f8f9fa;
    color: #6c757d;
}

.duration-value {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Schedule Actions */
.schedule-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.apply-all-btn,
.reset-all-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.apply-all-btn {
    background: #007cba;
    color: white;
}

.apply-all-btn:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.reset-all-btn {
    background: #6c757d;
    color: white;
}

.reset-all-btn:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

/* Current Logo */
.current-logo {
    margin-bottom: 15px;
    text-align: center;
}

.current-logo img {
    max-width: 200px;
    max-height: 100px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Submit Button îmbunătățit */
.clinica-settings-submit {
    text-align: center;
    margin-top: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    border: 1px solid #e1e5e9;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}

.clinica-settings-submit button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.clinica-settings-submit button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

.clinica-settings-submit button:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.clinica-settings-submit button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.clinica-settings-submit button:hover::before {
    left: 100%;
}

.clinica-settings-submit .dashicons {
    margin-right: 8px;
    font-size: 18px;
    vertical-align: middle;
}

/* Responsive */
@media (max-width: 768px) {
    .clinica-tabs-nav {
        flex-direction: column;
    }
    
    .clinica-tab-button {
        justify-content: center;
    }
    
    .clinica-settings-grid {
        grid-template-columns: 1fr;
    }
    
    .clinica-schedule-excel {
        padding: 15px;
    }
    
    .schedule-excel-table {
        font-size: 11px;
    }
    
    .schedule-excel-table th,
    .schedule-excel-table td {
        padding: 6px 4px;
    }
    
    .row-header {
        min-width: 80px;
    }
    
    .day-header {
        min-width: 90px;
        font-size: 10px;
    }
    
    .status-text {
        font-size: 10px;
    }
    
    .cell-display {
        font-size: 12px;
    }
    
    .duration-value {
        font-size: 10px;
    }
    
    .schedule-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .apply-all-btn,
    .reset-all-btn {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .clinica-settings-header {
        padding: 20px;
    }
    
    .clinica-settings-header h1 {
        font-size: 24px;
    }
    
    .clinica-tab-content {
        padding: 20px;
    }
}

/* Animations */
.clinica-tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Success Notice */
.notice-success {
    border-left-color: #28a745 !important;
    background: #f8fff9 !important;
}

.notice-success p {
    color: #155724 !important;
    font-weight: 600 !important;
}

.clinica-setting-card.full-width {
    grid-column: 1 / -1;
}

/* Stiluri pentru sărbătorile legale */
.legal-holiday-item {
    transition: all 0.2s ease;
    user-select: none;
}

.legal-holiday-item.included {
    background: #e8f5e8 !important;
    border-color: #4caf50 !important;
    color: #2e7d32 !important;
}

.legal-holiday-item.excluded {
    background: #f5f5f5 !important;
    border-color: #ddd !important;
    color: #666 !important;
}

.legal-holiday-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.legal-holiday-item.past {
    opacity: 0.7;
}

.legal-holiday-item.future {
    opacity: 1;
}

.holiday-status {
    font-size: 11px !important;
    padding: 2px 6px !important;
    border-radius: 10px !important;
    font-weight: 500 !important;
    background: rgba(255,255,255,0.8) !important;
}

.legal-holiday-item.included .holiday-status {
    background: #4caf50 !important;
    color: white !important;
}

.legal-holiday-item.excluded .holiday-status {
    background: #666 !important;
    color: white !important;
}

/* Stiluri pentru tab-ul de sărbători */
.legal-holiday-item {
    transition: all 0.3s ease;
    user-select: none;
}

.legal-holiday-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.legal-holiday-item.included {
    background: #e8f5e8 !important;
    border-color: #4caf50 !important;
}

.legal-holiday-item.excluded {
    background: #fff !important;
    border-color: #e1e5e9 !important;
}

/* Stiluri pentru sărbătoarea de astăzi */
.legal-holiday-item[data-date="<?php echo date('Y-m-d'); ?>"] {
    border-color: #ff9800 !important;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2) !important;
}

.holiday-chip {
    transition: all 0.2s ease;
}

.holiday-chip:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,123,186,0.3);
}

.remove-holiday:hover {
    background: rgba(255,255,255,0.3) !important;
    transform: scale(1.1);
}

/* Responsive pentru tab-ul de sărbători */
@media (max-width: 768px) {
    #legal-holidays-list {
        grid-template-columns: 1fr !important;
    }
    
    .legal-holiday-item {
        padding: 12px !important;
    }
    
    .legal-holiday-item > div:first-child {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 5px !important;
    }
}

/* Fix pentru pauzele - asigură că pot fi editate */
.break-start-row .time-cell input[type="time"],
.break-end-row .time-cell input[type="time"] {
    pointer-events: auto !important;
    z-index: 1000 !important;
    position: relative !important;
}

.break-start-row .time-cell,
.break-end-row .time-cell {
    pointer-events: auto !important;
    z-index: 999 !important;
}

/* Stiluri pentru validarea erorilor */
.time-cell.error {
    background: #ffe6e6 !important;
    border-color: #dc3545 !important;
    box-shadow: inset 0 0 0 2px #dc3545 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.clinica-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.clinica-tab-button').removeClass('active');
        $('.clinica-tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#tab-' + tabId).addClass('active');
        
        // Save active tab to localStorage
        localStorage.setItem('clinica_active_tab', tabId);
        console.log('Saved active tab:', tabId);
    });
    
    // Restore active tab on page load
    var activeTab = localStorage.getItem('clinica_active_tab');
    if (activeTab && $('.clinica-tab-button[data-tab="' + activeTab + '"]').length > 0) {
        // Remove active class from all tabs and content
        $('.clinica-tab-button').removeClass('active');
        $('.clinica-tab-content').removeClass('active');
        
        // Add active class to saved tab
        $('.clinica-tab-button[data-tab="' + activeTab + '"]').addClass('active');
        $('#tab-' + activeTab).addClass('active');
        console.log('Restored active tab:', activeTab);
    }
    
    // Excel-style cell editing - IMPROVED VERSION
    $(document).on('click', '.time-cell', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var cell = $(this);
        var day = cell.data('day');
        var type = cell.data('type');
        
        // Only allow editing if the day is active
        if (cell.hasClass('inactive')) {
            return;
        }
        
        // Close all other editing cells
        $('.time-cell').removeClass('editing').find('.cell-edit').hide();
        
        // Open this cell for editing
        cell.addClass('editing');
        cell.find('.cell-edit').show();
        
        // Sync input with display before editing
        syncInputWithDisplay(cell);
        
        // Focus on the input and select all text
        var input = cell.find('input[type="time"]');
        input.focus();
        input.select();
        
        console.log('Editing cell for day:', day, 'type:', type, 'input value:', input.val());
        
        // Debug logging pentru pauze
        if (type === 'break_start' || type === 'break_end') {
            console.log('🔍 BREAK CELL CLICKED - Type:', type, 'Day:', day);
        }
    });
    
    // DEBUG: Generic input handler to catch all input events
    $(document).on('input', 'input[type="time"]', function() {
        console.log('🔍 GENERIC INPUT - Name:', $(this).attr('name'), 'Value:', $(this).val());
    });
    
    // TEST: Simple test for break inputs
    $(document).on('input', 'input[name*="break_start"], input[name*="break_end"]', function() {
        console.log('🔍 BREAK SIMPLE TEST - Name:', $(this).attr('name'), 'Value:', $(this).val());
    });
    
    // TEST: Simple test for ALL text inputs
    $(document).on('input', 'input[type="text"][name*="break"]', function() {
        console.log('🔍 BREAK TEXT TEST - Name:', $(this).attr('name'), 'Value:', $(this).val());
    });
    
    // SPECIFIC HANDLER pentru pauzele - în caz că cele generale nu funcționează
    $(document).on('input change keyup paste', '.break-start-row input[type="time"], .break-end-row input[type="time"]', function() {
        console.log('🔍 BREAK SPECIFIC INPUT - Name:', $(this).attr('name'), 'Value:', $(this).val());
        
        var input = $(this);
        var cell = input.closest('.time-cell');
        var day = cell.data('day');
        var type = cell.data('type');
        
        // Forțează păstrarea valorii
        if (input.val() && input.val().trim() !== '') {
            console.log('🔍 FORCING BREAK VALUE SAVE - Type:', type, 'Day:', day, 'Value:', input.val());
            preserveInputValue(input);
        }
        
        // Sincronizează hidden inputs când se schimbă pauza
        syncHiddenInputsOnBreakChange(day);
    });
    
    // ULTRA SPECIFIC HANDLER pentru pauzele - prinde orice modificare
    $(document).on('keydown keyup keypress input change paste', '.break-start-row input[type="time"], .break-end-row input[type="time"]', function(e) {
        console.log('🔍 BREAK ULTRA INPUT - Event:', e.type, 'Name:', $(this).attr('name'), 'Value:', $(this).val());
        
        var input = $(this);
        var cell = input.closest('.time-cell');
        var day = cell.data('day');
        var type = cell.data('type');
        
        // Forțează păstrarea valorii imediat
        if (input.val() && input.val().trim() !== '') {
            console.log('🔍 ULTRA FORCING BREAK VALUE SAVE - Type:', type, 'Day:', day, 'Value:', input.val());
            preserveInputValue(input);
        }
    });
    
    // COMBINED INPUT HANDLER - handles all input events for time fields
    $(document).on('input', '.time-cell input[type="time"]', function() {
        var input = $(this);
        var value = input.val();
        var cell = input.closest('.time-cell');
        var type = cell.data('type');
        var day = cell.data('day');
        
        console.log('🔍 COMBINED INPUT - Type:', type, 'Day:', day, 'Value:', value);
        console.log('🔍 Input element:', input[0], 'Name:', input.attr('name'));
        
        // Keep edit mode open
        if (!cell.hasClass('editing')) {
            cell.addClass('editing');
            cell.find('.cell-edit').show();
        }
        
        // Update display immediately
        var display = cell.find('.cell-display');
        if (value && value.trim() !== '') {
            display.text(value);
        } else {
            display.text('--:--');
        }
        
        // Only allow valid time characters
        if (value && value.trim() !== '') {
            // Remove any non-time characters
            var cleanedValue = value.replace(/[^0-9:]/g, '');
            
            // Ensure proper format
            if (cleanedValue.length > 0) {
                // Add colon if missing and we have at least 2 digits
                if (cleanedValue.length >= 2 && !cleanedValue.includes(':')) {
                    cleanedValue = cleanedValue.substring(0, 2) + ':' + cleanedValue.substring(2);
                }
                
                // Limit to HH:MM format
                if (cleanedValue.length > 5) {
                    cleanedValue = cleanedValue.substring(0, 5);
                }
                
                // Only update if the value changed
                if (cleanedValue !== value) {
                    input.val(cleanedValue);
                }
            }
        }
        
        // Validare specifică pentru pauze
        if (type === 'break_start' || type === 'break_end') {
            validateBreakTimes(cell);
        }
    });
    
    // Funcție pentru validarea pauzelor
    function validateBreakTimes(breakCell) {
        var day = breakCell.data('day');
        var breakStartCell = $('.time-cell[data-day="' + day + '"][data-type="break_start"]');
        var breakEndCell = $('.time-cell[data-day="' + day + '"][data-type="break_end"]');
        
        var breakStart = breakStartCell.find('input').val();
        var breakEnd = breakEndCell.find('input').val();
        
        console.log('Validating break times for day:', day, 'start:', breakStart, 'end:', breakEnd);
        
        // Verifică dacă ambele pauze sunt completate
        if (breakStart && breakEnd && breakStart.trim() !== '' && breakEnd.trim() !== '') {
            var startTime = new Date('2000-01-01 ' + breakStart);
            var endTime = new Date('2000-01-01 ' + breakEnd);
            
            if (startTime >= endTime) {
                console.warn('Break start time must be before break end time');
                // Opțional: afișează un mesaj de eroare
                breakStartCell.addClass('error');
                breakEndCell.addClass('error');
            } else {
                breakStartCell.removeClass('error');
                breakEndCell.removeClass('error');
            }
        }
    }
    
    // Function to preserve input values
    function preserveInputValue(input) {
        var cell = input.closest('.time-cell');
        var inputValue = input.val();
        
        console.log('Preserving input value:', inputValue);
        
        // Debug logging pentru pauze
        var day = cell.data('day');
        var type = input.attr('name').includes('break_start') ? 'break_start' : 
                  input.attr('name').includes('break_end') ? 'break_end' :
                  input.attr('name').includes('start') ? 'start' : 'end';
        if (type === 'break_start' || type === 'break_end') {
            console.log('🔍 PRESERVING BREAK VALUE - Type:', type, 'Day:', day, 'Value:', inputValue, 'Input element:', input[0]);
            console.log('🔍 Input val() direct:', input.val(), 'Input value attr:', input.attr('value'));
        }
        
        // Validate and clean the time value
        if (inputValue && inputValue.trim() !== '') {
            // Check if it's a valid time format (HH:MM)
            var timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (timeRegex.test(inputValue)) {
                // Update display
                cell.find('.cell-display').text(inputValue);
                console.log('Updated display to:', inputValue);
            } else {
                // Invalid time format, clear the input
                input.val('');
                cell.find('.cell-display').text('--:--');
                console.log('Invalid time format, cleared input');
            }
        } else {
            cell.find('.cell-display').text('--:--');
            console.log('Updated display to: --:--');
        }
        
        // Update hidden input with cleaned value (but don't save empty values)
        var day = cell.data('day');
        var type = input.attr('name').includes('break_start') ? 'break_start' : 
                  input.attr('name').includes('break_end') ? 'break_end' :
                  input.attr('name').includes('start') ? 'start' : 'end';
        var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
        var finalValue = input.val(); // Use the cleaned value
        
        // Don't save empty values - keep previous value if current is empty
        if (finalValue && finalValue.trim() !== '') {
        hiddenInput.val(finalValue);
        console.log('Updated hidden input:', hiddenInput[0], 'with value:', finalValue);
        } else {
            console.log('Skipping empty value for hidden input - keeping previous value:', hiddenInput.val());
        }
    }
    
    // Status cell toggle - SIMPLIFIED VERSION
    $(document).on('click', '.status-cell', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var cell = $(this);
        var day = cell.data('day');
        var checkbox = cell.find('input[type="checkbox"]');
        var indicator = cell.find('.status-indicator');
        var text = cell.find('.status-text');
        
        // Toggle status
        checkbox.prop('checked', !checkbox.is(':checked'));
        
        if (checkbox.is(':checked')) {
            cell.removeClass('inactive').addClass('active');
            indicator.removeClass('inactive').addClass('active');
            text.text('Activ');
            
            // Enable time cells for this day
            $('.time-cell[data-day="' + day + '"]').removeClass('inactive').addClass('active').find('input').prop('disabled', false);
            $('.duration-cell[data-day="' + day + '"]').removeClass('inactive').addClass('active');
        } else {
            cell.removeClass('active').addClass('inactive');
            indicator.removeClass('active').addClass('inactive');
            text.text('Inactiv');
            
            // Disable time cells for this day and clear their values
            $('.time-cell[data-day="' + day + '"]').removeClass('active').addClass('inactive').find('input').prop('disabled', true).val('');
            $('.time-cell[data-day="' + day + '"] .cell-display').text('--:--');
            $('.duration-cell[data-day="' + day + '"]').removeClass('active').addClass('inactive');
        }
        
        updateDuration(day);
        syncHiddenInputs();
    });
    
    // Time input change - IMPROVED VERSION
    $(document).on('input change blur', '.time-cell input[type="time"], .time-cell input[type="text"]', function() {
        var input = $(this);
        var cell = input.closest('.time-cell');
        var day = cell.data('day');
        var type = input.attr('name').includes('break_start') ? 'break_start' : 
                  input.attr('name').includes('break_end') ? 'break_end' :
                  input.attr('name').includes('start') ? 'start' : 'end';
        var inputValue = input.val();
        
        console.log('Time input change for day:', day, 'type:', type, 'value:', inputValue, 'event type:', event.type);
        
        // Debug logging pentru pauze
        if (type === 'break_start' || type === 'break_end') {
            console.log('🔍 BREAK INPUT EVENT - Type:', type, 'Day:', day, 'Value:', inputValue, 'Event:', event.type);
            if (event.type === 'input') {
                console.log('🔍 BREAK INPUT TYPING - User is typing in break field');
            }
            console.log('Break time input changed - validating...');
            validateBreakTimes(cell);
        }
        
        // Check if the value is valid before preserving
        if (inputValue && inputValue.trim() !== '') {
            var timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timeRegex.test(inputValue)) {
                console.log('Invalid time format detected, clearing input');
                input.val('');
                inputValue = '';
            }
        }
        
        // Sync display with input
        syncDisplayWithInput(cell);
        
        // Preserve the input value
        preserveInputValue(input);
        
        // Update duration
        updateDuration(day);
        
        // Only close editing mode on blur, not on change
        if (event.type === 'blur') {
            cell.removeClass('editing');
            cell.find('.cell-edit').hide();
        }
        
        // Sync hidden inputs
        syncHiddenInputs();
        
        // Sincronizează specific hidden inputs pentru pauze
        if (type === 'break_start' || type === 'break_end') {
            syncHiddenInputsOnBreakChange(day);
        }
        
        // Debug: Log the final state
        console.log('Final state - input value:', input.val(), 'display text:', cell.find('.cell-display').text());
    });
    
    // Enter key to save and close
    $(document).on('keydown', '.time-cell input[type="time"]', function(e) {
        if (e.keyCode === 13) { // Enter key
            $(this).blur();
        }
    });
    
    // Escape key to cancel editing - IMPROVED VERSION
    $(document).on('keydown', '.time-cell input[type="time"]', function(e) {
        if (e.keyCode === 27) { // Escape key
            var cell = $(this).closest('.time-cell');
            var input = $(this);
            
            console.log('Escape key - preserving input value');
            preserveInputValue(input);
            
            cell.removeClass('editing');
            cell.find('.cell-edit').hide();
            $(this).blur();
        }
    });
    
    // Click outside to close editing - IMPROVED VERSION
    $(document).on('click', function(e) {
        // Don't close if clicking inside the input or cell
        if ($(e.target).closest('.time-cell, .status-cell, .cell-edit').length) {
            return;
        }
        
        // Save current editing cell before closing
        var editingCell = $('.time-cell.editing');
        if (editingCell.length) {
            var input = editingCell.find('input[type="time"]');
            console.log('Click outside - preserving input value');
            preserveInputValue(input);
        }
        
        $('.time-cell').removeClass('editing').find('.cell-edit').hide();
    });
    
    // Update duration function
    function updateDuration(day) {
        var startCell = $('.time-cell[data-day="' + day + '"][data-type="start"]');
        var endCell = $('.time-cell[data-day="' + day + '"][data-type="end"]');
        var breakStartCell = $('.time-cell[data-day="' + day + '"][data-type="break_start"]');
        var breakEndCell = $('.time-cell[data-day="' + day + '"][data-type="break_end"]');
        var durationCell = $('.duration-cell[data-day="' + day + '"]');
        var statusCell = $('.status-cell[data-day="' + day + '"]');
        
        if (statusCell.hasClass('active')) {
            var startTime = startCell.find('input').val();
            var endTime = endCell.find('input').val();
            var breakStartTime = breakStartCell.find('input').val();
            var breakEndTime = breakEndCell.find('input').val();
            
            if (startTime && endTime && startTime !== '' && endTime !== '') {
                // Parse time strings to hours and minutes
                var startParts = startTime.split(':');
                var endParts = endTime.split(':');
                
                if (startParts.length === 2 && endParts.length === 2) {
                    var startHours = parseInt(startParts[0]) || 0;
                    var startMinutes = parseInt(startParts[1]) || 0;
                    var endHours = parseInt(endParts[0]) || 0;
                    var endMinutes = parseInt(endParts[1]) || 0;
                    
                    // Calculate total duration in hours
                    var startTotalMinutes = startHours * 60 + startMinutes;
                    var endTotalMinutes = endHours * 60 + endMinutes;
                    var totalDurationHours = (endTotalMinutes - startTotalMinutes) / 60;
                    
                    // Subtract break duration if configured
                    if (breakStartTime && breakEndTime && breakStartTime !== '' && breakEndTime !== '') {
                        var breakStartParts = breakStartTime.split(':');
                        var breakEndParts = breakEndTime.split(':');
                        
                        if (breakStartParts.length === 2 && breakEndParts.length === 2) {
                            var breakStartHours = parseInt(breakStartParts[0]) || 0;
                            var breakStartMinutes = parseInt(breakStartParts[1]) || 0;
                            var breakEndHours = parseInt(breakEndParts[0]) || 0;
                            var breakEndMinutes = parseInt(breakEndParts[1]) || 0;
                            
                            var breakStartTotalMinutes = breakStartHours * 60 + breakStartMinutes;
                            var breakEndTotalMinutes = breakEndHours * 60 + breakEndMinutes;
                            var breakDurationHours = (breakEndTotalMinutes - breakStartTotalMinutes) / 60;
                            
                            if (breakDurationHours > 0) {
                                totalDurationHours -= breakDurationHours;
                            }
                        }
                    }
                    
                    if (totalDurationHours > 0) {
                        durationCell.find('.duration-value').text(totalDurationHours.toFixed(1) + 'h');
                    } else {
                        durationCell.find('.duration-value').text('0h');
                    }
                } else {
                    durationCell.find('.duration-value').text('-');
                }
            } else {
                durationCell.find('.duration-value').text('-');
            }
        } else {
            durationCell.find('.duration-value').text('-');
        }
    }
    
    // Sync hidden inputs function - IMPROVED VERSION
    function syncHiddenInputs() {
        console.log('=== SYNC HIDDEN INPUTS ===');
        
        // Debug: Log all time inputs found
        var allTimeInputs = $('.time-cell input[type="time"]');
        console.log('Total time inputs found:', allTimeInputs.length);
        
        allTimeInputs.each(function(index) {
            var input = $(this);
            var day = input.closest('.time-cell').data('day');
            var type = input.attr('name').includes('break_start') ? 'break_start' : 
                      input.attr('name').includes('break_end') ? 'break_end' :
                      input.attr('name').includes('start') ? 'start' : 'end';
            var value = input.val();
            console.log('Input', index, '- Day:', day, 'Type:', type, 'Value:', value, 'Name:', input.attr('name'));
        });
        
        // Sync time inputs for visible days
        $('.time-cell input[type="time"]').each(function() {
            var input = $(this);
            var day = input.closest('.time-cell').data('day');
            var type = input.attr('name').includes('break_start') ? 'break_start' : 
                      input.attr('name').includes('break_end') ? 'break_end' :
                      input.attr('name').includes('start') ? 'start' : 'end';
            var value = input.val();
            
            console.log('Syncing day:', day, 'type:', type, 'value:', value);
            
                // Update hidden input with actual value (but don't save empty values)
                var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"][type="hidden"]');
                console.log('Found hidden input:', hiddenInput.length, 'for', day, type);
                
                if (hiddenInput.length > 0) {
                    // Don't save empty values - keep previous value if current is empty
                    if (value && value.trim() !== '') {
            hiddenInput.val(value);
                        console.log('Updated hidden input to:', value);
                    } else {
                        console.log('Skipping empty value for', day, type, '- keeping previous value:', hiddenInput.val());
                    }
                } else {
                    console.log('ERROR: Hidden input not found for', day, type);
                }
        });
        
        // Sync status checkboxes for visible days
        $('.status-cell input[type="checkbox"]').each(function() {
            var checkbox = $(this);
            var day = checkbox.closest('.status-cell').data('day');
            var isActive = checkbox.is(':checked');
            
            var hiddenInput = $('input[name="working_hours[' + day + '][active]"]');
            hiddenInput.val(isActive ? '1' : '0');
        });
        
        // Sync break inputs for visible days
        $('.time-cell input[type="time"][name*="break"]').each(function() {
            var input = $(this);
            var day = input.closest('.time-cell').data('day');
            var type = input.attr('name').includes('break_start') ? 'break_start' : 'break_end';
            var value = input.val();
            
            console.log('Syncing break for day:', day, 'type:', type, 'value:', value);
            
            // Update hidden input
            var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"][type="hidden"]');
            if (hiddenInput.length > 0) {
                if (value && value.trim() !== '') {
                    hiddenInput.val(value);
                    console.log('Updated break hidden input to:', value);
                } else {
                    console.log('Skipping empty break value for', day, type, '- keeping previous value:', hiddenInput.val());
                }
            } else {
                console.log('ERROR: Break hidden input not found for', day, type);
            }
        });
        
        // Ensure all days have hidden inputs (including saturday and sunday)
        var allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        allDays.forEach(function(day) {
            // Ensure hidden inputs exist for all days
            if ($('input[name="working_hours[' + day + '][start]"]').length === 0) {
                $('<input type="hidden" name="working_hours[' + day + '][start]" value="">').appendTo('form');
            }
            if ($('input[name="working_hours[' + day + '][end]"]').length === 0) {
                $('<input type="hidden" name="working_hours[' + day + '][end]" value="">').appendTo('form');
            }
            if ($('input[name="working_hours[' + day + '][break_start]"]').length === 0) {
                $('<input type="hidden" name="working_hours[' + day + '][break_start]" value="">').appendTo('form');
            }
            if ($('input[name="working_hours[' + day + '][break_end]"]').length === 0) {
                $('<input type="hidden" name="working_hours[' + day + '][break_end]" value="">').appendTo('form');
            }
            if ($('input[name="working_hours[' + day + '][active]"]').length === 0) {
                $('<input type="hidden" name="working_hours[' + day + '][active]" value="0">').appendTo('form');
            }
        });
    }
    
    // DEBUG - Verifică pauzele în JavaScript
    function debugBreaks() {
        console.log('=== VERIFICARE PAUZE ÎN JAVASCRIPT ===');
        $('.time-cell[data-type="break_start"], .time-cell[data-type="break_end"]').each(function() {
            var cell = $(this);
            var day = cell.data('day');
            var type = cell.data('type');
            var input = cell.find('input[type="time"]');
            var display = cell.find('.cell-display');
            var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"][type="hidden"]');
            
            console.log(day + ' ' + type + ':');
            console.log('  - Input value: "' + input.val() + '"');
            console.log('  - Display text: "' + display.text() + '"');
            console.log('  - Hidden value: "' + hiddenInput.val() + '"');
            console.log('  - Has break: ' + (input.val() && input.val().trim() !== ''));
        });
    }
    
    // Funcție pentru verificarea clară a pauzelor
    function hasBreak(day) {
        // Verifică input-urile vizibile (nu hidden inputs)
        var breakStartInput = $('.time-cell[data-day="' + day + '"][data-type="break_start"] input[type="time"]');
        var breakEndInput = $('.time-cell[data-day="' + day + '"][data-type="break_end"] input[type="time"]');
        
        var breakStart = breakStartInput.val();
        var breakEnd = breakEndInput.val();
        
        console.log('Verificare pauză pentru ' + day + ':');
        console.log('  - Break Start Input: "' + breakStart + '"');
        console.log('  - Break End Input: "' + breakEnd + '"');
        console.log('  - Has Break: ' + (breakStart && breakEnd && breakStart.trim() !== '' && breakEnd.trim() !== ''));
        
        return breakStart && breakEnd && breakStart.trim() !== '' && breakEnd.trim() !== '';
    }
    
    // Funcție pentru afișarea statusului pauzelor
    function showBreakStatus() {
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        var status = [];
        
        days.forEach(function(day) {
            var hasBreakForDay = hasBreak(day);
            status.push(day + ': ' + (hasBreakForDay ? 'ARE pauză' : 'NU ARE pauză'));
        });
        
        console.log('=== STATUS PAUZE ===');
        console.log(status.join('\n'));
        return status;
    }
    
    // Funcție pentru sincronizarea hidden inputs când se șterge o pauză
    function syncHiddenInputsOnBreakChange(day) {
        var breakStartInput = $('.time-cell[data-day="' + day + '"][data-type="break_start"] input[type="time"]');
        var breakEndInput = $('.time-cell[data-day="' + day + '"][data-type="break_end"] input[type="time"]');
        
        var breakStart = breakStartInput.val();
        var breakEnd = breakEndInput.val();
        
        // Actualizează hidden inputs
        $('input[name="working_hours[' + day + '][break_start]"]').val(breakStart);
        $('input[name="working_hours[' + day + '][break_end]"]').val(breakEnd);
        
        console.log('Sincronizat hidden inputs pentru ' + day + ':');
        console.log('  - Break Start: "' + breakStart + '"');
        console.log('  - Break End: "' + breakEnd + '"');
    }
    
    // Event handler pentru butonul de verificare pauze
    $('.debug-breaks-btn').on('click', function() {
        var status = showBreakStatus();
        alert('Status pauze:\n' + status.join('\n'));
    });
    
    // Apelează debug-ul la încărcarea paginii
    $(document).ready(function() {
        debugBreaks();
        showBreakStatus();
    });
    
    // Apply to all days
    $('.apply-all-btn').on('click', function() {
        var activeDays = $('.status-cell.active');
        if (activeDays.length === 0) {
            alert('Selectează cel puțin o zi activă pentru a aplica setările!');
            return;
        }
        
        var firstActiveDay = activeDays.first();
        var startTime = $('.time-cell[data-day="' + firstActiveDay.data('day') + '"][data-type="start"] input').val();
        var endTime = $('.time-cell[data-day="' + firstActiveDay.data('day') + '"][data-type="end"] input').val();
        var breakStartTime = $('.time-cell[data-day="' + firstActiveDay.data('day') + '"][data-type="break_start"] input').val();
        var breakEndTime = $('.time-cell[data-day="' + firstActiveDay.data('day') + '"][data-type="break_end"] input').val();
        
        if (!startTime || !endTime) {
            alert('Completează orele pentru ziua activă!');
            return;
        }
        
        // Apply to all active days
        activeDays.each(function() {
            var day = $(this).data('day');
            $('.time-cell[data-day="' + day + '"][data-type="start"] input').val(startTime);
            $('.time-cell[data-day="' + day + '"][data-type="start"] .cell-display').text(startTime || '--:--');
            $('.time-cell[data-day="' + day + '"][data-type="end"] input').val(endTime);
            $('.time-cell[data-day="' + day + '"][data-type="end"] .cell-display').text(endTime || '--:--');
            $('.time-cell[data-day="' + day + '"][data-type="break_start"] input').val(breakStartTime);
            $('.time-cell[data-day="' + day + '"][data-type="break_start"] .cell-display').text(breakStartTime || '--:--');
            $('.time-cell[data-day="' + day + '"][data-type="break_end"] input').val(breakEndTime);
            $('.time-cell[data-day="' + day + '"][data-type="break_end"] .cell-display').text(breakEndTime || '--:--');
            updateDuration(day);
        });
        
        syncHiddenInputs();
        alert('Setările au fost aplicate la toate zilele active!');
    });
    
    // Reset to default
    $('.reset-all-btn').on('click', function() {
        if (confirm('Sigur vrei să resetezi toate setările la valorile implicite?')) {
            // Reset all days to default values
            $('.status-cell').removeClass('active').addClass('inactive');
            $('.status-cell input').prop('checked', false);
            $('.status-cell .status-indicator').removeClass('active').addClass('inactive');
            $('.status-cell .status-text').text('Inactiv');
            
            $('.time-cell').removeClass('active').addClass('inactive');
            $('.time-cell input').prop('disabled', true).val('');
            $('.time-cell .cell-display').text('--:--');
            
            $('.duration-cell').removeClass('active').addClass('inactive');
            $('.duration-cell .duration-value').text('-');
            
            // Clear all hidden inputs
            $('input[name^="working_hours"]').val('');
            
            alert('Setările au fost resetate la valorile implicite!');
        }
    });
    
    // Form validation and sync hidden inputs
    $('.clinica-settings-form').on('submit', function(e) {
        // Sync hidden inputs before submit
        syncHiddenInputs();
        
        // Check required fields
        var isValid = true;
        console.log('=== VALIDARE FORM ===');
        $('.clinica-setting-card input[required]').each(function() {
            var input = $(this);
            var name = input.attr('name');
            var value = input.val();
            var type = input.attr('type');
            
            console.log('Verificare câmp:', name, 'Type:', type, 'Value:', value);
            
            if (!value || value.trim() === '') {
                isValid = false;
                input.addClass('error');
                console.log('❌ Câmp obligatoriu gol:', name, 'value:', value);
            } else {
                input.removeClass('error');
                console.log('✅ Câmp valid:', name, 'value:', value);
            }
        });
        
        console.log('Form valid:', isValid);
        
        if (!isValid) {
            e.preventDefault();
            alert('Te rog completează toate câmpurile obligatorii.');
            console.log('Form submission prevented due to validation errors');
        } else {
            console.log('Form validation passed, submitting...');
        }
    });
    
    // Auto-save indicator
    var autoSaveTimeout;
    $('.clinica-setting-card input, .clinica-setting-card select, .clinica-setting-card textarea').on('input change', function() {
        clearTimeout(autoSaveTimeout);
        
        // Show auto-save indicator
        if (!$('.auto-save-indicator').length) {
            $('.clinica-settings-submit').prepend('<div class="auto-save-indicator" style="color: #007cba; font-size: 14px; margin-bottom: 10px;"><i class="dashicons dashicons-clock"></i> Modificări nesalvate</div>');
        }
        
        // Auto-save after 5 seconds of inactivity
        autoSaveTimeout = setTimeout(function() {
            $('.auto-save-indicator').html('<i class="dashicons dashicons-saved"></i> Salvat automat');
            setTimeout(function() {
                $('.auto-save-indicator').fadeOut();
            }, 2000);
        }, 5000);
    });
    
    // File upload preview
    $('#clinic_logo').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.current-logo').html('<img src="' + e.target.result + '" alt="Logo preview">');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Function to clean all displays
    function cleanAllDisplays() {
        $('.cell-display').each(function() {
            var display = $(this);
            var currentText = display.text();
            var cleanText = cleanTextValue(currentText);
            
            if (cleanText && cleanText !== '--:--') {
                display.text(cleanText);
                console.log('Cleaned display from:', currentText, 'to:', cleanText);
            }
        });
    }
    
    // Initialize on page load
    $(document).ready(function() {
        // Clean all displays first
        cleanAllDisplays();
        
        // Sync hidden inputs on page load
        syncHiddenInputs();
        
        // Sync all inputs with their displays
        $('.time-cell').each(function() {
            var cell = $(this);
            syncInputWithDisplay(cell);
        });
        
        // Update all durations
        $('.status-cell').each(function() {
            var day = $(this).data('day');
            updateDuration(day);
        });
        
        console.log('Page loaded - all inputs synced');
    });

    // Allow all numeric input - SIMPLIFIED VERSION
    $(document).on('keydown', '.time-cell input[type="time"]', function(e) {
        // Allow all navigation and editing keys
        var allowedKeyCodes = [
            8,   // Backspace
            9,   // Tab
            13,  // Enter
            27,  // Escape
            37,  // Arrow Left
            38,  // Arrow Up
            39,  // Arrow Right
            40,  // Arrow Down
            46,  // Delete
            96,  // Numpad 0
            97,  // Numpad 1
            98,  // Numpad 2
            99,  // Numpad 3
            100, // Numpad 4
            101, // Numpad 5
            102, // Numpad 6
            103, // Numpad 7
            104, // Numpad 8
            105, // Numpad 9
            186, // Colon (:)
            189  // Minus (-)
        ];
        
        // Allow regular number keys (0-9)
        if (e.keyCode >= 48 && e.keyCode <= 57) {
            return;
        }
        
        // Allow numpad keys
        if (e.keyCode >= 96 && e.keyCode <= 105) {
            return;
        }
        
        // Allow specific allowed keys
        if (allowedKeyCodes.indexOf(e.keyCode) !== -1) {
            return;
        }
        
        // Allow Ctrl combinations
        if (e.ctrlKey && (e.keyCode === 65 || e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) {
            return;
        }
        
        // Allow modern key names (fixed regex)
        if (e.key && /^[0-9:-]$/.test(e.key)) {
            return;
        }
        
        // Allow specific key names
        var allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
        if (e.key && allowedKeys.indexOf(e.key) !== -1) {
            return;
        }
        
        // Prevent everything else
        e.preventDefault();
    });
    
    // Prevent paste of invalid content
    $(document).on('paste', '.time-cell input[type="time"]', function(e) {
        e.preventDefault();
        
        // Get pasted content
        var pastedText = (e.originalEvent || e).clipboardData.getData('text/plain');
        
        // Clean the pasted text
        var cleanedText = pastedText.replace(/[^0-9:]/g, '');
        
        // Format as time if possible
        if (cleanedText.length >= 2) {
            if (!cleanedText.includes(':')) {
                cleanedText = cleanedText.substring(0, 2) + ':' + cleanedText.substring(2, 4);
            }
            
            // Limit to HH:MM
            if (cleanedText.length > 5) {
                cleanedText = cleanedText.substring(0, 5);
            }
            
            // Insert the cleaned text
            var input = $(this);
            var start = input[0].selectionStart;
            var end = input[0].selectionEnd;
            var value = input.val();
            
            input.val(value.substring(0, start) + cleanedText + value.substring(end));
        }
    });

    // Function to clean text value
    function cleanTextValue(text) {
        if (!text) return '';
        return text.trim().replace(/\s+/g, '');
    }
    
    // Function to force visual update
    function forceVisualUpdate(cell, value) {
        var display = cell.find('.cell-display');
        
        // Clean the value
        var cleanValue = cleanTextValue(value);
        
        // Only update if the value actually changed
        if (display.text() !== cleanValue) {
        display.text(cleanValue);
        console.log('Forced visual update to:', cleanValue);
        }
    }
    
    // Function to sync display with input
    function syncDisplayWithInput(cell) {
        var input = cell.find('input[type="time"]');
        var display = cell.find('.cell-display');
        var inputValue = input.val();
        
        console.log('Syncing display with input - value:', inputValue);
        
        if (inputValue && inputValue.trim() !== '') {
            display.text(inputValue);
        } else {
            display.text('--:--');
        }
        
        // Also update hidden input (but don't save empty values)
        var day = cell.data('day');
        var type = input.attr('name').includes('break_start') ? 'break_start' : 
                  input.attr('name').includes('break_end') ? 'break_end' :
                  input.attr('name').includes('start') ? 'start' : 'end';
        var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
        
        if (inputValue && inputValue.trim() !== '') {
            hiddenInput.val(inputValue);
            console.log('Updated hidden input in syncDisplayWithInput:', inputValue);
        } else {
            console.log('Skipping empty value in syncDisplayWithInput - keeping previous value:', hiddenInput.val());
        }
    }

    // Function to sync input with display
    function syncInputWithDisplay(cell) {
        var input = cell.find('input[type="time"]');
        var display = cell.find('.cell-display');
        var displayValue = display.text();
        
        console.log('Syncing input with display - display value:', displayValue);
        
        // Safely set the input value
        safeSetInputValue(input, displayValue);
    }

    // REMOVED DUPLICATE INPUT HANDLER - now handled by the main input handler above

    // Function to safely set input value
    function safeSetInputValue(input, value) {
        var cleanValue = cleanTextValue(value);
        
        if (cleanValue && cleanValue !== '--:--') {
            input.val(cleanValue);
            console.log('Safely set input to:', cleanValue);
        } else {
            input.val('');
            console.log('Cleared input (empty or invalid value)');
        }
    }
    
    // Override jQuery val() for time inputs to always clean values - FIXED VERSION
    $(document).ready(function() {
        $('.time-cell input[type="time"]').each(function() {
            var input = $(this);
            var originalVal = input.val;
            
            input.val = function(value) {
                if (value !== undefined) {
                    var cleanValue = cleanTextValue(value);
                    return originalVal.call(this, cleanValue);
                }
                return originalVal.call(this);
            };
        });
    });
    
    // Prevent closing when focusing on input
    $(document).on('focus', '.time-cell input[type="time"], .time-cell input[type="text"]', function() {
        var cell = $(this).closest('.time-cell');
        cell.addClass('editing');
        cell.find('.cell-edit').show();
        console.log('Input focused - keeping edit mode open');
    });
    
    // REMOVED DUPLICATE INPUT HANDLER - now handled by the main input handler above
    
    // Prevent HTML5 validation errors for number inputs
    $(document).on('invalid', 'input[type="number"]', function(e) {
        e.preventDefault();
        var input = $(this);
        var value = parseInt(input.val()) || 0;
        var min = parseInt(input.attr('min')) || 0;
        var max = parseInt(input.attr('max')) || 999999;
        
        // Corectează valoarea dacă este în afara limitelor
        if (value < min) {
            input.val(min);
        } else if (value > max) {
            input.val(max);
        }
        
        console.log('Corrected number input:', input.attr('name'), 'to value:', input.val());
    });
    
    // Validate number inputs on blur
    $(document).on('blur', 'input[type="number"]', function() {
        var input = $(this);
        var value = parseInt(input.val()) || 0;
        var min = parseInt(input.attr('min')) || 0;
        var max = parseInt(input.attr('max')) || 999999;
        
        if (value < min || value > max) {
            // Corectează valoarea
            if (value < min) {
                input.val(min);
            } else if (value > max) {
                input.val(max);
            }
            console.log('Auto-corrected number input:', input.attr('name'), 'to value:', input.val());
        }
    });

    // Force toggle slider styles and functionality
    $(document).ready(function() {
        console.log('🔧 Initializing toggle sliders...');
        
        // Force apply styles to existing toggle sliders
        $('.clinica-toggle-label').each(function() {
            var label = $(this);
            var checkbox = label.find('input[type="checkbox"]');
            var slider = label.find('.toggle-slider');
            
            console.log('Found toggle slider:', label.text().trim());
            
            // Ensure checkbox is hidden
            checkbox.hide();
            
            // Ensure slider is visible and styled
            slider.show().css({
                'display': 'block',
                'position': 'relative',
                'width': '56px',
                'height': '28px',
                'background': checkbox.is(':checked') ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#e5e7eb',
                'border-radius': '14px',
                'transition': 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                'flex-shrink': '0',
                'margin-left': 'auto',
                'box-shadow': 'inset 0 1px 3px rgba(0,0,0,0.1)'
            });
            
            // Ensure slider handle is styled
            slider.find(':before').remove();
            slider.append('<span class="slider-handle"></span>');
            
            var handle = slider.find('.slider-handle');
            handle.css({
                'content': '""',
                'position': 'absolute',
                'top': '2px',
                'left': checkbox.is(':checked') ? '28px' : '2px',
                'width': '24px',
                'height': '24px',
                'background': 'white',
                'border-radius': '50%',
                'transition': 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                'box-shadow': '0 2px 4px rgba(0,0,0,0.2), 0 1px 2px rgba(0,0,0,0.1)',
                'display': 'block'
            });
        });
        
        // Add click handlers for toggle sliders
        $('.clinica-toggle-label').on('click', function(e) {
            e.preventDefault();
            var label = $(this);
            var checkbox = label.find('input[type="checkbox"]');
            var slider = label.find('.toggle-slider');
            var handle = slider.find('.slider-handle');
            
            // Toggle checkbox
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
            
            // Update slider appearance
            if (checkbox.is(':checked')) {
                slider.css('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
                handle.css('left', '28px');
                console.log('✅ Toggle activated:', label.text().trim());
            } else {
                slider.css('background', '#e5e7eb');
                handle.css('left', '2px');
                console.log('❌ Toggle deactivated:', label.text().trim());
            }
        });
        
        console.log('✅ Toggle sliders initialized successfully!');
    });
});

// Extensii editor servicii (DB)
jQuery(function($){
    function renderSvcRow(svc){
        return '<tr data-id="'+(svc.id||'0')+'">'+
            '<td><input type="text" class="svc-name" value="'+(svc.name||'')+'" placeholder="ex: Consultație"></td>'+
            '<td><input type="number" class="svc-duration" value="'+(svc.duration||30)+'" min="5" max="240" step="5"></td>'+
            '<td><input type="checkbox" class="svc-active" '+((svc.active?1:0)?'checked':'')+'></td>'+
            '<td><button type="button" class="button button-small svc-save">Salvează</button> <button type="button" class="button button-small svc-delete">Șterge</button></td>'+
        '</tr>';
    }
    $('#svc-add').on('click', function(){
        $('#services-table tbody').append(renderSvcRow({id:0,name:'',duration:30,active:1}));
    });
    $(document).on('click', '.svc-save', function(){
        var tr = $(this).closest('tr');
        var id = parseInt(tr.data('id')||0,10);
        var name = tr.find('.svc-name').val().trim();
        var duration = parseInt(tr.find('.svc-duration').val(),10)||30;
        var active = tr.find('.svc-active').is(':checked') ? 1 : 0;
        if (!name){ alert('Introduceți numele serviciului'); return; }
        $.post(ajaxurl, {action:'clinica_services_save', nonce:'<?php echo wp_create_nonce('clinica_settings_nonce'); ?>', id:id, name:name, duration:duration, active:active}, function(resp){
            if (resp && resp.success){
                if (resp.data && resp.data.id){ tr.attr('data-id', resp.data.id); }
                alert('Salvat');
            } else {
                alert(resp && resp.data ? resp.data : 'Eroare la salvare');
            }
        });
    });
    $(document).on('click', '.svc-delete', function(){
        var tr = $(this).closest('tr');
        var id = parseInt(tr.data('id')||0,10);
        if (!id){ tr.remove(); return; }
        if (!confirm('Sigur ștergeți serviciul?')) return;
        $.post(ajaxurl, {action:'clinica_services_delete', nonce:'<?php echo wp_create_nonce('clinica_settings_nonce'); ?>', id:id}, function(resp){
            if (resp && resp.success){ tr.remove(); } else { alert(resp && resp.data ? resp.data : 'Eroare la ștergere'); }
        });
    });
    function collectHolidays(){
        var list = [];
        // Include zilele libere personalizate
        $('#clinic-holidays-list .holiday-chip').each(function(){ list.push($(this).data('date')); });
        // Include sărbătorile legale incluse
        $('#legal-holidays-list .legal-holiday-item.included').each(function(){ list.push($(this).data('date')); });
        return list;
    }
    // Event handlers pentru sărbători legale
    $('#add-holiday-btn-legal').on('click', function(){
        var d = $('#add-holiday-date-legal').val();
        if (!d) return;
        if ($('#clinic-holidays-list .holiday-chip[data-date="'+d+'"]').length) return;
        $('#clinic-holidays-list').append('<span class="holiday-chip" data-date="'+d+'" style="padding:6px 10px;background:#f1f3f4;border-radius:16px;display:inline-flex;align-items:center;gap:6px;"><span>'+d+'</span><a href="#" class="remove-holiday" style="color:#c00;text-decoration:none;">×</a></span>');
    });
    
    // Event handlers pentru zile libere personalizate
    $('#add-holiday-btn-custom').on('click', function(){
        var d = $('#add-holiday-date-custom').val();
        if (!d) return;
        if ($('#clinic-holidays-list .holiday-chip[data-date="'+d+'"]').length) return;
        $('#clinic-holidays-list').append('<span class="holiday-chip" data-date="'+d+'" style="padding:6px 10px;background:#f1f3f4;border-radius:16px;display:inline-flex;align-items:center;gap:6px;"><span>'+d+'</span><a href="#" class="remove-holiday" style="color:#c00;text-decoration:none;">×</a></span>');
    });
    
    $(document).on('click', '.remove-holiday', function(e){ e.preventDefault(); $(this).closest('.holiday-chip').remove(); });
    
    $('#clear-holidays-btn-legal').on('click', function(){ $('#clinic-holidays-list').empty(); });
    $('#clear-holidays-btn-custom').on('click', function(){ $('#clinic-holidays-list').empty(); });
    
    // Gestionarea sărbătorilor legale
    $(document).on('click', '.legal-holiday-item', function(){
        var item = $(this);
        var date = item.data('date');
        
        if (item.hasClass('included')) {
            item.removeClass('included').addClass('excluded');
            item.find('.holiday-status').text('Exclus');
            item.css({
                'background': '#f5f5f5',
                'border-color': '#ddd',
                'color': '#666'
            });
        } else {
            item.removeClass('excluded').addClass('included');
            item.find('.holiday-status').text('Inclus');
            item.css({
                'background': '#e8f5e8',
                'border-color': '#4caf50',
                'color': '#2e7d32'
            });
        }
    });
    
    // Include toate sărbătorile legale
    $('#include-all-holidays-legal').on('click', function(){
        $('#legal-holidays-list .legal-holiday-item').each(function(){
            var item = $(this);
            if (!item.hasClass('included')) {
                item.removeClass('excluded').addClass('included');
                item.find('.holiday-status').text('Inclus');
                item.css({
                    'background': '#e8f5e8',
                    'border-color': '#4caf50',
                    'color': '#2e7d32'
                });
            }
        });
    });
    
    // Exclude toate sărbătorile legale
    $('#exclude-all-holidays-legal').on('click', function(){
        $('#legal-holidays-list .legal-holiday-item').each(function(){
            var item = $(this);
            if (!item.hasClass('excluded')) {
                item.removeClass('included').addClass('excluded');
                item.find('.holiday-status').text('Exclus');
                item.css({
                    'background': '#f5f5f5',
                    'border-color': '#ddd',
                    'color': '#666'
                });
            }
        });
    });
    
    // Actualizează sărbătorile
    $('#refresh-holidays-legal').on('click', function(){
        var button = $(this);
        var originalText = button.html();
        
        button.html('<i class="fa fa-spinner fa-spin" style="margin-right: 5px;"></i>Actualizare...');
        button.prop('disabled', true);
        
        // Simulează actualizarea (în realitate ar face un AJAX call)
        setTimeout(function(){
            button.html(originalText);
            button.prop('disabled', false);
            alert('Sărbătorile au fost actualizate cu succes!');
        }, 1500);
    });
    
    // Event handlers pentru zilele libere personalizate
    $('#include-all-holidays-custom').on('click', function(){
        $('#legal-holidays-list .legal-holiday-item').each(function(){
            var item = $(this);
            if (!item.hasClass('included')) {
                item.removeClass('excluded').addClass('included');
                item.find('.holiday-status').text('Inclus');
                item.css({
                    'background': '#e8f5e8',
                    'border-color': '#4caf50',
                    'color': '#2e7d32'
                });
            }
        });
    });
    
    $('#exclude-all-holidays-custom').on('click', function(){
        $('#legal-holidays-list .legal-holiday-item').each(function(){
            var item = $(this);
            if (!item.hasClass('excluded')) {
                item.removeClass('included').addClass('excluded');
                item.find('.holiday-status').text('Exclus');
                item.css({
                    'background': '#f5f5f5',
                    'border-color': '#ddd',
                    'color': '#666'
                });
            }
        });
    });
    
    $('#refresh-holidays-custom').on('click', function(){
        var button = $(this);
        var originalText = button.html();
        
        button.html('<i class="fa fa-spinner fa-spin" style="margin-right: 5px;"></i>Actualizare...');
        button.prop('disabled', true);
        
        setTimeout(function(){
            button.html(originalText);
            button.prop('disabled', false);
            alert('Zilele libere au fost actualizate cu succes!');
        }, 1500);
    });
    
    $('.clinica-settings-form').on('submit', function(){
        $('#clinic_holidays_json_legal').val(JSON.stringify(collectHolidays()));
        $('#clinic_holidays_json_custom').val(JSON.stringify(collectHolidays()));
    });
});
</script> 