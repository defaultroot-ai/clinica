# CNP-uri pentru Cetățeni Străini și Formular Actualizat

## 🎯 Cerințe Specifice

### CNP-uri pentru Cetățeni Străini
- **Cetățeni români**: CNP standard (13 cifre)
- **Cetățeni străini cu drept de sedere**: CNP temporar/permanent
- **Validare extinsă** pentru toate tipurile de CNP
- **Suport pentru rezidenți temporari și permanenți**

### Formular de Creare Pacient
- **CNP (obligatoriu)** - cu validare pentru străini
- **Nume (obligatoriu)**
- **Prenume (obligatoriu)**
- **Adresa de email**
- **Telefon Principal**
- **Telefon Secundar**
- **Data nașterii** - autofill din CNP
- **Sex** - autofill din CNP
- **Vârsta** - autofill din data nașterii
- **Parolă** - generată automat (opțiuni configurabile)

### Acces Restricționat
- **Pacienții NU se pot înregistra singuri**
- **Doar personal medical** poate crea pacienți:
  - Administrator
  - Manager
  - Doctor
  - Asistent
  - Receptionist

## 🔧 Implementare Tehnică

### Validare CNP Extinsă pentru Străini
```php
class Clinica_CNP_Validator {
    
    /**
     * Validare CNP pentru români și străini
     */
    public function validate_cnp($cnp) {
        // Verifică lungimea
        if (strlen($cnp) !== 13) {
            return ['valid' => false, 'error' => 'CNP-ul trebuie să aibă exact 13 caractere'];
        }
        
        // Verifică dacă conține doar cifre
        if (!ctype_digit($cnp)) {
            return ['valid' => false, 'error' => 'CNP-ul trebuie să conțină doar cifre'];
        }
        
        // Determină tipul de CNP
        $cnp_type = $this->determine_cnp_type($cnp);
        
        switch ($cnp_type) {
            case 'romanian':
                return $this->validate_romanian_cnp($cnp);
            case 'foreign_permanent':
                return $this->validate_foreign_permanent_cnp($cnp);
            case 'foreign_temporary':
                return $this->validate_foreign_temporary_cnp($cnp);
            default:
                return ['valid' => false, 'error' => 'Tip de CNP necunoscut'];
        }
    }
    
    /**
     * Determină tipul de CNP
     */
    private function determine_cnp_type($cnp) {
        $first_digit = $cnp[0];
        
        // CNP românesc
        if (in_array($first_digit, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
            return 'romanian';
        }
        
        // CNP străin permanent
        if ($first_digit === '0') {
            return 'foreign_permanent';
        }
        
        // CNP străin temporar
        if ($first_digit === '9') {
            return 'foreign_temporary';
        }
        
        return 'unknown';
    }
    
    /**
     * Validare CNP românesc
     */
    private function validate_romanian_cnp($cnp) {
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        if ($control_digit != $cnp[12]) {
            return ['valid' => false, 'error' => 'CNP românesc invalid'];
        }
        
        return ['valid' => true, 'type' => 'romanian'];
    }
    
    /**
     * Validare CNP străin permanent
     */
    private function validate_foreign_permanent_cnp($cnp) {
        // CNP-urile pentru străini cu drept de sedere permanent
        // au primul digit 0 și urmează un algoritm similar
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        if ($control_digit != $cnp[12]) {
            return ['valid' => false, 'error' => 'CNP străin permanent invalid'];
        }
        
        return ['valid' => true, 'type' => 'foreign_permanent'];
    }
    
    /**
     * Validare CNP străin temporar
     */
    private function validate_foreign_temporary_cnp($cnp) {
        // CNP-urile pentru străini cu drept de sedere temporar
        // au primul digit 9 și urmează un algoritm similar
        $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnp[$i] * $control_digits[$i];
        }
        
        $control_digit = $sum % 11;
        if ($control_digit == 10) {
            $control_digit = 1;
        }
        
        if ($control_digit != $cnp[12]) {
            return ['valid' => false, 'error' => 'CNP străin temporar invalid'];
        }
        
        return ['valid' => true, 'type' => 'foreign_temporary'];
    }
}
```

