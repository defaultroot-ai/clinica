<?php
/**
 * Gestionare baza de date pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Database {
    
    /**
     * Creează toate tabelele necesare
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Verifică dacă tabelele există deja
        if (self::tables_exist()) {
            // Dacă tabelele există, asigură servicii + foreign keys
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            // Asigură existența tabelei servicii chiar dacă restul există
            $table_services = $wpdb->prefix . 'clinica_services';
            $sql_services = "CREATE TABLE $table_services (
                id INT AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                duration INT NOT NULL DEFAULT 30,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_active (active),
                INDEX idx_name (name)
            ) $charset_collate;";
            dbDelta($sql_services);
            self::add_foreign_keys();
            return;
        }
        
        // Tabela pacienți
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $sql_patients = "CREATE TABLE $table_patients (
            id INT AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            cnp VARCHAR(13) UNIQUE NOT NULL,
            email VARCHAR(191) DEFAULT NULL,
            cnp_type ENUM('romanian', 'foreign_permanent', 'foreign_temporary') DEFAULT 'romanian',
            phone_primary VARCHAR(20),
            phone_secondary VARCHAR(20),
            birth_date DATE,
            gender ENUM('male', 'female'),
            age INT,
            address TEXT,
            emergency_contact VARCHAR(20),
            blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
            allergies TEXT,
            medical_history TEXT,
            password_method ENUM('cnp', 'birth_date') DEFAULT 'cnp',
            import_source VARCHAR(50),
            created_by BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            family_id INT DEFAULT NULL COMMENT 'Family management - ID familie',
            family_role ENUM('head', 'spouse', 'child', 'parent', 'sibling') DEFAULT NULL COMMENT 'Family management - Rol în familie',
            family_head_id INT DEFAULT NULL COMMENT 'Family management - ID cap de familie',
            family_name VARCHAR(100) DEFAULT NULL COMMENT 'Family management - Nume familie',
            PRIMARY KEY (id),
            INDEX idx_cnp (cnp),
            INDEX idx_email (email),
            INDEX idx_cnp_type (cnp_type),
            INDEX idx_user_id (user_id),
            INDEX idx_phone_primary (phone_primary),
            INDEX idx_phone_secondary (phone_secondary),
            INDEX idx_family_id (family_id),
            INDEX idx_family_head_id (family_head_id),
            INDEX idx_family_name (family_name)
        ) $charset_collate;";
        
        // Tabela programări
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $sql_appointments = "CREATE TABLE $table_appointments (
            id INT AUTO_INCREMENT,
            patient_id BIGINT UNSIGNED NOT NULL,
            doctor_id BIGINT UNSIGNED NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            duration INT DEFAULT 30,
            status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            type ENUM('consultation', 'examination', 'procedure', 'follow_up') DEFAULT 'consultation',
            notes TEXT,
            created_by BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_patient_id (patient_id),
            INDEX idx_doctor_id (doctor_id),
            INDEX idx_appointment_date (appointment_date),
            INDEX idx_status (status),
            INDEX idx_type (type)
        ) $charset_collate;";
        
        // Tabela dosare medicale
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        $sql_medical_records = "CREATE TABLE $table_medical_records (
            id INT AUTO_INCREMENT,
            patient_id BIGINT UNSIGNED NOT NULL,
            doctor_id BIGINT UNSIGNED NOT NULL,
            record_date DATE NOT NULL,
            diagnosis TEXT,
            treatment TEXT,
            prescription TEXT,
            notes TEXT,
            attachments TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_patient_id (patient_id),
            INDEX idx_doctor_id (doctor_id),
            INDEX idx_record_date (record_date)
        ) $charset_collate;";
        
        // Tabela setări clinică
        $table_settings = $wpdb->prefix . 'clinica_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id INT AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json', 'file') DEFAULT 'text',
            setting_group VARCHAR(50) DEFAULT 'general',
            setting_label VARCHAR(255),
            setting_description TEXT,
            is_public BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_setting_key (setting_key),
            INDEX idx_setting_group (setting_group)
        ) $charset_collate;";
        
        // Tabela log-uri autentificare
        $table_login_logs = $wpdb->prefix . 'clinica_login_logs';
        $sql_login_logs = "CREATE TABLE $table_login_logs (
            id INT AUTO_INCREMENT,
            user_id BIGINT UNSIGNED DEFAULT 0,
            identifier VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE,
            reason VARCHAR(100),
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_identifier (identifier),
            INDEX idx_ip_address (ip_address),
            INDEX idx_login_time (login_time),
            INDEX idx_success (success)
        ) $charset_collate;";
        
        // Tabela import-uri
        $table_imports = $wpdb->prefix . 'clinica_imports';
        $sql_imports = "CREATE TABLE $table_imports (
            id INT AUTO_INCREMENT,
            import_type ENUM('icmed', 'joomla', 'csv', 'excel') NOT NULL,
            filename VARCHAR(255),
            total_records INT DEFAULT 0,
            imported_records INT DEFAULT 0,
            failed_records INT DEFAULT 0,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            created_by BIGINT UNSIGNED,
            error_log TEXT,
            PRIMARY KEY (id),
            INDEX idx_import_type (import_type),
            INDEX idx_status (status),
            INDEX idx_started_at (started_at)
        ) $charset_collate;";
        
        // Tabela notificări
        $table_notifications = $wpdb->prefix . 'clinica_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id INT AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type ENUM('appointment', 'reminder', 'system', 'alert') DEFAULT 'system',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_read_at (read_at),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        // Tabela servicii (catalog)
        $table_services = $wpdb->prefix . 'clinica_services';
        $sql_services = "CREATE TABLE $table_services (
            id INT AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            duration INT NOT NULL DEFAULT 30,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_active (active),
            INDEX idx_name (name)
        ) $charset_collate;";
        
        // Tabela alocare doctori-servicii
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        $sql_doctor_services = "CREATE TABLE $table_doctor_services (
            id INT AUTO_INCREMENT,
            doctor_id BIGINT UNSIGNED NOT NULL,
            service_id INT NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_doctor_service (doctor_id, service_id),
            INDEX idx_doctor_id (doctor_id),
            INDEX idx_service_id (service_id),
            INDEX idx_active (active)
        ) $charset_collate;";
        
        // Tabela program global clinică
        $table_clinic_schedule = $wpdb->prefix . 'clinica_clinic_schedule';
        $sql_clinic_schedule = "CREATE TABLE $table_clinic_schedule (
            id INT AUTO_INCREMENT,
            day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            break_start TIME NULL,
            break_end TIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_day (day_of_week),
            INDEX idx_active (active)
        ) $charset_collate;";
        
        // Tabela roluri active utilizatori (pentru roluri duble)
        $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
        $sql_user_active_roles = "CREATE TABLE $table_user_active_roles (
            id INT AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            active_role VARCHAR(50) NOT NULL,
            last_switched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_role (user_id, active_role),
            INDEX idx_user_id (user_id),
            INDEX idx_active_role (active_role),
            INDEX idx_last_switched (last_switched)
        ) $charset_collate;";
        
        // Execută crearea tabelelor
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Verifică și curăță SQL-urile înainte de execuție
        $sqls = array(
            'patients' => $sql_patients,
            'appointments' => $sql_appointments,
            'medical_records' => $sql_medical_records,
            'settings' => $sql_settings,
            'login_logs' => $sql_login_logs,
            'imports' => $sql_imports,
            'notifications' => $sql_notifications,
            'services' => $sql_services,
            'doctor_services' => $sql_doctor_services,
            'clinic_schedule' => $sql_clinic_schedule,
            'user_active_roles' => $sql_user_active_roles
        );
        
        foreach ($sqls as $table_name => $sql) {
            // Verifică că SQL-ul este valid și complet
            if (!empty($sql) && strpos($sql, 'CREATE TABLE') !== false && strpos($sql, ';') !== false) {
                try {
                    dbDelta($sql);
                    error_log("[CLINICA] Table '$table_name' created/updated successfully.");
                } catch (Exception $e) {
                    error_log("[CLINICA] Error creating table '$table_name': " . $e->getMessage());
                }
            } else {
                error_log("[CLINICA] Invalid SQL for table '$table_name' - skipped.");
            }
        }
        
        // Adaugă foreign key-urile separat pentru a evita problemele cu dbDelta
        self::add_foreign_keys();
        
        // Actualizează versiunea bazei de date
        update_option('clinica_db_version', '1.0.0');
    }
    
    /**
     * Forțează recrearea tabelelor
     */
    public static function force_recreate_tables() {
        // Șterge tabelele existente
        self::drop_tables();
        
        // Recreează tabelele
        self::create_tables();
    }
    
    /**
     * Adaugă foreign key-urile separat
     */
    private static function add_foreign_keys() {
        global $wpdb;
        
        // Verifică dacă foreign key-urile există deja
        $foreign_keys_exist = get_option('clinica_foreign_keys_added', false);
        
        if ($foreign_keys_exist) {
            return;
        }
        
        // Funcție pentru a verifica dacă un foreign key există
        $foreign_key_exists = function($table, $constraint_name) use ($wpdb) {
            $result = $wpdb->get_results("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '$table' 
                AND CONSTRAINT_NAME = '$constraint_name'
            ");
            return !empty($result);
        };
        
        // Foreign key-uri pentru tabela pacienți
        $table_patients = $wpdb->prefix . 'clinica_patients';
        if (!$foreign_key_exists($table_patients, 'fk_patients_user_id')) {
            $wpdb->query("ALTER TABLE $table_patients ADD CONSTRAINT fk_patients_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }

        // Asigură existența coloanei email în clinica_patients pentru backward-compat
        $has_email_col = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'email'", $table_patients));
        if (!$has_email_col) {
            $wpdb->query("ALTER TABLE $table_patients ADD COLUMN email VARCHAR(191) NULL AFTER cnp");
            // index opțional pentru căutări după email
            $wpdb->query("ALTER TABLE $table_patients ADD INDEX idx_email (email)");
        }
        if (!$foreign_key_exists($table_patients, 'fk_patients_created_by')) {
            $wpdb->query("ALTER TABLE $table_patients ADD CONSTRAINT fk_patients_created_by FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID)");
        }
        
        // Foreign key-uri pentru tabela programări
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        if (!$foreign_key_exists($table_appointments, 'fk_appointments_patient_id')) {
            $wpdb->query("ALTER TABLE $table_appointments ADD CONSTRAINT fk_appointments_patient_id FOREIGN KEY (patient_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }
        if (!$foreign_key_exists($table_appointments, 'fk_appointments_doctor_id')) {
            $wpdb->query("ALTER TABLE $table_appointments ADD CONSTRAINT fk_appointments_doctor_id FOREIGN KEY (doctor_id) REFERENCES {$wpdb->users}(ID)");
        }
        if (!$foreign_key_exists($table_appointments, 'fk_appointments_created_by')) {
            $wpdb->query("ALTER TABLE $table_appointments ADD CONSTRAINT fk_appointments_created_by FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID)");
        }
        
        // Foreign key-uri pentru tabela dosare medicale
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        if (!$foreign_key_exists($table_medical_records, 'fk_medical_records_patient_id')) {
            $wpdb->query("ALTER TABLE $table_medical_records ADD CONSTRAINT fk_medical_records_patient_id FOREIGN KEY (patient_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }
        if (!$foreign_key_exists($table_medical_records, 'fk_medical_records_doctor_id')) {
            $wpdb->query("ALTER TABLE $table_medical_records ADD CONSTRAINT fk_medical_records_doctor_id FOREIGN KEY (doctor_id) REFERENCES {$wpdb->users}(ID)");
        }
        
        // Foreign key-uri pentru tabela import-uri
        $table_imports = $wpdb->prefix . 'clinica_imports';
        if (!$foreign_key_exists($table_imports, 'fk_imports_created_by')) {
            $wpdb->query("ALTER TABLE $table_imports ADD CONSTRAINT fk_imports_created_by FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID)");
        }
        
        // Foreign key-uri pentru tabela notificări
        $table_notifications = $wpdb->prefix . 'clinica_notifications';
        if (!$foreign_key_exists($table_notifications, 'fk_notifications_user_id')) {
            $wpdb->query("ALTER TABLE $table_notifications ADD CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }

        // Foreign key-uri pentru tabela alocare doctori-servicii
        $table_doctor_services = $wpdb->prefix . 'clinica_doctor_services';
        if (!$foreign_key_exists($table_doctor_services, 'fk_doctor_services_doctor_id')) {
            $wpdb->query("ALTER TABLE $table_doctor_services ADD CONSTRAINT fk_doctor_services_doctor_id FOREIGN KEY (doctor_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }
        if (!$foreign_key_exists($table_doctor_services, 'fk_doctor_services_service_id')) {
            $wpdb->query("ALTER TABLE $table_doctor_services ADD CONSTRAINT fk_doctor_services_service_id FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}clinica_services(id) ON DELETE CASCADE");
        }

        // Foreign key-uri pentru tabela program global clinică
        // Nu avem nevoie de foreign key pentru day_of_week (este ENUM)
        
        // Foreign key pentru tabela roluri active utilizatori
        $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
        if (!$foreign_key_exists($table_user_active_roles, 'fk_user_active_roles_user_id')) {
            $wpdb->query("ALTER TABLE $table_user_active_roles ADD CONSTRAINT fk_user_active_roles_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE");
        }
        
        // Marchează că foreign key-urile au fost adăugate
        update_option('clinica_foreign_keys_added', true);
    }
    
    /**
     * Verifică dacă tabelele există
     */
    public static function tables_exist() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'clinica_patients',
            $wpdb->prefix . 'clinica_appointments',
            $wpdb->prefix . 'clinica_medical_records',
            $wpdb->prefix . 'clinica_login_logs',
            $wpdb->prefix . 'clinica_imports',
            $wpdb->prefix . 'clinica_notifications',
            $wpdb->prefix . 'clinica_services',
            $wpdb->prefix . 'clinica_doctor_services',
            $wpdb->prefix . 'clinica_clinic_schedule',
            $wpdb->prefix . 'clinica_user_active_roles'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Șterge toate tabelele pentru a le recreea
     */
    public static function drop_tables() {
        global $wpdb;
        
        // Ordinea corectă pentru ștergere - tabelele cu foreign keys înainte
        $tables = array(
            $wpdb->prefix . 'clinica_notifications',
            $wpdb->prefix . 'clinica_imports',
            $wpdb->prefix . 'clinica_login_logs',
            $wpdb->prefix . 'clinica_medical_records', // Referă pacienți și doctori
            $wpdb->prefix . 'clinica_appointments',    // Referă pacienți și doctori
            $wpdb->prefix . 'clinica_patients',         // Referă wp_users
            $wpdb->prefix . 'clinica_doctor_services',
            $wpdb->prefix . 'clinica_clinic_schedule',
            $wpdb->prefix . 'clinica_user_active_roles'  // Referă wp_users
        );
        
        // Dezactivează verificarea foreign keys temporar
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Reactivează verificarea foreign keys
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Șterge opțiunile
        delete_option('clinica_db_version');
        delete_option('clinica_foreign_keys_added');
    }
    
    /**
     * Obține statistici despre baza de date
     */
    public static function get_database_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Numărul de pacienți
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $stats['total_patients'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
        
        // Pacienți pe tip CNP
        $stats['patients_by_cnp_type'] = $wpdb->get_results("
            SELECT cnp_type, COUNT(*) as count 
            FROM $table_patients 
            GROUP BY cnp_type
        ");
        
        // Numărul de programări
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $stats['total_appointments'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_appointments");
        
        // Programări pe status
        $stats['appointments_by_status'] = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_appointments 
            GROUP BY status
        ");
        
        // Programări pentru astăzi
        $today = date('Y-m-d');
        $stats['appointments_today'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_appointments 
            WHERE appointment_date = %s
        ", $today));
        
        // Numărul de dosare medicale
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        $stats['total_medical_records'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_medical_records");
        
        // Log-uri de autentificare
        $table_login_logs = $wpdb->prefix . 'clinica_login_logs';
        $stats['total_login_attempts'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_login_logs");
        $stats['successful_logins'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_login_logs WHERE success = 1");
        $stats['failed_logins'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_login_logs WHERE success = 0");
        
        return $stats;
    }
    
    /**
     * Curăță datele vechi
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        // Șterge log-urile de autentificare mai vechi de 90 de zile
        $table_login_logs = $wpdb->prefix . 'clinica_login_logs';
        $wpdb->query("
            DELETE FROM $table_login_logs 
            WHERE login_time < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        // Șterge notificările citite mai vechi de 30 de zile
        $table_notifications = $wpdb->prefix . 'clinica_notifications';
        $wpdb->query("
            DELETE FROM $table_notifications 
            WHERE read_at IS NOT NULL 
            AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Șterge import-urile eșuate mai vechi de 7 zile
        $table_imports = $wpdb->prefix . 'clinica_imports';
        $wpdb->query("
            DELETE FROM $table_imports 
            WHERE status = 'failed' 
            AND started_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
    }
    
    /**
     * Backup baza de date
     */
    public static function backup_database() {
        global $wpdb;
        
        $backup_data = array();
        
        // Backup pacienți
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $backup_data['patients'] = $wpdb->get_results("SELECT * FROM $table_patients", ARRAY_A);
        
        // Backup programări
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        $backup_data['appointments'] = $wpdb->get_results("SELECT * FROM $table_appointments", ARRAY_A);
        
        // Backup dosare medicale
        $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
        $backup_data['medical_records'] = $wpdb->get_results("SELECT * FROM $table_medical_records", ARRAY_A);
        
        // Salvează backup-ul
        $backup_file = CLINICA_PLUGIN_PATH . 'backups/backup_' . date('Y-m-d_H-i-s') . '.json';
        
        if (!is_dir(dirname($backup_file))) {
            wp_mkdir_p(dirname($backup_file));
        }
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        return $backup_file;
    }
    
    /**
     * Restore baza de date
     */
    public static function restore_database($backup_file) {
        global $wpdb;
        
        if (!file_exists($backup_file)) {
            return false;
        }
        
        $backup_data = json_decode(file_get_contents($backup_file), true);
        
        if (!$backup_data) {
            return false;
        }
        
        // Restore pacienți
        if (isset($backup_data['patients'])) {
            $table_patients = $wpdb->prefix . 'clinica_patients';
            foreach ($backup_data['patients'] as $patient) {
                $wpdb->replace($table_patients, $patient);
            }
        }
        
        // Restore programări
        if (isset($backup_data['appointments'])) {
            $table_appointments = $wpdb->prefix . 'clinica_appointments';
            foreach ($backup_data['appointments'] as $appointment) {
                $wpdb->replace($table_appointments, $appointment);
            }
        }
        
        // Restore dosare medicale
        if (isset($backup_data['medical_records'])) {
            $table_medical_records = $wpdb->prefix . 'clinica_medical_records';
            foreach ($backup_data['medical_records'] as $record) {
                $wpdb->replace($table_medical_records, $record);
            }
        }
        
        return true;
    }
    
    /**
     * Optimizează tabelele
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'clinica_patients',
            $wpdb->prefix . 'clinica_appointments',
            $wpdb->prefix . 'clinica_medical_records',
            $wpdb->prefix . 'clinica_login_logs',
            $wpdb->prefix . 'clinica_imports',
            $wpdb->prefix . 'clinica_notifications',
            $wpdb->prefix . 'clinica_services',
            $wpdb->prefix . 'clinica_doctor_services',
            $wpdb->prefix . 'clinica_clinic_schedule',
            $wpdb->prefix . 'clinica_user_active_roles'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }
    
    /**
     * Verifică integritatea bazei de date
     */
    public static function check_database_integrity() {
        global $wpdb;
        
        $issues = array();
        
        // Verifică referințele străine
        $table_patients = $wpdb->prefix . 'clinica_patients';
        $orphaned_patients = $wpdb->get_results("
            SELECT p.* FROM $table_patients p 
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
            WHERE u.ID IS NULL
        ");
        
        if (!empty($orphaned_patients)) {
            $issues[] = 'Pacienți orfani (fără utilizator WordPress)';
        }
        
        // Verifică CNP-uri duplicate
        $duplicate_cnps = $wpdb->get_results("
            SELECT cnp, COUNT(*) as count 
            FROM $table_patients 
            GROUP BY cnp 
            HAVING count > 1
        ");
        
        if (!empty($duplicate_cnps)) {
            $issues[] = 'CNP-uri duplicate găsite';
        }
        
        return $issues;
    }
    
    /**
     * Normalizează un nume din UPPERCASE în Title Case
     */
    public static function normalize_name($name) {
        if (empty($name)) {
            return $name;
        }
        
        // Transformă UPPERCASE în Title Case cu suport pentru caractere românești
        $name = mb_strtolower($name, 'UTF-8');
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        
        // Tratează cazuri speciale (de, din, la, etc.)
        $small_words = array('de', 'din', 'la', 'cu', 'pe', 'prin', 'sub', 'peste', 'dupa', 'intre', 'fara');
        foreach ($small_words as $word) {
            $name = preg_replace('/\b' . mb_convert_case($word, MB_CASE_TITLE, 'UTF-8') . '\b/', mb_strtolower($word, 'UTF-8'), $name);
        }
        
        return $name;
    }
    
    /**
     * Normalizează numele unui utilizator
     */
    public static function normalize_user_names($user_id) {
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        
        if (!empty($first_name)) {
            $normalized_first_name = self::normalize_name($first_name);
            if ($normalized_first_name !== $first_name) {
                update_user_meta($user_id, 'first_name', $normalized_first_name);
            }
        }
        
        if (!empty($last_name)) {
            $normalized_last_name = self::normalize_name($last_name);
            if ($normalized_last_name !== $last_name) {
                update_user_meta($user_id, 'last_name', $normalized_last_name);
            }
        }
    }
    
    /**
     * Migrează la sistemul de roluri duble
     * Adaugă rolul de pacient la toți staff-ul existent
     */
    public static function migrate_to_dual_roles() {
        global $wpdb;
        
        // Verifică dacă migrarea a fost deja făcută
        $migration_done = get_option('clinica_dual_roles_migrated', false);
        if ($migration_done) {
            return true;
        }
        
        // Rolurile de staff care vor primi și rol de pacient
        $staff_roles = array(
            'clinica_administrator',
            'clinica_manager', 
            'clinica_doctor',
            'clinica_assistant',
            'clinica_receptionist'
        );
        
        // Găsește toți utilizatorii cu roluri de staff
        $staff_users = $wpdb->get_results("
            SELECT u.ID, u.user_login, um.meta_value as roles
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
            AND um.meta_value LIKE '%clinica_%'
        ");
        
        $migrated_count = 0;
        
        foreach ($staff_users as $user) {
            // Obține rolurile corecte din WordPress
            $user_obj = get_userdata($user->ID);
            if (!$user_obj) {
                continue;
            }
            
            $user_roles = $user_obj->roles;
            
            // Verifică dacă utilizatorul are un rol de staff
            $has_staff_role = false;
            foreach ($staff_roles as $staff_role) {
                if (in_array($staff_role, $user_roles)) {
                    $has_staff_role = true;
                    break;
                }
            }
            
            // Dacă are rol de staff dar nu are rol de pacient, adaugă-l
            if ($has_staff_role && !in_array('clinica_patient', $user_roles)) {
                $user_roles[] = 'clinica_patient';
                
                // Actualizează rolurile utilizatorului
                $user_obj = new WP_User($user->ID);
                $user_obj->set_role(''); // Șterge rolul principal
                
                // Adaugă toate rolurile
                foreach ($user_roles as $role) {
                    $user_obj->add_role($role);
                }
                
                // NU seta rolul principal - lasă toate rolurile active
                // set_role() șterge toate rolurile și setează doar unul
                
                // Adaugă în tabela de roluri active
                $table_user_active_roles = $wpdb->prefix . 'clinica_user_active_roles';
                
                // Găsește rolul principal de staff
                $primary_staff_role = '';
                foreach ($staff_roles as $staff_role) {
                    if (in_array($staff_role, $user_roles)) {
                        $primary_staff_role = $staff_role;
                        break;
                    }
                }
                
                $wpdb->replace(
                    $table_user_active_roles,
                    array(
                        'user_id' => $user->ID,
                        'active_role' => $primary_staff_role, // Rolul principal de staff
                        'last_switched' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s')
                );
                
                $migrated_count++;
            }
        }
        
        // Marchează migrarea ca fiind completă
        update_option('clinica_dual_roles_migrated', true);
        update_option('clinica_dual_roles_migration_date', current_time('mysql'));
        update_option('clinica_dual_roles_migrated_count', $migrated_count);
        
        return $migrated_count;
    }
    
    /**
     * Verifică dacă migrarea la roluri duble a fost făcută
     */
    public static function is_dual_roles_migrated() {
        return get_option('clinica_dual_roles_migrated', false);
    }
    
    /**
     * Resetează migrarea la roluri duble (pentru testare)
     */
    public static function reset_dual_roles_migration() {
        delete_option('clinica_dual_roles_migrated');
        delete_option('clinica_dual_roles_migration_date');
        delete_option('clinica_dual_roles_migrated_count');
    }
    
    /**
     * Actualizează tabelele cu noile coloane pentru tracking creator/editor
     */
    public static function update_tables_for_tracking() {
        global $wpdb;
        
        // Verifică dacă actualizarea a fost deja făcută
        $update_done = get_option('clinica_tracking_updated', false);
        if ($update_done) {
            return true;
        }
        
        $table_appointments = $wpdb->prefix . 'clinica_appointments';
        
        // Verifică dacă coloanele există deja
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_appointments");
        
        // Adaugă coloanele pentru tracking creator
        if (!in_array('created_by_type', $columns)) {
            $wpdb->query("ALTER TABLE $table_appointments 
                ADD COLUMN created_by_type ENUM('patient', 'doctor', 'assistant', 'receptionist', 'admin', 'manager') NOT NULL DEFAULT 'receptionist' 
                AFTER created_by");
        }
        
        // Adaugă coloanele pentru tracking editor
        if (!in_array('last_edited_by_type', $columns)) {
            $wpdb->query("ALTER TABLE $table_appointments 
                ADD COLUMN last_edited_by_type ENUM('patient', 'doctor', 'assistant', 'receptionist', 'admin', 'manager') DEFAULT NULL 
                AFTER created_by_type");
        }
        
        if (!in_array('last_edited_by_user_id', $columns)) {
            $wpdb->query("ALTER TABLE $table_appointments 
                ADD COLUMN last_edited_by_user_id INT(11) DEFAULT NULL 
                AFTER last_edited_by_type");
        }
        
        if (!in_array('last_edited_at', $columns)) {
            $wpdb->query("ALTER TABLE $table_appointments 
                ADD COLUMN last_edited_at DATETIME DEFAULT NULL 
                AFTER last_edited_by_user_id");
        }
        
        // Adaugă service_id dacă nu există
        if (!in_array('service_id', $columns)) {
            $wpdb->query("ALTER TABLE $table_appointments 
                ADD COLUMN service_id INT(11) DEFAULT NULL 
                AFTER type");
        }
        
        // Adaugă indexuri pentru performanță
        $indexes = $wpdb->get_col("SHOW INDEX FROM $table_appointments WHERE Key_name = 'idx_created_by_type'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_appointments ADD INDEX idx_created_by_type (created_by_type)");
        }
        
        $indexes = $wpdb->get_col("SHOW INDEX FROM $table_appointments WHERE Key_name = 'idx_last_edited_by_type'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_appointments ADD INDEX idx_last_edited_by_type (last_edited_by_type)");
        }
        
        $indexes = $wpdb->get_col("SHOW INDEX FROM $table_appointments WHERE Key_name = 'idx_service_id'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table_appointments ADD INDEX idx_service_id (service_id)");
        }
        
        // Marchează actualizarea ca fiind completă
        update_option('clinica_tracking_updated', true);
        update_option('clinica_tracking_update_date', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Verifică dacă actualizarea pentru tracking a fost făcută
     */
    public static function is_tracking_updated() {
        return get_option('clinica_tracking_updated', false);
    }
    
    /**
     * Resetează actualizarea pentru tracking (pentru testare)
     */
    public static function reset_tracking_update() {
        delete_option('clinica_tracking_updated');
        delete_option('clinica_tracking_update_date');
    }
} 