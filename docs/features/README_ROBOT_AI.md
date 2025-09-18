# 🤖 Robot Telefonic AI - Clinică Medicală

## Prezentare Generală

Robotul Telefonic AI este o soluție completă pentru automatizarea apelurilor în clinica medicală, folosind tehnologii WebRTC și AI pentru a oferi o experiență profesională pacienților.

## 🚀 Caracteristici Principale

### ✅ Funcționalități Complete
- **Identificare automată** a pacienților prin CNP sau telefon
- **Conversații AI naturale** în limba română
- **Routing inteligent** către departamentele corespunzătoare
- **Integrare completă** cu sistemul de programări
- **Transfer automat** la operator uman pentru cazuri complexe
- **Dashboard complet** pentru management și analytics

### 🎯 Beneficii
- **Disponibilitate 24/7** pentru pacienți
- **Reducerea încărcării** pe personal
- **Experiență îmbunătățită** pentru pacienți
- **Costuri zero** - folosește doar browser-ul
- **Compatibil cu orice hosting** - inclusiv Romarg VPS

## 📋 Cerințe Sistem

### Aparatură Necesară
- **PC/Laptop** cu microfon și difuzoare
- **Browser modern** (Chrome, Firefox, Safari, Edge)
- **Conectivitate internet** stabilă
- **HTTPS** obligatoriu pentru WebRTC

### Cerințe Software
- **WordPress** cu plugin-ul Clinica
- **PHP 7.4+**
- **MySQL 5.7+**
- **Suport pentru WebRTC** în browser

## 🛠️ Instalare

### Pasul 1: Verifică Aparatura
Accesează pagina de test pentru a verifica dacă aparatura funcționează:
```
http://localhost/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html
```

### Pasul 2: Instalează Robotul AI
Accesează scriptul de instalare:
```
http://localhost/plm/wp-content/plugins/clinica/tools/setup/install-ai-robot.php
```

### Pasul 3: Testează Robotul
Accesează robotul AI:
```
http://localhost/plm/wp-content/plugins/clinica/public/phone-call.html
```

## 📁 Structura Fișierelor

```
clinica/
├── public/
│   └── phone-call.html          # Pagina principală robot AI
├── api/
│   ├── identify-patient.php     # API identificare pacienți
│   └── webrtc-offer.php        # API procesare WebRTC
├── includes/
│   └── ai-config.php           # Configurare AI
├── tools/
│   ├── testing/
│   │   └── test-audio-setup.html  # Test aparatură
│   └── setup/
│       ├── install-ai-robot.php    # Script instalare
│       └── create-ai-tables.sql    # SQL tabele
└── docs/
    ├── ROBOT_AI_FUNCTIONALITATI.md  # Funcționalități detaliate
    └── SOLUTIE_GRATUITA_WEBRTC.md  # Documentație tehnică
```

## 🗄️ Baze de Date

### Tabele Principale
- `wp_clinica_ai_identifications` - Log identificări pacienți
- `wp_clinica_webrtc_calls` - Apeluri WebRTC
- `wp_clinica_webrtc_conversations` - Conversații în timpul apelurilor
- `wp_clinica_ai_conversations` - Conversații AI avansate
- `wp_clinica_ai_routing` - Decizii de routing
- `wp_clinica_ai_appointments` - Programări sugerate de AI
- `wp_clinica_ai_statistics` - Statistici robot AI
- `wp_clinica_ai_config` - Configurări AI
- `wp_clinica_ai_logs` - Log-uri AI

## 🔧 Configurare

### Configurări Principale
- **AI Enabled**: Activează/dezactivează robotul
- **Working Hours**: Programul de funcționare (8:30-19:30)
- **Language**: Limba principală (română)
- **Confidence Threshold**: Prag de încredere pentru AI
- **Emergency Transfer**: Transfer automat pentru cazuri urgente

### Personalizare
- **Greeting Messages**: Mesaje de salutare personalizate
- **Routing Rules**: Reguli pentru directionarea apelurilor
- **Appointment Slots**: Slot-uri disponibile pentru programări
- **Transfer Options**: Opțiuni de transfer la operator uman

## 📊 Dashboard și Analytics

### Statistici în Timp Real
- **Apeluri astăzi**: Numărul de apeluri procesate
- **Rata de succes**: Procentul de identificări reușite
- **Transferuri la uman**: Numărul de transferuri la operator
- **Durata medie apel**: Timpul mediu de conversație

