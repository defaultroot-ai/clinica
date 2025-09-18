# 🏠 Ghid de Gestionare a Familiilor - Plugin Clinica

## 📋 Prezentare Generală

Sistemul de gestionare a familiilor permite organizarea pacienților în familii pentru o administrare mai eficientă și o experiență mai bună pentru utilizatori.

### 🎯 Beneficii

- **Organizare eficientă**: Pacienții din aceeași familie sunt grupați logic
- **Gestionare simplificată**: Acces rapid la toți membrii unei familii
- **Rapoarte îmbunătățite**: Statistici pe familii, nu doar pe pacienți individuali
- **Experiență utilizator**: Interfață intuitivă pentru gestionarea familiilor

## 🚨 Corectări Recente Implementate (2025)

### Problema Identificată
Sistemul întâmpina eroarea "Capul familiei nu a fost găsit" la crearea familiilor din cauza unei probleme logice în fluxul de procesare.

### Soluțiile Implementate

#### 1. Corectarea Fluxului de Creare a Familiilor
- **Problema**: Datele de familie erau procesate înainte de salvarea pacientului în baza de date
- **Soluția**: Implementarea unui flux secvențial corect:
  1. Crearea familiei cu ID unic
  2. Salvarea pacientului în baza de date
  3. Actualizarea datelor de familie după salvarea pacientului

#### 2. Îmbunătățirea Metodei `create_family()`
- Verificarea existenței pacientului înainte de actualizare
- Adăugarea metodei `update_family_head()` pentru actualizarea capului familiei
- Gestionarea corectă a cazurilor când pacientul nu există încă

#### 3. Validări JavaScript Îmbunătățite
- Validarea câmpurilor de familie înainte de trimiterea formularului
- Verificarea completării numelui familiei și rolului
- Validarea selecției familiilor existente

#### 4. Corectarea Creării Automate a Familiilor
- Repararea metodei `create_families_auto()` pentru a extrage corect `family_id`
- Îmbunătățirea previzualizării familiilor cu email-uri și nume complete
- Gestionarea corectă a erorilor în procesul de creare automată

## 🏗️ Structura Sistemului

### Câmpuri în Baza de Date

Tabelul `wp_clinica_patients` a fost extins cu următoarele câmpuri:

- **`family_id`** (INT): ID-ul unic al familiei
- **`family_role`** (ENUM): Rolul în familie (head, spouse, child, parent, sibling)
- **`family_head_id`** (INT): ID-ul capului familiei
- **`family_name`** (VARCHAR): Numele familiei

### Roluri în Familie

1. **`head`** - Cap de familie (mama, tata, etc.)
2. **`spouse`** - Soț/Soție
3. **`child`** - Copil
4. **`parent`** - Părinte (bunici, etc.)
5. **`sibling`** - Frate/Soră

## 🚀 Instalare și Configurare

### Pasul 1: Actualizarea Bazei de Date

Rulați scriptul de actualizare:

```bash
# Accesați scriptul în browser
http://your-site.com/wp-content/plugins/clinica/update-family-fields.php
```

Sau rulați manual în phpMyAdmin:

```sql
ALTER TABLE wp_clinica_patients 
ADD COLUMN family_id INT DEFAULT NULL,
ADD COLUMN family_role ENUM('head', 'spouse', 'child', 'parent', 'sibling') DEFAULT NULL,
ADD COLUMN family_head_id INT DEFAULT NULL,
ADD COLUMN family_name VARCHAR(100) DEFAULT NULL;

-- Adăugați indexurile pentru performanță
ALTER TABLE wp_clinica_patients 
ADD INDEX idx_family_id (family_id),
ADD INDEX idx_family_head_id (family_head_id),
ADD INDEX idx_family_name (family_name);
```

### Pasul 2: Verificarea Fișierelor

Asigurați-vă că următoarele fișiere sunt prezente și actualizate:

- `includes/class-clinica-family-manager.php` ✅ (Corectat)
- `includes/class-clinica-patient-creation-form.php` ✅ (Corectat)
- `includes/class-clinica-family-auto-creator.php` ✅ (Corectat)
- `admin/views/families.php` ✅
- Actualizări în `clinica.php` ✅

### Pasul 3: Testarea Funcționalității

1. Accesați **Clinica > Familii** în meniul admin
2. Verificați că pagina se încarcă corect
3. Testați crearea unei familii noi
4. Testați crearea automată a familiilor pe baza email-urilor

