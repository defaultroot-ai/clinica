# Fix Autentificare - Username, Email, Telefon

## Problema
Autentificarea nu funcționa corect cu toate tipurile de identificatori (username, email, telefon).

## Cauza
Metoda `find_user_by_identifier()` din clasa `Clinica_Authentication` nu căuta corect după numerele de telefon în user meta.

## Soluția implementată

### 1. Îmbunătățirea metodei `find_user_by_identifier()`

**Înainte:**
```php
// Căutarea după telefon se făcea doar în tabela clinica_patients
$user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM $table_name WHERE phone_primary = %s OR phone_secondary = %s",
    $identifier,
    $identifier
));
```

**După:**
```php
// 1. Căutare după username
$user = get_user_by('login', $identifier);

// 2. Căutare după email
$user = get_user_by('email', $identifier);

// 3. Căutare după telefon în user meta (PRINCIPAL)
$user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM {$wpdb->usermeta} 
     WHERE meta_key IN ('phone_primary', 'phone_secondary') 
     AND meta_value = %s",
    $identifier
));

// 4. Căutare după telefon în tabela pacienți (fallback)
$user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM $table_name WHERE phone_primary = %s OR phone_secondary = %s",
    $identifier,
    $identifier
));
```

### 2. Ordinea de căutare optimizată

1. **Username** - pentru pacienți (CNP-ul este username-ul)
2. **Email** - pentru toți utilizatorii
3. **Telefon în user meta** - pentru sincronizare corectă
4. **Telefon în tabela pacienți** - fallback pentru compatibilitate

## Tipuri de autentificare suportate

### ✅ Username (CNP)
- Pentru pacienți, CNP-ul este username-ul
- Exemplu: `1800404080170`

### ✅ Email
- Pentru toți utilizatorii
- Exemplu: `pacient@example.com`

### ✅ Telefon Principal
- Din user meta (`phone_primary`)
- Exemplu: `0722123456`

### ✅ Telefon Secundar
- Din user meta (`phone_secondary`)
- Exemplu: `0211234567`

## Testare

Pentru a testa fix-ul:

1. Accesează `/wp-content/plugins/clinica/test-authentication-fix.php`
2. Scriptul va:
   - Afișa pacienții existenți
   - Testa căutarea cu fiecare tip de identificator
   - Verifica sincronizarea user meta
   - Simula AJAX login

## Verificări importante

### 1. Sincronizarea user meta
Numerele de telefon trebuie să fie salvate atât în:
- `clinica_patients` table
- `wp_usermeta` table (chei: `phone_primary`, `phone_secondary`)

### 2. Căutarea în user meta
Metoda `find_user_by_identifier()` caută acum în `wp_usermeta` pentru telefoane, ceea ce asigură că autentificarea funcționează corect.

### 3. Fallback pentru compatibilitate
Dacă nu găsește în user meta, caută în tabela `clinica_patients` pentru a menține compatibilitatea.

## Fișiere modificate

1. **`includes/class-clinica-authentication.php`** - Metoda `find_user_by_identifier()` actualizată
2. **`test-authentication-fix.php`** - Script de testare (nou)
3. **`README-AUTHENTICATION-FIX.md`** - Documentație (nou)

## Rezultat

Acum autentificarea funcționează corect cu:
- ✅ Username (CNP pentru pacienți)
- ✅ Email
- ✅ Telefon principal
- ✅ Telefon secundar

Utilizatorii se pot autentifica folosind oricare dintre aceste identificatori! 🎉 