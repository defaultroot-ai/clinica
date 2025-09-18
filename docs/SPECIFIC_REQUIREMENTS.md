# Cerințe Specifice - Sistem de Programări Medicale

## 👥 Roluri de Utilizatori

### Roluri Definitive
1. **Manager** - Acces complet la sistem, gestionare utilizatori, rapoarte
2. **Doctor** - Gestionare programări proprii, pacienți, consultații
3. **Asistent** - Suport pentru doctori, gestionare programări
4. **Receptionist** - Programări, check-in/check-out pacienți
5. **Pacient** - Programări proprii, istoric medical personal

### Capabilități per Rol
```php
// Definirea capabilităților pentru fiecare rol
const CLINICA_CAPABILITIES = [
    // Manager - Acces complet
    'clinica_manage_all' => [
        'clinica_manage_users',
        'clinica_manage_doctors',
        'clinica_manage_appointments',
        'clinica_manage_patients',
        'clinica_view_reports',
        'clinica_manage_settings',
        'clinica_manage_addons',
        'clinica_import_data',
        'clinica_export_data'
    ],
    
    // Doctor - Gestionare proprii programări și pacienți
    'clinica_doctor' => [
        'clinica_manage_own_appointments',
        'clinica_view_own_patients',
        'clinica_edit_own_patients',
        'clinica_view_own_reports',
        'clinica_manage_own_schedule'
    ],
    
    // Asistent - Suport pentru doctori
    'clinica_assistant' => [
        'clinica_manage_appointments',
        'clinica_view_patients',
        'clinica_edit_patients',
        'clinica_view_reports',
        'clinica_manage_schedules'
    ],
    
    // Receptionist - Programări și check-in
    'clinica_receptionist' => [
        'clinica_create_appointments',
        'clinica_edit_appointments',
        'clinica_view_patients',
        'clinica_check_in_patients',
        'clinica_check_out_patients'
    ],
    
    // Pacient - Acces limitat la propriile date
    'clinica_patient' => [
        'clinica_view_own_appointments',
        'clinica_create_own_appointments',
        'clinica_cancel_own_appointments',
        'clinica_view_own_medical_history',
        'clinica_view_own_prescriptions'
    ]
];
```

## 🔐 Sistem de Autentificare Avansat

### Metode de Autentificare
- **Username** (nume de utilizator)
- **Email** (adresa de email)
- **Telefon** (numărul de telefon)

