<?php
/**
 * Gestionare program global clinică
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Clinic_Schedule {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_clinica_save_clinic_schedule', array($this, 'ajax_save_clinic_schedule'));
        add_action('wp_ajax_clinica_get_clinic_schedule', array($this, 'ajax_get_clinic_schedule'));
    }
    
    /**
     * Salvează programul global al clinicii
     */
    public function ajax_save_clinic_schedule() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_clinic_schedule_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        if (!current_user_can('clinica_manage_settings')) {
            wp_send_json_error('Permisiuni insuficiente');
        }
        
        $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : array();
        
        if (!is_array($schedule)) {
            wp_send_json_error('Format invalid pentru program');
        }
        
        $result = $this->save_clinic_schedule($schedule);
        
        if ($result) {
            wp_send_json_success('Program salvat cu succes');
        } else {
            wp_send_json_error('Eroare la salvare');
        }
    }
    
    /**
     * Salvează programul în baza de date
     */
    public function save_clinic_schedule($schedule) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_clinic_schedule';
        
        // Șterge programul existent
        $wpdb->query("DELETE FROM $table");
        
        $success = true;
        
        foreach ($schedule as $day => $data) {
            if (!empty($data['active']) && !empty($data['start_time']) && !empty($data['end_time'])) {
                $result = $wpdb->insert(
                    $table,
                    array(
                        'day_of_week' => $day,
                        'active' => 1,
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'break_start' => !empty($data['break_start']) ? $data['break_start'] : null,
                        'break_end' => !empty($data['break_end']) ? $data['break_end'] : null,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
                );
                
                if (!$result) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Obține programul global al clinicii
     */
    public function ajax_get_clinic_schedule() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_clinic_schedule_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $schedule = $this->get_clinic_schedule();
        wp_send_json_success($schedule);
    }
    
    /**
     * Obține programul din baza de date
     */
    public function get_clinic_schedule() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_clinic_schedule';
        
        $schedule = array();
        
        // Inițializează cu programul implicit
        $default_schedule = array(
            'monday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'tuesday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'wednesday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'thursday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'friday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'saturday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => ''),
            'sunday' => array('active' => false, 'start_time' => '', 'end_time' => '', 'break_start' => '', 'break_end' => '')
        );
        
        // Obține programul din baza de date
        $db_schedule = $wpdb->get_results("SELECT * FROM $table WHERE active = 1 ORDER BY day_of_week");
        
        foreach ($db_schedule as $row) {
            $default_schedule[$row->day_of_week] = array(
                'active' => true,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'break_start' => $row->break_start,
                'break_end' => $row->break_end
            );
        }
        
        return $default_schedule;
    }
    
    /**
     * Obține programul pentru o zi specifică
     */
    public function get_day_schedule($day_of_week) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_clinic_schedule';
        
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE day_of_week = %s AND active = 1",
            $day_of_week
        ));
        
        if (!$schedule) {
            return null;
        }
        
        return array(
            'active' => true,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'break_start' => $schedule->break_start,
            'break_end' => $schedule->break_end
        );
    }
    
    /**
     * Verifică dacă o zi este disponibilă pentru programări
     */
    public function is_day_available($date) {
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        // Verifică programul global
        $global_schedule = $this->get_day_schedule($day_of_week);
        
        if (!$global_schedule || !$global_schedule['active']) {
            return false;
        }
        
        // Verifică dacă este zi de concediu
        $settings = Clinica_Settings::get_instance();
        $holidays = $settings->get('clinic_holidays', array());
        if (is_string($holidays)) {
            $holidays = json_decode($holidays, true);
        }
        
        if (is_array($holidays) && in_array($date, $holidays)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obține orele de început și sfârșit pentru o zi
     */
    public function get_day_hours($date) {
        $day_of_week = strtolower(date('l', strtotime($date)));
        $schedule = $this->get_day_schedule($day_of_week);
        
        if (!$schedule || !$schedule['active']) {
            return null;
        }
        
        return array(
            'start' => $schedule['start_time'],
            'end' => $schedule['end_time'],
            'break_start' => $schedule['break_start'],
            'break_end' => $schedule['break_end']
        );
    }
}

// Inițializează clasa
Clinica_Clinic_Schedule::get_instance();
