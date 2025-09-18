<?php
/**
 * Sistem de autentificare pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Authentication {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_clinica_login', array($this, 'ajax_login'));
        add_action('wp_ajax_nopriv_clinica_login', array($this, 'ajax_login'));
        add_action('wp_ajax_clinica_reset_password', array($this, 'ajax_reset_password'));
        add_action('wp_ajax_nopriv_clinica_reset_password', array($this, 'ajax_reset_password'));
        
        // Adaugă shortcode pentru formularul de login
        add_shortcode('clinica_login', array($this, 'render_login_shortcode'));
    }
    
    /**
     * Shortcode pentru formularul de login
     */
    public function render_login_shortcode($atts) {
        // Verifică dacă utilizatorul este deja logat
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_role = Clinica_Roles::get_user_role($current_user->ID);
            
            $redirect_url = '';
            switch ($user_role) {
                case 'clinica_patient':
                    $redirect_url = home_url('/clinica-patient-dashboard/');
                    break;
                case 'clinica_doctor':
                    $redirect_url = home_url('/clinica-doctor-dashboard/');
                    break;
                case 'clinica_assistant':
                    $redirect_url = home_url('/clinica-assistant-dashboard/');
                    break;
                case 'clinica_receptionist':
                    $redirect_url = home_url('/clinica-receptionist-dashboard/');
                    break;
                case 'clinica_manager':
                    $redirect_url = home_url('/clinica-manager-dashboard/');
                    break;
                case 'clinica_administrator':
                    $redirect_url = home_url('/clinica-manager-dashboard/');
                    break;
                default:
                    $redirect_url = home_url();
            }
            
            return '<div class="clinica-login-message">
                <p>Ești deja autentificat ca <strong>' . esc_html($current_user->display_name) . '</strong>.</p>
                <p><a href="' . esc_url($redirect_url) . '" class="button">Mergi la dashboard</a></p>
                <p><a href="' . esc_url(wp_logout_url()) . '" class="button">Deconectează-te</a></p>
            </div>';
        }
        
        return $this->render_login_form();
    }
    
    /**
     * Generează formularul de login HTML
     */
    private function render_login_form() {
        ob_start();
        ?>
        <div class="clinica-login-container">
            <div class="clinica-login-form-wrapper">
                <h2><?php _e('Autentificare Clinica', 'clinica'); ?></h2>
                
                <form id="clinica-login-form" class="clinica-login-form">
                    <?php wp_nonce_field('clinica_login', 'clinica_frontend_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="login_identifier"><?php _e('CNP, Email sau Telefon *', 'clinica'); ?></label>
                        <input type="text" id="login_identifier" name="identifier" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password"><?php _e('Parolă *', 'clinica'); ?></label>
                        <input type="password" id="login_password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember" value="1">
                            <?php _e('Ține-mă minte', 'clinica'); ?>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Autentificare', 'clinica'); ?></button>
                    </div>
                    
                    <div class="form-links">
                        <a href="#" id="forgot-password-link"><?php _e('Am uitat parola', 'clinica'); ?></a>
                    </div>
                </form>
                
                <!-- Formular reset parolă (ascuns inițial) -->
                <form id="clinica-reset-password-form" class="clinica-reset-form" style="display: none;">
                    <?php wp_nonce_field('clinica_reset_password', 'clinica_reset_nonce'); ?>
                    
                    <h3><?php _e('Resetare Parolă', 'clinica'); ?></h3>
                    <p><?php _e('Introdu CNP-ul pentru a reseta parola.', 'clinica'); ?></p>
                    
                    <div class="form-group">
                        <label for="reset_cnp"><?php _e('CNP *', 'clinica'); ?></label>
                        <input type="text" id="reset_cnp" name="cnp" maxlength="13" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Resetează Parola', 'clinica'); ?></button>
                        <button type="button" id="back-to-login" class="button"><?php _e('Înapoi la Login', 'clinica'); ?></button>
                    </div>
                </form>
                
                <div id="clinica-login-messages"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var loginForm = $('#clinica-login-form');
            var resetForm = $('#clinica-reset-password-form');
            var forgotLink = $('#forgot-password-link');
            var backToLogin = $('#back-to-login');
            var messages = $('#clinica-login-messages');
            
            // Toggle între login și reset parolă
            forgotLink.on('click', function(e) {
                e.preventDefault();
                loginForm.hide();
                resetForm.show();
            });
            
            backToLogin.on('click', function(e) {
                e.preventDefault();
                resetForm.hide();
                loginForm.show();
                messages.empty();
            });
            
            // Submit login
            loginForm.on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'clinica_login');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        messages.html('<div class="loading">Se procesează...</div>');
                    },
                    success: function(response) {
                        if (response.success) {
                            messages.html('<div class="success">' + response.data.message + '</div>');
                            // Redirect după 2 secunde
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        } else {
                            messages.html('<div class="error">' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        messages.html('<div class="error">Eroare la procesare. Încearcă din nou.</div>');
                    }
                });
            });
            
            // Submit reset parolă
            resetForm.on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'clinica_reset_password');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        messages.html('<div class="loading">Se procesează...</div>');
                    },
                    success: function(response) {
                        if (response.success) {
                            messages.html('<div class="success">' + response.data.message + '</div>');
                        } else {
                            messages.html('<div class="error">' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        messages.html('<div class="error">Eroare la procesare. Încearcă din nou.</div>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .clinica-login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .clinica-login-form-wrapper {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .clinica-login-form-wrapper h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .clinica-login-form .form-group {
            margin-bottom: 20px;
        }
        
        .clinica-login-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .clinica-login-form input[type="text"],
        .clinica-login-form input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .clinica-login-form input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .clinica-login-form .form-actions {
            text-align: center;
            margin-top: 25px;
        }
        
        .clinica-login-form .button {
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 4px;
        }
        
        .clinica-login-form .form-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .clinica-login-form .form-links a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .clinica-login-form .form-links a:hover {
            text-decoration: underline;
        }
        
        #clinica-login-messages {
            margin-top: 20px;
        }
        
        #clinica-login-messages .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }
        
        #clinica-login-messages .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
        
        #clinica-login-messages .loading {
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #bee5eb;
            text-align: center;
        }
        
        .clinica-reset-form {
            margin-top: 20px;
        }
        
        .clinica-reset-form h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .clinica-reset-form p {
            margin-bottom: 20px;
            color: #666;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Inițializează hook-urile
     */
    public function init() {
        // Hook pentru autentificare personalizată - prioritate mai mare pentru a nu interfera cu WordPress core
        add_filter('authenticate', array($this, 'custom_authenticate'), 30, 3);
        
        // Hook pentru login form personalizat
        add_action('login_form', array($this, 'custom_login_form'));
        
        // Hook pentru validare login - COMENTAT pentru a permite autentificarea WordPress admin să funcționeze
        // add_action('wp_authenticate', array($this, 'validate_login_fields'), 10, 1);
        
        // Hook pentru redirect după login
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
        
        // Hook pentru logout redirect
        add_action('wp_logout', array($this, 'custom_logout_redirect'));
        
        // Hook pentru verificare CNP
        add_action('wp_ajax_clinica_check_cnp', array($this, 'ajax_check_cnp'));
        add_action('wp_ajax_nopriv_clinica_check_cnp', array($this, 'ajax_check_cnp'));
    }
    
    /**
     * Autentificare personalizată cu username, email sau telefon
     */
    public function custom_authenticate($user, $username, $password) {
        // Dacă deja avem un user valid, returnăm
        if ($user instanceof WP_User) {
            return $user;
        }
        
        // Dacă nu avem username sau parolă, returnăm
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        // Verifică dacă suntem în contextul de login WordPress admin
        // Nu executa pentru admin WordPress pentru a nu interfera cu autentificarea standard
        if (is_admin() && !wp_doing_ajax() && !isset($_POST['clinica_frontend_nonce'])) {
            return $user; // Skip pentru admin WordPress
        }
        
        // Încercăm să găsim utilizatorul după username, email sau telefon
        $user = $this->find_user_by_identifier($username);
        
        if (!$user) {
            return new WP_Error('invalid_credentials', __('Credențiale incorecte.', 'clinica'));
        }
        
        // Verificăm parola
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return new WP_Error('invalid_credentials', __('Credențiale incorecte.', 'clinica'));
        }
        
        // Verificăm dacă utilizatorul este activ
        if (!$this->is_user_active($user->ID)) {
            return new WP_Error('inactive_user', __('Contul este inactiv.', 'clinica'));
        }
        
        // Log autentificare reușită
        $this->log_successful_login($user->ID);
        
        return $user;
    }
    
    /**
     * Găsește utilizatorul după username, email sau telefon
     */
    public function find_user_by_identifier($identifier) {
        global $wpdb;
        
        // 1. Încercăm să găsim după username
        $user = get_user_by('login', $identifier);
        if ($user) {
            return $user;
        }
        
        // 2. Încercăm să găsim după email
        $user = get_user_by('email', $identifier);
        if ($user) {
            return $user;
        }
        
        // 3. Încercăm să găsim după telefon în user meta
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key IN ('phone_primary', 'phone_secondary') 
             AND meta_value = %s",
            $identifier
        ));
        
        if ($user_id) {
            return get_user_by('ID', $user_id);
        }
        
        // 4. Încercăm să găsim după telefon în tabela pacienți (fallback)
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE phone_primary = %s OR phone_secondary = %s",
            $identifier,
            $identifier
        ));
        
        if ($user_id) {
            return get_user_by('ID', $user_id);
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul este activ
     */
    public function is_user_active($user_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return false;
        }
        
        // Verificăm dacă utilizatorul nu este șters
        if ($user->user_status == 1) {
            return false;
        }
        
        // Verificăm dacă utilizatorul nu este suspendat
        $suspended = get_user_meta($user_id, '_clinica_suspended', true);
        if ($suspended) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log autentificare reușită
     */
    public function log_successful_login($user_id) {
        $login_data = array(
            'user_id' => $user_id,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'login_time' => current_time('mysql'),
            'success' => true
        );
        
        // Salvăm în baza de date
        $this->save_login_log($login_data);
        
        // Actualizăm ultima autentificare
        update_user_meta($user_id, '_clinica_last_login', current_time('mysql'));
    }
    
    /**
     * Log autentificare eșuată
     */
    public function log_failed_login($identifier, $reason = 'invalid_credentials') {
        $login_data = array(
            'user_id' => 0,
            'identifier' => $identifier,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'login_time' => current_time('mysql'),
            'success' => false,
            'reason' => $reason
        );
        
        // Salvăm în baza de date
        $this->save_login_log($login_data);
    }
    
    /**
     * Salvează log-ul de autentificare
     */
    private function save_login_log($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_login_logs';
        
        $wpdb->insert($table_name, $data);
    }
    
    /**
     * Obține IP-ul clientului
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Formular de login personalizat
     */
    public function custom_login_form() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Schimbă placeholder-ul pentru username
            $('#user_login').attr('placeholder', '<?php _e('Username, Email, CNP sau Telefon', 'clinica'); ?>');
            
            // Adaugă validare personalizată
            $('#loginform').on('submit', function(e) {
                var username = $('#user_login').val();
                var password = $('#user_pass').val();
                
                if (!username || !password) {
                    alert('<?php _e('Vă rugăm să completați toate câmpurile.', 'clinica'); ?>');
                    e.preventDefault();
                    return false;
                }
                
                // Validare CNP dacă se pare că este CNP
                if (username.length === 13 && /^\d+$/.test(username)) {
                    // Este posibil să fie CNP - validăm
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'clinica_check_cnp',
                            cnp: username,
                            nonce: '<?php echo wp_create_nonce('clinica_cnp_check'); ?>'
                        },
                        success: function(response) {
                            if (response.success && !response.data.valid) {
                                alert('<?php _e('CNP-ul introdus nu este valid.', 'clinica'); ?>');
                                e.preventDefault();
                                return false;
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Validează câmpurile de login
     */
    public function validate_login_fields($username) {
        // Verifică dacă suntem în contextul de login WordPress admin
        // Nu executa pentru admin WordPress pentru a nu interfera cu autentificarea standard
        if (is_admin() && !wp_doing_ajax() && !isset($_POST['clinica_frontend_nonce'])) {
            return; // Skip pentru admin WordPress
        }
        
        // Verifică dacă username-ul este gol (doar pentru frontend Clinica)
        if (empty($username)) {
            wp_die(__('Câmpul de identificare este obligatoriu.', 'clinica'));
        }
        
        // Verifică dacă nu sunt prea multe încercări eșuate
        if ($this->too_many_failed_attempts()) {
            wp_die(__('Prea multe încercări eșuate. Vă rugăm să încercați din nou în câteva minute.', 'clinica'));
        }
    }
    
    /**
     * Verifică dacă sunt prea multe încercări eșuate
     */
    public function too_many_failed_attempts() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_login_logs';
        $ip_address = $this->get_client_ip();
        $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        
        $failed_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE ip_address = %s 
             AND success = 0 
             AND login_time > %s",
            $ip_address,
            $time_limit
        ));
        
        return $failed_attempts >= 5; // Maxim 5 încercări în 15 minute
    }
    
    /**
     * Redirect personalizat după login
     */
    public function custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (is_wp_error($user)) {
            return $redirect_to;
        }
        
        // Verifică dacă utilizatorul are rol Clinica
        if (Clinica_Roles::has_clinica_role($user->ID)) {
            $role = Clinica_Roles::get_user_role($user->ID);
            
            switch ($role) {
                case 'clinica_patient':
                    // Pacienții merg la pagina lor personală
                    return home_url('/clinica-patient-dashboard/');
                case 'clinica_doctor':
                    // Doctorii merg la dashboard-ul lor specific
                    return home_url('/clinica-doctor-dashboard/');
                case 'clinica_assistant':
                    // Asistenții merg la dashboard-ul lor specific
                    return home_url('/clinica-assistant-dashboard/');
                case 'clinica_receptionist':
                    // Receptionerii merg la dashboard-ul lor specific
                    return home_url('/clinica-receptionist-dashboard/');
                case 'clinica_manager':
                    // Managerii merg la dashboard-ul lor specific
                    return home_url('/clinica-manager-dashboard/');
                case 'clinica_administrator':
                    // Administratorii merg la dashboard manager
                    return home_url('/clinica-manager-dashboard/');
                default:
                    // Alții merg la homepage
                    return home_url();
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Redirect personalizat după logout
     */
    public function custom_logout_redirect() {
        wp_redirect(home_url());
        exit;
    }
    
    /**
     * AJAX pentru login
     */
    public function ajax_login() {
        // Verifică nonce-ul
        if (!wp_verify_nonce($_POST['clinica_frontend_nonce'], 'clinica_login')) {
            wp_send_json_error('Eroare de securitate');
        }
        
        $identifier = sanitize_text_field($_POST['identifier']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] == '1';
        
        if (empty($identifier) || empty($password)) {
            wp_send_json_error('Toate câmpurile sunt obligatorii');
        }
        
        // Găsește utilizatorul
        $user = $this->find_user_by_identifier($identifier);
        
        if (!$user) {
            $this->log_failed_login($identifier, 'user_not_found');
            wp_send_json_error('Credențiale incorecte');
        }
        
        // Verifică parola
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            $this->log_failed_login($identifier, 'wrong_password');
            wp_send_json_error('Credențiale incorecte');
        }
        
        // Verifică dacă utilizatorul este activ
        if (!$this->is_user_active($user->ID)) {
            $this->log_failed_login($identifier, 'inactive_user');
            wp_send_json_error('Contul este inactiv');
        }
        
        // Log autentificare reușită
        $this->log_successful_login($user->ID);
        
        // Autentifică utilizatorul
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Determină URL-ul de redirect
        $redirect_url = '';
        if (Clinica_Roles::has_clinica_role($user->ID)) {
            $role = Clinica_Roles::get_user_role($user->ID);
            
            switch ($role) {
                case 'clinica_patient':
                    $redirect_url = home_url('/clinica-patient-dashboard/');
                    break;
                case 'clinica_doctor':
                    $redirect_url = home_url('/clinica-doctor-dashboard/');
                    break;
                case 'clinica_assistant':
                    $redirect_url = home_url('/clinica-assistant-dashboard/');
                    break;
                case 'clinica_receptionist':
                    $redirect_url = home_url('/clinica-receptionist-dashboard/');
                    break;
                case 'clinica_manager':
                    $redirect_url = home_url('/clinica-manager-dashboard/');
                    break;
                case 'clinica_administrator':
                    $redirect_url = home_url('/clinica-manager-dashboard/');
                    break;
                default:
                    $redirect_url = home_url();
                    break;
            }
        } else {
            $redirect_url = home_url();
        }
        
        wp_send_json_success(array(
            'message' => 'Autentificare reușită! Veți fi redirecționat în câteva secunde...',
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * AJAX pentru verificare CNP
     */
    public function ajax_check_cnp() {
        check_ajax_referer('clinica_cnp_check', 'nonce');
        
        $cnp = sanitize_text_field($_POST['cnp']);
        
        if (empty($cnp)) {
            wp_send_json_error('CNP-ul este obligatoriu');
        }
        
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX pentru reset parolă
     */
    public function ajax_reset_password() {
        check_ajax_referer('clinica_reset_password', 'nonce');
        
        $identifier = sanitize_text_field($_POST['identifier']);
        
        if (empty($identifier)) {
            wp_send_json_error('Identificatorul este obligatoriu');
        }
        
        // Găsește utilizatorul
        $user = $this->find_user_by_identifier($identifier);
        
        if (!$user) {
            wp_send_json_error('Utilizatorul nu a fost găsit');
        }
        
        // Generează parolă temporară
        $password_generator = new Clinica_Password_Generator();
        $temp_password = $password_generator->generate_temporary_password($user->ID);
        
        // Trimite email cu parola temporară
        $this->send_reset_password_email($user, $temp_password);
        
        wp_send_json_success('Parola temporară a fost trimisă pe email');
    }
    
    /**
     * Trimite email pentru reset parolă
     */
    private function send_reset_password_email($user, $temp_password) {
        $to = $user->user_email;
        $subject = 'Resetare parolă - Clinica';
        
        $message = sprintf(
            'Bună %s,

Ați solicitat resetarea parolei pentru contul dvs. din sistemul Clinica.

Parola temporară: %s

Această parolă este valabilă 24 de ore.

Pentru a vă autentifica, folosiți parola temporară și apoi schimbați-o din profilul dvs.

Cu stimă,
Echipa Clinica',
            $user->display_name,
            $temp_password
        );
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Verifică parola temporară
     */
    public function verify_temporary_password($user_id, $temp_password) {
        $password_generator = new Clinica_Password_Generator();
        return $password_generator->validate_temporary_password($user_id, $temp_password);
    }
    
    /**
     * Obține istoricul de autentificări pentru un utilizator
     */
    public function get_login_history($user_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_login_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY login_time DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Obține statistici de autentificare
     */
    public function get_login_stats($user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_login_logs';
        
        $where_clause = '';
        $where_values = array();
        
        if ($user_id) {
            $where_clause = 'WHERE user_id = %d';
            $where_values[] = $user_id;
        }
        
        // Total încercări
        $total_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name $where_clause",
            $where_values
        ));
        
        // Încercări reușite
        $successful_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name $where_clause AND success = 1",
            $where_values
        ));
        
        // Încercări eșuate
        $failed_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name $where_clause AND success = 0",
            $where_values
        ));
        
        // Ultima autentificare
        $last_login = $wpdb->get_var($wpdb->prepare(
            "SELECT login_time FROM $table_name $where_clause AND success = 1 ORDER BY login_time DESC LIMIT 1",
            $where_values
        ));
        
        return array(
            'total_attempts' => $total_attempts,
            'successful_attempts' => $successful_attempts,
            'failed_attempts' => $failed_attempts,
            'success_rate' => $total_attempts > 0 ? round(($successful_attempts / $total_attempts) * 100, 2) : 0,
            'last_login' => $last_login
        );
    }
}

// Inițializează autentificarea
new Clinica_Authentication(); 