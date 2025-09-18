# Corectări Erori Bază de Date - Rezumat

## Probleme Identificate

### 1. Tabela `wp_clinica_doctors` inexistentă
**Eroare:** `Table 'plm.wp_clinica_doctors' doesn't exist`

**Cauza:** Query-ul din `get_recent_appointments_html()` încerca să facă JOIN cu o tabelă `clinica_doctors` care nu există în structura bazei de date.

**Soluția:** În loc să folosească o tabelă separată pentru doctori, sistemul folosește tabela `wp_users` pentru doctori, cu metadatele stocate în `wp_usermeta`.

### 2. Utilizare incorectă a `wpdb::prepare()`
**Eroare:** `Function wpdb::prepare was called incorrectly. The query argument of wpdb::prepare() must have a placeholder.`

**Cauza:** Metodele `get_recent_appointments_html()` și `get_recent_patients_html()` foloseau `$wpdb->prepare()` fără placeholders, ceea ce este incorect.

**Soluția:** Eliminarea `$wpdb->prepare()` pentru query-urile care nu au parametri dinamici.

## Corectări Aplicate

### 1. În `clinica.php` - Metoda `get_recent_appointments_html()`

**Înainte:**
```php
$appointments = $wpdb->get_results($wpdb->prepare("
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.type,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        d.first_name as doctor_first_name,
        d.last_name as doctor_last_name
    FROM {$wpdb->prefix}clinica_appointments a
    LEFT JOIN {$wpdb->prefix}clinica_patients p ON a.patient_id = p.id
    LEFT JOIN {$wpdb->prefix}clinica_doctors d ON a.doctor_id = d.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
"));
```

**După:**
```php
$appointments = $wpdb->get_results("
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.type,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        dm1.meta_value as doctor_first_name,
        dm2.meta_value as doctor_last_name
    FROM {$wpdb->prefix}clinica_appointments a
    LEFT JOIN {$wpdb->prefix}clinica_patients p ON a.patient_id = p.id
    LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID
    LEFT JOIN {$wpdb->usermeta} dm1 ON d.ID = dm1.user_id AND dm1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} dm2 ON d.ID = dm2.user_id AND dm2.meta_key = 'last_name'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
");
```

### 2. În `clinica.php` - Metoda `get_recent_patients_html()`

**Înainte:**
```php
$patients = $wpdb->get_results($wpdb->prepare("
    SELECT p.*, u.user_email, u.display_name,
           um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p 
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
    LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
    ORDER BY p.created_at DESC
    LIMIT 10
"));
```

**După:**
```php
$patients = $wpdb->get_results("
    SELECT p.*, u.user_email, u.display_name,
           um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p 
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
    LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
    ORDER BY p.created_at DESC
    LIMIT 10
");
```

### 3. Îmbunătățire afișare nume doctor

**Adăugat:** Gestionarea cazurilor când numele doctorului nu este disponibil:

```php
<td><?php 
    $doctor_first_name = $appointment->doctor_first_name ?: '';
    $doctor_last_name = $appointment->doctor_last_name ?: '';
    $doctor_name = trim($doctor_first_name . ' ' . $doctor_last_name);
    echo esc_html(!empty($doctor_name) ? $doctor_name : __('Doctor necunoscut', 'clinica')); 
?></td>
```

## Structura Corectă a Bazei de Date

### Tabelele care există:
- `wp_clinica_patients` - Pacienții
- `wp_clinica_appointments` - Programările
- `wp_clinica_medical_records` - Dosarele medicale
- `wp_clinica_login_logs` - Log-urile de autentificare
- `wp_clinica_imports` - Importurile
- `wp_clinica_notifications` - Notificările

### Doctorii sunt stocați în:
- `wp_users` - Informațiile de bază
- `wp_usermeta` - Metadatele (first_name, last_name, etc.)

### Relațiile:
- `clinica_appointments.doctor_id` → `wp_users.ID`
- `clinica_medical_records.doctor_id` → `wp_users.ID`

## Testare

Pentru a verifica că corectările funcționează, rulați:
```
http://your-site.com/wp-content/plugins/clinica/test-database-fixes.php
```

## Rezultat

După aceste corectări:
1. ✅ Erorile `Table 'plm.wp_clinica_doctors' doesn't exist` vor dispărea
2. ✅ Avertismentele `wpdb::prepare was called incorrectly` vor dispărea
3. ✅ Dashboard-ul admin va funcționa corect
4. ✅ Programările vor fi afișate cu numele corecte ale doctorilor

## Note Importante

- Doctorii sunt gestionați ca utilizatori WordPress cu roluri speciale
- Nu există o tabelă separată pentru doctori
- Toate referințele la `clinica_doctors` au fost înlocuite cu JOIN-uri la `wp_users` și `wp_usermeta`
- Query-urile fără parametri dinamici nu mai folosesc `$wpdb->prepare()` 