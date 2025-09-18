# Dashboard Doctor - Clinica Plugin

## Descriere

Dashboard-ul pentru doctori oferă o interfață modernă și intuitivă pentru gestionarea activităților medicale. Este accesibil prin shortcode și oferă funcționalități complete pentru programări, pacienți și fișe medicale.

## Funcționalități

### 1. Prezentare Generală
- **Statistici zilnice**: Programări astăzi (total, confirmate, în așteptare)
- **Statistici săptămânale**: Programări săptămâna aceasta (total, finalizate, anulate)
- **Pacienți activi**: Total pacienți și pacienți noi (luna aceasta)
- **Activități recente**: Ultimele activități din sistem

### 2. Gestionare Programări
- **Filtrare după dată**: Astăzi, mâine, săptămâna aceasta, luna aceasta
- **Filtrare după status**: Confirmate, în așteptare, anulate, finalizate
- **Acțiuni rapide**: Confirmare, anulare, finalizare programări
- **Detalii complete**: Informații despre pacient, tip programare, note

### 3. Gestionare Pacienți
- **Căutare avansată**: După nume sau CNP
- **Sortare**: După nume, ultima vizită, numărul de programări
- **Informații complete**: Date de contact, ultima vizită, istoric programări
- **Acces rapid la fișa medicală**

### 4. Fișe Medicale
- **Informații pacient**: Date personale, grupa de sânge, alergii
- **Istoric consultații**: Diagnostic, tratament, note
- **Rezultate analize**: Fișiere atașate, descărcare
- **Adăugare note medicale**: Interfață pentru adăugarea de note noi

## Instalare și Configurare

### 1. Shortcode
Adăugați shortcode-ul pe orice pagină:
```
[clinica_doctor_dashboard]
```

### 2. Roluri Utilizatori
Utilizatorii trebuie să aibă rolul `doctor` pentru a accesa dashboard-ul.

### 3. Metadate Doctor
Pentru o experiență completă, adăugați metadatele doctorului:
```php
update_user_meta($user_id, 'doctor_specialty', 'Cardiologie');
update_user_meta($user_id, 'doctor_license', '12345');
```

## Structura Fișierelor

```
clinica/
├── includes/
│   └── class-clinica-doctor-dashboard.php    # Clasa principală
├── assets/
│   ├── css/
│   │   └── doctor-dashboard.css              # Stiluri CSS
│   └── js/
│       └── doctor-dashboard.js               # Funcționalitate JavaScript
└── test-doctor-dashboard.php                 # Script de testare
```

## API Endpoints

### AJAX Actions
- `clinica_get_doctor_overview` - Statistici generale
- `clinica_get_doctor_appointments` - Lista programări
- `clinica_get_doctor_patients` - Lista pacienți
- `clinica_get_doctor_medical_records` - Fișe medicale
- `clinica_update_appointment_status` - Actualizare status programare
- `clinica_add_medical_note` - Adăugare notă medicală

### Parametri
- `nonce` - Token de securitate
- `date_filter` - Filtru dată (today, tomorrow, week, month)
- `status_filter` - Filtru status (confirmed, pending, cancelled, completed)
- `search` - Termen de căutare pentru pacienți
- `sort` - Sortare (name, recent, appointments)
- `patient_id` - ID pacient pentru fișe medicale

## Stilizare

### Culori Principale
- **Header**: Gradient verde (`#28a745` → `#20c997`)
- **Butoane primare**: Albastru WordPress (`#0073AA`)
- **Butoane secundare**: Roșu (`#dc3545`)
- **Tab-uri active**: Verde (`#28a745`)

### Responsive Design
- **Desktop**: Layout cu grid-uri și sidebar
- **Tablet**: Layout adaptat pentru ecrane medii
- **Mobile**: Layout vertical cu butoane mari

## Funcționalități Avansate

### 1. Keyboard Shortcuts
- `Ctrl/Cmd + 1` - Prezentare Generală
- `Ctrl/Cmd + 2` - Programări
- `Ctrl/Cmd + 3` - Pacienți
- `Ctrl/Cmd + 4` - Fișe Medicale

### 2. Auto-refresh
- Statisticile se actualizează automat la fiecare 5 minute
- Căutarea are debounce de 300ms

### 3. Export și Print
- Export programări în format CSV/PDF
- Export fișe medicale
- Funcționalitate de printare

### 4. Notificări
- Mesaje de succes/eroare
- Confirmări pentru acțiuni importante
- Auto-hide după 5 secunde

## Testare

### Script de Test
Rulați `test-doctor-dashboard.php` pentru a verifica:
- Funcționalitatea shortcode-ului
- Prezența fișierelor CSS/JS
- Configurarea rolurilor
- Metadatele doctorului

### Testare Manuală
1. **Autentificare**: Verificați că doar doctorii pot accesa
2. **Tab-uri**: Testați schimbarea între tab-uri
3. **Filtre**: Testați filtrarea programărilor și pacienților
4. **AJAX**: Verificați cererile în Developer Tools
5. **Responsive**: Testați pe diferite dispozitive

## Securitate

### Verificări
- **Nonce**: Toate cererile AJAX folosesc nonce-uri
- **Roluri**: Verificare rol `doctor` pentru toate acțiunile
- **Capacități**: Verificare `current_user_can('doctor')`
- **Sanitizare**: Toate datele de intrare sunt sanitizate

### Permisiuni
- Doar utilizatorii cu rolul `doctor` pot accesa dashboard-ul
- Fiecare doctor vede doar propriile programări și pacienți
- Fișele medicale sunt accesibile doar pentru pacienții doctorului

## Personalizare

### CSS Custom
Puteți personaliza aspectul prin CSS custom:
```css
.clinica-doctor-dashboard {
    /* Stiluri personalizate */
}

.dashboard-header {
    /* Header personalizat */
}
```

### JavaScript Custom
Puteți adăuga funcționalități custom:
```javascript
// Hook pentru încărcarea dashboard-ului
$(document).on('clinica_doctor_dashboard_loaded', function() {
    // Cod personalizat
});
```

## Troubleshooting

### Probleme Comune

1. **Dashboard nu se încarcă**
   - Verificați rolul utilizatorului
   - Verificați prezența fișierelor CSS/JS
   - Verificați erorile în consolă

2. **AJAX nu funcționează**
   - Verificați nonce-urile
   - Verificați permisiunile utilizatorului
   - Verificați erorile în Network tab

3. **Stiluri nu se aplică**
   - Verificați cache-ul browser-ului
   - Verificați conflictele cu alte plugin-uri
   - Verificați prezența fișierului CSS

### Debug
- Activați `WP_DEBUG` în `wp-config.php`
- Verificați log-urile de eroare
- Folosiți Developer Tools pentru debugging

## Suport

Pentru suport tehnic sau întrebări:
- Verificați documentația completă
- Rulați scripturile de testare
- Verificați log-urile de eroare
- Contactați echipa de dezvoltare

## Versiunea

Dashboard-ul doctorilor este parte din Clinica Plugin v1.0.0 și este compatibil cu WordPress 5.0+ și PHP 7.4+. 