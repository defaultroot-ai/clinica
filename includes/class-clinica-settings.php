<?php
/**
 * Gestionare setări pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Settings {
    
    /**
     * Instanța singleton
     */
    private static $instance = null;
    
    /**
     * Cache pentru setări
     */
    private $settings_cache = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_default_settings();
    }
    
    /**
     * Returnează instanța singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inițializează setările implicite
     */
    private function init_default_settings() {
        $default_settings = array(
            // Configurare clinică
            'clinic_name' => array(
                'value' => 'Clinica Medicală',
                'type' => 'text',
                'group' => 'clinic',
                'label' => 'Numele clinicii',
                'description' => 'Numele clinicii care va apărea în interfață și email-uri'
            ),
            'clinic_address' => array(
                'value' => '',
                'type' => 'textarea',
                'group' => 'clinic',
                'label' => 'Adresa clinicii',
                'description' => 'Adresa completă a clinicii'
            ),
            'clinic_phone' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'clinic',
                'label' => 'Telefon clinică',
                'description' => 'Numărul de telefon al clinicii'
            ),
            'clinic_email' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'clinic',
                'label' => 'Email clinică',
                'description' => 'Adresa de email a clinicii pentru notificări'
            ),
            'clinic_website' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'clinic',
                'label' => 'Website clinică',
                'description' => 'Adresa website-ului clinicii'
            ),
            'clinic_logo' => array(
                'value' => '',
                'type' => 'file',
                'group' => 'clinic',
                'label' => 'Logo clinică',
                'description' => 'Logo-ul clinicii (PNG, JPG, max 10MB)'
            ),
            
            // Program funcționare
            'working_hours' => array(
                'value' => json_encode(array(
                    'monday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'tuesday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'wednesday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'thursday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'friday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'saturday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false),
                    'sunday' => array('start' => '', 'end' => '', 'break_start' => '', 'break_end' => '', 'active' => false)
                )),
                'type' => 'json',
                'group' => 'schedule',
                'label' => 'Program funcționare',
                'description' => 'Programul de funcționare al clinicii'
            ),
            
            // Setări email
            'email_from_name' => array(
                'value' => 'Clinica Medicală',
                'type' => 'text',
                'group' => 'email',
                'label' => 'Nume expeditor email',
                'description' => 'Numele care va apărea ca expeditor în email-uri'
            ),
            'email_from_address' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'Adresa expeditor email',
                'description' => 'Adresa de email care va trimite notificările'
            ),
            'email_smtp_host' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Host',
                'description' => 'Serverul SMTP pentru trimiterea email-urilor'
            ),
            'email_smtp_port' => array(
                'value' => '587',
                'type' => 'number',
                'group' => 'email',
                'label' => 'SMTP Port',
                'description' => 'Portul SMTP (587 pentru TLS, 465 pentru SSL)'
            ),
            'email_smtp_username' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Username',
                'description' => 'Numele de utilizator pentru SMTP'
            ),
            'email_smtp_password' => array(
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Password',
                'description' => 'Parola pentru SMTP'
            ),
            'email_smtp_encryption' => array(
                'value' => 'tls',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Encryption',
                'description' => 'Tipul de criptare (tls, ssl, none)'
            ),
            
            // Setări programări
            'appointment_duration' => array(
                'value' => '30',
                'type' => 'number',
                'group' => 'appointments',
                'label' => 'Durată programări (minute)',
                'description' => 'Durata implicită a programărilor în minute'
            ),
            'appointment_interval' => array(
                'value' => '15',
                'type' => 'number',
                'group' => 'appointments',
                'label' => 'Interval între programări (minute)',
                'description' => 'Intervalul minim între programări'
            ),
            'appointment_advance_days' => array(
                'value' => '30',
                'type' => 'number',
                'group' => 'appointments',
                'label' => 'Zile în avans pentru programări',
                'description' => 'Câte zile în avans se pot face programări'
            ),
            'services_catalog' => array(
                'value' => json_encode(array(
                    array('id' => 'consultation', 'name' => 'Consultație', 'duration' => 30),
                    array('id' => 'examination', 'name' => 'Examinare', 'duration' => 20),
                    array('id' => 'procedure', 'name' => 'Procedură', 'duration' => 45),
                    array('id' => 'follow_up', 'name' => 'Control', 'duration' => 15)
                )),
                'type' => 'json',
                'group' => 'appointments',
                'label' => 'Catalog servicii',
                'description' => 'Lista serviciilor disponibile și durata implicită (minute)'
            ),
            'clinic_holidays' => array(
                'value' => json_encode(array()),
                'type' => 'json',
                'group' => 'appointments',
                'label' => 'Zile libere clinică',
                'description' => 'Date (YYYY-MM-DD) în care clinica este închisă'
            ),
            'max_appointments_per_doctor_per_day' => array(
                'value' => '24',
                'type' => 'number',
                'group' => 'appointments',
                'label' => 'Limită programări/zi/medic',
                'description' => 'Număr maxim de programări pe zi pentru fiecare medic'
            ),
            
            // Setări notificări
            'notifications_enabled' => array(
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Notificări activate',
                'description' => 'Activează sau dezactivează notificările email'
            ),
            'reminder_days' => array(
                'value' => '1',
                'type' => 'number',
                'group' => 'notifications',
                'label' => 'Zile înainte de reminder',
                'description' => 'Câte zile înainte de programare să se trimită reminder'
            ),
            'confirmation_required' => array(
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Confirmare programări',
                'description' => 'Cere confirmarea programărilor prin email'
            ),
            
            // Setări securitate
            'session_timeout' => array(
                'value' => '30',
                'type' => 'number',
                'group' => 'security',
                'label' => 'Timeout sesiuni (minute)',
                'description' => 'După câte minute de inactivitate să se închidă sesiunea'
            ),
            'login_attempts' => array(
                'value' => '5',
                'type' => 'number',
                'group' => 'security',
                'label' => 'Încercări de login',
                'description' => 'Numărul maxim de încercări de login înainte de blocare'
            ),
            'lockout_duration' => array(
                'value' => '15',
                'type' => 'number',
                'group' => 'security',
                'label' => 'Durată blocare (minute)',
                'description' => 'Durata blocării după încercări eșuate'
            ),
            
            // Setări performanță
            'items_per_page' => array(
                'value' => '20',
                'type' => 'number',
                'group' => 'performance',
                'label' => 'Elemente pe pagină',
                'description' => 'Numărul de elemente afișate pe pagină'
            ),
            'cache_enabled' => array(
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'label' => 'Cache activat',
                'description' => 'Activează cache-ul pentru performanță îmbunătățită'
            ),
            'auto_refresh' => array(
                'value' => '30',
                'type' => 'number',
                'group' => 'performance',
                'label' => 'Auto-refresh (secunde)',
                'description' => 'Intervalul de auto-refresh pentru dashboard-uri (0 = dezactivat)'
            )
        );
        
        foreach ($default_settings as $key => $setting) {
            $this->ensure_setting_exists($key, $setting);
        }
    }
    
    /**
     * Asigură că o setare există în baza de date
     */
    private function ensure_setting_exists($key, $setting) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'clinica_settings';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $table,
                array(
                    'setting_key' => $key,
                    'setting_value' => $setting['value'],
                    'setting_type' => $setting['type'],
                    'setting_group' => $setting['group'],
                    'setting_label' => $setting['label'],
                    'setting_description' => $setting['description']
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Obține o setare
     */
    public function get($key, $default = null) {
        // Verifică cache-ul
        if (isset($this->settings_cache[$key])) {
            return $this->settings_cache[$key];
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        $setting = $wpdb->get_row($wpdb->prepare(
            "SELECT setting_value, setting_type FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if (!$setting) {
            return $default;
        }
        
        $value = $this->parse_setting_value($setting->setting_value, $setting->setting_type);
        
        // Salvează în cache
        $this->settings_cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Setează o setare
     */
    public function set($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        // Obține tipul setării
        $setting_type = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_type FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if (!$setting_type) {
            return false;
        }
        
        // Convertește valoarea pentru stocare
        $stored_value = $this->prepare_setting_value($value, $setting_type);
        
        $result = $wpdb->update(
            $table,
            array(
                'setting_value' => $stored_value,
                'updated_at' => current_time('mysql')
            ),
            array('setting_key' => $key),
            array('%s', '%s'),
            array('%s')
        );
        
        // Șterge din cache
        unset($this->settings_cache[$key]);
        
        // Returnează true dacă update-ul a reușit (inclusiv dacă nu s-a modificat nimic)
        return $result !== false;
    }
    
    /**
     * Obține toate setările dintr-un grup
     */
    public function get_group($group) {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        $settings = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_key, setting_value, setting_type, setting_label, setting_description 
             FROM $table WHERE setting_group = %s ORDER BY setting_key",
            $group
        ));
        
        $result = array();
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = array(
                'value' => $this->parse_setting_value($setting->setting_value, $setting->setting_type),
                'label' => isset($setting->setting_label) ? $setting->setting_label : '',
                'description' => isset($setting->setting_description) ? $setting->setting_description : '',
                'type' => $setting->setting_type
            );
        }
        
        return $result;
    }
    
    /**
     * Obține toate setările publice
     */
    public function get_public_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        $settings = $wpdb->get_results(
            "SELECT setting_key, setting_value, setting_type 
             FROM $table WHERE is_public = 1 ORDER BY setting_key"
        );
        
        $result = array();
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $this->parse_setting_value($setting->setting_value, $setting->setting_type);
        }
        
        return $result;
    }
    
    /**
     * Parsează valoarea unei setări în funcție de tip
     */
    private function parse_setting_value($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'number':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'file':
                return $value; // URL-ul fișierului
            default:
                return $value;
        }
    }
    
    /**
     * Pregătește valoarea unei setări pentru stocare
     */
    private function prepare_setting_value($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
    
    /**
     * Șterge cache-ul
     */
    public function clear_cache($key = null) {
        if ($key === null) {
            // Șterge tot cache-ul
            $this->settings_cache = array();
        } else {
            // Șterge o setare specifică din cache
            unset($this->settings_cache[$key]);
        }
    }
    
    /**
     * Obține informațiile despre o setare
     */
    public function get_setting_info($key) {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE setting_key = %s",
            $key
        ));
    }
    
    /**
     * Adaugă o setare nouă
     */
    public function add_setting($key, $value, $type, $group, $label, $description = '', $is_public = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        return $wpdb->insert(
            $table,
            array(
                'setting_key' => $key,
                'setting_value' => $this->prepare_setting_value($value, $type),
                'setting_type' => $type,
                'setting_group' => $group,
                'setting_label' => $label,
                'setting_description' => $description,
                'is_public' => $is_public
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
    }
    
    /**
     * Șterge o setare
     */
    public function delete_setting($key) {
        global $wpdb;
        $table = $wpdb->prefix . 'clinica_settings';
        
        $result = $wpdb->delete(
            $table,
            array('setting_key' => $key),
            array('%s')
        );
        
        // Șterge din cache
        unset($this->settings_cache[$key]);
        
        return $result !== false;
    }
    
} 