# Dezvoltarea Adonurilor - Sistem de Programări Medicale

## 🧩 Arhitectura Modulară

### Principii de Design pentru Adonuri

1. **Loose Coupling**: Adonurile nu depind unul de altul
2. **High Cohesion**: Fiecare adon are o responsabilitate clară
3. **Extensibility**: Ușor de extins cu funcționalități noi
4. **Backward Compatibility**: Adonurile vechi funcționează cu versiuni noi
5. **Performance**: Adonurile nu afectează performanța sistemului principal

### Structura Standard pentru Adonuri

```
clinica-addons/
├── clinica-pacienti/
│   ├── clinica-pacienti.php
│   ├── includes/
│   │   ├── class-pacienti-loader.php
│   │   ├── class-pacienti-admin.php
│   │   ├── class-pacienti-public.php
│   │   └── class-pacienti-api.php
│   ├── admin/
│   │   ├── js/
│   │   ├── css/
│   │   └── partials/
│   ├── public/
│   │   ├── js/
│   │   ├── css/
│   │   └── partials/
│   ├── database/
│   │   └── schema.sql
│   ├── languages/
│   └── readme.txt
├── clinica-facturare/
├── clinica-rapoarte/
├── clinica-telemedicina/
└── clinica-laborator/
```

## 📦 Adon 1: Pacienți Avansați

### Funcționalități
- Gestionarea completă a pacienților
- Istoric medical detaliat
- Dosare medicale electronice
- Istoric programări
- Note și observații medicale
- Import/Export date pacienți

### Tabele de Bază de Date
```sql
-- Istoric Medical
wp_clinica_patient_medical_history
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- condition_name (VARCHAR)
- diagnosis_date (DATE)
- treatment (TEXT)
- status (ENUM: 'active', 'resolved', 'chronic')
- doctor_id (FOREIGN KEY)
- created_at (TIMESTAMP)

-- Alergii
wp_clinica_patient_allergies
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- allergy_name (VARCHAR)
- severity (ENUM: 'mild', 'moderate', 'severe')
- symptoms (TEXT)
- created_at (TIMESTAMP)

-- Medicamente
wp_clinica_patient_medications
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- medication_name (VARCHAR)
- dosage (VARCHAR)
- frequency (VARCHAR)
- start_date (DATE)
- end_date (DATE)
- prescribed_by (FOREIGN KEY)
- created_at (TIMESTAMP)

-- Note Medicale
wp_clinica_patient_notes
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- appointment_id (FOREIGN KEY)
- note_type (ENUM: 'consultation', 'follow_up', 'emergency')
- content (TEXT)
- doctor_id (FOREIGN KEY)
- created_at (TIMESTAMP)
```

### Hook-uri Specifice
```php
// Hook-uri pentru istoric medical
do_action('clinica_patient_medical_history_added', $patient_id, $history_data);
do_action('clinica_patient_allergy_added', $patient_id, $allergy_data);
do_action('clinica_patient_medication_added', $patient_id, $medication_data);

// Hook-uri pentru note
do_action('clinica_patient_note_added', $patient_id, $note_data);
do_action('clinica_patient_note_updated', $note_id, $note_data);

// Filter-uri pentru validare
apply_filters('clinica_patient_medical_history_validation', $validation_result, $history_data);
apply_filters('clinica_patient_allergy_validation', $validation_result, $allergy_data);
```

## 💰 Adon 2: Facturare și Plăți

### Funcționalități
- Generare facturi automate
- Integrare plăți online (Stripe, PayPal, etc.)
- Rapoarte financiare
- Gestionarea asigurărilor
- Facturare pentru servicii multiple
- Sistem de discount-uri

