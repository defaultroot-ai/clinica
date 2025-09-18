# Reparare Baza de Date Clinica

## Problema
Erorile de tip "Multiple primary key defined" apar când plugin-ul încearcă să recreeze tabelele care au deja chei primare definite.

## Soluția

### Opțiunea 1: Script de Reparare (Recomandat)

1. **Dezactivați plugin-ul** din WordPress Admin → Plugins
2. **Rulați scriptul de reparare**:
   - Accesați în browser: `http://your-site.com/wp-content/plugins/clinica/repair-database.php`
   - Scriptul va șterge și recreea toate tabelele corect
3. **Reactivați plugin-ul** din WordPress Admin → Plugins

### Opțiunea 2: Manual (Pentru utilizatori avansați)

1. **Dezactivați plugin-ul**
2. **Conectați-vă la baza de date** (phpMyAdmin sau linia de comandă)
3. **Ștergeți tabelele Clinica**:
   ```sql
   DROP TABLE IF EXISTS wp_clinica_notifications;
   DROP TABLE IF EXISTS wp_clinica_imports;
   DROP TABLE IF EXISTS wp_clinica_login_logs;
   DROP TABLE IF EXISTS wp_clinica_medical_records;
   DROP TABLE IF EXISTS wp_clinica_appointments;
   DROP TABLE IF EXISTS wp_clinica_patients;
   ```
4. **Ștergeți opțiunile**:
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'clinica_%';
   ```
5. **Reactivați plugin-ul**

### Opțiunea 3: Dezactivare/Activare

1. **Dezactivați plugin-ul** complet
2. **Ștergeți plugin-ul** din sistem
3. **Reinstalați plugin-ul** din nou
4. **Activați plugin-ul**

## Verificare

După reparare, verificați că:

1. **Nu mai apar erori** în log-urile WordPress
2. **Toate tabelele există** în baza de date
3. **Plugin-ul funcționează** normal în admin

## Structura Tabelelor Corecte

După reparare, tabelele ar trebui să aibă următoarea structură:

### wp_clinica_patients
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` BIGINT UNSIGNED NOT NULL
- `cnp` VARCHAR(13) UNIQUE NOT NULL
- ... (alte câmpuri)

### wp_clinica_appointments
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `patient_id` BIGINT UNSIGNED NOT NULL
- `doctor_id` BIGINT UNSIGNED NOT NULL
- ... (alte câmpuri)

### wp_clinica_medical_records
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `patient_id` BIGINT UNSIGNED NOT NULL
- `doctor_id` BIGINT UNSIGNED NOT NULL
- ... (alte câmpuri)

### wp_clinica_login_logs
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` BIGINT UNSIGNED DEFAULT 0
- ... (alte câmpuri)

### wp_clinica_imports
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `import_type` ENUM(...) NOT NULL
- ... (alte câmpuri)

### wp_clinica_notifications
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` BIGINT UNSIGNED NOT NULL
- ... (alte câmpuri)

## Foreign Keys

Toate tabelele ar trebui să aibă foreign key-urile corecte:

- `fk_patients_user_id` → `wp_users(ID)`
- `fk_appointments_patient_id` → `wp_users(ID)`
- `fk_appointments_doctor_id` → `wp_users(ID)`
- etc.

## Suport

Dacă întâmpinați probleme, verificați:

1. **Log-urile WordPress** pentru erori
2. **Log-urile MySQL** pentru erori de bază de date
3. **Permisiunile utilizatorului** de baza de date

## Notă de Securitate

Scriptul `repair-database.php` trebuie șters după utilizare pentru a preveni accesul neautorizat. 