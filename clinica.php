<?php
/**
 * Plugin Name: Clinica - Sistem de Gestionare Medicala
 * Plugin URI: https://clinica.ro
 * Description: Sistem complet de gestionare medicala cu programari, pacienti, dosare medicale si rapoarte. Suport pentru CNP-uri romanesti si straine.
 * Version: 1.0.0
 * Author: Clinica Team
 * Author URI: https://clinica.ro
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clinica
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Previne accesul direct
if (!defined('ABSPATH')) {
    exit;
}

// Defineste constantele plugin-ului
define('CLINICA_VERSION', '1.0.0');
define('CLINICA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLINICA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CLINICA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clasa principala a plugin-ului Clinica
 */
class Clinica_Plugin {
    
    /**
     * Instanta singleton
     */
    private static $instance = null;
    
    /**
     * Manager-ul de setări
     */
    private $settings = null;
    
    /**
     * Manager dashboard instance
     */
    private $manager_dashboard = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Returneaza instanCia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initializeaza hook-urile
     */
    private function init_hooks() {
        // Hook-uri de activare/dezactivare
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hook-uri de iniCiializare
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Ascunde admin bar-ul pentru rolurile Clinica (în afara de administratorul WordPress)
        add_action('init', array($this, 'hide_admin_bar_for_clinica_roles'));
        
        // Hook-uri pentru admin
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        // AJAX admin Doctors
        add_action('wp_ajax_clinica_get_doctor_schedule', array($this, 'ajax_get_doctor_schedule'));
        add_action('wp_ajax_clinica_save_doctor_schedule', array($this, 'ajax_save_doctor_schedule'));
        // AJAX services CRUD
        add_action('wp_ajax_clinica_services_save', array($this, 'ajax_services_save'));
        add_action('wp_ajax_clinica_services_delete', array($this, 'ajax_services_delete'));
        // Doctor profile: working hours per doctor
        add_action('show_user_profile', array($this, 'render_doctor_working_hours_profile'));
        add_action('edit_user_profile', array($this, 'render_doctor_working_hours_profile'));
        add_action('personal_options_update', array($this, 'save_doctor_working_hours_profile'));
        add_action('edit_user_profile_update', array($this, 'save_doctor_working_hours_profile'));
        
        // Hook-uri pentru frontend
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Hook pentru actualizarea automată a statusurilor programărilor
        add_action('clinica_auto_update_appointment_status', array($this, 'auto_update_appointment_status'));
        
        // Adaugă intervalul personalizat pentru cron job
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        
        // Hook-uri pentru API
        add_action('rest_api_init', array($this, 'register_api_routes'));
        
        // Hook-uri pentru securitate
        add_action('wp_loaded', array($this, 'check_permissions'));
        
        // TEMPORAR: Forțează actualizarea rolurilor la fiecare încărcare a admin-ului
        add_action('admin_init', array($this, 'force_update_roles_temp'));
        
        // AJAX handlers pentru gestionarea pacientilor
        add_action('wp_ajax_clinica_get_patient_data', array($this, 'ajax_get_patient_data'));
        add_action('wp_ajax_clinica_update_patient', array($this, 'ajax_update_patient'));
        
        // AJAX handlers pentru statusul pacienților
        add_action('wp_ajax_clinica_update_patient_status', array($this, 'ajax_update_patient_status'));
        add_action('wp_ajax_clinica_block_patient', array($this, 'ajax_block_patient'));
        add_action('wp_ajax_clinica_unblock_patient', array($this, 'ajax_unblock_patient'));
        add_action('wp_ajax_clinica_reactivate_patient', array($this, 'ajax_reactivate_patient'));
        add_action('wp_ajax_clinica_set_inactive_reason', array($this, 'ajax_set_inactive_reason'));
        add_action('wp_ajax_clinica_get_inactive_reason', array($this, 'ajax_get_inactive_reason'));
        // Sync emails with progress
        add_action('wp_ajax_clinica_sync_emails_progress', array($this, 'ajax_sync_emails_progress'));
        // Sync patients with progress
        add_action('wp_ajax_clinica_sync_patients_progress', array($this, 'ajax_sync_patients_progress'));
        // Logs - fetch recent errors & download
        add_action('wp_ajax_clinica_get_sync_errors', array($this, 'ajax_get_sync_errors'));
        add_action('wp_ajax_clinica_download_sync_log', array($this, 'ajax_download_sync_log'));
        add_action('wp_ajax_clinica_archive_sync_log', array($this, 'ajax_archive_sync_log'));
        
        // AJAX handlers pentru autosuggest
        add_action('wp_ajax_clinica_search_patients_suggestions', array($this, 'ajax_search_patients_suggestions'));
        add_action('wp_ajax_clinica_search_families_suggestions', array($this, 'ajax_search_families_suggestions'));
        
        // AJAX handlers pentru log-uri familii
        add_action('wp_ajax_clinica_get_family_logs', array($this, 'ajax_get_family_logs'));
        add_action('wp_ajax_clinica_export_family_logs', array($this, 'ajax_export_family_logs'));
        add_action('wp_ajax_clinica_clear_old_logs', array($this, 'ajax_clear_old_logs'));
        
        // AJAX handlers pentru dashboard-uri
        add_action('wp_ajax_clinica_get_doctor_overview', array($this, 'ajax_get_doctor_overview'));
        add_action('wp_ajax_clinica_get_doctor_activities', array($this, 'ajax_get_doctor_activities'));
        add_action('wp_ajax_clinica_get_doctor_appointments', array($this, 'ajax_get_doctor_appointments'));
        add_action('wp_ajax_clinica_get_doctor_patients', array($this, 'ajax_get_doctor_patients'));
        add_action('wp_ajax_clinica_get_doctor_patients_select', array($this, 'ajax_get_doctor_patients_select'));
        add_action('wp_ajax_clinica_get_doctor_medical_records', array($this, 'ajax_get_doctor_medical_records'));
        add_action('wp_ajax_clinica_update_doctor_appointment_status', array($this, 'ajax_update_doctor_appointment_status'));
        add_action('wp_ajax_clinica_add_doctor_medical_note', array($this, 'ajax_add_doctor_medical_note'));
        
        add_action('wp_ajax_clinica_get_assistant_overview', array($this, 'ajax_get_assistant_overview'));
        add_action('wp_ajax_clinica_get_assistant_appointments', array($this, 'ajax_get_assistant_appointments'));
        add_action('wp_ajax_clinica_get_assistant_patients', array($this, 'ajax_get_assistant_patients'));
        add_action('wp_ajax_clinica_create_assistant_appointment', array($this, 'ajax_create_assistant_appointment'));
        add_action('wp_ajax_clinica_update_assistant_appointment_status', array($this, 'ajax_update_assistant_appointment_status'));
        
        add_action('wp_ajax_clinica_receptionist_overview', array($this, 'ajax_receptionist_overview'));
        add_action('wp_ajax_clinica_receptionist_appointments', array($this, 'ajax_receptionist_appointments'));
        add_action('wp_ajax_clinica_receptionist_patients', array($this, 'ajax_receptionist_patients'));
        add_action('wp_ajax_clinica_receptionist_calendar', array($this, 'ajax_receptionist_calendar'));
        add_action('wp_ajax_clinica_receptionist_reports', array($this, 'ajax_receptionist_reports'));
        
        // AJAX handlers pentru validare CNP si generare parola
        add_action('wp_ajax_clinica_validate_cnp', array($this, 'ajax_validate_cnp'));
        add_action('wp_ajax_clinica_generate_password', array($this, 'ajax_generate_password'));
        add_action('wp_ajax_clinica_create_patient', array($this, 'ajax_create_patient'));
        
        // AJAX handlers pentru debug
        add_action('wp_ajax_clinica_test_db_connection', array($this, 'ajax_test_db_connection'));
        add_action('wp_ajax_clinica_test_patient_query', array($this, 'ajax_test_patient_query'));
        add_action('wp_ajax_clinica_create_test_patient', array($this, 'ajax_create_test_patient'));
        
        // AJAX handlers pentru corectare probleme
        add_action('wp_ajax_clinica_create_sample_patients', array($this, 'ajax_create_sample_patients'));
        add_action('wp_ajax_clinica_create_patients_table', array($this, 'ajax_create_patients_table'));
        add_action('wp_ajax_clinica_fix_permissions', array($this, 'ajax_fix_permissions'));
        add_action('wp_ajax_clinica_sync_patients', array($this, 'ajax_sync_patients'));
        add_action('wp_ajax_clinica_create_missing_users', array($this, 'ajax_create_missing_users'));
        add_action('wp_ajax_clinica_test_page_access', array($this, 'ajax_test_page_access'));
        add_action('wp_ajax_clinica_final_check', array($this, 'ajax_final_check'));
        
        // AJAX handlers pentru corectare nume
        add_action('wp_ajax_clinica_auto_fix_dash_name', array($this, 'ajax_auto_fix_dash_name'));
        
        // Live Updates AJAX handlers
        add_action('wp_ajax_clinica_appointments_digest', array($this, 'ajax_appointments_digest'));
        add_action('wp_ajax_clinica_appointments_changes', array($this, 'ajax_appointments_changes'));
    }

