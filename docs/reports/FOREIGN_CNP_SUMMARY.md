# CNP-uri Străine și Formular Actualizat - Sumar

## 🎯 Cerințe Specifice Actualizate

### CNP-uri pentru Cetățeni Străini
- **Cetățeni români**: CNP standard (13 cifre)
- **Cetățeni străini cu drept de sedere permanent**: CNP cu primul digit 0
- **Cetățeni străini cu drept de sedere temporar**: CNP cu primul digit 9
- **Validare extinsă** pentru toate tipurile de CNP

### Formular de Creare Pacient Actualizat
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

### Validare CNP Extinsă
```php
// Tipuri de CNP suportate
- 'romanian': CNP standard (1-9)
- 'foreign_permanent': Străin permanent (0)
- 'foreign_temporary': Străin temporar (9)

// Validare pentru fiecare tip
- Algoritm matematic specific
- Verificare digit de control
- Validare format și lungime
```

### Extragerea Informațiilor din CNP
```php
// Informații extrase automat
- Data nașterii (din pozițiile 2-7)
- Sex (din primul digit)
- Vârsta (calculată din data nașterii)
- Tipul de CNP (român/străin)
```

### Generarea Parolei
```php
// Opțiuni de generare
- 'cnp': Primele 6 cifre ale CNP-ului
- 'birth_date': Data nașterii (dd.mm.yyyy)

// Configurabil de către personal medical
```

## 📊 Impact asupra Sistemului

### Structura Bazei de Date
```sql
-- Tabela pacienți actualizată
CREATE TABLE wp_clinica_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    cnp VARCHAR(13) UNIQUE NOT NULL,
    cnp_type ENUM('romanian', 'foreign_permanent', 'foreign_temporary'),
    phone_primary VARCHAR(20),
    phone_secondary VARCHAR(20),
    birth_date DATE,
    gender ENUM('male', 'female'),
    age INT,
    address TEXT,
    emergency_contact VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_history TEXT,
    password_method ENUM('cnp', 'birth_date'),
    import_source VARCHAR(50),
    created_by BIGINT UNSIGNED, -- ID-ul utilizatorului care a creat pacientul
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES wp_users(ID),
    INDEX idx_cnp (cnp),
    INDEX idx_cnp_type (cnp_type),
    INDEX idx_user_id (user_id)
);
```

### API Endpoints Noi
```php
// Endpoint pentru validarea CNP
POST /wp-json/clinica/v1/validate-cnp
{
    "cnp": "1234567890123"
}

// Endpoint pentru parsarea CNP
POST /wp-json/clinica/v1/parse-cnp
{
    "cnp": "1234567890123"
}

// Endpoint pentru generarea parolei
POST /wp-json/clinica/v1/generate-password
{
    "cnp": "1234567890123",
    "birth_date": "1990-01-01",
    "method": "cnp"
}
```

## 🎨 Interfața Utilizator

### Formular de Creare Pacient
- **Secțiuni organizate** pentru o experiență intuitivă
- **Validare în timp real** pentru toate câmpurile
- **Completare automată** din CNP
- **Generare automată parole** cu opțiuni
- **Feedback vizual** pentru validări

### Permisiuni și Securitate
- **Acces restricționat** pentru crearea pacienților
- **Verificare permisiuni** în timp real
- **Audit trail** pentru toate operațiunile
- **Validare strictă** pentru toate datele

## 📅 Timeline Actualizat

### Faza 1: Fundația (Săptămâni 1-2)
**Săptămâna 1**: Setup și structura de bază
- Configurare mediu de dezvoltare
- Structura plugin-ului principal
- Tabele de bază de date cu suport CNP străini

**Săptămâna 2**: Roluri și formular pacienți
- 5 roluri personalizate cu permisiuni granulare
- Sistem de autentificare avansat
- Formular de creare pacienți cu completare automată
- Validare CNP extinsă pentru străini
- Generare automată parole

### Faza 2: Import și Optimizări (Săptămâni 3-4)
- Import din ICMED cu suport CNP străini
- Import din Joomla cu suport CNP străini
- Optimizări pentru volume mari
- Testare cu date reale

## 🔐 Securitate și Conformitate

### Validare Strictă
- **Validare CNP** pentru toate tipurile
- **Verificare unicitate** în sistem
- **Validare format** pentru toate câmpurile
- **Sanitizare** pentru toate input-urile

### Audit Trail
- **Log crearea** pacienților
- **Log modificările** de profil
- **Log importurile** din sisteme externe
- **Conformitate GDPR** pentru date medicale

### Permisiuni Granulare
- **Crearea pacienților** - doar personal medical
- **Editarea profilurilor** - doar personal autorizat
- **Vizualizarea datelor** - conform rolului
- **Importul datelor** - doar administratori

## 🎯 Beneficii ale Implementării

### Pentru Personal Medical
- **Formular intuitiv** cu completare automată
- **Validare în timp real** pentru toate tipurile de CNP
- **Generare automată parole** cu opțiuni configurabile
- **Acces restricționat** pentru securitate
- **Suport complet** pentru cetățeni străini

### Pentru Pacienți
- **Identificare unică** cu CNP (român sau străin)
- **Credențiale securizate** generate automat
- **Informații complete** extrase din CNP
- **Proces simplificat** de înregistrare
- **Acces personalizat** la datele medicale

### Pentru Sistem
- **Suport complet** pentru cetățeni străini
- **Validare robustă** pentru toate tipurile de CNP
- **Securitate avansată** cu permisiuni granulare
- **Scalabilitate** pentru volume mari
- **Conformitate** cu reglementările medicale

## 📋 Checklist de Implementare

### Faza 1: Fundația
- [ ] Implementare validare CNP extinsă
- [ ] Creare formular de creare pacienți
- [ ] Implementare completare automată din CNP
- [ ] Implementare generare automată parole
- [ ] Implementare permisiuni restricționate

### Faza 2: Import
- [ ] Actualizare import ICMED pentru CNP străini
- [ ] Actualizare import Joomla pentru CNP străini
- [ ] Testare import cu date reale
- [ ] Validare sincronizare pentru toate tipurile

### Faza 3: Interfață și Testare
- [ ] Formular de creare pacienți complet
- [ ] Validare în timp real pentru toate câmpurile
- [ ] Interfață de import actualizată
- [ ] Testare cu pacienți români și străini

### Faza 4: Lansare
- [ ] Testare completă cu date reale
- [ ] Validare performanță pentru volume mari
- [ ] Testare securitate și permisiuni
- [ ] Documentație și training

---

**Concluzie**: Implementarea suportului pentru CNP-uri străine și formularul actualizat oferă o soluție completă și securizată pentru gestionarea pacienților români și străini, cu accent pe ușurința de utilizare și conformitatea cu reglementările medicale. 