### Extragerea Informațiilor din CNP
```php
class Clinica_CNP_Parser {
    
    /**
     * Extrage data nașterii din CNP
     */
    public function extract_birth_date($cnp) {
        $year = substr($cnp, 1, 2);
        $month = substr($cnp, 3, 2);
        $day = substr($cnp, 5, 2);
        
        // Determină secolul
        $first_digit = $cnp[0];
        $century = $this->determine_century($first_digit);
        
        $full_year = $century . $year;
        
        return sprintf('%04d-%02d-%02d', $full_year, $month, $day);
    }
    
    /**
     * Extrage sexul din CNP
     */
    public function extract_gender($cnp) {
        $first_digit = $cnp[0];
        
        // Pentru români
        if (in_array($first_digit, ['1', '3', '5', '7', '9'])) {
            return 'male';
        } elseif (in_array($first_digit, ['2', '4', '6', '8'])) {
            return 'female';
        }
        
        // Pentru străini
        if ($first_digit === '0') {
            // Străin permanent - verifică al doilea digit
            $second_digit = $cnp[1];
            return in_array($second_digit, ['1', '3', '5', '7', '9']) ? 'male' : 'female';
        }
        
        if ($first_digit === '9') {
            // Străin temporar - verifică al doilea digit
            $second_digit = $cnp[1];
            return in_array($second_digit, ['1', '3', '5', '7', '9']) ? 'male' : 'female';
        }
        
        return 'unknown';
    }
    
    /**
     * Calculează vârsta din data nașterii
     */
    public function calculate_age($birth_date) {
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);
        return $age->y;
    }
    
    /**
     * Determină secolul
     */
    private function determine_century($first_digit) {
        switch ($first_digit) {
            case '1':
            case '2':
                return '19';
            case '3':
            case '4':
                return '18';
            case '5':
            case '6':
                return '20';
            case '7':
            case '8':
                return '19';
            case '9':
                return '19';
            case '0':
                return '20'; // Pentru străini permanenți
            default:
                return '20';
        }
    }
}
```

### Generarea Parolei
```php
class Clinica_Password_Generator {
    
    /**
     * Generează parolă din CNP sau data nașterii
     */
    public function generate_password($cnp, $birth_date, $method = 'cnp') {
        switch ($method) {
            case 'cnp':
                return $this->generate_from_cnp($cnp);
            case 'birth_date':
                return $this->generate_from_birth_date($birth_date);
            default:
                return $this->generate_from_cnp($cnp);
        }
    }
    
    /**
     * Generează parolă din primele 6 cifre ale CNP-ului
     */
    private function generate_from_cnp($cnp) {
        $first_six = substr($cnp, 0, 6);
        
        return $first_six;
    }
    
    /**
     * Generează parolă din data nașterii (dd.mm.yyyy)
     */
    private function generate_from_birth_date($birth_date) {
        $date = new DateTime($birth_date);
        $formatted = $date->format('d.m.Y');
        
        return $formatted; // Returnează formatul cu puncte: dd.mm.yyyy
    }
}
```