    /**
     * Randează câmpul de program per-doctor în profil utilizator
     */
    public function render_doctor_working_hours_profile($user) {
        if (!in_array('clinica_doctor', (array) $user->roles) && !in_array('clinica_manager', (array) $user->roles)) {
            return;
        }
        $schedule = get_user_meta($user->ID, 'clinica_working_hours', true);
        if (is_string($schedule)) { $schedule = json_decode($schedule, true); }
        if (!is_array($schedule)) { $schedule = array(); }
        $days = array('monday'=>'Luni','tuesday'=>'Marți','wednesday'=>'Miercuri','thursday'=>'Joi','friday'=>'Vineri','saturday'=>'Sâmbătă','sunday'=>'Duminică');
        ?>
        <h2>Program de lucru (Clinica)</h2>
        <table class="form-table">
            <tr>
                <th><label>Program per-doctor</label></th>
                <td>
                    <p>Completează intervalele orare pentru fiecare zi. Dacă lași gol, se va folosi programul global.</p>
                    <p><button type="button" class="button" id="clinica-copy-global-hours">Copiază programul clinicii</button></p>
                    <table style="border-collapse:collapse;">
                        <tr>
                            <th style="text-align:left;padding:4px 8px;">Zi</th>
                            <th style="text-align:left;padding:4px 8px;">Activ</th>
                            <th style="text-align:left;padding:4px 8px;">Start</th>
                            <th style="text-align:left;padding:4px 8px;">Sfârșit</th>
                            <th style="text-align:left;padding:4px 8px;">Pauză start</th>
                            <th style="text-align:left;padding:4px 8px;">Pauză sfârșit</th>
                        </tr>
                        <?php foreach ($days as $key=>$label): 
                            $row = isset($schedule[$key]) ? $schedule[$key] : array('active'=>false,'start'=>'','end'=>'');
                        ?>
                        <tr>
                            <td style="padding:4px 8px;">&nbsp;<?php echo esc_html($label); ?></td>
                            <td style="padding:4px 8px;"><input type="checkbox" name="clinica_working_hours[<?php echo $key; ?>][active]" value="1" <?php checked(!empty($row['active'])); ?>></td>
                            <td style="padding:4px 8px;"><input type="time" name="clinica_working_hours[<?php echo $key; ?>][start]" value="<?php echo esc_attr(isset($row['start'])?$row['start']:''); ?>"></td>
                            <td style="padding:4px 8px;"><input type="time" name="clinica_working_hours[<?php echo $key; ?>][end]" value="<?php echo esc_attr(isset($row['end'])?$row['end']:''); ?>"></td>
                            <td style="padding:4px 8px;"><input type="time" name="clinica_working_hours[<?php echo $key; ?>][break_start]" value="<?php echo esc_attr(isset($row['break_start'])?$row['break_start']:''); ?>"></td>
                            <td style="padding:4px 8px;"><input type="time" name="clinica_working_hours[<?php echo $key; ?>][break_end]" value="<?php echo esc_attr(isset($row['break_end'])?$row['break_end']:''); ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <script>
                    jQuery(function($){
                        $('#clinica-copy-global-hours').on('click', function(e){
                            e.preventDefault();
                            var days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                            try {
                                var global = <?php 
                                    $settings = Clinica_Settings::get_instance();
                                    $wh = $settings->get('working_hours', array());
                                    if (is_string($wh)) { $wh = json_decode($wh, true); }
                                    echo wp_json_encode(is_array($wh)?$wh:array());
                                ?>;
                                days.forEach(function(d){
                                    var row = global[d] || {active:false,start:'',end:''};
                                    $('input[name="clinica_working_hours['+d+'][active]"]').prop('checked', !!row.active);
                                    $('input[name="clinica_working_hours['+d+'][start]"]').val(row.start||'');
                                    $('input[name="clinica_working_hours['+d+'][end]"]').val(row.end||'');
                                    $('input[name="clinica_working_hours['+d+'][break_start]"]').val(row.break_start||'');
                                    $('input[name="clinica_working_hours['+d+'][break_end]"]').val(row.break_end||'');
                                });
                            } catch(err) { console.error(err); }
                        });
                    });
                    </script>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Salvează programul per-doctor
     */
    public function save_doctor_working_hours_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) { return false; }
        $user = get_userdata($user_id);
        if (!$user || (!in_array('clinica_doctor', (array)$user->roles) && !in_array('clinica_manager', (array)$user->roles))) {
            return false;
        }
        if (!isset($_POST['clinica_working_hours'])) { delete_user_meta($user_id, 'clinica_working_hours'); return true; }
        $raw = $_POST['clinica_working_hours'];
        $clean = array();
        $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        foreach ($days as $day) {
            $row = isset($raw[$day]) ? $raw[$day] : array();
            $clean[$day] = array(
                'active' => isset($row['active']) && $row['active'] ? true : false,
                'start' => isset($row['start']) ? sanitize_text_field($row['start']) : '',
                'end' => isset($row['end']) ? sanitize_text_field($row['end']) : '',
                'break_start' => isset($row['break_start']) ? sanitize_text_field($row['break_start']) : '',
                'break_end' => isset($row['break_end']) ? sanitize_text_field($row['break_end']) : ''
            );
        }
        update_user_meta($user_id, 'clinica_working_hours', wp_json_encode($clean));
        // Salvează concedii per-doctor dacă vin în request
        if (isset($_POST['clinica_doctor_holidays'])) {
            $hol_raw = wp_unslash($_POST['clinica_doctor_holidays']);
            $hol = json_decode($hol_raw, true);
            if (!is_array($hol)) { $hol = array(); }
            update_user_meta($user_id, 'clinica_doctor_holidays', wp_json_encode(array_values(array_unique($hol))));
        }
        return true;
    }
    
    /**
     * Incarca dependenCiele
     */
    private function load_dependencies() {
        // Incarca clasele principale
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-cnp-validator.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-cnp-parser.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-password-generator.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-patient-permissions.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-patient-creation-form.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-database.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-roles.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-api.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-importers.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-authentication.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-patient-dashboard.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-doctor-dashboard.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-assistant-dashboard.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-manager-dashboard.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-receptionist-dashboard.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-family-manager.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-family-auto-creator.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-settings.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-services-manager.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-live-updates.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-clinic-schedule.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-romanian-holidays.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-role-switcher-widget.php';
        require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-role-display-shortcode.php';
        
        // Paginile admin se încarcă doar când sunt necesare
    }
    
    /**
     * Activare plugin
     */
    public function activate() {
        // Creeaza rolurile personalizate
        Clinica_Roles::create_roles();
        
        // Creează tabelele doar dacă nu există deja (NON-DESTRUCTIVE)
        Clinica_Database::create_tables();
        
        // Creează tabelele pentru timeslots (NON-DESTRUCTIVE)
        $services_manager = Clinica_Services_Manager::get_instance();
        $services_manager->create_timeslots_tables();
        
        // Migrează la sistemul de roluri duble (NON-DESTRUCTIVE)
        if (!Clinica_Database::is_dual_roles_migrated()) {
            $migrated_count = Clinica_Database::migrate_to_dual_roles();
            error_log("[CLINICA] Dual roles migration completed. Migrated $migrated_count users.");
        }
        
        // Asigură că toate tabelele există (verificare suplimentară)
        $this->ensure_all_tables_exist();
        
        // Sincronizare completă la activare (pacienți + emailuri + roluri)
        $this->auto_sync_existing_patients();
        try {
            global $wpdb;
            $clinica_table = $wpdb->prefix . 'clinica_patients';
            // 1) Aliniază emailurile patients <-> users (aceleași reguli ca în pagina de sync)
            $rows = $wpdb->get_results("SELECT p.id, p.user_id, p.email AS p_email, u.user_email AS u_email FROM $clinica_table p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id");
            $affected_patients = 0; $affected_users = 0; $roles_set = 0; $differences_fixed = 0;
            foreach ($rows as $r) {
                $p_email = trim((string)$r->p_email);
                $u_email = trim((string)$r->u_email);
                $p_valid = !empty($p_email) && is_email($p_email);
                $u_valid = !empty($u_email) && is_email($u_email);
                if (!$p_valid && $u_valid) { if (false !== $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id))) { $affected_patients++; } }
                if ($p_valid && !$u_valid && (int)$r->user_id > 0) { if (false !== $wpdb->update($wpdb->users, array('user_email' => $p_email), array('ID' => (int)$r->user_id))) { $affected_users++; } }
                if ($p_valid && $u_valid && strcasecmp($p_email, $u_email) !== 0) { if (false !== $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id))) { $differences_fixed++; } }
                // asigură rolul clinica_patient
                if ((int)$r->user_id > 0) { $u = get_userdata((int)$r->user_id); if ($u && !in_array('clinica_patient', (array)$u->roles, true)) { $u->add_role('clinica_patient'); $roles_set++; } }
            }
            update_option('clinica_last_sync', array(
                'date' => current_time('mysql'),
                'affected_patients' => $affected_patients,
                'affected_users' => $affected_users,
                'differences_fixed' => $differences_fixed,
                'roles_set' => $roles_set,
                'source' => 'activation_full'
            ));
        } catch (\Throwable $e) {
            // fail-safe: nu blocăm activarea pentru erori non-critice
        }
        
        // Creeaza paginile necesare
        $this->create_pages();
        
        // Seteaza versiunea
        update_option('clinica_version', CLINICA_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Programează cron job pentru actualizarea automată a statusurilor programărilor
        if (!wp_next_scheduled('clinica_auto_update_appointment_status')) {
            wp_schedule_event(time(), 'clinica_every_5_minutes', 'clinica_auto_update_appointment_status');
        }
    }
    
    /**
     * Creeaza paginile necesare
     */
    private function create_pages() {
        // Array cu toate paginile care trebuie create
        $pages_to_create = array(
            array(
                'title' => 'Dashboard Pacient',
                'slug' => 'clinica-patient-dashboard',
                'content' => '[clinica_patient_dashboard]'
            ),
            array(
                'title' => 'Dashboard Doctor',
                'slug' => 'clinica-doctor-dashboard',
                'content' => '[clinica_doctor_dashboard]'
            ),
            array(
                'title' => 'Dashboard Asistent',
                'slug' => 'clinica-assistant-dashboard',
                'content' => '[clinica_assistant_dashboard]'
            ),
            array(
                'title' => 'Dashboard Manager',
                'slug' => 'clinica-manager-dashboard',
                'content' => '[clinica_manager_dashboard]'
            ),
            array(
                'title' => 'Dashboard Receptionist',
                'slug' => 'clinica-receptionist-dashboard',
                'content' => '[clinica_receptionist_dashboard]'
            ),
            array(
                'title' => 'Creare Pacient',
                'slug' => 'clinica-create-patient-frontend',
                'content' => '[clinica_create_patient_form]'
            ),
            array(
                'title' => 'Autentificare Clinica',
                'slug' => 'clinica-login',
                'content' => '[clinica_login]'
            )
        );
        
        // Creeaza fiecare pagina daca nu exista deja
        foreach ($pages_to_create as $page_data) {
            $existing_page = get_page_by_path($page_data['slug']);
            
            if (!$existing_page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_content' => $page_data['content'],
                    'post_author' => 1, // Administrator
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));
                
                if ($page_id) {
                    // Adauga meta pentru a marca pagina ca fiind creata de plugin
                    update_post_meta($page_id, '_clinica_plugin_page', 'yes');
                    update_post_meta($page_id, '_clinica_page_type', $page_data['slug']);
                }
            }
        }
    }
    
    /**
     * Asigură că toate tabelele există (verificare suplimentară)
     */
    private function ensure_all_tables_exist() {
        global $wpdb;
        
        // Lista tuturor tabelelor necesare
        $required_tables = array(
            $wpdb->prefix . 'clinica_patients',
            $wpdb->prefix . 'clinica_appointments',
            $wpdb->prefix . 'clinica_medical_records',
            $wpdb->prefix . 'clinica_settings',
            $wpdb->prefix . 'clinica_login_logs',
            $wpdb->prefix . 'clinica_imports',
            $wpdb->prefix . 'clinica_notifications',
            $wpdb->prefix . 'clinica_services',
            $wpdb->prefix . 'clinica_doctor_services',
            $wpdb->prefix . 'clinica_clinic_schedule',
            $wpdb->prefix . 'clinica_user_active_roles',
            $wpdb->prefix . 'clinica_doctor_timeslots'
        );
        
        $missing_tables = array();
        
        // Verifică fiecare tabel
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $missing_tables[] = $table;
            }
        }
        
        // Dacă lipsesc tabele, le creează
        if (!empty($missing_tables)) {
            error_log("[CLINICA] Missing tables detected: " . implode(', ', $missing_tables));
            
            // Recreează tabelele lipsă
            Clinica_Database::create_tables();
            
            // Creează tabelele pentru timeslots
            $services_manager = Clinica_Services_Manager::get_instance();
            $services_manager->create_timeslots_tables();
            
            error_log("[CLINICA] Missing tables recreated successfully.");
        }
    }
    
    /**
     * Dezactivare plugin
     */
    public function deactivate() {
        // NU șterge nimic din baza de date pentru siguranță
        // Clinica_Roles::remove_roles(); // COMENTAT pentru siguranță
        
        // Curăță cron job-ul pentru actualizarea automată a statusurilor
        $timestamp = wp_next_scheduled('clinica_auto_update_appointment_status');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'clinica_auto_update_appointment_status');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log dezactivarea
        error_log("[CLINICA] Plugin deactivated safely - no data lost.");
    }
    
    /**
     * IniCiializare plugin
     */
    public function init() {
        // Initializeaza sesiunea
        if (function_exists('session_status') && session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Setează un director sigur pentru sesiuni în uploads dacă tmp-ul nu e accesibil
            $uploads = wp_get_upload_dir();
            $sessPath = trailingslashit($uploads['basedir']) . 'sessions';
            if (!is_dir($sessPath)) { wp_mkdir_p($sessPath); }
            if (is_writable($sessPath)) {
                @session_save_path($sessPath);
            }
            @session_start();
        }
        
        // Initializeaza componentele
        $this->init_components();
    }
    
    /**
     * Incarca textdomain pentru traduceri
     */
    public function load_textdomain() {
        load_plugin_textdomain('clinica', false, dirname(CLINICA_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Ascunde admin bar-ul pentru rolurile Clinica (în afara de administratorul WordPress)
     */
    public function hide_admin_bar_for_clinica_roles() {
        if (class_exists('Clinica_Roles')) {
            Clinica_Roles::hide_admin_bar_for_clinica_roles();
        }
    }
    
    /**
     * Adaugă intervalul personalizat pentru cron job (la fiecare 5 minute)
     */
    public function add_cron_interval($schedules) {
        $schedules['clinica_every_5_minutes'] = array(
            'interval' => 300, // 5 minute în secunde
            'display' => __('La fiecare 5 minute (Clinica)', 'clinica')
        );
        return $schedules;
    }
    
    /**
     * Actualizează automat statusurile programărilor de la 'confirmed' la 'completed'
     * după 30 de minute de la ora programării
     */
    public function auto_update_appointment_status() {
        global $wpdb;
        
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        
        // Găsește programările cu status 'confirmed' care au trecut de 30+ minute de la sfârșitul programării
        $current_time = current_time('mysql');
        
        $appointments_to_update = $wpdb->get_results($wpdb->prepare("
            SELECT id, appointment_date, appointment_time, duration
            FROM $table_appointments 
            WHERE status = 'confirmed' 
            AND DATE_ADD(CONCAT(appointment_date, ' ', appointment_time), INTERVAL COALESCE(duration, 30) MINUTE) < DATE_SUB(%s, INTERVAL 30 MINUTE)
        ", $current_time));
        
        if (!empty($appointments_to_update)) {
            $updated_count = 0;
            
            foreach ($appointments_to_update as $appointment) {
                // Actualizează statusul la 'completed'
                $result = $wpdb->update(
                    $table_appointments,
                    array(
                        'status' => 'completed',
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $appointment->id)
                );
                
                if ($result !== false) {
                    $updated_count++;
                    
                    // Log pentru audit
                    $plugin_root = dirname(dirname(__FILE__));
                    if (!file_exists($plugin_root . '/logs')) { 
                        @mkdir($plugin_root . '/logs', 0755, true); 
                    }
                    $line = sprintf("[%s] AUTO_UPDATE_APPOINTMENT id=%d date=%s time=%s status=completed\n",
                        current_time('mysql'), 
                        (int)$appointment->id, 
                        $appointment->appointment_date, 
                        $appointment->appointment_time
                    );
                    @file_put_contents($plugin_root . '/logs/appointment-audit.log', $line, FILE_APPEND);
                }
            }
            
            // Log rezultatul
            error_log("[CLINICA] Auto-updated $updated_count appointments from 'confirmed' to 'completed'");
        }
    }
    
    /**
     * Funcție de test pentru actualizarea automată a statusurilor
     * Poate fi apelată manual pentru testare
     */
    public function test_auto_update_appointment_status() {
        // Forțează rularea funcției de actualizare automată
        $this->auto_update_appointment_status();
        
        // Returnează un mesaj de confirmare
        return "Testul de actualizare automată a statusurilor a fost rulat cu succes!";
    }
    
    /**
     * Initializeaza componentele
     */
    private function init_components() {
        // Initializeaza API-ul
        new Clinica_API();
        
        // Initializeaza importatorii
        new Clinica_Importers();
        
        // Initializeaza autentificarea
        new Clinica_Authentication();
        
        // Initializeaza formularul de creare pacienti (pentru AJAX)
        new Clinica_Patient_Creation_Form();
        
        // Initializeaza managerul de familii
        new Clinica_Family_Manager();
        
        // Initializeaza setările
        $this->settings = Clinica_Settings::get_instance();
        
        // Initializeaza dashboard-urile
        if (class_exists('Clinica_Patient_Dashboard')) {
            new Clinica_Patient_Dashboard();
        }
        if (class_exists('Clinica_Doctor_Dashboard')) {
            new Clinica_Doctor_Dashboard();
        }
        if (class_exists('Clinica_Assistant_Dashboard')) {
            new Clinica_Assistant_Dashboard();
        }
        if (class_exists('Clinica_Manager_Dashboard')) {
            $this->manager_dashboard = new Clinica_Manager_Dashboard();
        }
        if (class_exists('Clinica_Receptionist_Dashboard')) {
            new Clinica_Receptionist_Dashboard();
        }
        
        // AZnregistreaza shortcode-urile
        add_shortcode('clinica_create_patient_form', array($this, 'render_create_patient_form'));
        add_shortcode('clinica_patient_dashboard', array($this, 'render_patient_dashboard'));
        add_shortcode('clinica_doctor_dashboard', array($this, 'render_doctor_dashboard'));
        add_shortcode('clinica_assistant_dashboard', array($this, 'render_assistant_dashboard'));
        add_shortcode('clinica_manager_dashboard', array($this, 'render_manager_dashboard'));
        add_shortcode('clinica_receptionist_dashboard', array($this, 'render_receptionist_dashboard'));
        
        // AJAX handler pentru preview dashboard-uri
        add_action('wp_ajax_load_dashboard_preview', array($this, 'ajax_load_dashboard_preview'));
        add_action('wp_ajax_nopriv_load_dashboard_preview', array($this, 'ajax_load_dashboard_preview'));
        
        // Verificare automată și sincronizare pacienți (doar pentru admin)
        if (is_admin() && current_user_can('manage_options')) {
            add_action('admin_init', array($this, 'check_and_auto_sync_patients'));
        }
    }
    
    /**
     * Obtine HTML pentru programarile recente
     */
    public function get_recent_appointments_html() {
        global $wpdb;
        
        $appointments = $wpdb->get_results("
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.type,
                pm1.meta_value as patient_first_name,
                pm2.meta_value as patient_last_name,
                dm1.meta_value as doctor_first_name,
                dm2.meta_value as doctor_last_name
            FROM {$wpdb->prefix}clinica_appointments a
            LEFT JOIN {$wpdb->prefix}clinica_patients p ON a.patient_id = p.id
            LEFT JOIN {$wpdb->users} pu ON p.user_id = pu.ID
            LEFT JOIN {$wpdb->usermeta} pm1 ON pu.ID = pm1.user_id AND pm1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} pm2 ON pu.ID = pm2.user_id AND pm2.meta_key = 'last_name'
            LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
            LEFT JOIN {$wpdb->usermeta} dm1 ON d.ID = dm1.user_id AND dm1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} dm2 ON d.ID = dm2.user_id AND dm2.meta_key = 'last_name'
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 10
        ");
        
        if (empty($appointments)) {
            return '<p>' . __('Nu exista programari recente.', 'clinica') . '</p>';
        }
        
        ob_start();
        ?>
        <table class="recent-table">
            <thead>
                <tr>
                    <th><?php _e('Data', 'clinica'); ?></th>
                    <th><?php _e('Ora', 'clinica'); ?></th>
                    <th><?php _e('Pacient', 'clinica'); ?></th>
                    <th><?php _e('Doctor', 'clinica'); ?></th>
                    <th><?php _e('Status', 'clinica'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo esc_html(date('d.m.Y', strtotime($appointment->appointment_date))); ?></td>
                    <td><?php echo esc_html($appointment->appointment_time); ?></td>
                    <td><?php echo esc_html($appointment->patient_first_name . ' ' . $appointment->patient_last_name); ?></td>
                    <td><?php 
                        $doctor_first_name = $appointment->doctor_first_name ?: '';
                        $doctor_last_name = $appointment->doctor_last_name ?: '';
                        $doctor_name = trim($doctor_first_name . ' ' . $doctor_last_name);
                        echo esc_html(!empty($doctor_name) ? $doctor_name : __('Doctor necunoscut', 'clinica')); 
                    ?></td>
                    <td>
                        <span class="status-<?php echo esc_attr($appointment->status); ?>">
                            <?php echo esc_html($appointment->status); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtine HTML pentru pacientii recenti
     */
    public function get_recent_patients_html() {
        global $wpdb;
        
        $patients = $wpdb->get_results("
            SELECT p.*, u.user_email, u.display_name,
                   um1.meta_value as first_name, um2.meta_value as last_name
            FROM {$wpdb->prefix}clinica_patients p 
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
            LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
            ORDER BY p.created_at DESC
            LIMIT 10
        ");
        
        if (empty($patients)) {
            return '<p>' . __('Nu exista pacienti recenti.', 'clinica') . '</p>';
        }
        
        ob_start();
        ?>
        <table class="recent-table">
            <thead>
                <tr>
                    <th><?php _e('Nume', 'clinica'); ?></th>
                    <th><?php _e('CNP', 'clinica'); ?></th>
                    <th><?php _e('Email', 'clinica'); ?></th>
                    <th><?php _e('Telefon', 'clinica'); ?></th>
                    <th><?php _e('Data Inregistrarii', 'clinica'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><?php 
                        $full_name = trim($patient->first_name . ' ' . $patient->last_name);
                        echo esc_html(!empty($full_name) ? $full_name : $patient->display_name); 
                    ?></td>
                    <td><?php echo esc_html($patient->cnp); ?></td>
                    <td><?php echo esc_html($patient->user_email); ?></td>
                    <td><?php echo esc_html($patient->phone_primary); ?></td>
                    <td><?php echo esc_html(date('d.m.Y', strtotime($patient->created_at))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Creeaza meniul admin
     */
    public function admin_menu() {
        // Meniul principal
        add_menu_page(
            __('Clinica', 'clinica'),
            __('Clinica', 'clinica'),
            'clinica_view_dashboard',
            'clinica',
            array($this, 'admin_dashboard'),
            'dashicons-heart',
            30
        );
        
        // Submeniuri
        add_submenu_page(
            'clinica',
            __('Dashboard', 'clinica'),
            __('Dashboard', 'clinica'),
            'clinica_view_dashboard',
            'clinica',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'clinica',
            __('Pacienti', 'clinica'),
            __('Pacienti', 'clinica'),
            'clinica_view_patients',
            'clinica-patients',
            array($this, 'admin_patients')
        );
        
        add_submenu_page(
            'clinica',
            __('Pacienți Inactivi', 'clinica'),
            __('Pacienți Inactivi', 'clinica'),
            'manage_options',
            'clinica-inactive-patients',
            array($this, 'admin_inactive_patients')
        );
        
        add_submenu_page(
            'clinica',
            __('Pacienți Străini', 'clinica'),
            __('Pacienți Străini', 'clinica'),
            'manage_options',
            'clinica-foreign-patients',
            array($this, 'foreign_patients_page')
        );
        
        add_submenu_page(
            'clinica',
            __('E-mailuri Neactualizate', 'clinica'),
            __('E-mailuri Neactualizate', 'clinica'),
            'manage_options',
            'clinica-invalid-emails',
            array($this, 'invalid_emails_page')
        );
        
        add_submenu_page(
            'clinica',
            __('Creare Pacient', 'clinica'),
            __('Creare Pacient', 'clinica'),
            'clinica_create_patients',
            'clinica-create-patient',
            array($this, 'admin_create_patient')
        );
        
        add_submenu_page(
            'clinica',
            __('Familii', 'clinica'),
            __('Familii', 'clinica'),
            'clinica_view_patients',
            'clinica-families',
            array($this, 'admin_families')
        );
        
        add_submenu_page(
            'clinica',
            __('Programari', 'clinica'),
            __('Programari', 'clinica'),
            'clinica_view_appointments',
            'clinica-appointments',
            array($this, 'admin_appointments')
        );
        
        // Medici – pagină dedicată
        add_submenu_page(
            'clinica',
            __('Medici', 'clinica'),
            __('Medici', 'clinica'),
            'clinica_view_doctors',
            'clinica-doctors',
            array($this, 'admin_doctors')
        );
        
        add_submenu_page(
            'clinica',
            __('Dashboard Servicii & Programare', 'clinica'),
            __('Servicii', 'clinica'),
            'clinica_manage_services',
            'clinica-services',
            array($this, 'admin_services_dashboard')
        );

        add_submenu_page(
            'clinica',
            __('Timeslots Avansați', 'clinica'),
            __('Timeslots Avansați', 'clinica'),
            'manage_options',
            'clinica-timeslots-advanced',
            array($this, 'admin_timeslots_advanced')
        );

        add_submenu_page(
            'clinica',
            __('Import Pacienti', 'clinica'),
            __('Import Pacienti', 'clinica'),
            'clinica_import_patients',
            'clinica-import',
            array($this, 'admin_import')
        );
        
        add_submenu_page(
            'clinica',
            __('Sincronizare Pacienți', 'clinica'),
            __('Sincronizare Pacienți', 'clinica'),
            'manage_options',
            'clinica-sync-patients',
            array($this, 'admin_sync_patients')
        );
        
        add_submenu_page(
            'clinica',
            __('CNP-uri Invalide', 'clinica'),
            __('CNP-uri Invalide', 'clinica'),
            'manage_options',
            'clinica-invalid-cnps',
            array($this, 'admin_invalid_cnps')
        );
        
        add_submenu_page(
            'clinica',
            __('Rapoarte', 'clinica'),
            __('Rapoarte', 'clinica'),
            'clinica_view_reports',
            'clinica-reports',
            array($this, 'admin_reports')
        );
        
        add_submenu_page(
            'clinica',
            __('Setari', 'clinica'),
            __('Setari', 'clinica'),
            'clinica_manage_settings',
            'clinica-settings',
            array($this, 'admin_settings')
        );
        
        add_submenu_page(
            'clinica',
            __('Shortcode-uri', 'clinica'),
            __('Shortcode-uri', 'clinica'),
            'manage_options',
            'clinica-shortcodes',
            array($this, 'admin_shortcodes')
        );
        
        add_submenu_page(
            'clinica',
            __('Roluri Duble', 'clinica'),
            __('Roluri Duble', 'clinica'),
            'manage_options',
            'clinica-dual-roles',
            array($this, 'admin_dual_roles')
        );
        
        // Pagini de debug (doar pentru administratori)
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'clinica',
                __('Corectare Nume', 'clinica'),
                __('Corectare Nume', 'clinica'),
                'manage_options',
                'clinica-fix-names',
                array($this, 'admin_fix_names')
            );
            
            add_submenu_page(
                'clinica',
                __('Debug Pacienti', 'clinica'),
                __('Debug Pacienti', 'clinica'),
                'manage_options',
                'clinica-debug-patients',
                array($this, 'admin_debug_patients')
            );
            
            add_submenu_page(
                'clinica',
                __('Test Pacienti', 'clinica'),
                __('Test Pacienti', 'clinica'),
                'manage_options',
                'clinica-test-patients',
                array($this, 'admin_test_patients')
            );
        }
    }
    
    /**
     * Dashboard admin
     */
    public function admin_dashboard() {
        include CLINICA_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Gestionare pacienti
     */
    public function admin_patients() {
        include CLINICA_PLUGIN_PATH . 'admin/views/patients.php';
    }
    
    public function admin_inactive_patients() {
        include CLINICA_PLUGIN_PATH . 'admin/views/inactive-patients.php';
    }
    
    public function foreign_patients_page() {
        include CLINICA_PLUGIN_PATH . 'admin/views/foreign-patients.php';
    }
    
    public function invalid_emails_page() {
        include CLINICA_PLUGIN_PATH . 'admin/views/invalid-emails.php';
    }
    
    /**
     * Creare pacient
     */
    public function admin_create_patient() {
        include CLINICA_PLUGIN_PATH . 'admin/views/create-patient.php';
    }
    
    public function admin_families() {
        include CLINICA_PLUGIN_PATH . 'admin/views/families.php';
    }
    
    /**
     * Gestionare programari
     */
    public function admin_appointments() {
        include CLINICA_PLUGIN_PATH . 'admin/views/appointments.php';
    }
    
    /**
     * Import pacienti
     */
    public function admin_import() {
        include CLINICA_PLUGIN_PATH . 'admin/views/import.php';
    }
    
    /**
     * Sincronizare pacienți
     */
    public function admin_sync_patients() {
        include CLINICA_PLUGIN_PATH . 'admin/views/sync-patients.php';
    }
    
    /**
     * CNP-uri Invalide
     */
    public function admin_invalid_cnps() {
        include CLINICA_PLUGIN_PATH . 'admin/views/invalid-cnps.php';
    }
    
    /**
     * Corectare Nume cu Cratime
     */
    public function admin_fix_names() {
        include CLINICA_PLUGIN_PATH . 'admin/views/fix-names.php';
    }
    
    /**
     * Rapoarte
     */
    public function admin_reports() {
        include CLINICA_PLUGIN_PATH . 'admin/views/reports.php';
    }
    
    /**
     * Pagina Medici (admin)
     */
    public function admin_doctors() {
        if (!Clinica_Patient_Permissions::can_view_doctors()) {
            wp_die(__('Nu aveți permisiunea de a vedea medicii.', 'clinica'));
        }
        include CLINICA_PLUGIN_PATH . 'admin/views/doctors.php';
    }
    
    /**
     * Dashboard Servicii & Programare
     */
    public function admin_services_dashboard() {
        // Permite accesul administratorilor WordPress și utilizatorilor cu permisiuni Clinica
        if (!current_user_can('clinica_manage_services') && !current_user_can('manage_options')) {
            wp_die(__('Nu aveți permisiunea de a gestiona serviciile.', 'clinica'));
        }

        // Obține datele necesare
        $services_manager = Clinica_Services_Manager::get_instance();
        $services = $services_manager->get_all_services_with_allocations();
        $doctors = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));

        include CLINICA_PLUGIN_PATH . 'admin/views/services-dashboard.php';
    }

    /**
     * Pagina Timeslots Avansați
     */
    public function admin_timeslots_advanced() {
        // Permite accesul administratorilor WordPress
        if (!current_user_can('manage_options')) {
            wp_die(__('Nu aveți permisiunea de a accesa Timeslots Avansați.', 'clinica'));
        }

        // Datele sunt încărcate în fișierul timeslots-advanced.php
        include CLINICA_PLUGIN_PATH . 'admin/views/timeslots-advanced.php';
    }

    /**
     * AJAX: obține programul și concediile unui doctor
     */
    public function ajax_get_doctor_schedule() {
        if (!current_user_can('clinica_view_doctors')) { wp_send_json_error('Permisiuni insuficiente'); }
        check_ajax_referer('clinica_doctors_nonce', 'nonce');
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        if ($doctor_id <= 0) { wp_send_json_error('ID invalid'); }
        $schedule = get_user_meta($doctor_id, 'clinica_working_hours', true);
        if (is_string($schedule)) { $schedule = json_decode($schedule, true); }
        if (!is_array($schedule)) { $schedule = array(); }
        $holidays = get_user_meta($doctor_id, 'clinica_doctor_holidays', true);
        if (is_string($holidays)) { $holidays = json_decode($holidays, true); }
        if (!is_array($holidays)) { $holidays = array(); }
        wp_send_json_success(array('schedule' => $schedule, 'holidays' => $holidays));
    }

    /**
     * AJAX: salvează programul și concediile unui doctor
     */
    public function ajax_save_doctor_schedule() {
        if (!current_user_can('clinica_view_doctors')) { wp_send_json_error('Permisiuni insuficiente'); }
        check_ajax_referer('clinica_doctors_nonce', 'nonce');
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        if ($doctor_id <= 0) { wp_send_json_error('ID invalid'); }
        $raw = isset($_POST['schedule']) ? json_decode(stripslashes($_POST['schedule']), true) : array();
        $hol = isset($_POST['holidays']) ? json_decode(stripslashes($_POST['holidays']), true) : array();
        if (!is_array($raw)) { $raw = array(); }
        if (!is_array($hol)) { $hol = array(); }
        update_user_meta($doctor_id, 'clinica_working_hours', wp_json_encode($raw));
        update_user_meta($doctor_id, 'clinica_doctor_holidays', wp_json_encode(array_values(array_unique($hol))));
        wp_send_json_success('Salvat');
    }

    /**
     * AJAX: salvează/creează serviciu (name, duration, active)
     */
    public function ajax_services_save() {
        if (!current_user_can('clinica_manage_settings') && !current_user_can('manage_options')) { wp_send_json_error('Permisiuni insuficiente'); }
        check_ajax_referer('clinica_settings_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_services';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $active = isset($_POST['active']) ? (intval($_POST['active']) ? 1 : 0) : 1;
        if (empty($name)) { wp_send_json_error('Nume invalid'); }
        if ($duration < 5 || $duration > 240) { wp_send_json_error('Durată invalidă'); }
        if ($id > 0) {
            $ok = $wpdb->update($table, array('name'=>$name,'duration'=>$duration,'active'=>$active,'updated_at'=>current_time('mysql')), array('id'=>$id));
            if ($ok === false) { wp_send_json_error('Eroare la actualizare'); }
            wp_send_json_success(array('id'=>$id));
        } else {
            $ok = $wpdb->insert($table, array('name'=>$name,'duration'=>$duration,'active'=>$active,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')));
            if ($ok === false) { wp_send_json_error('Eroare la creare'); }
            wp_send_json_success(array('id'=>$wpdb->insert_id));
        }
    }

    /**
     * AJAX: șterge serviciu
     */
    public function ajax_services_delete() {
        if (!current_user_can('clinica_manage_settings') && !current_user_can('manage_options')) { wp_send_json_error('Permisiuni insuficiente'); }
        check_ajax_referer('clinica_settings_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_services';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) { wp_send_json_error('ID invalid'); }
        $ok = $wpdb->delete($table, array('id'=>$id));
        if ($ok === false) { wp_send_json_error('Eroare la ștergere'); }
        wp_send_json_success('Șters');
    }
    
    /**
     * Setari
     */
    public function admin_settings() {
        // asigură existența tabelei servicii
        if (class_exists('Clinica_Database')) { Clinica_Database::create_tables(); }
        include CLINICA_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Shortcode-uri
     */
    public function admin_shortcodes() {
        include CLINICA_PLUGIN_PATH . 'admin/views/shortcodes.php';
    }
    
    /**
     * Pagina roluri duble
     */
    public function admin_dual_roles() {
        // Verifică permisiunile
        if (!current_user_can('manage_options')) {
            wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
        }
        
        // Procesează acțiunile
        if (isset($_POST['action'])) {
            $this->handle_dual_roles_actions();
        }
        
        include CLINICA_PLUGIN_PATH . 'admin/views/dual-roles.php';
    }
    
    /**
     * Gestionare acțiuni pentru roluri duble
     */
    public function handle_dual_roles_actions() {
        $action = sanitize_text_field($_POST['action']);
        $nonce = sanitize_text_field($_POST['_wpnonce']);
        
        // Verifică nonce-ul
        if (!wp_verify_nonce($nonce, 'clinica_dual_roles_action')) {
            wp_die(__('Eroare de securitate. Vă rugăm să încercați din nou.', 'clinica'));
        }
        
        switch ($action) {
            case 'migrate_roles':
                $this->migrate_dual_roles();
                break;
            case 'reset_migration':
                $this->reset_dual_roles_migration();
                break;
            case 'add_patient_role':
                $user_id = intval($_POST['user_id']);
                $this->add_patient_role_to_user($user_id);
                break;
            case 'switch_user_role':
                $user_id = intval($_POST['user_id']);
                $new_role = sanitize_text_field($_POST['new_role']);
                $this->switch_user_role($user_id, $new_role);
                break;
            case 'remove_patient_role':
                $user_id = intval($_POST['user_id']);
                $this->remove_patient_role_from_user($user_id);
                break;
        }
    }
    
    /**
     * Migrează la roluri duble
     */
    private function migrate_dual_roles() {
        $migrated_count = Clinica_Database::migrate_to_dual_roles();
        add_action('admin_notices', function() use ($migrated_count) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf(__('Migrarea a fost completată cu succes! %d utilizatori au fost migrați.', 'clinica'), $migrated_count);
            echo '</p></div>';
        });
    }
    
    /**
     * Resetează migrarea
     */
    private function reset_dual_roles_migration() {
        Clinica_Database::reset_dual_roles_migration();
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo __('Migrarea a fost resetată cu succes!', 'clinica');
            echo '</p></div>';
        });
    }
    
    /**
     * Adaugă rol de pacient la utilizator
     */
    private function add_patient_role_to_user($user_id) {
        $result = Clinica_Roles::add_patient_role_to_staff($user_id);
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo __('Rolul de pacient a fost adăugat cu succes!', 'clinica');
                echo '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo __('Eroare la adăugarea rolului de pacient!', 'clinica');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Schimbă rolul utilizatorului
     */
    private function switch_user_role($user_id, $new_role) {
        $result = Clinica_Roles::switch_user_role($user_id, $new_role);
        if ($result) {
            add_action('admin_notices', function() use ($new_role) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo sprintf(__('Rolul a fost schimbat cu succes la: %s', 'clinica'), $new_role);
                echo '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo __('Eroare la schimbarea rolului!', 'clinica');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Elimină rolul de pacient de la utilizator
     */
    private function remove_patient_role_from_user($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $user->remove_role('clinica_patient');
            
            // Șterge din tabela de roluri active
            global $wpdb;
            $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
            $wpdb->delete(
                $table_user_active_roles,
                array('user_id' => $user_id, 'active_role' => 'clinica_patient'),
                array('%d', '%s')
            );
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo __('Rolul de pacient a fost eliminat cu succes!', 'clinica');
                echo '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo __('Utilizatorul nu a fost găsit!', 'clinica');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Debug Pacienti
     */
    public function admin_debug_patients() {
        include CLINICA_PLUGIN_PATH . 'tools/testing/debug-patients-table.php';
    }
    
    /**
     * Test Pacienti
     */
    public function admin_test_patients() {
        include CLINICA_PLUGIN_PATH . 'tools/testing/test-patients-display-fix.php';
    }
    
    /**
     * Incarca scripturile pentru admin
     */
    public function admin_scripts($hook) {
        // Lista paginilor plugin-ului
        $clinica_pages = array(
            'toplevel_page_clinica',
            'clinica_page_clinica-patients',
            'clinica_page_clinica-inactive-patients',
            'clinica_page_clinica-foreign-patients',
            'clinica_page_clinica-invalid-emails',
            'clinica_page_clinica-create-patient',
            'clinica_page_clinica-families',
            'clinica_page_clinica-appointments',
            'clinica_page_clinica-doctors',
            'clinica_page_clinica-import',
            'clinica_page_clinica-reports',
            'clinica_page_clinica-settings',
            'clinica_page_clinica-shortcodes',
            'clinica_page_clinica-debug-patients',
            'clinica_page_clinica-test-patients'
        );
        
        if (!in_array($hook, $clinica_pages)) {
            return;
        }
        
        // CSS
        $css_file = CLINICA_PLUGIN_PATH . 'assets/css/admin.css';
        $css_version = file_exists($css_file) ? CLINICA_VERSION . '.' . filemtime($css_file) : CLINICA_VERSION;
        
        // Încarcă Dashicons pentru iconițe
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            'clinica-admin',
            CLINICA_PLUGIN_URL . 'assets/css/admin.css',
            array('dashicons'),
            $css_version
        );
        
        // JavaScript
        $js_file = CLINICA_PLUGIN_PATH . 'assets/js/admin.js';
        $js_version = file_exists($js_file) ? CLINICA_VERSION . '.' . filemtime($js_file) : CLINICA_VERSION;
        
        wp_enqueue_script(
            'clinica-admin',
            CLINICA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $js_version,
            true
        );
        
        // Încarcă Thickbox pentru pagina de doctori
        if ($hook === 'clinica_page_clinica-doctors') {
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
        }
        
        // Localize script
        wp_localize_script('clinica-admin', 'clinica_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clinica_nonce'),
            'strings' => array(
                'confirm_delete' => __('Sigur doriCii sa CitergeCii?', 'clinica'),
                'loading' => __('Se Ancarca...', 'clinica'),
                'success' => __('OperaCiiune reuCiita!', 'clinica'),
                'error' => __('A aparut o eroare!', 'clinica')
            )
        ));
        
        // Adaugă ajaxurl pentru autosuggest
        $autosuggest_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'search_nonce' => wp_create_nonce('clinica_search_nonce'),
            'family_nonce' => wp_create_nonce('clinica_family_nonce')
        );
        
        wp_localize_script('clinica-admin', 'clinica_autosuggest', $autosuggest_data);
    }
    
    /**
     * Incarca scripturile pentru frontend
     */
    public function frontend_scripts() {
        // Încarcă Dashicons pentru iconițe
        wp_enqueue_style('dashicons');
        
        // CSS de baza pentru toate paginile
        wp_enqueue_style(
            'clinica-frontend',
            CLINICA_PLUGIN_URL . 'assets/css/frontend.css',
            array('dashicons'),
            CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/css/frontend.css')
        );
        
        // JavaScript de baza pentru toate paginile
        wp_enqueue_script(
            'clinica-frontend',
            CLINICA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/js/frontend.js'),
            true
        );
        
        // Localize script pentru frontend
        wp_localize_script('clinica-frontend', 'clinica_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clinica_frontend_nonce'),
            'rest_url' => rest_url('clinica/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
        
        // Incarca scripturile specifice doar pe paginile respective
        $current_page = get_post();
        if ($current_page) {
            $page_content = $current_page->post_content;
            
            // Dashboard Pacient
            if (strpos($page_content, '[clinica_patient_dashboard]') !== false) {
                wp_enqueue_style(
                    'clinica-patient-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/css/patient-dashboard.css',
                    array(),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/css/patient-dashboard.css')
                );
                
                wp_enqueue_script(
                    'clinica-patient-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/js/patient-dashboard.js',
                    array('jquery'),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/js/patient-dashboard.js'),
                    true
                );
                
                wp_localize_script('clinica-patient-dashboard', 'clinica_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('clinica_dashboard_nonce')
                ));
            }
            
            // Dashboard Doctor - încărcat prin clasa Clinica_Doctor_Dashboard
            
            // Dashboard Asistent
            if (strpos($page_content, '[clinica_assistant_dashboard]') !== false) {
                wp_enqueue_style(
                    'clinica-assistant-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/css/assistant-dashboard.css',
                    array(),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/css/assistant-dashboard.css')
                );
                
                wp_enqueue_script(
                    'clinica-assistant-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/js/assistant-dashboard.js',
                    array('jquery'),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/js/assistant-dashboard.js'),
                    true
                );
                
                wp_localize_script('clinica-assistant-dashboard', 'clinicaAssistantAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('clinica_assistant_nonce')
                ));
            }
            
            // Dashboard Manager
            if (strpos($page_content, '[clinica_manager_dashboard]') !== false) {
                wp_enqueue_style(
                    'clinica-manager-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/css/manager-dashboard.css',
                    array(),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/css/manager-dashboard.css')
                );
                
                wp_enqueue_script(
                    'clinica-manager-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/js/manager-dashboard.js',
                    array('jquery'),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/js/manager-dashboard.js'),
                    true
                );
                
                wp_localize_script('clinica-manager-dashboard', 'clinicaManagerAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('clinica_manager_nonce')
                ));
            }
            
            // Dashboard Receptionist
            if (strpos($page_content, '[clinica_receptionist_dashboard]') !== false) {
                wp_enqueue_style(
                    'clinica-receptionist-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/css/receptionist-dashboard.css',
                    array(),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/css/receptionist-dashboard.css')
                );
                
                wp_enqueue_script(
                    'clinica-receptionist-dashboard',
                    CLINICA_PLUGIN_URL . 'assets/js/receptionist-dashboard.js',
                    array('jquery'),
                    CLINICA_VERSION . '.' . filemtime(CLINICA_PLUGIN_PATH . 'assets/js/receptionist-dashboard.js'),
                    true
                );
                
                wp_localize_script('clinica-receptionist-dashboard', 'clinicaReceptionistAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('clinica_receptionist_nonce')
                ));
            }
        }
    }
    
    /**
     * AZnregistreaza rutele API
     */
    public function register_api_routes() {
        // Rute pentru validare CNP
        register_rest_route('clinica/v1', '/validate-cnp', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_validate_cnp'),
            'permission_callback' => array($this, 'api_permission_callback')
        ));
        
        // Rute pentru parsare CNP
        register_rest_route('clinica/v1', '/parse-cnp', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_parse_cnp'),
            'permission_callback' => array($this, 'api_permission_callback')
        ));
        
        // Rute pentru generare parola
        register_rest_route('clinica/v1', '/generate-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_generate_password'),
            'permission_callback' => array($this, 'api_permission_callback')
        ));
        
        // Rute pentru creare pacient
        register_rest_route('clinica/v1', '/create-patient', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_create_patient'),
            'permission_callback' => array($this, 'api_permission_callback')
        ));
    }
    
    /**
     * Callback pentru permisiuni API
     */
    public function api_permission_callback($request) {
        return Clinica_Patient_Permissions::can_create_patient();
    }
    
    /**
     * API pentru validare CNP
     */
    public function api_validate_cnp($request) {
        $cnp = sanitize_text_field($request->get_param('cnp'));
        
        if (empty($cnp)) {
            return new WP_Error('invalid_cnp', 'CNP-ul este obligatoriu', array('status' => 400));
        }
        
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * API pentru parsare CNP
     */
    public function api_parse_cnp($request) {
        $cnp = sanitize_text_field($request->get_param('cnp'));
        
        if (empty($cnp)) {
            return new WP_Error('invalid_cnp', 'CNP-ul este obligatoriu', array('status' => 400));
        }
        
        $parser = new Clinica_CNP_Parser();
        $birth_date = $parser->extract_birth_date($cnp);
        $gender = $parser->extract_gender($cnp);
        $age = $parser->calculate_age($birth_date);
        
        return new WP_REST_Response(array(
            'birth_date' => $birth_date,
            'gender' => $gender,
            'age' => $age
        ), 200);
    }
    
    /**
     * API pentru generare parola
     */
    public function api_generate_password($request) {
        $cnp = sanitize_text_field($request->get_param('cnp'));
        $birth_date = sanitize_text_field($request->get_param('birth_date'));
        $method = sanitize_text_field($request->get_param('method'));
        
        if (empty($cnp) || empty($birth_date) || empty($method)) {
            return new WP_Error('invalid_params', 'Toate parametrii sunt obligatorii', array('status' => 400));
        }
        
        $generator = new Clinica_Password_Generator();
        $password = $generator->generate_password($cnp, $birth_date, $method);
        
        return new WP_REST_Response(array(
            'password' => $password
        ), 200);
    }
    
    /**
     * API pentru creare pacient
     */
    public function api_create_patient($request) {
        // Verifica permisiunile
        if (!Clinica_Patient_Permissions::can_create_patient()) {
            return new WP_Error('insufficient_permissions', 'Nu aveCii permisiunea de a crea pacienti', array('status' => 403));
        }
        
        // Valideaza datele
        $cnp = sanitize_text_field($request->get_param('cnp'));
        $last_name = sanitize_text_field($request->get_param('last_name'));
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $email = sanitize_email($request->get_param('email'));
        $phone_primary = sanitize_text_field($request->get_param('phone_primary'));
        $phone_secondary = sanitize_text_field($request->get_param('phone_secondary'));
        $birth_date = sanitize_text_field($request->get_param('birth_date'));
        $gender = sanitize_text_field($request->get_param('gender'));
        $password_method = sanitize_text_field($request->get_param('password_method'));
        
        // Validare obligatorii
        if (empty($cnp) || empty($last_name) || empty($first_name)) {
            return new WP_Error('missing_required_fields', 'CNP, numele Cii prenumele sunt obligatorii', array('status' => 400));
        }
        
        // Valideaza CNP-ul
        $validator = new Clinica_CNP_Validator();
        $cnp_result = $validator->validate_cnp($cnp);
        
        if (!$cnp_result['valid']) {
            return new WP_Error('invalid_cnp', $cnp_result['error'], array('status' => 400));
        }
        
        // Genereaza parola
        $generator = new Clinica_Password_Generator();
        $password = $generator->generate_password($cnp, $birth_date, $password_method);
        
        // Creeaza utilizatorul WordPress
        $user_data = array(
            'user_login' => $cnp,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'clinica_patient'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', 'Nu s-a putut crea utilizatorul', array('status' => 500));
        }
        
        // Salveaza datele suplimentare An tabela pacienti
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient_data = array(
            'user_id' => $user_id,
            'cnp' => $cnp,
            'cnp_type' => $cnp_result['type'],
            'phone_primary' => $phone_primary,
            'phone_secondary' => $phone_secondary,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'age' => $parser->calculate_age($birth_date),
            'password_method' => $password_method,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $patient_data);
        
        if ($result === false) {
            // Cterge utilizatorul daca nu s-a putut salva pacientul
            wp_delete_user($user_id);
            return new WP_Error('patient_creation_failed', 'Nu s-a putut salva pacientul', array('status' => 500));
        }
        
        // Salveaza numerele de telefon ca user meta
        if (!empty($phone_primary)) {
            update_user_meta($user_id, 'phone_primary', $phone_primary);
        }
        if (!empty($phone_secondary)) {
            update_user_meta($user_id, 'phone_secondary', $phone_secondary);
        }
        
        // Trimite email de confirmare
        $this->send_welcome_email($user_id, $cnp, $password);
        
        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $user_id,
            'message' => 'Pacientul a fost creat cu succes!'
        ), 200);
    }
    
    /**
     * Trimite email de bun venit
     */
    private function send_welcome_email($user_id, $cnp, $password) {
        $user = get_userdata($user_id);
        $to = $user->user_email;
        $subject = 'Bun venit An sistemul Clinica!';
        
        $message = sprintf(
            'Buna %s %s,

Bun venit An sistemul de gestionare medicala Clinica!

CredenCiialele de autentificare:
- Username: %s
- Parola: %s

Va puteCii autentifica la: %s

Pentru asistenCia, contactaCii echipa medicala.

Cu stima,
Echipa Clinica',
            $user->first_name,
            $user->last_name,
            $cnp,
            $password,
            home_url('/wp-login.php')
        );
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Verifica permisiunile
     */
    public function check_permissions() {
        // Verifica daca utilizatorul are permisiunile necesare
        if (is_admin() && !current_user_can('clinica_view_dashboard')) {
            // Redirect catre pagina principala daca nu are permisiuni
            // add_action('admin_init', function() {
            //     wp_redirect(home_url());
            //     exit;
            // });
        }
    }
    
    /**
     * Render formular creare pacient (shortcode)
     */
    public function render_create_patient_form($atts) {
        // Verifica permisiunile
        if (!Clinica_Patient_Permissions::can_create_patient()) {
            return '<p>' . __('Nu aveCii permisiunea de a crea pacienti.', 'clinica') . '</p>';
        }
        
        $form = new Clinica_Patient_Creation_Form();
        return $form->render_form();
    }
    
    /**
     * Render dashboard pacient (shortcode)
     */
    public function render_patient_dashboard($atts) {
        // FoloseCite clasa dedicata pentru dashboard
        $dashboard = new Clinica_Patient_Dashboard();
        return $dashboard->render_dashboard_shortcode($atts);
    }
    
    /**
     * Genereaza HTML-ul pentru dashboard-ul pacientului
     */
    private function get_patient_dashboard_html($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if (!$patient) {
            return '<p>' . __('Nu s-au gasit date pentru pacient.', 'clinica') . '</p>';
        }
        
        $user = get_user_by('ID', $user_id);
        
        ob_start();
        ?>
        <div class="clinica-patient-dashboard">
            <h2><?php _e('Dashboard Pacient', 'clinica'); ?></h2>
            
            <div class="patient-info">
                <h3><?php _e('InformaCiii personale', 'clinica'); ?></h3>
                <table class="patient-details">
                    <tr>
                        <th><?php _e('Nume complet:', 'clinica'); ?></th>
                        <td><?php echo esc_html($user->display_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('CNP:', 'clinica'); ?></th>
                        <td><?php echo esc_html($patient->cnp); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Data naCiterii:', 'clinica'); ?></th>
                        <td><?php echo esc_html($patient->birth_date); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('VArsta:', 'clinica'); ?></th>
                        <td><?php echo esc_html($patient->age); ?> <?php _e('ani', 'clinica'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Telefon:', 'clinica'); ?></th>
                        <td><?php echo esc_html($patient->phone_primary); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email:', 'clinica'); ?></th>
                        <td><?php echo esc_html($user->user_email); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="patient-appointments">
                <h3><?php _e('Programarile mele', 'clinica'); ?></h3>
                <?php echo $this->get_patient_appointments_html($user_id); ?>
            </div>
        </div>
        
        <style>
        .clinica-patient-dashboard {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .patient-info {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .patient-details {
            width: 100%;
            border-collapse: collapse;
        }
        
        .patient-details th,
        .patient-details td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .patient-details th {
            font-weight: bold;
            width: 30%;
        }
        
        .patient-appointments {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Genereaza HTML-ul pentru programarile pacientului
     */
    private function get_patient_appointments_html($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_appointments';
        $appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name
             FROM $table_name a 
             LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID 
             WHERE a.patient_id = %d 
             ORDER BY a.appointment_date DESC, a.appointment_time DESC 
             LIMIT 10",
            $user_id
        ));
        
        if (empty($appointments)) {
            return '<p>' . __('Nu aveCii programari.', 'clinica') . '</p>';
        }
        
        ob_start();
        ?>
        <table class="appointments-table">
            <thead>
                <tr>
                    <th><?php _e('Data', 'clinica'); ?></th>
                    <th><?php _e('Ora', 'clinica'); ?></th>
                    <th><?php _e('Doctor', 'clinica'); ?></th>
                    <th><?php _e('Tip', 'clinica'); ?></th>
                    <th><?php _e('Status', 'clinica'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo esc_html(date('d.m.Y', strtotime($appointment->appointment_date))); ?></td>
                    <td><?php echo esc_html($appointment->appointment_time); ?></td>
                    <td><?php echo esc_html($appointment->doctor_first_name . ' ' . $appointment->doctor_last_name); ?></td>
                    <td><?php echo esc_html($appointment->type); ?></td>
                    <td><?php echo esc_html($appointment->status); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <style>
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .appointments-table th,
        .appointments-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .appointments-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render doctor dashboard
     */
    public function render_doctor_dashboard($atts) {
        if (class_exists('Clinica_Doctor_Dashboard')) {
            return Clinica_Doctor_Dashboard::get_dashboard_html(get_current_user_id());
        }
        return '<div class="clinica-error">Dashboard-ul doctorului nu este disponibil.</div>';
    }
    
    /**
     * Render assistant dashboard
     */
    public function render_assistant_dashboard($atts) {
        if (class_exists('Clinica_Assistant_Dashboard')) {
            return Clinica_Assistant_Dashboard::get_dashboard_html(get_current_user_id());
        }
        return '<div class="clinica-error">Dashboard-ul asistentului nu este disponibil.</div>';
    }
    
    /**
     * Render manager dashboard
     */
    public function render_manager_dashboard($atts) {
        if (class_exists('Clinica_Manager_Dashboard')) {
            return Clinica_Manager_Dashboard::get_dashboard_html(get_current_user_id());
        }
        return '<div class="clinica-error">Dashboard-ul managerului nu este disponibil.</div>';
    }
    
    /**
     * Render receptionist dashboard
     */
    public function render_receptionist_dashboard($atts) {
        // Verifica daca utilizatorul este autentificat Cii are rolul de receptionist
        if (!is_user_logged_in()) {
            return '<p>Trebuie sa fiCii autentificat pentru a accesa dashboard-ul receptionist.</p>';
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<p>Nu aveCii permisiunea de a accesa dashboard-ul receptionist.</p>';
        }
        
        // Returneaza HTML-ul pentru dashboard-ul receptionist
        return Clinica_Receptionist_Dashboard::get_dashboard_html($user->ID);
    }
    
    /**
     * AJAX handler pentru Ancarcarea preview dashboard-uri
     */
    public function ajax_load_dashboard_preview() {
        check_ajax_referer('dashboard_preview_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Verifica daca utilizatorul este administrator
        if (!in_array('administrator', $user_roles)) {
            wp_die('Access denied');
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        
        switch ($dashboard_type) {
            case 'patient':
                if (class_exists('Clinica_Patient_Dashboard')) {
                    $dashboard = new Clinica_Patient_Dashboard();
                    echo $dashboard->render_dashboard_shortcode(array());
                } else {
                    echo '<div class="clinica-error">Dashboard-ul pacientului nu este disponibil.</div>';
                }
                break;
                
            case 'doctor':
                if (class_exists('Clinica_Doctor_Dashboard')) {
                    echo Clinica_Doctor_Dashboard::get_dashboard_html($current_user->ID);
                } else {
                    echo '<div class="clinica-error">Dashboard-ul doctorului nu este disponibil.</div>';
                }
                break;
                
            case 'assistant':
                if (class_exists('Clinica_Assistant_Dashboard')) {
                    echo Clinica_Assistant_Dashboard::get_dashboard_html($current_user->ID);
                } else {
                    echo '<div class="clinica-error">Dashboard-ul asistentului nu este disponibil.</div>';
                }
                break;
                
            case 'manager':
                if (class_exists('Clinica_Manager_Dashboard')) {
                    if (isset($this->manager_dashboard)) {
                        echo $this->manager_dashboard->render_dashboard(array());
                    } else {
                        // Creează o instanță temporară doar pentru shortcode
                        $dashboard = new Clinica_Manager_Dashboard();
                        echo $dashboard->render_dashboard(array());
                    }
                } else {
                    echo '<div class="clinica-error">Dashboard-ul managerului nu este disponibil.</div>';
                }
                break;
                
            case 'receptionist':
                if (class_exists('Clinica_Receptionist_Dashboard')) {
                    $dashboard = new Clinica_Receptionist_Dashboard();
                    echo $dashboard->render_dashboard_shortcode(array());
                } else {
                    echo '<div class="clinica-error">Dashboard-ul receptionistului nu este disponibil.</div>';
                }
                break;
                
            default:
                echo '<div class="clinica-error">Tip de dashboard invalid.</div>';
                break;
        }
        
        wp_die();
    }

    /**
     * AJAX handler pentru obCiinerea datelor pacientilor
     */
    public function ajax_get_patient_data() {
        check_ajax_referer('clinica_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $patient_id = intval($_POST['patient_id']);

        if ($patient_id <= 0) {
            wp_send_json_error('Invalid patient ID');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';

        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $patient_id
        ));

        if (!$patient) {
            wp_send_json_error('Patient not found');
            return;
        }

        $user = get_user_by('ID', $patient_id);

        wp_send_json_success(array(
            'user_id' => $patient_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->user_login,
            'email' => $patient->email,
            'phone_primary' => $patient->phone_primary,
            'phone_secondary' => $patient->phone_secondary,
            'birth_date' => $patient->birth_date,
            'gender' => $patient->gender,
            'age' => $patient->age,
            'password_method' => $patient->password_method,
            'address' => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'created_at' => $patient->created_at,
            'created_by' => $patient->created_by,
            'cnp' => $patient->cnp,
            'cnp_type' => $patient->cnp_type,
            // Informații de familie
            'family_id' => $patient->family_id,
            'family_role' => $patient->family_role,
            'family_head_id' => $patient->family_head_id,
            'family_name' => $patient->family_name
        ));
    }

    /**
     * AJAX handler pentru actualizarea datelor pacientilor
     */
    public function ajax_update_patient() {
        check_ajax_referer('clinica_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $patient_id = intval($_POST['patient_id']);
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone_primary = sanitize_text_field($_POST['phone_primary'] ?? '');
        $phone_secondary = sanitize_text_field($_POST['phone_secondary'] ?? '');
        $birth_date = sanitize_text_field($_POST['birth_date'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $password_method = sanitize_text_field($_POST['password_method'] ?? 'cnp');

        if ($patient_id <= 0) {
            wp_send_json_error('Invalid patient ID');
            return;
        }

        // Update WordPress user data
        $user_data = array(
            'ID' => $patient_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $email
        );

        $user_result = wp_update_user($user_data);

        if (is_wp_error($user_result)) {
            wp_send_json_error('Failed to update user data: ' . $user_result->get_error_message());
            return;
        }

        // Update patient data in clinica_patients table
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';

        $address = sanitize_textarea_field($_POST['address'] ?? '');
        $emergency_contact = sanitize_text_field($_POST['emergency_contact'] ?? '');
        
        // Procesează datele de familie
        $family_data = $this->process_family_update_data($_POST, $patient_id);
        
        $patient_data = array(
            'phone_primary' => $phone_primary,
            'phone_secondary' => $phone_secondary,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'password_method' => $password_method,
            'address' => $address,
            'emergency_contact' => $emergency_contact,
            // Adaugă datele de familie
            'family_id' => $family_data['family_id'],
            'family_role' => $family_data['family_role'],
            'family_head_id' => $family_data['family_head_id'],
            'family_name' => $family_data['family_name']
        );

        // Remove empty values to avoid overwriting with empty strings
        $patient_data = array_filter($patient_data, function($value) {
            return $value !== '';
        });

        if (!empty($patient_data)) {
            $result = $wpdb->update($table_name, $patient_data, array('user_id' => $patient_id));

            if ($result === false) {
                wp_send_json_error('Failed to update patient data in database: ' . $wpdb->last_error);
                return;
            }
        }

        // Update user meta for phone numbers
        if (!empty($phone_primary)) {
            update_user_meta($patient_id, 'phone_primary', $phone_primary);
        } else {
            delete_user_meta($patient_id, 'phone_primary');
        }
        if (!empty($phone_secondary)) {
            update_user_meta($patient_id, 'phone_secondary', $phone_secondary);
        } else {
            delete_user_meta($patient_id, 'phone_secondary');
        }

        wp_send_json_success(array('message' => 'Patient updated successfully'));
    }
    
    /**
     * Procesează datele de familie pentru actualizarea pacientului
     */
    private function process_family_update_data($data, $patient_id) {
        $family_data = array(
            'family_id' => null,
            'family_role' => null,
            'family_head_id' => null,
            'family_name' => null
        );
        
        // Verifică opțiunea de familie
        $family_option = sanitize_text_field($data['family_option'] ?? 'none');
        
        // Dacă păstrează familia actuală, obține datele din baza de date
        if ($family_option === 'current') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'clinica_patients';
            $current_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT family_id, family_role, family_head_id, family_name FROM $table_name WHERE user_id = %d",
                $patient_id
            ));
            
            if ($current_patient) {
                $family_data['family_id'] = $current_patient->family_id;
                $family_data['family_role'] = $current_patient->family_role;
                $family_data['family_head_id'] = $current_patient->family_head_id;
                $family_data['family_name'] = $current_patient->family_name;
            }
        }
        
        // Dacă creează o familie nouă
        if ($family_option === 'new') {
            $family_name = sanitize_text_field($data['family_name'] ?? '');
            $family_role = sanitize_text_field($data['family_role'] ?? '');
            
            if (!empty($family_name) && !empty($family_role)) {
                $family_manager = new Clinica_Family_Manager();
                $result = $family_manager->create_family($family_name, $patient_id);
                
                if ($result['success']) {
                    $family_data['family_id'] = $result['data']['family_id'];
                    $family_data['family_role'] = $family_role;
                    $family_data['family_head_id'] = $patient_id;
                    $family_data['family_name'] = $family_name;
                }
            }
        }
        
        // Dacă adaugă la o familie existentă
        if ($family_option === 'existing') {
            $selected_family_id = intval($data['selected_family_id'] ?? 0);
            $existing_family_role = sanitize_text_field($data['existing_family_role'] ?? '');
            
            if ($selected_family_id > 0 && !empty($existing_family_role)) {
                $family_manager = new Clinica_Family_Manager();
                $result = $family_manager->add_family_member($patient_id, $selected_family_id, $existing_family_role);
                
                if ($result['success']) {
                    // Obține informațiile despre familie
                    $family_members = $family_manager->get_family_members($selected_family_id);
                    if (!empty($family_members)) {
                        $family_head = null;
                        foreach ($family_members as $member) {
                            if ($member->family_role === 'head') {
                                $family_head = $member;
                                break;
                            }
                        }
                        
                        $family_data['family_id'] = $selected_family_id;
                        $family_data['family_role'] = $existing_family_role;
                        $family_data['family_head_id'] = $family_head ? $family_head->id : null;
                        $family_data['family_name'] = $family_head ? $family_head->family_name : null;
                    }
                }
            }
        }
        
        // Dacă nu face parte dintr-o familie, șterge din familia actuală dacă există
        if ($family_option === 'none') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'clinica_patients';
            $current_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT family_id FROM $table_name WHERE user_id = %d",
                $patient_id
            ));
            
            if ($current_patient && $current_patient->family_id) {
                // Șterge pacientul din familia actuală
                $family_manager = new Clinica_Family_Manager();
                $family_manager->remove_family_member($patient_id, $current_patient->family_id);
            }
        }
        
        return $family_data;
    }

    /**
     * AJAX handlers pentru Doctor Dashboard
     */
    public function ajax_get_doctor_overview() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru overview
        $data = array(
            'today' => array(
                'total' => 8,
                'confirmed' => 6,
                'pending' => 2
            ),
            'week' => array(
                'total' => 35,
                'completed' => 28,
                'cancelled' => 2
            ),
            'patients' => array(
                'active' => 45,
                'new' => 12
            )
        );
        
        wp_send_json_success($data);
    }

    public function ajax_get_doctor_activities() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru activitati
        $activities = array(
            '<div class="activity-item"><div class="activity-icon appointment"></div><div class="activity-content"><div class="activity-title">Programare finalizata pentru Ion Popescu</div><div class="activity-time">Acum 30 minute</div></div></div>',
            '<div class="activity-item"><div class="activity-icon patient"></div><div class="activity-content"><div class="activity-title">Nota medicala adaugata pentru Maria Ionescu</div><div class="activity-time">Acum 1 ora</div></div></div>',
            '<div class="activity-item"><div class="activity-icon appointment"></div><div class="activity-content"><div class="activity-title">Programare confirmata pentru maine</div><div class="activity-time">Acum 2 ore</div></div></div>'
        );
        
        $html = implode('', $activities);
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_doctor_appointments() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru programari
        $appointments = array(
            array(
                'id' => 1,
                'patient_name' => 'Ion Popescu',
                'patient_cnp' => '1234567890123',
                'date' => '2024-01-15',
                'time' => '09:00',
                'status' => 'confirmed',
                'type' => 'Consultatie',
                'notes' => 'Control de rutina'
            ),
            array(
                'id' => 2,
                'patient_name' => 'Maria Ionescu',
                'patient_cnp' => '9876543210987',
                'date' => '2024-01-15',
                'time' => '10:30',
                'status' => 'pending',
                'type' => 'Consultatie',
                'notes' => 'Prima consultaCiie'
            )
        );
        
        $html = '';
        foreach ($appointments as $appointment) {
            $html .= '<div class="appointment-item">';
            $html .= '<div class="appointment-time">' . esc_html($appointment['time']) . '</div>';
            $html .= '<div class="appointment-patient">' . esc_html($appointment['patient_name']) . '</div>';
            $html .= '<div class="appointment-type">' . esc_html($appointment['type']) . '</div>';
            $html .= '<div class="appointment-status status-' . esc_attr($appointment['status']) . '">' . esc_html($appointment['status']) . '</div>';
            $html .= '<div class="appointment-actions">';
            $html .= '<button onclick="updateAppointmentStatus(' . $appointment['id'] . ', \'confirmed\')" class="button-small">Confirma</button>';
            $html .= '<button onclick="updateAppointmentStatus(' . $appointment['id'] . ', \'cancelled\')" class="button-small button-secondary">Anuleaza</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_doctor_patients() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru pacienti
        $patients = array(
            array(
                'id' => 1,
                'name' => 'Ion Popescu',
                'cnp' => '1234567890123',
                'email' => 'ion.popescu@email.com',
                'phone' => '0722123456',
                'last_visit' => '2024-01-10',
                'appointments_count' => 5
            ),
            array(
                'id' => 2,
                'name' => 'Maria Ionescu',
                'cnp' => '9876543210987',
                'email' => 'maria.ionescu@email.com',
                'phone' => '0733123456',
                'last_visit' => '2024-01-12',
                'appointments_count' => 3
            )
        );
        
        $html = '';
        foreach ($patients as $patient) {
            $html .= '<div class="patient-item">';
            $html .= '<div class="patient-name">' . esc_html($patient['name']) . '</div>';
            $html .= '<div class="patient-cnp">' . esc_html($patient['cnp']) . '</div>';
            $html .= '<div class="patient-email">' . esc_html($patient['email']) . '</div>';
            $html .= '<div class="patient-phone">' . esc_html($patient['phone']) . '</div>';
            $html .= '<div class="patient-actions">';
            $html .= '<button onclick="viewPatientMedicalRecord(' . $patient['id'] . ')" class="button-small">Vezi Dosar</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_doctor_patients_select() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru selectarea pacientilor
        $patients = array(
            array('id' => 1, 'name' => 'Ion Popescu', 'cnp' => '1234567890123'),
            array('id' => 2, 'name' => 'Maria Ionescu', 'cnp' => '9876543210987'),
            array('id' => 3, 'name' => 'Gheorghe Popa', 'cnp' => '4567891230456')
        );
        
        $html = '<option value="">Selecteaza un pacient...</option>';
        foreach ($patients as $patient) {
            $html .= '<option value="' . $patient['id'] . '">' . esc_html($patient['name']) . ' (' . esc_html($patient['cnp']) . ')</option>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_doctor_medical_records() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Date demo pentru dosarul medical
        $html = '<div class="medical-records">';
        $html .= '<h3>Dosar Medical - Ion Popescu</h3>';
        $html .= '<div class="patient-info">';
        $html .= '<p><strong>CNP:</strong> 1234567890123</p>';
        $html .= '<p><strong>Data naCiterii:</strong> 15.03.1985</p>';
        $html .= '<p><strong>Grupa sanguina:</strong> A+</p>';
        $html .= '<p><strong>Alergii:</strong> Penicilina</p>';
        $html .= '</div>';
        $html .= '<div class="medical-notes">';
        $html .= '<h4>Note Medicale</h4>';
        $html .= '<div class="note-item">';
        $html .= '<div class="note-date">10.01.2024</div>';
        $html .= '<div class="note-content">Hipertensiune arteriala. Prescris Amlodipina 5mg zilnic.</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="add-note">';
        $html .= '<textarea id="medical-note-text" placeholder="Adauga o nota medicala..."></textarea>';
        $html .= '<button onclick="addMedicalNote(' . $patient_id . ')" class="button">Adauga Nota</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_update_doctor_appointment_status() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Aici ar trebui sa actualizezi statusul An baza de date
        // Pentru moment, doar returneaza succes
        
        wp_send_json_success('Status actualizat cu succes');
    }

    public function ajax_add_doctor_medical_note() {
        check_ajax_referer('clinica_doctor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $note_text = sanitize_textarea_field($_POST['note_text']);
        
        // Aici ar trebui sa salvezi nota An baza de date
        // Pentru moment, doar returneaza succes
        
        wp_send_json_success('Nota adaugata cu succes');
    }

    /**
     * AJAX handlers pentru Assistant Dashboard
     */
    public function ajax_get_assistant_overview() {
        check_ajax_referer('clinica_assistant_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru overview
        $data = array(
            'today' => array(
                'total' => 12,
                'confirmed' => 8,
                'pending' => 4
            ),
            'patients' => array(
                'today' => 3,
                'week' => 15,
                'month' => 45
            ),
            'doctors' => array(
                'total' => 8,
                'today' => 6
            )
        );
        
        wp_send_json_success($data);
    }

    public function ajax_get_assistant_appointments() {
        check_ajax_referer('clinica_assistant_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru programari
        $appointments = array(
            array(
                'id' => 1,
                'patient_name' => 'Ion Popescu',
                'doctor_name' => 'Dr. Popescu',
                'date' => '2024-01-15',
                'time' => '09:00',
                'status' => 'confirmed',
                'type' => 'Consultatie'
            ),
            array(
                'id' => 2,
                'patient_name' => 'Maria Ionescu',
                'doctor_name' => 'Dr. Ionescu',
                'date' => '2024-01-15',
                'time' => '10:30',
                'status' => 'pending',
                'type' => 'Consultatie'
            )
        );
        
        $html = '';
        foreach ($appointments as $appointment) {
            $html .= '<div class="appointment-item">';
            $html .= '<div class="appointment-time">' . esc_html($appointment['time']) . '</div>';
            $html .= '<div class="appointment-patient">' . esc_html($appointment['patient_name']) . '</div>';
            $html .= '<div class="appointment-doctor">' . esc_html($appointment['doctor_name']) . '</div>';
            $html .= '<div class="appointment-type">' . esc_html($appointment['type']) . '</div>';
            $html .= '<div class="appointment-status status-' . esc_attr($appointment['status']) . '">' . esc_html($appointment['status']) . '</div>';
            $html .= '<div class="appointment-actions">';
            $html .= '<button onclick="updateAppointmentStatus(' . $appointment['id'] . ', \'confirmed\')" class="button-small">Confirma</button>';
            $html .= '<button onclick="updateAppointmentStatus(' . $appointment['id'] . ', \'cancelled\')" class="button-small button-secondary">Anuleaza</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_assistant_patients() {
        check_ajax_referer('clinica_assistant_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru pacienti
        $patients = array(
            array(
                'id' => 1,
                'name' => 'Ion Popescu',
                'cnp' => '1234567890123',
                'email' => 'ion.popescu@email.com',
                'phone' => '0722123456',
                'last_visit' => '2024-01-10',
                'appointments_count' => 5
            ),
            array(
                'id' => 2,
                'name' => 'Maria Ionescu',
                'cnp' => '9876543210987',
                'email' => 'maria.ionescu@email.com',
                'phone' => '0733123456',
                'last_visit' => '2024-01-12',
                'appointments_count' => 3
            )
        );
        
        $html = '';
        foreach ($patients as $patient) {
            $html .= '<div class="patient-item">';
            $html .= '<div class="patient-name">' . esc_html($patient['name']) . '</div>';
            $html .= '<div class="patient-cnp">' . esc_html($patient['cnp']) . '</div>';
            $html .= '<div class="patient-email">' . esc_html($patient['email']) . '</div>';
            $html .= '<div class="patient-phone">' . esc_html($patient['phone']) . '</div>';
            $html .= '<div class="patient-actions">';
            $html .= '<button onclick="editPatient(' . $patient['id'] . ')" class="button-small">Editeaza</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_create_assistant_appointment() {
        check_ajax_referer('clinica_assistant_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Aici ar trebui sa salvezi programarea An baza de date
        // Pentru moment, doar returneaza succes
        
        wp_send_json_success('Programare creata cu succes');
    }

    public function ajax_update_assistant_appointment_status() {
        check_ajax_referer('clinica_assistant_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Aici ar trebui sa actualizezi statusul An baza de date
        // Pentru moment, doar returneaza succes
        
        wp_send_json_success('Status actualizat cu succes');
    }

    /**
     * AJAX handlers pentru Receptionist Dashboard
     */
    public function ajax_receptionist_overview() {
        check_ajax_referer('clinica_receptionist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru overview
        $data = array(
            'stats' => array(
                'today_appointments' => 24,
                'new_patients' => 8,
                'confirmed_appointments' => 18,
                'pending_appointments' => 6
            ),
            'upcoming_appointments' => array(
                array(
                    'id' => 1,
                    'time' => '09:00',
                    'patient' => 'Ionescu Maria',
                    'doctor' => 'Dr. Popescu',
                    'service' => 'Consultatie',
                    'status' => 'confirmed'
                ),
                array(
                    'id' => 2,
                    'time' => '10:30',
                    'patient' => 'Popescu Ion',
                    'doctor' => 'Dr. Ionescu',
                    'service' => 'Analize',
                    'status' => 'pending'
                )
            )
        );
        
        wp_send_json_success($data);
    }

    public function ajax_receptionist_appointments() {
        check_ajax_referer('clinica_receptionist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru programari
        $appointments = array(
            array(
                'id' => 1,
                'patient_name' => 'Ion Popescu',
                'doctor_name' => 'Dr. Popescu',
                'date' => '2024-01-15',
                'time' => '09:00',
                'status' => 'confirmed',
                'type' => 'Consultatie'
            ),
            array(
                'id' => 2,
                'patient_name' => 'Maria Ionescu',
                'doctor_name' => 'Dr. Ionescu',
                'date' => '2024-01-15',
                'time' => '10:30',
                'status' => 'pending',
                'type' => 'Consultatie'
            )
        );
        
        $html = '';
        foreach ($appointments as $appointment) {
            $html .= '<div class="appointment-item">';
            $html .= '<div class="appointment-time">' . esc_html($appointment['time']) . '</div>';
            $html .= '<div class="appointment-patient">' . esc_html($appointment['patient_name']) . '</div>';
            $html .= '<div class="appointment-doctor">' . esc_html($appointment['doctor_name']) . '</div>';
            $html .= '<div class="appointment-type">' . esc_html($appointment['type']) . '</div>';
            $html .= '<div class="appointment-status status-' . esc_attr($appointment['status']) . '">' . esc_html($appointment['status']) . '</div>';
            $html .= '<div class="appointment-actions">';
            $html .= '<button onclick="editAppointment(' . $appointment['id'] . ')" class="button-small">Editeaza</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_receptionist_patients() {
        check_ajax_referer('clinica_receptionist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru pacienti
        $patients = array(
            array(
                'id' => 1,
                'name' => 'Ion Popescu',
                'cnp' => '1234567890123',
                'email' => 'ion.popescu@email.com',
                'phone' => '0722123456',
                'last_visit' => '2024-01-10',
                'appointments_count' => 5
            ),
            array(
                'id' => 2,
                'name' => 'Maria Ionescu',
                'cnp' => '9876543210987',
                'email' => 'maria.ionescu@email.com',
                'phone' => '0733123456',
                'last_visit' => '2024-01-12',
                'appointments_count' => 3
            )
        );
        
        $html = '';
        foreach ($patients as $patient) {
            $html .= '<div class="patient-item">';
            $html .= '<div class="patient-name">' . esc_html($patient['name']) . '</div>';
            $html .= '<div class="patient-cnp">' . esc_html($patient['cnp']) . '</div>';
            $html .= '<div class="patient-email">' . esc_html($patient['email']) . '</div>';
            $html .= '<div class="patient-phone">' . esc_html($patient['phone']) . '</div>';
            $html .= '<div class="patient-actions">';
            $html .= '<button onclick="editPatient(' . $patient['id'] . ')" class="button-small">Editeaza</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_receptionist_calendar() {
        check_ajax_referer('clinica_receptionist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru calendar
        $data = array(
            'current_month' => 'Ianuarie 2024',
            'appointments' => array(
                array('date' => '2024-01-15', 'time' => '09:00', 'patient' => 'Ion Popescu', 'doctor' => 'Dr. Popescu'),
                array('date' => '2024-01-15', 'time' => '10:30', 'patient' => 'Maria Ionescu', 'doctor' => 'Dr. Ionescu'),
                array('date' => '2024-01-16', 'time' => '14:00', 'patient' => 'Gheorghe Popa', 'doctor' => 'Dr. Popescu')
            )
        );
        
        wp_send_json_success($data);
    }

    public function ajax_receptionist_reports() {
        check_ajax_referer('clinica_receptionist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteCii autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveCii permisiunea de a accesa aceasta funcCiionalitate');
        }
        
        // Date demo pentru rapoarte
        $data = array(
            'total_appointments' => 156,
            'confirmed_appointments' => 142,
            'cancelled_appointments' => 8,
            'new_patients' => 23,
            'total_revenue' => '15,600 RON'
        );
        
        wp_send_json_success($data);
    }

    /**
     * AJAX handlers pentru validare CNP si generare parola
     */
    public function ajax_validate_cnp() {
        // Verifica multiple nonce-uri pentru compatibilitate
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_doctor_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_validate_cnp')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $cnp = sanitize_text_field($_POST['cnp']);
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
        }
        
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        if ($result['valid']) {
            $parser = new Clinica_CNP_Parser();
            $parsed_data = $parser->parse_cnp($cnp);
            
            wp_send_json_success(array(
                'birth_date' => $parsed_data['birth_date'],
                'gender' => $parsed_data['gender'],
                'gender_label' => $parsed_data['gender'] === 'male' ? 'Masculin' : 'Feminin',
                'age' => $parsed_data['age'],
                'cnp_type' => $parsed_data['cnp_type'],
                'cnp_type_label' => $parsed_data['cnp_type']
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }

    public function ajax_generate_password() {
        // Verifica multiple nonce-uri pentru compatibilitate
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_doctor_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_generate_password')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $cnp = sanitize_text_field($_POST['cnp']);
        $password_method = sanitize_text_field($_POST['password_method'] ?? 'cnp');
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
        }
        
        $generator = new Clinica_Password_Generator();
        $password = $generator->generate_password($cnp, $password_method);
        
        wp_send_json_success(array('password' => $password));
    }

    public function ajax_create_patient() {
        // Verifica multiple nonce-uri pentru compatibilitate
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_doctor_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_create_patient')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
        }
        
        // Parse form data
        parse_str($_POST['form_data'], $form_data);
        
        $cnp = sanitize_text_field($form_data['cnp'] ?? '');
        $first_name = sanitize_text_field($form_data['first_name'] ?? '');
        $last_name = sanitize_text_field($form_data['last_name'] ?? '');
        $email = sanitize_email($form_data['email'] ?? '');
        $phone_primary = sanitize_text_field($form_data['phone_primary'] ?? '');
        $phone_secondary = sanitize_text_field($form_data['phone_secondary'] ?? '');
        $birth_date = sanitize_text_field($form_data['birth_date'] ?? '');
        $gender = sanitize_text_field($form_data['gender_value'] ?? '');
        $cnp_type = sanitize_text_field($form_data['cnp_type_value'] ?? '');
        $password_method = sanitize_text_field($form_data['password_method'] ?? 'cnp');
        
        if (empty($cnp) || empty($first_name) || empty($last_name)) {
            wp_send_json_error('Toate cAmpurile obligatorii trebuie completate');
        }
        
        // Verifica daca CNP-ul exista deja
        $existing_user = get_user_by('login', $cnp);
        if ($existing_user) {
            wp_send_json_error('Un pacient cu acest CNP exista deja An sistem');
        }
        
        // Genereaza parola
        $generator = new Clinica_Password_Generator();
        $password = $generator->generate_password($cnp, $password_method);
        
        // Creeaza utilizatorul WordPress
        $user_id = wp_create_user($cnp, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Eroare la crearea utilizatorului: ' . $user_id->get_error_message());
        }
        
        // Actualizeaza informaCiiile utilizatorului
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Atribuie rolul de pacient
        $user = new WP_User($user_id);
        $user->set_role('clinica_patient');
        
        // Salveaza An tabela clinica_patients
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $result = $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'cnp' => $cnp,
            'email' => $email,
            'phone_primary' => $phone_primary,
            'phone_secondary' => $phone_secondary,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'cnp_type' => $cnp_type,
            'password_method' => $password_method,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ));
        
        if ($result === false) {
            // Cterge utilizatorul daca inserarea An tabela pacienti a eCiuat
            wp_delete_user($user_id);
            wp_send_json_error('Eroare la salvarea datelor pacientului');
        }
        
        // Salveaza telefoanele ca user meta
        if (!empty($phone_primary)) {
            update_user_meta($user_id, 'phone_primary', $phone_primary);
        }
        if (!empty($phone_secondary)) {
            update_user_meta($user_id, 'phone_secondary', $phone_secondary);
        }
        
        // Trimite email de bun venit
        $this->send_welcome_email($user_id, $cnp, $password);
        
        wp_send_json_success('Pacientul a fost creat cu succes');
    }

    /**
     * AJAX handlers pentru debug
     */
    public function ajax_test_db_connection() {
        check_ajax_referer('clinica_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        
        // Testeaza conexiunea la baza de date
        $result = $wpdb->get_var("SELECT 1");
        
        if ($result === '1') {
            wp_send_json_success('Conexiunea la baza de date functioneaza');
        } else {
            wp_send_json_error('Eroare la conexiunea cu baza de date: ' . $wpdb->last_error);
        }
    }

    public function ajax_test_patient_query() {
        check_ajax_referer('clinica_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Verifica daca tabela exista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            wp_send_json_error('Tabela clinica_patients nu exista');
        }
        
        // Testeaza query-ul pentru pacienti
        $query = "SELECT p.*, u.user_email, u.display_name,
                  um1.meta_value as first_name, um2.meta_value as last_name
                  FROM $table_name p 
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                  LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                  ORDER BY p.created_at DESC 
                  LIMIT 5";
        
        $results = $wpdb->get_results($query);
        
        if ($results !== false) {
            wp_send_json_success(array(
                'count' => count($results),
                'message' => 'Query-ul functioneaza corect'
            ));
        } else {
            wp_send_json_error('Eroare la query-ul pacientilor: ' . $wpdb->last_error);
        }
    }

    public function ajax_create_test_patient() {
        check_ajax_referer('clinica_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        // Creeaza un pacient test
        $test_cnp = '1800404080170';
        $test_email = 'test.patient@example.com';
        $test_password = '123456';
        
        // Verifica daca CNP-ul exista deja
        $existing_user = get_user_by('login', $test_cnp);
        if ($existing_user) {
            wp_send_json_error('Un pacient cu acest CNP exista deja');
        }
        
        // Creeaza utilizatorul WordPress
        $user_id = wp_create_user($test_cnp, $test_password, $test_email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Eroare la crearea utilizatorului: ' . $user_id->get_error_message());
        }
        
        // Actualizeaza informatiile utilizatorului
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'display_name' => 'Test Patient'
        ));
        
        // Atribuie rolul de pacient
        $user = new WP_User($user_id);
        $user->set_role('clinica_patient');
        
        // Salveaza in tabela clinica_patients
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $result = $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'cnp' => $test_cnp,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'email' => $test_email,
            'phone_primary' => '0722123456',
            'birth_date' => '1980-04-04',
            'gender' => 'male',
            'cnp_type' => 'romanian',
            'password_method' => 'cnp',
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ));
        
        if ($result === false) {
            // Sterge utilizatorul daca inserarea in tabela pacienti a esuat
            wp_delete_user($user_id);
            wp_send_json_error('Eroare la salvarea datelor pacientului: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array(
            'user_id' => $user_id,
            'message' => 'Pacient test creat cu succes'
        ));
    }

    /**
     * AJAX handlers pentru corectarea problemelor
     */
    public function ajax_create_sample_patients() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Verifica daca tabela exista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_send_json_error('Tabela clinica_patients nu exista');
        }
        
        $created_count = 0;
        $sample_patients = [
            [
                'cnp' => '1800404080170',
                'first_name' => 'Ion',
                'last_name' => 'Popescu',
                'email' => 'ion.popescu@example.com',
                'phone' => '0722123456',
                'birth_date' => '1980-04-04',
                'gender' => 'male'
            ],
            [
                'cnp' => '2850515080281',
                'first_name' => 'Maria',
                'last_name' => 'Ionescu',
                'email' => 'maria.ionescu@example.com',
                'phone' => '0733123456',
                'birth_date' => '1985-05-15',
                'gender' => 'female'
            ],
            [
                'cnp' => '1900606080392',
                'first_name' => 'Vasile',
                'last_name' => 'Dumitrescu',
                'email' => 'vasile.dumitrescu@example.com',
                'phone' => '0744123456',
                'birth_date' => '1990-06-06',
                'gender' => 'male'
            ]
        ];
        
        foreach ($sample_patients as $patient_data) {
            // Verifica daca CNP-ul exista deja
            $existing_user = get_user_by('login', $patient_data['cnp']);
            if ($existing_user) {
                continue; // Sari peste daca exista deja
            }
            
            // Creeaza utilizatorul WordPress
            $user_id = wp_create_user($patient_data['cnp'], '123456', $patient_data['email']);
            
            if (is_wp_error($user_id)) {
                continue; // Sari peste daca esueaza
            }
            
            // Actualizeaza informatiile utilizatorului
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $patient_data['first_name'],
                'last_name' => $patient_data['last_name'],
                'display_name' => $patient_data['first_name'] . ' ' . $patient_data['last_name']
            ));
            
            // Atribuie rolul de pacient
            $user = new WP_User($user_id);
            $user->set_role('clinica_patient');
            
            // Salveaza in tabela clinica_patients
            $result = $wpdb->insert($table_name, array(
                'user_id' => $user_id,
                'cnp' => $patient_data['cnp'],
                'email' => $patient_data['email'],
                'phone_primary' => $patient_data['phone'],
                'birth_date' => $patient_data['birth_date'],
                'gender' => $patient_data['gender'],
                'cnp_type' => 'romanian',
                'password_method' => 'cnp',
                'created_at' => current_time('mysql'),
                'created_by' => get_current_user_id()
            ));
            
            if ($result !== false) {
                $created_count++;
            }
        }
        
        wp_send_json_success(array(
            'count' => $created_count,
            'message' => 'Pacienti test creati cu succes'
        ));
    }

    public function ajax_create_patients_table() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // SQL pentru crearea tabelei
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            cnp varchar(13) NOT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            phone_primary varchar(20) DEFAULT NULL,
            phone_secondary varchar(20) DEFAULT NULL,
            birth_date date DEFAULT NULL,
            gender enum('male','female') DEFAULT NULL,
            cnp_type enum('romanian','foreign_permanent','foreign_temporary') DEFAULT 'romanian',
            password_method enum('cnp','birth_date') DEFAULT 'cnp',
            address text DEFAULT NULL,
            emergency_contact varchar(20) DEFAULT NULL,
            blood_type varchar(5) DEFAULT NULL,
            allergies text DEFAULT NULL,
            medical_history text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            updated_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY cnp (cnp),
            KEY created_at (created_at),
            KEY birth_date (birth_date)
        ) " . $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        if (empty($wpdb->last_error)) {
            wp_send_json_success('Tabela creata cu succes');
        } else {
            wp_send_json_error('Eroare la crearea tabelei: ' . $wpdb->last_error);
        }
    }

    public function ajax_fix_permissions() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        // Incarca si creeaza rolurile
        require_once('includes/class-clinica-roles.php');
        Clinica_Roles::create_roles();
        
        // Adauga permisiuni la utilizatorul curent
        $current_user = wp_get_current_user();
        $current_user->add_cap('clinica_manage_all');
        $current_user->add_cap('clinica_view_patients');
        $current_user->add_cap('clinica_create_patients');
        $current_user->add_cap('clinica_edit_patients');
        $current_user->add_cap('clinica_delete_patients');
        
        wp_send_json_success('Permisiunile au fost corectate');
    }

    public function ajax_sync_patients() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Verifica daca tabela exista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_send_json_error('Tabela clinica_patients nu exista');
        }
        
        // Sincronizeaza utilizatorii cu rol clinica_patient
        $patients_users = get_users(array(
            'role' => 'clinica_patient',
            'number' => -1
        ));
        
        $synced_count = 0;
        foreach ($patients_users as $user) {
            // Verifica daca pacientul exista deja in tabela
            $existing_patient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user->ID
            ));
            
            if (!$existing_patient) {
                // Creeaza inregistrarea in tabela pacienti
                $wpdb->insert($table_name, array(
                    'user_id' => $user->ID,
                    'cnp' => $user->user_login,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->user_email,
                    'created_at' => current_time('mysql'),
                    'created_by' => get_current_user_id()
                ));
                $synced_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => "Sincronizare completa. $synced_count pacienti sincronizati."
        ));
    }

    public function ajax_create_missing_users() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Verifica daca tabela exista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_send_json_error('Tabela clinica_patients nu exista');
        }
        
        // Gaseste pacientii fara utilizatori WordPress
        $patients_without_users = $wpdb->get_results("
            SELECT p.* FROM $table_name p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        $created_count = 0;
        foreach ($patients_without_users as $patient) {
            // Creeaza utilizatorul WordPress
            $user_id = wp_create_user($patient->cnp, '123456', $patient->email);
            
            if (!is_wp_error($user_id)) {
                // Actualizeaza informatiile utilizatorului
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'display_name' => $patient->first_name . ' ' . $patient->last_name
                ));
                
                // Atribuie rolul de pacient
                $user = new WP_User($user_id);
                $user->set_role('clinica_patient');
                
                // Actualizeaza user_id in tabela pacienti
                $wpdb->update($table_name, 
                    array('user_id' => $user_id), 
                    array('id' => $patient->id)
                );
                
                $created_count++;
            }
        }
        
        wp_send_json_success(array(
            'count' => $created_count,
            'message' => 'Utilizatori creati cu succes'
        ));
    }

    /**
     * AJAX cu progres pentru sincronizare emailuri users <-> patients
     * Acceptă parametrii: step (offset), batch (dimensiune batch)
     * Returnează: total, processed, updated_patients, updated_users, differences_fixed, roles_set, done
     */
    public function ajax_sync_emails_progress() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }
        global $wpdb;
        $clinica_table = $wpdb->prefix . 'clinica_patients';
        $batch = isset($_POST['batch']) ? max(50, (int)$_POST['batch']) : 200;
        $step = isset($_POST['step']) ? max(0, (int)$_POST['step']) : 0;
        $log_file = CLINICA_PLUGIN_PATH . 'logs/sync-errors.log';
        if (!file_exists(dirname($log_file))) { @mkdir(dirname($log_file), 0755, true); }

        // Total înregistrări
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");
        // Colectează un batch
        $rows = $wpdb->get_results($wpdb->prepare("SELECT p.id, p.user_id, p.email AS p_email, u.user_email AS u_email FROM $clinica_table p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id ORDER BY p.id ASC LIMIT %d OFFSET %d", $batch, $step));

        $out = array('total' => $total, 'processed' => $step + count($rows), 'updated_patients' => 0, 'updated_users' => 0, 'differences_fixed' => 0, 'roles_set' => 0, 'done' => false);
        foreach ($rows as $r) {
            $p_email = trim((string)$r->p_email); $u_email = trim((string)$r->u_email);
            $p_valid = !empty($p_email) && is_email($p_email); $u_valid = !empty($u_email) && is_email($u_email);
            if (!$p_valid && $u_valid) {
                $res = $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id));
                if ($res !== false) { $out['updated_patients']++; } else { @file_put_contents($log_file, '['.current_time('mysql')."] emails->patients FAIL: patient_id={$r->id} err={$wpdb->last_error}\n", FILE_APPEND); }
            }
            if ($p_valid && !$u_valid && (int)$r->user_id > 0) {
                $res = $wpdb->update($wpdb->users, array('user_email' => $p_email), array('ID' => (int)$r->user_id));
                if ($res !== false) { $out['updated_users']++; } else { @file_put_contents($log_file, '['.current_time('mysql')."] patients->users FAIL: user_id={$r->user_id} err={$wpdb->last_error}\n", FILE_APPEND); }
            }
            if ($p_valid && $u_valid && strcasecmp($p_email, $u_email) !== 0) {
                $res = $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id));
                if ($res !== false) { $out['differences_fixed']++; } else { @file_put_contents($log_file, '['.current_time('mysql')."] align patients FAIL: patient_id={$r->id} err={$wpdb->last_error}\n", FILE_APPEND); }
            }
            if ((int)$r->user_id > 0) { $u = get_userdata((int)$r->user_id); if ($u && !in_array('clinica_patient', (array)$u->roles, true)) { $u->add_role('clinica_patient'); $out['roles_set']++; } }
        }
        if ($out['processed'] >= $total) { $out['done'] = true; update_option('clinica_last_sync', array('date' => current_time('mysql'), 'affected_patients' => $out['updated_patients'], 'affected_users' => $out['updated_users'], 'differences_fixed' => $out['differences_fixed'], 'roles_set' => $out['roles_set'], 'source' => 'ajax_progress')); }
        wp_send_json_success($out);
    }

    /**
     * AJAX cu progres pentru inserarea pacienților lipsă (subscriberi -> clinica_patients)
     */
    public function ajax_sync_patients_progress() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }
        global $wpdb;
        $clinica_table = $wpdb->prefix . 'clinica_patients';
        $cap_key = $wpdb->prefix . 'capabilities';
        $batch = isset($_POST['batch']) ? max(50, (int)$_POST['batch']) : 200;
        $step = isset($_POST['step']) ? max(0, (int)$_POST['step']) : 0;

        // Total subscriberi care nu au înregistrare în patients
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} u WHERE u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '$cap_key' AND meta_value LIKE '%subscriber%') AND u.ID NOT IN (SELECT user_id FROM $clinica_table)");

        // Batch de utilizatori
        $users = $wpdb->get_results($wpdb->prepare("SELECT u.ID, u.user_login, u.user_email,
            (SELECT meta_value FROM {$wpdb->usermeta} m WHERE m.user_id=u.ID AND m.meta_key='phone_primary' LIMIT 1) AS phone_primary,
            (SELECT meta_value FROM {$wpdb->usermeta} m WHERE m.user_id=u.ID AND m.meta_key='phone_secondary' LIMIT 1) AS phone_secondary
            FROM {$wpdb->users} u
            WHERE u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '$cap_key' AND meta_value LIKE '%subscriber%')
            AND u.ID NOT IN (SELECT user_id FROM $clinica_table)
            ORDER BY u.ID ASC LIMIT %d OFFSET %d", $batch, $step));

        // Verifică dacă query-ul a reușit
        if ($users === null) {
            wp_send_json_error('Eroare la interogarea bazei de date: ' . $wpdb->last_error);
        }

        $out = array('total' => $total, 'processed' => $step + count($users), 'inserted' => 0, 'roles_set' => 0, 'done' => false);
        foreach ($users as $user) {
            $cnp = $user->user_login; 
            $len = strlen($cnp); 
            $is_numeric = ctype_digit($cnp); 
            $is_valid_cnp = ($is_numeric && $len >= 12 && $len <= 14);
            
            if (!$is_valid_cnp) { continue; }
            
            // Folosește clasa CNP Parser modificată pentru validarea corectă
            require_once CLINICA_PLUGIN_PATH . 'includes/class-clinica-cnp-parser.php';
            $cnp_parser = new Clinica_CNP_Parser();
            $cnp_data = $cnp_parser->parse_cnp($cnp);
            
            // Verifică dacă CNP-ul este valid (inclusiv data de naștere)
            if (empty($cnp_data['birth_date'])) {
                continue; // Sari peste CNP-urile invalide
            }
            
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
            if (false !== $wpdb->insert($clinica_table, $data)) { $out['inserted']++; }
            $wp_u = get_userdata((int)$user->ID); if ($wp_u && !in_array('clinica_patient', (array)$wp_u->roles, true)) { $wp_u->add_role('clinica_patient'); $out['roles_set']++; }
        }
        if ($out['processed'] >= $total) { $out['done'] = true; }
        wp_send_json_success($out);
    }

    /**
     * Returnează ultimele 50 linii din logs/sync-errors.log pentru afișare în UI
     */
    public function ajax_get_sync_errors() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }
        $log_file = CLINICA_PLUGIN_PATH . 'logs/sync-errors.log';
        if (!file_exists($log_file)) {
            wp_send_json_success(array('lines' => array()));
        }
        $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            wp_send_json_error('Nu pot citi fișierul de log.');
        }
        $slice = array_slice($lines, -50);
        // escape basic html
        $slice = array_map(function($l){ return esc_html($l); }, $slice);
        wp_send_json_success(array('lines' => $slice));
    }

    /**
     * Descarcă log-ul complet de erori (doar admin)
     */
    public function ajax_download_sync_log() {
        check_admin_referer('clinica_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }
        $log_file = CLINICA_PLUGIN_PATH . 'logs/sync-errors.log';
        if (!file_exists($log_file)) {
            wp_die('Nu există log.');
        }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sync-errors.log"');
        readfile($log_file);
        exit;
    }

    /**
     * Arhivează logul curent și îl golește (doar admin)
     */
    public function ajax_archive_sync_log() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }
        $log_file = CLINICA_PLUGIN_PATH . 'logs/sync-errors.log';
        if (!file_exists($log_file)) {
            wp_send_json_error('Nu există log de arhivat.');
        }
        $timestamp = current_time('Ymd_His');
        $archive = CLINICA_PLUGIN_PATH . 'logs/sync-errors-' . $timestamp . '.log';
        if (!@copy($log_file, $archive)) {
            wp_send_json_error('Copierea logului a eșuat.');
        }
        if (@file_put_contents($log_file, '') === false) {
            wp_send_json_error('Nu pot goli logul după arhivare.');
        }
        wp_send_json_success(array('archive' => basename($archive)));
    }

    public function ajax_test_page_access() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        // Testeaza accesul la pagina pacienti
        $can_view_patients = Clinica_Patient_Permissions::can_view_patients();
        $can_create_patients = Clinica_Patient_Permissions::can_create_patient();
        
        if ($can_view_patients) {
            wp_send_json_success(array(
                'message' => 'Accesul la pagina pacienti functioneaza corect'
            ));
        } else {
            wp_send_json_error('Utilizatorul nu are permisiunea de a vedea pacientii');
        }
    }

    public function ajax_final_check() {
        check_ajax_referer('clinica_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $issues = array();
        $successes = array();
        
        // Verifica 1: Tabela exista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if ($table_exists) {
            $successes[] = 'Tabela clinica_patients exista';
        } else {
            $issues[] = 'Tabela clinica_patients nu exista';
        }
        
        // Verifica 2: Exista pacienti
        if ($table_exists) {
            $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            if ($total_patients > 0) {
                $successes[] = "Exista $total_patients pacienti in baza de date";
            } else {
                $issues[] = 'Nu exista pacienti in baza de date';
            }
        }
        
        // Verifica 3: Permisiuni
        $can_view_patients = Clinica_Patient_Permissions::can_view_patients();
        if ($can_view_patients) {
            $successes[] = 'Utilizatorul are permisiunea de a vedea pacientii';
        } else {
            $issues[] = 'Utilizatorul nu are permisiunea de a vedea pacientii';
        }
        
        // Verifica 4: Query functioneaza
        if ($table_exists) {
            $test_query = "SELECT COUNT(*) FROM $table_name";
            $result = $wpdb->get_var($test_query);
            if ($result !== false) {
                $successes[] = 'Query-ul pentru pacienti functioneaza';
            } else {
                $issues[] = 'Query-ul pentru pacienti nu functioneaza';
            }
        }
        
        $message = '';
        if (!empty($successes)) {
            $message .= ' ' . implode(', ', $successes) . '. ';
        }
        if (!empty($issues)) {
            $message .= ' Probleme: ' . implode(', ', $issues);
        }
        
        if (empty($issues)) {
            wp_send_json_success(array(
                'message' => $message ?: 'Toate verificarile au trecut cu succes!'
            ));
        } else {
            wp_send_json_error($message);
        }
    }

    /**
     * Sincronizeaza automat pacienții existenți din WordPress cu tabela clinica_patients
     * Această metodă este apelată la activarea plugin-ului
     */
    private function auto_sync_existing_patients() {
        global $wpdb;
        
        // Verifică dacă tabela există
        $clinica_table = $wpdb->prefix . 'clinica_patients';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$clinica_table'");
        
        if (!$table_exists) {
            return; // Tabela nu există încă, va fi creată de Clinica_Database
        }
        
        // Găsește toți utilizatorii cu rolul "clinica_patient"
        $patients_query = "
            SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
                   um1.meta_value as first_name,
                   um2.meta_value as last_name,
                   um3.meta_value as phone_primary,
                   um4.meta_value as phone_secondary
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone_primary'
            LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone_secondary'
            WHERE u.ID IN (
                SELECT user_id 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = '{$wpdb->prefix}capabilities' 
                AND meta_value LIKE '%clinica_patient%'
            )
            ORDER BY u.user_registered DESC
        ";
        
        $patients = $wpdb->get_results($patients_query);
        
        if (empty($patients)) {
            return; // Nu există pacienți de sincronizat
        }
        
        // Găsește pacienții care trebuie sincronizați
        $existing_patients = $wpdb->get_results("SELECT user_id FROM $clinica_table");
        $existing_user_ids = array_column($existing_patients, 'user_id');
        
        $synced_count = 0;
        foreach ($patients as $patient) {
            if (!in_array($patient->ID, $existing_user_ids)) {
                // Verifică dacă CNP-ul este valid (13 cifre)
                $cnp = $patient->user_login;
                if (strlen($cnp) === 13 && ctype_digit($cnp)) {
                    // Parsează CNP-ul pentru informații
                    $parser = new Clinica_CNP_Parser();
                    $parsed_data = $parser->parse_cnp($cnp);
                    
                    // Pregătește datele pentru inserare
                    $patient_data = array(
                        'user_id' => $patient->ID,
                        'cnp' => $cnp,
                        'cnp_type' => $parsed_data['type'] ?? 'romanian',
                        'phone_primary' => $patient->phone_primary ?? '',
                        'phone_secondary' => $patient->phone_secondary ?? '',
                        'birth_date' => $parsed_data['birth_date'] ?? null,
                        'gender' => $parsed_data['gender'] ?? null,
                        'age' => $parsed_data['age'] ?? null,
                        'address' => '',
                        'emergency_contact' => '',
                        'blood_type' => '',
                        'allergies' => '',
                        'medical_history' => '',
                        'password_method' => 'cnp',
                        'import_source' => 'auto_sync_activation',
                        'created_by' => 1, // Admin user ID
                        'created_at' => $patient->user_registered ?: current_time('mysql')
                    );
                    
                    // Inserează în tabela clinica_patients
                    $result = $wpdb->insert($clinica_table, $patient_data);
                    if ($result !== false) {
                        $synced_count++;
                    }
                }
            }
        }
        
        // Salvează informația despre sincronizare
        if ($synced_count > 0) {
            update_option('clinica_last_sync', array(
                'date' => current_time('mysql'),
                'count' => $synced_count,
                'source' => 'activation'
            ));
        }
    }
    
    /**
     * Verifică și sincronizează automat pacienții dacă este necesar
     * Această metodă este apelată la fiecare acces în admin
     */
    public function check_and_auto_sync_patients() {
        // Verifică dacă a trecut suficient timp de la ultima verificare (max 1 dată pe zi)
        $last_check = get_option('clinica_last_sync_check', 0);
        $current_time = time();
        
        if ($current_time - $last_check < 86400) { // 24 ore
            return;
        }
        
        global $wpdb;
        $clinica_table = $wpdb->prefix . 'clinica_patients';
        
        // Verifică dacă tabela există și are pacienți
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$clinica_table'");
        if (!$table_exists) {
            return;
        }
        
        $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");
        
        // Dacă tabela este goală, sincronizează automat
        if ($total_patients == 0) {
            $this->auto_sync_existing_patients();
            
            // Salvează timpul verificării
            update_option('clinica_last_sync_check', $current_time);
            
            // Adaugă un mesaj de notificare pentru admin
            if (is_admin()) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-info is-dismissible">';
                    echo '<p><strong>Clinica Plugin:</strong> Pacienții au fost sincronizați automat cu baza de date.</p>';
                    echo '</div>';
                });
            }
        }
    }
    
    /**
     * AJAX handler pentru căutarea de sugestii pacienți
     */
    public function ajax_search_patients_suggestions() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_search_nonce')) {
            wp_die('Eroare de securitate');
        }
        
        // Verifică permisiunile
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die('Nu aveți permisiunea de a efectua această acțiune');
        }
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $search_type = sanitize_text_field($_POST['search_type'] ?? '');
        
        if (strlen($search_term) < 2) {
            wp_send_json_error('Termenul de căutare trebuie să aibă cel puțin 2 caractere');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $suggestions = array();
        
        if ($search_type === 'search-input') {
            // Căutare generală - nume, email, telefon
            $query = $wpdb->prepare(
                "SELECT p.user_id, p.cnp, p.phone_primary, p.phone_secondary, p.family_id, p.family_name,
                        u.user_email, u.display_name,
                        um1.meta_value as first_name, um2.meta_value as last_name
                 FROM $table_name p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                 WHERE (um1.meta_value LIKE %s OR um2.meta_value LIKE %s OR u.user_email LIKE %s 
                        OR p.phone_primary LIKE %s OR p.phone_secondary LIKE %s OR p.cnp LIKE %s)
                 ORDER BY um1.meta_value, um2.meta_value
                 LIMIT 10",
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%'
            );
            
            $results = $wpdb->get_results($query);
            
            foreach ($results as $result) {
                $full_name = trim($result->first_name . ' ' . $result->last_name);
                $name = !empty($full_name) ? $full_name : $result->display_name;
                
                $suggestions[] = array(
                    'id' => (int)$result->user_id,
                    'name' => $name,
                    'email' => $result->user_email,
                    'phone' => $result->phone_primary ?: $result->phone_secondary,
                    'cnp' => $result->cnp,
                    'family_name' => $result->family_name
                );
            }
            
        } elseif ($search_type === 'cnp-filter') {
            // Căutare specifică CNP
            $query = $wpdb->prepare(
                "SELECT p.user_id, p.cnp,
                        um1.meta_value as first_name, um2.meta_value as last_name
                 FROM $table_name p 
                 LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                 WHERE p.cnp LIKE %s
                 ORDER BY p.cnp
                 LIMIT 10",
                '%' . $wpdb->esc_like($search_term) . '%'
            );
            
            $results = $wpdb->get_results($query);
            
            foreach ($results as $result) {
                $full_name = trim($result->first_name . ' ' . $result->last_name);
                $name = !empty($full_name) ? $full_name : 'Necunoscut';
                
                $suggestions[] = array(
                    'cnp' => $result->cnp,
                    'name' => $name
                );
            }
        }
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'searchTerm' => $search_term
        ));
    }
    
    /**
     * AJAX handler pentru căutarea de sugestii familii
     */
    public function ajax_search_families_suggestions() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_nonce')) {
            wp_die('Eroare de securitate');
        }
        
        // Verifică permisiunile
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die('Nu aveți permisiunea de a efectua această acțiune');
        }
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        
        if (strlen($search_term) < 2) {
            wp_send_json_error('Termenul de căutare trebuie să aibă cel puțin 2 caractere');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Căutare familii
        $query = $wpdb->prepare(
            "SELECT family_id, family_name, COUNT(*) as member_count,
                    GROUP_CONCAT(
                        CONCAT(um1.meta_value, ' ', um2.meta_value, ' (', family_role, ')')
                        ORDER BY family_role = 'head' DESC, um1.meta_value
                        SEPARATOR ', '
                    ) as members
             FROM $table_name p
             LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
             WHERE family_id IS NOT NULL AND family_id > 0 
                   AND family_name LIKE %s
             GROUP BY family_id, family_name
             ORDER BY family_name
             LIMIT 10",
            '%' . $wpdb->esc_like($search_term) . '%'
        );
        
        $results = $wpdb->get_results($query);
        $suggestions = array();
        
        foreach ($results as $result) {
            $suggestions[] = array(
                'family_id' => $result->family_id,
                'family_name' => $result->family_name,
                'member_count' => $result->member_count,
                'members' => $result->members
            );
        }
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'searchTerm' => $search_term
        ));
    }
    
    /**
     * AJAX handler pentru obținerea log-urilor familiilor
     */
    public function ajax_get_family_logs() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_logs_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveți permisiunea de a accesa log-urile');
        }
        
        $logs = Clinica_Family_Auto_Creator::get_family_creation_logs(20);
        $html = $this->render_family_logs_html($logs);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler pentru exportul log-urilor
     */
    public function ajax_export_family_logs() {
        if (!wp_verify_nonce($_GET['nonce'], 'clinica_family_logs_nonce')) {
            wp_die('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Nu aveți permisiunea de a exporta log-urile');
        }
        
        $logs = Clinica_Family_Auto_Creator::get_family_creation_logs(100);
        
        // Setează headers pentru download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="family-creation-logs-' . date('Y-m-d') . '.csv"');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Header CSV
        fputcsv($output, array('Data', 'Utilizator', 'Familii Create', 'Detalii'));
        
        foreach ($logs as $log) {
            $families_summary = array();
            foreach ($log['families'] as $family) {
                $families_summary[] = $family['family_name'] . ' (' . count($family['members']) . ' membri)';
            }
            
            fputcsv($output, array(
                $log['timestamp'],
                $log['user_name'],
                count($log['families']),
                implode('; ', $families_summary)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX handler pentru ștergerea log-urilor vechi
     */
    public function ajax_clear_old_logs() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_family_logs_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveți permisiunea de a șterge log-urile');
        }
        
        Clinica_Family_Auto_Creator::cleanup_old_logs(30);
        
        wp_send_json_success('Log-urile vechi au fost șterse cu succes');
    }
    
    /**
     * Render HTML pentru log-urile familiilor
     */
    private function render_family_logs_html($logs) {
        if (empty($logs)) {
            return '<div style="text-align: center; padding: 40px; color: #666;">
                <h3>Nu există log-uri</h3>
                <p>Nu s-au creat familii automat încă.</p>
            </div>';
        }
        
        $html = '<div class="family-logs">';
        
        foreach (array_reverse($logs) as $log) {
            $html .= '<div class="log-entry" style="border: 1px solid #ddd; margin: 15px 0; padding: 20px; border-radius: 8px; background: white;">';
            
            // Header log
            $html .= '<div class="log-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">';
            $html .= '<div>';
            $html .= '<h4 style="margin: 0; color: #0073aa;">Creare Automată Familii</h4>';
            $html .= '<p style="margin: 5px 0; color: #666; font-size: 12px;">';
            $html .= '<strong>Data:</strong> ' . date('d.m.Y H:i:s', strtotime($log['timestamp'])) . ' | ';
            $html .= '<strong>Utilizator:</strong> ' . esc_html($log['user_name']) . ' | ';
            $html .= '<strong>Familii create:</strong> ' . $log['total_families'];
            $html .= '</p>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Detalii familii
            if (!empty($log['families'])) {
                $html .= '<div class="families-details">';
                $html .= '<h5 style="margin: 15px 0 10px 0; color: #495057;">Familii create:</h5>';
                
                foreach ($log['families'] as $family) {
                    $html .= '<div class="family-detail" style="border: 1px solid #e9ecef; margin: 10px 0; padding: 15px; border-radius: 5px; background: #f8f9fa;">';
                    $html .= '<h6 style="margin: 0 0 10px 0; color: #0073aa;">' . esc_html($family['family_name']) . '</h6>';
                    $html .= '<p style="margin: 5px 0; color: #666; font-size: 12px;">';
                    $html .= '<strong>ID Familie:</strong> ' . $family['family_id'] . ' | ';
                    $html .= '<strong>Email de bază:</strong> ' . esc_html($family['base_email']) . ' | ';
                    $html .= '<strong>Membri:</strong> ' . count($family['members']);
                    $html .= '</p>';
                    
                    // Membrii familiei
                    if (!empty($family['members'])) {
                        $html .= '<div class="family-members" style="margin-top: 10px;">';
                        $html .= '<p style="margin: 5px 0; font-weight: 500; color: #495057;">Membri:</p>';
                        $html .= '<div style="display: flex; flex-wrap: wrap; gap: 5px;">';
                        
                        foreach ($family['members'] as $member) {
                            $html .= '<span style="display: inline-block; padding: 3px 8px; background: #e9ecef; border-radius: 3px; font-size: 11px; color: #495057;">';
                            $html .= esc_html($member['patient_name']) . ' (' . esc_html($member['role_label']) . ')';
                            $html .= '</span>';
                        }
                        
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * AJAX handlers pentru statusul pacienților
     */
    public function ajax_update_patient_status() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Actualizează statusul în user meta
        update_user_meta($patient_id, 'clinica_patient_status', $status);
        
        wp_send_json_success('Status actualizat cu succes');
    }

    public function ajax_block_patient() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Blochează pacientul - setează statusul ca 'blocked'
        update_user_meta($patient_id, 'clinica_patient_status', 'blocked');
        
        wp_send_json_success('Pacientul a fost blocat cu succes');
    }

    public function ajax_unblock_patient() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Deblochează pacientul - setează statusul ca 'active'
        update_user_meta($patient_id, 'clinica_patient_status', 'active');
        
        wp_send_json_success('Pacientul a fost deblocat cu succes');
    }

    public function ajax_reactivate_patient() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă pacientul este marcat ca decedat
        $reason = get_user_meta($patient_id, 'clinica_inactive_reason', true);
        if ($reason === 'deces') {
            wp_send_json_error('Nu se poate reactiva un pacient marcat ca decedat');
        }
        
        // Reactivează pacientul - setează statusul ca 'active'
        update_user_meta($patient_id, 'clinica_patient_status', 'active');
        
        wp_send_json_success('Pacientul a fost reactivat cu succes');
    }

    public function ajax_set_inactive_reason() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        // Validează motivul
        if (!in_array($reason, ['deces', 'transfer'])) {
            wp_send_json_error('Motivul trebuie să fie "deces" sau "transfer"');
        }
        
        // Salvează motivul pentru care pacientul a devenit inactiv
        update_user_meta($patient_id, 'clinica_inactive_reason', $reason);
        
        wp_send_json_success('Motivul pentru care pacientul a devenit inactiv a fost salvat cu succes');
    }

    public function ajax_get_inactive_reason() {
        check_ajax_referer('clinica_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveti permisiunea de a accesa aceasta functionalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $reason = get_user_meta($patient_id, 'clinica_inactive_reason', true);
        
        wp_send_json_success(array('reason' => $reason));
    }
    
    /**
     * TEMPORAR: Forțează actualizarea rolurilor la fiecare încărcare a admin-ului
     */
    public function force_update_roles_temp() {
        // Rulează doar o dată per sesiune
        if (!get_transient('clinica_roles_updated')) {
            try {
                // Forțează actualizarea rolurilor
                Clinica_Roles::create_roles();
                
                // Adaugă manual permisiunea la toate rolurile
                $roles_to_update = array('clinica_doctor', 'clinica_assistant', 'clinica_receptionist');
                
                foreach ($roles_to_update as $role_name) {
                    $role = get_role($role_name);
                    if ($role) {
                        $role->add_cap('clinica_manage_services');
                    }
                }
                
                set_transient('clinica_roles_updated', true, 3600); // 1 oră
            } catch (Exception $e) {
                // Ignoră erorile
            }
        }
    }
    
    /**
     * AJAX handler pentru digest-ul programărilor (Live Updates)
     */
    public function ajax_appointments_digest() {
        $live_updates = Clinica_Live_Updates::get_instance();
        $live_updates->ajax_appointments_digest();
    }
    
    /**
     * AJAX handler pentru schimbările programărilor (Live Updates)
     */
    public function ajax_appointments_changes() {
        $live_updates = Clinica_Live_Updates::get_instance();
        $live_updates->ajax_appointments_changes();
    }
    
    /**
     * AJAX handler pentru corectarea automată a numelor cu cratime
     */
    public function ajax_auto_fix_dash_name() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_auto_fix_dash_name')) {
            wp_send_json_error('Eroare de securitate');
            return;
        }
        
        // Verifică permisiunile
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $new_first_name = sanitize_text_field($_POST['new_first_name']);
        $new_last_name = sanitize_text_field($_POST['new_last_name']);
        
        if (!$user_id || !$new_first_name || !$new_last_name) {
            wp_send_json_error('Parametri invalizi');
            return;
        }
        
        // Verifică dacă utilizatorul există
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('Utilizatorul nu există');
            return;
        }
        
        // Actualizează first_name și last_name
        $result1 = update_user_meta($user_id, 'first_name', $new_first_name);
        $result2 = update_user_meta($user_id, 'last_name', $new_last_name);
        
        if ($result1 === false || $result2 === false) {
            wp_send_json_error('Eroare la actualizarea metadatelor utilizatorului');
            return;
        }
        
        // Actualizează display_name
        $new_display_name = $new_last_name . ' ' . $new_first_name;
        $result3 = wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $new_display_name
        ));
        
        if (is_wp_error($result3)) {
            wp_send_json_error('Eroare la actualizarea display_name: ' . $result3->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Numele a fost corectat cu succes',
            'new_display_name' => $new_display_name,
            'new_first_name' => $new_first_name,
            'new_last_name' => $new_last_name
        ));
    }

}
// Initializeaza plugin-ul
Clinica_Plugin::get_instance();

