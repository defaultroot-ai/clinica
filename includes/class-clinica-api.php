<?php
/**
 * API REST pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_API {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Înregistrează rutele API
     */
    public function register_routes() {
        // Endpoint pentru pacienți
        register_rest_route('clinica/v1', '/patients', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_patients'),
                'permission_callback' => array($this, 'check_patients_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'search' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'cnp' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'phone' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_patient'),
                'permission_callback' => array($this, 'check_create_patient_permission'),
            ),
        ));
        
        // Endpoint pentru un pacient specific
        register_rest_route('clinica/v1', '/patients/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_patient'),
                'permission_callback' => array($this, 'check_patient_permission'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_patient'),
                'permission_callback' => array($this, 'check_edit_patient_permission'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_patient'),
                'permission_callback' => array($this, 'check_delete_patient_permission'),
            ),
        ));
        
        // Endpoint pentru programări
        register_rest_route('clinica/v1', '/appointments', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_appointments'),
                'permission_callback' => array($this, 'check_appointments_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'patient_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'doctor_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'date' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_appointment'),
                'permission_callback' => array($this, 'check_create_appointment_permission'),
            ),
        ));
        
        // Endpoint pentru un programare specifică
        register_rest_route('clinica/v1', '/appointments/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_appointment'),
                'permission_callback' => array($this, 'check_appointment_permission'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_appointment'),
                'permission_callback' => array($this, 'check_edit_appointment_permission'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_appointment'),
                'permission_callback' => array($this, 'check_delete_appointment_permission'),
            ),
        ));
        
        // Endpoint pentru statistici
        register_rest_route('clinica/v1', '/stats', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_stats_permission'),
            ),
        ));
        
        // Endpoint pentru validare CNP
        register_rest_route('clinica/v1', '/validate-cnp', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'validate_cnp'),
                'permission_callback' => '__return_true',
            ),
        ));
    }
    
    /**
     * Verifică permisiunea pentru vizualizarea pacienților
     */
    public function check_patients_permission($request) {
        return Clinica_Patient_Permissions::can_view_patients();
    }
    
    /**
     * Verifică permisiunea pentru crearea pacienților
     */
    public function check_create_patient_permission($request) {
        return Clinica_Patient_Permissions::can_create_patient();
    }
    
    /**
     * Verifică permisiunea pentru un pacient specific
     */
    public function check_patient_permission($request) {
        $patient_id = $request['id'];
        return Clinica_Patient_Permissions::can_view_patient($patient_id);
    }
    
    /**
     * Verifică permisiunea pentru editarea pacienților
     */
    public function check_edit_patient_permission($request) {
        $patient_id = $request['id'];
        return Clinica_Patient_Permissions::can_edit_patient_profile($patient_id);
    }
    
    /**
     * Verifică permisiunea pentru ștergerea pacienților
     */
    public function check_delete_patient_permission($request) {
        return Clinica_Patient_Permissions::can_delete_patient();
    }
    
    /**
     * Verifică permisiunea pentru vizualizarea programărilor
     */
    public function check_appointments_permission($request) {
        return Clinica_Patient_Permissions::can_view_appointments();
    }
    
    /**
     * Verifică permisiunea pentru crearea programărilor
     */
    public function check_create_appointment_permission($request) {
        return Clinica_Patient_Permissions::can_create_appointments();
    }
    
    /**
     * Verifică permisiunea pentru o programare specifică
     */
    public function check_appointment_permission($request) {
        return Clinica_Patient_Permissions::can_view_appointments();
    }
    
    /**
     * Verifică permisiunea pentru editarea programărilor
     */
    public function check_edit_appointment_permission($request) {
        return Clinica_Patient_Permissions::can_manage_appointments();
    }
    
    /**
     * Verifică permisiunea pentru ștergerea programărilor
     */
    public function check_delete_appointment_permission($request) {
        return Clinica_Patient_Permissions::can_manage_appointments();
    }
    
    /**
     * Verifică permisiunea pentru statistici
     */
    public function check_stats_permission($request) {
        return Clinica_Patient_Permissions::can_view_reports();
    }
    
    /**
     * Obține lista de pacienți
     */
    public function get_patients($request) {
        global $wpdb;
        
        $page = $request['page'];
        $per_page = $request['per_page'];
        $search = $request['search'];
        $cnp = $request['cnp'];
        $phone = $request['phone'];
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(p.cnp LIKE %s OR u.first_name LIKE %s OR u.last_name LIKE %s OR u.user_email LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($cnp)) {
            $where_conditions[] = "p.cnp = %s";
            $where_values[] = $cnp;
        }
        
        if (!empty($phone)) {
            $where_conditions[] = "(p.phone_primary = %s OR p.phone_secondary = %s)";
            $where_values[] = $phone;
            $where_values[] = $phone;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Numărul total de pacienți
        $total_query = "SELECT COUNT(*) FROM $table_name p 
                       LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                       $where_clause";
        
        if (!empty($where_values)) {
            $total_query = $wpdb->prepare($total_query, $where_values);
        }
        
        $total = $wpdb->get_var($total_query);
        
        // Lista de pacienți
        $query = "SELECT p.*, u.user_email, u.first_name, u.last_name, u.display_name 
                  FROM $table_name p 
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                  $where_clause 
                  ORDER BY p.created_at DESC 
                  LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        $patients = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        // Formatează datele pentru răspuns
        $formatted_patients = array();
        foreach ($patients as $patient) {
            $formatted_patients[] = $this->format_patient_data($patient);
        }
        
        return new WP_REST_Response(array(
            'patients' => $formatted_patients,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ), 200);
    }
    
    /**
     * Obține un pacient specific
     */
    public function get_patient($request) {
        global $wpdb;
        
        $patient_id = $request['id'];
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, u.user_email, u.first_name, u.last_name, u.display_name 
             FROM $table_name p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE p.user_id = %d",
            $patient_id
        ));
        
        if (!$patient) {
            return new WP_Error('patient_not_found', 'Pacientul nu a fost găsit', array('status' => 404));
        }
        
        return new WP_REST_Response($this->format_patient_data($patient), 200);
    }
    
    /**
     * Creează un pacient nou
     */
    public function create_patient($request) {
        $params = $request->get_params();
        
        // Validează datele
        $validation = $this->validate_patient_data($params);
        if (!$validation['valid']) {
            return new WP_Error('invalid_data', $validation['message'], array('status' => 400));
        }
        
        // Creează pacientul
        $form = new Clinica_Patient_Creation_Form();
        $result = $form->create_patient($params);
        
        if ($result['success']) {
            return new WP_REST_Response($result['data'], 201);
        } else {
            return new WP_Error('creation_failed', $result['message'], array('status' => 500));
        }
    }
    
    /**
     * Actualizează un pacient
     */
    public function update_patient($request) {
        $patient_id = $request['id'];
        $params = $request->get_params();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Verifică dacă pacientul există
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $patient_id
        ));
        
        if (!$existing) {
            return new WP_Error('patient_not_found', 'Pacientul nu a fost găsit', array('status' => 404));
        }
        
        // Actualizează datele
        $update_data = array();
        $allowed_fields = array('phone_primary', 'phone_secondary', 'address', 'emergency_contact', 'blood_type', 'allergies', 'medical_history');
        
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $update_data[$field] = sanitize_text_field($params[$field]);
            }
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $patient_id)
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', 'Eroare la actualizarea pacientului', array('status' => 500));
            }
        }
        
        // Actualizează și datele utilizatorului WordPress
        $user_data = array();
        if (isset($params['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($params['first_name']);
        }
        if (isset($params['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($params['last_name']);
        }
        if (isset($params['email'])) {
            $user_data['user_email'] = sanitize_email($params['email']);
        }
        
        if (!empty($user_data)) {
            $user_data['ID'] = $patient_id;
            $user_data['display_name'] = ($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '');
            
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                return new WP_Error('user_update_failed', $result->get_error_message(), array('status' => 500));
            }
        }
        
        return new WP_REST_Response(array('message' => 'Pacientul a fost actualizat cu succes'), 200);
    }
    
    /**
     * Șterge un pacient
     */
    public function delete_patient($request) {
        $patient_id = $request['id'];
        
        // Șterge utilizatorul WordPress (va șterge automat și din tabela pacienți datorită foreign key)
        $result = wp_delete_user($patient_id);
        
        if (!$result) {
            return new WP_Error('delete_failed', 'Eroare la ștergerea pacientului', array('status' => 500));
        }
        
        return new WP_REST_Response(array('message' => 'Pacientul a fost șters cu succes'), 200);
    }
    
    /**
     * Obține lista de programări
     */
    public function get_appointments($request) {
        global $wpdb;
        
        $page = $request['page'];
        $per_page = $request['per_page'];
        $patient_id = $request['patient_id'];
        $doctor_id = $request['doctor_id'];
        $date = $request['date'];
        $status = $request['status'];
        
        $table_name = $wpdb->prefix . 'clinica_appointments';
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($patient_id)) {
            $where_conditions[] = "patient_id = %d";
            $where_values[] = $patient_id;
        }
        
        if (!empty($doctor_id)) {
            $where_conditions[] = "doctor_id = %d";
            $where_values[] = $doctor_id;
        }
        
        if (!empty($date)) {
            $where_conditions[] = "appointment_date = %s";
            $where_values[] = $date;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $where_values[] = $status;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Numărul total de programări
        $total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        
        if (!empty($where_values)) {
            $total_query = $wpdb->prepare($total_query, $where_values);
        }
        
        $total = $wpdb->get_var($total_query);
        
        // Lista de programări
        $query = "SELECT a.*, 
                         p.first_name as patient_first_name, p.last_name as patient_last_name,
                         d.first_name as doctor_first_name, d.last_name as doctor_last_name
                  FROM $table_name a 
                  LEFT JOIN {$wpdb->users} p ON a.patient_id = p.ID 
                  LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID 
                  $where_clause 
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                  LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        $appointments = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        // Formatează datele pentru răspuns
        $formatted_appointments = array();
        foreach ($appointments as $appointment) {
            $formatted_appointments[] = $this->format_appointment_data($appointment);
        }
        
        return new WP_REST_Response(array(
            'appointments' => $formatted_appointments,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ), 200);
    }
    
    /**
     * Obține o programare specifică
     */
    public function get_appointment($request) {
        global $wpdb;
        
        $appointment_id = $request['id'];
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, 
                    p.first_name as patient_first_name, p.last_name as patient_last_name,
                    d.first_name as doctor_first_name, d.last_name as doctor_last_name
             FROM $table_name a 
             LEFT JOIN {$wpdb->users} p ON a.patient_id = p.ID 
             LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID 
             WHERE a.id = %d",
            $appointment_id
        ));
        
        if (!$appointment) {
            return new WP_Error('appointment_not_found', 'Programarea nu a fost găsită', array('status' => 404));
        }
        
        return new WP_REST_Response($this->format_appointment_data($appointment), 200);
    }
    
    /**
     * Creează o programare nouă
     */
    public function create_appointment($request) {
        $params = $request->get_params();
        
        // Validează datele
        $validation = $this->validate_appointment_data($params);
        if (!$validation['valid']) {
            return new WP_Error('invalid_data', $validation['message'], array('status' => 400));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        $appointment_data = array(
            'patient_id' => intval($params['patient_id']),
            'doctor_id' => intval($params['doctor_id']),
            'appointment_date' => sanitize_text_field($params['appointment_date']),
            'appointment_time' => sanitize_text_field($params['appointment_time']),
            'duration' => intval($params['duration'] ?? 30),
            'type' => sanitize_text_field($params['type'] ?? 'consultation'),
            'notes' => sanitize_textarea_field($params['notes'] ?? ''),
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table_name, $appointment_data);
        
        if ($result === false) {
            return new WP_Error('creation_failed', 'Eroare la crearea programării', array('status' => 500));
        }
        
        $appointment_id = $wpdb->insert_id;
        
        return new WP_REST_Response(array(
            'id' => $appointment_id,
            'message' => 'Programarea a fost creată cu succes'
        ), 201);
    }
    
    /**
     * Actualizează o programare
     */
    public function update_appointment($request) {
        $appointment_id = $request['id'];
        $params = $request->get_params();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        // Verifică dacă programarea există
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $appointment_id
        ));
        
        if (!$existing) {
            return new WP_Error('appointment_not_found', 'Programarea nu a fost găsită', array('status' => 404));
        }
        
        // Actualizează datele
        $update_data = array();
        $allowed_fields = array('appointment_date', 'appointment_time', 'duration', 'status', 'type', 'notes');
        
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $update_data[$field] = sanitize_text_field($params[$field]);
            }
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $appointment_id)
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', 'Eroare la actualizarea programării', array('status' => 500));
            }
        }
        
        return new WP_REST_Response(array('message' => 'Programarea a fost actualizată cu succes'), 200);
    }
    
    /**
     * Șterge o programare
     */
    public function delete_appointment($request) {
        $appointment_id = $request['id'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clinica_appointments';
        
        $result = $wpdb->delete($table_name, array('id' => $appointment_id));
        
        if (!$result) {
            return new WP_Error('delete_failed', 'Eroare la ștergerea programării', array('status' => 500));
        }
        
        return new WP_REST_Response(array('message' => 'Programarea a fost ștearsă cu succes'), 200);
    }
    
    /**
     * Obține statistici
     */
    public function get_stats($request) {
        $stats = Clinica_Database::get_database_stats();
        
        return new WP_REST_Response($stats, 200);
    }
    
    /**
     * Validează CNP
     */
    public function validate_cnp($request) {
        $params = $request->get_params();
        $cnp = sanitize_text_field($params['cnp'] ?? '');
        
        if (empty($cnp)) {
            return new WP_Error('missing_cnp', 'CNP-ul este obligatoriu', array('status' => 400));
        }
        
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        if ($result['valid']) {
            $parser = new Clinica_CNP_Parser();
            $result['parsed_data'] = $parser->parse_cnp($cnp);
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Formatează datele pacientului pentru răspuns
     */
    private function format_patient_data($patient) {
        return array(
            'id' => $patient->user_id,
            'cnp' => $patient->cnp,
            'cnp_type' => $patient->cnp_type,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'display_name' => $patient->display_name,
            'email' => $patient->user_email,
            'phone_primary' => $patient->phone_primary,
            'phone_secondary' => $patient->phone_secondary,
            'birth_date' => $patient->birth_date,
            'gender' => $patient->gender,
            'age' => $patient->age,
            'address' => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'blood_type' => $patient->blood_type,
            'allergies' => $patient->allergies,
            'medical_history' => $patient->medical_history,
            'created_at' => $patient->created_at,
            'updated_at' => $patient->updated_at
        );
    }
    
    /**
     * Formatează datele programării pentru răspuns
     */
    private function format_appointment_data($appointment) {
        return array(
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'patient_name' => $appointment->patient_first_name . ' ' . $appointment->patient_last_name,
            'doctor_id' => $appointment->doctor_id,
            'doctor_name' => $appointment->doctor_first_name . ' ' . $appointment->doctor_last_name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'duration' => $appointment->duration,
            'status' => $appointment->status,
            'type' => $appointment->type,
            'notes' => $appointment->notes,
            'created_at' => $appointment->created_at,
            'updated_at' => $appointment->updated_at
        );
    }
    
    /**
     * Validează datele pacientului
     */
    private function validate_patient_data($data) {
        if (empty($data['cnp'])) {
            return array('valid' => false, 'message' => 'CNP-ul este obligatoriu');
        }
        
        if (empty($data['first_name'])) {
            return array('valid' => false, 'message' => 'Prenumele este obligatoriu');
        }
        
        if (empty($data['last_name'])) {
            return array('valid' => false, 'message' => 'Numele este obligatoriu');
        }
        
        if (empty($data['phone_primary'])) {
            return array('valid' => false, 'message' => 'Numărul de telefon principal este obligatoriu');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Validează datele programării
     */
    private function validate_appointment_data($data) {
        if (empty($data['patient_id'])) {
            return array('valid' => false, 'message' => 'ID-ul pacientului este obligatoriu');
        }
        
        if (empty($data['doctor_id'])) {
            return array('valid' => false, 'message' => 'ID-ul doctorului este obligatoriu');
        }
        
        if (empty($data['appointment_date'])) {
            return array('valid' => false, 'message' => 'Data programării este obligatorie');
        }
        
        if (empty($data['appointment_time'])) {
            return array('valid' => false, 'message' => 'Ora programării este obligatorie');
        }
        
        return array('valid' => true);
    }
}

// Inițializează API-ul
new Clinica_API(); 