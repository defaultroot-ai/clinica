ti se pare# ROADMAP - Plugin Clinica Medicală

## 📋 Starea Actuală (August 2025)

### ✅ Funcționalități Implementate

#### 1. **Sistem de Roluri și Permisiuni**
- Roluri: `clinica_patient`, `clinica_doctor`, `clinica_manager`
- Capabilități personalizate pentru fiecare rol
- Sistem de verificare permisiuni în toate funcțiile

#### 2. **Dashboard Pacient**
- **Shortcode**: `[clinica_patient_dashboard]`
- **Funcționalități**:
  - Vizualizare profil pacient (CNP, email, telefon, etc.)
  - Lista programărilor cu filtrare (Toate, Viitoare, Trecute, Anulate)
  - Formular de creare programări proprii
  - Anularea programărilor viitoare
  - Statistici rapide (total programări, viitoare, mesaje)

#### 3. **Sistem de Programări**
- **Tabela**: `wp_clinica_appointments`
- **Funcționalități**:
  - Creare programări de către pacienți
  - Selectare serviciu, doctor, dată, oră
  - Verificare disponibilitate (exclude sloturi ocupate)
  - Limitări: 1 programare/24h per pacient
  - Statusuri: scheduled, confirmed, completed, cancelled, no_show

#### 4. **Catalog de Servicii**
- **Tabela**: `wp_clinica_services`
- **Funcționalități**:
  - CRUD servicii cu durate personalizate
  - Interfață admin pentru gestionare
  - Mapare servicii la programări

#### 5. **Gestionare Doctori**
- **Pagina Admin**: "Medici" (`admin/views/doctors.php`)
- **Funcționalități**:
  - Program de lucru per-doctor (în loc de global)
  - Pause și zile libere per-doctor
  - Editare inline din admin

#### 6. **Calendar Interactiv**
- **Integrare**: Flatpickr
- **Funcționalități**:
  - Afișare zile disponibile
  - Ascundere weekenduri vizual
  - Ziua curentă evidențiată
  - Sloturi de timp cu durata serviciului

#### 7. **Notificări Email**
- Confirmare creare programare
- Confirmare anulare programare
- Template-uri HTML personalizate
- Configurare expeditor din setări

#### 8. **Admin Backend**
- **Pagina Programări**: `admin/views/appointments.php`
- **Funcționalități**:
  - Lista toate programările cu filtrare
  - Afișare tip serviciu, ora fără secunde
  - Statusuri în română
  - Acțiuni: Vezi, Editează, Anulează

### 🔧 Probleme Rezolvate Recent

1. **Structura Tabelă Programări**
   - Adăugat câmpul `service_id`
   - Corectat maparea servicii la programări
   - Rezolvat afișarea tipului serviciului

2. **Frontend Dashboard**
   - Corectat încărcarea programărilor
   - Rezolvat problema cu ID-ul la anulare
   - Îmbunătățit CSS pentru carduri

3. **Backend Admin**
   - Corectat afișarea tipului serviciu
   - Formatat ora fără secunde
   - Statusuri în română
   - Buton anulare cu JavaScript

## 🗓️ Lucrări și planificare (22 → 25 August 2025)

### Backend > Pagina Programări (acțiuni)
- [x] Unifică stilul butoanelor și poziționarea (fără dependență de CSS WP)
- [x] „Vezi” în modal (AJAX) cu detalii complete programare
- [x] Implementare acțiune „Adaugă” (formular dedicat) cu validări
- [x] Log audit pentru creare/anulare programări
- [ ] Implementare „Editează” (formular) cu revalidări conflict slot-uri
- [ ] Confirmare la ștergere (dialog) + protecție cu nonce și capabilități
- [ ] Log audit pentru acțiuni critice (update/delete)
- [ ] Teste manuale: permisiuni pe roluri, fluxuri și mesaje de eroare

### Backend > Sincronizare Pacienți & Emailuri
- [x] Adaugă `email` în `wp_clinica_patients` + index
- [x] Sincronizare pacienți lipsă (users → patients) cu progres live
- [x] Sincronizare e-mailuri users ↔ patients cu progres live
- [x] Buton „Sincronizare completă” + bare progres unificate
- [x] Rezumat „Ultima sincronizare” în setări + acțiune rapidă
- [x] Vizualizare/descărcare/arhivare erori CNP invalide
- [x] Păstrează roluri duale („Subscriber, Pacient”)

## 🗓️ Plan pentru luni (25 August 2025)

- [ ] Implementare „Editează programare” (UI + validări + AJAX, cu rezumat live)
- [ ] Extindere audit pentru update/delete programări + opțiune de export din admin
- [ ] Îmbunătățiri UX formular: debouncing autosuggest, empty states, mesaje coerente
- [ ] Hardening securitate: capabilități explicite per endpoint + nonce-uri dedicate
- [ ] Testare manuală completă pe fluxuri principale (creare/vezi/editează/anulează)

## 🚀 Planuri Viitoare

### **Faza 1: Stabilizare și Testare (Săptembrie 2025)**

#### 1.1 **Testare Completă Funcționalități**
- [ ] Testare creare programări din frontend
- [ ] Testare anulare programări (frontend + admin)
- [ ] Testare filtrare programări
- [ ] Testare calendar și sloturi
- [ ] Testare notificări email

#### 1.2 **Debug și Optimizare**
- [ ] Verificare erori JavaScript
- [ ] Optimizare query-uri baza de date
- [ ] Verificare permisiuni și securitate
- [ ] Testare pe diferite versiuni WordPress

