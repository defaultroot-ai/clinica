# Arhitectura Tehnică - Sistem de Programări Medicale

## 🏗️ Arhitectura Generală

### Pattern-uri de Design
- **MVC (Model-View-Controller)** pentru separarea logicii
- **Singleton** pentru clase de configurare
- **Factory Pattern** pentru crearea obiectelor
- **Observer Pattern** pentru notificări și evenimente
- **Repository Pattern** pentru accesul la date

### Structura de Date

#### Tabele Principale
```sql
-- Programări
wp_clinica_appointments
- id (PRIMARY KEY)
- patient_id (FOREIGN KEY)
- doctor_id (FOREIGN KEY)
- appointment_date (DATETIME)
- duration (INT) -- în minute
- status (ENUM: 'scheduled', 'confirmed', 'cancelled', 'completed')
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

-- Pacienți
wp_clinica_patients
- id (PRIMARY KEY)
- user_id (FOREIGN KEY wp_users)
- cnp (VARCHAR(13) UNIQUE) -- CNP ca username WordPress
- phone (VARCHAR)
- birth_date (DATE)
- gender (ENUM: 'male', 'female', 'other')
- address (TEXT)
- emergency_contact (VARCHAR)
- blood_type (ENUM: 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')
- allergies (TEXT)
- medical_history (TEXT)
- import_source (VARCHAR) -- 'icmed', 'joomla', 'manual'
- import_date (TIMESTAMP)
- created_at (TIMESTAMP)

-- Medici
wp_clinica_doctors
- id (PRIMARY KEY)
- user_id (FOREIGN KEY wp_users)
- first_name (VARCHAR)
- last_name (VARCHAR)
- specialization (VARCHAR)
- license_number (VARCHAR)
- phone (VARCHAR)
- email (VARCHAR)
- schedule_template (JSON)
- created_at (TIMESTAMP)

-- Servicii Medicale
wp_clinica_services
- id (PRIMARY KEY)
- name (VARCHAR)
- description (TEXT)
- duration (INT) -- în minute
- price (DECIMAL)
- category (VARCHAR)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)

-- Programări Servicii
wp_clinica_appointment_services
- id (PRIMARY KEY)
- appointment_id (FOREIGN KEY)
- service_id (FOREIGN KEY)
- price (DECIMAL)
- notes (TEXT)

-- Notificări
wp_clinica_notifications
- id (PRIMARY KEY)
- user_id (FOREIGN KEY)
- type (ENUM: 'email', 'sms', 'push')
- title (VARCHAR)
- message (TEXT)
- status (ENUM: 'pending', 'sent', 'failed')
- scheduled_at (TIMESTAMP)
- sent_at (TIMESTAMP)
- created_at (TIMESTAMP)
```

## 🔌 Hook-uri WordPress

### Action Hooks
```php
// Activare/Dezactivare
do_action('clinica_plugin_activated');
do_action('clinica_plugin_deactivated');

// Programări
do_action('clinica_appointment_created', $appointment_id);
do_action('clinica_appointment_updated', $appointment_id);
do_action('clinica_appointment_cancelled', $appointment_id);
do_action('clinica_appointment_completed', $appointment_id);

// Pacienți
do_action('clinica_patient_registered', $patient_id);
do_action('clinica_patient_updated', $patient_id);

// Medici
do_action('clinica_doctor_registered', $doctor_id);
do_action('clinica_doctor_schedule_updated', $doctor_id);

// Notificări
do_action('clinica_notification_sent', $notification_id);
do_action('clinica_notification_failed', $notification_id);
```

### Filter Hooks
```php
// Programări
apply_filters('clinica_appointment_data', $appointment_data);
apply_filters('clinica_available_slots', $available_slots, $doctor_id, $date);
apply_filters('clinica_appointment_duration', $duration, $service_id);

// Pacienți
apply_filters('clinica_patient_data', $patient_data);
apply_filters('clinica_patient_validation', $validation_result, $patient_data);

// Medici
apply_filters('clinica_doctor_schedule', $schedule, $doctor_id);
apply_filters('clinica_doctor_services', $services, $doctor_id);

// Notificări
apply_filters('clinica_notification_template', $template, $notification_type);
apply_filters('clinica_notification_recipients', $recipients, $notification_data);
```

## 🔐 Securitate

### Autentificare și Autorizare
```php
// Verificare permisiuni
function clinica_check_permission($capability, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return user_can($user_id, $capability);
}

// Capabilități personalizate
const CLINICA_CAPABILITIES = [
    'clinica_manage_appointments',
    'clinica_view_patients',
    'clinica_edit_patients',
    'clinica_manage_doctors',
    'clinica_view_reports',
    'clinica_manage_settings',
    'clinica_manage_addons'
];
```