### Tabele de Bază de Date
```sql
-- Facturi
wp_clinica_invoices
- id (PRIMARY KEY)
- appointment_id (FOREIGN KEY)
- patient_id (FOREIGN KEY)
- invoice_number (VARCHAR)
- total_amount (DECIMAL)
- tax_amount (DECIMAL)
- discount_amount (DECIMAL)
- final_amount (DECIMAL)
- status (ENUM: 'draft', 'sent', 'paid', 'overdue', 'cancelled')
- due_date (DATE)
- paid_date (DATE)
- created_at (TIMESTAMP)

-- Elemente Factură
wp_clinica_invoice_items
- id (PRIMARY KEY)
- invoice_id (FOREIGN KEY)
- service_id (FOREIGN KEY)
- description (TEXT)
- quantity (INT)
- unit_price (DECIMAL)
- total_price (DECIMAL)
- created_at (TIMESTAMP)

-- Plăți
wp_clinica_payments
- id (PRIMARY KEY)
- invoice_id (FOREIGN KEY)
- payment_method (VARCHAR)
- transaction_id (VARCHAR)
- amount (DECIMAL)
- status (ENUM: 'pending', 'completed', 'failed', 'refunded')
- gateway_response (TEXT)
- created_at (TIMESTAMP)

-- Asigurări
wp_clinica_insurance
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- insurance_provider (VARCHAR)
- policy_number (VARCHAR)
- coverage_type (VARCHAR)
- coverage_percentage (DECIMAL)
- valid_from (DATE)
- valid_until (DATE)
- created_at (TIMESTAMP)
```

### Integrări Plăți
```php
class Clinica_Payment_Gateway {
    private $gateways = [
        'stripe' => 'Clinica_Stripe_Gateway',
        'paypal' => 'Clinica_PayPal_Gateway',
        'card' => 'Clinica_Card_Gateway'
    ];
    
    public function process_payment($invoice_id, $gateway, $payment_data) {
        $gateway_class = $this->gateways[$gateway] ?? null;
        
        if (!$gateway_class || !class_exists($gateway_class)) {
            throw new Exception('Payment gateway not found');
        }
        
        $gateway_instance = new $gateway_class();
        return $gateway_instance->process($invoice_id, $payment_data);
    }
}

class Clinica_Stripe_Gateway {
    public function process($invoice_id, $payment_data) {
        // Integrare cu Stripe API
        $stripe = new \Stripe\StripeClient(CLINICA_STRIPE_SECRET_KEY);
        
        try {
            $payment_intent = $stripe->paymentIntents->create([
                'amount' => $payment_data['amount'] * 100, // Stripe folosește cenți
                'currency' => 'ron',
                'payment_method' => $payment_data['payment_method_id'],
                'confirm' => true,
                'return_url' => $payment_data['return_url']
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $payment_intent->id,
                'status' => $payment_intent->status
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

## 📊 Adon 3: Rapoarte și Analytics

### Funcționalități
- Dashboard analitice în timp real
- Statistici programări
- Rapoarte performanță medici
- Analiza veniturilor
- Export date în multiple formate
- Grafice interactive

### Tabele de Bază de Date
```sql
-- Statistici Programări
wp_clinica_appointment_stats
- id (PRIMARY KEY)
- doctor_id (FOREIGN KEY)
- date (DATE)
- total_appointments (INT)
- completed_appointments (INT)
- cancelled_appointments (INT)
- no_show_appointments (INT)
- average_duration (INT)
- created_at (TIMESTAMP)

-- Statistici Venituri
wp_clinica_revenue_stats
- id (PRIMARY KEY)
- doctor_id (FOREIGN KEY)
- date (DATE)
- total_revenue (DECIMAL)
- total_invoices (INT)
- paid_invoices (INT)
- pending_invoices (INT)
- average_invoice_amount (DECIMAL)
- created_at (TIMESTAMP)

