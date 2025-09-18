# 🏥 GHIDARE COMPLETĂ - SETAREA PROGRAMULUI CU SERVICII ȘI SLOTURI

## 📋 **PASII PENTRU CONFIGURAREA SISTEMULUI DE PROGRAMĂRI**

### **ETAPA 1: CONFIGURAREA SERVICIILOR** 🩺

#### **1.1 Crearea Serviciilor**
```
📍 LOCAȚIE: WordPress Admin → Clinica → Servicii & Programare → Tab "Servicii"

PASII:
1. Click "Adaugă Serviciu"
2. Completează:
   - Nume serviciu (ex: "Consultatie boala acuta")
   - Durata în minute (ex: 15, 30, 45)
   - Status: Activ/Inactiv
3. Click "Salvează Serviciu"
```

#### **1.2 Structura Serviciilor**
```
📊 TABELA: wp_clinica_services
├── id (INT) - ID unic
├── name (VARCHAR) - Numele serviciului
├── duration (INT) - Durata în minute
├── active (TINYINT) - Status activ/inactiv
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

---

### **ETAPA 2: ALOAREA DOCTORILOR LA SERVICII** 👨‍⚕️

#### **2.1 Alocarea Doctorilor**
```
📍 LOCAȚIE: WordPress Admin → Clinica → Servicii & Programare → Tab "Alocări Doctori"

PASII:
1. Selectează serviciul din dropdown
2. Vezi lista de doctori disponibili
3. Bifează doctorii care pot oferi serviciul
4. Click "Salvează Alocări"
```

#### **2.2 Structura Alocărilor**
```
📊 TABELA: wp_clinica_doctor_services
├── id (INT) - ID unic
├── doctor_id (BIGINT) - ID doctor
├── service_id (INT) - ID serviciu
├── active (TINYINT) - Status alocare
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

---

### **ETAPA 3: CONFIGURAREA TIMESLOTS-URILOR** ⏰

#### **3.1 Crearea Timeslots-urilor**
```
📍 LOCAȚIE: WordPress Admin → Clinica → Servicii & Programare → Tab "Timeslots"

PASII:
1. Selectează doctorul din dropdown
2. Selectează serviciul din dropdown
3. Alege ziua din săptămână (1=Luni, 7=Duminică)
4. Setează ora de început (ex: 10:00)
5. Setează ora de sfârșit (ex: 18:00)
6. Setează durata slotului (ex: 30 minute)
7. Click "Adaugă Timeslot"
```

#### **3.2 Structura Timeslots-urilor**
```
📊 TABELA: wp_clinica_doctor_timeslots
├── id (INT) - ID unic
├── doctor_id (INT) - ID doctor
├── service_id (INT) - ID serviciu
├── day_of_week (TINYINT) - Ziua săptămânii (1-7)
├── start_time (TIME) - Ora de început
├── end_time (TIME) - Ora de sfârșit
├── slot_duration (INT) - Durata slotului în minute
├── is_active (BOOLEAN) - Status activ/inactiv
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

---

## 🔄 **LOGICA SISTEMULUI DE PROGRAMĂRI**

### **FLUXUL DE DATE** 📊

```
1. SERVICII
   ↓
2. ALOAREA DOCTORILOR LA SERVICII
   ↓
3. CONFIGURAREA TIMESLOTS-URILOR
   ↓
4. GENERAREA ZILELOR DISPONIBILE
   ↓
5. GENERAREA SLOTURILOR DISPONIBILE
   ↓
