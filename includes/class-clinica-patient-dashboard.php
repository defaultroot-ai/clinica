<?php
/**
 * Dashboard pentru pacienți
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Patient_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Adaugă shortcode pentru dashboard
        add_shortcode('clinica_patient_dashboard', array($this, 'render_dashboard_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_clinica_get_patient_data', array($this, 'ajax_get_patient_data'));
        add_action('wp_ajax_clinica_update_patient_info', array($this, 'ajax_update_patient_info'));
        add_action('wp_ajax_clinica_get_appointments', array($this, 'ajax_get_appointments'));
        add_action('wp_ajax_clinica_cancel_appointment', array($this, 'ajax_cancel_appointment'));
        add_action('wp_ajax_clinica_get_medical_history', array($this, 'ajax_get_medical_history'));
        add_action('wp_ajax_clinica_get_patient_family', array($this, 'ajax_get_patient_family'));
        add_action('wp_ajax_clinica_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_clinica_get_recent_activities', array($this, 'ajax_get_recent_activities'));
        // Booking helpers
        add_action('wp_ajax_clinica_get_booking_patients', array($this, 'ajax_get_booking_patients'));
        add_action('wp_ajax_clinica_get_doctors_for_service', array($this, 'ajax_get_doctors_for_service'));
        add_action('wp_ajax_clinica_get_doctor_availability_days', array($this, 'ajax_get_doctor_availability_days'));
        add_action('wp_ajax_clinica_get_doctor_slots', array($this, 'ajax_get_doctor_slots'));
        add_action('wp_ajax_clinica_create_own_appointment', array($this, 'ajax_create_own_appointment'));
        add_action('wp_ajax_clinica_get_services_catalog', array($this, 'ajax_get_services_catalog'));
        add_action('wp_ajax_clinica_get_appointment', array($this, 'ajax_get_appointment'));
        // Admin create appointment
        add_action('wp_ajax_clinica_admin_create_appointment', array($this, 'ajax_admin_create_appointment'));
        // Admin update appointment
        add_action('wp_ajax_clinica_admin_update_appointment', array($this, 'ajax_admin_update_appointment'));
        // Admin transfer appointment
        add_action('wp_ajax_clinica_admin_transfer_appointment', array($this, 'ajax_admin_transfer_appointment'));
        add_action('wp_ajax_clinica_get_romanian_holidays', array($this, 'ajax_get_romanian_holidays'));
    }

    /**
     * Admin: creare programare din pagina Programări
     */
    public function ajax_admin_create_appointment() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'clinica_admin_create_nonce')) {
            wp_send_json_error(__('Eroare de securitate (nonce).', 'clinica'));
        }
        if (!Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error(__('Nu aveți permisiunea pentru această acțiune.', 'clinica'));
        }

        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
        $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $send_email = !empty($_POST['send_email']);

        if ($patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
            wp_send_json_error(__('Date incomplete.', 'clinica'));
        }

        // Normalizează ora (H:i)
        if (strlen($appointment_time) > 5) { $appointment_time = substr($appointment_time, 0, 5); }

        // Tipul din service_id
        $type = '';
        if ($service_id > 0) {
            $type = $this->get_service_name_by_id($service_id);
        }

        // Verifică conflict slot-uri
        $slotStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . $appointment_time);
        if (!$slotStart) { wp_send_json_error(__('Dată/oră invalide.', 'clinica')); }
        $slotEnd = (clone $slotStart)->modify('+' . max(1,$duration) . ' minutes');

        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            wp_send_json_error(__('Tabela programări nu există.', 'clinica'));
        }

        // Conflicte medic
        $rows = $wpdb->get_results($wpdb->prepare("SELECT appointment_time, duration FROM $table WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')", $doctor_id, $appointment_date));
        foreach ($rows as $r) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . substr($r->appointment_time,0,5));
            if (!$exStart) { continue; }
            $exEnd = (clone $exStart)->modify('+' . (int)$r->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) { wp_send_json_error(__('Interval ocupat pentru medic.', 'clinica')); }
        }
        // Conflicte pacient
        $rows = $wpdb->get_results($wpdb->prepare("SELECT appointment_time, duration FROM $table WHERE patient_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')", $patient_id, $appointment_date));
        foreach ($rows as $r) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . substr($r->appointment_time,0,5));
            if (!$exStart) { continue; }
            $exEnd = (clone $exStart)->modify('+' . (int)$r->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) { wp_send_json_error(__('Interval ocupat pentru pacient.', 'clinica')); }
        }

        // Inserție (service_id doar dacă există coloana)
        $columns = $wpdb->get_col("DESC $table", 0);
        $data = array(
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'duration' => $duration,
            'type' => $type,
            'status' => in_array($status, array('scheduled','confirmed','completed','cancelled','no_show'), true) ? $status : 'confirmed',
            'notes' => $notes,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        if (is_array($columns) && in_array('service_id', $columns, true)) {
            $data['service_id'] = $service_id;
        }
        $ok = $wpdb->insert($table, $data);
        if ($ok === false) { wp_send_json_error(__('Eroare la inserție.', 'clinica')); }

        // Audit
        $plugin_root = dirname(dirname(__FILE__));
        if (!file_exists($plugin_root . '/logs')) { @mkdir($plugin_root . '/logs', 0755, true); }
        $line = sprintf("[%s] CREATE_APPOINTMENT id=%d patient_id=%d doctor_id=%d date=%s time=%s duration=%d status=%s\n",
            current_time('mysql'), (int)$wpdb->insert_id, (int)$patient_id, (int)$doctor_id, $appointment_date, $appointment_time, (int)$duration, $status
        );
        @file_put_contents($plugin_root . '/logs/appointment-audit.log', $line, FILE_APPEND);

        // Notificare doar către pacient dacă email valid
        if ($send_email) {
            $patient = get_userdata($patient_id);
            $doctor = get_userdata($doctor_id);
            $data = array(
                'type' => $type,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'duration' => $duration,
                'patient_name' => $patient ? $patient->display_name : '',
                'patient_email' => $patient ? $patient->user_email : '',
                'doctor_name' => $doctor ? $doctor->display_name : ''
            );
            $this->send_appointment_notifications('created', $data);
        }

        wp_send_json_success(array('id' => (int)$wpdb->insert_id));
    }

    /**
     * Admin: obține datele programării pentru editare
     */
    public function get_appointment_data_for_edit($appointment_id) {
        global $wpdb;
        
        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, 
                   p.display_name as patient_name,
                   d.display_name as doctor_name,
                   s.name as service_name,
                   s.duration as service_duration
            FROM {$wpdb->prefix}clinica_appointments a
            LEFT JOIN {$wpdb->users} p ON a.patient_id = p.ID
            LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
            LEFT JOIN {$wpdb->prefix}clinica_services s ON (a.service_id = s.id OR (a.service_id IS NULL AND a.type = s.id))
            WHERE a.id = %d
        ", $appointment_id));
        
        if (!$appointment) {
            return false;
        }
        
        // Formatează datele pentru formular
        $formatted_data = array(
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'patient_name' => $appointment->patient_name,
            'service_id' => $appointment->service_id ?: $appointment->type,
            'service_name' => $appointment->service_name,
            'doctor_id' => $appointment->doctor_id,
            'doctor_name' => $appointment->doctor_name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'duration' => $appointment->service_duration ?: $appointment->duration,
            'status' => $appointment->status,
            'notes' => $appointment->notes
        );
        
        return $formatted_data;
    }

    /**
     * Admin: actualizare programare din pagina Programări
     */
    public function ajax_admin_update_appointment() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'clinica_admin_update_appointment_nonce')) {
            wp_send_json_error(__('Eroare de securitate (nonce).', 'clinica'));
        }
        if (!Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error(__('Nu aveți permisiunea pentru această acțiune.', 'clinica'));
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
        $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'confirmed';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if ($appointment_id <= 0 || $patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
            wp_send_json_error(__('Date incomplete.', 'clinica'));
        }

        // Normalizează ora (H:i)
        if (strlen($appointment_time) > 5) { $appointment_time = substr($appointment_time, 0, 5); }

        // Tipul din service_id
        $type = '';
        if ($service_id > 0) {
            $type = $this->get_service_name_by_id($service_id);
        }

        // Verifică conflict slot-uri (exclude programarea curentă)
        $slotStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . $appointment_time);
        if (!$slotStart) { wp_send_json_error(__('Dată/oră invalide.', 'clinica')); }
        $slotEnd = (clone $slotStart)->modify('+' . max(1,$duration) . ' minutes');

        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        
        // Conflicte medic (exclude programarea curentă)
        $rows = $wpdb->get_results($wpdb->prepare("SELECT appointment_time, duration FROM $table WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed') AND id != %d", $doctor_id, $appointment_date, $appointment_id));
        foreach ($rows as $r) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . substr($r->appointment_time,0,5));
            if (!$exStart) { continue; }
            $exEnd = (clone $exStart)->modify('+' . (int)$r->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) { wp_send_json_error(__('Interval ocupat pentru medic.', 'clinica')); }
        }
        
        // Conflicte pacient (exclude programarea curentă)
        $rows = $wpdb->get_results($wpdb->prepare("SELECT appointment_time, duration FROM $table WHERE patient_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed') AND id != %d", $patient_id, $appointment_date, $appointment_id));
        foreach ($rows as $r) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . substr($r->appointment_time,0,5));
            if (!$exStart) { continue; }
            $exEnd = (clone $exStart)->modify('+' . (int)$r->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) { wp_send_json_error(__('Interval ocupat pentru pacient.', 'clinica')); }
        }

        // Determină tipul utilizatorului curent
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $user_type = 'receptionist'; // default
        
        if (in_array('administrator', $user_roles)) {
            $user_type = 'admin';
        } elseif (in_array('manager', $user_roles)) {
            $user_type = 'manager';
        } elseif (in_array('doctor', $user_roles)) {
            $user_type = 'doctor';
        } elseif (in_array('assistant', $user_roles)) {
            $user_type = 'assistant';
        } elseif (in_array('receptionist', $user_roles)) {
            $user_type = 'receptionist';
        }
        
        // Citește programarea existentă (pt. decizii de status)
        $existing_appt = $wpdb->get_row($wpdb->prepare(
            "SELECT appointment_date, appointment_time, status, duration FROM $table WHERE id = %d",
            $appointment_id
        ));

        // Blochează actualizarea dacă programarea este deja completată
        if ($existing_appt && $existing_appt->status === 'completed') {
            wp_send_json_error(__('Programarea este completată și nu mai poate fi editată.', 'clinica'));
        }

        // Actualizare (service_id doar dacă există coloana)
        $columns = $wpdb->get_col("DESC $table", 0);
        $data = array(
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'duration' => $duration,
            'type' => $type,
            // Statusul va fi recalculat mai jos; setăm o valoare provizorie corectă
            'status' => in_array($status, array('scheduled','confirmed','completed','cancelled','no_show'), true) ? $status : 'confirmed',
            'notes' => $notes,
            'updated_at' => current_time('mysql'),
            'last_edited_by_type' => $user_type,
            'last_edited_by_user_id' => get_current_user_id(),
            'last_edited_at' => current_time('mysql')
        );
        if (is_array($columns) && in_array('service_id', $columns, true)) {
            $data['service_id'] = $service_id;
        }

        // Recalculează statusul conform regulilor cerute
        $nowMysql = current_time('mysql');
        $newStartTs = strtotime($appointment_date . ' ' . $appointment_time);
        $apptDuration = max(1, (int)$duration);
        $newEndTs = $newStartTs ? strtotime("+{$apptDuration} minutes", $newStartTs) : false;
        $completedThresholdTs = $newEndTs ? strtotime('+30 minutes', $newEndTs) : false;

        // Reguli status
        $wasCancelled = $existing_appt && $existing_appt->status === 'cancelled';
        $timeChanged = !$existing_appt || ($existing_appt->appointment_date !== $appointment_date) || (substr((string)$existing_appt->appointment_time,0,5) !== $appointment_time);

        if ($wasCancelled && !$timeChanged) {
            // Dacă era anulată și NU s-a schimbat data/ora, păstrăm 'cancelled'
            $data['status'] = 'cancelled';
        } else {
            // Altfel aplicăm regula automată: viitor = confirmed, trecut+30min = completed
            $autoStatus = 'confirmed';
            if ($completedThresholdTs !== false && strtotime($nowMysql) > $completedThresholdTs) {
                $autoStatus = 'completed';
            }
            $data['status'] = $autoStatus;
        }
        
        $ok = $wpdb->update($table, $data, array('id' => $appointment_id));
        if ($ok === false) { wp_send_json_error(__('Eroare la actualizare.', 'clinica')); }
        
        // Re-evaluează după salvare (siguranță) – armonizează dacă între timp s-a schimbat timpul curent
        $this->check_and_update_appointment_status_after_edit($appointment_id, $appointment_date, $appointment_time);

        // Audit
        $plugin_root = dirname(dirname(__FILE__));
        if (!file_exists($plugin_root . '/logs')) { @mkdir($plugin_root . '/logs', 0755, true); }
        $line = sprintf("[%s] UPDATE_APPOINTMENT id=%d patient_id=%d doctor_id=%d date=%s time=%s duration=%d status=%s\n",
            current_time('mysql'), (int)$appointment_id, (int)$patient_id, (int)$doctor_id, $appointment_date, $appointment_time, (int)$duration, $status
        );
        @file_put_contents($plugin_root . '/logs/appointment-audit.log', $line, FILE_APPEND);

        wp_send_json_success(array('id' => (int)$appointment_id));
    }
    
    /**
     * Admin: transfer programare la alt doctor
     */
    public function ajax_admin_transfer_appointment() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'clinica_admin_transfer_appointment_nonce')) {
            wp_send_json_error(__('Eroare de securitate (nonce).', 'clinica'));
        }
        if (!Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error(__('Nu aveți permisiunea pentru această acțiune.', 'clinica'));
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $new_doctor_id = isset($_POST['new_doctor_id']) ? intval($_POST['new_doctor_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        $new_time = isset($_POST['new_time']) ? sanitize_text_field($_POST['new_time']) : '';
        $transfer_notes = isset($_POST['transfer_notes']) ? sanitize_textarea_field($_POST['transfer_notes']) : '';
        $send_email = isset($_POST['send_email']) ? (bool)$_POST['send_email'] : false;

        if ($appointment_id <= 0 || $new_doctor_id <= 0 || empty($new_date) || empty($new_time)) {
            wp_send_json_error(__('Date incomplete pentru transfer.', 'clinica'));
        }

        // Normalizează ora (H:i)
        if (strlen($new_time) > 5) { 
            $new_time = substr($new_time, 0, 5); 
        }

        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        
        // Obține programarea existentă
        $existing_appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $appointment_id
        ));
        
        if (!$existing_appointment) {
            wp_send_json_error(__('Programarea nu a fost găsită.', 'clinica'));
        }

        // Verifică dacă programarea poate fi transferată
        if (!in_array($existing_appointment->status, array('scheduled', 'confirmed'))) {
            wp_send_json_error(__('Doar programările programate sau confirmate pot fi transferate.', 'clinica'));
        }

        // Verifică dacă noul doctor este diferit de cel curent
        if ($existing_appointment->doctor_id == $new_doctor_id) {
            wp_send_json_error(__('Nu puteți transfera programarea la același doctor.', 'clinica'));
        }

        // Verifică dacă noul doctor oferă serviciul respectiv
        $service_id = $existing_appointment->service_id ?: $existing_appointment->type;
        if ($service_id > 0) {
            $doctor_services = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}clinica_doctor_services WHERE doctor_id = %d AND service_id = %d",
                $new_doctor_id, $service_id
            ));
            if (!$doctor_services) {
                wp_send_json_error(__('Noul doctor nu oferă acest serviciu.', 'clinica'));
            }
        }

        // Verifică disponibilitatea doctorului pentru data selectată
        if (!$this->is_doctor_available_on_date($new_doctor_id, $new_date, $service_id)) {
            wp_send_json_error(__('Noul doctor nu lucrează în această zi.', 'clinica'));
        }

        // Verifică conflicte pentru noul doctor
        $slotStart = DateTime::createFromFormat('Y-m-d H:i', $new_date . ' ' . $new_time);
        if (!$slotStart) { 
            wp_send_json_error(__('Dată/oră invalide.', 'clinica')); 
        }
        
        $duration = max(1, (int)$existing_appointment->duration);
        $slotEnd = (clone $slotStart)->modify('+' . $duration . ' minutes');

        // Conflicte medic nou
        $conflicts = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time, duration FROM $table WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed') AND id != %d", 
            $new_doctor_id, $new_date, $appointment_id
        ));
        
        foreach ($conflicts as $conflict) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $new_date . ' ' . substr($conflict->appointment_time, 0, 5));
            if (!$exStart) { continue; }
            $exEnd = (clone $exStart)->modify('+' . (int)$conflict->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) { 
                wp_send_json_error(__('Noul doctor are deja o programare în acest interval.', 'clinica')); 
            }
        }

        // Verifică conflicte pentru pacient (dacă s-a schimbat data)
        if ($existing_appointment->appointment_date !== $new_date) {
            $patient_conflicts = $wpdb->get_results($wpdb->prepare(
                "SELECT appointment_time, duration FROM $table WHERE patient_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed') AND id != %d", 
                $existing_appointment->patient_id, $new_date, $appointment_id
            ));
            
            foreach ($patient_conflicts as $conflict) {
                $exStart = DateTime::createFromFormat('Y-m-d H:i', $new_date . ' ' . substr($conflict->appointment_time, 0, 5));
                if (!$exStart) { continue; }
                $exEnd = (clone $exStart)->modify('+' . (int)$conflict->duration . ' minutes');
                if ($slotStart < $exEnd && $exStart < $slotEnd) { 
                    wp_send_json_error(__('Pacientul are deja o programare în acest interval.', 'clinica')); 
                }
            }
        }

        // Determină tipul utilizatorului curent
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $user_type = 'receptionist'; // default
        
        if (in_array('administrator', $user_roles)) {
            $user_type = 'admin';
        } elseif (in_array('manager', $user_roles)) {
            $user_type = 'manager';
        } elseif (in_array('doctor', $user_roles)) {
            $user_type = 'doctor';
        } elseif (in_array('assistant', $user_roles)) {
            $user_type = 'assistant';
        } elseif (in_array('receptionist', $user_roles)) {
            $user_type = 'receptionist';
        }

        // Pregătește datele pentru transfer
        $data = array(
            'doctor_id' => $new_doctor_id,
            'appointment_date' => $new_date,
            'appointment_time' => $new_time,
            'updated_at' => current_time('mysql'),
            'last_edited_by_type' => $user_type,
            'last_edited_by_user_id' => get_current_user_id(),
            'last_edited_at' => current_time('mysql')
        );

        // Adaugă notele de transfer la notele existente
        if (!empty($transfer_notes)) {
            $existing_notes = !empty($existing_appointment->notes) ? $existing_appointment->notes . "\n" : '';
            $data['notes'] = $existing_notes . "[TRANSFER] " . $transfer_notes;
        }

        // Recalculează statusul conform regulilor
        $nowMysql = current_time('mysql');
        $newStartTs = strtotime($new_date . ' ' . $new_time);
        $newEndTs = $newStartTs ? strtotime("+{$duration} minutes", $newStartTs) : false;
        $completedThresholdTs = $newEndTs ? strtotime('+30 minutes', $newEndTs) : false;

        // Aplică regula automată: viitor = confirmed, trecut+30min = completed
        $autoStatus = 'confirmed';
        if ($completedThresholdTs !== false && strtotime($nowMysql) > $completedThresholdTs) {
            $autoStatus = 'completed';
        }
        $data['status'] = $autoStatus;

        // Execută transferul
        $ok = $wpdb->update($table, $data, array('id' => $appointment_id));
        
        if ($ok === false) { 
            wp_send_json_error(__('Eroare la transferul programării.', 'clinica')); 
        }

        // Re-evaluează statusul după transfer
        $this->check_and_update_appointment_status_after_edit($appointment_id, $new_date, $new_time);

        // Audit trail pentru transfer
        $plugin_root = dirname(dirname(__FILE__));
        if (!file_exists($plugin_root . '/logs')) { 
            @mkdir($plugin_root . '/logs', 0755, true); 
        }
        
        $old_doctor_name = $this->get_doctor_name_by_id($existing_appointment->doctor_id);
        $new_doctor_name = $this->get_doctor_name_by_id($new_doctor_id);
        
        $line = sprintf(
            "[%s] TRANSFER_APPOINTMENT id=%d patient_id=%d from_doctor=%d(%s) to_doctor=%d(%s) old_date=%s old_time=%s new_date=%s new_time=%s duration=%d status=%s notes=%s\n",
            current_time('mysql'), 
            (int)$appointment_id, 
            (int)$existing_appointment->patient_id, 
            (int)$existing_appointment->doctor_id, 
            $old_doctor_name,
            (int)$new_doctor_id, 
            $new_doctor_name,
            $existing_appointment->appointment_date, 
            $existing_appointment->appointment_time,
            $new_date, 
            $new_time, 
            (int)$duration, 
            $autoStatus,
            $transfer_notes
        );
        @file_put_contents($plugin_root . '/logs/appointment-audit.log', $line, FILE_APPEND);

        // Trimite email de notificare dacă este solicitat
        if ($send_email) {
            $this->send_transfer_notification_email($existing_appointment, $new_doctor_id, $new_date, $new_time, $transfer_notes);
        }

        wp_send_json_success(array(
            'id' => (int)$appointment_id,
            'message' => __('Programarea a fost transferată cu succes.', 'clinica'),
            'new_doctor' => $new_doctor_name,
            'new_date' => $new_date,
            'new_time' => $new_time
        ));
    }
    
    /**
     * Shortcode pentru dashboard pacient
     */
    public function render_dashboard_shortcode($atts) {
        // Verifică dacă utilizatorul este logat
        if (!is_user_logged_in()) {
            return $this->render_login_redirect();
        }
        
        $current_user = wp_get_current_user();
        
        // Verifică dacă utilizatorul este pacient sau administrator
        $user_roles = $current_user->roles;
        if (!in_array('clinica_patient', $user_roles) && !in_array('administrator', $user_roles)) {
            return $this->render_access_denied();
        }
        
        // Obține datele pacientului
        $patient_data = $this->get_patient_data($current_user->ID);
        
        if (!$patient_data) {
            return $this->render_error_message('Nu s-au găsit datele pacientului.');
        }
        
        return $this->render_dashboard($patient_data);
    }
    
    /**
     * Render redirect la login
     */
    private function render_login_redirect() {
        return '<div class="clinica-dashboard-login-required">
            <div class="login-message">
                <h3>Autentificare necesară</h3>
                <p>Pentru a accesa dashboard-ul pacientului, trebuie să vă autentificați.</p>
                <a href="' . home_url('/login/') . '" class="button button-primary">Autentificare</a>
            </div>
        </div>';
    }
    
    /**
     * Render mesaj acces refuzat
     */
    private function render_access_denied() {
        return '<div class="clinica-dashboard-access-denied">
            <div class="access-message">
                <h3>Acces refuzat</h3>
                <p>Nu aveți permisiunea de a accesa dashboard-ul pacientului.</p>
                <a href="' . home_url() . '" class="button">Înapoi la pagina principală</a>
            </div>
        </div>';
    }
    
    /**
     * Render mesaj de eroare
     */
    private function render_error_message($message) {
        return '<div class="clinica-dashboard-error">
            <div class="error-message">
                <h3>Eroare</h3>
                <p>' . esc_html($message) . '</p>
                <a href="' . home_url() . '" class="button">Înapoi la pagina principală</a>
            </div>
        </div>';
    }
    
    /**
     * Obține datele pacientului
     */
    public function get_patient_data($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Obține datele din tabela pacienți împreună cu numele din usermeta
        $patient_data = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, u.user_email, u.display_name,
             um1.meta_value as first_name, um2.meta_value as last_name
             FROM $table_name p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
             WHERE p.user_id = %d",
            $user_id
        ));
        
        if (!$patient_data) {
            return false;
        }
        
        // Adaugă informațiile suplimentare din wp_users
        $user_data = get_userdata($user_id);
        if ($user_data) {
            $patient_data->user_registered = $user_data->user_registered;
        }
        
        // Adaugă notele din user meta
        $patient_data->notes = get_user_meta($user_id, '_clinica_notes', true);
        
        return $patient_data;
    }
    
    /**
     * Render dashboard-ul principal
     */
    private function render_dashboard($patient_data) {
        ob_start();
        ?>
        <div class="clinica-patient-dashboard" data-patient-id="<?php echo esc_attr($patient_data->user_id); ?>">
            <!-- Header Dashboard -->
            <div class="dashboard-header">
                <div class="patient-info-header">
                    <div class="patient-avatar">
                        <div class="avatar-placeholder">
                            <?php 
                            $full_name = trim($patient_data->first_name . ' ' . $patient_data->last_name);
                            if (!empty($full_name)) {
                                echo strtoupper(substr($patient_data->first_name, 0, 1) . substr($patient_data->last_name, 0, 1));
                            } else {
                                echo strtoupper(substr($patient_data->display_name, 0, 2));
                            }
                            ?>
                        </div>
                    </div>
                    <div class="patient-details">
                        <h2><?php 
                            $full_name = trim($patient_data->first_name . ' ' . $patient_data->last_name);
                            echo esc_html(!empty($full_name) ? $full_name : $patient_data->display_name); 
                        ?></h2>
                        <p class="patient-cnp">CNP: <?php echo esc_html($patient_data->cnp); ?></p>
                        <p class="patient-email"><?php echo esc_html($patient_data->user_email); ?></p>
                    </div>
                </div>
                <div class="dashboard-actions">
                    <button type="button" class="button" id="edit-profile-btn">Editează Profilul</button>
                    <!-- Butonul de deconectare este ascuns aici deoarece există deja în meniul principal -->
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="dashboard-tabs">
                <button class="tab-button active" data-tab="overview">Prezentare generală</button>
                <button class="tab-button" data-tab="appointments">Programări</button>
                <button class="tab-button" data-tab="family">Membrii de familie</button>
                <button class="tab-button" data-tab="messages">Mesaje</button>
            </div>
            
            <!-- Tab Content -->
            <div class="dashboard-content">
                <!-- Tab Overview -->
                <div class="tab-content active" id="overview">
                    <div class="dashboard-grid">
                        <!-- Informații personale -->
                        <div class="dashboard-card">
                            <h3>Informații personale</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Nume complet:</label>
                                    <span><?php 
                                        $full_name = trim($patient_data->first_name . ' ' . $patient_data->last_name);
                                        echo esc_html(!empty($full_name) ? $full_name : $patient_data->display_name); 
                                    ?></span>
                                </div>
                                <div class="info-item">
                                    <label>CNP:</label>
                                    <span><?php echo esc_html($patient_data->cnp); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Data nașterii:</label>
                                    <span><?php echo esc_html($this->format_date($patient_data->birth_date)); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Vârsta:</label>
                                    <span><?php echo esc_html($this->calculate_age($patient_data->birth_date)); ?> ani</span>
                                </div>
                                <div class="info-item">
                                    <label>Sex:</label>
                                    <span><?php echo esc_html($this->get_gender_label($patient_data->gender)); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Telefon principal:</label>
                                    <span><?php echo esc_html($patient_data->phone_primary); ?></span>
                                </div>
                                <?php if (!empty($patient_data->phone_secondary)): ?>
                                <div class="info-item">
                                    <label>Telefon secundar:</label>
                                    <span><?php echo esc_html($patient_data->phone_secondary); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span><?php echo esc_html($patient_data->user_email); ?></span>
                                </div>
                                <?php if (!empty($patient_data->address)): ?>
                                <div class="info-item">
                                    <label>Adresă:</label>
                                    <span><?php echo esc_html($patient_data->address); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Informații medicale - ASCUNS TEMPORAR -->
                        <!--
                        <div class="dashboard-card">
                            <h3>Informații medicale</h3>
                            <div class="info-grid">
                                <?php if (!empty($patient_data->blood_type)): ?>
                                <div class="info-item">
                                    <label>Grupa sanguină:</label>
                                    <span><?php echo esc_html($patient_data->blood_type); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient_data->allergies)): ?>
                                <div class="info-item">
                                    <label>Alergii:</label>
                                    <span><?php echo esc_html($patient_data->allergies); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient_data->emergency_contact)): ?>
                                <div class="info-item">
                                    <label>Contact urgență:</label>
                                    <span><?php echo esc_html($patient_data->emergency_contact); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        -->
                        
                        <!-- Statistici rapide -->
                        <div class="dashboard-card">
                            <h3 style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                                <span>Statistici rapide</span>
                                <span class="stats-period" style="font-weight:500; color:#6c757d; display:flex; align-items:center; gap:6px;">
                                    <label for="stats-period" style="font-size:12px;">Perioadă:</label>
                                    <select id="stats-period" style="padding:4px 8px; font-size:12px;">
                                        <option value="30d">30 zile</option>
                                        <option value="3m">3 luni</option>
                                        <option value="6m" selected>6 luni</option>
                                        <option value="12m">12 luni</option>
                                        <option value="all">Total</option>
                                    </select>
                                </span>
                            </h3>
                            <div class="info-grid" id="quick-stats-list">
                                <div class="info-item">
                                    <label>Programări totale:</label>
                                    <span id="total-appointments">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Programări viitoare:</label>
                                    <span id="upcoming-appointments">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Programări anulate:</label>
                                    <span id="cancelled-appointments">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Programări completate:</label>
                                    <span id="completed-appointments">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Neprezentări:</label>
                                    <span id="no-show-appointments">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Mesaje necitite:</label>
                                    <span id="unread-messages">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ultimele activități -->
                        <div class="dashboard-card">
                            <h3>Ultimele activități</h3>
                            <div class="activity-list" id="recent-activities">
                                <div class="loading">Se încarcă...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Appointments -->
                <div class="tab-content" id="appointments">
                    <div class="appointments-container">
                        <div class="appointments-header">
                            <h3>Programările mele</h3>
                            <div class="appointments-filters">
                                <select id="appointment-filter">
                                    <option value="all">Toate programările</option>
                                    <option value="upcoming">Viitoare</option>
                                    <option value="past">Trecute</option>
                                    <option value="cancelled">Anulate</option>
                                </select>
                                <button type="button" class="button button-primary" id="new-appointment-btn">Programare nouă</button>
                            </div>
                        </div>
                        
                        <div id="new-appointment-form" class="appointment-form" style="display:none;">
                            <div class="form-2col">
                                <div class="form-col left">
                                    <div class="form-row">
                                        <label for="booking-patient">Pentru</label>
                                        <select id="booking-patient"></select>
                                    </div>
                                    <div class="form-row">
                                        <label for="booking-service">Serviciu</label>
                                        <select id="booking-service">
                                            <option value="">Selectează serviciu</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label for="booking-doctor">Doctor preferat</label>
                                        <select id="booking-doctor">
                                            <option value="">Selectează doctor</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <div id="booking-summary" class="booking-summary"></div>
                                    </div>
                                    <div class="form-row">
                                        <label for="booking-notes">Observații (opțional)</label>
                                        <textarea id="booking-notes" rows="3" style="resize:vertical;"></textarea>
                                    </div>
                                </div>
                                <div class="form-col right">
                                    <input type="hidden" id="booking-date" value="" />
                                    <input type="hidden" id="booking-slot" value="" />
                                    <div class="form-row">
                                        <label>Zi disponibilă</label>
                                        <div id="booking-calendar"><input type="text" id="booking-date-picker" /></div>
                                    </div>
                                    <div class="form-row">
                                        <label>Interval orar</label>
                                        <div id="booking-slots" class="slots-grid">
                                            <!-- slot buttons rendered here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="button" id="cancel-appointment-form">Renunță</button>
                                <button type="button" class="button button-primary" id="create-appointment-btn" disabled>Finalizează programarea</button>
                            </div>
                            <div class="form-hint" id="booking-autofill"></div>
                        </div>
                        
                        <div class="appointments-list" id="appointments-list">
                            <div class="loading">Se încarcă programările...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Medical - ASCUNS TEMPORAR -->
                <!--
                <div class="tab-content" id="medical">
                    <div class="medical-container">
                        <div class="medical-header">
                            <h3>Informații medicale</h3>
                        </div>
                        <div class="medical-content">
                            <?php if (!empty($patient_data->medical_history)): ?>
                            <div class="medical-section">
                                <h4>Istoric medical</h4>
                                <div class="medical-text"><?php echo nl2br(esc_html($patient_data->medical_history)); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="medical-section">
                                <h4>Rezultate analize</h4>
                                <div class="medical-results" id="medical-results">
                                    <div class="loading">Se încarcă rezultatele...</div>
                                </div>
                            </div>
                            
                            <div class="medical-section">
                                <h4>Prescripții</h4>
                                <div class="prescriptions" id="prescriptions">
                                    <div class="loading">Se încarcă prescripțiile...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                -->
                
                <!-- Tab Family -->
                <div class="tab-content" id="family">
                    <div class="family-container">
                        <div class="family-header">
                            <h3>Membrii de familie</h3>
                            <button type="button" class="button" id="add-family-member-btn">Adaugă membru</button>
                        </div>
                        <div class="family-info">
                            <div class="family-status" id="family-status">
                                <div class="loading">Se încarcă informațiile despre familie...</div>
                            </div>
                        </div>
                        <div class="family-members" id="family-members">
                            <div class="loading">Se încarcă membrii familiei...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Messages -->
                <div class="tab-content" id="messages">
                    <div class="messages-container">
                        <div class="messages-header">
                            <h3>Mesaje</h3>
                            <button type="button" class="button" id="new-message-btn">Mesaj nou</button>
                        </div>
                        <div class="messages-list" id="messages-list">
                            <div class="loading">Se încarcă mesajele...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div id="clinica-dashboard-messages"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Revert orice forțare de 1600px – folosim 1200px (CSS handlează)
            (function(){
                var $dash = $('.clinica-patient-dashboard');
                if (!$dash.length) return;
                $dash.css({ width: '', 'max-width': '', 'margin-left':'', 'margin-right':'' });
                var wrappers = ['.page-content', '.entry-content', '.content-area', '.site-main'];
                wrappers.forEach(function(sel){
                    var $el = $dash.closest(sel);
                    if ($el && $el.length) {
                        $el.attr('style','');
                    }
                });
            })();
            var dashboard = $('.clinica-patient-dashboard');
            var patientId = dashboard.data('patient-id');
            
            // Încarcă serviciile la inițializare
            loadServices();
            
            // Inițializează calendarul cu zilele disponibile pentru serviciul și doctorul selectat
            setTimeout(function() {
                var selectedService = $('#booking-service').val();
                var selectedDoctor = $('#booking-doctor').val();
                if (selectedService && selectedDoctor) {
                    var serviceId = parseInt(selectedService, 10) || 0;
                    loadAvailableDays(selectedDoctor, serviceId);
                }
            }, 100);
            
            // Tab navigation
            $('.tab-button').on('click', function() {
                var tabId = $(this).data('tab');
                
                // Update active tab
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Update active content
                $('.tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
                
                // Load content based on tab
                switch(tabId) {
                    case 'appointments':
                        // Golește imediat și ascunde pentru a evita orice "flash" de conținut vechi
                        $('#appointments-list')
                            .css('visibility','hidden')
                            .html('<div class="loading">Se încarcă programările...</div>');
                        loadAppointments();
                        break;
                    case 'family':
                        loadFamilyData();
                        break;
                    case 'medical':
                        loadMedicalData();
                        break;
                    case 'messages':
                        loadMessages();
                        break;
                }
            });
            
            // Load initial data
            loadDashboardStats();
            loadRecentActivities();
            
            // Load appointments
            function loadAppointments() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_get_appointments',
                        patient_id: patientId,
                        filter: $('#appointment-filter').val(),
                        nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#appointments-list').html(response.data.html).css('visibility','visible');
                        } else {
                            $('#appointments-list').html('<div class="error">' + response.data + '</div>').css('visibility','visible');
                        }
                    },
                    error: function() {
                        $('#appointments-list').html('<div class="error">Eroare la încărcarea programărilor</div>').css('visibility','visible');
                    }
                });
            }
            
            // Filter change reloads appointments
            $('#appointment-filter').on('change', function() {
                loadAppointments();
            });
            
            // Load medical data
            function loadMedicalData() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_get_medical_history',
                        patient_id: patientId,
                        nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#medical-results').html(response.data.results);
                            $('#prescriptions').html(response.data.prescriptions);
                        } else {
                            $('#medical-results').html('<div class="error">' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $('#medical-results').html('<div class="error">Eroare la încărcarea datelor medicale</div>');
                    }
                });
            }
            
            // Load family data
            function loadFamilyData() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_get_patient_family',
                        patient_id: patientId,
                        nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#family-status').html(response.data.status);
                            $('#family-members').html(response.data.members);
                        } else {
                            $('#family-status').html('<div class="error">' + response.data + '</div>');
                            $('#family-members').html('<div class="error">Eroare la încărcarea membrilor familiei</div>');
                        }
                    },
                    error: function() {
                        $('#family-status').html('<div class="error">Eroare la încărcarea datelor familiei</div>');
                        $('#family-members').html('<div class="error">Eroare la încărcarea membrilor familiei</div>');
                    }
                });
            }
            
            // Load messages
            function loadMessages() {
                $('#messages-list').html('<div class="loading">Se încarcă mesajele...</div>');
                // TODO: Implement messages loading
            }
            
            // Helper function to reset calendar
            function resetCalendar() {
                var input = $('#booking-date-picker');
                if (input && input[0] && input[0]._flatpickr) {
                    try {
                        input[0]._flatpickr.destroy();
                        input[0]._flatpickr = null;
                    } catch(e) {
                        // Silent fail
                    }
                }
                var container = document.getElementById('booking-calendar');
                if (container) {
                    container.innerHTML = '';
                }
            }
            
            // Load dashboard stats
            function loadDashboardStats() {
                var period = $('#stats-period').val() || '6m';
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_get_dashboard_stats',
                        patient_id: patientId,
                        period: period,
                        debug: 1,
                        nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
                    },
                    success: function(resp){
                        if (resp && resp.success && resp.data){
                            console.log('Dashboard stats', resp.data);
                            var s = resp.data;
                            $('#total-appointments').text(s.total_appointments ?? '0');
                            $('#upcoming-appointments').text(s.upcoming_appointments ?? '0');
                            $('#unread-messages').text(s.unread_messages ?? '0');
                            $('#cancelled-appointments').text(s.range_cancelled ?? '0');
                            $('#completed-appointments').text(s.range_completed ?? '0');
                            $('#no-show-appointments').text(s.range_no_show ?? '0');
                            if (s.debug){
                                console.log('Distinct statuses:', s.debug.distinct_statuses);
                                console.log('Range breakdown:', s.debug.range_breakdown);
                                console.log('Sample cancelled in range:', s.debug.sample_cancelled);
                            }
                        }
                    }
                });
            }

            // Reload pe schimbarea perioadei
            $(document).on('change', '#stats-period', function(){
                loadDashboardStats();
            });
            
            // Load recent activities
            function loadRecentActivities() {
                $('#recent-activities').html('<div class="no-activities">Nu există activități recente</div>');
            }
            
            // Edit profile button
            $('#edit-profile-btn').on('click', function() {
                // TODO: Implement edit profile modal
                alert('Funcționalitatea de editare profil va fi implementată în curând.');
            });
            
            // Add family member button
            $('#add-family-member-btn').on('click', function() {
                // TODO: Implement add family member modal
                alert('Funcționalitatea de adăugare membru familie va fi implementată în curând.');
            });
            
            // New message button
            $('#new-message-btn').on('click', function() {
                // TODO: Implement new message modal
                alert('Funcționalitatea de mesaje va fi implementată în curând.');
            });

            // New Appointment UI
            $('#new-appointment-btn').on('click', function() {
                $('#new-appointment-form').slideDown(150);
                loadBookingPatients();
                loadServices();
                $('#create-appointment-btn').prop('disabled', true);
                
                // Așteaptă ca formularul să fie vizibil înainte de a încerca să inițializezi calendarul
                setTimeout(function() {
                    console.log('=== FORM SHOULD BE VISIBLE NOW ===');
                    var appointmentForm = document.getElementById('new-appointment-form');
                    console.log('Appointment form display after slideDown:', appointmentForm ? window.getComputedStyle(appointmentForm).display : 'N/A');
                    
                    var input = document.getElementById('booking-date-picker');
                    console.log('Input element after slideDown:', input);
                }, 200);
            });
            $('#cancel-appointment-form').on('click', function(){
                $('#new-appointment-form').slideUp(150);
            });

            $('#booking-service').on('change', function(){
                // dacă există deja doctor și dată selectate, notăm pentru posibil reload
                var prevDoctor = $('#booking-doctor').val();
                var prevDate = $('#booking-date').val();
                $('#booking-doctor').html('<option value="">Selectează doctor</option>');
                $('#booking-date').html('<option value="">Selectează zi</option>');
                $('#booking-slot').html('<option value="">Selectează interval</option>');
                // goliți vizual grila de sloturi
                $('#booking-slots').empty().append('<div class="slot-btn disabled">-</div>');
                
                // Resetează calendarul complet
                resetCalendar();
                
                if ($(this).val()) { 
                    loadDoctors($(this).val(), prevDoctor); 
                    // loadDoctors va apela loadAvailableDays dacă prevDoctor există
                } else {
                    // Dacă nu este selectat niciun serviciu, resetează calendarul
                    resetCalendar();
                }
                // Nu mai încărca sloturile aici - se vor încărca automat când calendarul se actualizează
                updateCreateButtonState();
            });
            $('#booking-doctor').on('change', function(){
                $('#booking-date').html('<option value="">Selectează zi</option>');
                $('#booking-slot').html('<option value="">Selectează interval</option>');
                
                // Resetează calendarul complet
                resetCalendar();
                
                if ($(this).val()) { 
                    var serviceId = parseInt($('#booking-service').val(), 10) || 0;
                    loadAvailableDays($(this).val(), serviceId); 
                } else {
                    // Dacă nu este selectat niciun doctor, resetează calendarul
                    resetCalendar();
                }
                updateCreateButtonState();
            });
            $('#booking-date').on('change', function(){
                $('#booking-slot').html('<option value=\"\">Selectează interval</option>');
                if ($(this).val()) {
                    var selectedServiceId = parseInt($('#booking-service').val(), 10);
                    var services = $('#booking-service').data('services') || [];
                    var duration = parseInt($('#booking-service option:selected').data('duration'), 10) || <?php echo (int) Clinica_Settings::get_instance()->get('appointment_duration', 30); ?>;
                    var match = services.find(function(s){ return parseInt(s.id,10) === selectedServiceId; });
                    if (match && match.duration) { duration = match.duration; }
                    loadSlots($('#booking-doctor').val(), $(this).val(), duration);
                }
                updateCreateButtonState();
            });
            $('#booking-slot, #booking-patient').on('change', function(){
                updateCreateButtonState();
                updateAutofillHint();
            });

            $('#create-appointment-btn').on('click', function(){
                createAppointment();
            });

            // Helper: format ISO date (YYYY-MM-DD) to Romanian DD.MM.YYYY
            function formatDateRo(iso){
                if (!iso || typeof iso !== 'string') return iso || '';
                var p = iso.split('-');
                if (p.length === 3) { return p[2] + '.' + p[1] + '.' + p[0]; }
                return iso;
            }

            function updateCreateButtonState(){
                var enabled = $('#booking-patient').val() && $('#booking-service').val() && $('#booking-doctor').val() && $('#booking-date').val() && $('#booking-slot').val();
                $('#create-appointment-btn').prop('disabled', !enabled);
                updateSummary();
            }

            function updateAutofillHint(){
                var patientId = $('#booking-patient').val();
                if (!patientId) { $('#booking-autofill').text(''); return; }
                $('#booking-autofill').text('Datele vor fi completate automat pentru utilizatorul selectat.');
            }

            function loadBookingPatients(){
                // Eu + membrii familiei
                var options = '<option value="'+patientId+'">Eu</option>';
                // Încercăm să citim membrii familiei (dacă există Family Manager pe backend)
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_get_patient_family', patient_id: patientId, nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        if (resp && resp.success && resp.data && resp.data.members) {
                            // extrage textul și caută cardurile pentru nume dacă se dorește mai târziu
                        }
                    }
                });
                $('#booking-patient').html(options);
            }

            function loadServices(){
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_get_services_catalog', nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        var html = '<option value="">Selectează serviciu</option>';
                        if (resp && resp.success && Array.isArray(resp.data)) {
                            $('#booking-service').data('services', resp.data);
                            resp.data.forEach(function(s){ html += '<option value="'+s.id+'" data-duration="'+s.duration+'">'+s.name+'</option>'; });
                        } else {
                            html += '<option value="Consultatie" data-duration="30">Consultație</option>';
                        }
                        $('#booking-service').html(html);
                    },
                    error: function(){
                        $('#booking-service').html('<option value="Consultatie" data-duration="30">Consultație</option>');
                    }
                });
            }

            function loadDoctors(service, keepDoctorId){
                // TODO: filtrare doctori după serviciu; momentan toți doctorii
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_get_doctors_for_service', service: service, nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        var html = '<option value="">Selectează doctor</option>';
                        if (resp && resp.success && Array.isArray(resp.data)) {
                            resp.data.forEach(function(d){ html += '<option value="'+d.id+'">'+d.name+'</option>'; });
                        }
                        $('#booking-doctor').html(html);
                        if (keepDoctorId && $('#booking-doctor option[value="'+keepDoctorId+'"]').length) {
                            $('#booking-doctor').val(keepDoctorId);
                            // Dacă păstrăm doctorul, actualizează calendarul cu noul serviciu
                            var serviceId = parseInt(service, 10) || 0;
                            loadAvailableDays(keepDoctorId, serviceId);
                        }
                    },
                    error: function(){
                        $('#booking-doctor').html('<option value="">Selectează doctor</option>');
                    }
                });
            }

            function loadAvailableDays(doctorId, serviceId){
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_get_doctor_availability_days', doctor_id: doctorId, service_id: serviceId || 0, nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        var days = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                        renderCalendar(days);
                    },
                    error: function(){ renderCalendar([]); }
                });
            }

            function renderCalendar(days){
                // Verifică dacă formularul de programare este vizibil
                var appointmentForm = document.getElementById('new-appointment-form');
                var isFormVisible = appointmentForm && window.getComputedStyle(appointmentForm).display !== 'none';
                
                if (!isFormVisible) {
                    return;
                }
                
                // Dacă nu există zile disponibile, nu afișa calendarul și ascunde sloturile
                if (!days || days.length === 0) {
                    var container = document.getElementById('booking-calendar');
                    if (container) {
                        container.innerHTML = '<div class="no-availability">Nu există zile disponibile pentru acest doctor și serviciu</div>';
                    }
                    
                    // Ascunde și secțiunea de sloturi
                    var slotsContainer = document.getElementById('booking-slots');
                    if (slotsContainer) {
                        slotsContainer.innerHTML = '<div class="slot-btn disabled">-</div>';
                    }
                    
                    // Resetează câmpurile
                    $('#booking-date').val('');
                    $('#booking-slot').html('<option value="">Selectează interval</option>');
                    updateCreateButtonState();
                    return;
                }
                
                // Așteaptă puțin ca DOM-ul să se actualizeze
                setTimeout(function() {
                    initFPWithRetry();
                }, 50);
                
                var input = $('#booking-date-picker');
                var available = {};
                (days||[]).forEach(function(rec){ 
                    var d = (typeof rec==='string')?rec:rec.date; 
                    available[d] = rec; 
                    console.log('Adding to available:', d, '=', rec);
                });
                console.log('Available dates:', available);
                console.log('Available dates keys:', Object.keys(available));
                console.log('Available dates values:', Object.values(available));
                // load Flatpickr dynamically
                function loadScript(src, cb){ var s=document.createElement('script'); s.src=src; s.onload=cb; document.head.appendChild(s); }
                function loadCSS(href){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l); }
                
                function initFPWithRetry() {
                    var el = document.getElementById('booking-date-picker');
                    
                    if (!el) {
                        // Încearcă să creeze elementul dacă nu există
                        var container = document.getElementById('booking-calendar');
                        if (container) {
                            var input = document.createElement('input');
                            input.type = 'text';
                            input.id = 'booking-date-picker';
                            input.style.display = 'none';
                            container.appendChild(input);
                            el = input;
                        }
                    }
                    
                    if (el) {
                        initFP();
                    }
                }
                function initFP(){
                    var el = document.getElementById('booking-date-picker');
                    if (!el) {
                        var elJQuery = $('#booking-date-picker');
                        if (elJQuery.length > 0) {
                            el = elJQuery[0];
                        } else {
                            return;
                        }
                    }
                    el.readOnly = true;
                    // curăță containerul și distruge instanța existentă pentru a evita 2 calendare
                    var container = document.getElementById('booking-calendar');
                    if (!container) {
                        var containerJQuery = $('#booking-calendar');
                        if (containerJQuery.length > 0) {
                            container = containerJQuery[0];
                        } else {
                            return;
                        }
                    }
                    
                    if (container) { 
                        container.innerHTML = '';
                    } else {
                        return;
                    }
                    if (el._flatpickr && typeof el._flatpickr.destroy === 'function') {
                        try { el._flatpickr.destroy(); } catch(e){}
                    }
                    var keys = Object.keys(available);
                    var minDate = keys.length ? keys[0] : 'today';
                    var maxDate = null; // NU limitez maxDate - las calendarul să afișeze toate zilele
                    
                    // Setează luna corectă bazată pe prima zi disponibilă
                    var defaultDate = keys.length ? keys[0] : 'today';
                    
                    // Verifică dacă Flatpickr este disponibil
                    if (typeof flatpickr === 'undefined') {
                        return;
                    }
                    function computeMonths(){
                        try {
                            var cw = document.getElementById('booking-calendar').clientWidth || 600;
                            return cw > 900 ? 3 : (cw > 650 ? 2 : 1);
                        } catch(e){ return 1; }
                    }
                    el._flatpickr = flatpickr(el, {
                        locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.ro) ? 'ro' : undefined,
                        dateFormat: 'Y-m-d',
                        minDate: minDate,
                        maxDate: maxDate,
                        defaultDate: defaultDate,
                        inline: true,
                        allowInput: false,
                        appendTo: document.getElementById('booking-calendar'),
                        showMonths: 1,
                        disable: [function(date){
                            // disable weekend și date indisponibile sau full
                            if (date.getDay() === 0 || date.getDay() === 6) return true;
                            // Folosește format local pentru a evita problemele de timezone
                            var s = date.getFullYear() + '-' + 
                                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(date.getDate()).padStart(2, '0');
                            var isAvailable = available[s] && !available[s].full;
                            console.log('Checking date:', s, 'available[s]:', available[s], 'isAvailable:', isAvailable);
                            if (!isAvailable) {
                                console.log('Disabling date:', s, 'available:', available[s]);
                            }
                            return !isAvailable;
                        }],
                        onDayCreate: function(dObj, dStr, fp, dayElem){
                            if (!dayElem || !dayElem.dateObj) return;
                            // Folosește format local pentru a evita problemele de timezone
                            var s = dayElem.dateObj.getFullYear() + '-' + 
                                   String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(dayElem.dateObj.getDate()).padStart(2, '0');
                            
                            // Verifică sărbătorile legale românești
                            if (window.ClinicaRomanianHolidays && window.ClinicaRomanianHolidays.isHoliday) {
                                if (window.ClinicaRomanianHolidays.isHoliday(s)) {
                                    dayElem.classList.add('legal-holiday');
                                    dayElem.title = window.ClinicaRomanianHolidays.getHolidayName ? window.ClinicaRomanianHolidays.getHolidayName(s) : 'Sărbătoare';
                                }
                            }
                            
                            if (dayElem.dateObj.getDay()===0 || dayElem.dateObj.getDay()===6){ dayElem.classList.add('weekend'); }
                            if (available[s] && available[s].full){ dayElem.classList.add('full'); dayElem.title = 'Zi plină'; }
                        },
                        onChange: function(selectedDates, dateStr, fp){
                            if (!dateStr) return;
                            $('#booking-date').val(dateStr);
                            var selectedServiceId = parseInt($('#booking-service').val(),10);
                            var services = $('#booking-service').data('services') || [];
                            var duration = parseInt($('#booking-service option:selected').data('duration'), 10) || <?php echo (int) Clinica_Settings::get_instance()->get('appointment_duration', 30); ?>;
                            var match = services.find(function(s){ return parseInt(s.id,10) === selectedServiceId; });
                            if (match && match.duration) { duration = match.duration; }
                            
                            loadSlots($('#booking-doctor').val(), dateStr, duration);
                            updateCreateButtonState();
                        }
                    });
                    // setează implicit prima zi disponibilă
                    if (keys.length && el._flatpickr) { 
                        el._flatpickr.setDate(keys[0], true); 
                    }
                }
                if (!window.flatpickr){
                    loadCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
                    loadCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css');
                    loadScript('https://cdn.jsdelivr.net/npm/flatpickr', function(){
                        loadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js', function(){
                            loadScript('<?php echo CLINICA_PLUGIN_URL; ?>assets/js/romanian-holidays.js', function(){
                                initFP();
                            });
                        });
                    });
                } else {
                    initFP();
                }
            }

            function loadSlots(doctorId, day, duration, serviceId){
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_get_doctor_slots', doctor_id: doctorId, day: day, duration: duration, service_id: serviceId || 0, nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        var slots = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                        renderSlots(slots);
                    },
                    error: function(xhr, status, error){
                        renderSlots([]);
                    }
                });
            }

            function renderSlots(slots){
                var grid = $('#booking-slots');
                grid.empty();
                slots.forEach(function(s){
                    var b = $('<div/>').addClass('slot-btn').text(s).attr('data-slot', s);
                    b.on('click', function(){
                        $('.slot-btn').removeClass('selected');
                        $(this).addClass('selected');
                        $('#booking-slot').val(s);
                        updateCreateButtonState();
                    });
                    grid.append(b);
                });
                if (slots.length === 0){ grid.append('<div class="slot-btn disabled">-</div>'); }
            }

            function createAppointment(){
                var payload = {
                    action: 'clinica_create_own_appointment',
                    nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>',
                    patient_id: $('#booking-patient').val() || patientId,
                    doctor_id: $('#booking-doctor').val(),
                    appointment_date: $('#booking-date').val(),
                    appointment_time: ($('#booking-slot').val() || '').split(' - ')[0],
                    duration: (function(){
                        var selectedServiceId = parseInt($('#booking-service').val(),10);
                        var services = $('#booking-service').data('services') || [];
                        var d = parseInt($('#booking-service option:selected').data('duration'), 10) || <?php echo (int) Clinica_Settings::get_instance()->get('appointment_duration', 30); ?>;
                        var match = services.find(function(s){ return parseInt(s.id,10) === selectedServiceId; });
                        if (match && match.duration) { d = match.duration; }
                        return d;
                    })(),
                    type: (function(){
                        var selectedServiceId = parseInt($('#booking-service').val(),10);
                        var services = $('#booking-service').data('services') || [];
                        var match = services.find(function(s){ return parseInt(s.id,10) === selectedServiceId; });
                        return match ? match.name : '';
                    })(),
                    service_id: (function(){
                        var id = parseInt($('#booking-service').val(),10);
                        return isNaN(id) ? 0 : id;
                    })(),
                    notes: ($('#booking-notes').val() || '').trim()
                };
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: payload,
                    success: function(resp){
                        if (resp && resp.success) {
                            alert('Programarea a fost creată cu succes');
                            $('#new-appointment-form').slideUp(150);
                            loadAppointments();
                            loadDashboardStats();
                        } else {
                            alert((resp && resp.data) ? resp.data : 'Eroare la creare programare');
                        }
                    },
                    error: function(){
                        alert('Eroare la creare programare');
                    }
                });
            }

            function updateSummary(){
                var services = $('#booking-service').data('services') || [];
                var svcId = parseInt($('#booking-service').val(),10);
                var svc = services.find(function(s){ return parseInt(s.id,10) === svcId; }) || {name:'', duration: <?php echo (int) Clinica_Settings::get_instance()->get('appointment_duration', 30); ?>};
                var doctor = $('#booking-doctor option:selected').text();
                var date = formatDateRo($('#booking-date').val());
                var slot = $('#booking-slot').val();
                var patient = $('#booking-patient option:selected').text();
                var parts = [];
                if (svcId) parts.push('Serviciu: <strong>'+ (svc.name || svcId) +'</strong> ('+ (svc.duration||'') +' min)');
                if (doctor) parts.push('Doctor: <strong>'+ doctor +'</strong>');
                if (date) parts.push('Data: <strong>'+ date +'</strong>');
                if (slot) parts.push('Interval: <strong>'+ slot +'</strong>');
                if (patient) parts.push('Pacient: <strong>'+ patient +'</strong>');
                $('#booking-summary').html(parts.join(' • '));
            }
        });
        </script>
        
        <style>
        /* Revert la 1200px pentru consistență */
        .clinica-patient-dashboard {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .patient-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .patient-avatar {
            width: 60px;
            height: 60px;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: #0073aa;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .patient-details h2 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .patient-details p {
            margin: 2px 0;
            color: #666;
            font-size: 14px;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 10px;
        }
        
        .dashboard-tabs {
            display: flex;
            background: #fff;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .tab-button:hover {
            background: #e9ecef;
        }
        
        .tab-button.active {
            background: #0073aa;
            color: white;
        }
        
        .dashboard-content {
            background: #fff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dashboard-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .dashboard-card h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 500;
            color: #666;
        }
        
        .info-item span {
            color: #333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .activity-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error {
            text-align: center;
            padding: 20px;
            color: #dc3545;
            background: #f8d7da;
            border-radius: 4px;
        }
        
        .no-activities {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        /* Appointments list */
        .appointments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }
        .appointment-form {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .form-grid { display: grid; gap: 12px; }
        .form-2col { display: grid; grid-template-columns: 1fr 1.2fr; gap: 16px; }
        .form-col .form-row { margin-bottom: 10px; }
        .booking-summary { font-size: 13px; color: #555; padding: 8px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; }
        #booking-calendar { background: #f8f9fa; padding: 8px; border-radius: 6px; border: 1px solid #e9ecef; }
        #booking-date-picker { display: none; }
        /* Calendar compact, o singură coloană */
        /* Calendar cu lățime elegantă, centrat în container */
        #booking-calendar { display: flex; justify-content: center; }
        #booking-calendar .flatpickr-calendar { width: clamp(360px, 70%, 560px); max-width: 100%; display: block; font-size: 14px; }
        #booking-calendar .flatpickr-innerContainer { display: block; width: auto; }
        #booking-calendar .flatpickr-rContainer { display: block !important; width: auto; padding: 0; margin: 0; }
        /* Aliniere nativă Flatpickr: 7 coloane egale și fără padding lateral diferit */
        #booking-calendar .flatpickr-weekdays { padding: 0; }
        #booking-calendar .flatpickr-weekdaycontainer { display: block; }
        #booking-calendar .flatpickr-weekday { display: inline-block; width: 14.285714%; text-align: center; font-weight: 600; color: #4b5563; box-sizing: border-box; }
        #booking-calendar .flatpickr-days { width: auto; padding: 0; }
        #booking-calendar .dayContainer { display: flex; flex-wrap: wrap; }
        /* 7 coloane egale pentru fiecare zi */
        #booking-calendar .flatpickr-day { width: 14.285714%; height: 36px; line-height: 36px; margin: 0; border-radius: 8px; font-weight: 500; box-sizing: border-box; text-align: center; }
        #booking-calendar .flatpickr-day.today { border-color: #0073aa; box-shadow: inset 0 0 0 1px #0073aa; }
        #booking-calendar .flatpickr-day.selected, 
        #booking-calendar .flatpickr-day.startRange, 
        #booking-calendar .flatpickr-day.endRange { background: #0073aa; border-color: #0073aa; color: #fff; }
        #booking-calendar .flatpickr-day.weekend { color: #9ca3af; }
        #booking-calendar .flatpickr-day.flatpickr-disabled { opacity: 0.35; }
        #booking-calendar .flatpickr-day.full { opacity: 0.45; }
        #booking-calendar .flatpickr-day.legal-holiday { background: #e3f2fd !important; color: #1976d2 !important; font-weight: bold !important; border: 2px solid #2196f3 !important; }
        #booking-calendar .flatpickr-day.legal-holiday.weekend { background: #e3f2fd !important; color: #1976d2 !important; }
        .flatpickr-day.weekend { pointer-events: none; position: relative; }
        .flatpickr-day.full { opacity: 0.35; pointer-events: none; }
        .flatpickr-day.weekend { pointer-events: none; position: relative; }
        .flatpickr-day.full { opacity: 0.35; pointer-events: none; }
        .flatpickr-day.today { border-color: #0073aa; }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: #0073aa; border-color: #0073aa; color: #fff; }
        .slots-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; }
        .slot-btn { padding: 8px; text-align: center; background:#fff; border:1px solid #e1e5e9; border-radius:6px; cursor:pointer; font-size:13px; }
        .slot-btn.disabled { opacity:.4; cursor:not-allowed; }
        .slot-btn.selected { background:#0073aa; color:#fff; border-color:#0073aa; }
        .form-row { display: grid; gap: 6px; }
        .form-row label { font-weight: 500; color: #333; }
        .form-row select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px; }
        .form-hint { font-size: 12px; color: #666; margin-top: 6px; }

        /* FINAL Flatpickr alignment fix: force identical 7-column grids for header and days */
        #booking-calendar .flatpickr-calendar { width: 100%; max-width: 560px; margin: 0 auto; display: block; }
        #booking-calendar .flatpickr-weekdays, #booking-calendar .flatpickr-days { padding: 8px 12px !important; }
        #booking-calendar .flatpickr-weekdaycontainer { display: grid !important; grid-template-columns: repeat(7, 1fr) !important; column-gap: 0 !important; }
        #booking-calendar .flatpickr-days .dayContainer { width: 100% !important; max-width: 100% !important; display: grid !important; grid-template-columns: repeat(7, 1fr) !important; column-gap: 0 !important; row-gap: 6px !important; }
        #booking-calendar .flatpickr-day { width: 100% !important; height: 38px !important; line-height: 38px !important; margin: 0 !important; text-align: center !important; }
        /* Marchează vizual weekendul cu un simbol interzis CSS (cerc roșu + bară albă) */
        #booking-calendar .flatpickr-day.weekend { position: relative; }
        #booking-calendar .flatpickr-day.weekend::before {
            content: '' !important;
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 18px; height: 18px; border-radius: 50%; background: #ff0000 !important; z-index: 1;
        }
        #booking-calendar .flatpickr-day.weekend::after {
            content: '' !important;
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 10px; height: 2px; background: #fff !important; border-radius: 2px; z-index: 2;
        }
        /* Evidențiere zi curentă (roșu #ff0000) când nu este selectată */
        #booking-calendar .flatpickr-day.today:not(.selected) {
            background: #ffecec !important;
            border-color: #ff0000 !important;
            box-shadow: inset 0 0 0 2px #ff0000 !important;
            color: #ff0000 !important;
            font-weight: 700;
        }
        /* Zi selectată în roșu intens */
        #booking-calendar .flatpickr-day.selected,
        #booking-calendar .flatpickr-day.startRange,
        #booking-calendar .flatpickr-day.endRange {
            background: #ff0000 !important;
            border-color: #ff0000 !important;
            color: #fff !important;
        }
        .appointment-card, .appointment-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #333;
        }
        .appointment-meta, .appointment-details {
            color: #555;
            font-size: 14px;
            display: grid;
            gap: 4px;
        }
        .appointment-actions { 
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .appointment-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-scheduled { background-color: #e7f3ff; color: #0073aa; }
        .status-confirmed { background-color: #d4edda; color: #155724; }
        .status-completed { background-color: #d1ecf1; color: #0c5460; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .status-no_show { background-color: #fff3cd; color: #856404; }
        
        /* Stiluri pentru appointment-item (JavaScript) */
        .appointment-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .appointment-detail:last-child {
            border-bottom: none;
        }
        .appointment-detail label {
            font-weight: 600;
            color: #333;
            min-width: 80px;
        }
        .appointment-detail span {
            color: #666;
            text-align: right;
        }
        
        /* Grid pentru programări */
        .appointments-grid {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .dashboard-tabs {
                flex-direction: column;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .appointment-card, .appointment-item {
                padding: 12px;
            }
            
            .appointment-header {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX pentru obținerea datelor pacientului
     */
    public function ajax_get_patient_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $user_id = get_current_user_id();
        $patient_data = $this->get_patient_data($user_id);
        
        if (!$patient_data) {
            wp_send_json_error('Nu s-au găsit datele pacientului');
        }
        
        wp_send_json_success($patient_data);
    }
    
    /**
     * AJAX pentru actualizarea informațiilor pacientului
     */
    public function ajax_update_patient_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $user_id = get_current_user_id();
        $data = $this->sanitize_patient_update_data($_POST);
        
        $result = $this->update_patient_info($user_id, $data);
        
        if ($result['success']) {
            wp_send_json_success('Informațiile au fost actualizate cu succes');
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX pentru obținerea programărilor
     */
    public function ajax_get_appointments() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă utilizatorul curent este pacientul respectiv
        if (get_current_user_id() !== $patient_id && !Clinica_Patient_Permissions::can_view_appointments()) {
            wp_send_json_error('Nu aveți permisiunea de a accesa aceste date');
        }
        
        $appointments = $this->get_appointments($patient_id);

        // Transformă rezultatele DB în obiecte simple pentru frontend (JSON)
        $appointments_array = array();
        foreach ((array) $appointments as $a) {
            // Prioritate 1: service_name din JOIN
            $type_value = isset($a->service_name) ? (string) $a->service_name : '';
            
            // Prioritate 2: service_id numeric
            if (empty($type_value) && isset($a->service_id) && ctype_digit($a->service_id)) {
                $type_value = $this->get_service_name_by_id((int) $a->service_id);
            }
            
            // Prioritate 3: type enum vechi
            if (empty($type_value) && isset($a->type) && ctype_digit($a->type)) {
                $type_value = $this->get_service_name_by_id((int) $a->type);
            }
            
            // Fallback: mapare după durată
            if (empty($type_value)) {
                $byDuration = $this->get_service_name_by_duration(isset($a->duration) ? (int)$a->duration : 0);
                if (!empty($byDuration)) { $type_value = $byDuration; }
            }
            
            if (empty($type_value)) { $type_value = '-'; }
            $appointments_array[] = array(
                'id' => isset($a->id) ? (int) $a->id : (isset($a->ID) ? (int) $a->ID : 0),
                'appointment_date' => isset($a->appointment_date) ? (string) $a->appointment_date : '',
                'appointment_time' => isset($a->appointment_time) ? (string) $a->appointment_time : '',
                'status' => isset($a->status) ? $this->translate_status($a->status) : 'Programată',
                'doctor_name' => isset($a->doctor_name) ? (string) $a->doctor_name : '',
                'type' => $type_value,
                'duration' => isset($a->duration) ? (int) $a->duration : 0,
                'notes' => isset($a->notes) ? (string) $a->notes : ''
            );
        }

        // Returnează atât JSON cât și HTML pentru compatibilitate în UI existent
        wp_send_json_success(array(
            'appointments' => $appointments_array,
            'html' => $this->render_appointments_list($appointments)
        ));
    }
    
    /**
     * AJAX pentru anularea programărilor
     */
    public function ajax_cancel_appointment() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_ok = wp_verify_nonce($nonce, 'clinica_dashboard_nonce') || wp_verify_nonce($nonce, 'clinica_admin_cancel_nonce');
        if (!$nonce_ok) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        if ($appointment_id <= 0) {
            wp_send_json_error('ID programare invalid');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error('Tabela programări nu există');
        }
        
        // Verifică programarea și proprietarul
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $appointment_id
        ));
        if (!$appointment) {
            wp_send_json_error('Programarea nu există');
        }
        
        $current_user_id = get_current_user_id();
        // Permite: proprietar programare SAU utilizator cu capabilități admin/manager doctor
        if ((int)$appointment->patient_id !== (int)$current_user_id && !Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error('Nu aveți permisiunea de a anula această programare');
        }
        
        // Permite anularea doar dacă e viitoare și în status scheduled/confirmed
        $today = current_time('Y-m-d');
        if (!in_array($appointment->status, array('scheduled','confirmed')) || $appointment->appointment_date < $today) {
            wp_send_json_error('Programarea nu poate fi anulată');
        }
        
        // Determină tipul utilizatorului curent
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $user_type = 'receptionist'; // default
        
        if (in_array('administrator', $user_roles)) {
            $user_type = 'admin';
        } elseif (in_array('manager', $user_roles)) {
            $user_type = 'manager';
        } elseif (in_array('doctor', $user_roles)) {
            $user_type = 'doctor';
        } elseif (in_array('assistant', $user_roles)) {
            $user_type = 'assistant';
        } elseif (in_array('receptionist', $user_roles)) {
            $user_type = 'receptionist';
        } elseif ((int)$appointment->patient_id === (int)$current_user_id) {
            $user_type = 'patient';
        }
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'status' => 'cancelled', 
                'updated_at' => current_time('mysql'),
                'last_edited_by_type' => $user_type,
                'last_edited_by_user_id' => $current_user_id,
                'last_edited_at' => current_time('mysql')
            ),
            array('id' => $appointment_id)
        );
        
        if ($updated === false) {
            wp_send_json_error('Eroare la anulare');
        }
        
        // Audit log
        $source = wp_verify_nonce($nonce, 'clinica_admin_cancel_nonce') ? 'admin' : 'frontend';
        $actor_id = get_current_user_id();
        $actor = get_userdata($actor_id);
        $actor_email = $actor ? $actor->user_email : '';
        $log_line = sprintf(
            "[%s] CANCEL_APPOINTMENT source=%s id=%d patient_id=%d doctor_id=%d actor_id=%d actor_email=%s status_before=%s\n",
            current_time('mysql'),
            $source,
            (int)$appointment_id,
            (int)$appointment->patient_id,
            (int)$appointment->doctor_id,
            (int)$actor_id,
            $actor_email,
            $appointment->status
        );
        $log_dir = dirname(__FILE__) . '/../logs';
        $plugin_root = dirname(dirname(__FILE__));
        $log_path = $plugin_root . '/logs/appointment-audit.log';
        if (!file_exists($plugin_root . '/logs')) {
            @mkdir($plugin_root . '/logs', 0755, true);
        }
        @file_put_contents($log_path, $log_line, FILE_APPEND);

        // Notificări email anulare
        $patient_user = get_userdata($appointment->patient_id);
        $doctor_user = get_userdata($appointment->doctor_id);
        $data = array(
            'type' => $appointment->type,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'duration' => $appointment->duration,
            'patient_name' => $patient_user ? $patient_user->display_name : '',
            'patient_email' => $patient_user ? $patient_user->user_email : '',
            'doctor_name' => $doctor_user ? $doctor_user->display_name : '',
            'doctor_email' => $doctor_user ? $doctor_user->user_email : ''
        );
        $this->send_appointment_notifications('cancelled', $data);
        
        wp_send_json_success('Programarea a fost anulată');
    }

    /**
     * Anulează o programare
     */
    private function cancel_appointment($appointment_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';

        $result = $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('ID' => $appointment_id)
        );

        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la anularea programării.');
        }

        return array('success' => true);
    }
    
    /**
     * AJAX pentru obținerea istoricului medical
     */
    public function ajax_get_medical_history() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă utilizatorul curent este pacientul respectiv
        if (get_current_user_id() !== $patient_id) {
            wp_send_json_error('Nu aveți permisiunea de a accesa aceste date');
        }
        
        $medical_data = $this->get_medical_history($patient_id);
        
        wp_send_json_success($medical_data);
    }
    
    /**
     * AJAX pentru obținerea datelor familiei pacientului
     */
    public function ajax_get_patient_family() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă utilizatorul curent este pacientul respectiv
        if (get_current_user_id() !== $patient_id) {
            wp_send_json_error('Nu aveți permisiunea de a accesa aceste date');
        }
        
        $family_data = $this->get_patient_family_data($patient_id);
        
        wp_send_json_success($family_data);
    }
    
    /**
     * Sanitizează datele pentru actualizare
     */
    private function sanitize_patient_update_data($data) {
        return array(
            'phone_primary' => sanitize_text_field($data['phone_primary'] ?? ''),
            'phone_secondary' => sanitize_text_field($data['phone_secondary'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'emergency_contact' => sanitize_text_field($data['emergency_contact'] ?? ''),
            'allergies' => sanitize_textarea_field($data['allergies'] ?? ''),
            'medical_history' => sanitize_textarea_field($data['medical_history'] ?? '')
        );
    }
    
    /**
     * Actualizează informațiile pacientului
     */
    private function update_patient_info($user_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('user_id' => $user_id)
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Eroare la actualizarea datelor');
        }
        
        return array('success' => true);
    }
    
    /**
     * Obține programările pacientului
     */
    private function get_appointments($patient_id) {
        global $wpdb;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $table_name = $wpdb->prefix . 'clinica_appointments';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }

        $where = array('a.patient_id = %d');
        $values = array($patient_id);

        $today = current_time('Y-m-d');
        switch ($filter) {
            case 'upcoming':
                $where[] = "a.appointment_date >= %s";
                $values[] = $today;
                $where[] = "a.status IN ('scheduled','confirmed')";
                break;
            case 'past':
                $where[] = "a.appointment_date < %s";
                $values[] = $today;
                $where[] = "a.status IN ('completed','no_show')";
                break;
            case 'cancelled':
                $where[] = "a.status = 'cancelled'";
                break;
            case 'all':
            default:
                // fără filtrare suplimentară
                break;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $query = "SELECT a.*, 
                         COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), d.display_name) as doctor_name,
                         s.name as service_name
                  FROM $table_name a 
                  LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID 
                  LEFT JOIN {$wpdb->usermeta} um1 ON d.ID = um1.user_id AND um1.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} um2 ON d.ID = um2.user_id AND um2.meta_key = 'last_name'
                  LEFT JOIN {$wpdb->prefix}clinica_services s ON a.service_id = s.id
                  $where_clause 
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC ";

        $prepared = $wpdb->prepare($query, $values);
        $rows = $wpdb->get_results($prepared);
        if (!$rows) {
            return array();
        }
        return $rows;
    }
    
    /**
     * Render lista de programări
     */
    private function render_appointments_list($appointments) {
        if (empty($appointments)) {
            return '<div class="no-appointments">
                <p>Nu aveți programări în acest moment.</p>
                <p>Programările vor apărea aici când vor fi create de personalul medical.</p>
            </div>';
        }
        
        // Construiește tabelul (responsive) cu sortare + paginare
        ob_start();
        ?>
        <div class="patient-appointments-table-wrap">
            <style>
            .patient-appointments-table-wrap .appt-status-scheduled { color:#64748b; }
            .patient-appointments-table-wrap .appt-status-confirmed { color:#16a34a; }
            .patient-appointments-table-wrap .appt-status-completed { color:#2563eb; }
            .patient-appointments-table-wrap .appt-status-cancelled { color:#dc2626; }
            .patient-appointments-table-wrap .appt-status-no_show { color:#d97706; }
            </style>
            <div class="table-actions" style="margin: 8px 0; display:flex; gap:12px; align-items:center;">
                <label for="appt-rows-per-page" style="font-weight:600;">Rânduri pe pagină</label>
                <select id="appt-rows-per-page" style="max-width:90px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <div id="appt-table-info" style="margin-left:auto; opacity:.8;"></div>
            </div>
            <div class="table-responsive" style="overflow:auto; border:1px solid #e2e8f0; border-radius:6px;">
                <table id="patient-appointments-table" class="patient-appointments-table" style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th data-sort="date" style="text-align:left; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Data</th>
                            <th data-sort="text" style="text-align:left; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Interval</th>
                            <th data-sort="text" style="text-align:left; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Doctor</th>
                            <th data-sort="text" style="text-align:left; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Serviciu</th>
                            <th data-sort="num" style="text-align:right; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Durată</th>
                            <th data-sort="text" style="text-align:left; padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;">Status</th>
                            <th style="text-align:right; padding:10px; border-bottom:1px solid #e2e8f0;">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <?php
                            // Normalizează ID
            if (!isset($appointment->id) || empty($appointment->id)) {
                                if (isset($appointment->ID)) { $appointment->id = $appointment->ID; } else { continue; }
                            }
                            $date_raw = isset($appointment->appointment_date) ? (string)$appointment->appointment_date : '';
                            $date_display = $this->format_date($date_raw);
                            $start_time = substr(isset($appointment->appointment_time) ? (string)$appointment->appointment_time : '', 0, 5);
                            $dur = isset($appointment->duration) ? (int)$appointment->duration : 0;
                            $end_time = $start_time ? date('H:i', strtotime($start_time) + 60 * max(0,$dur)) : '';
                            $doctor_name = isset($appointment->doctor_name) ? esc_html($appointment->doctor_name) : '';
                            // Tip/serviciu
                            $type_val = isset($appointment->service_name) ? (string)$appointment->service_name : '';
                            if (empty($type_val) && isset($appointment->service_id) && ctype_digit((string)$appointment->service_id)) {
                                $type_val = $this->get_service_name_by_id((int)$appointment->service_id);
                            }
                            if (empty($type_val) && isset($appointment->type) && ctype_digit((string)$appointment->type)) {
                                $type_val = $this->get_service_name_by_id((int)$appointment->type);
                            }
                            if (empty($type_val)) { $type_val = '-'; }
                            $status_raw = isset($appointment->status) ? (string)$appointment->status : '';
                            $status_map = array('scheduled'=>'Programată','confirmed'=>'Acceptat','completed'=>'Completată','cancelled'=>'Anulată','no_show'=>'Nu s-a prezentat');
                            $status_label = isset($status_map[$status_raw]) ? $status_map[$status_raw] : $status_raw;
                            $can_cancel = in_array($status_raw, array('scheduled','confirmed')) && $date_raw >= current_time('Y-m-d');
                            ?>
                            <tr data-id="<?php echo (int)$appointment->id; ?>">
                                <td data-key="<?php echo esc_attr($date_raw); ?>" style="padding:10px; border-bottom:1px solid #eef2f7; white-space:nowrap;"><?php echo esc_html($date_display); ?></td>
                                <td data-key="<?php echo esc_attr($start_time); ?>" style="padding:10px; border-bottom:1px solid #eef2f7; white-space:nowrap;"><?php echo esc_html(trim($start_time . ($end_time ? ' - ' . $end_time : ''))); ?></td>
                                <td style="padding:10px; border-bottom:1px solid #eef2f7;"><?php echo $doctor_name; ?></td>
                                <td style="padding:10px; border-bottom:1px solid #eef2f7;"><?php echo esc_html($type_val); ?></td>
                                <td data-key="<?php echo (int)$dur; ?>" style="padding:10px; border-bottom:1px solid #eef2f7; text-align:right; white-space:nowrap;"><?php echo (int)$dur; ?> min</td>
                                <td style="padding:10px; border-bottom:1px solid #eef2f7;"><span class="appt-status appt-status-<?php echo esc_attr($status_raw); ?>"><?php echo esc_html($status_label); ?></span></td>
                                <td style="padding:10px; border-bottom:1px solid #eef2f7; text-align:right;">
                                    <?php if ($can_cancel): ?>
                                        <button type="button" class="button button-secondary js-cancel-appointment">Anulează</button>
                                    <?php else: ?>
                                        <span style="opacity:.6;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" style="display:flex; gap:8px; align-items:center; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="button" id="appt-prev">Înapoi</button>
                <div id="appt-page-indicator" style="min-width:120px; text-align:center;"></div>
                <button type="button" class="button" id="appt-next">Înainte</button>
            </div>
        </div>
        <script>
        jQuery(function($){
            var $wrap = $('.patient-appointments-table-wrap');
            var $table = $('#patient-appointments-table');
            var $tbody = $table.find('tbody');
            var rows = $tbody.find('tr').toArray();
            var currentPage = 1;
            var pageSize = parseInt($('#appt-rows-per-page').val(), 10) || 10;
            var sort = {idx: 0, dir: 'asc', type: 'date'};

            function getCellKey(td, type){
                var key = $(td).data('key');
                if (key === undefined) key = $(td).text().trim();
                if (type === 'num') return parseFloat(key) || 0;
                if (type === 'date') return key; // YYYY-MM-DD deja în data-key
                return key.toLowerCase();
            }

            function render(){
                var total = rows.length;
                var totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;
                var start = (currentPage - 1) * pageSize;
                var end = Math.min(start + pageSize, total);
                $tbody.empty();
                for (var i=start; i<end; i++){ $tbody.append(rows[i]); }
                $('#appt-page-indicator').text('Pagina ' + currentPage + ' / ' + totalPages);
                var info = total ? ((start+1) + '-' + end + ' din ' + total) : '0 din 0';
                $('#appt-table-info').text(info);
            }

            function doSort(idx, type){
                if (sort.idx === idx){ sort.dir = (sort.dir === 'asc') ? 'desc' : 'asc'; }
                else { sort.idx = idx; sort.dir = 'asc'; sort.type = type; }
                rows.sort(function(a,b){
                    var ka = getCellKey($(a).children().eq(idx), type);
                    var kb = getCellKey($(b).children().eq(idx), type);
                    if (ka < kb) return sort.dir === 'asc' ? -1 : 1;
                    if (ka > kb) return sort.dir === 'asc' ? 1 : -1;
                    return 0;
                });
                currentPage = 1;
                render();
            }

            $table.find('th[data-sort]').each(function(i){
                $(this).on('click', function(){
                    doSort(i, $(this).data('sort'));
                });
            });

            $('#appt-rows-per-page').on('change', function(){ pageSize = parseInt($(this).val(),10)||10; currentPage = 1; render(); });
            $('#appt-prev').on('click', function(){ if (currentPage>1){ currentPage--; render(); }});
            $('#appt-next').on('click', function(){ var totalPages = Math.max(1, Math.ceil(rows.length / pageSize)); if (currentPage<totalPages){ currentPage++; render(); }});

            // Delegare pentru anulare
            $(document).off('click', '.js-cancel-appointment').on('click', '.js-cancel-appointment', function(e){
                e.preventDefault();
                var $tr = $(this).closest('tr');
                var id = $tr.data('id');
                if (!confirm('Sigur doriți să anulați această programare?')) { return; }
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'clinica_cancel_appointment', appointment_id: id, nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' },
                    success: function(resp){
                        if (resp && resp.success){
                            $tr.find('.appt-status').removeClass(function(idx, cls){ return (cls.match(/appt-status-\S+/g)||[]).join(' '); }).addClass('appt-status-cancelled').text('Anulată');
                            $tr.find('.js-cancel-appointment').remove();
                        } else {
                            alert((resp&&resp.data)||'Eroare la anulare');
                        }
                    },
                    error: function(){ alert('Eroare la anulare'); }
                });
            });

            // Sortare inițială: Data desc + Ora desc (prin data-key date + time până la rând stabilit)
            doSort(0, 'date');
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render un element de programare
     */
    private function render_appointment_item($appointment) {
        // Acceptă atât obiecte cât și array-uri
        $is_array = is_array($appointment);
        $get_value = function($key, $default = '') use ($appointment, $is_array) {
            if ($is_array) {
                return isset($appointment[$key]) ? $appointment[$key] : $default;
            } else {
                return isset($appointment->$key) ? $appointment->$key : $default;
            }
        };
        
        $date_display = $this->format_date($get_value('appointment_date'));
        $start_time = substr($get_value('appointment_time'), 0, 5);
        $end_time = '';
        if (!empty($get_value('duration'))) {
            $end_time = date('H:i', strtotime($get_value('appointment_time')) + 60 * (int)$get_value('duration'));
        }
        $doctor_name = esc_html($get_value('doctor_name'));
        // Tip: rezolvă din service_id sau enum vechi
        $type_val = $get_value('type', '');
        $service_id = $get_value('service_id', '');
        
        // Prioritate 1: service_id (numele serviciului)
        if (!empty($service_id) && ctype_digit($service_id)) {
            $type_val = $this->get_service_name_by_id((int) $service_id);
        }
        // Prioritate 2: type enum vechi
        elseif ($type_val !== '' && ctype_digit($type_val)) { 
            $type_val = $this->get_service_name_by_id((int) $type_val); 
        }
        
        // Mapare enum vechi
        $legacy = array('consultation'=>'Consultație','examination'=>'Examinare','procedure'=>'Procedură','follow_up'=>'Control');
        if (isset($legacy[$type_val])) { $type_val = $legacy[$type_val]; }
        
        // Fallback dacă totul e gol
        if (empty($type_val)) { $type_val = '-'; }
        
        $type = esc_html($type_val);
        $status_raw = $get_value('status');
        $status_class = 'status-' . sanitize_html_class($status_raw);
        
        // Traduce statusul în română
        $status_translations = array(
            'scheduled' => 'Programată',
            'confirmed' => 'Acceptat', 
            'completed' => 'Completată',
            'cancelled' => 'Anulată',
            'no_show' => 'Nu s-a prezentat'
        );
        $status = esc_html(isset($status_translations[$status_raw]) ? $status_translations[$status_raw] : $status_raw);
 
        $can_cancel = in_array($get_value('status'), array('scheduled', 'confirmed')) && $get_value('appointment_date') >= current_time('Y-m-d');
 
        ob_start();
        ?>
        <div class="appointment-card" data-id="<?php echo esc_attr($get_value('id')); ?>">
            <div class="appointment-header">
                <div><?php echo $date_display; ?> • <?php echo esc_html($start_time . ($end_time ? ' - ' . $end_time : '')); ?></div>
                <span class="appointment-status <?php echo $status_class; ?>">&nbsp;<?php echo $status; ?></span>
            </div>
            <div class="appointment-meta">
                <?php
                // Afișează numele pacientului și vârsta
                $patient_id = $get_value('patient_id');
                if ($patient_id) {
                    $patient = get_userdata($patient_id);
                    if ($patient) {
                        $patient_name = $patient->display_name;
                        $patient_cnp = $patient->user_login; // CNP-ul este în username
                        $patient_age = $this->get_age_from_cnp($patient_cnp);
                        
                        echo '<div><strong>Pacient:</strong> ' . esc_html($patient_name);
                        if ($patient_age !== null) {
                            echo ' (' . $patient_age . ' ani)';
                        }
                        echo '</div>';
                    }
                }
                ?>
                <div><strong>Doctor:</strong> <?php echo $doctor_name; ?></div>
                <div><strong>Tip:</strong> <?php echo $type; ?></div>
                <?php if (!empty($get_value('duration'))) : ?>
                <div><strong>Durată:</strong> <?php echo intval($get_value('duration')); ?> min</div>
                <?php endif; ?>
            </div>
            <div class="appointment-actions">
                <?php if ($can_cancel) : ?>
                <button type="button" class="button button-secondary js-cancel-appointment">Anulează</button>
                <?php endif; ?>
            </div>
        </div>
        <script>
        jQuery(document).off('click', '.js-cancel-appointment').on('click', '.js-cancel-appointment', function(e){
            e.preventDefault();
            var card = jQuery(this).closest('.appointment-card');
            var id = card.data('id');
            if (!confirm('Sigur doriți să anulați această programare?')) { return; }
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'clinica_cancel_appointment',
                    appointment_id: id,
                    nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
                },
                success: function(response){
                    if (response.success) {
                        card.find('.appointment-status').removeClass('status-scheduled status-confirmed').addClass('status-cancelled').text('Anulată');
                        card.find('.js-cancel-appointment').remove();
                    } else {
                        alert(response.data || 'Eroare la anulare');
                    }
                },
                error: function(){
                    alert('Eroare la anulare');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obține istoricul medical
     */
    private function get_medical_history($patient_id) {
        // TODO: Implementare când vor fi create tabelele pentru rezultate și prescripții
        return array(
            'results' => '<div class="no-results">Nu există rezultate de analize încă.</div>',
            'prescriptions' => '<div class="no-prescriptions">Nu există prescripții încă.</div>'
        );
    }
    
    /**
     * Formatează data
     */
    private function format_date($date) {
        if (empty($date)) {
            return '-';
        }
        
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        if ($date_obj) {
            return $date_obj->format('d.m.Y');
        }
        
        return $date;
    }
    
    /**
     * Calculează vârsta
     */
    private function calculate_age($birth_date) {
        if (empty($birth_date)) {
            return '-';
        }
        
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);
        
        return $age->y;
    }
    
    /**
     * Obține eticheta pentru sex
     */
    private function get_gender_label($gender) {
        switch ($gender) {
            case 'male':
                return 'Masculin';
            case 'female':
                return 'Feminin';
            default:
                return 'Necunoscut';
        }
    }
    
    /**
     * AJAX pentru obținerea statisticilor dashboard
     */
    public function ajax_get_dashboard_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă utilizatorul curent este pacientul respectiv
        if (get_current_user_id() !== $patient_id) {
            wp_send_json_error('Nu aveți permisiunea de a accesa aceste date');
        }
        
        // Obține statisticile
        $stats = $this->get_dashboard_stats($patient_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX pentru obținerea activităților recente
     */
    public function ajax_get_recent_activities() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $patient_id = intval($_POST['patient_id']);
        
        // Verifică dacă utilizatorul curent este pacientul respectiv
        if (get_current_user_id() !== $patient_id) {
            wp_send_json_error('Nu aveți permisiunea de a accesa aceste date');
        }
        
        // Obține activitățile recente
        $activities = $this->get_recent_activities($patient_id);
        
        wp_send_json_success($activities);
    }
    
    /**
     * Obține statisticile dashboard
     */
    private function get_dashboard_stats($patient_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        $today = current_time('Y-m-d');

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array(
                'total_appointments' => 0,
                'upcoming_appointments' => 0,
                'unread_messages' => 0,
                'total_results' => 0,
                'total_prescriptions' => 0
            );
        }

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d",
            $patient_id
        ));
        $upcoming = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d AND appointment_date >= %s AND status IN ('scheduled','confirmed')",
            $patient_id,
            $today
        ));

        // Interval în funcție de selector (default 6 luni)
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '6m';
        switch ($period) {
            case '30d':
                $from_date = date('Y-m-d', strtotime('-30 days', strtotime($today)));
                break;
            case '3m':
                $from_date = date('Y-m-d', strtotime('-3 months', strtotime($today)));
                break;
            case '12m':
                $from_date = date('Y-m-d', strtotime('-12 months', strtotime($today)));
                break;
            case 'all':
                $from_date = '1970-01-01';
                break;
            case '6m':
            default:
                $from_date = date('Y-m-d', strtotime('-6 months', strtotime($today)));
                break;
        }
        // Folosim updated_at pentru statusuri (reflectă momentul schimbării)
        $from_ts = $from_date . ' 00:00:00';
        $now_ts  = current_time('mysql');
        $range_cancelled = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d AND updated_at BETWEEN %s AND %s AND status = 'cancelled'",
            $patient_id,
            $from_ts,
            $now_ts
        ));
        $range_completed = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d AND updated_at BETWEEN %s AND %s AND status = 'completed'",
            $patient_id,
            $from_ts,
            $now_ts
        ));
        $range_no_show = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d AND updated_at BETWEEN %s AND %s AND status = 'no_show'",
            $patient_id,
            $from_ts,
            $now_ts
        ));

        $result = array(
            'total_appointments' => $total,
            'upcoming_appointments' => $upcoming,
            'unread_messages' => 0,
            'total_results' => 0,
            'total_prescriptions' => 0,
            'range_cancelled' => $range_cancelled,
            'range_completed' => $range_completed,
            'range_no_show' => $range_no_show
        );

        // Debug detaliat opțional
        if (!empty($_POST['debug'])) {
            // statusuri distincte în tabel
            $distinct = $wpdb->get_col("SELECT DISTINCT status FROM $table_name");
            // breakdown în interval
            $breakdown = $wpdb->get_results($wpdb->prepare(
                "SELECT status, COUNT(*) as cnt FROM $table_name WHERE patient_id = %d AND updated_at BETWEEN %s AND %s GROUP BY status",
                $patient_id, $from_ts, $now_ts
            ), ARRAY_A);
            // un eșantion de programări anulate din interval
            $sample_cancelled = $wpdb->get_results($wpdb->prepare(
                "SELECT id, appointment_date, updated_at, status FROM $table_name WHERE patient_id = %d AND updated_at BETWEEN %s AND %s AND status = 'cancelled' ORDER BY updated_at DESC LIMIT 5",
                $patient_id, $from_ts, $now_ts
            ), ARRAY_A);
            $result['debug'] = array(
                'period_from' => $from_ts,
                'period_to' => $now_ts,
                'distinct_statuses' => $distinct,
                'range_breakdown' => $breakdown,
                'sample_cancelled' => $sample_cancelled
            );
        }

        return $result;
    }
    
    /**
     * Obține activitățile recente
     */
    private function get_recent_activities($patient_id) {
        // TODO: Implementare când vor fi create tabelele pentru activități
        return array();
    }
    
    /**
     * Obține datele familiei pacientului
     */
    private function get_patient_family_data($patient_id) {
        // Verifică dacă există clasa Family Manager
        if (!class_exists('Clinica_Family_Manager')) {
            return array(
                'status' => '<div class="family-not-configured">Funcționalitatea de familie nu este configurată</div>',
                'members' => '<div class="no-family-members">Nu există membri de familie</div>'
            );
        }
        
        $family_manager = new Clinica_Family_Manager();
        $patient_family = $family_manager->get_patient_family($patient_id);
        
        if (!$patient_family) {
            return array(
                'status' => '<div class="family-status">
                    <h4>Status Familie</h4>
                    <p>Nu faceți parte din nicio familie înregistrată.</p>
                    <p>Pentru a fi adăugat într-o familie, contactați personalul medical.</p>
                </div>',
                'members' => '<div class="no-family-members">
                    <p>Nu există membri de familie înregistrați.</p>
                </div>'
            );
        }
        
        // Obține membrii familiei
        $family_members = $family_manager->get_family_members($patient_family['id']);
        
        // Render status familie
        $status_html = '<div class="family-status">
            <h4>Familia ' . esc_html($patient_family['name']) . '</h4>
            <p>Rolul dvs. în familie: <strong>' . esc_html($family_manager->get_family_role_label($patient_family['role'])) . '</strong></p>
            <p>Numărul total de membri: <strong>' . count($family_members) . '</strong></p>
        </div>';
        
        // Render membrii familiei
        $members_html = '<div class="family-members-list">';
        if (empty($family_members)) {
            $members_html .= '<div class="no-family-members">
                <p>Nu există alți membri în familie.</p>
            </div>';
        } else {
            $members_html .= '<div class="members-grid">';
            foreach ($family_members as $member) {
                if ($member->user_id != $patient_id) { // Nu afișa pacientul curent
                    $first_name = isset($member->first_name) ? $member->first_name : '';
                    $last_name = isset($member->last_name) ? $member->last_name : '';
                    $display_name = isset($member->display_name) ? $member->display_name : ($first_name . ' ' . $last_name);
                    
                    $members_html .= '<div class="family-member-card">
                        <div class="member-avatar">
                            <div class="avatar-placeholder">
                                ' . strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) . '
                            </div>
                        </div>
                        <div class="member-info">
                            <h5>' . esc_html($display_name) . '</h5>
                            <p class="member-role">' . esc_html($family_manager->get_family_role_label($member->family_role)) . '</p>
                            <p class="member-age">' . esc_html($this->calculate_age($member->birth_date)) . ' ani</p>
                        </div>
                    </div>';
                }
            }
            $members_html .= '</div>';
        }
        $members_html .= '</div>';
        
        return array(
            'status' => $status_html,
            'members' => $members_html
        );
    }

    public function ajax_get_doctors_for_service() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        if (!is_user_logged_in()) { wp_send_json_error('Neautorizat'); }
        
        $users = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));
        $doctors = array();
        foreach ($users as $u) {
            $doctors[] = array('id' => $u->ID, 'name' => $u->display_name);
        }
        wp_send_json_success($doctors);
    }

    public function ajax_get_doctor_availability_days() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        if ($doctor_id <= 0) { wp_send_json_error('Doctor invalid'); }

        // Program per-doctor din user meta, fallback global
        $doctor_schedule = get_user_meta($doctor_id, 'clinica_working_hours', true);
        if (is_string($doctor_schedule)) { $doctor_schedule = json_decode($doctor_schedule, true); }
        if (!is_array($doctor_schedule) || empty($doctor_schedule)) {
            $settings = Clinica_Settings::get_instance();
            $doctor_schedule = $settings->get('working_hours', array());
            if (is_string($doctor_schedule)) { $doctor_schedule = json_decode($doctor_schedule, true); }
        }

        $days = array();
        $date = new DateTime(current_time('Y-m-d'));
        $settings = Clinica_Settings::get_instance();
        $holidays = $settings->get('clinic_holidays', array());
        if (is_string($holidays)) { $holidays = json_decode($holidays, true); }
        $holidays = is_array($holidays) ? $holidays : array();
        $max_per_day = (int) $settings->get('max_appointments_per_doctor_per_day', 24);
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        $advance_days = (int) $settings->get('appointment_advance_days', 90);
        if ($advance_days < 1) { $advance_days = 1; }
        if ($advance_days > 365) { $advance_days = 365; }
        $todayStr = current_time('Y-m-d');
        
        // Dacă avem service_id, verifică timeslots-urile specifice
        $service_timeslots = array();
        $service_timeslots_by_day = array();
        $has_any_service_timeslots = false;
        if ($service_id > 0) {
            $timeslots_table = $wpdb->prefix . 'clinica_doctor_timeslots';
            $service_timeslots = $wpdb->get_results($wpdb->prepare(
                "SELECT day_of_week, start_time, end_time FROM $timeslots_table WHERE doctor_id = %d AND service_id = %d AND is_active = 1",
                $doctor_id, $service_id
            ));
            
            // Grupează timeslots-urile pe zile
            foreach ($service_timeslots as $timeslot) {
                $service_timeslots_by_day[$timeslot->day_of_week] = true;
                $has_any_service_timeslots = true;
            }
        }
        for ($i = 0; $i < $advance_days; $i++) {
            $dow = strtolower($date->format('l'));
            $dateStr = $date->format('Y-m-d');
            
            // Verifică sărbătorile legale românești
            $is_legal_holiday = Clinica_Romanian_Holidays::is_holiday($dateStr);
            
            // Verifică programul de lucru general
            $has_working_hours = !in_array($dateStr, $holidays, true) && 
                                !$is_legal_holiday &&
                                isset($doctor_schedule[$dow]) && 
                                !empty($doctor_schedule[$dow]['active']) && 
                                !empty($doctor_schedule[$dow]['start']) && 
                                !empty($doctor_schedule[$dow]['end']);
            
            // Verifică dacă serviciul are timeslots specifice pentru această zi
            $has_service_timeslots = true;
            if ($has_any_service_timeslots) {
                // Dacă serviciul are timeslots specifice, verifică dacă ziua curentă este disponibilă
                $day_of_week = $date->format('N'); // 1 = Luni, 2 = Marți, etc.
                $has_service_timeslots = isset($service_timeslots_by_day[$day_of_week]);
                
                // Dacă serviciul are timeslots specifice, permite ziua chiar dacă nu există program de lucru
                if ($has_service_timeslots) {
                    $has_working_hours = true;
                }
            } else {
                // Dacă nu există timeslots specifice pentru serviciu, permite zilele cu programul general
                $has_service_timeslots = true;
            }
            
            // Pentru ziua curentă, verifică doar dacă doctorul mai lucrează
            if ($dateStr === $todayStr && $has_working_hours) {
                $current_time = current_time('H:i');
                $end_time = $doctor_schedule[$dow]['end'];
                
                // Dacă programul s-a terminat deja, nu permite programări
                if ($current_time >= $end_time) {
                    $has_service_timeslots = false;
                }
            }
            
            if ($has_working_hours && $has_service_timeslots) {
                // calculează câte programări are medicul în această zi
                $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')", $doctor_id, $dateStr));
                $days[] = array('date' => $dateStr, 'full' => ($max_per_day > 0 && $count >= $max_per_day), 'today' => ($dateStr === $todayStr));
            }
            $date->modify('+1 day');
        }
        wp_send_json_success($days);
    }

    public function ajax_get_doctor_slots() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $day = isset($_POST['day']) ? sanitize_text_field($_POST['day']) : '';
        $req_duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        if ($doctor_id <= 0 || empty($day)) { wp_send_json_error('Parametri invalizi'); }

        // Dacă avem service_id, verifică dacă există timeslots specifice
        if ($service_id > 0) {
            global $wpdb;
            $timeslots_table = $wpdb->prefix . 'clinica_doctor_timeslots';
            $day_of_week = date('N', strtotime($day));
            
            $service_timeslots = $wpdb->get_results($wpdb->prepare(
                "SELECT start_time, end_time, slot_duration FROM $timeslots_table 
                 WHERE doctor_id = %d AND service_id = %d AND day_of_week = %d AND is_active = 1",
                $doctor_id, $service_id, $day_of_week
            ));
            
            if (!empty($service_timeslots)) {
                // Folosește timeslots-urile specifice
                $services_manager = Clinica_Services_Manager::get_instance();
                $available_slots = $services_manager->generate_available_slots($doctor_id, $service_id, $day);
                
                // Convertește la formatul așteptat
                $formatted_slots = array();
                foreach ($available_slots as $slot) {
                    $formatted_slots[] = $slot['start_time'] . ' - ' . $slot['end_time'];
                }
                
                wp_send_json_success($formatted_slots);
            }
        }

        // Program per-doctor din user meta, fallback global
        $doctor_schedule = get_user_meta($doctor_id, 'clinica_working_hours', true);
        if (is_string($doctor_schedule)) { $doctor_schedule = json_decode($doctor_schedule, true); }
        if (!is_array($doctor_schedule) || empty($doctor_schedule)) {
            $settings = Clinica_Settings::get_instance();
            $doctor_schedule = $settings->get('working_hours', array());
            if (is_string($doctor_schedule)) { $doctor_schedule = json_decode($doctor_schedule, true); }
        }

        // Verifică sărbătorile legale românești
        if (Clinica_Romanian_Holidays::is_holiday($day)) {
            wp_send_json_success(array());
        }
        
        // Concedii per-doctor
        $doc_holidays = get_user_meta($doctor_id, 'clinica_doctor_holidays', true);
        if (is_string($doc_holidays)) { $doc_holidays = json_decode($doc_holidays, true); }
        $doc_holidays = is_array($doc_holidays) ? $doc_holidays : array();
        if (in_array($day, $doc_holidays, true)) {
            wp_send_json_success(array());
        }

        $settings = Clinica_Settings::get_instance();
        $interval = (int) $settings->get('appointment_interval', 15);
        $default_duration = (int) $settings->get('appointment_duration', 30);
        $duration = $req_duration > 0 ? $req_duration : $default_duration;
        $max_per_day = (int) $settings->get('max_appointments_per_doctor_per_day', 24);

        $dow = strtolower((new DateTime($day))->format('l'));
        if (!isset($doctor_schedule[$dow]) || empty($doctor_schedule[$dow]['active']) || empty($doctor_schedule[$dow]['start']) || empty($doctor_schedule[$dow]['end'])) {
            wp_send_json_success(array());
        }

        $start = $doctor_schedule[$dow]['start'];
        $end = $doctor_schedule[$dow]['end'];
        $break_start = isset($doctor_schedule[$dow]['break_start']) ? $doctor_schedule[$dow]['break_start'] : '';
        $break_end = isset($doctor_schedule[$dow]['break_end']) ? $doctor_schedule[$dow]['break_end'] : '';

        // Preia programările existente pentru doctor în acea zi
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        $existing = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time, duration FROM $table WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')",
            $doctor_id, $day
        ));
        $occupied = array();
        foreach ($existing as $ex) {
            $occupied[] = array('start' => $ex->appointment_time, 'duration' => (int)$ex->duration);
        }
        // Dacă limita per zi este atinsă, nu mai afișăm sloturi
        if ($max_per_day > 0 && count($occupied) >= $max_per_day) {
            wp_send_json_success(array());
        }

        // Generează sloturi și exclude ocupatele
        $slots = array();
        $cursor = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . $start);
        $endTime = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . $end);
        $step = max($interval, $duration);
        
        // Pentru ziua curentă, începe de la ora curentă + 30 minute (buffer)
        $today = current_time('Y-m-d');
        if ($day === $today) {
            $current_time = current_time('H:i');
            $current_datetime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $current_time);
            $min_start_time = (clone $current_datetime)->modify('+30 minutes'); // Buffer de 30 min
            
            // Setează cursorul la ora minimă necesară
            if ($min_start_time > $cursor) {
                $cursor = $min_start_time;
                // Ajustează cursorul la următorul interval
                $minutes = $cursor->format('i');
                $interval_minutes = $step;
                $next_interval = ceil($minutes / $interval_minutes) * $interval_minutes;
                if ($next_interval >= 60) {
                    $cursor->modify('+1 hour')->setTime($cursor->format('H'), 0);
                } else {
                    $cursor->setTime($cursor->format('H'), $next_interval);
                }
            }
        }
        
        while ($cursor < $endTime) {
            $slotStart = clone $cursor;
            $slotEnd = (clone $cursor)->modify('+' . $duration . ' minutes');
            if ($slotEnd > $endTime) { break; }

            // excludem pauza
            if (!empty($break_start) && !empty($break_end)) {
                $brStart = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . $break_start);
                $brEnd = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . $break_end);
                if ($brStart && $brEnd && $slotStart < $brEnd && $brStart < $slotEnd) {
                    $cursor->modify('+' . $step . ' minutes');
                    continue;
                }
            }

            // verifică overlap cu ocupatele
            $is_free = true;
            foreach ($occupied as $occ) {
                // Unele înregistrări TIME pot fi în format H:i:s. Încearcă mai multe formate.
                $occStart = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . $occ['start']);
                if (!$occStart) {
                    $occStart = DateTime::createFromFormat('Y-m-d H:i:s', $day . ' ' . $occ['start']);
                }
                if (!$occStart && !empty($occ['start'])) {
                    $occStart = DateTime::createFromFormat('Y-m-d H:i', $day . ' ' . substr((string)$occ['start'], 0, 5));
                }
                if (!$occStart) { continue; }
                $occEnd = (clone $occStart)->modify('+' . (int)$occ['duration'] . ' minutes');
                if ($slotStart < $occEnd && $occStart < $slotEnd) { $is_free = false; break; }
            }
            if ($is_free) {
                $slots[] = $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i');
                // respectă limita totală (sloturi posibile rămase)
                if ($max_per_day > 0 && (count($occupied) + count($slots)) >= $max_per_day) {
                    break;
                }
            }
            $cursor->modify('+' . $step . ' minutes');
        }

        wp_send_json_success($slots);
    }

    public function ajax_create_own_appointment() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        if (!Clinica_Patient_Permissions::can_create_own_appointments()) {
            wp_send_json_error('Nu aveți permisiunea de a crea programări');
        }
        $patient_id = intval($_POST['patient_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $appointment_date = sanitize_text_field($_POST['appointment_date']);
        $appointment_time = sanitize_text_field($_POST['appointment_time']);
        $type = sanitize_text_field($_POST['type']);
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $duration = intval($_POST['duration']);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (get_current_user_id() !== $patient_id && !Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error('Nu puteți crea programări pentru alt utilizator');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error('Tabela programări nu există');
        }

        // Limită: max 1 programare/24h per pacient (pe baza momentului creării) pentru programări active
        // Permitem o nouă programare dacă cea anterioară a fost anulată
        $nowMysql = current_time('mysql');
        $has_recent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE patient_id = %d AND status IN ('scheduled','confirmed') AND created_at >= DATE_SUB(%s, INTERVAL 24 HOUR)",
            $patient_id,
            $nowMysql
        ));
        if ($has_recent > 0) {
            wp_send_json_error('Puteți face cel mult o programare la fiecare 24 de ore.');
        }

        // Verifică conflictul cu programările existente pentru doctor și pacient
        $slotStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . $appointment_time);
        if (!$slotStart) { wp_send_json_error('Dată/oră invalide'); }
        $slotEnd = (clone $slotStart)->modify('+' . $duration . ' minutes');

        // Conflicte pentru doctor
        $existing_doctor = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time, duration FROM $table_name WHERE doctor_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')",
            $doctor_id, $appointment_date
        ));
        foreach ($existing_doctor as $ex) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . $ex->appointment_time);
            $exEnd = (clone $exStart)->modify('+' . (int)$ex->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) {
                wp_send_json_error('Intervalul selectat este ocupat pentru medic.');
            }
        }
        // Conflicte pentru pacient
        $existing_patient = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time, duration FROM $table_name WHERE patient_id = %d AND appointment_date = %s AND status IN ('scheduled','confirmed','completed')",
            $patient_id, $appointment_date
        ));
        foreach ($existing_patient as $ex) {
            $exStart = DateTime::createFromFormat('Y-m-d H:i', $appointment_date . ' ' . $ex->appointment_time);
            $exEnd = (clone $exStart)->modify('+' . (int)$ex->duration . ' minutes');
            if ($slotStart < $exEnd && $exStart < $slotEnd) {
                wp_send_json_error('Aveți deja o programare în acest interval.');
            }
        }

        // Completează tipul din service_id dacă lipsește
        if (empty($type) && $service_id > 0) {
            $resolved = $this->get_service_name_by_id($service_id);
            if (!empty($resolved)) { $type = $resolved; }
        }

        $result = $wpdb->insert($table_name, array(
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'duration' => $duration,
            'type' => $type,
            'status' => 'confirmed',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'notes' => $notes
        ));

        if ($result === false) {
            wp_send_json_error('Eroare la creare programare');
        }
        // Notificări email
        $patient_user = get_userdata($patient_id);
        $doctor_user = get_userdata($doctor_id);
        $data = array(
            'type' => $type,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'duration' => $duration,
            'patient_name' => $patient_user ? $patient_user->display_name : '',
            'patient_email' => $patient_user ? $patient_user->user_email : '',
            'doctor_name' => $doctor_user ? $doctor_user->display_name : '',
            'doctor_email' => $doctor_user ? $doctor_user->user_email : ''
        );
        $this->send_appointment_notifications('created', $data);
        wp_send_json_success(array('id' => $wpdb->insert_id));
    }

    public function ajax_get_booking_patients() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        $current_user_id = get_current_user_id();
        if (!$current_user_id) { wp_send_json_error('Neautorizat'); }

        $list = array();
        $current_user = get_userdata($current_user_id);
        $list[] = array('id' => $current_user_id, 'name' => ($current_user ? $current_user->display_name : 'Eu'));

        if (class_exists('Clinica_Family_Manager')) {
            $fm = new Clinica_Family_Manager();
            $family = $fm->get_patient_family($current_user_id);
            if ($family && !empty($family['id'])) {
                $members = $fm->get_family_members($family['id']);
                foreach ($members as $m) {
                    if ((int)$m->user_id === (int)$current_user_id) { continue; }
                    $list[] = array('id' => (int)$m->user_id, 'name' => $m->display_name);
                }
            }
        }
        wp_send_json_success($list);
    }

    public function ajax_get_services_catalog() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_services';
        $rows = $wpdb->get_results("SELECT id, name, duration FROM $table WHERE active = 1 ORDER BY name ASC");
        $result = array();
        foreach ($rows as $r) {
            $result[] = array('id' => (int)$r->id, 'name' => $r->name, 'duration' => (int)$r->duration);
        }
        wp_send_json_success($result);
    }

    /**
     * Traduce statusul programării în română
     */
    private function translate_status($status) {
        $translations = array(
            'scheduled' => 'Programată',
            'confirmed' => 'Acceptat', 
            'completed' => 'Completată',
            'cancelled' => 'Anulată',
            'no_show' => 'Nu s-a prezentat'
        );
        return isset($translations[$status]) ? $translations[$status] : $status;
    }

    /**
     * Extrage vârsta din CNP-ul din username
     */
    private function get_age_from_cnp($cnp) {
        if (empty($cnp) || strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return null;
        }
        
        // Extrage anul din CNP
        $year = intval(substr($cnp, 1, 2));
        $month = intval(substr($cnp, 3, 2));
        $day = intval(substr($cnp, 5, 2));
        
        // Determină secolul
        $century = intval(substr($cnp, 0, 1));
        if ($century <= 2) {
            $full_year = 1900 + $year;
        } else {
            $full_year = 2000 + $year;
        }
        
        // Calculează vârsta
        $birth_date = new DateTime($full_year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $day));
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        
        return $age;
    }

    /**
     * Returnează denumirea serviciului după ID, folosind tabela clinica_services
     */
    private function get_service_name_by_id($service_id) {
        if (empty($service_id)) {
            return '';
        }
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_services';
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $table WHERE id = %d", intval($service_id)));
        return $name ? $name : (string)$service_id;
    }

    /**
     * Returnează denumirea serviciului după durată (fallback când lipsește tipul)
     * Dacă există mai multe servicii cu aceeași durată, ia primul activ alfabetic.
     */
    private function get_service_name_by_duration($duration) {
        if (empty($duration)) { return ''; }
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_services';
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $table WHERE duration = %d AND active = 1 ORDER BY name ASC LIMIT 1", intval($duration)));
        return $name ? $name : '';
    }

    /**
     * Construiește antetele pentru wp_mail, inclusiv From și Content-Type
     */
    private function build_email_headers() {
        $settings = Clinica_Settings::get_instance();
        $from_name = $settings->get('email_from_name', 'Clinica Medicală');
        $from_email = $settings->get('email_from_address', get_option('admin_email'));
        $headers = array();
        if (!empty($from_email)) {
            $headers[] = 'From: ' . esc_html($from_name) . ' <' . sanitize_email($from_email) . '>';
        }
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return $headers;
    }

    /**
     * Trimite emailuri de notificare pentru programări (creare/anulare) către pacient și medic
     */
    private function send_appointment_notifications($action, $data) {
        $settings = Clinica_Settings::get_instance();
        $enabled = $settings->get('notifications_enabled', true);
        if (!$enabled) { return; }

        $headers = $this->build_email_headers();

        $service_label = $this->get_service_name_by_id($data['type'] ?? '');
        $date = esc_html($data['appointment_date'] ?? '');
        $time = esc_html($data['appointment_time'] ?? '');
        $duration = intval($data['duration'] ?? 0);
        $doctor_name = esc_html($data['doctor_name'] ?? '');
        $patient_name = esc_html($data['patient_name'] ?? '');

        if ($action === 'created') {
            $subject_patient = 'Confirmare programare: ' . ($service_label ?: 'Serviciu') . ' - ' . $date . ' ' . $time;
            $subject_doctor = 'Programare nouă: ' . $patient_name . ' - ' . $date . ' ' . $time;
            $body = '<p>Bună,</p>' .
                '<p>Programarea a fost înregistrată cu succes.</p>' .
                '<ul>' .
                '<li><strong>Serviciu:</strong> ' . esc_html($service_label) . '</li>' .
                '<li><strong>Doctor:</strong> ' . $doctor_name . '</li>' .
                '<li><strong>Data:</strong> ' . $date . '</li>' .
                '<li><strong>Ora:</strong> ' . $time . '</li>' .
                ($duration ? ('<li><strong>Durată:</strong> ' . $duration . ' min</li>') : '') .
                '<li><strong>Pacient:</strong> ' . $patient_name . '</li>' .
                '</ul>' .
                '<p>Vă mulțumim!</p>';
        } else {
            $subject_patient = 'Anulare programare: ' . ($service_label ?: 'Serviciu') . ' - ' . $date . ' ' . $time;
            $subject_doctor = 'Programare anulată: ' . $patient_name . ' - ' . $date . ' ' . $time;
            $body = '<p>Bună,</p>' .
                '<p>Programarea a fost anulată.</p>' .
                '<ul>' .
                '<li><strong>Serviciu:</strong> ' . esc_html($service_label) . '</li>' .
                '<li><strong>Doctor:</strong> ' . $doctor_name . '</li>' .
                '<li><strong>Data:</strong> ' . $date . '</li>' .
                '<li><strong>Ora:</strong> ' . $time . '</li>' .
                '<li><strong>Pacient:</strong> ' . $patient_name . '</li>' .
                '</ul>' .
                '<p>Vă mulțumim!</p>';
        }

        // Trimite DOAR către pacient, dacă are o adresă VALIDĂ
        if (!empty($data['patient_email']) && function_exists('is_email') && is_email($data['patient_email'])) {
            @wp_mail($data['patient_email'], $subject_patient, $body, $headers);
        }
    }

    public function ajax_get_appointment() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) { wp_send_json_error('ID invalid'); }
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_appointments';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$row) { wp_send_json_error('Programarea nu există'); }
        // permisiune: doar propriile programări
        if ((int)$row->patient_id !== (int)get_current_user_id() && !Clinica_Patient_Permissions::can_manage_appointments()) {
            wp_send_json_error('Acces interzis');
        }
        $doctor = get_userdata($row->doctor_id);
        $typeVal = $row->type;
        if (ctype_digit((string)$typeVal)) { $typeVal = $this->get_service_name_by_id((int)$typeVal); }
        // Mapare enum vechi la etichete
        $legacyMap = array(
            'consultation' => 'Consultație',
            'examination'  => 'Examinare',
            'procedure'    => 'Procedură',
            'follow_up'    => 'Control'
        );
        if (isset($legacyMap[$typeVal])) { $typeVal = $legacyMap[$typeVal]; }
        // Fallback: încearcă să deduci după durată când tipul lipsește
        if (empty($typeVal)) {
            $byDuration = $this->get_service_name_by_duration((int)$row->duration);
            if (!empty($byDuration)) { $typeVal = $byDuration; }
        }
        // Obține informații despre pacient
        $patient = get_userdata($row->patient_id);
        $patient_info = '';
        if ($patient) {
            $patient_name = $patient->display_name;
            $patient_cnp = $patient->user_login; // CNP-ul este în username
            $patient_age = $this->get_age_from_cnp($patient_cnp);
            
            $patient_info = '<p><strong>Pacient:</strong> ' . esc_html($patient_name);
            if ($patient_age !== null) {
                $patient_info .= ' (' . $patient_age . ' ani)';
            }
            $patient_info .= '</p>';
        }
        
        $html = '<div class="appointment-modal-content">'
            .'<h3>'.esc_html($this->format_date($row->appointment_date)).' • '.esc_html(substr($row->appointment_time,0,5)).'</h3>'
            .$patient_info
            .'<p><strong>Doctor:</strong> '.esc_html($doctor ? $doctor->display_name : '').'</p>'
            .'<p><strong>Tip:</strong> '.esc_html($typeVal ?: '-').'</p>'
            .'<p><strong>Durată:</strong> '.intval($row->duration).' min</p>'
            .'<p><strong>Interval:</strong> '.esc_html(substr($row->appointment_time,0,5)).' - '.esc_html(date('H:i', strtotime($row->appointment_time) + 60*max(0,(int)$row->duration))).'</p>'
            .'<p><strong>Observații:</strong> '.esc_html($row->notes ?: '—').'</p>'
            .'<p><strong>Status:</strong> '.esc_html($this->translate_status($row->status)).'</p>'
            .'</div>';
        wp_send_json_success($html);
    }
    
    /**
     * Verifică și actualizează statusul programării după editare
     */
    private function check_and_update_appointment_status_after_edit($appointment_id, $appointment_date, $appointment_time) {
        global $wpdb;
        
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        
        // Obține programarea actualizată
        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT id, appointment_date, appointment_time, status, duration
            FROM $table_appointments 
            WHERE id = %d
        ", $appointment_id));
        
        if (!$appointment) {
            return;
        }
        
        // Verifică dacă programarea are status 'confirmed' și dacă a trecut de 30+ minute de la sfârșitul programării
        if ($appointment->status === 'confirmed') {
            $current_time = current_time('mysql');
            
            // Calculează sfârșitul programării (ora + durata reală)
            $appointment_start = $appointment->appointment_date . ' ' . $appointment->appointment_time;
            $duration = $appointment->duration ?: 30; // Durata din baza de date sau 30 min implicit
            $appointment_end = date('Y-m-d H:i:s', strtotime($appointment_start . " +{$duration} minutes"));
            
            // Verifică dacă au trecut 30+ minute de la sfârșitul programării
            $result = $wpdb->get_var($wpdb->prepare("
                SELECT 1 FROM $table_appointments 
                WHERE id = %d 
                AND %s < DATE_SUB(%s, INTERVAL 30 MINUTE)
            ", $appointment_id, $appointment_end, $current_time));
            
            if ($result) {
                // Actualizează statusul la 'completed'
                $wpdb->update(
                    $table_appointments,
                    array(
                        'status' => 'completed',
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $appointment_id)
                );
                
                // Log pentru audit
                $plugin_root = dirname(dirname(__FILE__));
                if (!file_exists($plugin_root . '/logs')) { 
                    @mkdir($plugin_root . '/logs', 0755, true); 
                }
                $line = sprintf("[%s] AUTO_UPDATE_APPOINTMENT_AFTER_EDIT id=%d date=%s time=%s status=completed\n",
                    current_time('mysql'), 
                    (int)$appointment_id, 
                    $appointment->appointment_date, 
                    $appointment->appointment_time
                );
                @file_put_contents($plugin_root . '/logs/appointment-audit.log', $line, FILE_APPEND);
                
                error_log("[CLINICA] Auto-updated appointment ID $appointment_id from 'confirmed' to 'completed' after edit");
            }
        }
    }
    
    /**
     * AJAX: Obține sărbătorile legale românești pentru un an
     */
    public function ajax_get_romanian_holidays() {
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_dashboard_nonce')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        if ($year < 2020 || $year > 2030) {
            wp_send_json_error('An invalid');
        }
        
        // Obține sărbătorile din clasa noastră
        $holidays = Clinica_Romanian_Holidays::get_holidays($year);
        
        // Creează array cu date și nume
        $holidays_with_names = array(
            'dates' => $holidays,
            'names' => array()
        );
        
        // Adaugă numele pentru fiecare sărbătoare
        foreach ($holidays as $holiday_date) {
            $holiday_info = Clinica_Romanian_Holidays::get_holiday_info($holiday_date);
            if ($holiday_info && isset($holiday_info['name'])) {
                $holidays_with_names['names'][$holiday_date] = $holiday_info['name'];
            } else {
                $holidays_with_names['names'][$holiday_date] = 'Sărbătoare legală';
            }
        }
        
        wp_send_json_success($holidays_with_names);
    }
    
    /**
     * Obține numele doctorului după ID
     */
    private function get_doctor_name_by_id($doctor_id) {
        $doctor = get_userdata($doctor_id);
        return $doctor ? $doctor->display_name : 'Doctor necunoscut';
    }
    
    /**
     * Trimite email de notificare pentru transfer
     */
    private function send_transfer_notification_email($appointment, $new_doctor_id, $new_date, $new_time, $transfer_notes) {
        // Obține informațiile pacientului
        $patient = get_userdata($appointment->patient_id);
        if (!$patient) {
            return false;
        }
        
        // Obține informațiile noului doctor
        $new_doctor = get_userdata($new_doctor_id);
        if (!$new_doctor) {
            return false;
        }
        
        // Obține informațiile doctorului vechi
        $old_doctor = get_userdata($appointment->doctor_id);
        
        // Pregătește subiectul
        $subject = sprintf(
            __('Programarea a fost transferată - %s', 'clinica'),
            $this->format_date($new_date)
        );
        
        // Pregătește conținutul email-ului
        $message = sprintf(
            __("Bună ziua %s,\n\nProgramarea dumneavoastră a fost transferată cu următoarele detalii:\n\n", 'clinica'),
            $patient->display_name
        );
        
        $message .= sprintf(__("Data nouă: %s\n", 'clinica'), $this->format_date($new_date));
        $message .= sprintf(__("Ora nouă: %s\n", 'clinica'), $new_time);
        $message .= sprintf(__("Doctor nou: %s\n", 'clinica'), $new_doctor->display_name);
        
        if ($old_doctor) {
            $message .= sprintf(__("Doctor anterior: %s\n", 'clinica'), $old_doctor->display_name);
        }
        
        if (!empty($transfer_notes)) {
            $message .= sprintf(__("Observații: %s\n", 'clinica'), $transfer_notes);
        }
        
        $message .= __("\nVă rugăm să contactați clinica dacă aveți întrebări.\n\nCu respect,\nEchipa Clinică", 'clinica');
        
        // Trimite email-ul
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        return wp_mail($patient->user_email, $subject, $message, $headers);
    }
    
    /**
     * Verifică dacă doctorul este disponibil într-o anumită zi
     */
    private function is_doctor_available_on_date($doctor_id, $date, $service_id = 0) {
        // Obține programul de lucru al doctorului
        $doctor_schedule = get_user_meta($doctor_id, 'clinica_working_hours', true);
        if (is_string($doctor_schedule)) { 
            $doctor_schedule = json_decode($doctor_schedule, true); 
        }
        
        if (!is_array($doctor_schedule) || empty($doctor_schedule)) {
            $settings = Clinica_Settings::get_instance();
            $doctor_schedule = $settings->get('working_hours', array());
            if (is_string($doctor_schedule)) { 
                $doctor_schedule = json_decode($doctor_schedule, true); 
            }
        }

        // Verifică sărbătorile legale românești
        $is_legal_holiday = Clinica_Romanian_Holidays::is_holiday($date);
        if ($is_legal_holiday) {
            return false;
        }

        // Verifică sărbătorile clinicii
        $settings = Clinica_Settings::get_instance();
        $holidays = $settings->get('clinic_holidays', array());
        if (is_string($holidays)) { 
            $holidays = json_decode($holidays, true); 
        }
        $holidays = is_array($holidays) ? $holidays : array();
        
        if (in_array($date, $holidays, true)) {
            return false;
        }

        // Obține ziua săptămânii
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$date_obj) {
            return false;
        }
        
        $dow = strtolower($date_obj->format('l')); // monday, tuesday, etc.
        
        // Verifică programul de lucru general
        $has_working_hours = isset($doctor_schedule[$dow]) && 
                            !empty($doctor_schedule[$dow]['active']) && 
                            !empty($doctor_schedule[$dow]['start']) && 
                            !empty($doctor_schedule[$dow]['end']);

        // Dacă serviciul are timeslots specifice, verifică-le
        if ($service_id > 0) {
            global $wpdb;
            $timeslots_table = $wpdb->prefix . 'clinica_doctor_timeslots';
            $service_timeslots = $wpdb->get_results($wpdb->prepare(
                "SELECT COUNT(*) as count FROM $timeslots_table WHERE doctor_id = %d AND service_id = %d AND day_of_week = %d AND is_active = 1",
                $doctor_id, $service_id, $date_obj->format('N') // N = 1 (Luni) până la 7 (Duminică)
            ));
            
            if ($service_timeslots && $service_timeslots[0]->count > 0) {
                // Serviciul are timeslots specifice pentru această zi
                return true;
            }
        }

        return $has_working_hours;
    }
}

// Inițializează dashboard-ul pacient
new Clinica_Patient_Dashboard(); 