### Formular de Creare Pacient Actualizat
```php
class Clinica_Patient_Creation_Form {
    
    public function render_patient_form() {
        // Verifică permisiunile
        if (!$this->can_create_patient()) {
            wp_die('Nu aveți permisiunea de a crea pacienți');
        }
        
        ?>
        <form class="clinica-patient-creation-form" method="post" id="patientForm">
            <?php wp_nonce_field('clinica_create_patient', 'clinica_patient_nonce'); ?>
            
            <div class="form-section">
                <h3>Informații de Identificare</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cnp">CNP *</label>
                        <input type="text" id="cnp" name="cnp" maxlength="13" required 
                               pattern="[0-9]{13}" title="CNP-ul trebuie să conțină exact 13 cifre">
                        <small>CNP-ul va fi folosit ca username pentru autentificare</small>
                        <div id="cnp-validation" class="validation-message"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Nume *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">Prenume *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Informații de Contact</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Adresa de Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_primary">Telefon Principal</label>
                        <input type="tel" id="phone_primary" name="phone_primary">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_secondary">Telefon Secundar</label>
                        <input type="tel" id="phone_secondary" name="phone_secondary">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Informații Personale</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birth_date">Data Nașterii</label>
                        <input type="date" id="birth_date" name="birth_date" readonly>
                        <small>Completat automat din CNP</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Sex</label>
                        <select id="gender" name="gender" disabled>
                            <option value="">Selectează</option>
                            <option value="male">Masculin</option>
                            <option value="female">Feminin</option>
                        </select>
                        <small>Completat automat din CNP</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Vârsta</label>
                        <input type="number" id="age" name="age" readonly>
                        <small>Calculată automat</small>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Generare Parolă</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password_method">Metoda de generare parolă</label>
                        <select id="password_method" name="password_method">
                            <option value="cnp">Primele 6 cifre ale CNP-ului</option>
                            <option value="birth_date">Data nașterii (zz.ll.aaaa)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="generated_password">Parola Generată</label>
                        <input type="text" id="generated_password" name="generated_password" readonly>
                        <button type="button" id="regenerate_password" class="btn btn-secondary">Regenerează</button>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Informații Medicale</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">Grupa Sanguină</label>
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
                    <label for="medical_history">Istoric Medical</label>
                    <textarea id="medical_history" name="medical_history" rows="4" 
                              placeholder="Boli cronice, intervenții chirurgicale, etc."></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Creează Pacient</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Resetează</button>
            </div>
        </form>
        
        <script>
        // Validare CNP în timp real
        document.getElementById('cnp').addEventListener('input', function(e) {
            const cnp = e.target.value;
            const validationDiv = document.getElementById('cnp-validation');
            
            if (cnp.length === 13) {
                // Validare CNP
                fetch('/wp-json/clinica/v1/validate-cnp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    body: JSON.stringify({ cnp: cnp })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        validationDiv.innerHTML = '<span class="success">✓ CNP valid</span>';
                        fillAutoFields(cnp);
                    } else {
                        validationDiv.innerHTML = '<span class="error">✗ ' + data.error + '</span>';
                    }
                });
            } else {
                validationDiv.innerHTML = '';
            }
        });
        
        // Completează câmpurile automat
        function fillAutoFields(cnp) {
            fetch('/wp-json/clinica/v1/parse-cnp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({ cnp: cnp })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('birth_date').value = data.birth_date;
                document.getElementById('gender').value = data.gender;
                document.getElementById('age').value = data.age;
                
                // Generează parola
                generatePassword();
            });
        }
        
        // Generează parola
        function generatePassword() {
            const cnp = document.getElementById('cnp').value;
            const birthDate = document.getElementById('birth_date').value;
            const method = document.getElementById('password_method').value;
            
            fetch('/wp-json/clinica/v1/generate-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({ 
                    cnp: cnp, 
                    birth_date: birthDate, 
                    method: method 
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('generated_password').value = data.password;
            });
        }
        
        // Regenerează parola
        document.getElementById('regenerate_password').addEventListener('click', generatePassword);
        document.getElementById('password_method').addEventListener('change', generatePassword);
        
        // Resetează formularul
        function resetForm() {
            document.getElementById('patientForm').reset();
            document.getElementById('cnp-validation').innerHTML = '';
            document.getElementById('birth_date').readOnly = true;
            document.getElementById('gender').disabled = true;
            document.getElementById('age').readOnly = true;
        }
        </script>
        <?php
    }
    
    /**
     * Verifică dacă utilizatorul poate crea pacienți
     */
    private function can_create_patient() {
        $allowed_roles = ['clinica_administrator', 'clinica_manager', 'clinica_doctor', 'clinica_assistant', 'clinica_receptionist'];
        
        foreach ($allowed_roles as $role) {
            if (current_user_can($role)) {
                return true;
            }
        }
        
        return false;
    }
}
```

