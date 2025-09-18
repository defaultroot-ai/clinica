# Demo Clinica - Sistem de Gestionare Medicală

## 🎯 Descriere

Acest demo prezintă funcționalitățile principale ale sistemului de gestionare medicală Clinica, inclusiv crearea de pacienți, autentificarea și dashboard-ul principal.

## 📁 Fișiere Demo

### 1. `demo-patient-creation.html`
**Formularul de creare pacienți cu funcționalități avansate**

#### Caracteristici:
- **Validare CNP în timp real** pentru români și străini
- **Completare automată** din CNP (data nașterii, sex, vârsta)
- **Generare automată parole** (CNP sau data nașterii)
- **Design modern și responsive**
- **Validare în timp real** pentru toate câmpurile

#### CNP-uri de test:
- `1234567890123` - Cetățean român
- `0234567890123` - Cetățean străin permanent
- `9234567890123` - Cetățean străin temporar

#### Funcționalități demo:
- Buton "Completează Date Demo" pentru testare rapidă
- Validare CNP cu feedback vizual
- Generare parole cu opțiuni configurabile
- Simulare creare pacient cu notificări

### 2. `demo-login.html`
**Pagina de autentificare cu multiple metode**

#### Caracteristici:
- **Autentificare prin username, CNP sau email**
- **Credențiale demo predefinite**
- **Selector de roluri** pentru testare
- **Autentificare rapidă cu CNP**
- **Design modern cu animații**

#### Credențiale demo:
- **Pacient**: `1234567890123` / `123456`
- **Doctor**: `dr.popescu` / `doctor123!`
- **Receptionist**: `receptionist` / `reception123!`
- **Manager**: `manager` / `manager123!`

#### Funcționalități:
- Copiere credențiale în clipboard
- Autentificare rapidă cu CNP
- Simulare proces de autentificare
- Redirect către dashboard

### 3. `demo-dashboard.html`
**Dashboard-ul principal al sistemului**

#### Caracteristici:
- **Sidebar de navigare** cu toate secțiunile
- **Cards cu statistici** în timp real
- **Quick actions** pentru operațiuni rapide
- **Activitate recentă** cu notificări
- **Design responsive** pentru mobile

#### Secțiuni principale:
- **Dashboard** - Vizualizare generală
- **Pacienți** - Gestionare pacienți
- **Programări** - Calendar și programări
- **Medici** - Gestionare personal medical
- **Dosare Medicale** - Acces la fișe
- **Rapoarte** - Statistici și rapoarte
- **Setări** - Configurare sistem

#### Funcționalități demo:
- Statistici în timp real
- Activitate recentă cu actualizări
- Quick actions cu notificări
- Buton demo pentru crearea rapidă de pacienți

## 🚀 Cum să folosești Demo-ul

### 1. Deschide fișierele în browser
```bash
# Deschide în browser
start demo-patient-creation.html
start demo-login.html
start demo-dashboard.html
```

### 2. Testează crearea de pacienți
1. Deschide `demo-patient-creation.html`
2. Folosește CNP-urile de test pentru validare
3. Testează completarea automată din CNP
4. Experimentează cu generarea parolelor
5. Folosește butonul "Completează Date Demo"

### 3. Testează autentificarea
1. Deschide `demo-login.html`
2. Selectează un rol din butoanele de sus
3. Folosește credențialele predefinite
4. Testează autentificarea rapidă cu CNP
5. Experimentează cu diferite roluri

### 4. Explorează dashboard-ul
1. Deschide `demo-dashboard.html`
2. Navighează prin sidebar-ul de meniu
3. Interacționează cu cards-urile de statistici
4. Testează quick actions
5. Folosește butonul demo pentru crearea de pacienți

## 🎨 Design și UX

### Caracteristici de design:
- **Design modern** cu gradient-uri și umbre
- **Animații fluide** pentru interacțiuni
- **Feedback vizual** pentru toate acțiunile
- **Responsive design** pentru toate dispozitivele
- **Iconuri FontAwesome** pentru claritate

### Paleta de culori:
- **Primary**: #3498db (Albastru)
- **Success**: #27ae60 (Verde)
- **Warning**: #f39c12 (Portocaliu)
- **Danger**: #e74c3c (Roșu)
- **Dark**: #2c3e50 (Gri închis)

## 🔧 Funcționalități Tehnice

### Validare CNP:
- **Algoritm matematic** pentru validare
- **Suport pentru cetățeni străini**
- **Validare în timp real** cu feedback
- **Extragere automată** informații din CNP

### Generare parole:
- **Opțiune 1**: Primele 6 cifre CNP
- **Opțiune 2**: Data nașterii (dd.mm.yyyy)
- **Configurabil** de către personal medical

### Autentificare:
- **Multiple metode**: username, CNP, email
- **Credențiale demo** pentru testare
- **Simulare proces** de autentificare
- **Redirect inteligent** după login

## 📱 Responsive Design

### Breakpoints:
- **Desktop**: > 768px
- **Tablet**: 768px - 1024px
- **Mobile**: < 768px

### Adaptări mobile:
- **Sidebar colapsabil** pe mobile
- **Grid responsive** pentru cards
- **Quick actions** adaptate pentru touch
- **Formulare optimizate** pentru mobile

## 🎯 Scenarii de Testare

### 1. Creare pacient nou:
1. Completează CNP-ul
2. Verifică validarea în timp real
3. Observă completarea automată
4. Generează parola
5. Completează restul informațiilor
6. Submit formularul

### 2. Autentificare cu roluri diferite:
1. Selectează rolul "Doctor"
2. Folosește credențialele predefinite
3. Testează autentificarea
4. Repetă pentru alte roluri

### 3. Navigare în dashboard:
1. Explorează sidebar-ul
2. Interacționează cu cards-urile
3. Testează quick actions
4. Verifică activitatea recentă

## 🔒 Securitate Demo

### Caracteristici de securitate:
- **Validare strictă** pentru toate input-urile
- **Sanitizare** date înainte de procesare
- **Permisiuni granulare** pentru roluri
- **Audit trail** pentru toate operațiunile

### Limitări demo:
- **Nu salvează date** în baza de date
- **Simulează** procesele de backend
- **Credențiale hardcodate** pentru testare
- **Nu include** funcționalități de producție

## 📞 Suport

Pentru întrebări sau probleme cu demo-ul:
- Verifică console-ul browser-ului pentru erori
- Asigură-te că toate fișierele sunt în același folder
- Testează cu diferite browser-e (Chrome, Firefox, Safari)

---

**Notă**: Acest demo prezintă interfața și funcționalitățile de bază. Pentru implementarea completă, consultă documentația tehnică din folderul `docs/`. 