-- Rapoarte Personalizate
wp_clinica_custom_reports
- id (PRIMARY KEY)
- name (VARCHAR)
- description (TEXT)
- query_sql (TEXT)
- parameters (JSON)
- schedule (VARCHAR) -- cron schedule
- last_run (TIMESTAMP)
- created_at (TIMESTAMP)
```

### API pentru Rapoarte
```php
class Clinica_Reports_API {
    public function get_appointment_stats($doctor_id, $start_date, $end_date) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT 
                DATE(appointment_date) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                AVG(duration) as avg_duration
             FROM {$wpdb->prefix}clinica_appointments
             WHERE doctor_id = %d 
             AND appointment_date BETWEEN %s AND %s
             GROUP BY DATE(appointment_date)
             ORDER BY date",
            $doctor_id, $start_date, $end_date
        );
        
        return $wpdb->get_results($query);
    }
    
    public function get_revenue_stats($doctor_id, $start_date, $end_date) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT 
                DATE(i.created_at) as date,
                SUM(i.final_amount) as total_revenue,
                COUNT(*) as total_invoices,
                SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                AVG(i.final_amount) as avg_invoice
             FROM {$wpdb->prefix}clinica_invoices i
             JOIN {$wpdb->prefix}clinica_appointments a ON i.appointment_id = a.id
             WHERE a.doctor_id = %d 
             AND i.created_at BETWEEN %s AND %s
             GROUP BY DATE(i.created_at)
             ORDER BY date",
            $doctor_id, $start_date, $end_date
        );
        
        return $wpdb->get_results($query);
    }
    
    public function export_data($report_type, $parameters, $format = 'csv') {
        $data = $this->get_report_data($report_type, $parameters);
        
        switch ($format) {
            case 'csv':
                return $this->export_to_csv($data);
            case 'excel':
                return $this->export_to_excel($data);
            case 'pdf':
                return $this->export_to_pdf($data);
            case 'json':
                return json_encode($data);
            default:
                throw new Exception('Unsupported export format');
        }
    }
}
```

## 🏥 Adon 4: Telemedicină

### Funcționalități
- Consultanțe video în timp real
- Chat medical securizat
- Încărcare și partajare documente
- Prescripții electronice
- Programări pentru telemedicină
- Istoric consultații online

### Tabele de Bază de Date
```sql
-- Consultații Online
wp_clinica_telemedicine_consultations
- id (PRIMARY KEY)
- appointment_id (FOREIGN KEY)
- room_id (VARCHAR)
- start_time (TIMESTAMP)
- end_time (TIMESTAMP)
- status (ENUM: 'scheduled', 'in_progress', 'completed', 'cancelled')
- notes (TEXT)
- recording_url (VARCHAR)
- created_at (TIMESTAMP)

-- Mesaje Chat
wp_clinica_telemedicine_messages
- id (PRIMARY KEY)
- consultation_id (FOREIGN KEY)
- sender_id (FOREIGN KEY)
- sender_type (ENUM: 'doctor', 'patient')
- message (TEXT)
- message_type (ENUM: 'text', 'file', 'image')
- file_url (VARCHAR)
- created_at (TIMESTAMP)

-- Documente Partajate
wp_clinica_telemedicine_documents
- id (PRIMARY KEY)
- consultation_id (FOREIGN KEY)
- uploaded_by (FOREIGN KEY)
- file_name (VARCHAR)
- file_path (VARCHAR)
- file_size (INT)
- mime_type (VARCHAR)
- created_at (TIMESTAMP)

-- Prescripții Electronice
wp_clinica_electronic_prescriptions
- id (PRIMARY KEY)
- consultation_id (FOREIGN KEY)
- patient_id (FOREIGN KEY)
- doctor_id (FOREIGN KEY)
- prescription_number (VARCHAR)
- medications (JSON)
- instructions (TEXT)
- valid_until (DATE)
- status (ENUM: 'active', 'expired', 'cancelled')
- created_at (TIMESTAMP)
```

### Integrare Video Call
```php
class Clinica_Video_Call {
    private $provider = 'twilio'; // sau 'agora', 'zoom'
    
    public function create_room($consultation_id) {
        switch ($this->provider) {
            case 'twilio':
                return $this->create_twilio_room($consultation_id);
            case 'agora':
                return $this->create_agora_room($consultation_id);
            case 'zoom':
                return $this->create_zoom_room($consultation_id);
        }
    }
    
