# Integrarea Pacienților cu Utilizatorii WordPress

## 🎯 Cerințe Specifice

### Identificare Pacienți
- **CNP (Cod Numeric Personal)** ca username WordPress
- **Pacienții sunt utilizatori WordPress** cu rol `clinica_patient`
- **Sincronizare automată** între profilul pacient și utilizatorul WordPress
- **Validare CNP** la crearea utilizatorului

## 🔧 Implementare Tehnică

### Crearea Utilizatorului WordPress cu CNP
```php
class Clinica_Patient_User_Manager {
    
    /**
     * Creează un pacient nou cu utilizator WordPress
     */
    public function create_patient_with_user($patient_data) {
        // Validare CNP
        if (!$this->validate_cnp($patient_data['cnp'])) {
            throw new Exception('CNP invalid');
        }
        
        // Verifică dacă CNP-ul există deja
        if ($this->cnp_exists($patient_data['cnp'])) {
            throw new Exception('CNP-ul există deja în sistem');
        }
        
        // Creează utilizatorul WordPress
        $user_data = [
            'user_login' => $patient_data['cnp'], // CNP ca username
            'user_email' => $patient_data['email'],
            'user_pass' => $this->generate_secure_password(),
            'first_name' => $patient_data['first_name'],
            'last_name' => $patient_data['last_name'],
            'role' => 'clinica_patient',
            'user_registered' => current_time('mysql')
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            throw new Exception('Eroare la crearea utilizatorului: ' . $user_id->get_error_message());
        }
        
        // Creează profilul pacientului
        $patient_profile = [
            'user_id' => $user_id,
            'cnp' => $patient_data['cnp'],
            'phone' => $patient_data['phone'],
            'birth_date' => $patient_data['birth_date'],
            'gender' => $patient_data['gender'],
            'address' => $patient_data['address'],
            'emergency_contact' => $patient_data['emergency_contact'] ?? '',
            'blood_type' => $patient_data['blood_type'] ?? '',
            'allergies' => $patient_data['allergies'] ?? '',
            'medical_history' => $patient_data['medical_history'] ?? '',
            'created_at' => current_time('mysql')
        ];
        
        $this->create_patient_profile($patient_profile);
        
        // Trimite email cu credențiale
        $this->send_welcome_email($user_id, $user_data['user_login'], $user_data['user_pass']);
        
        // Log crearea pacientului
        $this->log_patient_creation($user_id, $patient_data);
        
        return $user_id;
    }
    
    /**
     * Validare CNP românesc
     */
    private function validate_cnp($cnp) {
        // Verifică lungimea
        if (strlen($cnp) !== 13) {
            return false;
        }
        
        // Verifică dacă conține doar cifre
        if (!ctype_digit($cnp)) {
            return false;
        }
        
        // Algoritm de validare CNP
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        return $control_digit == $cnp[12];
    }
    
    /**
     * Verifică dacă CNP-ul există deja
     */
    private function cnp_exists($cnp) {
        global $wpdb;
        
        $existing_user = get_user_by('login', $cnp);
        if ($existing_user) {
            return true;
        }
        
        // Verifică și în tabela pacienților
        $existing_patient = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $cnp
        ));
        
        return $existing_patient !== null;
    }
    
    /**
     * Generează parolă securizată
     */
    private function generate_secure_password() {
        $password = wp_generate_password(12, true, true);
        
        // Adaugă caractere speciale pentru securitate medicală
        $special_chars = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $password .= $special_chars[array_rand($special_chars)];
        
        return $password;
    }
}
```