6. AFIȘAREA ÎN CALENDAR
```

### **ALGORITMUL DE GENERARE A ZILELOR** 🗓️

```
PENTRU FIECARE ZI ÎN URMĂTOARELE 90 DE ZILE:
├── 1. Verifică dacă există timeslots specifice pentru serviciu
│   ├── DA: Afișează doar zilele cu timeslots
│   └── NU: Nu afișa nicio zi
├── 2. Verifică sărbătorile legale românești
├── 3. Verifică concediile doctorului
├── 4. Verifică programul general de lucru (fallback)
└── 5. Calculează numărul de programări existente
```

### **ALGORITMUL DE GENERARE A SLOTURILOR** ⏱️

```
PENTRU O ZI SELECTATĂ:
├── 1. Verifică dacă există timeslots specifice
│   ├── DA: Folosește timeslots-urile specifice
│   └── NU: Folosește programul general
├── 2. Generează sloturi pe baza:
│   ├── Ora de început
│   ├── Ora de sfârșit
│   ├── Durata slotului
│   └── Intervalul între sloturi
├── 3. Exclude sloturile ocupate
├── 4. Exclude pauzele
└── 5. Returnează sloturile disponibile
```

---

## 🎯 **EXEMPLE PRACTICE DE CONFIGURARE**

### **EXEMPLU 1: Consultatie boala acuta** 🩺
```
SERVIUL: Consultatie boala acuta
├── Durata: 15 minute
├── Doctor: Coserea Andreea
├── Zile: Miercuri
├── Ore: 10:00 - 18:00
└── Sloturi: 10:00, 10:15, 10:30, ..., 17:45
```

### **EXEMPLU 2: Vaccinare HPV** 💉
```
SERVIUL: Vaccinare HPV
├── Durata: 30 minute
├── Doctor: Coserea Andreea
├── Zile: Joi, Vineri
├── Ore: 10:00 - 18:00
└── Sloturi: 10:00, 10:30, 11:00, ..., 17:30
```

---

## ⚠️ **REGULI ȘI CONSTRÂNGERI**

### **REGULI PENTRU TIMESLOTS** 📋
1. **Obligatoriu**: Doctorul trebuie să fie alocat la serviciu
2. **Obligatoriu**: Serviciul trebuie să fie activ
3. **Obligatoriu**: Timeslot-ul trebuie să fie activ
4. **Validare**: Ora de început < Ora de sfârșit
5. **Validare**: Durata slotului > 0
6. **Unicitate**: Nu pot exista timeslots suprapuse pentru același doctor/serviciu/zi

### **REGULI PENTRU CALENDAR** 📅
1. **Prioritate**: Timeslots-urile specifice au prioritate asupra programului general
2. **Filtrare**: Doar zilele cu timeslots specifice sunt afișate
3. **Excludere**: Sărbătorile legale sunt excluse automat
4. **Excludere**: Concediile doctorului sunt excluse
5. **Limitare**: Numărul maxim de programări per zi per doctor

---

## 🔧 **FUNCȚII CHEIE DIN COD**

### **1. Generarea Zilelor Disponibile**
```php
// Fișier: class-clinica-patient-dashboard.php
// Funcția: ajax_get_doctor_availability_days()

LOGICA:
1. Verifică timeslots-urile specifice pentru serviciu
2. Dacă există: afișează doar zilele cu timeslots
3. Dacă nu există: nu afișa nicio zi
4. Exclude sărbătorile și concediile
5. Calculează programările existente
```

### **2. Generarea Sloturilor Disponibile**
```php
// Fișier: class-clinica-patient-dashboard.php
// Funcția: ajax_get_doctor_slots()

LOGICA:
1. Verifică timeslots-urile specifice pentru zi
2. Dacă există: folosește timeslots-urile specifice
3. Dacă nu există: folosește programul general
4. Generează sloturi pe baza duratei și intervalului
5. Exclude sloturile ocupate și pauzele
```

### **3. Gestionarea Timeslots-urilor**
```php
// Fișier: class-clinica-services-manager.php
// Funcții: add_timeslot(), update_timeslot(), delete_timeslot()