### Validare și Sanitizare
```php
// Validare date programări
function clinica_validate_appointment_data($data) {
    $errors = [];
    
    if (empty($data['patient_id'])) {
        $errors[] = 'Patient ID is required';
    }
    
    if (empty($data['doctor_id'])) {
        $errors[] = 'Doctor ID is required';
    }
    
    if (empty($data['appointment_date'])) {
        $errors[] = 'Appointment date is required';
    } elseif (!clinica_is_valid_datetime($data['appointment_date'])) {
        $errors[] = 'Invalid appointment date format';
    }
    
    return $errors;
}

// Sanitizare date
function clinica_sanitize_appointment_data($data) {
    return [
        'patient_id' => intval($data['patient_id']),
        'doctor_id' => intval($data['doctor_id']),
        'appointment_date' => sanitize_text_field($data['appointment_date']),
        'notes' => sanitize_textarea_field($data['notes']),
        'status' => sanitize_text_field($data['status'])
    ];
}
```

## 📡 API REST

### Endpoints Principale
```php
// Programări
GET    /wp-json/clinica/v1/appointments
POST   /wp-json/clinica/v1/appointments
GET    /wp-json/clinica/v1/appointments/{id}
PUT    /wp-json/clinica/v1/appointments/{id}
DELETE /wp-json/clinica/v1/appointments/{id}

// Pacienți
GET    /wp-json/clinica/v1/patients
POST   /wp-json/clinica/v1/patients
GET    /wp-json/clinica/v1/patients/{id}
PUT    /wp-json/clinica/v1/patients/{id}
DELETE /wp-json/clinica/v1/patients/{id}

// Medici
GET    /wp-json/clinica/v1/doctors
POST   /wp-json/clinica/v1/doctors
GET    /wp-json/clinica/v1/doctors/{id}
PUT    /wp-json/clinica/v1/doctors/{id}
DELETE /wp-json/clinica/v1/doctors/{id}

// Servicii
GET    /wp-json/clinica/v1/services
GET    /wp-json/clinica/v1/services/{id}

// Sloturi disponibile
GET    /wp-json/clinica/v1/availability/{doctor_id}/{date}
```

### Autentificare API
```php
// JWT Token pentru API
function clinica_generate_api_token($user_id) {
    $payload = [
        'user_id' => $user_id,
        'exp' => time() + (24 * 60 * 60), // 24 ore
        'iat' => time()
    ];
    
    return JWT::encode($payload, CLINICA_JWT_SECRET, 'HS256');
}

// Verificare token
function clinica_verify_api_token($token) {
    try {
        $payload = JWT::decode($token, CLINICA_JWT_SECRET, ['HS256']);
        return $payload->user_id;
    } catch (Exception $e) {
        return false;
    }
}
```

## 🎨 Frontend Architecture

### Componente React/Vue
```javascript
// Calendar Component
class AppointmentCalendar extends React.Component {
    state = {
        appointments: [],
        selectedDate: new Date(),
        loading: false
    };
    
    componentDidMount() {
        this.loadAppointments();
    }
    
    loadAppointments = async () => {
        this.setState({ loading: true });
        try {
            const response = await fetch('/wp-json/clinica/v1/appointments');
            const appointments = await response.json();
            this.setState({ appointments, loading: false });
        } catch (error) {
            console.error('Error loading appointments:', error);
            this.setState({ loading: false });
        }
    };
    
    render() {
        // Calendar UI implementation
    }
}

// Form Component pentru Programări
class AppointmentForm extends React.Component {
    state = {
        patient_id: '',
        doctor_id: '',
        service_id: '',
        appointment_date: '',
        notes: '',
        errors: {}
    };
    
    handleSubmit = async (e) => {
        e.preventDefault();
        const errors = this.validateForm();
        
        if (Object.keys(errors).length === 0) {
            try {
                const response = await fetch('/wp-json/clinica/v1/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    body: JSON.stringify(this.state)
                });
                
                if (response.ok) {
                    this.props.onSuccess();
                }
            } catch (error) {
                console.error('Error creating appointment:', error);
            }
        } else {
            this.setState({ errors });
        }
    };
    
    render() {
        // Form UI implementation
    }
}
```

## 🔄 Sistem de Notificări

