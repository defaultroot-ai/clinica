# Dashboard Pacient - Clinica Plugin

## Prezentare generală

Dashboard-ul pacient este o interfață modernă și intuitivă care permite pacienților să vizualizeze și să gestioneze informațiile lor medicale, programările și comunicarea cu personalul medical.

## Caracteristici principale

### 🏠 Prezentare generală
- **Informații personale**: Nume, CNP, data nașterii, vârstă, sex, contacte
- **Informații medicale**: Grupa sanguină, alergii, contact de urgență
- **Statistici rapide**: Programări totale, viitoare, mesaje necitite
- **Ultimele activități**: Istoricul recent al activităților

### 📅 Programări
- **Lista programărilor**: Toate programările pacientului
- **Filtrare**: Viitoare, trecute, anulate
- **Detalii complete**: Data, ora, doctor, tip, status, observații
- **Status vizual**: Confirmată, în așteptare, anulată

### 🏥 Informații medicale
- **Istoric medical**: Informații despre condițiile medicale
- **Rezultate analize**: Rezultatele testelor medicale
- **Prescripții**: Medicamentele prescrise și instrucțiuni

### 💬 Mesaje
- **Comunicare**: Mesaje cu personalul medical
- **Status citire**: Mesaje citite/necitite
- **Mesaj nou**: Funcționalitate de trimitere mesaje

## Structura tehnică

### Clase principale

#### `Clinica_Patient_Dashboard`
Clasa principală care gestionează întregul dashboard.

**Metode principale:**
- `render_dashboard_shortcode()` - Render shortcode-ul principal
- `get_patient_data()` - Obține datele pacientului
- `ajax_get_appointments()` - AJAX pentru programări
- `ajax_get_medical_history()` - AJAX pentru istoric medical

### Shortcode-uri

#### `[clinica_patient_dashboard]`
Shortcode-ul principal pentru afișarea dashboard-ului.

**Utilizare:**
```php
[clinica_patient_dashboard]
```

**Atribute:**
- Nu acceptă atribute momentan

### AJAX Endpoints

#### `clinica_get_appointments`
Obține programările pacientului.

**Parametri:**
- `patient_id` - ID-ul pacientului
- `nonce` - Nonce de securitate

**Răspuns:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "appointment_date": "2024-01-15",
            "appointment_time": "10:00",
            "doctor_name": "Dr. Popescu",
            "type": "Consultare",
            "status": "confirmed",
            "notes": "Observații..."
        }
    ]
}
```

#### `clinica_get_medical_history`
Obține istoricul medical al pacientului.

**Parametri:**
- `patient_id` - ID-ul pacientului
- `nonce` - Nonce de securitate

**Răspuns:**
```json
{
    "success": true,
    "data": {
        "results": "HTML pentru rezultate",
        "prescriptions": "HTML pentru prescripții"
    }
}
```

#### `clinica_get_dashboard_stats`
Obține statisticile dashboard-ului.

**Parametri:**
- `patient_id` - ID-ul pacientului
- `nonce` - Nonce de securitate

**Răspuns:**
```json
{
    "success": true,
    "data": {
        "total_appointments": 5,
        "upcoming_appointments": 2,
        "unread_messages": 1
    }
}
```

## Fișiere CSS și JavaScript

### CSS: `assets/css/patient-dashboard.css`
- Design modern și responsive
- Gradient-uri și animații
- Layout grid flexibil
- Stiluri pentru toate componentele

### JavaScript: `assets/js/patient-dashboard.js`
- Gestionare tab-uri
- AJAX pentru încărcarea datelor
- Sistem de mesaje
- Auto-refresh
- Keyboard shortcuts

## Funcționalități avansate

### Sistem de cache
- Cache pentru datele AJAX
- Timestamp pentru invalidare
- Optimizare performanță

### Auto-refresh
- Actualizare automată la 30 secunde
- Refresh inteligent doar pentru tab-ul activ
- Posibilitate de oprire

### Keyboard shortcuts
- `Ctrl/Cmd + 1-4` - Navigare tab-uri
- `Escape` - Închidere modale

### Responsive design
- Layout adaptabil pentru mobile
- Tab-uri verticale pe ecrane mici
- Optimizare pentru touch

## Securitate

### Verificări de permisiuni
- Verificare autentificare
- Verificare rol pacient
- Nonce pentru AJAX

### Sanitizare date
- Sanitizare input-uri
- Escape output-uri
- Validare parametri

## Testare

### Script de test: `test-dashboard.php`
- Testare funcționalitate completă
- Verificare JavaScript
- Test AJAX endpoints

**Utilizare:**
1. Accesează `/wp-content/plugins/clinica/test-dashboard.php`
2. Autentifică-te ca pacient
3. Verifică funcționalitățile

## Integrare cu alte componente

### Autentificare
- Integrare cu `Clinica_Authentication`
- Redirect după login
- Verificare roluri

### Baza de date
- Integrare cu `Clinica_Database`
- Tabele pacienți și programări
- Relații între tabele

### Roluri și permisiuni
- Integrare cu `Clinica_Roles`
- Verificare rol `clinica_patient`
- Permisiuni specifice

## Configurare

### Activare
Dashboard-ul se activează automat la activarea plugin-ului.

### Pagină dedicată
Se creează automat o pagină cu slug-ul `clinica-patient-dashboard`.

### Shortcode
Poate fi folosit în orice pagină sau post.

## Personalizare

### CSS Custom
```css
/* Personalizare header */
.dashboard-header {
    background: linear-gradient(135deg, #your-color1, #your-color2);
}

/* Personalizare tab-uri */
.tab-button.active {
    background: #your-active-color;
}
```

### JavaScript Custom
```javascript
// Adăugare funcționalitate custom
jQuery(document).ready(function($) {
    // Cod custom aici
});
```

## Troubleshooting

### Probleme comune

#### Dashboard nu se încarcă
1. Verifică dacă utilizatorul este logat
2. Verifică dacă are rolul `clinica_patient`
3. Verifică console-ul pentru erori JavaScript

#### AJAX nu funcționează
1. Verifică nonce-ul
2. Verifică permisiunile
3. Verifică log-urile PHP

#### Stiluri nu se încarcă
1. Verifică dacă CSS-ul este enqueued
2. Verifică cache-ul browser-ului
3. Verifică conflictele cu alte plugin-uri

### Debug
```php
// Activare debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Verificare log-uri
tail -f wp-content/debug.log
```

## Roadmap

### Versiunea următoare
- [ ] Editare profil pacient
- [ ] Sistem de mesaje complet
- [ ] Notificări push
- [ ] Export date medicale
- [ ] Integrare calendar

### Versiuni viitoare
- [ ] Aplicație mobilă
- [ ] Video consultații
- [ ] Integrare dispozitive medicale
- [ ] AI pentru recomandări

## Suport

Pentru suport tehnic sau întrebări:
- Documentația completă în `/docs/`
- Teste în `/test/`
- Log-uri în `wp-content/debug.log`

## Changelog

### v1.0.0 (2024-01-15)
- Implementare inițială dashboard
- Tab-uri pentru informații, programări, medical, mesaje
- AJAX pentru încărcarea datelor
- Design responsive
- Sistem de cache
- Auto-refresh
- Keyboard shortcuts 