## 📱 Utilizarea Sistemului

### Crearea unei Familii Noi

1. **Accesați pagina Familii**: Clinica > Familii
2. **Clic pe "Creează Familie Nouă"**
3. **Completați informațiile**:
   - Numele familiei (ex: "Familia Popescu")
   - Cap de familie (opțional - poate fi adăugat ulterior)
4. **Clic pe "Creează familia"**

### Adăugarea Membrilor în Familie

1. **În pagina Familii**, găsiți familia dorită
2. **Clic pe "Adaugă membru"**
3. **Selectați pacientul** din lista pacienților fără familie
4. **Alegeți rolul** în familie
5. **Clic pe "Adaugă membru"**

### Crearea Automată a Familiilor

#### Detectarea Familiilor pe baza Email-urilor
1. **Clic pe "Creează Familii Automat"**
2. **Configurați opțiunile**:
   - ✅ Creează părintele ca șef de familie
   - ✅ Atribuie roluri automat
   - ✅ Doar pacienții fără familie
3. **Clic pe "Detectează Familii"**
4. **Verificați previzualizarea** - veți vedea:
   - Numele familiei
   - Email-ul de bază
   - Fiecare membru cu:
     - Numele complet
     - Email-ul
     - Rolul atribuit automat
5. **Clic pe "Creează Familiile Detectate"**

#### Pattern-uri de Email Suportate
- **Părinte**: `nume@email.com`
- **Copil/Membru**: `nume+altnume@email.com`
- Sistemul detectează automat pattern-ul `+` pentru grupare

### Gestionarea Familiilor Existente

#### Vizualizarea Membrilor
- Fiecare familie afișează toți membrii cu rolurile lor
- Membrii sunt ordonați logic (cap de familie, soț/soție, copii, etc.)

#### Editarea Familiilor
- Clic pe "Editează" pentru a modifica informațiile familiei
- Puteți schimba numele familiei sau rolurile membrilor

#### Eliminarea Membrilor
- Clic pe iconița de ștergere lângă membru
- Confirmați eliminarea
- **Notă**: Pacientul nu este șters, doar eliminat din familie

### Căutarea Familiilor

1. **Folosiți bara de căutare** din pagina Familii
2. **Introduceți** numele familiei sau al unui membru
3. **Rezultatele** se afișează în timp real

## 🎨 Interfața Utilizator

### Pagina Principală Familii

```
┌─────────────────────────────────────────────────────────┐
│ 👨‍👩‍👧‍👦 Gestionare Familii                    [+ Creează] │
├─────────────────────────────────────────────────────────┤
│ 📊 Statistici:                                          │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐                    │
│ │ 5 Familii│ │ 12 Fără │ │ 23 Membri│                    │
│ └─────────┘ └─────────┘ └─────────┘                    │
├─────────────────────────────────────────────────────────┤
│ 🏠 Familii existente:                                   │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Familia Popescu                    [3 membri]       │ │
│ │ ├─ Ion Popescu (Cap de familie)                     │ │
│ │ ├─ Maria Popescu (Soție)                            │ │
│ │ └─ Ana Popescu (Copil)                              │ │
│ │ [Adaugă membru] [Editează]                          │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Modal pentru Crearea Familii

```
┌─────────────────────────────────────┐
│ ✕ Creează Familie Nouă              │
├─────────────────────────────────────┤
│ Numele familiei *:                  │
│ [Familia Popescu              ]     │
│                                     │
│ Cap de familie (opțional):          │
│ [Selectează un pacient ▼]           │
│                                     │
│ [Creează familia] [Anulează]        │
└─────────────────────────────────────┘
```

## 🔧 Funcționalități Avansate

### API pentru Familii

Sistemul oferă următoarele endpoint-uri AJAX:

```php
// Crearea unei familii
wp_ajax_clinica_create_family

// Adăugarea unui membru
wp_ajax_clinica_add_family_member

// Obținerea membrilor
wp_ajax_clinica_get_family_members

// Eliminarea unui membru
wp_ajax_clinica_remove_family_member