// Hook-uri pentru normalizarea automată a numelor
add_action('user_register', array('Clinica_Database', 'normalize_user_names'));
add_action('profile_update', array('Clinica_Database', 'normalize_user_names'));

// Hook pentru activare
register_activation_hook(__FILE__, function() {
    // Creeaza rolurile
    Clinica_Roles::create_roles();
    
    // Creeaza tabelele (NON-DESTRUCTIVE)
    Clinica_Database::create_tables();
    
    // Creează tabelele pentru timeslots (NON-DESTRUCTIVE)
    $services_manager = Clinica_Services_Manager::get_instance();
    $services_manager->create_timeslots_tables();
    
    // Migrează la sistemul de roluri duble (NON-DESTRUCTIVE)
    if (!Clinica_Database::is_dual_roles_migrated()) {
        $migrated_count = Clinica_Database::migrate_to_dual_roles();
        error_log("[CLINICA] Dual roles migration completed. Migrated $migrated_count users.");
    }
    
    // Actualizează tabelele cu noile coloane pentru tracking (NON-DESTRUCTIVE)
    if (!Clinica_Database::is_tracking_updated()) {
        Clinica_Database::update_tables_for_tracking();
        error_log("[CLINICA] Tables updated for tracking creator/editor.");
    }
    
    // Verificare suplimentară pentru tabelele lipsă
    global $wpdb;
    $required_tables = array(
        $wpdb->prefix . 'clinica_patients',
        $wpdb->prefix . 'clinica_appointments',
        $wpdb->prefix . 'clinica_medical_records',
        $wpdb->prefix . 'clinica_settings',
        $wpdb->prefix . 'clinica_login_logs',
        $wpdb->prefix . 'clinica_imports',
        $wpdb->prefix . 'clinica_notifications',
        $wpdb->prefix . 'clinica_services',
        $wpdb->prefix . 'clinica_doctor_services',
        $wpdb->prefix . 'clinica_clinic_schedule',
        $wpdb->prefix . 'clinica_user_active_roles',
        $wpdb->prefix . 'clinica_doctor_timeslots'
    );
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        error_log("[CLINICA] Missing tables detected during activation: " . implode(', ', $missing_tables));
        // Recreează tabelele lipsă
        Clinica_Database::create_tables();
        $services_manager->create_timeslots_tables();
        error_log("[CLINICA] Missing tables recreated during activation.");
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    error_log("[CLINICA] Plugin activated safely - all tables verified and created if needed.");
});

// Hook pentru dezactivare
register_deactivation_hook(__FILE__, function() {
    // NU șterge nimic din baza de date pentru siguranță
    // Toate datele rămân intacte
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log dezactivarea
    error_log("[CLINICA] Plugin deactivated safely - all data preserved.");
});
