# Fix Actualizare Pacient - AJAX Handler

## Problema
Eroarea "Failed to update patient" apărea la încercarea de actualizare a datelor unui pacient din interfața de administrare.

## Cauza
AJAX handler-ul `ajax_update_patient()` încerca să actualizeze câmpuri care nu există în tabela `clinica_patients`:
- `first_name`
- `last_name` 
- `user_email`

Aceste câmpuri sunt stocate în tabelele WordPress (`wp_users` și `wp_usermeta`), nu în tabela personalizată `clinica_patients`.

## Soluția
Am actualizat handler-ul AJAX să:

1. **Actualizeze datele utilizatorului WordPress** folosind `wp_update_user()` pentru:
   - `first_name`
   - `last_name`
   - `user_email`

2. **Actualizeze datele pacientului** în tabela `clinica_patients` pentru:
   - `phone_primary`
   - `phone_secondary`
   - `birth_date`
   - `gender`
   - `password_method`

3. **Actualizeze user meta** pentru numerele de telefon

## Codul actualizat

```php
// Update WordPress user data
$user_data = array(
    'ID' => $patient_id,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'user_email' => $email
);

$user_result = wp_update_user($user_data);

if (is_wp_error($user_result)) {
    wp_send_json_error('Failed to update user data: ' . $user_result->get_error_message());
    return;
}

// Update patient data in clinica_patients table
$patient_data = array(
    'phone_primary' => $phone_primary,
    'phone_secondary' => $phone_secondary,
    'birth_date' => $birth_date,
    'gender' => $gender,
    'password_method' => $password_method,
    'updated_at' => current_time('mysql'),
    'updated_by' => get_current_user_id()
);

$result = $wpdb->update($table_name, $patient_data, array('user_id' => $patient_id));
```

## Testare
Pentru a testa fix-ul:

1. Accesează `/wp-content/plugins/clinica/test-patient-update-fix.php`
2. Scriptul va simula o actualizare AJAX
3. Verifică că nu apar erori și că datele sunt actualizate corect

## Verificări
- ✅ Datele utilizatorului WordPress sunt actualizate
- ✅ Datele pacientului în tabela `clinica_patients` sunt actualizate
- ✅ User meta pentru numerele de telefon sunt actualizate
- ✅ Mesajele de eroare sunt mai descriptive

## Fișiere modificate
- `clinica.php` - AJAX handler `ajax_update_patient()`
- `test-patient-update-fix.php` - Script de testare (nou)
- `README-PATIENT-UPDATE-FIX.md` - Documentație (nou) 