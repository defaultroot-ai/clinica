<?php
/**
 * Dashboard Doctor - Clinica
 */
if (!defined('ABSPATH')) exit;

class Clinica_Doctor_Dashboard {
    
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
        add_shortcode('clinica_doctor_dashboard', array($this, 'render_dashboard_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_clinica_load_doctor_patient_form', array($this, 'ajax_load_patient_form'));
        add_action('wp_ajax_clinica_doctor_dashboard_overview', array($this, 'ajax_overview'));
        add_action('wp_ajax_clinica_doctor_dashboard_appointments', array($this, 'ajax_appointments'));
        add_action('wp_ajax_clinica_doctor_dashboard_patients', array($this, 'ajax_patients'));
        add_action('wp_ajax_clinica_doctor_dashboard_medical', array($this, 'ajax_medical'));
        add_action('wp_ajax_clinica_doctor_dashboard_reports', array($this, 'ajax_reports'));
        add_action('wp_ajax_clinica_get_appointment_details', array($this, 'ajax_get_appointment_details'));
        add_action('wp_ajax_clinica_get_patient_appointments', array($this, 'ajax_get_patient_appointments'));
    }
    
    /**
     * Formatează data programării pentru afișare consistentă
     */
    private function format_appointment_date($date) {
        if (empty($date)) {
            return 'N/A';
        }
        
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        if ($date_obj) {
            return $date_obj->format('d.m.Y');
        }
        
        // Fallback la strtotime dacă DateTime nu funcționează
        return date('d.m.Y', strtotime($date));
    }

