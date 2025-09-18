# Idei de Extindere - Tab "Membrii de familie" (Dashboard Pacient)

## 1. Adăugare membru de familie (self-service)
- **Buton „Adaugă membru”** deschide un modal cu formular:
  - CNP sau email, nume, prenume, relație (părinte, copil, soț/soție etc.)
  - Validare automată CNP/email (să nu existe dubluri, să fie pacient real)
  - Trimitere invitație pe email dacă nu există cont
  - Confirmare de către membrul adăugat (acceptă/refuză legătura de familie)
- **ID funcționalitate:** `add_family_member_modal`

## 2. Editare și gestionare membri
- **Roluri editabile**: pacientul poate schimba relația (ex: din „copil” în „frate”)
- **Eliminare membru**: cu confirmare și eventual notificare către cel eliminat
- **Vizualizare detalii**: click pe card → afișează date de contact, vârstă, istoric medical sumar (dacă are permisiune)
- **ID-uri:** `edit_family_member_role`, `remove_family_member`, `family_member_details`

## 3. Notificări și log de activitate
- **Notificări** la adăugare/eliminare membru (email, dashboard)
- **Istoric modificări**: cine, când, ce modificare a făcut în familie
- **ID:** `family_activity_log`, `family_notifications`

## 4. Permisiuni avansate
- **Acces la date medicale**: pacientul poate acorda/restricționa accesul membrilor la fișa medicală, programări, rezultate analize etc.
- **Control granular**: exemplu – părintele vede tot, fratele doar date de contact
- **ID:** `family_access_permissions`

## 5. Export și rapoarte
- **Export familie PDF**: listă cu membrii, date de contact, relații, vârste
- **Export istoric modificări familie**
- **ID:** `export_family_pdf`

## 6. Interfață modernă și UX
- **Drag & drop** pentru reordonarea membrilor
- **Avatar personalizat** (poze reale, nu doar inițiale)
- **Căutare rapidă** printre membrii familiei (pentru familii extinse)
- **ID-uri:** `family_member_avatar_upload`, `family_member_search`

## 7. Funcționalități avansate (pentru viitor)
- **Integrare cu programări**: poți face programare pentru un membru al familiei direct din tab
- **Calendar comun**: vezi programările tuturor membrilor familiei într-un calendar
- **Alerte medicale**: notificări automate pentru vaccinări, rețete, controale periodice pentru copii/bătrâni din familie
- **ID-uri:** `family_calendar_view`, `family_notifications`

---

**Dacă vrei să implementezi una sau mai multe dintre aceste idei, consultă acest fișier pentru referință rapidă!** 