### Actualizarea Importului din Sisteme Externe
```php
class Clinica_ICMED_Importer_Updated {
    
    public function import($data_file) {
        $patients = $this->parse_icmed_data($data_file);
        $imported = 0;
        $errors = [];
        
        foreach ($patients as $patient_data) {
            try {
                // Mapează datele ICMED la formatul nostru
                $mapped_data = $this->map_icmed_to_patient($patient_data);
                
                // Creează pacientul cu utilizator WordPress
                $patient_manager = new Clinica_Patient_User_Manager();
                $user_id = $patient_manager->create_patient_with_user($mapped_data);
                
                $imported++;
                
                // Log import
                $this->log_import($user_id, $patient_data, 'icmed');
                
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
    
    private function map_icmed_to_patient($icmed_data) {
        return [
            'cnp' => $icmed_data['cnp'],
            'first_name' => $icmed_data['first_name'],
            'last_name' => $icmed_data['last_name'],
            'email' => $icmed_data['email'] ?? $this->generate_email_from_cnp($icmed_data['cnp']),
            'phone' => $icmed_data['phone'],
            'birth_date' => $icmed_data['birth_date'],
            'gender' => $icmed_data['gender'],
            'address' => $icmed_data['address'],
            'emergency_contact' => $icmed_data['emergency_contact'],
            'blood_type' => $icmed_data['blood_type'],
            'allergies' => $icmed_data['allergies'],
            'medical_history' => $icmed_data['medical_history']
        ];
    }
    
    private function generate_email_from_cnp($cnp) {
        // Generează email temporar pentru pacienții fără email
        return "patient.{$cnp}@" . parse_url(get_site_url(), PHP_URL_HOST);
    }
}

class Clinica_Joomla_Importer_Updated {
    
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
        
        // Query pentru extragerea datelor cu CNP
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
                cb.address,
                cb.cnp,
                cb.emergency_contact,
                cb.blood_type,
                cb.allergies
            FROM {$joomla_db_config['prefix']}users u
            LEFT JOIN {$joomla_db_config['prefix']}comprofiler cb ON u.id = cb.user_id
            WHERE u.block = 0
        ";
        
        $result = $joomla_db->query($query);
        $imported = 0;
        $errors = [];
        
        while ($row = $result->fetch_assoc()) {
            try {
                $patient_data = $this->map_joomla_to_patient($row);
                
                // Creează pacientul cu utilizator WordPress
                $patient_manager = new Clinica_Patient_User_Manager();
                $user_id = $patient_manager->create_patient_with_user($patient_data);
                
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
    
    private function map_joomla_to_patient($joomla_user) {
        $name_parts = explode(' ', $joomla_user['name']);
        
        return [
            'cnp' => $joomla_user['cnp'] ?? $this->generate_cnp_from_username($joomla_user['username']),
            'first_name' => $name_parts[0] ?? '',
            'last_name' => isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '',
            'email' => $joomla_user['email'],
            'phone' => $joomla_user['phone'] ?? '',
            'birth_date' => $joomla_user['birth_date'] ?? null,
            'gender' => $joomla_user['gender'] ?? '',
            'address' => $joomla_user['address'] ?? '',
            'emergency_contact' => $joomla_user['emergency_contact'] ?? '',
            'blood_type' => $joomla_user['blood_type'] ?? '',
            'allergies' => $joomla_user['allergies'] ?? '',
            'medical_history' => ''
        ];
    }
}
```

### Formular de Creare Pacient
```php
class Clinica_Patient_Form {
    
    public function render_patient_form() {
        ?>
        <form class="clinica-patient-form" method="post">
            <?php wp_nonce_field('clinica_create_patient', 'clinica_patient_nonce'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cnp">CNP *</label>
                    <input type="text" id="cnp" name="cnp" maxlength="13" required 
                           pattern="[0-9]{13}" title="CNP-ul trebuie să conțină exact 13 cifre">
                    <small>CNP-ul va fi folosit ca username pentru autentificare</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prenume *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Nume *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="birth_date">Data nașterii *</label>
                    <input type="date" id="birth_date" name="birth_date" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gen</label>
                    <select id="gender" name="gender">
                        <option value="">Selectează</option>
                        <option value="male">Masculin</option>
                        <option value="female">Feminin</option>
                        <option value="other">Altul</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Adresă</label>
                <textarea id="address" name="address" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="emergency_contact">Contact de urgență</label>
                <input type="tel" id="emergency_contact" name="emergency_contact">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="blood_type">Grupa sanguină</label>
                    <select id="blood_type" name="blood_type">
                        <option value="">Selectează</option>
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
                    <label for="allergies">Alergii</label>
                    <input type="text" id="allergies" name="allergies" 
                           placeholder="Ex: penicilină, praf, etc.">
                </div>
            </div>
            
            <div class="form-group">
                <label for="medical_history">Istoric medical</label>
                <textarea id="medical_history" name="medical_history" rows="4" 
                          placeholder="Boli cronice, intervenții chirurgicale, etc."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Creează Pacient</button>
        </form>
        
        <script>
        // Validare CNP în timp real
        document.getElementById('cnp').addEventListener('input', function(e) {
            const cnp = e.target.value;
            if (cnp.length === 13) {
                // Validare CNP
                if (!validateCNP(cnp)) {
                    e.target.setCustomValidity('CNP invalid');
                } else {
                    e.target.setCustomValidity('');
                }
            }
        });
        
        function validateCNP(cnp) {
            if (!/^\d{13}$/.test(cnp)) return false;
            
            const controlDigits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
            let sum = 0;
            
            for (let i = 0; i < 12; i++) {
                sum += parseInt(cnp[i]) * controlDigits[i];
            }
            
            let controlDigit = sum % 11;
            if (controlDigit === 10) controlDigit = 1;
            
            return controlDigit === parseInt(cnp[12]);
        }
        </script>
        <?php
    }
}
```

