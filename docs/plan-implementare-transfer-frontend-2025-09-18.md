# Plan Implementare Transfer Programări - Frontend
**Data:** 18 Septembrie 2025  
**Obiectiv:** Implementarea funcționalității de mutare programări în dashboard-urile frontend (doctor, asistent, receptionist)

## 1. ANALIZA SITUAȚIEI ACTUALE

### ✅ Ce există deja:
- **Backend transfer modal** - complet funcțional
- **Funcția `ajax_admin_transfer_appointment`** - validări complete
- **UI components** - calendar Flatpickr, selecție doctori, sloturi
- **Logica de validare** - disponibilitate, conflicte, permisiuni

### 🎯 Ce trebuie implementat:
- **Frontend transfer modal** pentru fiecare rol
- **Integrare cu dashboard-urile existente**
- **Adaptare UI pentru frontend**
- **Configurare permisiuni per rol**

## 2. ROLURILE ȘI DASHBOARD-URILE

### 2.1 Dashboard Doctor
- **Fișier:** `includes/class-clinica-doctor-dashboard.php`
- **Permisiuni:** Poate muta programările proprii
- **Restricții:** Doar programările din ziua curentă/viitoare

### 2.2 Dashboard Asistent
- **Fișier:** `includes/class-clinica-assistant-dashboard.php`
- **Permisiuni:** Poate muta programările pentru doctorii asignați
- **Restricții:** Doar programările din ziua curentă/viitoare

### 2.3 Dashboard Receptionist
- **Fișier:** `includes/class-clinica-receptionist-dashboard.php`
- **Permisiuni:** Poate muta orice programare
- **Restricții:** Fără restricții de timp

## 3. IMPLEMENTAREA PE ETAPE

### ETAPA 1: Pregătire și Analiză
- [ ] **1.1** Analizează dashboard-urile existente
- [ ] **1.2** Identifică locațiile pentru butoanele "Mută programare"
- [ ] **1.3** Verifică permisiunile existente per rol
- [ ] **1.4** Creează checkpoint pentru starea actuală

### ETAPA 2: Implementare Core Transfer
- [ ] **2.1** Creează funcția `ajax_frontend_transfer_appointment`
- [ ] **2.2** Adaptează validările pentru frontend
- [ ] **2.3** Implementează permisiunile per rol
- [ ] **2.4** Adaugă notificări email/SMS

### ETAPA 3: UI Components Frontend
- [ ] **3.1** Creează modal transfer pentru frontend
- [ ] **3.2** Adaptează calendar Flatpickr pentru frontend
- [ ] **3.3** Implementează selecția doctori cu butoane
- [ ] **3.4** Implementează selecția sloturi cu grilă
- [ ] **3.5** Adaugă stiluri CSS pentru frontend

### ETAPA 4: Integrare Dashboard Doctor
- [ ] **4.1** Adaugă buton "Mută programare" în lista programărilor
- [ ] **4.2** Implementează JavaScript pentru transfer
- [ ] **4.3** Testează funcționalitatea
- [ ] **4.4** Verifică permisiunile (doar programările proprii)

### ETAPA 5: Integrare Dashboard Asistent
- [ ] **5.1** Adaugă buton "Mută programare" în lista programărilor
- [ ] **5.2** Implementează JavaScript pentru transfer
- [ ] **5.3** Testează funcționalitatea
- [ ] **5.4** Verifică permisiunile (doar doctorii asignați)

### ETAPA 6: Integrare Dashboard Receptionist
- [ ] **6.1** Adaugă buton "Mută programare" în lista programărilor
- [ ] **6.2** Implementează JavaScript pentru transfer
- [ ] **6.3** Testează funcționalitatea
- [ ] **6.4** Verifică permisiunile (toate programările)

### ETAPA 7: Testare și Optimizare
- [ ] **7.1** Testează toate scenariile de transfer
- [ ] **7.2** Verifică validările și restricțiile
- [ ] **7.3** Testează notificările
- [ ] **7.4** Optimizează performanța
- [ ] **7.5** Testează pe toate rolurile

### ETAPA 8: Documentare și Finalizare
- [ ] **8.1** Documentează funcționalitatea
- [ ] **8.2** Creează ghid de utilizare
- [ ] **8.3** Finalizează testele
- [ ] **8.4** Commit final

