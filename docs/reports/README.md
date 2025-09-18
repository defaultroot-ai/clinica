# Plugin Clinica - Sistem de Gestionare Medicală

Un sistem complet de gestionare a programărilor medicale pentru WordPress, cu suport pentru autentificare cu username, email sau telefon, validare CNP și import de pacienți.

## Caracteristici

### 🔐 Autentificare Flexibilă
- **Username, Email sau Telefon**: Pacienții se pot autentifica folosind oricare din aceste identificatori
- **CNP ca Username**: Pentru pacienți, CNP-ul este folosit ca username WordPress
- **Validare CNP în timp real**: Verificare automată a validității CNP-ului
- **Log-uri de autentificare**: Monitorizare completă a încercărilor de login
- **Protecție împotriva atacurilor**: Limitare încercări eșuate (5 în 15 minute)

### 👥 Gestionare Pacienți
- **Creare pacienți**: Formular complet cu validare CNP și autocompletare
- **Generare parole automate**: Din primele 6 cifre CNP sau data nașterii (dd.mm.yyyy)
- **Suport CNP străini**: Pentru cetățeni străini cu reședință permanentă/temporară
- **Import în masă**: Suport pentru import din ICMED, Joomla Community Builder, CSV
- **Permisiuni granulare**: Sistem de roluri și capabilități avansat

### 🏥 Roluri și Permisiuni
- **Manager**: Acces complet la sistem
- **Doctor**: Gestionare pacienți și programări
- **Asistent**: Creare pacienți și programări
- **Recepționer**: Programări și vizualizare pacienți
- **Pacient**: Acces la propriul dashboard

### 📊 API REST Complet
- **Endpoint-uri pentru pacienți**: CRUD complet cu permisiuni
- **Endpoint-uri pentru programări**: Gestionare programări
- **Validare CNP**: API pentru verificare CNP
- **Statistici**: Rapoarte și analize

## Instalare

1. **Descarcă plugin-ul** în directorul `/wp-content/plugins/clinica/`
2. **Activează plugin-ul** din panoul de administrare WordPress
3. **Verifică rolurile** - se vor crea automat rolurile Clinica
4. **Configurează permisiunile** pentru utilizatorii existenți

## Structura Plugin-ului

```
clinica/
├── clinica.php                          # Fișier principal
├── includes/
│   ├── class-clinica-authentication.php # Sistem autentificare
│   ├── class-clinica-cnp-validator.php  # Validare CNP
│   ├── class-clinica-cnp-parser.php     # Parsare CNP
│   ├── class-clinica-password-generator.php # Generare parole
│   ├── class-clinica-roles.php          # Gestionare roluri
│   ├── class-clinica-database.php       # Baza de date
│   ├── class-clinica-api.php            # API REST
│   ├── class-clinica-importers.php      # Import pacienți
│   ├── class-clinica-patient-permissions.php # Permisiuni
│   └── class-clinica-patient-creation-form.php # Formular creare
├── admin/
│   └── views/                           # Pagini admin
├── assets/
│   ├── css/                             # Stiluri
│   └── js/                              # JavaScript
└── languages/                           # Traduceri
```

## Utilizare

### Autentificare Pacienți

Pacienții se pot autentifica folosind:
- **CNP-ul** (username)
- **Adresa de email**
- **Numărul de telefon**

```php
// Exemplu de autentificare
$auth = new Clinica_Authentication();
$user = $auth->find_user_by_identifier('1234567890123'); // CNP
$user = $auth->find_user_by_identifier('patient@email.com'); // Email
$user = $auth->find_user_by_identifier('0722123456'); // Telefon
```

### Creare Pacient

```php
// Folosind formularul
$form = new Clinica_Patient_Creation_Form();
echo $form->render_form();

// Folosind API
$data = array(
    'cnp' => '1234567890123',
    'first_name' => 'Ion',
    'last_name' => 'Popescu',
    'email' => 'ion.popescu@email.com',
    'phone_primary' => '0722123456',
    'password_method' => 'cnp' // sau 'birth_date'
);

$result = $form->create_patient($data);
```