#### 1.3 **Documentație**
- [ ] Manual utilizator final
- [ ] Manual administrator
- [ ] API documentation pentru dezvoltatori

### **Faza 2: Funcționalități Avansate (Octombrie 2025)**

#### 2.1 **Sistem de Mesaje**
- [ ] Chat între pacient și doctor
- [ ] Notificări push
- [ ] Istoric conversații

#### 2.2 **Gestionare Dosare Medicale**
- [ ] Tabela `wp_clinica_medical_records`
- [ ] Upload fișiere (rezultate, prescripții)
- [ ] Istoric medical complet

#### 2.3 **Sistem de Reminder-uri**
- [ ] Email-uri de reamintire programări
- [ ] SMS-uri (integrare serviciu extern)
- [ ] Calendar personal cu notificări

### **Faza 3: Integrări și Extensii (Noiembrie 2025)**

#### 3.1 **Integrare Sistem Plăți**
- [ ] Stripe/PayPal pentru programări
- [ ] Facturare automată
- [ ] Rapoarte financiare

#### 3.2 **API REST**
- [ ] Endpoint-uri pentru aplicații mobile
- [ ] Autentificare OAuth2
- [ ] Rate limiting și securitate

#### 3.3 **Integrare Calendar Extern**
- [ ] Google Calendar
- [ ] Outlook Calendar
- [ ] Sincronizare bidirecțională

### **Faza 4: Mobile și PWA (Decembrie 2025)**

#### 4.1 **Aplicație Mobile**
- [ ] React Native sau Flutter
- [ ] Push notifications
- [ ] Offline functionality

#### 4.2 **Progressive Web App**
- [ ] Service workers
- [ ] Cache offline
- [ ] Install prompt

## 🛠️ Tehnologii Utilizate

### **Backend**
- **PHP**: 7.4+ (WordPress 5.0+)
- **Database**: MySQL/MariaDB cu tabele custom
- **WordPress**: Hooks, AJAX, Settings API

### **Frontend**
- **JavaScript**: jQuery, ES6+
- **CSS**: Grid, Flexbox, Responsive Design
- **Librării**: Flatpickr (calendar), Font Awesome (iconițe)

### **Infrastructură**
- **Email**: wp_mail cu template-uri HTML
- **Sesiuni**: PHP sessions cu verificări de securitate
- **Cache**: WordPress transients + cache JavaScript

## 📊 Metrici de Performanță

### **Obiective Curent**
- [ ] Timp încărcare dashboard < 2 secunde
- [ ] Răspuns AJAX < 500ms
- [ ] Compatibilitate 100% cu WordPress 5.0+
- [ ] Responsive design pe toate dispozitivele

### **Obiective Viitoare**
- [ ] Suport 1000+ utilizatori simultan
- [ ] Uptime 99.9%
- [ ] Integrare cu 5+ servicii externe
- [ ] Suport multi-lingvistic (EN, RO, DE)

## 🔒 Securitate și Conformitate

### **Implementat**
- ✅ Nonce verificare pentru toate acțiunile AJAX
- ✅ Verificare permisiuni la fiecare funcție
- ✅ Sanitizare input-uri
- ✅ Escape output-uri
- ✅ Verificare proprietar programări

### **Planificat**
- [ ] Audit securitate complet
- [ ] Implementare rate limiting
- [ ] Logging și monitoring
- [ ] Conformitate GDPR
- [ ] Backup automat baza de date

## 📝 Note de Dezvoltare

### **Arhitectura**
- Plugin folosește pattern-ul MVC
- Fiecare clasă are o responsabilitate specifică
- AJAX handlers separați pentru fiecare acțiune
- CSS și JS organizate modular

### **Baza de Date**
- Tabele custom cu prefix `wp_clinica_`
- Relații între `users`, `clinica_patients`, `clinica_appointments`
- Indexuri pentru performanță
- Backup automat prin WordPress

### **Compatibilitate**
- Testat pe WordPress 5.0 - 6.0
- Compatibil cu majoritatea temelor
- Nu interferează cu alte plugin-uri
- Fallback-uri pentru funcționalități lipsă

---

## 🗓️ Plan pentru mâine (26 August 2025)

### 🔧 Probleme de rezolvat urgent:

#### 1. **Serviciul dispare la editare programare**
- **Problema**: La editare, dropdown-ul serviciului arată "Selectează serviciu" în loc de serviciul corect
- **Cauza**: JavaScript-ul încearcă să seteze din nou valoarea serviciului cu `setTimeout`, suprascriind cea din PHP
- **Soluția**: Elimin complet setarea serviciului din JavaScript - PHP-ul deja setează corect cu `selected()`
- **Fișier**: `clinica/admin/views/appointments.php` - să șterg liniile cu `setTimeout` pentru serviciu

#### 2. **Testare funcționalitate editare**
- Verifică că serviciul rămâne selectat corect
- Verifică că doctorul se încarcă și rămâne selectat
- Verifică că intervalul orar se încarcă și rămâne selectat
- Testează salvarea modificărilor

### 📋 Lucrări în curs:
- [ ] Finalizare funcționalitate editare programări
- [ ] Testare completă fluxului editare
- [ ] Verificare că toate câmpurile se păstrează corect

---

**Ultima actualizare**: 25 August 2025  
**Versiune**: 1.0.0  
**Dezvoltator**: Asistent AI + Utilizator  
**Status**: Dezvoltare activă - Faza 1
