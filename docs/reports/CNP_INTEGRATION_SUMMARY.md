# Integrarea CNP ca Username WordPress - Sumar

## 🎯 Cerința Specifică

**Pacienții vor fi utilizatori WordPress cu CNP-ul ca username pentru autentificare.**

## 🔧 Implementare Tehnică

### 1. Crearea Utilizatorului WordPress
```php
// Exemplu de creare pacient cu CNP
$user_data = [
    'user_login' => $patient_data['cnp'], // CNP ca username
    'user_email' => $patient_data['email'],
    'user_pass' => $generated_password,
    'first_name' => $patient_data['first_name'],
    'last_name' => $patient_data['last_name'],
    'role' => 'clinica_patient'
];

$user_id = wp_insert_user($user_data);
```

### 2. Validare CNP
- **Lungime exactă**: 13 caractere
- **Doar cifre**: Validare format
- **Algoritm de control**: Validare matematică CNP românesc
- **Unicitate**: CNP-ul trebuie să fie unic în sistem

### 3. Sincronizare Automată
- **Profilul pacient** sincronizat cu **utilizatorul WordPress**
- **Actualizări automate** între cele două entități
- **Audit trail** pentru toate modificările

## 📊 Impact asupra Importului

### Import din ICMED
```php
// Mapează datele ICMED
$mapped_data = [
    'cnp' => $icmed_data['cnp'],
    'first_name' => $icmed_data['first_name'],
    'last_name' => $icmed_data['last_name'],
    'email' => $icmed_data['email'],
    // ... alte câmpuri
];

// Creează pacientul cu utilizator WordPress
$patient_manager = new Clinica_Patient_User_Manager();
$user_id = $patient_manager->create_patient_with_user($mapped_data);
```

### Import din Joomla
```php
// Mapează datele Joomla
$mapped_data = [
    'cnp' => $joomla_data['cnp'] ?? $this->generate_cnp_from_username($joomla_data['username']),
    'first_name' => $name_parts[0],
    'last_name' => $name_parts[1],
    'email' => $joomla_data['email'],
    // ... alte câmpuri
];

// Creează pacientul cu utilizator WordPress
$user_id = $patient_manager->create_patient_with_user($mapped_data);
```

## 🗄️ Structura Bazei de Date Actualizată

### Tabela Pacienți
```sql
CREATE TABLE wp_clinica_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    cnp VARCHAR(13) UNIQUE NOT NULL, -- CNP ca username
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    emergency_contact VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_history TEXT,
    import_source VARCHAR(50), -- 'icmed', 'joomla', 'manual'
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    INDEX idx_cnp (cnp),
    INDEX idx_user_id (user_id)
);
```

## 🔐 Securitate și Validare

### Validare CNP
```php
private function validate_cnp($cnp) {
    // Verifică lungimea
    if (strlen($cnp) !== 13) return false;
    
    // Verifică dacă conține doar cifre
    if (!ctype_digit($cnp)) return false;
    
    // Algoritm de validare CNP
    $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
    $sum = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $sum += $cnp[$i] * $control_digits[$i];
    }
    
    $control_digit = $sum % 11;
    if ($control_digit == 10) $control_digit = 1;
    
    return $control_digit == $cnp[12];
}
```

### Verificare Unicitate
```php
private function cnp_exists($cnp) {
    // Verifică în utilizatorii WordPress
    $existing_user = get_user_by('login', $cnp);
    if ($existing_user) return true;
    
    // Verifică în tabela pacienților
    global $wpdb;
    $existing_patient = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
        $cnp
    ));
    
    return $existing_patient !== null;
}
```

## 📧 Notificări și Comunicare

### Email de Bun Venit
```php
public function send_welcome_email($user_id, $username, $password) {
    $user = get_user_by('id', $user_id);
    
    $subject = 'Bun venit la Clinica - Credențiale de acces';
    $message = "
    Stimate {$user->first_name} {$user->last_name},
    
    Contul dumneavoastră a fost creat cu succes.
    
    Credențiale de acces:
    - Username: {$username} (CNP-ul dumneavoastră)
    - Parolă: {$password}
    
    Pentru a vă autentifica: " . home_url('/login') . "
    
    Cu stimă,
    Echipa Clinică
    ";
    
    wp_mail($user->user_email, $subject, $message);
}
```

## 🔄 Workflow Complet

### 1. Crearea Pacientului
1. **Validare CNP** - Verifică formatul și unicitatea
2. **Creare utilizator WordPress** - Cu CNP ca username
3. **Creare profil pacient** - Date medicale în tabela separată
4. **Trimite email** - Cu credențiale de acces
5. **Log operațiunea** - Pentru audit trail

### 2. Import din Sisteme Externe
1. **Parsare date** - Din ICMED sau Joomla
2. **Validare CNP** - Pentru fiecare pacient
3. **Creare utilizator** - WordPress cu CNP
4. **Creare profil** - Date medicale
5. **Notificare** - Email cu credențiale

### 3. Actualizarea Profilului
1. **Sincronizare** - Între utilizator și profil
2. **Validare** - Toate modificările
3. **Log** - Pentru audit trail
4. **Notificare** - Dacă este necesar

## 📋 Checklist de Implementare

### Faza 1: Fundația
- [ ] Implementare validare CNP
- [ ] Creare clase pentru gestionarea pacienților
- [ ] Actualizare structura bazei de date
- [ ] Testare creare pacient cu CNP

### Faza 2: Import
- [ ] Actualizare import ICMED
- [ ] Actualizare import Joomla
- [ ] Testare import cu date reale
- [ ] Validare sincronizare

### Faza 3: Interfață
- [ ] Formular creare pacient
- [ ] Validare în timp real CNP
- [ ] Interfață de import
- [ ] Rapoarte import

### Faza 4: Testare
- [ ] Testare cu 4000+ pacienți
- [ ] Validare performanță
- [ ] Testare securitate
- [ ] Testare sincronizare

## 🎯 Beneficii

### Pentru Pacienți
- **Autentificare simplă** - Cu CNP-ul lor
- **Acces personalizat** - La propriile date medicale
- **Securitate** - Credențiale unice și securizate

### Pentru Clinică
- **Identificare unică** - CNP-ul nu se poate duplica
- **Sincronizare perfectă** - Între sisteme
- **Audit trail** - Pentru conformitate medicală
- **Scalabilitate** - Pentru volume mari

### Pentru Sistem
- **Integrare nativă** - Cu WordPress
- **Performanță** - Indexuri optimizate
- **Securitate** - Validare strictă
- **Flexibilitate** - Ușor de extins

---

**Concluzie**: Integrarea CNP ca username WordPress oferă o soluție robustă și securizată pentru identificarea pacienților, cu sincronizare perfectă între profilul medical și contul de utilizator. 