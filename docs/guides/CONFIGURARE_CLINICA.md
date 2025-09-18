# Configurare Clinică - Ghid Complet

## 📋 Prezentare Generală

Sistemul de configurare clinică permite personalizarea completă a plugin-ului Clinica pentru a se adapta nevoilor specifice fiecărei clinici medicale.

## 🏥 Configurare Clinică

### Informații de bază
- **Numele clinicii** - Afișat în interfață și email-uri
- **Adresa clinicii** - Pentru programări și documente
- **Telefon clinică** - Pentru contact direct
- **Email clinică** - Pentru notificări și comunicare
- **Website clinică** - Pentru referințe externe
- **Logo clinică** - Pentru branding profesional

### Program Funcționare
Configurare detaliată pentru zilele de lucru:
- **Luni - Vineri**: 09:00 - 17:00
- **Weekend**: Închis (sâmbătă și duminică)

## 📧 Setări Email

### Configurare SMTP
- **SMTP Host** - Serverul de email
- **SMTP Port** - Portul (587 pentru TLS, 465 pentru SSL)
- **SMTP Username** - Numele de utilizator
- **SMTP Password** - Parola
- **SMTP Encryption** - Tipul de criptare (TLS/SSL/None)

### Notificări
- **Nume expeditor** - Numele care apare în email-uri
- **Adresa expeditor** - Email-ul care trimite notificările

## 📅 Setări Programări

### Durată și Intervale
- **Durată programări** - Timpul alocat fiecărei programări (30 minute)
- **Interval între programări** - Timpul minim între programări (15 minute)
- **Zile în avans** - Câte zile în avans se pot face programări (30 zile)

## 🔔 Setări Notificări

### Activare Notificări
- **Notificări activate** - Activează/dezactivează toate notificările
- **Reminder** - Zile înainte de programare pentru reminder (1 zi)
- **Confirmare programări** - Cere confirmarea prin email

## 🔒 Setări Securitate

### Sesiuni și Login
- **Timeout sesiuni** - După câte minute de inactivitate se închide sesiunea (30 minute)
- **Încercări de login** - Numărul maxim de încercări înainte de blocare (5)
- **Durată blocare** - Timpul de blocare după încercări eșuate (15 minute)

## ⚡ Setări Performanță

### Optimizare
- **Elemente pe pagină** - Numărul de elemente afișate (20)
- **Cache activat** - Activează cache-ul pentru performanță
- **Auto-refresh** - Intervalul de actualizare automată (30 secunde, 0 = dezactivat)

## 🛠️ Utilizare

### Accesare Setări
1. Mergi la **Admin → Clinica → Setări**
2. Completează informațiile pentru fiecare secțiune
3. Salvează modificările

### Funcții Disponibile

#### Obținere Setări
```php
$settings = Clinica_Settings::get_instance();
$clinic_name = $settings->get('clinic_name');
$clinic_email = $settings->get('clinic_email');
```

#### Setare Valori
```php
$settings->set('clinic_name', 'Nume Nou Clinică');
$settings->set('appointment_duration', 45);
```

#### Obținere Grup Setări
```php
$clinic_settings = $settings->get_group('clinic');
$email_settings = $settings->get_group('email');
```

#### Setări Publice
```php
$public_settings = $settings->get_public_settings();
```

## 📊 Tipuri de Setări

### Text
- Pentru nume, adrese, email-uri
- Sanitizare automată

### Number
- Pentru durate, intervale, timeout-uri
- Validare numerică

### Boolean
- Pentru activare/dezactivare
- Checkbox în interfață

### Textarea
- Pentru adrese complete
- Text multilinie

### JSON
- Pentru date complexe (program funcționare)
- Serializare automată

### File
- Pentru logo-uri și fișiere
- Upload și validare

## 🔧 Configurare Avansată

### Adăugare Setări Noi
```php
$settings->add_setting(
    'custom_setting',
    'valoare_implicita',
    'text',
    'custom_group',
    'Label Setare',
    'Descriere setare',
    false // public
);
```

### Ștergere Setări
```php
$settings->delete_setting('setting_key');
```

### Cache Management
```php
$settings->clear_cache(); // Șterge cache-ul
```

## 📝 Exemple de Utilizare

### În Template-uri
```php
$settings = Clinica_Settings::get_instance();
$clinic_name = $settings->get('clinic_name');
$clinic_phone = $settings->get('clinic_phone');

echo "<h1>$clinic_name</h1>";
echo "<p>Contact: $clinic_phone</p>";
```

### În Email-uri
```php
$settings = Clinica_Settings::get_instance();
$from_name = $settings->get('email_from_name');
$from_email = $settings->get('email_from_address');

wp_mail($to, $subject, $message, array(
    'From: ' . $from_name . ' <' . $from_email . '>'
));
```

### În Programări
```php
$settings = Clinica_Settings::get_instance();
$duration = $settings->get('appointment_duration');
$interval = $settings->get('appointment_interval');

// Calculează timpul disponibil
$available_slots = calculate_available_slots($duration, $interval);
```

## 🚨 Troubleshooting

### Probleme Comune

#### Setările nu se salvează
- Verifică permisiunile de scriere în baza de date
- Verifică dacă tabelul `wp_clinica_settings` există
- Verifică log-urile de eroare WordPress

#### Email-urile nu se trimit
- Verifică configurarea SMTP
- Testează conexiunea la serverul de email
- Verifică dacă portul și criptarea sunt corecte

#### Cache-ul nu se actualizează
- Folosește `$settings->clear_cache()`
- Verifică dacă cache-ul este activat
- Restart serverul web dacă este necesar

### Debug
```php
// Verifică informațiile despre o setare
$setting_info = $settings->get_setting_info('setting_key');
var_dump($setting_info);

// Verifică toate setările dintr-un grup
$group_settings = $settings->get_group('clinic');
var_dump($group_settings);
```

## 📈 Performanță

### Optimizări Recomandate
1. **Cache activat** - Pentru setări frecvent accesate
2. **Auto-refresh** - Pentru dashboard-uri active
3. **Elemente pe pagină** - Optimizat pentru server

### Monitorizare
- Verifică timpul de încărcare al paginilor
- Monitorizează utilizarea memoriei
- Testează performanța cache-ului

## 🔄 Backup și Restore

### Backup Setări
```php
$settings = Clinica_Settings::get_instance();
$all_settings = array();

$groups = array('clinic', 'schedule', 'email', 'appointments', 'notifications', 'security', 'performance');
foreach ($groups as $group) {
    $all_settings[$group] = $settings->get_group($group);
}

// Salvează în fișier
file_put_contents('settings_backup.json', json_encode($all_settings, JSON_PRETTY_PRINT));
```

### Restore Setări
```php
$backup_data = json_decode(file_get_contents('settings_backup.json'), true);
$settings = Clinica_Settings::get_instance();

foreach ($backup_data as $group => $group_settings) {
    foreach ($group_settings as $key => $setting) {
        $settings->set($key, $setting['value']);
    }
}
```

---

**Notă**: Toate setările sunt salvate în baza de date și sunt disponibile global în aplicație. Modificările sunt imediat active după salvare. 