    public function enqueue_assets() {
        // Verifică dacă suntem pe o pagină cu shortcode-ul dashboard-ului de medic
        if (is_page() && has_shortcode(get_post()->post_content, 'clinica_doctor_dashboard')) {
            // CSS pentru dashboard-ul de medic
            wp_enqueue_style(
                'clinica-doctor-dashboard', 
                plugin_dir_url(__FILE__) . '../assets/css/doctor-dashboard.css', 
                array(), 
                time() // Forțează reîncărcarea cache-ului
            );
            
            // CSS pentru butonul Dashboard Pacient
            wp_enqueue_style(
                'clinica-patient-dashboard-button', 
                plugin_dir_url(__FILE__) . '../assets/css/patient-dashboard-button.css', 
                array(), 
                '1.0.0'
            );
            
            // JavaScript pentru dashboard-ul de medic
            wp_enqueue_script(
                'clinica-doctor-dashboard', 
                plugin_dir_url(__FILE__) . '../assets/js/doctor-dashboard.js', 
                array('jquery'), 
                time(), // Forțează reîncărcarea cache-ului
                true
            );
            
            // Include și CSS-ul pentru formularul de creare pacienți
            wp_enqueue_style(
                'clinica-frontend', 
                plugin_dir_url(__FILE__) . '../assets/css/frontend.css', 
                array(), 
                '1.0.0'
            );
            
            // Include live updates script
            wp_enqueue_script(
                'clinica-live-updates', 
                plugin_dir_url(__FILE__) . '../assets/js/live-updates.js', 
                array('jquery'), 
                '1.0.0', 
                true
            );
            
            // Localize script pentru AJAX
            wp_localize_script('clinica-doctor-dashboard', 'clinicaDoctorAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clinica_doctor_nonce'),
                'version' => time() // Forțează reîncărcarea cache-ului
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
        if (!in_array('clinica_doctor', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru doctori și administratori.</div>';
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
        if (!in_array('clinica_doctor', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru doctori și administratori.</div>';
        }

        ob_start();
        ?>
        <div class="clinica-doctor-dashboard">
            <div class="clinica-doctor-header">
                <div class="header-left">
                    <h1>Portal Doctor</h1>
                    <p>Gestionare pacienți și dosare medicale</p>
                </div>
                <div class="header-right">
                    <?php if (in_array('clinica_patient', $user->roles)): ?>
                    <a href="<?php echo esc_url(home_url('/clinica-patient-dashboard/')); ?>" class="patient-dashboard-btn">
                        <i class="fa fa-user"></i> Cont pacient
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="clinica-doctor-stats">
                <div class="clinica-doctor-stat-card doctor-info-card">
                    <h3>Informații Doctor</h3>
                    <div class="doctor-details">
                        <div class="doctor-detail-item">
                            <strong>Nume:</strong> <?php echo esc_html($user->display_name); ?>
                        </div>
                        <div class="doctor-detail-item">
                            <strong>Email:</strong> <?php echo esc_html($user->user_email); ?>
                        </div>
                        <div class="doctor-detail-item">
                            <strong>Telefon:</strong> <?php echo esc_html(get_user_meta($user->ID, 'phone', true) ?: 'Nu este setat'); ?>
                        </div>
                    </div>
                </div>
                <div class="clinica-doctor-stat-card work-schedule-card">
                    <h3>Program de Lucru</h3>
                    <div class="schedule-details">
                        <?php
                        // Obține programul real din baza de date
                        $schedule = get_user_meta($user->ID, 'clinica_working_hours', true);
                        if (is_string($schedule)) {
                            $schedule = json_decode($schedule, true);
                        }
                        if (!is_array($schedule)) {
                            $schedule = array();
                        }
                        
                        $days = array(
                            'monday' => 'Luni',
                            'tuesday' => 'Marți', 
                            'wednesday' => 'Miercuri',
                            'thursday' => 'Joi',
                            'friday' => 'Vineri',
                            'saturday' => 'Sâmbătă',
                            'sunday' => 'Duminică'
                        );
                        
                        $has_schedule = false;
                        foreach ($days as $key => $label) {
                            $day_data = isset($schedule[$key]) ? $schedule[$key] : array();
                            $is_active = isset($day_data['active']) ? $day_data['active'] : false;
                            $start_time = isset($day_data['start']) ? $day_data['start'] : '';
                            $end_time = isset($day_data['end']) ? $day_data['end'] : '';
                            $break_start = isset($day_data['break_start']) ? $day_data['break_start'] : '';
                            $break_end = isset($day_data['break_end']) ? $day_data['break_end'] : '';
                            
                            if ($is_active && $start_time && $end_time) {
                                $has_schedule = true;
                                echo '<div class="schedule-item">';
                                echo '<strong>' . esc_html($label) . ':</strong> ' . esc_html($start_time) . ' - ' . esc_html($end_time);
                                if ($break_start && $break_end) {
                                    echo ' (Pauză: ' . esc_html($break_start) . ' - ' . esc_html($break_end) . ')';
                                }
                                echo '</div>';
                            }
                        }
                        
                        if (!$has_schedule) {
                            echo '<div class="schedule-item">';
                            echo '<em>Programul nu este configurat</em>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <div class="clinica-doctor-stat-card today-services-card">
                    <h3>Servicii Astăzi</h3>
                    <div class="services-details">
                        <?php
                        // Obține serviciile din ziua actuală
                        global $wpdb;
                        $table_appointments = $wpdb->prefix . 'clinica_appointments';
                        $table_services = $wpdb->prefix . 'clinica_services';
                        
                        $today_appointments = $wpdb->get_results($wpdb->prepare("
                            SELECT a.*, s.name as service_name, s.duration
                            FROM $table_appointments a
                            LEFT JOIN $table_services s ON a.service_id = s.id
                            WHERE a.doctor_id = %d
                            AND DATE(a.appointment_date) = CURDATE()
                            AND a.status = 'confirmed'
                            ORDER BY a.appointment_time ASC
                        ", $user->ID));
                        
                        if (empty($today_appointments)) {
                            echo '<div class="service-item">';
                            echo '<em>Nu există servicii programate pentru astăzi</em>';
                            echo '</div>';
                        } else {
                            foreach ($today_appointments as $appointment) {
                                $start_time = $appointment->appointment_time;
                                $duration = $appointment->duration ?: 30; // Durata implicită 30 min
                                
                                // Calculează ora de sfârșit
                                $end_time = '';
                                if ($start_time) {
                                    $start_datetime = DateTime::createFromFormat('H:i:s', $start_time);
                                    if (!$start_datetime) {
                                        $start_datetime = DateTime::createFromFormat('H:i', $start_time);
                                    }
                                    if ($start_datetime) {
                                        $end_datetime = clone $start_datetime;
                                        $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
                                        $end_time = $end_datetime->format('H:i');
                                    }
                                }
                                
                                echo '<div class="service-item">';
                                echo '<strong>' . esc_html($appointment->service_name ?: 'Serviciu nedefinit') . '</strong>';
                                if ($start_time && $end_time) {
                                    // Elimină secundele din timpul de început
                                    $clean_start_time = substr($start_time, 0, 5);
                                    echo '<br><span class="service-time">' . esc_html($clean_start_time) . ' - ' . esc_html($end_time) . '</span>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="clinica-doctor-tabs">
                <div class="clinica-doctor-tab-nav">
                    <button class="clinica-doctor-tab-button active" data-tab="overview">
                        <span class="tab-icon dashicons dashicons-dashboard"></span>
                        Prezentare Generală
                    </button>
                    <button class="clinica-doctor-tab-button" data-tab="appointments">
                        <span class="tab-icon dashicons dashicons-calendar-alt"></span>
                        Programări
                    </button>
                    <button class="clinica-doctor-tab-button" data-tab="patients">
                        <span class="tab-icon dashicons dashicons-admin-users"></span>
                        Pacienți
                    </button>
                    <!-- TEMPORAR ASCUNS - Dosare Medicale
                    <button class="clinica-doctor-tab-button" data-tab="medical">
                        <span class="tab-icon dashicons dashicons-heart"></span>
                        Dosare Medicale
                    </button>
                    -->
                    <button class="clinica-doctor-tab-button" data-tab="reports">
                        <span class="tab-icon dashicons dashicons-chart-bar"></span>
                        Rapoarte
                    </button>
                </div>

                <div class="clinica-doctor-tab-content active" data-tab="overview">
                    <div class="clinica-doctor-actions">
                        <button class="clinica-doctor-btn clinica-doctor-btn-primary" data-action="add-appointment">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Programare Nouă
                        </button>
                        <button class="clinica-doctor-btn clinica-doctor-btn-success" data-action="add-patient">
                            <span class="dashicons dashicons-admin-users"></span>
                            Pacient Nou
                        </button>
                        <button class="clinica-doctor-btn clinica-doctor-btn-secondary" data-action="view-patients">
                            <span class="dashicons dashicons-admin-users"></span>
                            Vezi Pacienții
                        </button>
                    </div>

                    <div class="clinica-doctor-form">
                        <h3>Programări Următoare</h3>
                        <div class="clinica-doctor-loading">Se încarcă programările...</div>
                    </div>
                </div>

                <div class="clinica-doctor-tab-content" data-tab="appointments">
                    <div class="clinica-doctor-loading">Se încarcă programările...</div>
                </div>

                <div class="clinica-doctor-tab-content" data-tab="patients">
                    <div class="clinica-doctor-loading">Se încarcă pacienții...</div>
                </div>

                <!-- TEMPORAR ASCUNS - Conținut Dosare Medicale
                <div class="clinica-doctor-tab-content" data-tab="medical">
                    <div class="clinica-doctor-loading">Se încarcă dosarele medicale...</div>
                </div>
                -->

                <div class="clinica-doctor-tab-content" data-tab="reports">
                    <div class="clinica-doctor-loading">Se încarcă rapoartele...</div>
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
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
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
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Statistici reale
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        
        // Programări astăzi
        $today_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d AND appointment_date = %s
        ", $user->ID, $today));
        
        // Programări săptămâna aceasta
        $week_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d AND appointment_date BETWEEN %s AND %s
        ", $user->ID, $week_start, $week_end));
        
        // Pacienți activi (cu programări în ultimele 30 de zile)
        $active_patients = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT patient_id) FROM $table_appointments 
            WHERE doctor_id = %d AND appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $user->ID));
        
        // Dosare medicale
        $medical_records = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_medical_records 
            WHERE doctor_id = %d
        ", $user->ID));
        
        // Programări următoare (următoarele 7 zile)
        $upcoming_appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.type,
                a.service_id,
                p.cnp,
                u.display_name as patient_name,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_patients p ON a.patient_id = p.user_id
            LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
            LEFT JOIN $table_services s ON a.service_id = s.id
            WHERE a.doctor_id = %d 
            AND a.appointment_date BETWEEN %s AND DATE_ADD(%s, INTERVAL 7 DAY)
            AND a.status IN ('scheduled', 'confirmed')
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 10
        ", $user->ID, $today, $today));
        
        $data = array(
            'stats' => array(
                'today_appointments' => (int)$today_appointments,
                'week_appointments' => (int)$week_appointments,
                'active_patients' => (int)$active_patients,
                'medical_records' => (int)$medical_records
            ),
            'upcoming_appointments' => array()
        );
        
        // Procesează programările următoare
        foreach ($upcoming_appointments as $appointment) {
            $status_text = $this->get_status_text($appointment->status);
            $type_text = $this->get_type_text($appointment->type);
            $service_name = $appointment->service_name ?: $type_text;
            
            $data['upcoming_appointments'][] = array(
                'id' => $appointment->id,
                'appointment_date' => $this->format_appointment_date($appointment->appointment_date),
                'time' => $appointment->appointment_time,
                'patient' => $appointment->patient_name,
                'cnp' => $appointment->cnp,
                'type' => $service_name,
                'status' => $appointment->status,
                'status_text' => $status_text
            );
        }
        
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
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Obține doar programările viitoare cu status acceptat pentru medicul curent
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.duration,
                a.status,
                a.type,
                a.notes,
                a.service_id,
                p.cnp,
                p.phone_primary,
                u.display_name as patient_name,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_patients p ON a.patient_id = p.user_id
            LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
            LEFT JOIN $table_services s ON a.service_id = s.id
            WHERE a.doctor_id = %d
            AND a.status = 'confirmed'
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 50
        ", $user->ID));
        
        if (empty($appointments)) {
            $html = '<div class="clinica-doctor-no-data">
                        <div class="no-data-icon">📅</div>
                        <h3>Nu există programări viitoare</h3>
                        <p>Nu aveți programări viitoare cu status acceptat. Creați o programare nouă pentru a începe.</p>
                    </div>';
        } else {
            $html = '<div class="clinica-doctor-appointments">';
            $html .= '<h3>Programări Viitoare (' . count($appointments) . ')</h3>';
            
            foreach ($appointments as $appointment) {
                // Calculează clasa CSS pentru status
                $status_class = 'status-' . $appointment->status;
                if ($appointment->status === 'confirmed') {
                    $status_class = 'status-accepted';
                }
                $status_text = $this->get_status_text($appointment->status);
                $type_text = $this->get_type_text($appointment->type);
                // Folosește serviciul ca tip dacă tipul este gol
                $display_type = $appointment->type ? $type_text : ($appointment->service_name ?: 'N/A');
                $service_name = $appointment->service_name ?: $display_type;
                
                $html .= '<div class="clinica-doctor-appointment-item">';
                // Formatează data și ora
            $appointment_date = $this->format_appointment_date($appointment->appointment_date);
            $appointment_time = $appointment->appointment_time ?: 'N/A';
            $duration = $appointment->duration ?: 30; // Durata implicită 30 min
            
            // Elimină secundele din timp (dacă există)
            if ($appointment_time !== 'N/A' && strpos($appointment_time, ':') !== false) {
                $time_parts = explode(':', $appointment_time);
                if (count($time_parts) >= 2) {
                    $appointment_time = $time_parts[0] . ':' . $time_parts[1];
                }
            }
            
            // Calculează ora de sfârșit
            $time_slot = $appointment_time;
            if ($appointment_time !== 'N/A') {
                $start_time = DateTime::createFromFormat('H:i', $appointment_time);
                if ($start_time) {
                    $end_time = clone $start_time;
                    $end_time->add(new DateInterval('PT' . $duration . 'M'));
                    $time_slot = $appointment_time . ' - ' . $end_time->format('H:i');
                }
            }
            
            $day_name = $appointment->appointment_date ? date('l', strtotime($appointment->appointment_date)) : '';
            
            // Traduce numele zilei
            $day_names = array(
                'Monday' => 'Luni',
                'Tuesday' => 'Marți',
                'Wednesday' => 'Miercuri',
                'Thursday' => 'Joi',
                'Friday' => 'Vineri',
                'Saturday' => 'Sâmbătă',
                'Sunday' => 'Duminică'
            );
            $day_name_ro = isset($day_names[$day_name]) ? $day_names[$day_name] : $day_name;
            
            $html .= '<div class="appointment-time">' . esc_html($appointment_date) . ' - ' . esc_html($time_slot) . ' ' . esc_html($day_name_ro) . '</div>';
                $html .= '<div class="appointment-patient">' . esc_html($appointment->patient_name) . '</div>';
                $html .= '<div class="appointment-cnp">' . esc_html($appointment->cnp) . '</div>';
                $html .= '<div class="appointment-type">' . esc_html($service_name) . '</div>';
                $html .= '<div class="appointment-status ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</div>';
                $html .= '<div class="appointment-actions">';
                $html .= '<button class="clinica-doctor-btn clinica-doctor-btn-primary" onclick="viewAppointmentDetails(' . $appointment->id . ')">Vezi</button>';
                
                // Afișează butonul "Mută" doar dacă statusul permite transferul
                if (!in_array($appointment->status, array('completed', 'cancelled', 'no_show'))) {
                    $html .= '<button class="clinica-doctor-btn clinica-doctor-btn-secondary" onclick="openTransferModalFrontend(' . $appointment->id . ', ' . $appointment->doctor_id . ', ' . $appointment->patient_id . ', ' . ($appointment->service_id ?: 0) . ', \'' . $appointment->appointment_date . '\', \'' . $appointment_time . '\', ' . $duration . ', \'' . esc_js($appointment->patient_name ?: 'Pacient necunoscut') . '\', \'' . esc_js($appointment->doctor_name ?: 'Doctor necunoscut') . '\', \'' . esc_js($service_name) . '\')">Mută</button>';
                } else {
                    $html .= '<button class="clinica-doctor-btn clinica-doctor-btn-disabled" disabled title="Programarea nu poate fi mutată (status: ' . esc_attr($status_text) . ')">Mută</button>';
                }
                
                $html .= '</div>';
                $html .= '</div>';
            }
            
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
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Obține pacienții care au programări cu medicul curent
        $patients = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT
                p.user_id as id,
                u.display_name as name,
                p.cnp,
                p.email,
                p.phone_primary as phone,
                p.birth_date,
                p.gender,
                MAX(a.appointment_date) as last_visit,
                COUNT(CASE WHEN a.status = 'confirmed' AND a.appointment_date >= CURDATE() THEN a.id END) as appointments_count
            FROM $table_patients p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            LEFT JOIN $table_appointments a ON p.user_id = a.patient_id AND a.doctor_id = %d
            WHERE a.doctor_id = %d
            GROUP BY p.user_id, u.display_name, p.cnp, p.email, p.phone_primary, p.birth_date, p.gender
            ORDER BY last_visit DESC
            LIMIT 50
        ", $user->ID, $user->ID));
        
        if (empty($patients)) {
            $html = '<div class="clinica-doctor-no-data">
                        <div class="no-data-icon">👥</div>
                        <h3>Nu există pacienți</h3>
                        <p>Nu aveți pacienți în sistem. Creați o programare pentru un pacient nou.</p>
                    </div>';
        } else {
            $html = '<div class="clinica-doctor-patients">';
            $html .= '<h3>Pacienți (' . count($patients) . ')</h3>';
            
            foreach ($patients as $patient) {
                $last_visit = $this->format_appointment_date($patient->last_visit) !== 'N/A' ? $this->format_appointment_date($patient->last_visit) : 'Niciodată';
                $age = $patient->birth_date ? $this->calculate_age($patient->birth_date) : 'N/A';
                
                $html .= '<div class="clinica-doctor-patient-item">';
                $html .= '<div class="patient-name">' . esc_html($patient->name) . '</div>';
                $html .= '<div class="patient-cnp">' . esc_html($patient->cnp) . '</div>';
                $html .= '<div class="patient-email">' . esc_html($patient->email) . '</div>';
                $html .= '<div class="patient-phone">' . esc_html($patient->phone) . '</div>';
                $html .= '<div class="patient-birth-date">' . esc_html($this->format_appointment_date($patient->birth_date)) . '</div>';
                $html .= '<div class="patient-age">' . esc_html($age) . ' ani</div>';
                $html .= '<div class="patient-appointments">' . esc_html($patient->appointments_count) . ' viit.</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler pentru dosare medicale
     */
    public function ajax_medical() {
        // Error logging pentru debugging
        
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        
        global $wpdb;
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Verifică dacă tabelul există
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_medical_records'");
        if (!$table_exists) {
            $html = '<div class="clinica-doctor-medical-records">';
            $html .= '<h3>Dosare Medicale</h3>';
            $html .= '<div class="clinica-error">Tabelul de dosare medicale nu există încă. Contactați administratorul.</div>';
            $html .= '</div>';
            wp_send_json_success(array('html' => $html));
            return;
        }
        
        // Încarcă dosarele medicale reale (versiune simplă cu PDF-uri)
        $medical_records = $wpdb->get_results($wpdb->prepare("
            SELECT 
                mr.id,
                mr.patient_id,
                mr.patient_name,
                mr.patient_cnp,
                mr.record_title,
                mr.record_date,
                mr.pdf_url,
                mr.file_name,
                mr.file_size,
                mr.created_at
            FROM $table_medical_records mr
            WHERE mr.doctor_id = %d
            ORDER BY mr.record_date DESC
            LIMIT 50
        ", $user->ID));
        
        
        $html = '<div class="clinica-doctor-medical-records">';
        $html .= '<div class="records-header">';
        $html .= '<h3>Dosare Medicale</h3>';
        $html .= '<button class="clinica-doctor-btn clinica-doctor-btn-success" onclick="addMedicalRecord()">';
        $html .= '<span class="dashicons dashicons-plus-alt"></span> Adaugă Dosar';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div class="medical-records-list">';
        
        if (empty($medical_records)) {
            $html .= '<div class="clinica-info">Nu există dosare medicale încă.</div>';
        } else {
            foreach ($medical_records as $record) {
                $patient_name = $record->patient_name ?: 'Pacient necunoscut';
                $cnp = $record->patient_cnp ?: 'CNP necunoscut';
                $record_title = $record->record_title ?: 'Dosar fără titlu';
                $record_date = $this->format_appointment_date($record->record_date) !== 'N/A' ? $this->format_appointment_date($record->record_date) : 'Data necunoscută';
                $file_name = $record->file_name ?: 'document.pdf';
                $file_size = $record->file_size ? $this->format_file_size($record->file_size) : '';
                $pdf_url = $record->pdf_url ?: '#';
                
                $html .= '<div class="medical-record-item">';
                $html .= '<div class="record-header">';
                $html .= '<div class="record-patient">' . esc_html($patient_name) . ' (' . esc_html($cnp) . ')</div>';
                $html .= '<div class="record-date">' . esc_html($record_date) . '</div>';
                $html .= '</div>';
                $html .= '<div class="record-title">' . esc_html($record_title) . '</div>';
                $html .= '<div class="record-file-info">';
                $html .= '<span class="file-name">' . esc_html($file_name) . '</span>';
                if ($file_size) {
                    $html .= '<span class="file-size">(' . esc_html($file_size) . ')</span>';
                }
                $html .= '</div>';
                $html .= '<div class="record-actions">';
                $html .= '<a href="' . esc_url($pdf_url) . '" target="_blank" class="clinica-doctor-btn clinica-doctor-btn-primary">';
                $html .= '<span class="dashicons dashicons-media-document"></span> Vezi PDF';
                $html .= '</a>';
                $html .= '<a href="' . esc_url($pdf_url) . '" download class="clinica-doctor-btn clinica-doctor-btn-secondary">';
                $html .= '<span class="dashicons dashicons-download"></span> Download';
                $html .= '</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler pentru rapoarte
     */
    public function ajax_reports() {
        // Error logging pentru debugging
        
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        
        // Verifică dacă tabelele există
        $appointments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_appointments'");
        $patients_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_patients'");
        
        if (!$appointments_table_exists || !$patients_table_exists) {
            $data = array(
                'total_appointments' => 0,
                'today_appointments' => 0,
                'cancelled_appointments' => 0,
                'active_patients' => 0,
                'new_patients' => 0,
                'average_rating' => 0,
                'error' => 'Tabelele necesare nu există încă. Contactați administratorul.'
            );
            wp_send_json_success($data);
            return;
        }
        
        // Calculează rapoartele reale
        $total_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d
        ", $user->ID));
        
        $today_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d 
            AND status = 'confirmed'
            AND DATE(appointment_date) = CURDATE()
        ", $user->ID));
        
        $cancelled_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d AND status = 'cancelled'
        ", $user->ID));
        
        $upcoming_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE doctor_id = %d 
            AND status = 'confirmed' 
            AND appointment_date > CURDATE()
            AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND WEEKDAY(appointment_date) BETWEEN 0 AND 4
        ", $user->ID));
        
        
        $data = array(
            'total_appointments' => (int)$total_appointments,
            'today_appointments' => (int)$today_appointments,
            'cancelled_appointments' => (int)$cancelled_appointments,
            'upcoming_appointments' => (int)$upcoming_appointments
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Helper method pentru formatarea mărimii fișierului
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Helper method pentru traducerea statusurilor
     */
    private function get_status_text($status) {
        $statuses = array(
            'scheduled' => 'Programată',
            'confirmed' => 'Acceptat',
            'completed' => 'Finalizată',
            'cancelled' => 'Anulată',
            'no_show' => 'Ne-prezentat'
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
    }
    
    /**
     * Helper method pentru traducerea tipurilor
     */
    private function get_type_text($type) {
        $types = array(
            'consultation' => 'Consultație',
            'examination' => 'Examinare',
            'procedure' => 'Procedură',
            'follow_up' => 'Control'
        );
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }
    
    /**
     * Helper method pentru calcularea vârstei
     */
    private function calculate_age($birth_date) {
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);
        return $age->y;
    }
    
    /**
     * AJAX handler pentru detalii programare
     */
    public function ajax_get_appointment_details() {
        // Error logging pentru debugging
        
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        if (!$appointment_id) {
            wp_send_json_error('ID programare invalid');
        }
        
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Verifică dacă tabelele există
        $appointments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_appointments'");
        if (!$appointments_table_exists) {
            wp_send_json_error('Tabelul de programări nu există încă');
        }
        
        // Încarcă detaliile programării
        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT 
                a.id,
                a.patient_id,
                a.doctor_id,
                a.appointment_date,
                a.appointment_time,
                a.duration,
                a.status,
                a.type,
                a.notes,
                a.created_at,
                a.updated_at,
                p.cnp as patient_cnp,
                u.display_name as patient_name,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_patients p ON a.patient_id = p.user_id
            LEFT JOIN {$wpdb->users} u ON a.patient_id = u.ID
            LEFT JOIN $table_services s ON a.service_id = s.id
            WHERE a.id = %d AND a.doctor_id = %d
        ", $appointment_id, $user->ID));
        
        if (!$appointment) {
            wp_send_json_error('Programarea nu a fost găsită sau nu aveți permisiunea de a o vizualiza');
        }
        
        
        // Formatează datele pentru afișare
        $appointment_time = $appointment->appointment_time ?: 'N/A';
        $duration = $appointment->duration ?: 30;
        
        // Calculează intervalul de timp
        $time_slot = $appointment_time;
        if ($appointment_time !== 'N/A') {
            // Elimină secundele din timp (dacă există)
            $clean_time = $appointment_time;
            if (strpos($appointment_time, ':') !== false) {
                $time_parts = explode(':', $appointment_time);
                if (count($time_parts) >= 2) {
                    $clean_time = $time_parts[0] . ':' . $time_parts[1];
                }
            }
            
            $start_time = DateTime::createFromFormat('H:i', $clean_time);
            if ($start_time) {
                $end_time = clone $start_time;
                $end_time->add(new DateInterval('PT' . $duration . 'M'));
                $time_slot = $clean_time . ' - ' . $end_time->format('H:i');
            }
        }
        
        // Folosește serviciul ca tip dacă tipul este gol
        $display_type = $appointment->type ? $this->get_type_text($appointment->type) : ($appointment->service_name ?: 'N/A');
        
        $data = array(
            'id' => $appointment->id,
            'appointment_date' => $this->format_appointment_date($appointment->appointment_date),
            'appointment_time' => $time_slot,
            'duration' => $appointment->duration ? $appointment->duration . ' min' : 'N/A',
            'status' => $this->get_status_text($appointment->status),
            'type' => $display_type,
            'patient_name' => $appointment->patient_name ?: 'N/A',
            'patient_cnp' => $appointment->patient_cnp ?: 'N/A',
            'service_name' => $appointment->service_name ?: 'N/A',
            'notes' => $appointment->notes ?: 'Fără note',
            'created_at' => $appointment->created_at ? date('d.m.Y H:i', strtotime($appointment->created_at)) : 'N/A',
            'updated_at' => $appointment->updated_at ? date('d.m.Y H:i', strtotime($appointment->updated_at)) : 'N/A'
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler pentru programările unui pacient
     */
    public function ajax_get_patient_appointments() {
        // Error logging pentru debugging
        
        if (!$this->verify_ajax_nonce($_POST['nonce'])) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        $user = wp_get_current_user();
        if (!in_array('clinica_doctor', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_send_json_error('Nu aveți permisiunea de a accesa această funcționalitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        if (!$patient_id) {
            wp_send_json_error('ID pacient invalid');
        }
        
        
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Verifică dacă tabelele există
        $appointments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_appointments'");
        if (!$appointments_table_exists) {
            wp_send_json_error('Tabelul de programări nu există încă');
        }
        
        // Încarcă doar programările viitoare cu status acceptat pentru doctorul curent
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                a.type,
                a.notes,
                s.name as service_name
            FROM $table_appointments a
            LEFT JOIN $table_services s ON a.service_id = s.id
            WHERE a.patient_id = %d 
            AND a.doctor_id = %d 
            AND a.status = 'confirmed'
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 20
        ", $patient_id, $user->ID));
        
        
        // Formatează datele pentru afișare
        $formatted_appointments = array();
        foreach ($appointments as $appointment) {
            $appointment_time = $appointment->appointment_time ?: 'N/A';
            $duration = 30; // Durata implicită 30 min
            
            // Calculează intervalul de timp
            $time_slot = $appointment_time;
            if ($appointment_time !== 'N/A') {
                // Elimină secundele din timp (dacă există)
                $clean_time = $appointment_time;
                if (strpos($appointment_time, ':') !== false) {
                    $time_parts = explode(':', $appointment_time);
                    if (count($time_parts) >= 2) {
                        $clean_time = $time_parts[0] . ':' . $time_parts[1];
                    }
                }
                
                $start_time = DateTime::createFromFormat('H:i', $clean_time);
                if ($start_time) {
                    $end_time = clone $start_time;
                    $end_time->add(new DateInterval('PT' . $duration . 'M'));
                    $time_slot = $clean_time . ' - ' . $end_time->format('H:i');
                }
            }
            
            
            // Folosește serviciul ca tip dacă tipul este gol
            $display_type = $appointment->type ? $this->get_type_text($appointment->type) : ($appointment->service_name ?: 'N/A');
            
            $formatted_appointments[] = array(
                'id' => $appointment->id,
                'appointment_date' => $this->format_appointment_date($appointment->appointment_date),
                'appointment_time' => $time_slot,
                'status' => $this->get_status_text($appointment->status),
                'type' => $display_type,
                'service_name' => $appointment->service_name ?: 'N/A',
                'notes' => $appointment->notes ?: 'Fără note'
            );
        }
        
        wp_send_json_success($formatted_appointments);
    }
} 