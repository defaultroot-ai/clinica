# Implementare Roluri Duble - Clinica Plugin

## **📋 PREZENTARE GENERALĂ**

Sistemul de roluri duble permite ca personalul clinicii (medici, asistente, receptioneri, manageri) să aibă și rolul de pacient, permițându-le să acceseze atât dashboard-ul de staff cât și dashboard-ul de pacient.

## **🔧 MODIFICĂRI IMPLEMENTATE**

### **1. Tabel Nou: `wp_clinica_user_active_roles`**
```sql
CREATE TABLE wp_clinica_user_active_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    active_role VARCHAR(50) NOT NULL,
    last_switched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (user_id, active_role),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

### **2. Funcții Noi în `class-clinica-database.php`**
- `migrate_to_dual_roles()` - Migrează automat toți staff-ul la roluri duble
- `is_dual_roles_migrated()` - Verifică dacă migrarea a fost făcută
- `reset_dual_roles_migration()` - Resetează migrarea (pentru testare)

### **3. Funcții Noi în `class-clinica-roles.php`**
- `add_patient_role_to_staff($user_id)` - Adaugă rol de pacient la staff
- `has_dual_role($user_id)` - Verifică dacă utilizatorul are roluri duble
- `get_user_roles($user_id)` - Obține toate rolurile utilizatorului
- `get_user_active_role($user_id)` - Obține rolul activ din tabela de roluri active
- `update_user_active_role($user_id, $active_role)` - Actualizează rolul activ
- `switch_user_role($user_id, $new_role)` - Schimbă rolul activ
- `get_available_roles_for_user($user_id)` - Obține rolurile disponibile
- `can_access_patient_dashboard($user_id)` - Verifică accesul la dashboard pacient
- `can_access_staff_dashboard($user_id)` - Verifică accesul la dashboard staff

### **4. Funcții Noi în `class-clinica-patient-permissions.php`**
- `can_access_patient_dashboard($user_id)` - Verifică accesul la dashboard pacient
- `can_access_staff_dashboard($user_id)` - Verifică accesul la dashboard staff
- `has_dual_role($user_id)` - Verifică roluri duble
- `get_user_active_role($user_id)` - Obține rolul activ
- `get_available_roles_for_user($user_id)` - Obține rolurile disponibile
- `switch_user_role($user_id, $new_role)` - Schimbă rolul activ
- `can_access_dashboard_by_active_role($user_id)` - Verifică accesul în funcție de rolul activ
- `get_dashboard_url_by_active_role($user_id)` - Obține URL-ul corect pentru dashboard
- `can_access_page_by_active_role($page, $user_id)` - Verifică accesul la pagină în funcție de rolul activ

### **5. Migrare Automată**
- Se execută automat la activarea plugin-ului
- Adaugă rolul de pacient la toți utilizatorii cu roluri de staff
- **NON-DESTRUCTIVE** - nu modifică datele existente
- Se execută o singură dată (verifică flag-ul `clinica_dual_roles_migrated`)

## **🚀 UTILIZARE**

### **Pentru Dezvoltatori:**
```php
// Verifică dacă utilizatorul are roluri duble
if (Clinica_Roles::has_dual_role($user_id)) {
    // Utilizatorul poate accesa atât dashboard-ul de staff cât și cel de pacient
}

// Obține rolul activ
$active_role = Clinica_Roles::get_user_active_role($user_id);

// Schimbă rolul activ
Clinica_Roles::switch_user_role($user_id, 'clinica_patient');

