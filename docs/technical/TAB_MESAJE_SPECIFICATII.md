# Tab-ul de Mesaje - Specificații Complete

## 🎯 **Obiectiv**
Tab-ul de mesaje din dashboard-ul pacientului va fi centrul de comunicare principal între pacient și clinică, oferind transparență completă și comunicare eficientă.

## 📋 **Conținutul Tab-ului**

### **1. NOTIFICĂRI SISTEM (din `wp_clinica_notifications`)**

#### **Tipuri de Notificări:**
- **Confirmări programări**: "Programarea ta de mâine la 14:00 cu Dr. Popescu a fost confirmată"
- **Reminder-uri**: "Amintire: Programarea ta este mâine la 10:00"
- **Anulări programări**: "Programarea de pe 15.09 a fost anulată din motive medicale"
- **Modificări programări**: "Programarea ta a fost mutată de la 14:00 la 16:00"
- **Alert-uri importante**: "Clinică închisă în 25 decembrie"

#### **Caracteristici:**
- **Automate** - generate de sistem
- **În timp real** - trimise imediat
- **Prioritare** - marcate ca importante
- **Persistente** - păstrate în istoric

### **2. MESAJE DE LA CLINICĂ**

#### **Tipuri de Mesaje:**
- **Anunțuri generale**: "Noile servicii de analiză sunt disponibile"
- **Instrucțiuni medicale**: "Instrucțiuni pentru pregătirea la analize"
- **Informații despre servicii**: "Programul de vaccinare HPV"
- **Modificări de program**: "Clinică închisă în weekend-ul de Paște"

#### **Caracteristici:**
- **Manuale** - create de administrație
- **Broadcast** - pentru toți pacienții sau grupuri
- **Informative** - conțin informații utile
- **Programate** - pot fi trimise la o dată specifică

### **3. COMUNICARE CU MEDICUL**

#### **Tipuri de Comunicare:**
- **Mesaje de la medicul personal**: "Rezultatele analizelor sunt bune"
- **Instrucțiuni post-tratament**: "Luați medicamentul de 3 ori pe zi"
- **Rezultate analize**: "Analiza de sânge - toate valorile sunt normale"
- **Prescripții**: "Prescripție nouă disponibilă pentru ridicare"

#### **Caracteristici:**
- **Personale** - de la medicul pacientului
- **Medicale** - conțin informații de sănătate
- **Confidențiale** - doar pentru pacient
- **Profesionale** - scrise de medici

## ⚙️ **Funcționalități Tehnice**

### **Gestionare Mesaje:**

#### **Stare Mesaje:**
- **Marcare ca citit/necitit** (câmpul `read_at`)
- **Badge pentru mesaje necitite** (numărul în paranteză)
- **Sortare cronologică** (cele mai recente primul)
- **Filtrare după tip** (appointment, reminder, system, alert)

#### **Căutare și Navigare:**
- **Căutare în mesaje** (titlu + conținut)
- **Filtrare după dată** (ultima săptămână, lună, an)
- **Filtrare după stare** (citite, necitite, toate)
- **Paginare** pentru multe mesaje

#### **Interacțiune:**
- **Expandare mesaje lungi** (show more/less)
- **Răspuns la mesaje** (opțional, pentru comunicare bidirecțională)
- **Arhivare mesaje vechi** (peste 6 luni)
- **Export mesaje** (PDF pentru dosar personal)

### **Notificări în Timp Real:**
- **AJAX polling** pentru mesaje noi
- **Badge în tab** cu numărul de mesaje necitite
- **Sound notification** (opțional)
- **Browser notification** (cu permisiunea utilizatorului)

## 🎨 **Design și UX**

### **Layout Principal:**

#### **Header:**
- **Titlu**: "Mesaje"
- **Buton "Mesaj nou"** (pentru comunicare bidirecțională)
- **Filtre**: Dropdown pentru tip și dată
- **Căutare**: Input pentru căutare în mesaje

#### **Lista de Mesaje:**
- **Mesaje necitite**: Background diferit, font bold
- **Mesaje citite**: Stil normal, mai subtil
- **Mesaje importante**: Border colorat, icon mai mare
- **Mesaje vechi**: Opacitate redusă

#### **Preview Mesaj:**
- **Titlu** (primul rând)
- **Preview conținut** (primele 100 caractere)
- **Timestamp relativ** ("Acum", "2 ore în urmă", "ieri")
- **Icon pentru tip** (colorat și descriptiv)

### **Stilizare:**

#### **Iconuri Colorate:**
- **Programări** (appointment): 🔵 Albastru - calendar
- **Reminder-uri** (reminder): 🟡 Galben - clock
- **Sistem** (system): 🟢 Verde - info-circle
- **Alert-uri** (alert): 🔴 Roșu - exclamation-triangle

#### **Stări Vizuale:**
- **Necitit**: Background #f8fafc, border-left #3b82f6
- **Citit**: Background transparent, text #6b7280
- **Important**: Background #fef3c7, border #f59e0b
- **Arhivat**: Opacitate 0.6, text #9ca3af