### Validare CNP

```php
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp('1234567890123');

if ($result['valid']) {
    echo "CNP valid - " . $result['type'];
} else {
    echo "CNP invalid: " . $result['message'];
}
```

### Generare Parolă

```php
$generator = new Clinica_Password_Generator();
$password = $generator->generate_password_from_cnp('1234567890123', 'cnp');
// Rezultat: "123456" (primele 6 cifre)

$password = $generator->generate_password_from_cnp('1234567890123', 'birth_date');
// Rezultat: "15.03.1990" (data nașterii)
```

### API REST

#### Endpoint-uri disponibile:

```
GET /wp-json/clinica/v1/patients
POST /wp-json/clinica/v1/patients
GET /wp-json/clinica/v1/patients/{id}
PUT /wp-json/clinica/v1/patients/{id}
DELETE /wp-json/clinica/v1/patients/{id}

GET /wp-json/clinica/v1/appointments
POST /wp-json/clinica/v1/appointments
GET /wp-json/clinica/v1/appointments/{id}
PUT /wp-json/clinica/v1/appointments/{id}
DELETE /wp-json/clinica/v1/appointments/{id}

GET /wp-json/clinica/v1/stats
POST /wp-json/clinica/v1/validate-cnp
```

#### Exemplu utilizare API:

```javascript
// Obține lista de pacienți
fetch('/wp-json/clinica/v1/patients?per_page=20&page=1', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(response => response.json())
.then(data => console.log(data.patients));

// Validează CNP
fetch('/wp-json/clinica/v1/validate-cnp', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        cnp: '1234567890123'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Import Pacienți

```php
$importers = new Clinica_Importers();

// Import din CSV
$file_data = $_FILES['import_file'];
$import_id = $importers->start_import('csv', $file_data);

// Monitorizare progres
$progress = $importers->get_import_progress($import_id);
```

## Shortcode-uri

### Formular Creare Pacient
```php
[clinica_create_patient_form]
```

### Dashboard Pacient
```php
[clinica_patient_dashboard]
```

## Baza de Date

Plugin-ul creează următoarele tabele:

- `wp_clinica_patients` - Date pacienți
- `wp_clinica_appointments` - Programări
- `wp_clinica_medical_records` - Dosare medicale
- `wp_clinica_login_logs` - Log-uri autentificare
- `wp_clinica_imports` - Import-uri
- `wp_clinica_notifications` - Notificări

## Roluri și Capabilități

### Roluri create:
- `clinica_manager`
- `clinica_doctor`
- `clinica_assistant`
- `clinica_receptionist`
- `clinica_patient`

### Capabilități principale:
- `clinica_view_dashboard`
- `clinica_create_patients`
- `clinica_edit_patients`
- `clinica_delete_patients`
- `clinica_view_patients`
- `clinica_import_patients`
- `clinica_manage_appointments`
- `clinica_view_reports`
- `clinica_manage_settings`

## Securitate

- **Validare CNP**: Verificare algoritm de control
- **Sanitizare date**: Toate datele sunt sanitizate
- **Permisiuni**: Verificare granulară a permisiunilor
- **Log-uri**: Monitorizare completă a activității
- **Rate limiting**: Protecție împotriva atacurilor brute force
- **Nonce**: Protecție CSRF pentru toate formularele

## Compatibilitate

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.7+
- **Browser**: Toate browserele moderne

## Suport

Pentru suport tehnic sau întrebări, contactați echipa de dezvoltare.

## Licență

Acest plugin este licențiat sub GPL v2 sau ulterior.

## Changelog

### Versiunea 1.0.0
- Implementare sistem complet de autentificare
- Validare și parsare CNP
- Generare parole automate
- API REST complet
- Sistem de roluri și permisiuni
- Import pacienți din multiple surse
- Dashboard pacienți
- Log-uri de securitate 