## 4. FIȘIERE DE MODIFICAT

### 4.1 Fișiere Backend (existente)
- `includes/class-clinica-patient-dashboard.php` - funcția transfer
- `admin/views/appointments.php` - modal backend (referință)

### 4.2 Fișiere Frontend (de modificat)
- `includes/class-clinica-doctor-dashboard.php` - dashboard doctor
- `includes/class-clinica-assistant-dashboard.php` - dashboard asistent
- `includes/class-clinica-receptionist-dashboard.php` - dashboard receptionist
- `assets/css/frontend.css` - stiluri frontend
- `assets/js/frontend.js` - JavaScript frontend

### 4.3 Fișiere Noi (de creat)
- `templates/transfer-modal-frontend.php` - template modal
- `assets/css/transfer-frontend.css` - stiluri specifice
- `assets/js/transfer-frontend.js` - JavaScript specific

## 5. PERMISIUNI ȘI RESTRICȚII

### 5.1 Doctor
- **Poate muta:** Doar programările proprii
- **Restricții timp:** Doar programările din ziua curentă/viitoare
- **Notificări:** Pacient + Doctor nou

### 5.2 Asistent
- **Poate muta:** Programările pentru doctorii asignați
- **Restricții timp:** Doar programările din ziua curentă/viitoare
- **Notificări:** Pacient + Doctor vechi + Doctor nou

### 5.3 Receptionist
- **Poate muta:** Orice programare
- **Restricții timp:** Fără restricții
- **Notificări:** Pacient + Doctor vechi + Doctor nou

## 6. COMPONENTE UI

### 6.1 Modal Transfer
- **Header:** "Mută programare"
- **Body:** Calendar + Doctori + Sloturi
- **Footer:** Butoane Anulează + Confirmă

### 6.2 Calendar
- **Librărie:** Flatpickr (ca în backend)
- **Funcționalitate:** Doar zilele cu sloturi disponibile
- **Stil:** Adaptat pentru frontend

### 6.3 Selecție Doctori
- **Format:** Butoane în grilă (3 pe linie)
- **Funcționalitate:** Filtrare după rol
- **Stil:** Modern, responsive

### 6.4 Selecție Sloturi
- **Format:** Grilă de butoane
- **Funcționalitate:** Sloturi disponibile pentru doctor + serviciu
- **Stil:** Ca în backend, adaptat pentru frontend

## 7. NOTIFICĂRI

### 7.1 Email
- **Pacient:** Programarea a fost mutată
- **Doctor vechi:** Programarea a fost mutată
- **Doctor nou:** Programare nouă mutată

### 7.2 SMS (dacă este configurat)
- **Pacient:** Confirmare mutare
- **Doctor nou:** Notificare programare nouă

## 8. TESTARE

### 8.1 Scenarii de Test
- **Transfer reușit:** Programare mutată cu succes
- **Transfer eșuat:** Validări și mesaje de eroare
- **Permisiuni:** Testare per rol
- **Notificări:** Verificare trimitere email/SMS

### 8.2 Testare Cross-Role
- **Doctor:** Doar programările proprii
- **Asistent:** Doar doctorii asignați
- **Receptionist:** Toate programările

## 9. TIMP ESTIMAT

- **ETAPA 1-2:** 1 oră (analiză + core)
- **ETAPA 3:** 1.5 ore (UI components)
- **ETAPA 4-6:** 2 ore (integrare dashboard-uri)
- **ETAPA 7-8:** 0.5 ore (testare + documentare)
- **TOTAL:** 5 ore

## 10. RISCURI ȘI MITIGĂRI

### 10.1 Riscuri
- **Conflicte CSS:** Stiluri frontend vs backend
- **Permisiuni:** Logica complexă per rol
- **Performance:** Multiple AJAX calls

### 10.2 Mitigări
- **Namespace CSS:** Prefixe specifice
- **Testare extensivă:** Toate scenariile
- **Caching:** Pentru AJAX calls

---

**Status:** Plan creat  
**Următorul pas:** Începere ETAPA 1 - Analiză dashboard-uri existente