### Procesarea Formularului
```php
class Clinica_Patient_Controller {
    
    public function handle_patient_creation() {
        if (!isset($_POST['clinica_patient_nonce']) || 
            !wp_verify_nonce($_POST['clinica_patient_nonce'], 'clinica_create_patient')) {
            wp_die('Eroare de securitate');
        }
        
        // Validare date
        $errors = $this->validate_patient_data($_POST);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        try {
            $patient_manager = new Clinica_Patient_User_Manager();
            $user_id = $patient_manager->create_patient_with_user($_POST);
            
            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'Pacientul a fost creat cu succes!'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    private function validate_patient_data($data) {
        $errors = [];
        
        // Validare CNP
        if (empty($data['cnp'])) {
            $errors[] = 'CNP-ul este obligatoriu';
        } elseif (!$this->validate_cnp($data['cnp'])) {
            $errors[] = 'CNP-ul este invalid';
        }
        
        // Validare câmpuri obligatorii
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'birth_date'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' este obligatoriu';
            }
        }
        
        // Validare email
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = 'Email invalid';
        }
        
        // Validare telefon
        if (!empty($data['phone']) && !$this->is_valid_phone($data['phone'])) {
            $errors[] = 'Numărul de telefon este invalid';
        }
        
        return $errors;
    }
}
```

## 🗄️ Structura Bazei de Date Actualizată

### Tabela Pacienți
```sql
-- Tabela pacienți actualizată
CREATE TABLE wp_clinica_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    cnp VARCHAR(13) UNIQUE NOT NULL,
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    emergency_contact VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_history TEXT,
    import_source VARCHAR(50),
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    INDEX idx_cnp (cnp),
    INDEX idx_user_id (user_id),
    INDEX idx_phone (phone),
    INDEX idx_birth_date (birth_date)
);
```

## 🔐 Securitate și Validare

### Validare CNP
- **Lungime exactă**: 13 caractere
- **Doar cifre**: Validare format
- **Algoritm de control**: Validare matematică
- **Unicitate**: CNP-ul trebuie să fie unic în sistem

### Securitate Utilizatori
- **Parole securizate**: Generare automată cu caractere speciale
- **Validare strictă**: Toate câmpurile validate
- **Audit trail**: Log pentru toate operațiunile
- **Backup**: Backup automat înainte de creare

## 📧 Notificări și Comunicare

### Email de Bun Venit
```php
class Clinica_Patient_Notifications {
    
    public function send_welcome_email($user_id, $username, $password) {
        $user = get_user_by('id', $user_id);
        $patient = $this->get_patient_data($user_id);
        
        $subject = 'Bun venit la Clinica - Credențiale de acces';
        
        $message = "
        Stimate {$user->first_name} {$user->last_name},
        
        Contul dumneavoastră a fost creat cu succes în sistemul nostru medical.
        
        Credențiale de acces:
        - Username: {$username} (CNP-ul dumneavoastră)
        - Parolă: {$password}
        
        Vă recomandăm să schimbați parola la prima autentificare.
        
        Pentru a vă autentifica, accesați: " . home_url('/login') . "
        
        Cu stimă,
        Echipa Clinică
        ";
        
        wp_mail($user->user_email, $subject, $message);
    }
}
```

## 🔄 Sincronizare și Actualizări

### Actualizare Profil Pacient
```php
class Clinica_Patient_Sync {
    
    public function update_patient_profile($user_id, $patient_data) {
        // Actualizează utilizatorul WordPress
        $user_update = [
            'ID' => $user_id,
            'first_name' => $patient_data['first_name'],
            'last_name' => $patient_data['last_name'],
            'user_email' => $patient_data['email']
        ];
        
        wp_update_user($user_update);
        
        // Actualizează profilul pacientului
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'clinica_patients',
            [
                'phone' => $patient_data['phone'],
                'birth_date' => $patient_data['birth_date'],
                'gender' => $patient_data['gender'],
                'address' => $patient_data['address'],
                'emergency_contact' => $patient_data['emergency_contact'],
                'blood_type' => $patient_data['blood_type'],
                'allergies' => $patient_data['allergies'],
                'medical_history' => $patient_data['medical_history'],
                'updated_at' => current_time('mysql')
            ],
            ['user_id' => $user_id]
        );
        
        // Log actualizarea
        $this->log_profile_update($user_id, $patient_data);
    }
}
```

Această implementare asigură că fiecare pacient devine automat un utilizator WordPress cu CNP-ul ca username, menținând sincronizarea perfectă între profilul medical și contul de utilizator. 