## 🔐 Securitate și Permisiuni

### Verificarea Permisiunilor
```php
class Clinica_Patient_Permissions {
    
    /**
     * Verifică dacă utilizatorul poate crea pacienți
     */
    public static function can_create_patient($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $allowed_capabilities = [
            'clinica_create_patients',
            'clinica_manage_patients'
        ];
        
        foreach ($allowed_capabilities as $capability) {
            if (user_can($user_id, $capability)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifică dacă utilizatorul poate edita pacienți
     */
    public static function can_edit_patient($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_edit_patients');
    }
    
    /**
     * Verifică dacă utilizatorul poate vedea pacienți
     */
    public static function can_view_patients($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'clinica_view_patients');
    }
}
```

## 📊 API Endpoints pentru Formular

### Validare CNP
```php
// Endpoint pentru validarea CNP
add_action('rest_api_init', function () {
    register_rest_route('clinica/v1', '/validate-cnp', [
        'methods' => 'POST',
        'callback' => 'clinica_validate_cnp_endpoint',
        'permission_callback' => function() {
            return Clinica_Patient_Permissions::can_create_patient();
        }
    ]);
});

function clinica_validate_cnp_endpoint($request) {
    $cnp = $request->get_param('cnp');
    
    $validator = new Clinica_CNP_Validator();
    $result = $validator->validate_cnp($cnp);
    
    return new WP_REST_Response($result, 200);
}
```

### Parsare CNP
```php
// Endpoint pentru parsarea CNP
add_action('rest_api_init', function () {
    register_rest_route('clinica/v1', '/parse-cnp', [
        'methods' => 'POST',
        'callback' => 'clinica_parse_cnp_endpoint',
        'permission_callback' => function() {
            return Clinica_Patient_Permissions::can_create_patient();
        }
    ]);
});

function clinica_parse_cnp_endpoint($request) {
    $cnp = $request->get_param('cnp');
    
    $parser = new Clinica_CNP_Parser();
    $birth_date = $parser->extract_birth_date($cnp);
    $gender = $parser->extract_gender($cnp);
    $age = $parser->calculate_age($birth_date);
    
    return new WP_REST_Response([
        'birth_date' => $birth_date,
        'gender' => $gender,
        'age' => $age
    ], 200);
}
```

### Generare Parolă
```php
// Endpoint pentru generarea parolei
add_action('rest_api_init', function () {
    register_rest_route('clinica/v1', '/generate-password', [
        'methods' => 'POST',
        'callback' => 'clinica_generate_password_endpoint',
        'permission_callback' => function() {
            return Clinica_Patient_Permissions::can_create_patient();
        }
    ]);
});

function clinica_generate_password_endpoint($request) {
    $cnp = $request->get_param('cnp');
    $birth_date = $request->get_param('birth_date');
    $method = $request->get_param('method');
    
    $generator = new Clinica_Password_Generator();
    $password = $generator->generate_password($cnp, $birth_date, $method);
    
    return new WP_REST_Response([
        'password' => $password
    ], 200);
}
```

## 🎯 Beneficii ale Implementării

### Pentru Personal Medical
- **Formular intuitiv** cu completare automată
- **Validare în timp real** pentru CNP
- **Generare automată parole** cu opțiuni configurabile
- **Acces restricționat** pentru securitate

### Pentru Pacienți
- **Identificare unică** cu CNP
- **Credențiale securizate** generate automat
- **Informații complete** extrase din CNP
- **Proces simplificat** de înregistrare

### Pentru Sistem
- **Suport complet** pentru cetățeni străini
- **Validare robustă** pentru toate tipurile de CNP
- **Securitate avansată** cu permisiuni granulare
- **Scalabilitate** pentru volume mari

Această implementare oferă o soluție completă pentru gestionarea pacienților români și străini, cu formular intuitiv și securitate avansată. 