// Căutarea familiilor
wp_ajax_clinica_search_families
```

### Integrarea în Formularul de Creare Pacienți

Formularul de creare pacienți include acum un tab "Familie" cu opțiuni pentru:

- Nu face parte dintr-o familie
- Creează o familie nouă
- Adaugă la o familie existentă

### Rapoarte și Statistici

Sistemul generează automat statistici pentru:

- Numărul total de familii
- Numărul de pacienți fără familie
- Numărul total de membri în familii
- Distribuția pe roluri

## 🛠️ Dezvoltare și Extensii

### Adăugarea de Roluri Noi

Pentru a adăuga roluri noi, editați:

1. **Baza de date**:
```sql
ALTER TABLE wp_clinica_patients 
MODIFY COLUMN family_role ENUM('head', 'spouse', 'child', 'parent', 'sibling', 'new_role') DEFAULT NULL;
```

2. **Clasa Family Manager**:
```php
public function get_family_role_label($role) {
    $labels = array(
        'head' => 'Cap de familie',
        'spouse' => 'Soț/Soție',
        'child' => 'Copil',
        'parent' => 'Părinte',
        'sibling' => 'Frate/Soră',
        'new_role' => 'Noul Rol' // Adăugați aici
    );
    
    return isset($labels[$role]) ? $labels[$role] : $role;
}
```

### Hook-uri și Filtre

Sistemul oferă următoarele hook-uri pentru extensii:

```php
// Înainte de crearea unei familii
do_action('clinica_before_create_family', $family_name, $head_patient_id);

// După crearea unei familii
do_action('clinica_after_create_family', $family_id, $family_name);

// Înainte de adăugarea unui membru
do_action('clinica_before_add_family_member', $patient_id, $family_id, $family_role);

// După adăugarea unui membru
do_action('clinica_after_add_family_member', $patient_id, $family_id, $family_role);
```

## 🔍 Depanare

### Probleme Comune

#### 1. Câmpurile pentru familie nu apar
**Soluție**: Rulați scriptul `update-family-fields.php`

#### 2. Eroare "Clasa Clinica_Family_Manager nu există"
**Soluție**: Verificați că fișierul este încărcat în `clinica.php`

#### 3. Pagina Familii nu se încarcă
**Soluție**: Verificați permisiunile utilizatorului

#### 4. Nu pot adăuga pacienți în familie
**Soluție**: Verificați că pacientul nu face deja parte dintr-o familie

### Log-uri și Debug

Pentru debugging, activați log-urile WordPress:

```php
// În wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testarea Funcționalității

Rulați testele automate:

```bash
# Accesați scriptul de test
http://your-site.com/wp-content/plugins/clinica/update-family-fields.php
```

## 📈 Performanță și Optimizare

### Indexuri Recomandate

Sistemul include automat indexurile necesare:

- `idx_family_id` - pentru căutări rapide pe familie
- `idx_family_head_id` - pentru relații cap de familie
- `idx_family_name` - pentru căutări pe nume

### Query-uri Optimizate

Toate query-urile sunt optimizate pentru performanță:

```sql
-- Exemplu de query optimizat pentru membrii unei familii
SELECT p.*, u.display_name, u.user_email 
FROM wp_clinica_patients p 
LEFT JOIN wp_users u ON p.user_id = u.ID 
WHERE p.family_id = %d 
ORDER BY 
   CASE p.family_role 
       WHEN 'head' THEN 1 
       WHEN 'spouse' THEN 2 
       WHEN 'child' THEN 3 
       WHEN 'parent' THEN 4 
       WHEN 'sibling' THEN 5 
       ELSE 6 
   END,
   p.birth_date ASC
```

## 🔮 Funcționalități Viitoare

### Planuri de Dezvoltare

1. **Notificări pentru familii** - Email-uri pentru toți membrii
2. **Istoric familial** - Trasabilitatea problemelor medicale
3. **Programări familiale** - Programări pentru întreaga familie
4. **Rapoarte avansate** - Statistici detaliate pe familii
5. **Integrare cu programări** - Sugestii de programări pentru membrii familiei

### Contribuții

Pentru a contribui la dezvoltarea sistemului:

1. Fork repository-ul
2. Creați o branch pentru feature
3. Implementați funcționalitatea
4. Testați complet
5. Creați un pull request

## 📞 Suport

Pentru suport tehnic:

- **Email**: support@clinica.ro
- **Documentație**: https://clinica.ro/docs
- **GitHub**: https://github.com/clinica/family-management

---

**Versiune**: 1.0.0  
**Data**: 19 Iulie 2025  
**Autor**: Clinica Team 