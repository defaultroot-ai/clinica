# 📞 Sincronizare Numere de Telefon - Instrucțiuni Complete

## 🎯 Problema Identificată

Numărul de telefon nu este sincronizat pentru pacienții existenți din WordPress cu tabela `wp_clinica_patients`.

## 🔧 Scripturi Create

### 1. `check-phone-meta.php` - Verificare Meta Date
**Scop:** Verifică dacă numerele de telefon sunt salvate în meta datele utilizatorilor WordPress.

**URL:** `http://localhost/plm/wp-content/plugins/clinica/check-phone-meta.php`

**Ce face:**
- Găsește pacienții din WordPress
- Verifică meta datele pentru telefon
- Afișează toate meta keys găsite
- Verifică tabela clinica_patients

### 2. `sync-existing-patients.php` - Sincronizare Completă (Actualizat)
**Scop:** Sincronizează pacienții existenți din WordPress cu tabela `wp_clinica_patients`, inclusiv numerele de telefon.

**URL:** `http://localhost/plm/wp-content/plugins/clinica/sync-existing-patients.php`

**Ce face:**
- Găsește pacienții din WordPress cu rolul "clinica_patient"
- Include numerele de telefon din meta date (`phone_primary`, `phone_secondary`)
- Parsează CNP-ul pentru informații suplimentare
- Sincronizează în tabela `wp_clinica_patients`
- Afișează rezultatele detaliate

### 3. `update-phone-numbers.php` - Actualizare Telefoane
**Scop:** Actualizează numerele de telefon pentru pacienții deja sincronizați.

**URL:** `http://localhost/plm/wp-content/plugins/clinica/update-phone-numbers.php`

**Ce face:**
- Găsește pacienții fără numere de telefon
- Caută numerele în meta date (multiple keys: `phone_primary`, `phone`, `mobile`, `telefon`)
- Actualizează tabela `wp_clinica_patients`
- Afișează rezultatele

### 4. `test-phone-sync.php` - Test Final
**Scop:** Verifică dacă sincronizarea a funcționat corect.

**URL:** `http://localhost/plm/wp-content/plugins/clinica/test-phone-sync.php`

**Ce face:**
- Verifică pacienții cu numere de telefon
- Verifică pacienții fără numere de telefon
- Testează metoda `get_recent_patients_html()`
- Afișează statistici finale

## 📋 Pași de Urmat

### Pasul 1: Verificare Inițială
```
http://localhost/plm/wp-content/plugins/clinica/check-phone-meta.php
```
- Verifică dacă numerele de telefon sunt în meta datele WordPress
- Identifică meta keys folosite pentru telefon

### Pasul 2: Sincronizare Completă
```
http://localhost/plm/wp-content/plugins/clinica/sync-existing-patients.php
```
- Sincronizează toți pacienții cu numerele de telefon
- Afișează rezultatele detaliate

### Pasul 3: Actualizare Telefoane (dacă e necesar)
```
http://localhost/plm/wp-content/plugins/clinica/update-phone-numbers.php
```
- Actualizează pacienții care nu au numere de telefon
- Caută în multiple meta keys

### Pasul 4: Test Final
```
http://localhost/plm/wp-content/plugins/clinica/test-phone-sync.php
```
- Verifică dacă totul funcționează
- Afișează statistici finale

### Pasul 5: Verificare în Admin
```
http://localhost/plm/wp-admin/admin.php?page=clinica-patients
```
- Verifică dacă pacienții apar cu numerele de telefon

## 🔍 Meta Keys Verificate

Scripturile caută numerele de telefon în următoarele meta keys:
- `phone_primary` (principal)
- `phone_secondary` (secundar)
- `phone` (fallback)
- `mobile` (fallback)
- `telefon` (fallback)

## 📊 Rezultate Așteptate

După rularea scripturilor:
- ✅ Pacienții apar în lista din admin
- ✅ Numerele de telefon sunt afișate
- ✅ Metoda `get_recent_patients_html()` funcționează
- ✅ Toate funcționalitățile plugin-ului funcționează

## ⚠️ Note Importante

1. **Permisiuni:** Scripturile necesită permisiuni de administrator
2. **Backup:** Recomandat să faci backup înainte de rulare
3. **Testare:** Testează pe un mediu de dezvoltare înainte de producție
4. **Erori:** Verifică log-urile pentru erori

## 🚀 Rulare Rapidă

Pentru o rulare rapidă, folosește doar:
1. `sync-existing-patients.php` - pentru sincronizare completă
2. `test-phone-sync.php` - pentru verificare

## 📞 Suport

Dacă întâmpini probleme:
1. Verifică log-urile WordPress
2. Rulează scripturile de test
3. Verifică permisiunile de administrator 