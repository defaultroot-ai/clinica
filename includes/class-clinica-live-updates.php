<?php
/**
 * Live Updates pentru Plugin Clinica
 * Gestionează actualizările în timp real pentru dashboard-uri
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Live_Updates {
    
    /**
     * Instanta singleton
     */
    private static $instance = null;
    
    /**
     * Intervalul de polling în milisecunde
     */
    private $polling_interval = 15000; // 15 secunde
    
    /**
     * Cache pentru digest-uri
     */
    private $digest_cache = array();
    
    /**
     * Constructor privat pentru singleton
     */
    private function __construct() {
        // Constructor privat pentru singleton
    }
    
    /**
     * Obține instanța singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * AJAX handler pentru digest-ul programărilor
     */
    public function ajax_appointments_digest() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_live_updates_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        // Verifică autentificarea
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        // Obține filtrele
        $filters = $this->sanitize_filters($_POST);
        
        // Calculează digest-ul
        $digest = $this->calculate_appointments_digest($filters);
        
        wp_send_json_success(array(
            'digest' => $digest,
            'timestamp' => current_time('mysql'),
            'filters' => $filters
        ));
    }
    
    /**
     * AJAX handler pentru schimbările programărilor
     */
    public function ajax_appointments_changes() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_live_updates_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        // Verifică autentificarea
        if (!is_user_logged_in()) {
            wp_send_json_error('Nu sunteți autentificat');
        }
        
        // Obține parametrii
        $since_timestamp = sanitize_text_field($_POST['since']);
        $filters = $this->sanitize_filters($_POST);
        
        if (empty($since_timestamp)) {
            wp_send_json_error('Timestamp invalid');
        }
        
        // Obține schimbările
        $changes = $this->get_appointments_changes($since_timestamp, $filters);
        
        wp_send_json_success(array(
            'changes' => $changes,
            'last_update' => current_time('mysql'),
            'count' => count($changes)
        ));
    }
    
    /**
     * Calculează digest-ul pentru programări
     */
    private function calculate_appointments_digest($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        // Construiește condiții WHERE cu parametri
        $where_conditions = array();
        $where_values = array();
        
        // Filtru status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where_conditions[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        // Filtru doctor
        if (!empty($filters['doctor_id']) && $filters['doctor_id'] > 0) {
            $where_conditions[] = "doctor_id = %d";
            $where_values[] = intval($filters['doctor_id']);
        }
        
        // Filtru dată
        if (!empty($filters['date'])) {
            $where_conditions[] = "appointment_date = %s";
            $where_values[] = $filters['date'];
        }
        
        // Filtru perioadă
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "appointment_date >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "appointment_date <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        // Filtru pacient
        if (!empty($filters['patient_id']) && $filters['patient_id'] > 0) {
            $where_conditions[] = "patient_id = %d";
            $where_values[] = intval($filters['patient_id']);
        }
        
        // Construiește query-ul
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "
            SELECT 
                MAX(updated_at) as last_update,
                COUNT(*) as total_count
            FROM $table_name 
            $where_clause
        ";
        
        // Calculează digest-ul
        if (!empty($where_values)) {
            $result = $wpdb->get_row($wpdb->prepare($query, $where_values));
        } else {
            $result = $wpdb->get_row($query);
        }
        
        if (!$result) {
            return md5('empty');
        }
        
        return md5($result->last_update . $result->total_count);
    }
    
    /**
     * Obține schimbările programărilor de la un timestamp
     */
    private function get_appointments_changes($since_timestamp, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        // Construiește condiții WHERE cu parametri
        $where_conditions = array();
        $where_values = array();
        
        // Filtru status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where_conditions[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        // Filtru doctor
        if (!empty($filters['doctor_id']) && $filters['doctor_id'] > 0) {
            $where_conditions[] = "doctor_id = %d";
            $where_values[] = intval($filters['doctor_id']);
        }
        
        // Filtru dată
        if (!empty($filters['date'])) {
            $where_conditions[] = "appointment_date = %s";
            $where_values[] = $filters['date'];
        }
        
        // Filtru perioadă
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "appointment_date >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "appointment_date <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        // Filtru pacient
        if (!empty($filters['patient_id']) && $filters['patient_id'] > 0) {
            $where_conditions[] = "patient_id = %d";
            $where_values[] = intval($filters['patient_id']);
        }
        
        // Adaugă condiția pentru timestamp
        $where_conditions[] = "updated_at > %s";
        $where_values[] = $since_timestamp;
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                id,
                patient_id,
                doctor_id,
                appointment_date,
                appointment_time,
                status,
                type,
                service_id,
                duration,
                notes,
                updated_at,
                last_edited_by_type,
                last_edited_by_user_id,
                last_edited_at
            FROM $table_name 
            $where_clause
            ORDER BY updated_at ASC
            LIMIT 100
        ";
        
        // Obține schimbările
        $changes = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Formatează datele pentru frontend
        return $this->format_changes_for_frontend($changes);
    }
    
    
    /**
     * Sanitizează filtrele primite
     */
    private function sanitize_filters($post_data) {
        $filters = array();
        
        if (isset($post_data['status'])) {
            $filters['status'] = sanitize_text_field($post_data['status']);
        }
        
        if (isset($post_data['doctor_id'])) {
            $filters['doctor_id'] = intval($post_data['doctor_id']);
        }
        
        if (isset($post_data['date'])) {
            $filters['date'] = sanitize_text_field($post_data['date']);
        }
        
        if (isset($post_data['date_from'])) {
            $filters['date_from'] = sanitize_text_field($post_data['date_from']);
        }
        
        if (isset($post_data['date_to'])) {
            $filters['date_to'] = sanitize_text_field($post_data['date_to']);
        }
        
        if (isset($post_data['patient_id'])) {
            $filters['patient_id'] = intval($post_data['patient_id']);
        }
        
        return $filters;
    }
    
    /**
     * Formatează schimbările pentru frontend
     */
    private function format_changes_for_frontend($changes) {
        $formatted = array();
        
        foreach ($changes as $change) {
            $formatted[] = array(
                'id' => intval($change->id),
                'patient_id' => intval($change->patient_id),
                'doctor_id' => intval($change->doctor_id),
                'appointment_date' => $change->appointment_date,
                'appointment_time' => $change->appointment_time,
                'status' => $change->status,
                'type' => $change->type,
                'service_id' => $change->service_id ? intval($change->service_id) : null,
                'duration' => $change->duration ? intval($change->duration) : 30,
                'notes' => $change->notes,
                'updated_at' => $change->updated_at,
                'last_edited_by_type' => $change->last_edited_by_type,
                'last_edited_by_user_id' => $change->last_edited_by_user_id ? intval($change->last_edited_by_user_id) : null,
                'last_edited_at' => $change->last_edited_at
            );
        }
        
        return $formatted;
    }
    
    /**
     * Obține intervalul de polling
     */
    public function get_polling_interval() {
        return $this->polling_interval;
    }
    
    /**
     * Setează intervalul de polling
     */
    public function set_polling_interval($interval) {
        $this->polling_interval = max(5000, min(60000, intval($interval))); // 5-60 secunde
    }
}
