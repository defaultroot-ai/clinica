<?php
/**
 * Dashboard Assistant - Clinica
 */
if (!defined('ABSPATH')) exit;

class Clinica_Assistant_Dashboard {
    
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
        add_shortcode('clinica_assistant_dashboard', array($this, 'render_dashboard_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_clinica_load_assistant_patient_form', array($this, 'ajax_load_patient_form'));
        add_action('wp_ajax_clinica_assistant_dashboard_overview', array($this, 'ajax_overview'));
        add_action('wp_ajax_clinica_assistant_dashboard_appointments', array($this, 'ajax_appointments'));
        add_action('wp_ajax_clinica_assistant_dashboard_patients', array($this, 'ajax_patients'));
        add_action('wp_ajax_clinica_assistant_dashboard_calendar', array($this, 'ajax_calendar'));
        add_action('wp_ajax_clinica_assistant_dashboard_reports', array($this, 'ajax_reports'));
        
        // AJAX handlers pentru căutare și sugestii
        add_action('wp_ajax_clinica_assistant_search_patients_suggestions', array($this, 'ajax_search_patients_suggestions'));
        add_action('wp_ajax_clinica_assistant_search_families_suggestions', array($this, 'ajax_search_families_suggestions'));
        
        // AJAX handlers pentru modaluri
        add_action('wp_ajax_clinica_assistant_get_patient_data', array($this, 'ajax_get_patient_data'));
        add_action('wp_ajax_clinica_assistant_update_patient', array($this, 'ajax_update_patient'));
        add_action('wp_ajax_clinica_assistant_create_appointment', array($this, 'ajax_create_appointment'));
        add_action('wp_ajax_clinica_assistant_get_appointment_modal_data', array($this, 'ajax_get_appointment_modal_data'));
        add_action('wp_ajax_clinica_assistant_create_appointment_advanced', array($this, 'ajax_create_appointment_advanced'));
    }

