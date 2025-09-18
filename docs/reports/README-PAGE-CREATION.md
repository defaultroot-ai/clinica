# Crearea Automată a Paginilor cu Shortcode-uri

## Descriere

La activarea plugin-ului Clinica, se creează automat 6 pagini care conțin shortcode-urile necesare pentru funcționarea sistemului.

## Paginile Create

### 1. Dashboard Pacient
- **Slug:** `clinica-patient-dashboard`
- **Titlu:** Dashboard Pacient
- **Shortcode:** `[clinica_patient_dashboard]`
- **Descriere:** Pagina principală pentru pacienți autentificați

### 2. Dashboard Doctor
- **Slug:** `clinica-doctor-dashboard`
- **Titlu:** Dashboard Doctor
- **Shortcode:** `[clinica_doctor_dashboard]`
- **Descriere:** Pagina principală pentru doctori autentificați

### 3. Dashboard Asistent
- **Slug:** `clinica-assistant-dashboard`
- **Titlu:** Dashboard Asistent
- **Shortcode:** `[clinica_assistant_dashboard]`
- **Descriere:** Pagina principală pentru asistenți și recepționeri

### 4. Dashboard Manager
- **Slug:** `clinica-manager-dashboard`
- **Titlu:** Dashboard Manager
- **Shortcode:** `[clinica_manager_dashboard]`
- **Descriere:** Pagina principală pentru manageri

### 5. Creare Pacient
- **Slug:** `clinica-create-patient-frontend`
- **Titlu:** Creare Pacient
- **Shortcode:** `[clinica_create_patient_form]`
- **Descriere:** Formular pentru crearea de pacienți noi

### 6. Autentificare Clinica
- **Slug:** `clinica-login`
- **Titlu:** Autentificare Clinica
- **Shortcode:** `[clinica_login]`
- **Descriere:** Formular de autentificare pentru toți utilizatorii

## Implementare

### Funcția `create_pages()`

```php
private function create_pages() {
    // Array cu toate paginile care trebuie create
    $pages_to_create = array(
        array(
            'title' => 'Dashboard Pacient',
            'slug' => 'clinica-patient-dashboard',
            'content' => '[clinica_patient_dashboard]'
        ),
        // ... alte pagini
    );
    
    // Creează fiecare pagină dacă nu există deja
    foreach ($pages_to_create as $page_data) {
        $existing_page = get_page_by_path($page_data['slug']);
        
        if (!$existing_page) {
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_name' => $page_data['slug'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => $page_data['content'],
                'post_author' => 1, // Administrator
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
            
            if ($page_id) {
                // Adaugă meta pentru a marca pagina ca fiind creată de plugin
                update_post_meta($page_id, '_clinica_plugin_page', 'yes');
                update_post_meta($page_id, '_clinica_page_type', $page_data['slug']);
            }
        }
    }
}
```

### Caracteristici de Siguranță

1. **Verificare Duplicate:** Se verifică dacă pagina există deja înainte de creare
2. **Meta-uri Plugin:** Se adaugă meta-uri pentru a identifica paginile create de plugin
3. **Autor Administrator:** Toate paginile sunt create cu autorul administrator
4. **Comentarii Închise:** Comentariile sunt dezactivate pentru aceste pagini
5. **Status Publicat:** Paginile sunt create cu statusul "publish"

## Shortcode-uri Înregistrate

### În `init_components()`

```php
// Înregistrează shortcode-urile
add_shortcode('clinica_create_patient_form', array($this, 'render_create_patient_form'));
add_shortcode('clinica_patient_dashboard', array($this, 'render_patient_dashboard'));
add_shortcode('clinica_doctor_dashboard', array($this, 'render_doctor_dashboard'));
add_shortcode('clinica_assistant_dashboard', array($this, 'render_assistant_dashboard'));
add_shortcode('clinica_manager_dashboard', array($this, 'render_manager_dashboard'));
```

### Clasa de Autentificare

```php
// În Clinica_Authentication
add_shortcode('clinica_login', array($this, 'render_login_shortcode'));
```

## Testare

### Script de Test

Am creat `test-page-creation.php` pentru a verifica:

1. **Paginile Existente:** Verifică dacă toate paginile sunt create
2. **Shortcode-uri Înregistrate:** Verifică dacă toate shortcode-urile sunt disponibile
3. **Verificare Duplicate:** Verifică dacă nu există pagini duplicate
4. **Meta-uri Plugin:** Verifică dacă meta-urile sunt setate corect

### Cum să Rulezi Testul

1. Accesează `http://localhost/plm/wp-content/plugins/clinica/test-page-creation.php`
2. Verifică rezultatele pentru fiecare secțiune
3. Folosește link-urile pentru a vizita paginile create
4. Verifică dacă shortcode-urile funcționează corect

## URL-uri Generate

După activarea plugin-ului, următoarele URL-uri vor fi disponibile:

- `http://localhost/plm/clinica-patient-dashboard/`
- `http://localhost/plm/clinica-doctor-dashboard/`
- `http://localhost/plm/clinica-assistant-dashboard/`
- `http://localhost/plm/clinica-manager-dashboard/`
- `http://localhost/plm/clinica-create-patient-frontend/`
- `http://localhost/plm/clinica-login/`

## Gestionarea Paginilor

### Verificare Dacă Pagina Este Creată de Plugin

```php
$plugin_page = get_post_meta($page_id, '_clinica_plugin_page', true);
if ($plugin_page === 'yes') {
    // Pagina a fost creată de plugin
}
```

### Obținerea Tipului de Pagină

```php
$page_type = get_post_meta($page_id, '_clinica_page_type', true);
// Returnează slug-ul paginii (ex: 'clinica-patient-dashboard')
```

## Note Importante

1. **Nu se Creează Duplicate:** Funcția verifică dacă pagina există înainte de creare
2. **Permisiuni:** Doar administratorii pot accesa scriptul de test
3. **Compatibilitate:** Paginile sunt compatibile cu toate temele WordPress
4. **SEO:** Slug-urile sunt optimizate pentru SEO
5. **Securitate:** Comentariile sunt dezactivate pentru aceste pagini

## Următorii Pași

1. **Testează Paginile:** Accesează fiecare pagină pentru a verifica funcționalitatea
2. **Personalizează Design-ul:** Adaugă CSS personalizat pentru pagini
3. **Configurare Meniu:** Adaugă paginile în meniul principal al site-ului
4. **Testează Shortcode-urile:** Verifică dacă toate shortcode-urile funcționează corect
5. **Documentație Utilizatori:** Creează ghiduri pentru utilizatori pentru fiecare tip de dashboard 