    private function create_twilio_room($consultation_id) {
        $client = new Twilio\Rest\Client(CLINICA_TWILIO_SID, CLINICA_TWILIO_TOKEN);
        
        try {
            $room = $client->video->v1->rooms
                ->create([
                    'uniqueName' => 'clinica_consultation_' . $consultation_id,
                    'type' => 'group',
                    'recordParticipantsOnConnect' => true
                ]);
            
            return [
                'room_id' => $room->sid,
                'room_name' => $room->uniqueName,
                'status' => $room->status
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to create video room: ' . $e->getMessage());
        }
    }
    
    public function generate_access_token($room_id, $user_id, $user_type) {
        // Generare token pentru accesul la camera video
        $token = new Twilio\Jwt\AccessToken(
            CLINICA_TWILIO_SID,
            CLINICA_TWILIO_API_KEY,
            CLINICA_TWILIO_API_SECRET,
            3600, // 1 oră
            $user_id
        );
        
        $videoGrant = new Twilio\Jwt\Grants\VideoGrant();
        $videoGrant->room = $room_id;
        
        $token->addGrant($videoGrant);
        
        return $token->toJWT();
    }
}
```

## 🧪 Adon 5: Laborator și Imagistică

### Funcționalități
- Gestionarea rezultatelor de laborator
- Încărcare și organizare analize
- Istoric rezultate
- Notificări rezultate disponibile
- Integrare cu laboratoare partenere
- Interpretare automată rezultate

### Tabele de Bază de Date
```sql
-- Rezultate Laborator
wp_clinica_lab_results
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- doctor_id (FOREIGN KEY)
- lab_id (FOREIGN KEY)
- test_name (VARCHAR)
- test_category (VARCHAR)
- result_value (VARCHAR)
- unit (VARCHAR)
- reference_range (VARCHAR)
- is_abnormal (BOOLEAN)
- interpretation (TEXT)
- test_date (DATE)
- result_date (DATE)
- status (ENUM: 'pending', 'completed', 'abnormal')
- created_at (TIMESTAMP)

-- Laboratoare
wp_clinica_laboratories
- id (PRIMARY KEY)
- name (VARCHAR)
- address (TEXT)
- phone (VARCHAR)
- email (VARCHAR)
- api_key (VARCHAR)
- api_endpoint (VARCHAR)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)

-- Imagistică Medicală
wp_clinica_medical_imaging
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- doctor_id (FOREIGN KEY)
- imaging_type (VARCHAR) -- X-ray, MRI, CT, etc.
- body_part (VARCHAR)
- image_url (VARCHAR)
- report (TEXT)
- radiologist_id (FOREIGN KEY)
- imaging_date (DATE)
- report_date (DATE)
- status (ENUM: 'pending', 'completed', 'reviewed')
- created_at (TIMESTAMP)

-- Interpretări Automate
wp_clinica_ai_interpretations
- id (PRIMARY KEY)
- result_id (FOREIGN KEY)
- ai_model (VARCHAR)
- confidence_score (DECIMAL)
- interpretation (TEXT)
- recommendations (TEXT)
- created_at (TIMESTAMP)
```

### Integrare cu Laboratoare
```php
class Clinica_Lab_Integration {
    private $lab_apis = [
        'medlife' => 'Clinica_Medlife_Lab_API',
        'synevo' => 'Clinica_Synevo_Lab_API',
        'biodiagnostic' => 'Clinica_Biodiagnostic_Lab_API'
    ];
    
    public function fetch_results($lab_id, $patient_data) {
        $lab = $this->get_lab($lab_id);
        $api_class = $this->lab_apis[$lab->api_type] ?? null;
        
        if (!$api_class || !class_exists($api_class)) {
            throw new Exception('Lab API not supported');
        }
        
        $api = new $api_class($lab->api_key, $lab->api_endpoint);
        return $api->fetch_results($patient_data);
    }
    
    public function interpret_results($results) {
        // Integrare cu AI pentru interpretarea rezultatelor
        $ai_model = new Clinica_AI_Interpreter();
        
        foreach ($results as $result) {
            $interpretation = $ai_model->interpret($result);
            
            if ($interpretation) {
                $this->save_interpretation($result->id, $interpretation);
            }
        }
    }
}