    public function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'clinica_assistant_dashboard')) {
            wp_enqueue_style('clinica-assistant-dashboard', plugin_dir_url(__FILE__) . '../assets/css/assistant-dashboard.css', array(), '1.2.0');
            
            // CSS pentru butonul Dashboard Pacient
            wp_enqueue_style('clinica-patient-dashboard-button', plugin_dir_url(__FILE__) . '../assets/css/patient-dashboard-button.css', array(), '1.0.0');
            
            wp_enqueue_script('clinica-assistant-dashboard', plugin_dir_url(__FILE__) . '../assets/js/assistant-dashboard.js', array('jquery'), '1.0.1', true);
            
            // Include și CSS-ul pentru formularul de creare pacienți
            wp_enqueue_style('clinica-frontend', plugin_dir_url(__FILE__) . '../assets/css/frontend.css', array(), '1.0.0');
            
            // Include live updates script
            wp_enqueue_script('clinica-live-updates', plugin_dir_url(__FILE__) . '../assets/js/live-updates.js', array('jquery'), '1.0.0', true);
            
            // Localize script pentru AJAX
            wp_localize_script('clinica-assistant-dashboard', 'clinicaAssistantAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clinica_assistant_dashboard_nonce'),
                'dashboard_nonce' => wp_create_nonce('clinica_dashboard_nonce')
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
        if (!in_array('clinica_assistant', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru asistenți și administratori.</div>';
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
        if (!in_array('clinica_assistant', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru asistenți și administratori.</div>';
        }

        // Nume afișat și eticheta rolului
        $display_name = trim((string)get_user_meta($user->ID, 'first_name', true) . ' ' . (string)get_user_meta($user->ID, 'last_name', true));
        if ($display_name === '') { $display_name = $user->display_name; }
        $role_label = in_array('administrator', $user_roles, true) ? 'Administrator' : 'Asistent';

        ob_start();
        ?>
        <div class="clinica-assistant-dashboard">
            <div class="clinica-assistant-header">
                <div class="header-left">
                    <h1>Portal Asistent</h1>
                    <p class="subtitle">Gestionare programări și pacienți</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-name"><?php echo esc_html($display_name); ?></div>
                        <div class="user-role"><?php echo esc_html($role_label); ?></div>
                    </div>
                    <?php if (in_array('clinica_patient', $user_roles)): ?>
                    <a href="<?php echo esc_url(home_url('/clinica-patient-dashboard/')); ?>" class="patient-dashboard-btn">
                        <i class="fa fa-user"></i> Cont pacient
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="clinica-assistant-stats">
                <!-- Rând 1 -->
                <div class="clinica-assistant-stat-card">
                    <h3>Programări azi</h3>
                    <div class="stat-number" id="today-appointments">--</div>
                    <div class="stat-label" id="today-appointments-detail">-- confirmate, -- în așteptare</div>
                </div>
                <div class="clinica-assistant-stat-card">
                    <h3>Următoarele 2 ore</h3>
                    <div class="stat-number" id="next-2h-appointments">--</div>
                    <div class="stat-label" id="next-2h-doctors">pe -- doctori</div>
                </div>
                <div class="clinica-assistant-stat-card">
                    <h3>De confirmat</h3>
                    <div class="stat-number" id="pending-confirmation">--</div>
                    <div class="stat-label">necesită acțiune azi</div>
                </div>
                <!-- Rând 2 -->
                <div class="clinica-assistant-stat-card">
                    <h3>Ocupare azi</h3>
                    <div class="stat-number" id="occupancy-today">--%</div>
                    <div class="stat-label" id="free-slots-today">-- sloturi libere; primul la --:--</div>
                </div>
                <div class="clinica-assistant-stat-card">
                    <h3>Anulări azi</h3>
                    <div class="stat-number" id="cancellations-today">--</div>
                    <div class="stat-label" id="cancellations-percentage">--% din total</div>
                </div>
                <div class="clinica-assistant-stat-card">
                    <h3>Neprezentări (7 zile)</h3>
                    <div class="stat-number" id="no-shows-week">--</div>
                    <div class="stat-label">trend stabil</div>
                </div>
            </div>

            <div class="clinica-assistant-tabs">
                <div class="clinica-assistant-tab-nav">
                    <button class="clinica-assistant-tab-button active" data-tab="overview">
                        <span class="tab-icon dashicons dashicons-dashboard"></span>
                        Prezentare Generală
                    </button>
                    <button class="clinica-assistant-tab-button" data-tab="appointments">
                        <span class="tab-icon dashicons dashicons-calendar-alt"></span>
                        Programări
                    </button>
                    <button class="clinica-assistant-tab-button" data-tab="patients">
                        <span class="tab-icon dashicons dashicons-admin-users"></span>
                        Pacienți
                    </button>
                    <button class="clinica-assistant-tab-button" data-tab="calendar">
                        <span class="tab-icon dashicons dashicons-calendar"></span>
                        Calendar
                    </button>
                    <button class="clinica-assistant-tab-button" data-tab="reports">
                        <span class="tab-icon dashicons dashicons-chart-bar"></span>
                        Rapoarte
                    </button>
                </div>

                <div class="clinica-assistant-tab-content active" data-tab="overview">
                    <div class="clinica-assistant-actions">
                        <button class="clinica-assistant-btn clinica-assistant-btn-primary" data-action="add-appointment">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Programare Nouă
                        </button>
                        <button class="clinica-assistant-btn clinica-assistant-btn-success" data-action="add-patient">
                            <span class="dashicons dashicons-admin-users"></span>
                            Pacient Nou
                        </button>
                        <button class="clinica-assistant-btn clinica-assistant-btn-secondary" data-action="view-calendar">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Vezi Calendarul
                        </button>
                    </div>

                    <div class="clinica-assistant-form">
                        <h3>Programări Următoare</h3>
                        <div class="clinica-assistant-appointments-table">
                            <div class="appointments-header">
                                <div class="header-date">Data</div>
                                <div class="header-day">Ziua</div>
                                <div class="header-time">Interval</div>
                                <div class="header-doctor">Doctor</div>
                                <div class="header-patient">Pacient</div>
                                <div class="header-service">Serviciu</div>
                                <div class="header-status">Status</div>
                                <div class="header-actions">Acțiuni</div>
                            </div>
                            <div class="clinica-assistant-appointments-list" id="assistant-appointments-tbody">
                                <!-- Programările vor fi încărcate dinamic prin JavaScript -->
                                <div class="no-appointments" style="display: none;">
                                    <p>Se încarcă programările...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clinica-assistant-tab-content" data-tab="appointments">
                    <div class="clinica-assistant-loading">Se încarcă programările...</div>
                </div>

                <div class="clinica-assistant-tab-content" data-tab="patients">
                    <div class="clinica-assistant-loading">Se încarcă pacienții...</div>
                </div>

                <div class="clinica-assistant-tab-content" data-tab="calendar">
                    <div class="clinica-assistant-loading">Se încarcă calendarul...</div>
                </div>

                <div class="clinica-assistant-tab-content" data-tab="reports">
                    <div class="clinica-assistant-loading">Se încarcă rapoartele...</div>
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
        check_ajax_referer('clinica_assistant_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
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
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Calculează statisticile reale
        $stats = $this->calculate_assistant_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * Calculează statisticile pentru dashboard-ul Asistent
     */
    private function calculate_assistant_stats() {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_services = $wpdb->prefix . 'clinica_services';
        $table_doctor_timeslots = $wpdb->prefix . 'clinica_doctor_timeslots';
        
        $today = current_time('Y-m-d');
        $now = current_time('H:i:s');
        $next_2h = date('H:i:s', strtotime('+2 hours'));
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        // Programări astăzi
        $today_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s
        ", $today));
        
        $today_confirmed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s AND status = 'confirmed'
        ", $today));
        
        $today_scheduled = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s AND status = 'scheduled'
        ", $today));
        
        // Programări în următoarele 2 ore
        $next_2h_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s 
            AND appointment_time BETWEEN %s AND %s
            AND status IN ('scheduled', 'confirmed')
        ", $today, $now, $next_2h));
        
        $next_2h_doctors = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT doctor_id) FROM $table_appointments 
            WHERE appointment_date = %s 
            AND appointment_time BETWEEN %s AND %s
            AND status IN ('scheduled', 'confirmed')
        ", $today, $now, $next_2h));
        
        // Programări de confirmat (scheduled pentru azi)
        $pending_confirmation = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s AND status = 'scheduled'
        ", $today));
        
        // Ocupare azi (calcul aproximativ)
        $total_slots_today = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_doctor_timeslots 
            WHERE day_of_week = %s AND is_active = 1
        ", strtolower(date('l', strtotime($today)))));
        
        $occupied_slots = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s AND status IN ('scheduled', 'confirmed')
        ", $today));
        
        $occupancy_percentage = $total_slots_today > 0 ? round(($occupied_slots / $total_slots_today) * 100) : 0;
        $free_slots = max(0, $total_slots_today - $occupied_slots);
        
        // Primul slot liber
        $first_free_slot = $wpdb->get_var($wpdb->prepare("
            SELECT MIN(appointment_time) FROM $table_appointments 
            WHERE appointment_date = %s 
            AND appointment_time > %s 
            AND status IN ('scheduled', 'confirmed')
            ORDER BY appointment_time ASC
        ", $today, $now));
        
        // Anulări azi
        $cancellations_today = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s AND status = 'cancelled'
        ", $today));
        
        $cancellations_percentage = $today_appointments > 0 ? round(($cancellations_today / $today_appointments) * 100) : 0;
        
        // Neprezentări ultimele 7 zile
        $no_shows_week = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date BETWEEN %s AND %s 
            AND status = 'no_show'
        ", $week_ago, $today));
        
        return array(
            'today_appointments' => intval($today_appointments),
            'today_confirmed' => intval($today_confirmed),
            'today_scheduled' => intval($today_scheduled),
            'next_2h_appointments' => intval($next_2h_appointments),
            'next_2h_doctors' => intval($next_2h_doctors),
            'pending_confirmation' => intval($pending_confirmation),
            'occupancy_percentage' => $occupancy_percentage,
            'free_slots' => $free_slots,
            'first_free_slot' => $first_free_slot ? substr($first_free_slot, 0, 5) : '--:--',
            'cancellations_today' => intval($cancellations_today),
            'cancellations_percentage' => $cancellations_percentage,
            'no_shows_week' => intval($no_shows_week)
        );
    }

    /**
     * AJAX handler pentru programări
     */
    public function ajax_appointments() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Obține programările reale din baza de date
        $appointments = $this->get_assistant_appointments();
        
        $html = '<div class="clinica-assistant-appointments-table">';
        $html .= '<div class="appointments-header">';
        $html .= '<div class="header-date">Data</div>';
        $html .= '<div class="header-day">Ziua</div>';
        $html .= '<div class="header-time">Interval</div>';
        $html .= '<div class="header-doctor">Doctor</div>';
        $html .= '<div class="header-patient">Pacient</div>';
        $html .= '<div class="header-service">Serviciu</div>';
        $html .= '<div class="header-status">Status</div>';
        $html .= '<div class="header-actions">Acțiuni</div>';
        $html .= '</div>';
        
        foreach ($appointments as $appointment) {
            // Calculează intervalul de timp (ex: 13:30-13:45)
            $start_time = substr($appointment['time'], 0, 5);
            $duration = isset($appointment['duration']) ? (int)$appointment['duration'] : 30;
            $end_time = $start_time ? date('H:i', strtotime($start_time) + 60 * $duration) : '';
            $time_slot = $end_time ? $start_time . '-' . $end_time : $start_time;
            
            // Calculează ziua săptămânii
            $day_names = array(
                'Monday' => 'Luni',
                'Tuesday' => 'Marți', 
                'Wednesday' => 'Miercuri',
                'Thursday' => 'Joi',
                'Friday' => 'Vineri',
                'Saturday' => 'Sâmbătă',
                'Sunday' => 'Duminică'
            );
            $day_name = isset($appointment['date']) ? $day_names[date('l', strtotime($appointment['date']))] : '';
            
            // Formatează data
            $formatted_date = isset($appointment['date']) ? date('d.m.Y', strtotime($appointment['date'])) : '';
            
            $html .= '<div class="clinica-assistant-appointment-item">';
            $html .= '<div class="appointment-date">' . esc_html($formatted_date) . '</div>';
            $html .= '<div class="appointment-day">' . esc_html($day_name) . '</div>';
            $html .= '<div class="appointment-time">' . esc_html($time_slot) . '</div>';
            $html .= '<div class="appointment-doctor">' . esc_html($appointment['doctor_name']) . '</div>';
            $html .= '<div class="appointment-patient">' . esc_html($appointment['patient_name']) . '</div>';
            $html .= '<div class="appointment-type">' . esc_html($appointment['type']) . '</div>';
            $html .= '<div class="appointment-status status-' . esc_attr($appointment['status']) . '">' . esc_html($appointment['status']) . '</div>';
            $html .= '<div class="appointment-actions">';
            
            // Afișează butoanele doar dacă statusul permite modificări
            if (!in_array($appointment['status'], array('completed', 'cancelled', 'no_show'))) {
                $html .= '<button class="clinica-assistant-btn clinica-assistant-btn-secondary" onclick="editAppointment(' . $appointment['id'] . ')">Editează</button>';
                $html .= '<button class="clinica-assistant-btn clinica-assistant-btn-primary" onclick="openTransferModalFrontend(' . $appointment['id'] . ', ' . $appointment['doctor_id'] . ', ' . $appointment['patient_id'] . ', ' . $appointment['service_id'] . ', \'' . $appointment['date'] . '\', \'' . $appointment['time'] . '\', ' . $appointment['duration'] . ', \'' . esc_js($appointment['patient_name']) . '\', \'' . esc_js($appointment['doctor_name']) . '\', \'' . esc_js($appointment['type']) . '\')">Mută</button>';
            } else {
                // Pentru programările completed/cancelled/no_show, nu afișa niciun buton
                $html .= '<span style="color: #999; font-size: 18px; font-weight: bold; display: flex; justify-content: center; align-items: center; width: 100%;">—</span>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Închide clinica-assistant-appointments-table
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler pentru pacienți
     */
    public function ajax_patients() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Obține parametrii de filtrare
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $cnp_filter = isset($_POST['cnp']) ? sanitize_text_field($_POST['cnp']) : '';
        $age_filter = isset($_POST['age']) ? sanitize_text_field($_POST['age']) : '';
        $family_filter = isset($_POST['family']) ? sanitize_text_field($_POST['family']) : '';
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $sort_by = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'last_visit';
        $sort_order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'desc';
        
        // Obține pacienții cu filtrare
        $result = $this->get_assistant_patients_filtered($search, $cnp_filter, $age_filter, $family_filter, $page, $per_page, $sort_by, $sort_order);
        
        wp_send_json_success($result);
    }

    /**
     * AJAX handler pentru calendar
     */
    public function ajax_calendar() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Obține datele reale pentru calendar
        $data = $this->get_assistant_calendar_data();
        
        wp_send_json_success($data);
    }

    /**
     * AJAX handler pentru rapoarte
     */
    public function ajax_reports() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        // Obține datele reale pentru rapoarte
        $data = $this->get_assistant_reports_data();
        
        wp_send_json_success($data);
    }
    
    /**
     * Obține programările pentru dashboard-ul Asistent
     */
    private function get_assistant_appointments() {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Obține programările din ultimele 30 de zile
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.patient_id,
                a.doctor_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.type,
                a.service_id,
                a.duration,
                a.notes,
                a.created_at,
                a.updated_at,
                p.cnp as patient_cnp,
                u.display_name as patient_name,
                d.display_name as doctor_name,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_patients p ON a.patient_id = p.user_id
            LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
            LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
            LEFT JOIN $table_services s ON a.service_id = s.id
            WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 50
        "));
        
        $formatted_appointments = array();
        foreach ($appointments as $appointment) {
            $formatted_appointments[] = array(
                'id' => intval($appointment->id),
                'patient_id' => intval($appointment->patient_id),
                'doctor_id' => intval($appointment->doctor_id),
                'service_id' => intval($appointment->service_id),
                'patient_name' => $appointment->patient_name ?: 'Pacient necunoscut',
                'doctor_name' => $appointment->doctor_name ?: 'Doctor necunoscut',
                'date' => $this->format_appointment_date($appointment->appointment_date),
                'time' => substr($appointment->appointment_time, 0, 5),
                'status' => $appointment->status,
                'type' => $appointment->service_name ?: $appointment->type ?: 'Consultatie',
                'notes' => $appointment->notes ?: '',
                'duration' => $appointment->duration ?: 30,
                'created_at' => $appointment->created_at,
                'updated_at' => $appointment->updated_at
            );
        }
        
        return $formatted_appointments;
    }
    
    /**
     * Formatează data programării pentru afișare
     */
    private function format_appointment_date($date) {
        if (empty($date)) {
            return 'N/A';
        }
        
        try {
            $date_obj = new DateTime($date);
            return $date_obj->format('d.m.Y');
        } catch (Exception $e) {
            return date('d.m.Y', strtotime($date));
        }
    }
    
    /**
     * Obține pacienții pentru dashboard-ul Asistent (versiune simplă pentru compatibilitate)
     */
    private function get_assistant_patients() {
        $result = $this->get_assistant_patients_filtered('', '', '', '', 1, 50, 'last_visit', 'desc');
        return $result['patients'];
    }
    
    /**
     * Obține pacienții cu filtrare avansată pentru dashboard-ul Asistent
     */
    public function get_assistant_patients_filtered($search = '', $cnp_filter = '', $age_filter = '', $family_filter = '', $page = 1, $per_page = 20, $sort_by = 'last_visit', $sort_order = 'desc') {
        global $wpdb;
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        
        $offset = ($page - 1) * $per_page;
        
        // Construiește condițiile WHERE
        $where_conditions = array();
        $where_values = array();
        
        // Căutare generală
        if (!empty($search)) {
            $where_conditions[] = "(p.cnp LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR p.phone_primary LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Filtru CNP
        if (!empty($cnp_filter)) {
            $where_conditions[] = "p.cnp = %s";
            $where_values[] = $cnp_filter;
        }
        
        // Filtru familie
        if (!empty($family_filter)) {
            $where_conditions[] = "p.family_id = %s";
            $where_values[] = $family_filter;
        }
        
        // Filtru vârstă
        if (!empty($age_filter)) {
            $current_year = date('Y');
            $min_year = 0;
            $max_year = 9999;
            
            switch ($age_filter) {
                case '0-18':
                    $min_year = $current_year - 18;
                    $max_year = $current_year;
                    break;
                case '19-30':
                    $min_year = $current_year - 30;
                    $max_year = $current_year - 19;
                    break;
                case '31-50':
                    $min_year = $current_year - 50;
                    $max_year = $current_year - 31;
                    break;
                case '51-65':
                    $min_year = $current_year - 65;
                    $max_year = $current_year - 51;
                    break;
                case '51+':
                    $min_year = 1900;
                    $max_year = $current_year - 51;
                    break;
                case '65+':
                    $min_year = 1900;
                    $max_year = $current_year - 66;
                    break;
            }
            
            $age_condition = "(
                CASE 
                    WHEN SUBSTRING(p.cnp, 1, 1) IN ('1', '2') THEN 1900 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
                    WHEN SUBSTRING(p.cnp, 1, 1) IN ('3', '4') THEN 1800 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
                    WHEN SUBSTRING(p.cnp, 1, 1) IN ('5', '6') THEN 2000 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
                    ELSE 1900 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
                END BETWEEN %d AND %d
            )";
            
            $where_conditions[] = $age_condition;
            $where_values[] = $min_year;
            $where_values[] = $max_year;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Construiește clauza ORDER BY
        $order_clause = '';
        switch ($sort_by) {
            case 'name':
                $order_clause = "ORDER BY u.display_name " . strtoupper($sort_order);
                break;
            case 'cnp':
                $order_clause = "ORDER BY p.cnp " . strtoupper($sort_order);
                break;
            case 'email':
                $order_clause = "ORDER BY u.user_email " . strtoupper($sort_order);
                break;
            case 'appointments':
                $order_clause = "ORDER BY appointments_count " . strtoupper($sort_order);
                break;
            case 'created_at':
                $order_clause = "ORDER BY p.created_at " . strtoupper($sort_order);
                break;
            default:
                $order_clause = "ORDER BY last_visit " . strtoupper($sort_order);
        }
        
        // Query pentru numărul total
        $total_query = "SELECT COUNT(*) FROM (
            SELECT p.user_id
            FROM $table_patients p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            $where_clause
            GROUP BY p.user_id
        ) as count_query";
        
        if (!empty($where_values)) {
            $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total = $wpdb->get_var($total_query);
        }
        
        // Query pentru pacienți
        $query = "SELECT 
            p.user_id,
            p.cnp,
            p.family_id,
            u.display_name as name,
            u.user_email as email,
            p.phone_primary as phone,
            p.created_at,
            MAX(a.appointment_date) as last_visit,
            COUNT(a.id) as appointments_count
        FROM $table_patients p
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        LEFT JOIN $table_appointments a ON p.user_id = a.patient_id
        $where_clause
        GROUP BY p.user_id, p.cnp, p.family_id, u.display_name, u.user_email, p.phone_primary, p.created_at
        $order_clause
        LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        $patients = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        $formatted_patients = array();
        foreach ($patients as $patient) {
            // Calculează vârsta din CNP
            $age = $this->calculate_age_from_cnp($patient->cnp);
            $gender = $this->get_gender_from_cnp($patient->cnp);
            $birth_date = $this->get_birth_date_from_cnp($patient->cnp);
            
            $formatted_patients[] = array(
                'id' => intval($patient->user_id),
                'name' => $patient->name ?: 'Pacient necunoscut',
                'cnp' => $patient->cnp ?: 'N/A',
                'email' => $patient->email ?: 'N/A',
                'phone' => $patient->phone ?: 'N/A',
                'last_visit' => $patient->last_visit ? $this->format_appointment_date($patient->last_visit) : 'N/A',
                'appointments_count' => intval($patient->appointments_count),
                'age' => $age,
                'gender' => $gender,
                'birth_date' => $birth_date,
                'family_id' => $patient->family_id
            );
        }
        
        $total_pages = ceil($total / $per_page);
        
        return array(
            'patients' => $formatted_patients,
            'total' => intval($total),
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page
        );
    }
    
    /**
     * Calculează vârsta din CNP
     */
    private function calculate_age_from_cnp($cnp) {
        if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return null;
        }
        
        $year = substr($cnp, 1, 2);
        $month = substr($cnp, 3, 2);
        $day = substr($cnp, 5, 2);
        $sex_digit = substr($cnp, 0, 1);
        
        // Validează luna și ziua
        if ($month < 1 || $month > 12) {
            return null;
        }
        if ($day < 1 || $day > 31) {
            return null;
        }
        
        if ($sex_digit == 1 || $sex_digit == 2) {
            $full_year = 1900 + $year;
        } elseif ($sex_digit == 3 || $sex_digit == 4) {
            $full_year = 1800 + $year;
        } elseif ($sex_digit == 5 || $sex_digit == 6) {
            $full_year = 2000 + $year;
        } else {
            $full_year = 1900 + $year;
        }
        
        // Validează anul
        if ($full_year < 1800 || $full_year > 2100) {
            return null;
        }
        
        $birth_date = $full_year . '-' . $month . '-' . $day;
        
        // Verifică dacă data este validă
        if (!checkdate($month, $day, $full_year)) {
            return null;
        }
        
        try {
            $today = new DateTime();
            $birth = new DateTime($birth_date);
            $age = $today->diff($birth)->y;
            return $age;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obține genul din CNP
     */
    private function get_gender_from_cnp($cnp) {
        if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return null;
        }
        
        $sex_digit = substr($cnp, 0, 1);
        if ($sex_digit == 1 || $sex_digit == 3 || $sex_digit == 5 || $sex_digit == 7) {
            return 'M';
        } elseif ($sex_digit == 2 || $sex_digit == 4 || $sex_digit == 6 || $sex_digit == 8) {
            return 'F';
        }
        
        return null;
    }
    
    /**
     * Obține data nașterii din CNP
     */
    private function get_birth_date_from_cnp($cnp) {
        if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return null;
        }
        
        $year = substr($cnp, 1, 2);
        $month = substr($cnp, 3, 2);
        $day = substr($cnp, 5, 2);
        $sex_digit = substr($cnp, 0, 1);
        
        // Validează luna și ziua
        if ($month < 1 || $month > 12) {
            return null;
        }
        if ($day < 1 || $day > 31) {
            return null;
        }
        
        if ($sex_digit == 1 || $sex_digit == 2) {
            $full_year = 1900 + $year;
        } elseif ($sex_digit == 3 || $sex_digit == 4) {
            $full_year = 1800 + $year;
        } elseif ($sex_digit == 5 || $sex_digit == 6) {
            $full_year = 2000 + $year;
        } else {
            $full_year = 1900 + $year;
        }
        
        // Validează anul
        if ($full_year < 1800 || $full_year > 2100) {
            return null;
        }
        
        // Verifică dacă data este validă
        if (!checkdate($month, $day, $full_year)) {
            return null;
        }
        
        return $full_year . '-' . $month . '-' . $day;
    }
    
    /**
     * Obține datele pentru calendar
     */
    private function get_assistant_calendar_data() {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        
        $current_month = date('F Y');
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');
        
        // Obține programările din luna curentă
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.appointment_date,
                a.appointment_time,
                u.display_name as patient_name,
                d.display_name as doctor_name,
                a.status,
                a.type
            FROM $table_appointments a
            LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
            LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
            WHERE a.appointment_date BETWEEN %s AND %s
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ", $month_start, $month_end));
        
        $formatted_appointments = array();
        foreach ($appointments as $appointment) {
            $formatted_appointments[] = array(
                'date' => $appointment->appointment_date,
                'time' => substr($appointment->appointment_time, 0, 5),
                'patient' => $appointment->patient_name ?: 'Pacient necunoscut',
                'doctor' => $appointment->doctor_name ?: 'Doctor necunoscut',
                'status' => $appointment->status,
                'type' => $appointment->type ?: 'Consultatie'
            );
        }
        
        return array(
            'current_month' => $current_month,
            'appointments' => $formatted_appointments
        );
    }
    
    /**
     * Obține datele pentru rapoarte
     */
    private function get_assistant_reports_data() {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');
        
        // Statistici pentru luna curentă
        $total_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date BETWEEN %s AND %s
        ", $month_start, $month_end));
        
        $confirmed_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date BETWEEN %s AND %s AND status = 'confirmed'
        ", $month_start, $month_end));
        
        $cancelled_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date BETWEEN %s AND %s AND status = 'cancelled'
        ", $month_start, $month_end));
        
        $new_patients = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_patients 
            WHERE created_at BETWEEN %s AND %s
        ", $month_start . ' 00:00:00', $month_end . ' 23:59:59'));
        
        return array(
            'total_appointments' => intval($total_appointments),
            'confirmed_appointments' => intval($confirmed_appointments),
            'cancelled_appointments' => intval($cancelled_appointments),
            'new_patients' => intval($new_patients),
            'total_revenue' => 'N/A' // Nu avem sistem de facturare implementat
        );
    }
    
    /**
     * AJAX handler pentru sugestii de căutare pacienți
     */
    public function ajax_search_patients_suggestions() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $search_type = isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : 'search-input';
        
        if (strlen($search_term) < 2) {
            wp_send_json_success(array('suggestions' => array(), 'searchTerm' => $search_term));
        }
        
        global $wpdb;
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $where_conditions = array();
        $where_values = array();
        
        if ($search_type === 'cnp-filter') {
            $where_conditions[] = "p.cnp LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($search_term) . '%';
        } else {
            $where_conditions[] = "(p.cnp LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR p.phone_primary LIKE %s)";
            $search_term_like = '%' . $wpdb->esc_like($search_term) . '%';
            $where_values[] = $search_term_like;
            $where_values[] = $search_term_like;
            $where_values[] = $search_term_like;
            $where_values[] = $search_term_like;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "SELECT DISTINCT
            p.user_id,
            p.cnp,
            u.display_name as name,
            u.user_email as email,
            p.phone_primary as phone
        FROM $table_patients p
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        $where_clause
        ORDER BY u.display_name ASC
        LIMIT 10";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        $suggestions = array();
        foreach ($results as $result) {
            $suggestions[] = array(
                'id' => intval($result->user_id),
                'cnp' => $result->cnp,
                'name' => $result->name ?: 'Pacient necunoscut',
                'email' => $result->email ?: 'N/A',
                'phone' => $result->phone ?: 'N/A'
            );
        }
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'searchTerm' => $search_term
        ));
    }
    
    /**
     * AJAX handler pentru sugestii de căutare familii
     */
    public function ajax_search_families_suggestions() {
        // Verifică nonce-ul
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        
        if (strlen($search_term) < 2) {
            wp_send_json_success(array('suggestions' => array(), 'searchTerm' => $search_term));
        }
        
        global $wpdb;
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        $query = "SELECT DISTINCT
            p.family_id,
            p.family_name,
            COUNT(p.user_id) as family_size
        FROM $table_patients p
        WHERE p.family_id IS NOT NULL 
        AND (p.family_id LIKE %s OR p.family_name LIKE %s)
        GROUP BY p.family_id, p.family_name
        ORDER BY p.family_name ASC, p.family_id ASC
        LIMIT 10";
        
        $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
        $results = $wpdb->get_results($wpdb->prepare($query, $search_pattern, $search_pattern));
        
        $suggestions = array();
        foreach ($results as $result) {
            $suggestions[] = array(
                'family_id' => $result->family_id,
                'family_name' => $result->family_name,
                'family_size' => intval($result->family_size)
            );
        }
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'searchTerm' => $search_term
        ));
    }
    
    /**
     * AJAX handler pentru obținerea datelor pacientului
     */
    public function ajax_get_patient_data() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        
        if ($patient_id <= 0) {
            wp_send_json_error('ID pacient invalid');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $patient_id
        ));
        
        if (!$patient) {
            wp_send_json_error('Pacientul nu a fost găsit');
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
            'password_method' => $patient->password_method,
            'address' => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'cnp' => $patient->cnp
        ));
    }
    
    /**
     * AJAX handler pentru actualizarea datelor pacientului
     */
    public function ajax_update_patient() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $cnp = isset($_POST['cnp']) ? sanitize_text_field($_POST['cnp']) : '';
        $phone_primary = isset($_POST['phone_primary']) ? sanitize_text_field($_POST['phone_primary']) : '';
        $phone_secondary = isset($_POST['phone_secondary']) ? sanitize_text_field($_POST['phone_secondary']) : '';
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';
        $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $password_method = isset($_POST['password_method']) ? sanitize_text_field($_POST['password_method']) : 'cnp';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $emergency_contact = isset($_POST['emergency_contact']) ? sanitize_text_field($_POST['emergency_contact']) : '';
        
        // Date familie
        $family_option = isset($_POST['family_option']) ? sanitize_text_field($_POST['family_option']) : 'none';
        $family_name = isset($_POST['family_name']) ? sanitize_text_field($_POST['family_name']) : '';
        $family_role = isset($_POST['family_role']) ? sanitize_text_field($_POST['family_role']) : '';
        $existing_family_role = isset($_POST['existing_family_role']) ? sanitize_text_field($_POST['existing_family_role']) : '';
        $selected_family_id = isset($_POST['selected_family_id']) ? intval($_POST['selected_family_id']) : 0;
        $selected_family_name = isset($_POST['selected_family_name']) ? sanitize_text_field($_POST['selected_family_name']) : '';
        
        if ($patient_id <= 0) {
            wp_send_json_error('ID pacient invalid');
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
            wp_send_json_error('Eroare la actualizarea datelor utilizator: ' . $user_result->get_error_message());
        }
        
        // Update patient data in clinica_patients table
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient_data = array(
            'cnp' => $cnp,
            'phone_primary' => $phone_primary,
            'phone_secondary' => $phone_secondary,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'password_method' => $password_method,
            'address' => $address,
            'emergency_contact' => $emergency_contact
        );
        
        // Procesează datele de familie
        $family_data = $this->process_family_update_data($_POST, $patient_id);
        $patient_data = array_merge($patient_data, $family_data);
        
        // Remove empty values to avoid overwriting with empty strings
        $patient_data = array_filter($patient_data, function($value) {
            return $value !== '';
        });
        
        if (!empty($patient_data)) {
            $result = $wpdb->update($table_name, $patient_data, array('user_id' => $patient_id));
            
            if ($result === false) {
                wp_send_json_error('Eroare la actualizarea datelor pacientului în baza de date: ' . $wpdb->last_error);
            }
        }
        
        wp_send_json_success(array('message' => 'Pacientul a fost actualizat cu succes'));
    }
    
    /**
     * AJAX handler pentru crearea programării
     */
    public function ajax_create_appointment() {
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_assistant', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
        $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if ($patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
            wp_send_json_error('Date incomplete');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        $appointment_data = array(
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'duration' => $duration,
            'type' => 'consultation',
            'status' => $status,
            'notes' => $notes,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        if ($service_id > 0) {
            $appointment_data['service_id'] = $service_id;
        }
        
        $result = $wpdb->insert($table_name, $appointment_data);
        
        if ($result === false) {
            wp_send_json_error('Eroare la crearea programării');
        }
        
        wp_send_json_success(array('message' => 'Programarea a fost creată cu succes'));
    }
    
    /**
     * Procesează datele de familie pentru actualizarea pacientului
     */
    private function process_family_update_data($post_data, $patient_id) {
        $family_option = isset($post_data['family_option']) ? sanitize_text_field($post_data['family_option']) : 'none';
        $family_data = array(
            'family_id' => null,
            'family_role' => null,
            'family_head_id' => null,
            'family_name' => null
        );
        
        switch ($family_option) {
            case 'none':
                // Elimină pacientul din orice familie
                $family_data['family_id'] = null;
                $family_data['family_role'] = null;
                $family_data['family_head_id'] = null;
                $family_data['family_name'] = null;
                break;
                
            case 'new':
                // Creează o familie nouă
                $family_name = isset($post_data['family_name']) ? sanitize_text_field($post_data['family_name']) : '';
                $family_role = isset($post_data['family_role']) ? sanitize_text_field($post_data['family_role']) : '';
                
                if (!empty($family_name) && !empty($family_role)) {
                    global $wpdb;
                    $family_table = $wpdb->prefix . 'clinica_families';
                    
                    // Creează familia nouă
                    $family_result = $wpdb->insert($family_table, array(
                        'family_name' => $family_name,
                        'head_id' => $patient_id,
                        'created_at' => current_time('mysql')
                    ));
                    
                    if ($family_result) {
                        $family_id = $wpdb->insert_id;
                        $family_data['family_id'] = $family_id;
                        $family_data['family_role'] = $family_role;
                        $family_data['family_head_id'] = $patient_id;
                        $family_data['family_name'] = $family_name;
                    }
                }
                break;
                
            case 'existing':
                // Adaugă la o familie existentă
                $selected_family_id = isset($post_data['selected_family_id']) ? intval($post_data['selected_family_id']) : 0;
                $existing_family_role = isset($post_data['existing_family_role']) ? sanitize_text_field($post_data['existing_family_role']) : '';
                
                if ($selected_family_id > 0 && !empty($existing_family_role)) {
                    global $wpdb;
                    $family_table = $wpdb->prefix . 'clinica_families';
                    
                    // Obține informațiile despre familia selectată
                    $family_info = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $family_table WHERE id = %d",
                        $selected_family_id
                    ));
                    
                    if ($family_info) {
                        $family_data['family_id'] = $selected_family_id;
                        $family_data['family_role'] = $existing_family_role;
                        $family_data['family_head_id'] = $family_info->head_id;
                        $family_data['family_name'] = $family_info->family_name;
                    }
                }
                break;
                
            case 'current':
                // Păstrează familia actuală (nu face nimic)
                global $wpdb;
                $patient_table = $wpdb->prefix . 'clinica_patients';
                $current_family = $wpdb->get_row($wpdb->prepare(
                    "SELECT family_id, family_role, family_head_id, family_name FROM $patient_table WHERE user_id = %d",
                    $patient_id
                ));
                
                if ($current_family) {
                    $family_data['family_id'] = $current_family->family_id;
                    $family_data['family_role'] = $current_family->family_role;
                    $family_data['family_head_id'] = $current_family->family_head_id;
                    $family_data['family_name'] = $current_family->family_name;
                }
                break;
        }
        
        return $family_data;
    }
    
    /**
     * AJAX handler pentru încărcarea datelor necesare modalului de programare
     */
    public function ajax_get_appointment_modal_data() {
        // Verifică nonce
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_die('Eroare de securitate');
        }
        
        // Verifică permisiuni
        $current_user = wp_get_current_user();
        if (!in_array('clinica_assistant', $current_user->roles)) {
            wp_die('Nu ai permisiuni pentru această acțiune');
        }
        
        global $wpdb;
        
        // Încarcă serviciile
        $services_table = $wpdb->prefix . 'clinica_services';
        $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_services ORDER BY name ASC");
        
        // Obține numele utilizatorului curent
        $display_name = trim((string)get_user_meta($current_user->ID, 'first_name', true) . ' ' . (string)get_user_meta($current_user->ID, 'last_name', true));
        if ($display_name === '') { 
            $display_name = $current_user->display_name; 
        }
        
        $data = array(
            'services' => $services,
            'current_user_name' => $display_name
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler pentru crearea programării avansate
     */
    public function ajax_create_appointment_advanced() {
        // Verifică nonce
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_die('Eroare de securitate');
        }
        
        // Verifică permisiuni
        $current_user = wp_get_current_user();
        if (!in_array('clinica_assistant', $current_user->roles)) {
            wp_die('Nu ai permisiuni pentru această acțiune');
        }
        
        // Validează datele
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
        $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed';
        $created_by_type = isset($_POST['created_by_type']) ? sanitize_text_field($_POST['created_by_type']) : 'assistant';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $send_email = isset($_POST['send_email']) ? (bool)$_POST['send_email'] : false;
        
        if (!$patient_id || !$service_id || !$doctor_id || !$appointment_date || !$appointment_time) {
            wp_send_json_error('Completează toate câmpurile obligatorii');
        }
        
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'clinica_appointments';
        
        // Creează programarea
        $result = $wpdb->insert($appointments_table, array(
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'service_id' => $service_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'status' => $status,
            'created_by_type' => $created_by_type,
            'created_by' => $current_user->ID,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        
        if ($result === false) {
            wp_send_json_error('Eroare la crearea programării');
        }
        
        $appointment_id = $wpdb->insert_id;
        
        // Trimite email de confirmare dacă este solicitat
        if ($send_email) {
            $this->send_appointment_confirmation_email($appointment_id);
        }
        
        wp_send_json_success('Programarea a fost creată cu succes');
    }
    
    /**
     * Trimite email de confirmare pentru programare
     */
    private function send_appointment_confirmation_email($appointment_id) {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'clinica_appointments';
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, u.user_email, u.display_name, d.display_name as doctor_name, s.name as service_name 
             FROM $appointments_table a
             LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
             LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
             LEFT JOIN {$wpdb->prefix}clinica_services s ON a.service_id = s.id
             WHERE a.id = %d",
            $appointment_id
        ));
        
        if (!$appointment) {
            return false;
        }
        
        $subject = 'Confirmare programare - ' . get_bloginfo('name');
        $message = "Bună ziua,\n\n";
        $message .= "Programarea dumneavoastră a fost confirmată:\n\n";
        $message .= "Data: " . date('d.m.Y', strtotime($appointment->appointment_date)) . "\n";
        $message .= "Ora: " . $appointment->appointment_time . "\n";
        $message .= "Doctor: " . $appointment->doctor_name . "\n";
        $message .= "Serviciu: " . $appointment->service_name . "\n\n";
        $message .= "Vă mulțumim!";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($appointment->user_email, $subject, $message, $headers);
    }
} 