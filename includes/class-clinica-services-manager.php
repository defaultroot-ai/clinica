<?php
/**
 * Gestionare servicii și alocare doctori
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Services_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_clinica_get_doctors_for_service', array($this, 'ajax_get_doctors_for_service'));
        add_action('wp_ajax_clinica_save_doctor_service_allocation', array($this, 'ajax_save_doctor_service_allocation'));
        add_action('wp_ajax_clinica_get_service_doctors', array($this, 'ajax_get_service_doctors'));
        add_action('wp_ajax_clinica_get_services_for_doctor', array($this, 'ajax_get_services_for_doctor'));
        add_action('wp_ajax_clinica_normalize_name', array($this, 'ajax_normalize_name'));
        add_action('wp_ajax_nopriv_clinica_normalize_name', array($this, 'ajax_normalize_name'));
        
        // Hook-uri noi pentru timeslots
        add_action('wp_ajax_clinica_save_timeslot', array($this, 'ajax_save_timeslot'));
        add_action('wp_ajax_clinica_delete_timeslot', array($this, 'ajax_delete_timeslot'));
        add_action('wp_ajax_clinica_get_doctor_timeslots', array($this, 'ajax_get_doctor_timeslots'));
        add_action('wp_ajax_clinica_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_clinica_delete_all_doctor_service_timeslots', array($this, 'ajax_delete_all_doctor_service_timeslots'));
        add_action('wp_ajax_clinica_get_doctor_timeslots_count', array($this, 'ajax_get_doctor_timeslots_count'));
        add_action('wp_ajax_clinica_get_today_schedule', array($this, 'ajax_get_today_schedule'));
        add_action('wp_ajax_clinica_delete_day_timeslots', array($this, 'ajax_delete_day_timeslots'));
    }
    
    /**
     * Obține doctorii care pot oferi un anumit serviciu
     */
    public function ajax_get_doctors_for_service() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        // Dacă service_id = 0, returnează toți doctorii (pentru compatibilitate)
        if ($service_id === 0) {
            $users = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));
            $doctors = array();
            foreach ($users as $u) {
                $doctors[] = array('id' => $u->ID, 'name' => $u->display_name);
            }
            wp_send_json_success($doctors);
            return;
        }
        
        // Obține doctorii alocați la acest serviciu
        $doctors = $this->get_doctors_for_service($service_id);
        wp_send_json_success($doctors);
    }
    
    /**
     * Obține doctorii pentru un serviciu specific
     */
    public function get_doctors_for_service($service_id) {
        global $wpdb;
        
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Verifică dacă serviciul există și este activ
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_services WHERE id = %d AND active = 1",
            $service_id
        ));
        
        if (!$service) {
            return array();
        }
        
        // Obține TOȚI doctorii cu statusul lor de alocare la acest serviciu
        $doctors_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as doctor_id, u.display_name, u.user_email, 
                    COALESCE(ds.active, 0) as active
             FROM {$wpdb->users} u
             LEFT JOIN $table_doctor_services ds ON u.ID = ds.doctor_id AND ds.service_id = %d
             WHERE u.ID IN (
                 SELECT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = '{$wpdb->prefix}capabilities' 
                 AND (meta_value LIKE '%clinica_doctor%' OR meta_value LIKE '%clinica_manager%')
             )
             ORDER BY u.display_name ASC",
            $service_id
        ));
        
        // Convertește la formatul așteptat de JavaScript
        $doctors = array();
        foreach ($doctors_raw as $doctor) {
            $doctors[] = array(
                'id' => $doctor->doctor_id,
                'name' => $doctor->display_name,
                'email' => $doctor->user_email,
                'active' => $doctor->active
            );
        }
        
        return $doctors;
    }
    
    /**
     * AJAX: obține serviciile pentru un doctor
     */
    public function ajax_get_services_for_doctor() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_services_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisiuni insuficiente');
        }
        
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        
        if ($doctor_id <= 0) {
            wp_send_json_error('ID doctor invalid');
        }
        
        $services = $this->get_services_for_doctor($doctor_id);
        wp_send_json_success($services);
    }
    
    /**
     * Salvează alocarea unui doctor la un serviciu
     */
    public function ajax_save_doctor_service_allocation() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_services_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('clinica_manage_services') && !current_user_can('manage_options')) {
            wp_send_json_error('Permisiuni insuficiente');
        }
        
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
        
        if ($doctor_id <= 0 || $service_id <= 0) {
            wp_send_json_error('Parametri invalizi');
        }
        
        $result = $this->save_doctor_service_allocation($doctor_id, $service_id, $active);
        
        if ($result) {
            wp_send_json_success('Alocare salvată cu succes');
        } else {
            wp_send_json_error('Eroare la salvare');
        }
    }
    
    /**
     * Salvează alocarea doctor-serviciu în baza de date
     */
    public function save_doctor_service_allocation($doctor_id, $service_id, $active) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_doctor_services';
        
        // Verifică dacă alocarea există deja
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE doctor_id = %d AND service_id = %d",
            $doctor_id, $service_id
        ));
        
        if ($existing) {
            // Actualizează alocarea existentă
            return $wpdb->update(
                $table,
                array('active' => $active, 'updated_at' => current_time('mysql')),
                array('doctor_id' => $doctor_id, 'service_id' => $service_id),
                array('%d', '%s'),
                array('%d', '%d')
            );
        } else {
            // Creează o nouă alocare
            return $wpdb->insert(
                $table,
                array(
                    'doctor_id' => $doctor_id,
                    'service_id' => $service_id,
                    'active' => $active,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * AJAX: obține doctorii pentru un serviciu
     */
    public function ajax_get_service_doctors() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_services_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('clinica_manage_services') && !current_user_can('manage_options')) {
            wp_send_json_error('Permisiuni insuficiente');
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if ($service_id === 0) {
            // Returnează toate alocările pentru toate serviciile
            $doctors = $this->get_all_doctors_with_allocations();
            wp_send_json_success($doctors);
            return;
        }
        
        if ($service_id < 0) {
            wp_send_json_error('ID serviciu invalid');
        }
        
        $doctors = $this->get_doctors_for_service($service_id);
        wp_send_json_success($doctors);
    }
    
    /**
     * Obține serviciile pentru un doctor
     */
    public function get_services_for_doctor($doctor_id) {
        global $wpdb;
        
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        $services = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, ds.active as allocation_active 
             FROM $table_services s
             LEFT JOIN $table_doctor_services ds ON s.id = ds.service_id AND ds.doctor_id = %d
             WHERE s.active = 1
             ORDER BY s.name ASC",
            $doctor_id
        ));
        
        return $services;
    }
    
    /**
     * Obține toate serviciile cu alocările doctorilor
     */
    public function get_all_services_with_allocations() {
        global $wpdb;
        
        $table_services = $wpdb->prefix . 'clinica_services';
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        
        $services = $wpdb->get_results(
            "SELECT s.*, 
                    COUNT(ds.doctor_id) as total_doctors,
                    SUM(CASE WHEN ds.active = 1 THEN 1 ELSE 0 END) as active_doctors
             FROM $table_services s
             LEFT JOIN $table_doctor_services ds ON s.id = ds.service_id
             WHERE s.active = 1
             GROUP BY s.id
             ORDER BY s.name ASC"
        );
        
        return $services;
    }

    /**
     * Obține toate serviciile active (simplu, fără alocații)
     */
    public function get_all_services() {
        global $wpdb;

        $table_services = $wpdb->prefix . 'clinica_services';

        $services = $wpdb->get_results(
            "SELECT s.id, s.name, s.duration, s.active, s.created_at, s.updated_at
             FROM $table_services s
             WHERE s.active = 1
             ORDER BY s.name ASC"
        );

        return $services;
    }

    /**
     * Obține toți doctorii cu serviciile lor alocate
     */
    public function get_all_doctors_with_services() {
        global $wpdb;
        
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        $doctors = get_users(array(
            'role__in' => array('clinica_doctor', 'clinica_manager'),
            'orderby' => 'display_name'
        ));
        
        $result = array();
        foreach ($doctors as $doctor) {
            $services = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, ds.active as allocation_active
                 FROM $table_services s
                 INNER JOIN $table_doctor_services ds ON s.id = ds.service_id
                 WHERE ds.doctor_id = %d AND ds.active = 1
                 ORDER BY s.name ASC",
                $doctor->ID
            ));
            
            $result[] = array(
                'id' => $doctor->ID,
                'name' => $doctor->display_name,
                'email' => $doctor->user_email,
                'services' => $services
            );
        }
        
        return $result;
    }
    
    /**
     * Obține toate alocările doctori-servicii pentru dashboard
     */
    public function get_all_doctors_with_allocations() {
        global $wpdb;
        
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        $table_services = $wpdb->prefix . 'clinica_services';
        
        // Obține toate alocările existente
        $allocations = $wpdb->get_results(
            "SELECT ds.doctor_id, ds.service_id, ds.active,
                    u.display_name, u.user_email,
                    s.name as service_name
             FROM $table_doctor_services ds
             INNER JOIN {$wpdb->users} u ON ds.doctor_id = u.ID
             INNER JOIN $table_services s ON ds.service_id = s.id
             WHERE s.active = 1
             ORDER BY s.name ASC, u.display_name ASC"
        );
        
        return $allocations;
    }
    
    /**
     * AJAX handler pentru normalizarea numelor (UPPERCASE → Title Case)
     */
    public function ajax_normalize_name() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_normalize_name')) {
            wp_send_json_error('Eroare de securitate');
            return;
        }
        
        $name = sanitize_text_field($_POST['name']);
        
        if (empty($name)) {
            wp_send_json_error('Numele este obligatoriu');
            return;
        }
        
        // Normalizează numele folosind funcția din Clinica_Database
        $normalized_name = Clinica_Database::normalize_name($name);
        
        wp_send_json_success(array('normalized_name' => $normalized_name));
    }
    
    // ==================== TIMESLOTS MANAGEMENT ====================
    
    /**
     * Creează tabelele pentru timeslots
     */
    public function create_timeslots_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql_timeslots = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}clinica_doctor_timeslots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            doctor_id INT NOT NULL,
            service_id INT NOT NULL,
            day_of_week TINYINT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            slot_duration INT NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_slot (doctor_id, service_id, day_of_week, start_time),
            KEY idx_doctor_service (doctor_id, service_id),
            KEY idx_day_active (day_of_week, is_active)
        ) $charset_collate;";
        
        $sql_exceptions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}clinica_doctor_exceptions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            doctor_id INT NOT NULL,
            service_id INT NULL,
            exception_date DATE NOT NULL,
            exception_type ENUM('day_off', 'vacation', 'sick_leave', 'custom') NOT NULL,
            start_time TIME NULL,
            end_time TIME NULL,
            reason VARCHAR(255),
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_doctor_date (doctor_id, exception_date),
            KEY idx_service_date (service_id, exception_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_timeslots);
        dbDelta($sql_exceptions);
        
        return true;
    }
    
    /**
     * Adaugă timeslot pentru un doctor
     */
    public function add_timeslot($doctor_id, $service_id, $day_of_week, $start_time, $end_time, $slot_duration) {
        global $wpdb;
        
        // Verifică dacă doctorul este alocat la serviciu
        if (!$this->is_doctor_allocated_to_service($doctor_id, $service_id)) {
            return false;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'clinica_doctor_timeslots',
            array(
                'doctor_id' => $doctor_id,
                'service_id' => $service_id,
                'day_of_week' => $day_of_week,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'slot_duration' => $slot_duration,
                'is_active' => 1
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Actualizează timeslot
     */
    public function update_timeslot($timeslot_id, $data) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'clinica_doctor_timeslots',
            $data,
            array('id' => $timeslot_id),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Șterge timeslot
     */
    public function delete_timeslot($timeslot_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'clinica_doctor_timeslots',
            array('id' => $timeslot_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Șterge toate timeslot-urile pentru o zi specifică
     */
    public function delete_day_timeslots($doctor_id, $service_id, $day_of_week) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'clinica_doctor_timeslots',
            array(
                'doctor_id' => $doctor_id,
                'service_id' => $service_id,
                'day_of_week' => $day_of_week
            ),
            array('%d', '%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Obține toate timeslot-urile pentru un doctor
     */
    public function get_all_timeslots_for_doctor($doctor_id) {
        global $wpdb;
        
        $timeslots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}clinica_doctor_timeslots 
             WHERE doctor_id = %d AND is_active = 1
             ORDER BY day_of_week, start_time",
            $doctor_id
        ));
        
        return $timeslots;
    }
    
    /**
     * Obține timeslot-urile pentru un doctor și serviciu
     */
    public function get_timeslots_for_doctor_service($doctor_id, $service_id) {
        global $wpdb;
        
        $timeslots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}clinica_doctor_timeslots 
             WHERE doctor_id = %d AND service_id = %d AND is_active = 1
             ORDER BY day_of_week, start_time",
            $doctor_id, $service_id
        ));
        
        return $timeslots;
    }
    
    /**
     * Generează sloturile disponibile pentru o dată
     */
    public function generate_available_slots($doctor_id, $service_id, $date) {
        // Obține timeslot-urile pentru ziua respectivă
        $day_of_week = date('N', strtotime($date));
        $timeslots = $this->get_timeslots_for_doctor_service($doctor_id, $service_id);
        
        $day_timeslots = array_filter($timeslots, function($timeslot) use ($day_of_week) {
            return $timeslot->day_of_week == $day_of_week;
        });
        
        if (empty($day_timeslots)) {
            return array();
        }
        
        $slots = array();
        
        foreach ($day_timeslots as $timeslot) {
            $start_time = strtotime($timeslot->start_time);
            $end_time = strtotime($timeslot->end_time);
            $slot_duration = $timeslot->slot_duration * 60; // în secunde
            
            $current_time = $start_time;
            
            while ($current_time + $slot_duration <= $end_time) {
                $slot_end = $current_time + $slot_duration;
                
                $slot = array(
                    'start_time' => date('H:i:s', $current_time),
                    'end_time' => date('H:i:s', $slot_end),
                    'start_datetime' => $date . ' ' . date('H:i:s', $current_time),
                    'end_datetime' => $date . ' ' . date('H:i:s', $slot_end)
                );
                
                $slots[] = $slot;
                $current_time = $slot_end;
            }
        }
        
        // Elimină sloturile ocupate
        $slots = $this->remove_booked_slots($doctor_id, $service_id, $date, $slots);
        
        // Elimină excepțiile
        $slots = $this->remove_exceptions($doctor_id, $service_id, $date, $slots);
        
        // Elimină sloturile din pauzele clinicii
        $slots = $this->remove_clinic_breaks($date, $slots);
        
        return array_values($slots);
    }
    
    /**
     * Elimină sloturile din pauzele clinicii
     */
    private function remove_clinic_breaks($date, $slots) {
        // Obține programul clinicii
        $settings = Clinica_Settings::get_instance();
        $working_hours = $settings->get('working_hours', array());
        
        if (empty($working_hours)) {
            return $slots;
        }
        
        // Determină ziua săptămânii
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        if (!isset($working_hours[$day_of_week])) {
            return $slots;
        }
        
        $day_schedule = $working_hours[$day_of_week];
        $break_start = isset($day_schedule['break_start']) ? $day_schedule['break_start'] : '';
        $break_end = isset($day_schedule['break_end']) ? $day_schedule['break_end'] : '';
        
        // Dacă nu există pauză configurată, returnează sloturile
        if (empty($break_start) || empty($break_end)) {
            return $slots;
        }
        
        // Convertește pauza în timp Unix
        $break_start_time = strtotime($break_start);
        $break_end_time = strtotime($break_end);
        
        if ($break_start_time === false || $break_end_time === false) {
            return $slots;
        }
        
        // Filtrează sloturile care nu se suprapun cu pauza
        $filtered_slots = array();
        
        foreach ($slots as $slot) {
            $slot_start = strtotime($slot['start_time']);
            $slot_end = strtotime($slot['end_time']);
            
            // Verifică dacă slotul se suprapune cu pauza
            $overlaps_break = ($slot_start < $break_end_time && $break_start_time < $slot_end);
            
            if (!$overlaps_break) {
                $filtered_slots[] = $slot;
            }
        }
        
        return $filtered_slots;
    }
    
    /**
     * Elimină sloturile deja ocupate
     */
    private function remove_booked_slots($doctor_id, $service_id, $date, $slots) {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'clinica_appointments';
        
        $booked_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time, duration 
             FROM {$appointments_table} 
             WHERE doctor_id = %d 
             AND DATE(appointment_time) = %s 
             AND status NOT IN ('cancelled', 'no_show')",
            $doctor_id, $date
        ));
        
        foreach ($booked_slots as $booked_slot) {
            $slots = array_filter($slots, function($slot) use ($booked_slot) {
                return !$this->slots_overlap($slot, $booked_slot);
            });
        }
        
        return array_values($slots);
    }
    
    /**
     * Elimină excepțiile
     */
    private function remove_exceptions($doctor_id, $service_id, $date, $slots) {
        global $wpdb;
        
        $exceptions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}clinica_doctor_exceptions 
             WHERE doctor_id = %d AND exception_date = %s AND is_active = 1",
            $doctor_id, $date
        ));
        
        foreach ($exceptions as $exception) {
            if ($exception->start_time === null && $exception->end_time === null) {
                // Excepție pentru toată ziua
                return array();
            }
            
            $slots = array_filter($slots, function($slot) use ($exception) {
                return !$this->slots_overlap($slot, $exception);
            });
        }
        
        return array_values($slots);
    }
    
    /**
     * Verifică dacă două sloturi se suprapun
     */
    private function slots_overlap($slot, $booked_slot) {
        $slot_start = strtotime($slot['start_time']);
        $slot_end = strtotime($slot['end_time']);
        $booked_start = strtotime($booked_slot->appointment_time);
        $booked_end = strtotime($booked_slot->appointment_time) + ($booked_slot->duration * 60);
        
        return ($slot_start < $booked_end && $slot_end > $booked_start);
    }
    
    /**
     * Verifică dacă un doctor este alocat la un serviciu
     */
    private function is_doctor_allocated_to_service($doctor_id, $service_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_doctor_services 
             WHERE doctor_id = %d AND service_id = %d AND active = 1",
            $doctor_id, $service_id
        ));
        
        return $result > 0;
    }
    

    
    /**
     * AJAX handler pentru salvarea timeslot-ului
     */
    public function ajax_save_timeslot() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manage_clinic_schedule')) {
            wp_send_json_error('Nu aveți permisiunea de a gestiona timeslot-urile');
        }
        
        $timeslot_id = intval($_POST['timeslot_id']);
        $data = array(
            'doctor_id' => intval($_POST['doctor_id']),
            'service_id' => intval($_POST['service_id']),
            'day_of_week' => intval($_POST['day_of_week']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'end_time' => sanitize_text_field($_POST['end_time']),
            'slot_duration' => intval($_POST['slot_duration'])
        );
        
        if ($timeslot_id > 0) {
            $result = $this->update_timeslot($timeslot_id, $data);
        } else {
            $result = $this->add_timeslot(
                $data['doctor_id'],
                $data['service_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $data['slot_duration']
            );
        }
        
        if ($result) {
            wp_send_json_success('Timeslot salvat cu succes');
        } else {
            wp_send_json_error('Eroare la salvarea timeslot-ului');
        }
    }
    
    /**
     * AJAX handler pentru ștergerea timeslot-ului
     */
    public function ajax_delete_timeslot() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('clinica_manage_clinic_schedule') && !current_user_can('manage_options')) {
            wp_send_json_error('Nu aveți permisiunea de a gestiona timeslot-urile');
        }
        
        $timeslot_id = intval($_POST['timeslot_id']);
        
        $result = $this->delete_timeslot($timeslot_id);
        
        if ($result) {
            wp_send_json_success('Timeslot șters cu succes');
        } else {
            wp_send_json_error('Eroare la ștergerea timeslot-ului');
        }
    }
    
    /**
     * AJAX handler pentru ștergerea timeslot-urilor unei zile
     */
    public function ajax_delete_day_timeslots() {
        
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('clinica_manage_clinic_schedule') && !current_user_can('manage_options')) {
            wp_send_json_error('Nu aveți permisiunea de a gestiona timeslot-urile');
        }
        
        $doctor_id = intval($_POST['doctor_id']);
        $service_id = intval($_POST['service_id']);
        $day_of_week = intval($_POST['day_of_week']);
        
        
        $result = $this->delete_day_timeslots($doctor_id, $service_id, $day_of_week);
        
        if ($result) {
            wp_send_json_success('Timeslots-urile zilei au fost șterse cu succes');
        } else {
            wp_send_json_error('Eroare la ștergerea timeslots-urilor zilei');
        }
    }
    
    /**
     * AJAX handler pentru obținerea timeslot-urilor
     */
    public function ajax_get_doctor_timeslots() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manage_clinic_schedule')) {
            wp_send_json_error('Nu aveți permisiunea de a vizualiza timeslot-urile');
        }
        
        $doctor_id = intval($_POST['doctor_id']);
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

        $timeslots = $this->get_timeslots_for_doctor_service($doctor_id, $service_id);
        wp_send_json_success($timeslots);
    }
    
    /**
     * AJAX handler pentru obținerea sloturilor disponibile
     */
    public function ajax_get_available_slots() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $doctor_id = intval($_POST['doctor_id']);
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = sanitize_text_field($_POST['date']);
        
        $available_slots = $this->generate_available_slots($doctor_id, $service_id, $date);
        wp_send_json_success($available_slots);
    }
    
    /**
     * AJAX handler pentru ștergerea tuturor timeslots-urilor unui doctor și serviciu
     */
    public function ajax_delete_all_doctor_service_timeslots() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manage_clinic_schedule')) {
            wp_send_json_error('Nu aveți permisiunea de a gestiona timeslot-urile');
        }
        
        $doctor_id = intval($_POST['doctor_id']);
        $service_id = intval($_POST['service_id']);
        
        if ($doctor_id <= 0 || $service_id <= 0) {
            wp_send_json_error('ID-uri invalide');
        }
        
        $result = $this->delete_all_doctor_service_timeslots($doctor_id, $service_id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'deleted_count' => $result,
                'message' => "S-au șters {$result} timeslots pentru doctorul și serviciul selectat"
            ));
        } else {
            wp_send_json_error('Eroare la ștergerea timeslots-urilor');
        }
    }
    
    /**
     * Șterge toate timeslots-urile pentru un doctor și serviciu specificat
     */
    public function delete_all_doctor_service_timeslots($doctor_id, $service_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_doctor_timeslots';
        
        // Șterge toate timeslots-urile pentru doctorul și serviciul specificat
        $result = $wpdb->delete(
            $table,
            array(
                'doctor_id' => $doctor_id,
                'service_id' => $service_id
            ),
            array('%d', '%d')
        );
        
        return $result;
    }
    
    /**
     * AJAX handler pentru numărarea sloturilor unui doctor
     */
    public function ajax_get_doctor_timeslots_count() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        
        if ($doctor_id <= 0) {
            wp_send_json_error('ID doctor invalid');
        }
        
        global $wpdb;
        
        // Numără toate sloturile pentru acest doctor
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_doctor_timeslots WHERE doctor_id = %d",
            $doctor_id
        ));
        
        wp_send_json_success(array(
            'count' => intval($count)
        ));
    }
    
    /**
     * AJAX handler pentru programul zilei
     */
    public function ajax_get_today_schedule() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_timeslots_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
        
        global $wpdb;
        
        // DEBUG: Verifică mai întâi dacă există sloturi în baza de date
        $total_slots = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_doctor_timeslots");
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}clinica_doctor_timeslots'");
        
        // Verifică structura tabelei
        $table_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}clinica_doctor_timeslots");
        
        // Verifică dacă există câmpuri pentru ziua săptămânii
        $has_day_field = false;
        $has_weekday_field = false;
        foreach ($table_structure as $field) {
            if (strpos(strtolower($field->Field), 'day') !== false) {
                $has_day_field = true;
            }
            if (strpos(strtolower($field->Field), 'weekday') !== false) {
                $has_weekday_field = true;
            }
        }
        
        // DEBUG: Verifică structura tabelei pentru a vedea cum sunt stocate datele
        $sample_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_doctor_timeslots LIMIT 3");
        
        // Verifică dacă start_time conține dată completă sau doar ora
        if (!empty($sample_data)) {
            $first_row = $sample_data[0];
            
            // Verifică dacă e doar ora sau dată completă
            if (strpos($first_row->start_time, ' ') === false) {
            } else {
            }
        }
        
        // CORECTARE: Trebuie să verific ce zi a săptămânii e data specificată
        $day_of_week = date('N', strtotime($date)); // 1=Luni, 2=Marți, 3=Miercuri, 4=Joi, 5=Vineri
        $day_names = array(1 => 'Luni', 2 => 'Marți', 3 => 'Miercuri', 4 => 'Joi', 5 => 'Vineri');
        $current_day_name = $day_names[$day_of_week];
        
        
        // CORECTARE: Sloturile au doar ora, nu dată completă!
        // Trebuie să luăm toate sloturile și să le filtrăm manual pentru data specificată
        $all_slots = $wpdb->get_results(
            "SELECT 
                dt.doctor_id,
                dt.service_id,
                dt.start_time,
                dt.end_time,
                u.display_name as doctor_name,
                s.name as service_name
            FROM {$wpdb->prefix}clinica_doctor_timeslots dt
            LEFT JOIN {$wpdb->users} u ON dt.doctor_id = u.ID
            LEFT JOIN {$wpdb->prefix}clinica_services s ON dt.service_id = s.id
            ORDER BY dt.doctor_id, dt.start_time"
        );
        
        // CORECTARE: Verific dacă există câmp day_of_week în tabelă
        $has_day_field = false;
        foreach ($table_structure as $field) {
            if (strtolower($field->Field) === 'day_of_week' || strtolower($field->Field) === 'day') {
                $has_day_field = true;
                break;
            }
        }
        
        // Filtrăm manual pentru data specificată
        $slots = array();
        if ($has_day_field) {
            // Dacă există câmp day_of_week, folosim query corect
            $slots = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    dt.doctor_id,
                    dt.service_id,
                    dt.start_time,
                    dt.end_time,
                    u.display_name as doctor_name,
                    s.name as service_name
                FROM {$wpdb->prefix}clinica_doctor_timeslots dt
                LEFT JOIN {$wpdb->users} u ON dt.doctor_id = u.ID
                LEFT JOIN {$wpdb->prefix}clinica_services s ON dt.service_id = s.id
                WHERE dt.day_of_week = %d
                ORDER BY dt.doctor_id, dt.start_time",
                $day_of_week
            ));
        } else {
            // Dacă nu există câmp day_of_week, luăm toate sloturile (problema actuală)
            $slots = $all_slots;
        }
        
        
        
        
        // DEBUG: Analizează serviciile cu sloturi pentru astăzi
        $services_with_slots = array();
        foreach ($slots as $slot) {
            $service_name = $slot->service_name ?: 'Serviciu necunoscut';
            $doctor_name = $slot->doctor_name ?: 'Doctor necunoscut';
            
            if (!isset($services_with_slots[$service_name])) {
                $services_with_slots[$service_name] = array(
                    'total_slots' => 0,
                    'free_slots' => 0,
                    'occupied_slots' => 0,
                    'doctors' => array()
                );
            }
            $services_with_slots[$service_name]['total_slots']++;
            
            if (!in_array($doctor_name, $services_with_slots[$service_name]['doctors'])) {
                $services_with_slots[$service_name]['doctors'][] = $doctor_name;
            }
        }
        
        // CORECTARE: Elimină serviciile cu 0 sloturi
        $filtered_services = array();
        foreach ($services_with_slots as $service => $data) {
            if ($data['total_slots'] > 0) {
                $filtered_services[$service] = $data;
            }
        }
        $services_with_slots = $filtered_services;
        
        // Verifică dacă există programări pentru a calcula sloturile ocupate
        // Căutăm în toate tabelele posibile pentru programări
        $appointments_tables = array(
            $wpdb->prefix . 'clinica_appointments',
            $wpdb->prefix . 'clinica_ai_appointments',
            $wpdb->prefix . 'appointments',
            $wpdb->prefix . 'bookings',
            $wpdb->prefix . 'reservations'
        );
        
        $appointments_found = false;
        foreach ($appointments_tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($table_exists) {
                $appointments_found = true;
                
                // Încearcă să găsești programări pentru data specificată
                // Verifică structura tabelei pentru a folosi câmpurile corecte
                $columns = $wpdb->get_col("SHOW COLUMNS FROM $table");
                
                if (in_array('appointment_date', $columns)) {
                    // Tabela standard de programări
                    $appointments = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table WHERE DATE(appointment_date) = %s",
                        $date
                    ));
                } elseif (in_array('confirmed_slot', $columns)) {
                    // Tabela AI de programări
                    $appointments = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table WHERE DATE(confirmed_slot) = %s",
                        $date
                    ));
                } else {
                    // Fallback pentru alte tabele
                    $appointments = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table WHERE DATE(booking_date) = %s OR DATE(reservation_date) = %s",
                        $date, $date
                    ));
                }
                
                if (!empty($appointments)) {
                    // Aici ar trebui să calculezi sloturile ocupate pe baza programărilor reale
                    // Pentru moment, toate sloturile sunt libere
                    foreach ($services_with_slots as $service => &$data) {
                        $data['occupied_slots'] = 0; // Nu există programări făcute
                        $data['free_slots'] = $data['total_slots']; // Toate sloturile sunt libere
                    }
                } else {
                    // Toate sloturile sunt libere
                    foreach ($services_with_slots as $service => &$data) {
                        $data['occupied_slots'] = 0; // Nu există programări făcute
                        $data['free_slots'] = $data['total_slots']; // Toate sloturile sunt libere
                    }
                }
                break;
            }
        }
        
        if (!$appointments_found) {
            // Toate sloturile sunt libere
            foreach ($services_with_slots as $service => &$data) {
                $data['occupied_slots'] = 0; // Nu există programări făcute
                $data['free_slots'] = $data['total_slots']; // Toate sloturile sunt libere
            }
        }
        
        
        // DEBUG: Afișează rezumatul
        $summary = "REZUMAT SLOTURI ASTĂZI (" . $date . "):\n";
        $summary .= "Total sloturi: " . count($slots) . "\n";
        $summary .= "Servicii cu sloturi:\n";
        foreach ($services_with_slots as $service => $data) {
            $summary .= "- " . $service . ": " . $data['total_slots'] . " sloturi totale, " . $data['free_slots'] . " libere, " . $data['occupied_slots'] . " ocupate (doctori: " . implode(', ', $data['doctors']) . ")\n";
        }
        
        // DEBUG: Verifică dacă query-ul e corect
        if (count($slots) > 0) {
            $first_slot = $slots[0];
            
            // DEBUG: Verifică primele 5 sloturi pentru a vedea datele exacte
            for ($i = 0; $i < min(5, count($slots)); $i++) {
                $slot = $slots[$i];
            }
        }
        
        wp_send_json_success($slots);
    }
}

// Inițializează clasa
Clinica_Services_Manager::get_instance();