### Implementare Autentificare
```php
class Clinica_Authentication {
    public function authenticate($identifier, $password) {
        // Caută utilizatorul după username, email sau telefon
        $user = $this->find_user_by_identifier($identifier);
        
        if (!$user) {
            return new WP_Error('invalid_credentials', 'Credențiale invalide');
        }
        
        // Verifică parola
        if (!wp_check_password($password, $user->user_pass)) {
            return new WP_Error('invalid_credentials', 'Credențiale invalide');
        }
        
        // Verifică dacă utilizatorul are rol Clinica
        if (!$this->has_clinica_role($user)) {
            return new WP_Error('no_access', 'Nu aveți acces la sistemul medical');
        }
        
        return $user;
    }
    
    private function find_user_by_identifier($identifier) {
        global $wpdb;
        
        // Caută după username
        $user = get_user_by('login', $identifier);
        if ($user) return $user;
        
        // Caută după email
        $user = get_user_by('email', $identifier);
        if ($user) return $user;
        
        // Caută după telefon
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT u.* FROM {$wpdb->users} u 
             JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id 
             WHERE p.phone = %s",
            $identifier
        ));
        
        return $user;
    }
    
    private function has_clinica_role($user) {
        $clinica_roles = ['clinica_manager', 'clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_patient'];
        
        foreach ($clinica_roles as $role) {
            if (user_can($user->ID, $role)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### Formular de Login Personalizat
```php
class Clinica_Login_Form {
    public function render_login_form() {
        ?>
        <form class="clinica-login-form" method="post">
            <?php wp_nonce_field('clinica_login', 'clinica_login_nonce'); ?>
            
            <div class="form-group">
                <label for="clinica_identifier">Username, Email sau Telefon</label>
                <input type="text" id="clinica_identifier" name="clinica_identifier" required>
            </div>
            
            <div class="form-group">
                <label for="clinica_password">Parolă</label>
                <input type="password" id="clinica_password" name="clinica_password" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="clinica_remember" value="1">
                    Ține-mă minte
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Autentificare</button>
            
            <div class="form-links">
                <a href="<?php echo wp_lostpassword_url(); ?>">Ai uitat parola?</a>
                <a href="<?php echo home_url('/register'); ?>">Înregistrare pacient nou</a>
            </div>
        </form>
        <?php
    }
}
```

## 📊 Import Pacienți (4000+ pacienți)

### Sursa de Date
- **Platforma ICMED** - Sistem medical existent
- **Website Joomla + Community Builder** - Sistem web existent

### Integrare cu Utilizatorii WordPress
- **CNP ca username** pentru toți pacienții
- **Creare automată utilizator WordPress** la import
- **Sincronizare perfectă** între profilul pacient și utilizatorul WordPress
- **Validare CNP** cu algoritm matematic
- **Email cu credențiale** trimis automat

### Strategia de Import
```php
class Clinica_Data_Import {
    private $import_sources = [
        'icmed' => 'Clinica_ICMED_Importer',
        'joomla' => 'Clinica_Joomla_Importer'
    ];
    
    public function import_patients($source, $data_file) {
        $importer_class = $this->import_sources[$source] ?? null;
        
        if (!$importer_class || !class_exists($importer_class)) {
            throw new Exception('Import source not supported');
        }
        
        $importer = new $importer_class();
        return $importer->import($data_file);
    }
    
    public function validate_import_data($data) {
        $errors = [];
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'birth_date'];
        
        foreach ($data as $index => $patient) {
            foreach ($required_fields as $field) {
                if (empty($patient[$field])) {
                    $errors[] = "Rândul {$index}: Câmpul {$field} este obligatoriu";
                }
            }
            
            // Validare email
            if (!empty($patient['email']) && !is_email($patient['email'])) {
                $errors[] = "Rândul {$index}: Email invalid";
            }
            
            // Validare telefon
            if (!empty($patient['phone']) && !$this->is_valid_phone($patient['phone'])) {
                $errors[] = "Rândul {$index}: Telefon invalid";
            }
        }
        
        return $errors;
    }
}
```

### Import din ICMED
```php
class Clinica_ICMED_Importer {
    public function import($data_file) {
        $patients = $this->parse_icmed_data($data_file);
        $imported = 0;
        $errors = [];
        
        foreach ($patients as $patient_data) {
            try {
                $patient_id = $this->create_patient($patient_data);
                $imported++;
                
                // Log import
                $this->log_import($patient_id, $patient_data, 'icmed');
                
            } catch (Exception $e) {
                $errors[] = "Eroare la importul pacientului {$patient_data['name']}: " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($patients)
        ];
    }
    
    private function parse_icmed_data($file) {
        // Parsare fișier ICMED (CSV, XML, sau API)
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'csv':
                return $this->parse_csv($file);
            case 'xml':
                return $this->parse_xml($file);
            case 'json':
                return $this->parse_json($file);
            default:
                throw new Exception('Format fișier nesuportat');
        }
    }
    