LOGICA:
1. Verifică dacă doctorul este alocat la serviciu
2. Validează datele timeslot-ului
3. Verifică unicitatea timeslot-ului
4. Salvează/actualizează/șterge timeslot-ul
```

---

## 🚀 **WORKFLOW COMPLET DE CONFIGURARE**

### **PASUL 1: Pregătirea** 🛠️
```
1. Accesează WordPress Admin
2. Navighează la Clinica → Servicii & Programare
3. Verifică că toate tabelele sunt create
4. Verifică că doctorii au rolurile corecte
```

### **PASUL 2: Servicii** 🩺
```
1. Creează toate serviciile necesare
2. Setează durata corectă pentru fiecare serviciu
3. Activează serviciile care vor fi folosite
4. Testează că serviciile apar în dropdown-uri
```

### **PASUL 3: Alocări** 👨‍⚕️
```
1. Pentru fiecare serviciu, alocă doctorii
2. Verifică că alocările sunt salvate corect
3. Testează că doctorii apar în listele de servicii
4. Verifică că alocările inactive nu apar
```

### **PASUL 4: Timeslots** ⏰
```
1. Pentru fiecare doctor-serviciu, creează timeslots-uri
2. Setează zilele și orele corecte
3. Setează durata sloturilor
4. Testează că timeslots-urile sunt generate corect
```

### **PASUL 5: Testare** 🧪
```
1. Testează calendarul pentru fiecare doctor-serviciu
2. Verifică că zilele afișate sunt corecte
3. Verifică că sloturile se generează corect
4. Testează programarea unei programări
```

---

## 🎨 **INTERFAȚA UTILIZATORULUI**

### **DASHBOARD PRINCIPAL** 📊
```
┌─────────────────────────────────────────────────────────┐
│  🏥 DASHBOARD SERVICII & PROGRAMARE                    │
├─────────────────────────────────────────────────────────┤
│  📊 STATISTICI                                         │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐      │
│  │ Servicii│ │ Doctori │ │Timeslots│ │Alocări  │      │
│  │    3    │ │    4    │ │   104   │ │   12    │      │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘      │
├─────────────────────────────────────────────────────────┤
│  📑 TAB-URI                                            │
│  [Servicii] [Alocări Doctori] [Timeslots] [Program]    │
└─────────────────────────────────────────────────────────┘
```

### **TAB SERVICII** 🩺
```
┌─────────────────────────────────────────────────────────┐
│  🩺 GESTIONARE SERVICII                                │
├─────────────────────────────────────────────────────────┤
│  [+ Adaugă Serviciu]                                   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ Consultatie boala acuta                         │   │
│  │ Durata: 15 min | Status: ✅ Activ              │   │
│  │ [Editează] [Șterge] [Timeslots]                │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ Vaccinare HPV                                   │   │
│  │ Durata: 30 min | Status: ✅ Activ              │   │
│  │ [Editează] [Șterge] [Timeslots]                │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### **TAB TIMESLOTS** ⏰
```
┌─────────────────────────────────────────────────────────┐
│  ⏰ GESTIONARE TIMESLOTS                               │
├─────────────────────────────────────────────────────────┤
│  Doctor: [Coserea Andreea ▼]                          │
│  Serviciu: [Vaccinare HPV ▼]                          │
│                                                         │
│  📅 ZILELE SĂPTĂMÂNII                                  │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐│
│  │ L   │ │ M   │ │ Mi  │ │ J   │ │ V   │ │ S   │ │ D   ││
│  │     │ │     │ │     │ │ ✅  │ │ ✅  │ │     │ │     ││
│  └─────┘ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘│
│                                                         │
│  🕐 TIMESLOTS PENTRU JOI:                              │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 10:00 - 10:30 | 10:30 - 11:00 | 11:00 - 11:30 │   │
│  │ 11:30 - 12:00 | 12:00 - 12:30 | 12:30 - 13:00 │   │
│  │ 13:00 - 13:30 | 13:30 - 14:00 | 14:00 - 14:30 │   │
│  │ 14:30 - 15:00 | 15:00 - 15:30 | 15:30 - 16:00 │   │
│  │ 16:00 - 16:30 | 16:30 - 17:00 | 17:00 - 17:30 │   │
│  │ 17:30 - 18:00                                   │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

---

## 🔍 **DEBUGGING ȘI TROUBLESHOOTING**

### **PROBLEME COMUNE** ⚠️

#### **1. Nu se afișează zile în calendar**
```
CAUZE POSIBILE:
- Doctorul nu este alocat la serviciu
- Nu există timeslots configurate pentru serviciu
- Timeslots-urile sunt inactive
- Serviciul este inactiv

SOLUȚII:
1. Verifică alocarea doctorului la serviciu
2. Creează timeslots-uri pentru serviciu
3. Activează timeslots-urile
4. Activează serviciul
```

#### **2. Nu se generează sloturi**
```
CAUZE POSIBILE:
- Nu există timeslots pentru ziua selectată
- Timeslots-urile sunt inactive
- Ora de început >= Ora de sfârșit
- Durata slotului este 0

SOLUȚII:
1. Verifică timeslots-urile pentru ziua selectată
2. Activează timeslots-urile
3. Corectează orele de început/sfârșit
4. Setează durata slotului > 0
```

#### **3. Sloturile nu respectă durata serviciului**
```
CAUZE POSIBILE:
- Durata slotului nu corespunde cu durata serviciului
- Intervalul între sloturi este prea mic

SOLUȚII:
1. Setează durata slotului = durata serviciului
2. Setează intervalul între sloturi >= durata serviciului
3. Verifică setările globale de programări
```

---

## 📈 **MONITORIZARE ȘI STATISTICI**

### **METRICE IMPORTANTE** 📊
```
1. Numărul total de servicii active
2. Numărul total de doctori activi
3. Numărul total de timeslots configurate
4. Numărul total de alocări active
5. Numărul de programări pe zi
6. Utilizarea sloturilor per doctor
7. Serviciile cele mai populare
8. Zilele cu cea mai mare încărcare
```

### **RAPOARTE DISPONIBILE** 📋
```
1. Raport servicii per doctor
2. Raport timeslots per serviciu
3. Raport utilizare calendar
4. Raport programări per lună
5. Raport doctori cei mai ocupați
6. Raport servicii cele mai solicitate
```

---

## 🎯 **CONCLUZIE**

Sistemul de programări Clinica este un sistem complex cu următoarele componente principale:

1. **SERVICII** - Definițiile serviciilor medicale
2. **ALOCĂRI** - Legătura între doctori și servicii
3. **TIMESLOTS** - Programul specific per doctor-serviciu
4. **CALENDAR** - Interfața pentru programări
5. **SLOTURI** - Generarea automată a intervalelor disponibile

**ORDINEA DE CONFIGURARE ESTE CRUCIALĂ:**
1. Servicii → 2. Alocări → 3. Timeslots → 4. Testare

**REGULA DE AUR:** Fără timeslots specifice, nu se afișează zile în calendar!