### Template Engine
```php
class Clinica_Notification_Template {
    private $templates = [
        'appointment_confirmation' => [
            'subject' => 'Confirmare programare - {clinic_name}',
            'body' => 'Stimate {patient_name}, programarea dumneavoastră pentru {appointment_date} cu Dr. {doctor_name} a fost confirmată.'
        ],
        'appointment_reminder' => [
            'subject' => 'Reminder programare - {clinic_name}',
            'body' => 'Vă reamintim că aveți o programare mâine la {appointment_time} cu Dr. {doctor_name}.'
        ],
        'appointment_cancellation' => [
            'subject' => 'Programare anulată - {clinic_name}',
            'body' => 'Programarea dumneavoastră pentru {appointment_date} a fost anulată.'
        ]
    ];
    
    public function get_template($type) {
        return $this->templates[$type] ?? null;
    }
    
    public function render_template($type, $data) {
        $template = $this->get_template($type);
        if (!$template) {
            return false;
        }
        
        $subject = $this->replace_placeholders($template['subject'], $data);
        $body = $this->replace_placeholders($template['body'], $data);
        
        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
    
    private function replace_placeholders($text, $data) {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
}
```

### Queue System pentru Notificări
```php
class Clinica_Notification_Queue {
    public function add_to_queue($notification_data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'clinica_notifications',
            [
                'user_id' => $notification_data['user_id'],
                'type' => $notification_data['type'],
                'title' => $notification_data['title'],
                'message' => $notification_data['message'],
                'status' => 'pending',
                'scheduled_at' => $notification_data['scheduled_at'] ?? current_time('mysql'),
                'created_at' => current_time('mysql')
            ]
        );
        
        return $wpdb->insert_id;
    }
    
    public function process_queue() {
        global $wpdb;
        
        $pending_notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}clinica_notifications 
                 WHERE status = 'pending' 
                 AND scheduled_at <= %s",
                current_time('mysql')
            )
        );
        
        foreach ($pending_notifications as $notification) {
            $this->send_notification($notification);
        }
    }
    
    private function send_notification($notification) {
        switch ($notification->type) {
            case 'email':
                $this->send_email($notification);
                break;
            case 'sms':
                $this->send_sms($notification);
                break;
            case 'push':
                $this->send_push($notification);
                break;
        }
    }
}
```

## 📊 Cache și Optimizare

### Cache Strategy
```php
class Clinica_Cache {
    private $cache_group = 'clinica';
    private $cache_time = 3600; // 1 oră
    
    public function get($key) {
        return wp_cache_get($key, $this->cache_group);
    }
    
    public function set($key, $data, $time = null) {
        $time = $time ?: $this->cache_time;
        return wp_cache_set($key, $data, $this->cache_group, $time);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, $this->cache_group);
    }
    
    public function flush_group() {
        return wp_cache_flush_group($this->cache_group);
    }
}

// Utilizare cache
$cache = new Clinica_Cache();
$appointments = $cache->get('appointments_' . $doctor_id . '_' . $date);

if (!$appointments) {
    $appointments = $this->get_appointments($doctor_id, $date);
    $cache->set('appointments_' . $doctor_id . '_' . $date, $appointments);
}
```

## 🧪 Testing Strategy

### Unit Tests
```php
class Clinica_Appointment_Test extends WP_UnitTestCase {
    public function test_create_appointment() {
        $appointment_data = [
            'patient_id' => 1,
            'doctor_id' => 1,
            'appointment_date' => '2024-01-15 10:00:00',
            'notes' => 'Test appointment'
        ];
        
        $appointment_id = clinica_create_appointment($appointment_data);
        
        $this->assertIsInt($appointment_id);
        $this->assertGreaterThan(0, $appointment_id);
        
        $appointment = clinica_get_appointment($appointment_id);
        $this->assertEquals($appointment_data['patient_id'], $appointment->patient_id);
    }
    
    public function test_validate_appointment_data() {
        $invalid_data = [
            'patient_id' => '',
            'doctor_id' => 'invalid',
            'appointment_date' => 'invalid-date'
        ];
        
        $errors = clinica_validate_appointment_data($invalid_data);
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Patient ID is required', $errors);
    }
}
```

### Integration Tests
```php
class Clinica_API_Test extends WP_Test_REST_TestCase {
    public function test_appointments_endpoint() {
        $request = new WP_REST_Request('GET', '/clinica/v1/appointments');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
    }
    
    public function test_create_appointment_via_api() {
        $request = new WP_REST_Request('POST', '/clinica/v1/appointments');
        $request->set_param('patient_id', 1);
        $request->set_param('doctor_id', 1);
        $request->set_param('appointment_date', '2024-01-15 10:00:00');
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(201, $response->get_status());
    }
}
```

## 📈 Monitoring și Logging

### Logging System
```php
class Clinica_Logger {
    private $log_file;
    
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/clinica-logs/appointments.log';
        $this->ensure_log_directory();
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
}
```

Această arhitectură oferă o bază solidă pentru dezvoltarea sistemului de programări medicale, cu accent pe scalabilitate, securitate și mentenabilitate. 