    private function create_patient($data) {
        // Creează utilizator WordPress
        $user_data = [
            'user_login' => $this->generate_username($data['first_name'], $data['last_name']),
            'user_email' => $data['email'],
            'user_pass' => wp_generate_password(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => 'clinica_patient'
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            throw new Exception('Eroare la crearea utilizatorului: ' . $user_id->get_error_message());
        }
        
        // Creează profilul pacientului
        $patient_data = [
            'user_id' => $user_id,
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'medical_history' => $data['medical_history'] ?? '',
            'icmed_id' => $data['icmed_id'] ?? null, // Pentru referință
            'import_source' => 'icmed',
            'import_date' => current_time('mysql')
        ];
        
        $this->create_patient_profile($patient_data);
        
        // Trimite email cu credențiale
        $this->send_welcome_email($user_id, $user_data['user_login'], $user_data['user_pass']);
        
        return $user_id;
    }
}
```

### Import din Joomla + Community Builder
```php
class Clinica_Joomla_Importer {
    public function import($joomla_db_config) {
        // Conectare la baza de date Joomla
        $joomla_db = new mysqli(
            $joomla_db_config['host'],
            $joomla_db_config['username'],
            $joomla_db_config['password'],
            $joomla_db_config['database']
        );
        
        if ($joomla_db->connect_error) {
            throw new Exception('Eroare la conectarea la baza de date Joomla');
        }
        
        // Query pentru extragerea datelor
        $query = "
            SELECT 
                u.id,
                u.username,
                u.email,
                u.name,
                u.registerDate,
                cb.phone,
                cb.birth_date,
                cb.gender,
                cb.address
            FROM {$joomla_db_config['prefix']}users u
            LEFT JOIN {$joomla_db_config['prefix']}comprofiler cb ON u.id = cb.user_id
            WHERE u.block = 0
        ";
        
        $result = $joomla_db->query($query);
        $imported = 0;
        $errors = [];
        
        while ($row = $result->fetch_assoc()) {
            try {
                $patient_data = $this->map_joomla_data($row);
                $patient_id = $this->create_patient($patient_data);
                $imported++;
                
            } catch (Exception $e) {
                $errors[] = "Eroare la importul utilizatorului {$row['username']}: " . $e->getMessage();
            }
        }
        
        $joomla_db->close();
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => $result->num_rows
        ];
    }
    
    private function map_joomla_data($joomla_user) {
        $name_parts = explode(' ', $joomla_user['name']);
        
        return [
            'first_name' => $name_parts[0] ?? '',
            'last_name' => isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '',
            'email' => $joomla_user['email'],
            'phone' => $joomla_user['phone'] ?? '',
            'birth_date' => $joomla_user['birth_date'] ?? null,
            'gender' => $joomla_user['gender'] ?? '',
            'address' => $joomla_user['address'] ?? '',
            'joomla_id' => $joomla_user['id'],
            'register_date' => $joomla_user['registerDate']
        ];
    }
}
```

## 🗄️ Optimizări pentru 4000+ Pacienți

### Structura de Date Optimizată
```sql
-- Indexuri pentru performanță
CREATE INDEX idx_patients_user_id ON wp_clinica_patients(user_id);
CREATE INDEX idx_patients_phone ON wp_clinica_patients(phone);
CREATE INDEX idx_patients_email ON wp_clinica_patients(email);
CREATE INDEX idx_patients_name ON wp_clinica_patients(first_name, last_name);
CREATE INDEX idx_appointments_patient ON wp_clinica_appointments(patient_id);
CREATE INDEX idx_appointments_doctor ON wp_clinica_appointments(doctor_id);
CREATE INDEX idx_appointments_date ON wp_clinica_appointments(appointment_date);
```

### Cache Strategy pentru Volume Mari
```php
class Clinica_High_Volume_Cache {
    private $cache_group = 'clinica_high_volume';
    
