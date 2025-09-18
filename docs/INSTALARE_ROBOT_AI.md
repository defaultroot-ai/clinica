# 🚀 Ghid de Instalare - Robot Telefonic AI

## 📋 Pași de Instalare

### Pasul 1: Verifică Aparatura
Accesează pagina de test pentru a verifica dacă aparatura funcționează:
```
http://localhost/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html
```

**Rezultatul așteptat:** 4/4 teste trecute ✅

### Pasul 2: Instalează Fișierele
Accesează scriptul de instalare simplificat:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/install-ai-robot-simple.php
```

**Rezultatul așteptat:** Toate fișierele și directoarele create ✅

### Pasul 3: Creează Tabelele în Baza de Date
Accesează scriptul pentru crearea tabelelor:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/run-sql-manual.php
```

**Rezultatul așteptat:** 9 tabele create cu succes ✅

### Pasul 3.1: Verifică Utilizatorii Existenți
Verifică utilizatorii existenți în tabelele WordPress:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/check-existing-users.php
```

### Pasul 3.2: Verifică Tabelele (Opțional)
Dacă întâmpini probleme, verifică statusul tabelelor:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/check-tables.php
```

### Pasul 4: Testează Robotul AI
Accesează robotul AI:
```
http://localhost/plm/wp-content/plugins/clinica/public/phone-call.html
```

## 🔧 Configurare Baza de Date

Dacă scriptul SQL nu funcționează, poți rula manual în phpMyAdmin:

1. **Deschide phpMyAdmin:** `http://localhost/phpmyadmin`
2. **Selectează baza de date:** `plm`
3. **Rulă SQL-ul din fișierul:** `tools/setup/create-ai-tables.sql`

## 📁 Structura Fișierelor Create

```
clinica/
├── public/
│   └── phone-call.html          # 🤖 Robotul AI
├── api/
│   ├── identify-patient.php     # 🔍 Identificare pacienți
│   └── webrtc-offer.php        # 📞 Procesare apeluri
├── includes/
│   └── ai-config.php           # ⚙️ Configurare AI
├── tools/
│   ├── testing/
│   │   └── test-audio-setup.html  # 🎤 Test aparatură
│   └── setup/
│       ├── install-ai-robot-simple.php  # 🚀 Instalare simplificată
│       ├── run-sql-manual.php           # 📊 SQL manual
│       └── create-ai-tables.sql         # 🗄️ Tabele baza de date
└── docs/
    ├── ROBOT_AI_FUNCTIONALITATI.md      # 📖 Funcționalități
    └── SOLUTIE_GRATUITA_WEBRTC.md       # 🔧 Documentație tehnică
```

## 🗄️ Tabele Create în Baza de Date

- `wp_clinica_ai_identifications` - Log identificări pacienți
- `wp_clinica_webrtc_calls` - Apeluri WebRTC
- `wp_clinica_webrtc_conversations` - Conversații în timpul apelurilor
- `wp_clinica_ai_conversations` - Conversații AI avansate
- `wp_clinica_ai_routing` - Decizii de routing
- `wp_clinica_ai_appointments` - Programări sugerate de AI
- `wp_clinica_ai_statistics` - Statistici robot AI
- `wp_clinica_ai_config` - Configurări AI
- `wp_clinica_ai_logs` - Log-uri AI

## 🎯 Funcționalități Robot AI

### ✅ Identificare Automată
- Caută pacienții prin CNP sau telefon
- Salutare personalizată
- Verificare în baza de date

### ✅ Conversații AI
- Procesare cereri în română
- Răspunsuri vocale naturale
- Context management

### ✅ Routing Inteligent
- **Programări** → Secretariat
- **Vaccinări** → Asistente
- **Urgențe** → Doctori
- **Cazuri complexe** → Operator uman

### ✅ Integrare Programări
- Verificare slot-uri disponibile
- Sugestii programări
- Confirmare/modificare programări

## 🚨 Troubleshooting

### Problema: Eroare la conectarea la baza de date
**Soluție:** Verifică configurările în `run-sql-manual.php`:
```php
$db_host = 'localhost';
$db_name = 'plm'; // numele bazei tale
$db_user = 'root';
$db_pass = '';
```

### Problema: Eroare PDO "unbuffered queries"
**Soluție:** Scriptul a fost corectat pentru a folosi `PDO::MYSQL_ATTR_USE_BUFFERED_QUERY`. Rulă din nou scriptul SQL.

### Problema: "Pacientul nu a fost găsit"
**Soluție:** Verifică utilizatorii existenți și adaugă CNP/telefon dacă este necesar:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/check-existing-users.php
```

### Problema: Fișierele nu se creează
**Soluție:** Verifică permisiunile de scriere în directorul plugin-ului

### Problema: Microfon nu funcționează
**Soluție:** 
1. Verifică permisiunile browser-ului
2. Testează cu pagina de test audio
3. Verifică setările Windows pentru microfon

### Problema: Eroare WordPress
**Soluție:** Folosește scriptul simplificat `install-ai-robot-simple.php`

## 📊 Testare Completă

### 1. Test Aparatură
```
http://localhost/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html
```

### 2. Test Robot AI
```
http://localhost/plm/wp-content/plugins/clinica/public/phone-call.html
```

### 3. Test API-uri
- Identificare pacient: `api/identify-patient.php`
- Procesare WebRTC: `api/webrtc-offer.php`

## 🎉 Instalare Completă!

După ce ai urmat toți pașii, robotul AI este gata să:

- ✅ Răspundă automat la apeluri
- ✅ Identifice pacienții
- ✅ Facă routing inteligent
- ✅ Proceseze programări
- ✅ Transfere la operator uman

## 📞 Suport

Dacă întâmpini probleme:
1. Verifică log-urile în `logs/`
2. Testează aparatura cu pagina de test
3. Verifică configurările bazei de date
4. Contactează suportul tehnic

---

**🤖 Robotul Telefonic AI - Gata să servească pacienții!** 