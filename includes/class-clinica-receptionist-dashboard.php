<?php
/**
 * Dashboard Receptionist - Clinica
 */
if (!defined('ABSPATH')) exit;

class Clinica_Receptionist_Dashboard {
    
    /**
     * Verifică nonce-ul AJAX cu suport pentru multiple variante
     */
    private function verify_ajax_nonce($nonce, $action = '') {
        $valid_nonces = array(
            'clinica_nonce',
            'clinica_frontend_nonce', 
            'clinica_dashboard_nonce',
            'clinica_assistant_dashboard_nonce',
            'clinica_doctor_nonce',
            'clinica_receptionist_nonce'
        );
        
        foreach ($valid_nonces as $valid_nonce) {
            if (wp_verify_nonce($nonce, $valid_nonce)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function __construct() {
        add_shortcode('clinica_receptionist_dashboard', array($this, 'render_dashboard_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_clinica_load_patient_form', array($this, 'ajax_load_patient_form'));
        add_action('wp_ajax_clinica_receptionist_overview', array($this, 'ajax_overview'));
        add_action('wp_ajax_clinica_receptionist_appointments', array($this, 'ajax_appointments'));
        add_action('wp_ajax_clinica_receptionist_patients', array($this, 'ajax_patients'));
        add_action('wp_ajax_clinica_receptionist_calendar', array($this, 'ajax_calendar'));
        add_action('wp_ajax_clinica_receptionist_reports', array($this, 'ajax_reports'));
    }

    public function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'clinica_receptionist_dashboard')) {
            wp_enqueue_style('clinica-receptionist-dashboard', plugin_dir_url(__FILE__) . '../assets/css/receptionist-dashboard.css', array(), '1.0.0');
            wp_enqueue_script('clinica-receptionist-dashboard', plugin_dir_url(__FILE__) . '../assets/js/receptionist-dashboard.js', array('jquery'), '1.0.0', true);
            
            // Include și CSS-ul pentru formularul de creare pacienți
            wp_enqueue_style('clinica-frontend', plugin_dir_url(__FILE__) . '../assets/css/frontend.css', array(), '1.0.0');
            
            // Include live updates script
            wp_enqueue_script('clinica-live-updates', plugin_dir_url(__FILE__) . '../assets/js/live-updates.js', array('jquery'), '1.0.0', true);
            
            // Localize script pentru AJAX
            wp_localize_script('clinica-receptionist-dashboard', 'clinicaReceptionistAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clinica_receptionist_nonce')
            ));
            
            // Localize script pentru Live Updates
            wp_localize_script('clinica-live-updates', 'clinicaLiveUpdatesAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clinica_live_updates_nonce'),
                'pollingInterval' => 15000
            ));

            // CSS pentru transfer frontend
            wp_enqueue_style(
                'clinica-transfer-frontend', 
                plugin_dir_url(__FILE__) . '../assets/css/transfer-frontend.css', 
                array(), 
                '1.0.0'
            );
            
            // JavaScript pentru transfer frontend
            wp_enqueue_script(
                'clinica-transfer-frontend', 
                plugin_dir_url(__FILE__) . '../assets/js/transfer-frontend.js', 
                array('jquery'), 
                '1.0.0', 
                true
            );
        }
    }

    public function render_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="clinica-error">Trebuie să fiți autentificat pentru a accesa dashboard-ul.</div>';
        }
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        if (!in_array('clinica_receptionist', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru recepționiști și administratori.</div>';
        }
        
        // Folosește metoda statică pentru consistență
        return self::get_dashboard_html($current_user->ID);
    }

    /**
     * Metodă statică pentru generarea HTML-ului dashboard-ului
     */
    public static function get_dashboard_html($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return '<div class="clinica-error">Utilizator invalid.</div>';
        }

        $user_roles = $user->roles;
        if (!in_array('clinica_receptionist', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru recepționiști și administratori.</div>';
        }

        ob_start();
        ?>
        <div class="clinica-receptionist-dashboard">
            <div class="clinica-receptionist-header">
                <h1>Dashboard Receptionist</h1>
                <p>Gestionare programări și pacienți</p>
            </div>

            <div class="clinica-receptionist-stats">
                <div class="clinica-receptionist-stat-card">
                    <h3>Programări Astăzi</h3>
                    <div class="stat-number">24</div>
                    <div class="stat-label">+3 față de ieri</div>
                </div>
                <div class="clinica-receptionist-stat-card">
                    <h3>Pacienți Noi</h3>
                    <div class="stat-number">8</div>
                    <div class="stat-label">+2 față de ieri</div>
                </div>
                <div class="clinica-receptionist-stat-card">
                    <h3>Programări Confirmate</h3>
                    <div class="stat-number">18</div>
                    <div class="stat-label">75% din total</div>
                </div>
                <div class="clinica-receptionist-stat-card">
                    <h3>Programări În Așteptare</h3>
                    <div class="stat-number">6</div>
                    <div class="stat-label">Necesită confirmare</div>
                </div>
            </div>

            <div class="clinica-receptionist-tabs">
                <div class="clinica-receptionist-tab-nav">
                    <button class="clinica-receptionist-tab-button active" data-tab="overview">
                        <span class="tab-icon dashicons dashicons-dashboard"></span>
                        Prezentare Generală
                    </button>
                    <button class="clinica-receptionist-tab-button" data-tab="appointments">
                        <span class="tab-icon dashicons dashicons-calendar-alt"></span>
                        Programări
                    </button>
                    <button class="clinica-receptionist-tab-button" data-tab="patients">
                        <span class="tab-icon dashicons dashicons-admin-users"></span>
                        Pacienți
                    </button>
                    <button class="clinica-receptionist-tab-button" data-tab="calendar">
                        <span class="tab-icon dashicons dashicons-calendar"></span>
                        Calendar
                    </button>
                    <button class="clinica-receptionist-tab-button" data-tab="reports">
                        <span class="tab-icon dashicons dashicons-chart-bar"></span>
                        Rapoarte
                    </button>
                </div>

                <div class="clinica-receptionist-tab-content active" data-tab="overview">
                    <div class="clinica-receptionist-actions">
                        <button class="clinica-receptionist-btn clinica-receptionist-btn-primary" data-action="add-appointment">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Programare Nouă
                        </button>
                        <button class="clinica-receptionist-btn clinica-receptionist-btn-success" data-action="add-patient">
                            <span class="dashicons dashicons-admin-users"></span>
                            Pacient Nou
                        </button>
                        <button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" data-action="view-calendar">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Vezi Calendarul
                        </button>
                    </div>

                    <div class="clinica-receptionist-form">
                        <h3>Programări Următoare</h3>
                        <table class="clinica-receptionist-table">
                            <thead>
                                <tr>
                                    <th>Ora</th>
                                    <th>Pacient</th>
                                    <th>Doctor</th>
                                    <th>Serviciu</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="clinica-receptionist-loading">Se încarcă programările...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="clinica-receptionist-tab-content" data-tab="appointments">
                    <div class="clinica-receptionist-loading">Se încarcă programările...</div>
                </div>

                <div class="clinica-receptionist-tab-content" data-tab="patients">
                    <div class="clinica-receptionist-loading">Se încarcă pacienții...</div>
                </div>

                <div class="clinica-receptionist-tab-content" data-tab="calendar">
                    <div class="clinica-receptionist-loading">Se încarcă calendarul...</div>
                </div>

                <div class="clinica-receptionist-tab-content" data-tab="reports">
                    <div class="clinica-receptionist-loading">Se încarcă rapoartele...</div>
                </div>
            </div>
        </div>

        <!-- Modal pentru transfer programări -->
        <?php include_once plugin_dir_path(__FILE__) . '../templates/transfer-modal-frontend.php'; ?>

        <script>
        // Variabile globale pentru AJAX
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var clinicaAjax = {
            ajaxurl: ajaxurl,
            nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
        };
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler pentru încărcarea formularului de creare pacienți
     */
    public function ajax_load_patient_form() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Încarcă formularul de creare pacienți
        if (class_exists('Clinica_Patient_Creation_Form')) {
            $patient_form = new Clinica_Patient_Creation_Form();
            $form_html = $patient_form->render_form();
            
            wp_send_json_success(array('form_html' => $form_html));
        } else {
            wp_send_json_error('Formularul de creare pacienți nu este disponibil');
        }
    }

    /**
     * AJAX handler pentru overview
     */
    public function ajax_overview() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
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
                    'time' => '09:00',
                    'patient' => 'Ionescu Maria',
                    'doctor' => 'Dr. Popescu',
                    'service' => 'Consultatie',
                    'status' => 'confirmed',
                    'id' => 1
                ),
                array(
                    'time' => '10:30',
                    'patient' => 'Popescu Ion',
                    'doctor' => 'Dr. Ionescu',
                    'service' => 'Analize',
                    'status' => 'pending',
                    'id' => 2
                )
            )
        );
        
        wp_send_json_success($data);
    }

    /**
     * AJAX handler pentru programări
     */
    public function ajax_appointments() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Obține programările pentru ziua curentă
        $today = date('Y-m-d');
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.appointment_date as date,
                a.appointment_time as time,
                a.status,
                a.duration,
                a.service_id,
                a.doctor_id,
                a.patient_id,
                p.first_name,
                p.last_name,
                p.cnp,
                d.display_name as doctor_name,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_patients p ON a.patient_id = p.user_id
            LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
            LEFT JOIN {$wpdb->prefix}clinica_services s ON a.service_id = s.id
            WHERE a.appointment_date = %s
            ORDER BY a.appointment_time ASC
        ", $today));
        
        $html = '';
        foreach ($appointments as $appointment) {
            $patient_name = trim($appointment->first_name . ' ' . $appointment->last_name);
            $service_name = $appointment->service_name ?: 'N/A';
            $duration = $appointment->duration ?: 30;
            
            $html .= '<div class="clinica-receptionist-appointment-item">';
            $html .= '<div class="appointment-time">' . esc_html($appointment->time) . '</div>';
            $html .= '<div class="appointment-patient">' . esc_html($patient_name) . '</div>';
            $html .= '<div class="appointment-doctor">' . esc_html($appointment->doctor_name) . '</div>';
            $html .= '<div class="appointment-type">' . esc_html($service_name) . '</div>';
            $html .= '<div class="appointment-status status-' . esc_attr($appointment->status) . '">' . esc_html($appointment->status) . '</div>';
            $html .= '<div class="appointment-actions">';
            $html .= '<button class="clinica-receptionist-btn clinica-receptionist-btn-secondary" onclick="editAppointment(' . $appointment->id . ')">Editează</button>';
            
            // Afișează butonul "Mută" doar dacă statusul permite transferul
            if (!in_array($appointment->status, array('completed', 'cancelled', 'no_show'))) {
                $html .= '<button class="clinica-receptionist-btn clinica-receptionist-btn-primary" onclick="openTransferModalFrontend(' . $appointment->id . ', ' . $appointment->doctor_id . ', ' . $appointment->patient_id . ', ' . ($appointment->service_id ?: 0) . ', \'' . $appointment->date . '\', \'' . $appointment->time . '\', ' . $duration . ', \'' . esc_js($patient_name ?: 'Pacient necunoscut') . '\', \'' . esc_js($appointment->doctor_name ?: 'Doctor necunoscut') . '\', \'' . esc_js($service_name) . '\')">Mută</button>';
            } else {
                $html .= '<button class="clinica-receptionist-btn clinica-receptionist-btn-disabled" disabled title="Programarea nu poate fi mutată (status: ' . esc_attr($appointment->status) . ')">Mută</button>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler pentru pacienți
     */
    public function ajax_patients() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Date demo pentru pacienți
        $data = array(
            'patients' => array(
                array(
                    'name' => 'Ionescu Maria',
                    'cnp' => '1234567890123',
                    'phone' => '0722123456',
                    'email' => 'maria.ionescu@email.com',
                    'registration_date' => '2024-01-10',
                    'id' => 1
                ),
                array(
                    'name' => 'Popescu Ion',
                    'cnp' => '9876543210987',
                    'phone' => '0733987654',
                    'email' => 'ion.popescu@email.com',
                    'registration_date' => '2024-01-12',
                    'id' => 2
                )
            )
        );
        
        wp_send_json_success($data);
    }

    /**
     * AJAX handler pentru calendar
     */
    public function ajax_calendar() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Date demo pentru calendar
        $data = array(
            'calendar_data' => 'Calendar interactiv va fi implementat aici'
        );
        
        wp_send_json_success($data);
    }

    /**
     * AJAX handler pentru rapoarte
     */
    public function ajax_reports() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_receptionist', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Date demo pentru rapoarte
        $data = array(
            'stats' => array(
                'monthly_appointments' => 156,
                'new_patients' => 45,
                'confirmation_rate' => 87
            )
        );
        
        wp_send_json_success($data);
    }
} 