# Fix Redirect După Autentificare

## Problema
După autentificare, utilizatorii nu sunt redirecționați automat către dashboard-ul corespunzător rolului lor.

## Cauze posibile

### 1. **Roluri utilizatori incorecte**
- Utilizatorii nu au rolurile Clinica atribuite corect
- Pacienții nu au rolul `clinica_patient`
- Alți utilizatori nu au rolurile corespunzătoare

### 2. **Paginile dashboard nu există**
- Paginile cu shortcode-urile dashboard nu au fost create
- URL-urile de redirect sunt invalide

### 3. **Probleme JavaScript**
- Răspunsul AJAX nu este procesat corect
- Erori în console-ul browser-ului

### 4. **Probleme de permisiuni**
- Utilizatorii nu au acces la paginile respective

## Logica de redirect implementată

### În `ajax_login()`:
```php
// Determină URL-ul de redirect
$redirect_url = '';
if (Clinica_Roles::has_clinica_role($user->ID)) {
    $role = Clinica_Roles::get_user_role($user->ID);
    
    switch ($role) {
        case 'clinica_patient':
            $redirect_url = home_url('/clinica-patient-dashboard/');
            break;
        default:
            $redirect_url = admin_url('admin.php?page=clinica-dashboard');
            break;
    }
} else {
    $redirect_url = home_url();
}
```

### În JavaScript:
```javascript
success: function(response) {
    if (response.success) {
        messages.html('<div class="success">' + response.data.message + '</div>');
        // Redirect după 2 secunde
        setTimeout(function() {
            window.location.href = response.data.redirect_url;
        }, 2000);
    } else {
        messages.html('<div class="error">' + response.data + '</div>');
    }
}
```

## URL-uri de redirect pentru fiecare rol

| Rol | URL Redirect |
|-----|--------------|
| `clinica_patient` | `home_url('/clinica-patient-dashboard/')` |
| `clinica_doctor` | `admin_url('admin.php?page=clinica-dashboard')` |
| `clinica_assistant` | `admin_url('admin.php?page=clinica-dashboard')` |
| `clinica_receptionist` | `admin_url('admin.php?page=clinica-dashboard')` |
| `clinica_manager` | `admin_url('admin.php?page=clinica-dashboard')` |
| `clinica_administrator` | `admin_url('admin.php?page=clinica-dashboard')` |

## Scripturi de testare

### 1. **Test complet redirect**
```
/wp-content/plugins/clinica/test-login-redirect.php
```
- Verifică utilizatorii cu roluri Clinica
- Testează logica de redirect pentru fiecare rol
- Verifică existența paginilor dashboard
- Testează AJAX login simulat

### 2. **Verificare roluri utilizatori**
```
/wp-content/plugins/clinica/fix-user-roles.php
```
- Verifică rolurile tuturor utilizatorilor
- Identifică pacienții fără rolul `clinica_patient`
- Testează redirect-ul pentru fiecare tip de rol
- Oferă sfaturi pentru debugging

## Pași de debugging

### 1. **Verificați rolurile utilizatorilor**
1. Accesați `/wp-content/plugins/clinica/fix-user-roles.php`
2. Verificați că pacienții au rolul `clinica_patient`
3. Verificați că alți utilizatori au rolurile corespunzătoare

### 2. **Verificați paginile dashboard**
1. Verificați că pagina `/clinica-patient-dashboard/` există
2. Verificați că meniul admin `admin.php?page=clinica-dashboard` funcționează

### 3. **Testați autentificarea**
1. Deschideți Developer Tools în browser
2. Accesați Network tab
3. Încercați să vă autentificați
4. Verificați răspunsul AJAX pentru `redirect_url`

### 4. **Verificați console-ul browser-ului**
1. Deschideți Console tab în Developer Tools
2. Căutați erori JavaScript
3. Verificați că nu există erori de sintaxă

## Soluții comune

### 1. **Atribuire roluri utilizatori**
```php
// Pentru un pacient
$user = get_user_by('ID', $patient_id);
$user->add_role('clinica_patient');

// Pentru un doctor
$user = get_user_by('ID', $doctor_id);
$user->add_role('clinica_doctor');
```

### 2. **Creare pagini dashboard**
```php
// Creează pagina dashboard pacient
wp_insert_post(array(
    'post_title' => 'Dashboard Pacient',
    'post_name' => 'clinica-patient-dashboard',
    'post_content' => '[clinica_patient_dashboard]',
    'post_status' => 'publish',
    'post_type' => 'page'
));
```

### 3. **Verificare meniu admin**
```php
// Verifică că meniul admin este înregistrat
add_action('admin_menu', function() {
    add_menu_page(
        'Clinica Dashboard',
        'Clinica',
        'manage_options',
        'clinica-dashboard',
        'clinica_dashboard_callback'
    );
});
```

## Verificări finale

Pentru ca redirect-ul să funcționeze:

- ✅ Utilizatorul are un rol Clinica valid
- ✅ Metoda `get_user_role()` returnează rolul corect
- ✅ URL-ul de redirect este valid
- ✅ JavaScript-ul procesează corect răspunsul AJAX
- ✅ Paginile dashboard există
- ✅ Utilizatorul are permisiuni pentru paginile respective

## Fișiere relevante

- `includes/class-clinica-authentication.php` - Logica de autentificare și redirect
- `includes/class-clinica-roles.php` - Gestionarea rolurilor
- `clinica.php` - Înregistrarea meniurilor admin
- `test-login-redirect.php` - Script de testare (nou)
- `fix-user-roles.php` - Script de verificare roluri (nou) 