    public function get_patient_appointments($patient_id, $limit = 50) {
        $cache_key = "patient_appointments_{$patient_id}_{$limit}";
        $appointments = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $appointments) {
            global $wpdb;
            
            $appointments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}clinica_appointments 
                 WHERE patient_id = %d 
                 ORDER BY appointment_date DESC 
                 LIMIT %d",
                $patient_id, $limit
            ));
            
            wp_cache_set($cache_key, $appointments, $this->cache_group, 1800); // 30 minute
        }
        
        return $appointments;
    }
    
    public function get_doctor_schedule($doctor_id, $date) {
        $cache_key = "doctor_schedule_{$doctor_id}_{$date}";
        $schedule = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $schedule) {
            global $wpdb;
            
            $schedule = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}clinica_appointments 
                 WHERE doctor_id = %d 
                 AND DATE(appointment_date) = %s 
                 ORDER BY appointment_date",
                $doctor_id, $date
            ));
            
            wp_cache_set($cache_key, $schedule, $this->cache_group, 900); // 15 minute
        }
        
        return $schedule;
    }
}
```

### Paginare și Lazy Loading
```php
class Clinica_Pagination {
    public function get_patients_paginated($page = 1, $per_page = 50, $filters = []) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $where_clause = $this->build_where_clause($filters);
        
        // Query pentru total
        $total_query = "
            SELECT COUNT(*) FROM {$wpdb->prefix}clinica_patients p
            JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE 1=1 {$where_clause}
        ";
        $total = $wpdb->get_var($total_query);
        
        // Query pentru date
        $data_query = "
            SELECT p.*, u.user_email, u.user_registered
            FROM {$wpdb->prefix}clinica_patients p
            JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE 1=1 {$where_clause}
            ORDER BY p.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $patients = $wpdb->get_results($wpdb->prepare($data_query, $per_page, $offset));
        
        return [
            'patients' => $patients,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }
}
```

## 🔄 Workflow de Import

### Procesul de Import
1. **Pregătire date**
   - Export din ICMED/Joomla
   - Validare format date
   - Curățare date (duplicate, formatare)

2. **Import în faze**
   - Import 1000 pacienți per lot
   - Validare după fiecare lot
   - Rollback în caz de erori

3. **Post-import**
   - Verificare integritate date
   - Generare rapoarte import
   - Notificare utilizatori

### Script de Import
```php
class Clinica_Import_Script {
    public function run_import($source, $options = []) {
        $start_time = microtime(true);
        
        // Configurare
        $batch_size = $options['batch_size'] ?? 1000;
        $dry_run = $options['dry_run'] ?? false;
        
        // Inițializare import
        $importer = new Clinica_Data_Import();
        $total_imported = 0;
        $total_errors = 0;
        
        // Procesare în loturi
        $batch = 1;
        do {
            $data = $this->get_batch_data($source, $batch, $batch_size);
            
            if (empty($data)) {
                break;
            }
            
            if (!$dry_run) {
                $result = $importer->import_patients($source, $data);
                $total_imported += $result['imported'];
                $total_errors += count($result['errors']);
            }
            
            $batch++;
            
            // Log progres
            $this->log_progress($batch, $total_imported, $total_errors);
            
        } while (!empty($data));
        
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        // Raport final
        $this->generate_import_report($total_imported, $total_errors, $duration);
        
        return [
            'imported' => $total_imported,
            'errors' => $total_errors,
            'duration' => $duration
        ];
    }
}
```

## 📊 Impact asupra Arhitecturii

### Modificări Necesare
1. **Sistem de roluri** - 5 roluri specifice cu permisiuni granulare
2. **Autentificare** - Suport pentru 3 metode de identificare
3. **Baza de date** - Optimizări pentru volume mari
4. **Cache** - Strategii avansate de cache
5. **Import** - Sistem robust de import din sisteme externe

### Considerații de Performanță
- **Indexuri** pentru toate câmpurile de căutare
- **Cache** pentru query-uri frecvente
- **Paginare** pentru toate listele
- **Lazy loading** pentru date grele
- **Queue system** pentru operațiuni asincrone

### Securitate
- **Validare strictă** pentru toate datele importate
- **Sanitizare** pentru toate câmpurile
- **Audit trail** pentru toate operațiunile
- **Backup** automat înainte de import

Aceste cerințe specifice vor necesita ajustări în roadmap-ul original, cu accent pe scalabilitate, performanță și robustețea sistemului de import. 