// Verifică accesul la dashboard în funcție de rolul activ
if (Clinica_Patient_Permissions::can_access_dashboard_by_active_role($user_id)) {
    // Utilizatorul poate accesa dashboard-ul corespunzător rolului activ
}
```

### **Pentru Utilizatori:**
1. **Staff-ul** va avea automat rolul de pacient adăugat
2. **Rolul activ** determină ce dashboard poate accesa
3. **Schimbarea rolului** se face prin funcțiile specializate
4. **URL-urile** se ajustează automat în funcție de rolul activ

## **🔒 SIGURANȚĂ**

### **Garanții de Siguranță:**
1. **NON-DESTRUCTIVE** - Nu modifică datele existente
2. **BACKWARD COMPATIBLE** - Funcționalitățile existente rămân neschimbate
3. **MIGRARE AUTOMATĂ** - Se execută o singură dată
4. **VERIFICĂRI** - Verifică existența rolurilor înainte de adăugare
5. **LOG-URI** - Toate operațiunile sunt logate

### **Verificări de Siguranță:**
- Verifică existența utilizatorului înainte de modificare
- Verifică existența rolului înainte de adăugare
- Verifică dacă migrarea a fost deja făcută
- Folosește `wpdb->replace()` pentru operațiuni sigure
- Loghează toate operațiunile pentru debugging

## **🧪 TESTARE**

### **Fișier de Test:**
- `test_dual_roles.php` - Test complet pentru funcționalitatea de roluri duble
- Accesează: `/wp-content/plugins/clinica/test_dual_roles.php`

### **Teste Incluse:**
1. Verificare roluri existente
2. Verificare roluri duble
3. Verificare rol activ
4. Verificare roluri disponibile
5. Verificare permisiuni dashboard
6. Verificare URL-uri dashboard
7. Verificare status migrare
8. Verificare tabela roluri active
9. Teste pentru administratori (migrare, reset, schimbare roluri)

## **📊 MONITORIZARE**

### **Opțiuni WordPress:**
- `clinica_dual_roles_migrated` - Flag pentru migrarea completă
- `clinica_dual_roles_migration_date` - Data migrării
- `clinica_dual_roles_migrated_count` - Numărul de utilizatori migrați

### **Log-uri:**
- Toate operațiunile sunt logate în `error_log`
- Format: `[CLINICA] User X switched to role: Y`
- Format: `[CLINICA] Dual roles migration completed. Migrated X users.`

## **🔄 ROLLBACK**

### **Pentru a reveni la sistemul vechi:**
1. Resetează migrarea: `Clinica_Database::reset_dual_roles_migration()`
2. Șterge rolul de pacient de la staff: `$user->remove_role('clinica_patient')`
3. Șterge tabela: `DROP TABLE wp_clinica_user_active_roles`

### **Verificare Rollback:**
```php
// Verifică dacă migrarea a fost resetată
if (!Clinica_Database::is_dual_roles_migrated()) {
    echo "Migrarea a fost resetată cu succes";
}
```

## **📈 PERFORMANȚĂ**

### **Optimizări:**
- Verificări de existență înainte de operațiuni
- Folosește `wpdb->replace()` pentru operațiuni atomice
- Indexuri pe coloanele importante (`user_id`, `active_role`)
- Verificări de cache pentru rolul activ

### **Impact:**
- **Minim** - Nu afectează funcționalitățile existente
- **Scalabil** - Funcționează cu orice număr de utilizatori
- **Eficient** - Verificări rapide prin indexuri

## **🔮 VIITOR**

### **Funcționalități Planificate:**
1. **UI pentru schimbarea rolurilor** - Interfață grafică pentru utilizatori
2. **Notificări** - Notificări când se schimbă rolul
3. **Istoric** - Istoricul schimbărilor de roluri
4. **Configurare** - Setări pentru activarea/dezactivarea rolurilor duble

### **Îmbunătățiri Posibile:**
1. **Cache** - Cache pentru rolul activ
2. **AJAX** - Schimbare roluri prin AJAX
3. **Shortcode** - Shortcode pentru afișarea rolului activ
4. **Widget** - Widget pentru schimbarea rolurilor

---

**Data Implementării:** $(date)  
**Versiune:** 1.0.0  
**Status:** ✅ IMPLEMENTAT ȘI TESTAT
