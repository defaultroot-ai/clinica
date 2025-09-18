## Status lucru – 20.08.2025

### Rezumat pe scurt
- Am implementat fluxul complet de programări în frontend pentru pacienți (calendar + sloturi), cu verificări la creare și anulare, notificări email și statistici.
- Am introdus program per-doctor (inclusiv pauză pe zi) cu editor în profil și o pagină dedicată „Medici” în admin, cu modal de editare inline (program + concedii).
- Am adăugat „Catalog servicii” editabil în setări (UI tabelar) și limitări operaționale (max programări/zi/medic, 1 programare/24h/pacient).

### Ce s-a livrat azi
- Frontend pacient (în `Clinica_Patient_Dashboard`):
  - Formular „Programare nouă” redesenat pe două coloane: calendar zile disponibile și grilă de sloturi orare.
  - Selectare pacient/serviciu/medic, rezumat live, salvare și anulare cu notificări email.
  - Excludere din sloturi: program doctor, pauze, concedii, zile libere clinică, limită pe medic/zi, conflict cu programări existente.
- Setări și infrastructură:
  - `services_catalog` cu editor vizual (ID, Nume, Durată). Date serializate JSON.
  - `clinic_holidays` (zile libere) + `max_appointments_per_doctor_per_day`.
- Medici (admin):
  - Pagină dedicată „Medici” (listă din utilizatorii WP cu rol `clinica_doctor`/`clinica_manager`).
  - Modal AJAX pentru editarea programului per-doctor și a concediilor.
- Program per-doctor:
  - Câmpuri `break_start`/`break_end` (pauză) pe fiecare zi; concedii per-doctor (`clinica_doctor_holidays`).
- Emailuri:
  - Confirmare/anulare programare către pacient și medic, cu From din setări.
- Utilitare:
  - `tools/check/check-doctors-exist.php` – verificare existență medici (listă rapidă + link-uri WP Users).

### Fișiere atinse (selectiv)
- `includes/class-clinica-patient-dashboard.php` – formular, calendar/sloturi, emailuri, AJAX, statistici.
- `includes/class-clinica-settings.php` – `services_catalog`, `clinic_holidays`, `max_appointments_per_doctor_per_day`.
- `clinica.php` – meniu „Medici”, profil medic (program + pauze + concedii), AJAX admin doctors.
- `admin/views/settings.php` – UI catalog servicii, zile libere, limită/zi/medic.
- `admin/views/doctors.php` – listă medici + modal editare program/concedii.
- `includes/class-clinica-patient-permissions.php` – `can_view_doctors`.
- `tools/check/check-doctors-exist.php` – utilitar verificare medici.

### Recomandări imediate
- Logging emailuri: jurnal minim (succes/eroare) într-un fișier din `logs/` pentru suport.
- UI calendar: navigare pe săptămâni (următoarele 2–4) și evidențiere ziua curentă.
- Validări UI program doctor: forțarea `end > start` și pauza în interiorul intervalului zilnic.
- Mesaje UI: confirmări non-intruzive (toasts) la salvări în admin și în frontend.
- Securitate: nonce separat pentru ruta de creare programare publică și ratelimiting soft la 3 încercări/minut.

### Plan ulterior / mâine
- Extindere calendar
  - Navigare „<< săptămâna anterioară | săptămâna următoare >>” (limită: `appointment_advance_days`).
  - Afișare rapidă a „n sloturi rămase”/zi pe calendar.
- Servicii avansate
  - Opțiune durată variabilă per medic (override durata serviciului la nivel de doctor).
  - Mapare servicii-medici (filtrare doctori după ce pot presta).
- Notificări
  - Șabloane email personalizabile (header cu logo, footer, link confirmare/renunțare).
  - Opțional SMS (hook pregătit, fără integrare imediată).
- Admin Medici
  - Editare inline cu validare și previzualizare sloturi generate pentru o zi.
  - Export/Import program (JSON) pentru reutilizare.
- Observabilitate
  - Log dedicat pentru programări (`logs/appointments.log`) + evenimente WP hooks pentru audit.

### Note
- Pentru funcționalități noi, păstrăm schimbările minime în structura existentă și respectăm capabilitățile definite de roluri.
- Toate rutele AJAX folosesc nonce; REST rămâne disponibil pentru integrare viitoare.


Dacă vrei, pot:
adăuga săgeți pentru navigarea pe două săptămâni în calendar,
bloca zilele în care limita/zi e deja atinsă,
afișa în slot „n slot-uri rămase” sau status „aproape plin”.image.png