class Clinica_AI_Interpreter {
    public function interpret($lab_result) {
        // Integrare cu servicii AI (Google Health, IBM Watson, etc.)
        $ai_endpoint = CLINICA_AI_ENDPOINT;
        $api_key = CLINICA_AI_API_KEY;
        
        $data = [
            'test_name' => $lab_result->test_name,
            'result_value' => $lab_result->result_value,
            'unit' => $lab_result->unit,
            'reference_range' => $lab_result->reference_range,
            'patient_age' => $this->get_patient_age($lab_result->patient_id),
            'patient_gender' => $this->get_patient_gender($lab_result->patient_id)
        ];
        
        $response = wp_remote_post($ai_endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $interpretation = json_decode($body, true);
        
        return [
            'interpretation' => $interpretation['interpretation'],
            'confidence_score' => $interpretation['confidence'],
            'recommendations' => $interpretation['recommendations']
        ];
    }
}
```

## 🔧 Dezvoltarea unui Adon Nou

### Template pentru Adon Nou
```php
<?php
/**
 * Plugin Name: Clinica - [Nume Adon]
 * Plugin URI: https://clinica.example.com/addons/[nume-adon]
 * Description: [Descriere adon]
 * Version: 1.0.0
 * Author: [Nume Autor]
 * Author URI: [URL Autor]
 * License: GPL v2 or later
 * Text Domain: clinica-[nume-adon]
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLINICA_[ADON]_VERSION', '1.0.0');
define('CLINICA_[ADON]_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLINICA_[ADON]_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLINICA_[ADON]_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if main Clinica plugin is active
if (!class_exists('Clinica_Plugin')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo 'Clinica [Nume Adon] requires the main Clinica plugin to be installed and activated.';
        echo '</p></div>';
    });
    return;
}

// Main plugin class
class Clinica_[Adon]_Plugin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('clinica_plugin_activated', [$this, 'activate']);
        add_action('clinica_plugin_deactivated', [$this, 'deactivate']);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'clinica-[nume-adon]',
            false,
            dirname(CLINICA_[ADON]_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function init() {
        // Initialize plugin functionality
        $this->load_dependencies();
        $this->setup_admin();
        $this->setup_public();
    }
    
    private function load_dependencies() {
        require_once CLINICA_[ADON]_PLUGIN_DIR . 'includes/class-[adon]-loader.php';
        require_once CLINICA_[ADON]_PLUGIN_DIR . 'includes/class-[adon]-admin.php';
        require_once CLINICA_[ADON]_PLUGIN_DIR . 'includes/class-[adon]-public.php';
    }
    
    private function setup_admin() {
        if (is_admin()) {
            new Clinica_[Adon]_Admin();
        }
    }
    
    private function setup_public() {
        new Clinica_[Adon]_Public();
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup if necessary
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = file_get_contents(CLINICA_[ADON]_PLUGIN_DIR . 'database/schema.sql');
        $sql = str_replace('{prefix}', $wpdb->prefix, $sql);
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        $defaults = [
            'option_1' => 'default_value_1',
            'option_2' => 'default_value_2'
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option('clinica_[adon]_' . $option) === false) {
                add_option('clinica_[adon]_' . $option, $value);
            }
        }
    }
}

// Initialize plugin
Clinica_[Adon]_Plugin::get_instance();

// Activation/Deactivation hooks
register_activation_hook(__FILE__, [Clinica_[Adon]_Plugin::get_instance(), 'activate']);
register_deactivation_hook(__FILE__, [Clinica_[Adon]_Plugin::get_instance(), 'deactivate']);
```

### Checklist pentru Dezvoltarea Adonului

- [ ] **Planificare**
  - [ ] Definirea funcționalităților
  - [ ] Designul bazei de date
  - [ ] Planul de testare

- [ ] **Dezvoltare**
  - [ ] Structura de fișiere
  - [ ] Clasele principale
  - [ ] API endpoints
  - [ ] Interfața admin
  - [ ] Interfața publică

- [ ] **Integrare**
  - [ ] Hook-uri cu plugin-ul principal
  - [ ] Compatibilitate cu alte adonuri
  - [ ] Testarea integrației

- [ ] **Testare**
  - [ ] Unit tests
  - [ ] Integration tests
  - [ ] User acceptance testing
  - [ ] Performance testing

- [ ] **Documentație**
  - [ ] README pentru adon
  - [ ] Documentație API
  - [ ] Ghid de instalare
  - [ ] Ghid de utilizare

- [ ] **Lansare**
  - [ ] Versioning
  - [ ] Changelog
  - [ ] Package pentru distribuție
  - [ ] Lansare și suport

Această arhitectură modulară permite dezvoltarea flexibilă și scalabilă a sistemului de programări medicale, cu posibilitatea de a adăuga funcționalități noi prin adonuri specializate. 