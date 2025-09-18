<?php
/**
 * Formular de creare pacienți pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Patient_Creation_Form {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_clinica_create_patient', array($this, 'ajax_create_patient'));
        add_action('wp_ajax_nopriv_clinica_create_patient', array($this, 'ajax_create_patient'));
        add_action('wp_ajax_clinica_validate_cnp', array($this, 'ajax_validate_cnp'));
        add_action('wp_ajax_nopriv_clinica_validate_cnp', array($this, 'ajax_validate_cnp'));
        add_action('wp_ajax_clinica_check_cnp_exists', array($this, 'ajax_check_cnp_exists'));
        add_action('wp_ajax_nopriv_clinica_check_cnp_exists', array($this, 'ajax_check_cnp_exists'));
        add_action('wp_ajax_clinica_generate_password', array($this, 'ajax_generate_password'));
        add_action('wp_ajax_nopriv_clinica_generate_password', array($this, 'ajax_generate_password'));
    }
    
    /**
     * AJAX pentru crearea unui pacient
     */
    public function ajax_create_patient() {
        // Accept both clinica_create_patient and clinica_nonce
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_create_patient')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
                $nonce_valid = true;
            }
        }
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
        }
        
        // Verifică permisiunile
        if (!Clinica_Patient_Permissions::can_create_patient()) {
            wp_send_json_error('Nu aveți permisiunea de a crea pacienți');
        }
        
        $data = $this->sanitize_patient_data($_POST);
        
        // Validează datele
        $validation = $this->validate_patient_data($data);
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // Creează pacientul
        $result = $this->create_patient($data);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX pentru validarea CNP
     */
    public function ajax_validate_cnp() {
        
        // Verifică nonce-ul pentru admin sau frontend
        $nonce_valid = false;
        
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_validate_cnp')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
            return;
        }
        
        $cnp = sanitize_text_field($_POST['cnp']);
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
            return;
        }
        
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        if ($result['valid']) {
            // Parsează CNP-ul pentru a obține informații
            $parser = new Clinica_CNP_Parser();
            $parsed_data = $parser->parse_cnp($cnp);
            
            $result['parsed_data'] = $parsed_data;
            
            // Returnează succes cu datele parsate
            wp_send_json_success($result);
        } else {
            // Returnează eroare cu mesajul
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX pentru verificarea existenței CNP-ului
     */
    public function ajax_check_cnp_exists() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_check_cnp_exists')) {
            wp_send_json_error('Eroare de securitate');
            return;
        }
        
        $cnp = sanitize_text_field($_POST['cnp']);
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
            return;
        }
        
        // Verifică dacă CNP-ul există
        $exists = $this->cnp_exists($cnp);
        
        wp_send_json_success(array('exists' => $exists));
    }
    
    /**
     * AJAX pentru generarea parolei
     */
    public function ajax_generate_password() {
        // Verifică nonce-ul pentru admin sau frontend
        $nonce_valid = false;
        
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'clinica_generate_password')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error('Eroare de securitate');
            return;
        }
        
        $cnp = sanitize_text_field($_POST['cnp']);
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';
        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'cnp';
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
            return;
        }
        
        $password_generator = new Clinica_Password_Generator();
        $password = $password_generator->generate_password($cnp, $birth_date, $method);
        
        wp_send_json_success(array('password' => $password));
    }
    
    /**
     * Sanitizează datele pacientului
     */
    private function sanitize_patient_data($data) {
        // Convertește etichetele înapoi la valori pentru CNP type și gender
        $cnp_type_label = sanitize_text_field($data['cnp_type'] ?? '');
        $gender_label = sanitize_text_field($data['gender'] ?? '');
        
        $cnp_type = $this->convert_cnp_type_label_to_value($cnp_type_label);
        $gender = $this->convert_gender_label_to_value($gender_label);
        
        return array(
            'cnp' => sanitize_text_field($data['cnp'] ?? ''),
            'cnp_type' => $cnp_type,
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone_primary' => sanitize_text_field($data['phone_primary'] ?? ''),
            'phone_secondary' => sanitize_text_field($data['phone_secondary'] ?? ''),
            'birth_date' => sanitize_text_field($data['birth_date'] ?? ''),
            'gender' => $gender,
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'emergency_contact' => sanitize_text_field($data['emergency_contact'] ?? ''),
            'blood_type' => sanitize_text_field($data['blood_type'] ?? ''),
            'allergies' => sanitize_textarea_field($data['allergies'] ?? ''),
            'medical_history' => sanitize_textarea_field($data['medical_history'] ?? ''),
            'password_method' => sanitize_text_field($data['password_method'] ?? 'cnp'),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            // Câmpuri pentru familie
            'family_option' => sanitize_text_field($data['family_option'] ?? 'none'),
            'family_name' => sanitize_text_field($data['family_name'] ?? ''),
            'family_role' => sanitize_text_field($data['family_role'] ?? ''),
            'selected_family_id' => intval($data['selected_family_id'] ?? 0),
            'existing_family_role' => sanitize_text_field($data['existing_family_role'] ?? '')
        );
    }
    
    /**
     * Validează datele pacientului
     */
    private function validate_patient_data($data) {
        // Validare CNP
        if (empty($data['cnp'])) {
            return array('valid' => false, 'message' => 'CNP-ul este obligatoriu');
        }
        
        $validator = new Clinica_CNP_Validator();
        $cnp_validation = $validator->validate_cnp($data['cnp']);
        
        if (!$cnp_validation['valid']) {
            return array('valid' => false, 'message' => $cnp_validation['message']);
        }
        
        // Verifică dacă CNP-ul există deja
        if ($this->cnp_exists($data['cnp'])) {
            return array('valid' => false, 'message' => 'Un pacient cu acest CNP există deja');
        }
        
        // Validare nume
        if (empty($data['first_name'])) {
            return array('valid' => false, 'message' => 'Prenumele este obligatoriu');
        }
        
        if (empty($data['last_name'])) {
            return array('valid' => false, 'message' => 'Numele este obligatoriu');
        }
        
        // Validare email
        if (!empty($data['email']) && !is_email($data['email'])) {
            return array('valid' => false, 'message' => 'Adresa de email nu este validă');
        }
        
        // Verifică dacă email-ul există deja
        if (!empty($data['email']) && email_exists($data['email'])) {
            return array('valid' => false, 'message' => 'Un utilizator cu această adresă de email există deja');
        }
        
        // Validare telefon
        if (empty($data['phone_primary'])) {
            return array('valid' => false, 'message' => 'Numărul de telefon principal este obligatoriu');
        }
        
        // Verifică dacă telefonul există deja
        if ($this->phone_exists($data['phone_primary'])) {
            return array('valid' => false, 'message' => 'Un pacient cu acest număr de telefon există deja');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Verifică dacă CNP-ul există deja
     */
    private function cnp_exists($cnp) {
        global $wpdb;
        
        // Verifică în tabela pacienți
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $exists_in_patients = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE cnp = %s",
            $cnp
        ));
        
        // Verifică în tabela utilizatori WordPress (ca username)
        $exists_in_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login = %s",
            $cnp
        ));
        
        // Returnează true dacă există în oricare din tabele
        return ($exists_in_patients > 0) || ($exists_in_users > 0);
    }
    
    /**
     * Verifică dacă telefonul există deja
     */
    private function phone_exists($phone) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE phone_primary = %s OR phone_secondary = %s",
            $phone,
            $phone
        ));
        
        return $exists > 0;
    }
    
    /**
     * Convertește eticheta tipului CNP înapoi la valoarea corespunzătoare
     */
    private function convert_cnp_type_label_to_value($label) {
        switch ($label) {
            case 'Român':
                return 'romanian';
            case 'Străin cu reședință permanentă':
                return 'foreign_permanent';
            case 'Străin cu reședință temporară':
                return 'foreign_temporary';
            default:
                return 'romanian'; // default fallback
        }
    }
    
    /**
     * Convertește eticheta sexului înapoi la valoarea corespunzătoare
     */
    private function convert_gender_label_to_value($label) {
        switch ($label) {
            case 'Masculin':
                return 'male';
            case 'Feminin':
                return 'female';
            default:
                return ''; // empty fallback
        }
    }
    
    /**
     * Creează pacientul
     */
    private function create_patient($data) {
        global $wpdb;
        
        // Parsează CNP-ul pentru a obține informații
        $parser = new Clinica_CNP_Parser();
        $parsed_data = $parser->parse_cnp($data['cnp']);
        
        // Generează parola
        $password_generator = new Clinica_Password_Generator();
        $password = $password_generator->generate_password($data['cnp'], $data['birth_date'], $data['password_method']);
        
        // Normalizează numele înainte de salvare
        $normalized_first_name = Clinica_Database::normalize_name($data['first_name']);
        $normalized_last_name = Clinica_Database::normalize_name($data['last_name']);
        
        // Creează utilizatorul WordPress
        $user_data = array(
            'user_login' => $data['cnp'],
            'user_pass' => $password,
            'user_email' => $data['email'],
            'first_name' => $normalized_first_name,
            'last_name' => $normalized_last_name,
            'display_name' => $normalized_first_name . ' ' . $normalized_last_name,
            'role' => 'clinica_patient'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return array('success' => false, 'message' => $user_id->get_error_message());
        }
        
        // Salvează datele în tabela pacienți
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        // Procesează datele de familie
        $family_data = $this->process_family_data($data, $user_id);
        
        $patient_data = array(
            'user_id' => $user_id,
            'cnp' => $data['cnp'],
            'cnp_type' => $data['cnp_type'],
            'phone_primary' => $data['phone_primary'],
            'phone_secondary' => $data['phone_secondary'],
            'birth_date' => $data['birth_date'] ?: $parsed_data['birth_date'],
            'gender' => $data['gender'] ?: $parsed_data['gender'],
            'age' => $parsed_data['age'],
            'address' => $data['address'],
            'emergency_contact' => $data['emergency_contact'],
            'blood_type' => $data['blood_type'],
            'allergies' => $data['allergies'],
            'medical_history' => $data['medical_history'],
            'password_method' => $data['password_method'],
            'created_by' => get_current_user_id(),
            // Adaugă datele de familie
            'family_id' => $family_data['family_id'],
            'family_role' => $family_data['family_role'],
            'family_head_id' => $family_data['family_head_id'],
            'family_name' => $family_data['family_name']
        );
        
        $result = $wpdb->insert($table_name, $patient_data);
        
        if ($result === false) {
            // Șterge utilizatorul dacă inserarea în tabela pacienți a eșuat
            wp_delete_user($user_id);
            return array('success' => false, 'message' => 'Eroare la salvarea datelor pacientului');
        }
        
        // Salvează notele ca user meta
        if (!empty($data['notes'])) {
            update_user_meta($user_id, '_clinica_notes', $data['notes']);
        }
        
        // Salvează numerele de telefon ca user meta
        if (!empty($data['phone_primary'])) {
            update_user_meta($user_id, 'phone_primary', $data['phone_primary']);
        }
        if (!empty($data['phone_secondary'])) {
            update_user_meta($user_id, 'phone_secondary', $data['phone_secondary']);
        }
        
        // Log crearea pacientului
        $this->log_patient_creation($user_id, $data);
        
        // Actualizează datele de familie după salvarea pacientului
        $this->update_family_data_after_save($user_id, $family_data);
        
        return array(
            'success' => true,
            'data' => array(
                'user_id' => $user_id,
                'username' => $data['cnp'],
                'password' => $password,
                'patient_name' => $normalized_first_name . ' ' . $normalized_last_name,
                'message' => 'Pacientul a fost creat cu succes'
            )
        );
    }
    
    /**
     * Procesează datele de familie pentru pacient
     */
    private function process_family_data($data, $user_id) {
        $family_data = array(
            'family_id' => null,
            'family_role' => null,
            'family_head_id' => null,
            'family_name' => null
        );
        
        // Verifică dacă pacientul face parte dintr-o familie
        if (empty($data['family_option']) || $data['family_option'] === 'none') {
            return $family_data;
        }
        
        // Dacă creează o familie nouă, doar pregătește datele
        if ($data['family_option'] === 'new') {
            if (!empty($data['family_name']) && !empty($data['family_role'])) {
                $family_manager = new Clinica_Family_Manager();
                $result = $family_manager->create_family($data['family_name']);
                
                if ($result['success']) {
                    $family_data['family_id'] = $result['data']['family_id'];
                    $family_data['family_role'] = $data['family_role'];
                    $family_data['family_head_id'] = $user_id;
                    $family_data['family_name'] = $data['family_name'];
                }
            }
        }
        
        // Dacă adaugă la o familie existentă, doar pregătește datele
        if ($data['family_option'] === 'existing') {
            if (!empty($data['selected_family_id']) && !empty($data['existing_family_role'])) {
                $family_data['family_id'] = $data['selected_family_id'];
                $family_data['family_role'] = $data['existing_family_role'];
                $family_data['family_name'] = $data['selected_family_name'] ?? '';
            }
        }
        
        return $family_data;
    }
    
    /**
     * Actualizează datele de familie după ce pacientul este salvat
     */
    private function update_family_data_after_save($patient_id, $family_data) {
        if (empty($family_data['family_id'])) {
            return;
        }
        
        global $wpdb;
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $family_manager = new Clinica_Family_Manager();
        
        // Dacă este o familie nouă, actualizează capul familiei
        if ($family_data['family_role'] === 'head') {
            $family_manager->update_family_head(
                $family_data['family_id'],
                $patient_id,
                $family_data['family_name']
            );
        }
        
        // Dacă este un membru al unei familii existente, adaugă-l
        if ($family_data['family_role'] !== 'head' && !empty($family_data['family_id'])) {
            $family_manager->add_family_member(
                $patient_id,
                $family_data['family_id'],
                $family_data['family_role']
            );
        }
    }
    
    /**
     * Log crearea pacientului
     */
    private function log_patient_creation($user_id, $data) {
        $log_data = array(
            'action' => 'patient_created',
            'user_id' => $user_id,
            'created_by' => get_current_user_id(),
            'data' => array(
                'cnp' => $data['cnp'],
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone_primary']
            ),
            'timestamp' => current_time('mysql')
        );
        
        // Salvează log-ul
        $logs = get_option('clinica_patient_logs', array());
        $logs[] = $log_data;
        
        // Păstrează doar ultimele 1000 de log-uri
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option('clinica_patient_logs', $logs);
    }
    
    /**
     * Generează formularul HTML
     */
    public function render_form() {
        ob_start();
        ?>
        <div class="clinica-patient-form-container">
            <form id="clinica-patient-form" class="clinica-form">
                <?php wp_nonce_field('clinica_create_patient', 'clinica_nonce'); ?>
                
                <!-- Tab Navigation -->
                <div class="clinica-form-tabs">
                    <button type="button" class="clinica-tab-button active" data-tab="cnp">
                        <span class="tab-icon">📋</span>
                        <?php _e('CNP & Identitate', 'clinica'); ?>
                    </button>
                    <button type="button" class="clinica-tab-button" data-tab="personal">
                        <span class="tab-icon">👤</span>
                        <?php _e('Informații Personale', 'clinica'); ?>
                    </button>
                    <!-- Tab Medical - ASCUNS TEMPORAR -->
                    <!--
                    <button type="button" class="clinica-tab-button" data-tab="medical">
                        <span class="tab-icon">🏥</span>
                        <?php _e('Informații Medicale', 'clinica'); ?>
                    </button>
                    -->
                    <button type="button" class="clinica-tab-button" data-tab="family">
                        <span class="tab-icon">👨‍👩‍👧‍👦</span>
                        <?php _e('Familie', 'clinica'); ?>
                    </button>
                    <button type="button" class="clinica-tab-button" data-tab="account">
                        <span class="tab-icon">🔐</span>
                        <?php _e('Setări Cont', 'clinica'); ?>
                    </button>
                </div>
                
                <!-- Tab Content -->
                <div class="clinica-form-tab-content">
                    <!-- Tab 1: CNP & Identitate -->
                    <div class="clinica-tab-pane active" data-tab="cnp">
                        <div class="form-section">
                            <h3><?php _e('Informații CNP', 'clinica'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cnp"><?php _e('CNP *', 'clinica'); ?></label>
                                    <input type="text" id="cnp" name="cnp" maxlength="13" required>
                                    <div class="cnp-validation-message"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cnp_type"><?php _e('Tip CNP', 'clinica'); ?></label>
                                    <input type="text" id="cnp_type" readonly>
                                    <input type="hidden" id="cnp_type_value" name="cnp_type">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="birth_date"><?php _e('Data nașterii', 'clinica'); ?></label>
                                    <input type="date" id="birth_date" name="birth_date" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender"><?php _e('Sex', 'clinica'); ?></label>
                                    <input type="text" id="gender" readonly>
                                    <input type="hidden" id="gender_value" name="gender">
                                </div>
                                
                                <div class="form-group">
                                    <label for="age"><?php _e('Vârsta', 'clinica'); ?></label>
                                    <input type="number" id="age" name="age" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 2: Informații Personale -->
                    <div class="clinica-tab-pane" data-tab="personal">
                        <div class="form-section">
                            <h3><?php _e('Informații personale', 'clinica'); ?></h3>
                            
                                                         <div class="form-row">
                                 <div class="form-group">
                                     <label for="first_name"><?php _e('Prenume *', 'clinica'); ?></label>
                                     <input type="text" id="first_name" name="first_name" required>
                                 </div>
                                 
                                 <div class="form-group">
                                     <label for="last_name"><?php _e('Nume *', 'clinica'); ?></label>
                                     <input type="text" id="last_name" name="last_name" required>
                                 </div>
                             </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email"><?php _e('Email', 'clinica'); ?></label>
                                    <input type="email" id="email" name="email">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone_primary"><?php _e('Telefon principal *', 'clinica'); ?></label>
                                    <input type="tel" id="phone_primary" name="phone_primary" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone_secondary"><?php _e('Telefon secundar', 'clinica'); ?></label>
                                    <input type="tel" id="phone_secondary" name="phone_secondary">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address"><?php _e('Adresă', 'clinica'); ?></label>
                                <textarea id="address" name="address" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact"><?php _e('Contact de urgență', 'clinica'); ?></label>
                                <input type="tel" id="emergency_contact" name="emergency_contact">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 3: Informații Medicale - ASCUNS TEMPORAR -->
                    <!--
                    <div class="clinica-tab-pane" data-tab="medical">
                        <div class="form-section">
                            <h3><?php _e('Informații medicale', 'clinica'); ?></h3>
                            
                            <div class="form-group">
                                <label for="blood_type"><?php _e('Grupa sanguină', 'clinica'); ?></label>
                                <select id="blood_type" name="blood_type">
                                    <option value=""><?php _e('Selectează', 'clinica'); ?></option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="allergies"><?php _e('Alergii', 'clinica'); ?></label>
                                <textarea id="allergies" name="allergies" rows="3" placeholder="<?php _e('Descrieți alergiile cunoscute...', 'clinica'); ?>"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="medical_history"><?php _e('Istoric medical', 'clinica'); ?></label>
                                <textarea id="medical_history" name="medical_history" rows="4" placeholder="<?php _e('Descrieți istoricul medical relevant...', 'clinica'); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                    -->
                    
                    <!-- Tab 4: Familie -->
                    <div class="clinica-tab-pane" data-tab="family">
                        <div class="form-section">
                            <h3><?php _e('Informații familie', 'clinica'); ?></h3>
                            
                            <div class="form-group">
                                <label for="family_option"><?php _e('Opțiune familie', 'clinica'); ?></label>
                                <select id="family_option" name="family_option">
                                    <option value="none"><?php _e('Nu face parte dintr-o familie', 'clinica'); ?></option>
                                    <option value="new"><?php _e('Creează o familie nouă', 'clinica'); ?></option>
                                    <option value="existing"><?php _e('Adaugă la o familie existentă', 'clinica'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Opțiunea pentru familie nouă -->
                            <div id="new-family-section" class="family-section" style="display: none;">
                                <div class="form-group">
                                    <label for="family_name"><?php _e('Numele familiei *', 'clinica'); ?></label>
                                    <input type="text" id="family_name" name="family_name" placeholder="<?php _e('Ex: Familia Popescu', 'clinica'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="family_role"><?php _e('Rolul în familie *', 'clinica'); ?></label>
                                    <select id="family_role" name="family_role">
                                        <option value=""><?php _e('Selectează rolul', 'clinica'); ?></option>
                                        <option value="head"><?php _e('Cap de familie', 'clinica'); ?></option>
                                        <option value="spouse"><?php _e('Soț/Soție', 'clinica'); ?></option>
                                        <option value="child"><?php _e('Copil', 'clinica'); ?></option>
                                        <option value="parent"><?php _e('Părinte', 'clinica'); ?></option>
                                        <option value="sibling"><?php _e('Frate/Soră', 'clinica'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Opțiunea pentru familie existentă -->
                            <div id="existing-family-section" class="family-section" style="display: none;">
                                <div class="form-group">
                                    <label for="family_search"><?php _e('Caută familie', 'clinica'); ?></label>
                                    <div class="family-search-container">
                                        <input type="text" id="family_search" placeholder="<?php _e('Introduceți numele familiei sau al unui membru...', 'clinica'); ?>">
                                        <button type="button" id="search_family_btn" class="button"><?php _e('Caută', 'clinica'); ?></button>
                                    </div>
                                </div>
                                
                                <div id="family_search_results" class="family-search-results" style="display: none;">
                                    <h4><?php _e('Familii găsite:', 'clinica'); ?></h4>
                                    <div id="family_results_list"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="existing_family_role"><?php _e('Rolul în familie *', 'clinica'); ?></label>
                                    <select id="existing_family_role" name="existing_family_role">
                                        <option value=""><?php _e('Selectează rolul', 'clinica'); ?></option>
                                        <option value="spouse"><?php _e('Soț/Soție', 'clinica'); ?></option>
                                        <option value="child"><?php _e('Copil', 'clinica'); ?></option>
                                        <option value="parent"><?php _e('Părinte', 'clinica'); ?></option>
                                        <option value="sibling"><?php _e('Frate/Soră', 'clinica'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Afișare familie selectată -->
                            <div id="selected_family_info" class="selected-family-info" style="display: none;">
                                <h4><?php _e('Familia selectată:', 'clinica'); ?></h4>
                                <div id="selected_family_details"></div>
                                <button type="button" id="change_family_btn" class="button button-secondary"><?php _e('Schimbă familia', 'clinica'); ?></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 5: Setări Cont -->
                    <div class="clinica-tab-pane" data-tab="account">
                        <div class="form-section">
                            <h3><?php _e('Setări cont', 'clinica'); ?></h3>
                            
                            <div class="form-group">
                                <label for="password_method"><?php _e('Metoda de generare parolă', 'clinica'); ?></label>
                                <select id="password_method" name="password_method">
                                    <option value="cnp"><?php _e('Primele 6 cifre din CNP', 'clinica'); ?></option>
                                    <option value="birth_date"><?php _e('Data nașterii (dd.mm.yyyy)', 'clinica'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="generated_password"><?php _e('Parola generată', 'clinica'); ?></label>
                                <div class="password-display">
                                    <input type="text" id="generated_password" readonly>
                                    <button type="button" id="generate_password_btn" class="button"><?php _e('Generează', 'clinica'); ?></button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><?php _e('Note', 'clinica'); ?></label>
                                <textarea id="notes" name="notes" rows="3" placeholder="<?php _e('Note suplimentare despre pacient...', 'clinica'); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Navigation Buttons -->
                <div class="clinica-form-tab-navigation">
                    <button type="button" class="clinica-tab-nav-btn" id="prev-tab" disabled>
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php _e('Anterior', 'clinica'); ?>
                    </button>
                    
                    <div class="clinica-tab-progress">
                        <div class="clinica-tab-progress-bar">
                            <div class="clinica-tab-progress-fill" style="width: 25%;"></div>
                        </div>
                        <span class="clinica-tab-progress-text">1 din 5</span>
                    </div>
                    
                    <button type="button" class="clinica-tab-nav-btn" id="next-tab">
                        <?php _e('Următor', 'clinica'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Creează pacientul', 'clinica'); ?></button>
                    <button type="reset" class="button"><?php _e('Resetează', 'clinica'); ?></button>
                    <button type="button" class="button button-secondary" id="cancel-form"><?php _e('Anulează', 'clinica'); ?></button>
                </div>
            </form>
            
            <div id="clinica-form-messages"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var form = $('#clinica-patient-form');
            var cnpInput = $('#cnp');
            var birthDateInput = $('#birth_date');
            var genderInput = $('#gender');
            var genderValueInput = $('#gender_value');
            var cnpTypeInput = $('#cnp_type');
            var cnpTypeValueInput = $('#cnp_type_value');
            var ageInput = $('#age');
            var passwordInput = $('#generated_password');
                         var generatePasswordBtn = $('#generate_password_btn');
             
             // Transformare UPPERCASE → Title Case pentru nume și prenume (doar când se termină de scris)
             $('#last_name, #first_name').on('blur', function() {
                 var input = $(this);
                 var value = input.val().trim();
                 
                 // Dacă câmpul nu este gol și conține doar litere mari
                 if (value && value === value.toUpperCase() && value !== value.toLowerCase()) {
                     // Folosește funcția de normalizare din backend
                     $.ajax({
                         url: '<?php echo admin_url('admin-ajax.php'); ?>',
                         type: 'POST',
                         data: {
                             action: 'clinica_normalize_name',
                             name: value,
                             nonce: '<?php echo wp_create_nonce('clinica_normalize_name'); ?>'
                         },
                         success: function(response) {
                             if (response.success) {
                                 input.val(response.data.normalized_name);
                                 // Adaugă un efect vizual pentru a arăta că s-a făcut transformarea
                                 input.addClass('normalized');
                                 setTimeout(function() {
                                     input.removeClass('normalized');
                                 }, 1000);
                             }
                         },
                         error: function() {
                             console.log('Eroare la normalizarea numelui');
                         }
                     });
                 }
             });
             
             // Validare CNP în timp real
            cnpInput.on('input', function() {
                var cnp = $(this).val();
                
                if (cnp.length === 13) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'clinica_validate_cnp',
                            cnp: cnp,
                            nonce: '<?php echo wp_create_nonce('clinica_validate_cnp'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                if (response.data.parsed_data) {
                                    birthDateInput.val(response.data.parsed_data.birth_date);
                                    // Tip CNP
                                    var cnpType = response.data.parsed_data.cnp_type;
                                    var cnpTypeLabel = '';
                                    switch(cnpType) {
                                        case 'romanian':
                                            cnpTypeLabel = 'Român';
                                            break;
                                        case 'foreign_permanent':
                                            cnpTypeLabel = 'Străin cu reședință permanentă';
                                            break;
                                        case 'foreign_temporary':
                                            cnpTypeLabel = 'Străin cu reședință temporară';
                                            break;
                                        default:
                                            cnpTypeLabel = 'Necunoscut';
                                    }
                                    cnpTypeInput.val(cnpTypeLabel);
                                    cnpTypeValueInput.val(cnpType);
                                    // Sex
                                    var gender = response.data.parsed_data.gender;
                                    var genderLabel = '';
                                    switch(gender) {
                                        case 'male':
                                            genderLabel = 'Masculin';
                                            break;
                                        case 'female':
                                            genderLabel = 'Feminin';
                                            break;
                                        default:
                                            genderLabel = 'Necunoscut';
                                    }
                                    genderInput.val(genderLabel);
                                    genderValueInput.val(gender);
                                    ageInput.val(response.data.parsed_data.age);
                                }
                                
                                // Verifică dacă CNP-ul există deja
                                checkCNPExists(cnp);
                            } else {
                                $('.cnp-validation-message').html('<span class="invalid">' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $('.cnp-validation-message').html('<span class="invalid">Eroare la validare</span>');
                        }
                    });
                } else {
                    $('.cnp-validation-message').html('');
                }
            });
            
            // Funcție pentru verificarea existenței CNP-ului
            function checkCNPExists(cnp) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_check_cnp_exists',
                        cnp: cnp,
                        nonce: '<?php echo wp_create_nonce('clinica_check_cnp_exists'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.exists) {
                                $('.cnp-validation-message').html('<span class="invalid">⚠️ Acest CNP există deja în sistem!</span>');
                                // Dezactivează butonul de trimitere
                                $('button[type="submit"]').prop('disabled', true).addClass('disabled');
                                // Dezactivează navigarea la următorul tab
                                $('#next-tab').prop('disabled', true);
                            } else {
                                $('.cnp-validation-message').html('<span class="valid">✅ CNP valid și disponibil</span>');
                                // Reactivează butonul de trimitere
                                $('button[type="submit"]').prop('disabled', false).removeClass('disabled');
                                // Reactivează navigarea
                                $('#next-tab').prop('disabled', false);
                            }
                        } else {
                            $('.cnp-validation-message').html('<span class="invalid">Eroare la verificarea CNP-ului</span>');
                        }
                    },
                    error: function() {
                        $('.cnp-validation-message').html('<span class="invalid">Eroare la verificarea CNP-ului</span>');
                    }
                });
            }
            
            // Generare parolă
            generatePasswordBtn.on('click', function() {
                var cnp = cnpInput.val();
                var method = $('#password_method').val();
                var birth_date = birthDateInput.val();
                if (cnp.length === 13) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'clinica_generate_password',
                            cnp: cnp,
                            method: method,
                            birth_date: birth_date,
                            nonce: '<?php echo wp_create_nonce('clinica_generate_password'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                passwordInput.val(response.data.password);
                            }
                        }
                    });
                }
            });
            
            // Gestionare tab-uri
            var currentTab = 0;
            var tabs = ['cnp', 'personal', 'family', 'account'];
            var tabButtons = $('.clinica-tab-button');
            var tabPanes = $('.clinica-tab-pane');
            var prevBtn = $('#prev-tab');
            var nextBtn = $('#next-tab');
            var progressFill = $('.clinica-tab-progress-fill');
            var progressText = $('.clinica-tab-progress-text');
            
            // Variabile pentru gestionarea familiilor
            var familyOption = $('#family_option');
            var newFamilySection = $('#new-family-section');
            var existingFamilySection = $('#existing-family-section');
            var selectedFamilyInfo = $('#selected_family_info');
            var familySearch = $('#family_search');
            var searchFamilyBtn = $('#search_family_btn');
            var familyResultsList = $('#family_results_list');
            var selectedFamilyDetails = $('#selected_family_details');
            var changeFamilyBtn = $('#change_family_btn');
            
            // Gestionare opțiuni familie
            familyOption.on('change', function() {
                var option = $(this).val();
                
                // Ascunde toate secțiunile
                newFamilySection.hide();
                existingFamilySection.hide();
                selectedFamilyInfo.hide();
                
                // Arată secțiunea corespunzătoare
                if (option === 'new') {
                    newFamilySection.show();
                } else if (option === 'existing') {
                    existingFamilySection.show();
                }
            });
            
            // Căutare familii
            searchFamilyBtn.on('click', function() {
                var searchTerm = familySearch.val();
                if (searchTerm.length < 2) {
                    alert('Introduceți cel puțin 2 caractere pentru căutare');
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'clinica_search_families',
                        search_term: searchTerm,
                        nonce: '<?php echo wp_create_nonce('clinica_family_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayFamilyResults(response.data);
                        } else {
                            familyResultsList.html('<p class="error">Eroare la căutare: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        familyResultsList.html('<p class="error">Eroare la căutarea familiilor</p>');
                    }
                });
            });
            
            // Funcție pentru afișarea rezultatelor căutării
            function displayFamilyResults(families) {
                if (families.length === 0) {
                    familyResultsList.html('<p>Nu s-au găsit familii care să corespundă căutării.</p>');
                    return;
                }
                
                var html = '<div class="family-results">';
                families.forEach(function(family) {
                    html += '<div class="family-result-item" data-family-id="' + family.family_id + '">';
                    html += '<h5>' + family.family_name + '</h5>';
                    html += '<p><strong>Membri:</strong> ' + family.member_count + '</p>';
                    html += '<p><strong>Detalii:</strong> ' + family.members + '</p>';
                    html += '<button type="button" class="button select-family-btn" data-family-id="' + family.family_id + '">Selectează</button>';
                    html += '</div>';
                });
                html += '</div>';
                
                familyResultsList.html(html);
                $('#family_search_results').show();
            }
            
            // Selectare familie
            $(document).on('click', '.select-family-btn', function() {
                var familyId = $(this).data('family-id');
                var familyName = $(this).closest('.family-result-item').find('h5').text();
                
                // Salvează informațiile despre familie
                $('<input>').attr({
                    type: 'hidden',
                    name: 'selected_family_id',
                    value: familyId
                }).appendTo(form);
                
                $('<input>').attr({
                    type: 'hidden',
                    name: 'selected_family_name',
                    value: familyName
                }).appendTo(form);
                
                // Afișează informațiile despre familia selectată
                selectedFamilyDetails.html(
                    '<p><strong>Familia:</strong> ' + familyName + '</p>' +
                    '<p><strong>ID:</strong> ' + familyId + '</p>'
                );
                
                existingFamilySection.hide();
                selectedFamilyInfo.show();
            });
            
            // Schimbă familia
            changeFamilyBtn.on('click', function() {
                $('input[name="selected_family_id"]').remove();
                $('input[name="selected_family_name"]').remove();
                selectedFamilyInfo.hide();
                existingFamilySection.show();
                familySearch.val('');
                familyResultsList.html('');
                $('#family_search_results').hide();
            });
            
            // Funcție pentru schimbarea tab-ului
            function switchTab(tabIndex) {
                if (tabIndex < 0 || tabIndex >= tabs.length) return;
                
                // Ascunde toate tab-urile
                tabPanes.removeClass('active');
                tabButtons.removeClass('active');
                
                // Arată tab-ul curent
                $('.clinica-tab-pane[data-tab="' + tabs[tabIndex] + '"]').addClass('active');
                $('.clinica-tab-button[data-tab="' + tabs[tabIndex] + '"]').addClass('active');
                
                // Actualizează butoanele de navigare
                prevBtn.prop('disabled', tabIndex === 0);
                nextBtn.text(tabIndex === tabs.length - 1 ? 'Finalizează' : 'Următor');
                
                // Actualizează progress bar
                var progress = ((tabIndex + 1) / tabs.length) * 100;
                progressFill.css('width', progress + '%');
                progressText.text((tabIndex + 1) + ' din ' + tabs.length);
                
                currentTab = tabIndex;
            }
            
            // Click pe butoanele tab-urilor
            tabButtons.on('click', function() {
                var tabName = $(this).data('tab');
                var tabIndex = tabs.indexOf(tabName);
                switchTab(tabIndex);
            });
            
            // Buton Anterior
            prevBtn.on('click', function() {
                if (currentTab > 0) {
                    switchTab(currentTab - 1);
                }
            });
            
            // Buton Următor
            nextBtn.on('click', function() {
                if (currentTab < tabs.length - 1) {
                    // Validare pentru tab-ul curent
                    if (currentTab === 0) {
                        // Validare CNP
                        if (cnpInput.val().length !== 13) {
                            alert('Vă rugăm să introduceți un CNP valid (13 cifre)');
                            return;
                        }
                        
                        // Verifică dacă CNP-ul există deja
                        var cnpValidationMessage = $('.cnp-validation-message').text();
                        if (cnpValidationMessage.includes('există deja')) {
                            alert('Acest CNP există deja în sistem. Vă rugăm să introduceți un CNP diferit.');
                            return;
                        }
                        
                        if (!cnpValidationMessage.includes('valid și disponibil')) {
                            alert('Vă rugăm să așteptați validarea CNP-ului înainte de a continua.');
                            return;
                        }
                    } else if (currentTab === 1) {
                        // Validare informații personale
                        if (!$('#first_name').val() || !$('#last_name').val() || !$('#phone_primary').val()) {
                            alert('Vă rugăm să completați toate câmpurile obligatorii');
                            return;
                        }
                    } else if (currentTab === 3) {
                        // Validare informații familie
                        var familyOption = $('#family_option').val();
                        
                        if (familyOption === 'new') {
                            if (!$('#family_name').val() || !$('#family_role').val()) {
                                alert('Vă rugăm să completați numele familiei și rolul în familie');
                                return;
                            }
                        } else if (familyOption === 'existing') {
                            if (!$('input[name="selected_family_id"]').val() || !$('#existing_family_role').val()) {
                                alert('Vă rugăm să selectați o familie și să specificați rolul în familie');
                                return;
                            }
                        }
                    }
                    switchTab(currentTab + 1);
                } else {
                    // Ultimul tab - trimite formularul
                    form.submit();
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case 'ArrowLeft':
                            e.preventDefault();
                            if (currentTab > 0) switchTab(currentTab - 1);
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            if (currentTab < tabs.length - 1) switchTab(currentTab + 1);
                            break;
                    }
                }
            });
            
            // Buton Resetează - îmbunătățit
            $('button[type="reset"]').on('click', function(e) {
                e.preventDefault();
                
                // Confirmare pentru reset
                if (confirm('Sigur doriți să resetați formularul? Toate datele introduse vor fi șterse.')) {
                    // Reset formular
                    form[0].reset();
                    
                    // Reset tab-uri
                    switchTab(0);
                    
                    // Reset mesaje
                    $('.cnp-validation-message').html('');
                    $('#clinica-form-messages').html('');
                    
                    // Reactivează butoanele
                    $('button[type="submit"]').prop('disabled', false).removeClass('disabled');
                    $('#next-tab').prop('disabled', false);
                    
                    // Reset câmpuri readonly
                    $('#cnp_type').val('');
                    $('#cnp_type_value').val('');
                    $('#gender').val('');
                    $('#gender_value').val('');
                    $('#birth_date').val('');
                    $('#age').val('');
                    $('#generated_password').val('');
                    
                    console.log('Formular resetat cu succes');
                }
            });
            
            // Buton Anulează
            $('#cancel-form').on('click', function() {
                // Confirmare pentru anulare
                if (confirm('Sigur doriți să anulați crearea pacientului? Toate datele introduse vor fi pierdute.')) {
                    // Opțiuni pentru anulare:
                    
                    // 1. Reset formular și rămâi pe aceeași pagină
                    form[0].reset();
                    switchTab(0);
                    $('.cnp-validation-message').html('');
                    $('#clinica-form-messages').html('');
                    $('button[type="submit"]').prop('disabled', false).removeClass('disabled');
                    $('#next-tab').prop('disabled', false);
                    
                    // 2. Sau redirecționează către o altă pagină (decomentează linia de mai jos)
                    // window.location.href = '<?php echo admin_url('admin.php?page=clinica-patients'); ?>';
                    
                    // 3. Sau închide modal-ul dacă formularul este într-un modal (decomentează linia de mai jos)
                    // $('.modal').modal('hide');
                    
                    console.log('Crearea pacientului anulată');
                }
            });
            
            // Submit formular
            form.on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                // Suprascrie valorile pentru gender și cnp_type cu cele din hidden
                formData.set('gender', genderValueInput.val());
                formData.set('cnp_type', cnpTypeValueInput.val());
                formData.append('action', 'clinica_create_patient');
                formData.append('nonce', $('#clinica_nonce').val());
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#clinica-form-messages').html(
                                '<div class="notice notice-success">' +
                                '<p>' + response.data.message + '</p>' +
                                '<p><strong>Username:</strong> ' + response.data.username + '</p>' +
                                '<p><strong>Parolă:</strong> ' + response.data.password + '</p>' +
                                '</div>'
                            );
                            form[0].reset();
                            // Reset la primul tab
                            switchTab(0);
                        } else {
                            $('#clinica-form-messages').html(
                                '<div class="notice notice-error">' +
                                '<p>' + response.data + '</p>' +
                                '</div>'
                            );
                        }
                    }
                });
            });
        });
        </script>
        
        <style>
        .clinica-patient-form-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Tab Navigation */
        .clinica-form-tabs {
            display: flex;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            margin: 0;
        }
        
        .clinica-tab-button {
            flex: 1;
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            padding: 20px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .clinica-tab-button:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .clinica-tab-button.active {
            background: rgba(255,255,255,0.2);
            color: #fff;
            box-shadow: inset 0 -3px 0 #fff;
        }
        
        .clinica-tab-button .tab-icon {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        /* Tab Content */
        .clinica-form-tab-content {
            padding: 30px;
            min-height: 400px;
        }
        
        .clinica-tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .clinica-tab-pane.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .clinica-form .form-section {
            margin-bottom: 25px;
            padding: 25px;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            background: #fafbfc;
        }
        
        .clinica-form .form-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .clinica-form .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .clinica-form .form-group {
            flex: 1;
        }
        
        .clinica-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
        }
        
        .clinica-form input,
        .clinica-form select,
        .clinica-form textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .clinica-form input:focus,
        .clinica-form select:focus,
        .clinica-form textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
                 .clinica-form input[readonly] {
             background-color: #f8f9fa;
             color: #6c757d;
             cursor: not-allowed;
             border-color: #dee2e6;
         }
         
         /* Efect vizual pentru normalizarea numelor */
         .clinica-form input.normalized {
             border-color: #27ae60 !important;
             box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1) !important;
             transition: all 0.3s ease;
         }
        
        .clinica-form .password-display {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .clinica-form .password-display input {
            flex: 1;
        }
        
        .cnp-validation-message {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .cnp-validation-message .valid {
            color: #27ae60;
        }
        
        .cnp-validation-message .invalid {
            color: #e74c3c;
        }
        
        /* Tab Navigation Buttons */
        .clinica-form-tab-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
        }
        
        .clinica-tab-nav-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .clinica-tab-nav-btn:hover:not(:disabled) {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .clinica-tab-nav-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        
        .clinica-tab-progress {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .clinica-tab-progress-bar {
            width: 200px;
            height: 6px;
            background: #e1e5e9;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .clinica-tab-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
        }
        
        .clinica-tab-progress-text {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .form-actions {
            text-align: center;
            padding: 20px 30px;
            background: #fff;
            border-top: 1px solid #e1e5e9;
        }
        
        .form-actions .button {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .form-actions .button-primary {
            background: #27ae60;
            border-color: #27ae60;
        }
        
        .form-actions .button-primary:hover {
            background: #229954;
            border-color: #229954;
            transform: translateY(-1px);
        }
        
        .form-actions .button.disabled {
            background: #bdc3c7 !important;
            border-color: #bdc3c7 !important;
            cursor: not-allowed !important;
            transform: none !important;
        }
        
        .form-actions .button.disabled:hover {
            background: #bdc3c7 !important;
            border-color: #bdc3c7 !important;
            transform: none !important;
        }
        
        #clinica-form-messages {
            margin: 20px 30px;
        }
        
        /* Stiluri pentru gestionarea familiilor */
        .family-section {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .family-search-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .family-search-container input {
            flex: 1;
        }
        
        .family-search-results {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            background: white;
        }
        
        .family-results {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .family-result-item {
            padding: 15px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .family-result-item h5 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .family-result-item p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .select-family-btn {
            margin-top: 10px;
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .select-family-btn:hover {
            background: #2980b9;
            border-color: #2980b9;
        }
        
        .selected-family-info {
            margin-top: 15px;
            padding: 15px;
            border: 2px solid #27ae60;
            border-radius: 6px;
            background: #d5f4e6;
        }
        
        .selected-family-info h4 {
            margin: 0 0 10px 0;
            color: #27ae60;
        }
        
        .selected-family-info p {
            margin: 5px 0;
            color: #2c3e50;
        }
        
        .change-family-btn {
            margin-top: 10px;
            background: #f39c12;
            border-color: #f39c12;
            color: white;
        }
        
        .change-family-btn:hover {
            background: #e67e22;
            border-color: #e67e22;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .clinica-form-tabs {
                flex-direction: column;
            }
            
            .clinica-tab-button {
                padding: 15px;
                flex-direction: row;
                justify-content: center;
            }
            
            .clinica-form .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .clinica-form-tab-navigation {
                flex-direction: column;
                gap: 15px;
            }
            
            .clinica-tab-progress {
                order: -1;
            }
            
            .family-search-container {
                flex-direction: column;
            }
            
            .family-search-container input {
                width: 100%;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
} 