### Rapoarte
- **Istoric conversații**: Toate conversațiile cu AI
- **Identificări eșuate**: Pacienți care nu au fost găsiți
- **Routing decisions**: Deciziile de directionare a apelurilor
- **Performance metrics**: Metrici de performanță

## 🔄 Fluxul de Lucru

### 1. Identificare Pacient
```
Pacientul accesează pagina → Introduce CNP/telefon → AI identifică pacientul
```

### 2. Conversație AI
```
AI salută pacientul → Procesează cererea → Generează răspuns vocal
```

### 3. Routing Inteligent
```
AI analizează intenția → Directionează către departamentul corect
```

### 4. Transfer la Uman (dacă necesar)
```
AI detectează caz complex → Transfer la operator uman → Continuă conversația
```

## 🎯 Tipuri de Cereri Procesate

### Programări
- **Programări noi**: Sugestii slot-uri disponibile
- **Modificări**: Actualizare programări existente
- **Anulări**: Procesare anulări programări

### Vaccinări
- **Informații**: Detalii despre programul de vaccinări
- **Programări**: Slot-uri pentru vaccinări
- **Tipuri vaccin**: Recomandări pentru tipul de vaccin

### Consultații
- **Consultații generale**: Programări cu medicul de familie
- **Consultații specializate**: Programări cu specialiști
- **Urgențe**: Transfer la secția de urgență

### Informații Generale
- **Program clinică**: Orar de funcționare
- **Contact**: Informații de contact
- **Servicii**: Lista serviciilor oferite

## 🔒 Securitate și Conformitate

### Protecția Datelor
- **GDPR Compliance**: Conformitate cu reglementările europene
- **Date mascate**: Informații sensibile sunt mascate în răspunsuri
- **Retention Policy**: Politică de păstrare a datelor (30 zile)
- **Audit Trail**: Log complet al tuturor acțiunilor

### Securitate Tehnică
- **HTTPS obligatoriu**: Criptare pentru toate comunicările
- **CORS configurat**: Acces controlat pentru API-uri
- **Validare input**: Verificare strictă a datelor de intrare
- **Rate limiting**: Protecție împotriva abuzurilor

## 🚨 Troubleshooting

### Probleme Comune

#### Microfon nu funcționează
1. Verifică permisiunile browser-ului
2. Testează cu pagina de test audio
3. Verifică setările Windows pentru microfon
4. Încearcă cu căști cu microfon

#### Eroare de conexiune
1. Verifică dacă HTTPS este activat
2. Testează conectivitatea internet
3. Verifică firewall-ul
4. Contactează administratorul

#### Pacientul nu este găsit
1. Verifică CNP-ul sau numărul de telefon
2. Asigură-te că pacientul este înregistrat în sistem
3. Contactează secretariatul pentru asistență

### Log-uri și Debugging
- **Log-uri AI**: `/wp-content/plugins/clinica/logs/`
- **Log-uri WordPress**: Dashboard → Tools → Site Health
- **Console browser**: F12 → Console pentru erori JavaScript

## 📞 Suport

### Contact
- **Email**: support@clinica.ro
- **Telefon**: 0722-XXX-XXX
- **Documentație**: `/docs/` în plugin

### Resurse
- **Test Aparatură**: [Test Audio Setup](tools/testing/test-audio-setup.html)
- **Robot AI**: [Phone Call Interface](public/phone-call.html)
- **Instalare**: [Install Script](tools/setup/install-ai-robot.php)

## 🔄 Actualizări

### Versiunea Curentă
- **v1.0.0**: Versiunea inițială cu funcționalități de bază
- **Compatibilitate**: WordPress 5.0+, PHP 7.4+
- **Browser-uri**: Chrome 60+, Firefox 55+, Safari 11+, Edge 79+

### Roadmap
- **v1.1.0**: Integrare OpenAI pentru conversații mai naturale
- **v1.2.0**: Suport pentru multiple limbi
- **v1.3.0**: Integrare cu sisteme externe de programări
- **v2.0.0**: Video calls și screen sharing

## 📄 Licență

Acest robot AI este parte din plugin-ul Clinica și este sub aceeași licență ca restul sistemului.

---

**🤖 Robotul Telefonic AI - O soluție completă pentru automatizarea apelurilor în clinica medicală!** 