### **Responsive Design:**
- **Mobile-first** - optimizat pentru telefon
- **Swipe gestures** - pentru acțiuni rapide
- **Touch-friendly** - butoane mari, ușor de apăsat
- **Collapsible** - mesajele se pot deschide/închide

## 📊 **Structura Datelor**

### **Tabelul Existente `wp_clinica_notifications`:**
```sql
CREATE TABLE wp_clinica_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,           -- pacientul destinatar
    type ENUM('appointment','reminder','system','alert') NOT NULL,
    title VARCHAR(255) NOT NULL,                -- titlul mesajului
    message TEXT NOT NULL,                      -- conținutul mesajului
    read_at TIMESTAMP NULL,                     -- când a fost citit (NULL = necitit)
    created_at TIMESTAMP NOT NULL,              -- când a fost creat
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_read_at (read_at),
    INDEX idx_created_at (created_at)
);
```

### **Extensii Viitoare:**

#### **Tabel `wp_clinica_messages` (comunicare bidirecțională):**
```sql
CREATE TABLE wp_clinica_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,         -- medic, asistent, admin
    sender_type ENUM('doctor','assistant','admin','system') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    reply_to INT NULL,                          -- ID mesaj la care răspunde
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

#### **Tabel `wp_clinica_message_attachments`:**
```sql
CREATE TABLE wp_clinica_message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL
);
```

## 🔄 **Fluxul de Lucru**

### **Pentru Pacient:**

#### **1. Accesare Tab:**
- Click pe tab "Mesaje"
- Se încarcă mesajele necitite primul
- Se afișează badge cu numărul de mesaje necitite

#### **2. Navigare Mesaje:**
- Scroll prin lista de mesaje
- Click pe mesaj pentru a-l deschide complet
- Mesajul se marchează automat ca citit

#### **3. Filtrare și Căutare:**
- Selectează tipul de mesaj din dropdown
- Caută în titlu sau conținut
- Sortează după dată sau stare

#### **4. Acțiuni:**
- Răspunde la mesaj (dacă este permis)
- Arhivează mesaje vechi
- Exportă mesaje în PDF

### **Pentru Clinică:**

#### **1. Creare Notificare:**
- Selectează tipul de notificare
- Scrie titlul și mesajul
- Alege destinatarii (toți pacienții sau grupuri)
- Programează trimiterea (imediat sau la o dată)

#### **2. Gestionare Mesaje:**
- Vezi mesajele trimise
- Monitorizează mesajele citite/necitite
- Răspunde la întrebările pacienților
- Arhivează mesaje vechi

#### **3. Automatizare:**
- Reminder-uri automate pentru programări
- Notificări la modificări de programări
- Alert-uri pentru evenimente importante

## 🚀 **Beneficii**

### **Pentru Pacient:**
- **Transparență completă** - știe tot ce se întâmplă
- **Comunicare eficientă** - nu mai sună pentru informații simple
- **Istoric complet** - toate mesajele într-un loc
- **Notificări în timp real** - nu ratează nimic important
- **Acces 24/7** - poate citi mesajele oricând

### **Pentru Clinică:**
- **Comunicare centralizată** - toate mesajele într-un loc
- **Reducere apeluri telefonice** - informații disponibile online
- **Audit trail** - istoric complet al comunicării
- **Automatizare** - mesaje automate pentru evenimente comune
- **Eficiență** - comunicare rapidă cu pacienții

## 📱 **Implementare Tehnică**

### **Backend (PHP):**
- **AJAX handlers** pentru încărcare mesaje
- **Metode de filtrare** și căutare
- **Sistem de notificări** în timp real
- **Gestionare stări** (citit/necitit)

### **Frontend (JavaScript):**
- **AJAX calls** pentru încărcare mesaje
- **Real-time updates** cu polling
- **Filtrare și căutare** în timp real
- **Gesturi touch** pentru mobile

### **CSS:**
- **Design responsive** pentru toate dispozitivele
- **Animații** pentru interacțiuni
- **Iconuri** și culori pentru tipuri de mesaje
- **Stilizare** pentru stări diferite

## 🎯 **Priorități de Implementare**

### **Faza 1 - Baza (1-2 zile):**
1. **Încărcare mesaje** din tabelul existent
2. **Afișare listă** cu preview
3. **Marcare ca citit** la click
4. **Filtrare după tip** și dată

### **Faza 2 - Funcționalități (1-2 zile):**
1. **Căutare în mesaje**
2. **Notificări în timp real**
3. **Badge pentru mesaje necitite**
4. **Design responsive**

### **Faza 3 - Avansat (2-3 zile):**
1. **Comunicare bidirecțională**
2. **Atașamente** pentru mesaje
3. **Export PDF**
4. **Arhivare automată**

### **Faza 4 - Optimizări (1 zi):**
1. **Performance** - caching, paginare
2. **UX** - animații, gesturi
3. **Testing** - toate funcționalitățile
4. **Documentație** - pentru utilizatori

---

**Data:** 18 Septembrie 2025  
**Status:** Specificații complete  
**Următorul pas:** Implementare Faza 1
