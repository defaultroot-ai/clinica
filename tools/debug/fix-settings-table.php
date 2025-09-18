<?php
/**
 * Script pentru repararea tabelului de setări
 */

// Încarcă WordPress
require_once('C:/xampp8.2.12/htdocs/plm/wp-load.php');

global $wpdb;

echo "=== VERIFICARE ȘI REPARARE TABEL SETĂRI ===\n\n";

// 1. Verifică dacă tabelul există
$table_name = $wpdb->prefix . 'clinica_settings';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if (!$table_exists) {
    echo "❌ Tabelul $table_name nu există!\n";
    echo "Se creează tabelul...\n";
    
    // Creează tabelul
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $sql = "CREATE TABLE $table_name (
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
    ) " . $wpdb->get_charset_collate();
    
    $result = dbDelta($sql);
    echo "✅ Tabelul a fost creat!\n";
} else {
    echo "✅ Tabelul $table_name există.\n";
}

// 2. Verifică structura tabelului
echo "\n=== VERIFICARE STRUCTURĂ ===\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

echo "Coloane existente:\n";
foreach ($column_names as $column) {
    echo "- $column\n";
}

// Verifică dacă lipsește setting_label
if (!in_array('setting_label', $column_names)) {
    echo "\n❌ Coloana 'setting_label' lipsește!\n";
    echo "Se adaugă coloana...\n";
    
    $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN setting_label VARCHAR(255) AFTER setting_group");
    if ($result !== false) {
        echo "✅ Coloana 'setting_label' a fost adăugată!\n";
    } else {
        echo "❌ Eroare la adăugarea coloanei: " . $wpdb->last_error . "\n";
    }
} else {
    echo "\n✅ Coloana 'setting_label' există.\n";
}

// 3. Verifică dacă există setări
echo "\n=== VERIFICARE SETĂRI ===\n";
$settings_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "Număr de setări în baza de date: $settings_count\n";

if ($settings_count == 0) {
    echo "\n❌ Nu există setări în baza de date!\n";
    echo "Se adaugă setările implicite...\n";
    
    // Adaugă setările implicite
    $default_settings = array(
        // Setări clinică
        array('clinic_name', 'Clinica Mea', 'text', 'clinic', 'Numele clinicii', 'Numele clinicii afișat în aplicație'),
        array('clinic_email', 'contact@clinica.ro', 'text', 'clinic', 'Email clinică', 'Adresa de email a clinicii'),
        array('clinic_phone', '+40 123 456 789', 'text', 'clinic', 'Telefon clinică', 'Numărul de telefon al clinicii'),
        array('clinic_website', 'https://clinica.ro', 'text', 'clinic', 'Website clinică', 'Website-ul clinicii'),
        array('clinic_address', 'Strada Exemplu, Nr. 123, București', 'textarea', 'clinic', 'Adresa clinicii', 'Adresa completă a clinicii'),
        array('clinic_logo', '', 'file', 'clinic', 'Logo clinică', 'Logo-ul clinicii'),
        array('working_hours', '{"monday":{"start":"09:00","end":"17:00","enabled":true},"tuesday":{"start":"09:00","end":"17:00","enabled":true},"wednesday":{"start":"09:00","end":"17:00","enabled":true},"thursday":{"start":"09:00","end":"17:00","enabled":true},"friday":{"start":"09:00","end":"17:00","enabled":true},"saturday":{"start":"09:00","end":"13:00","enabled":false},"sunday":{"start":"00:00","end":"00:00","enabled":false}}', 'json', 'schedule', 'Program de lucru', 'Programul de lucru al clinicii'),
        
        // Setări email
        array('email_from_name', 'Clinica Mea', 'text', 'email', 'Nume expeditor email', 'Numele care apare ca expeditor în emailuri'),
        array('email_from_address', 'noreply@clinica.ro', 'text', 'email', 'Adresa expeditor email', 'Adresa de email care apare ca expeditor'),
        array('email_smtp_host', '', 'text', 'email', 'Server SMTP', 'Adresa serverului SMTP'),
        array('email_smtp_port', '587', 'number', 'email', 'Port SMTP', 'Portul serverului SMTP'),
        array('email_smtp_username', '', 'text', 'email', 'Username SMTP', 'Numele de utilizator pentru SMTP'),
        array('email_smtp_password', '', 'text', 'email', 'Parolă SMTP', 'Parola pentru SMTP'),
        array('email_smtp_encryption', 'tls', 'text', 'email', 'Criptare SMTP', 'Tipul de criptare SMTP (tls, ssl, none)'),
        
        // Setări programări
        array('appointment_duration', '30', 'number', 'appointments', 'Durată programare (minute)', 'Durata standard a unei programări în minute'),
        array('appointment_interval', '15', 'number', 'appointments', 'Interval programări (minute)', 'Intervalul minim între programări'),
        array('appointment_advance_days', '30', 'number', 'appointments', 'Zile în avans pentru programări', 'Numărul de zile în avans pentru care se pot face programări'),
        
        // Setări notificări
        array('notifications_enabled', '1', 'boolean', 'notifications', 'Notificări activate', 'Activează sistemul de notificări'),
        array('reminder_days', '1', 'number', 'notifications', 'Zile înainte de reamintire', 'Numărul de zile înainte de programare pentru trimiterea reamintirii'),
        array('confirmation_required', '1', 'boolean', 'notifications', 'Confirmare obligatorie', 'Necesită confirmarea programărilor'),
        
        // Setări securitate
        array('session_timeout', '3600', 'number', 'security', 'Timeout sesiune (secunde)', 'Durata sesiunii în secunde'),
        array('login_attempts', '5', 'number', 'security', 'Încercări de login', 'Numărul maxim de încercări de login'),
        array('lockout_duration', '900', 'number', 'security', 'Durată blocare (secunde)', 'Durata blocării după prea multe încercări'),
        
        // Setări performanță
        array('items_per_page', '20', 'number', 'performance', 'Elemente pe pagină', 'Numărul de elemente afișate pe pagină'),
        array('cache_enabled', '1', 'boolean', 'performance', 'Cache activat', 'Activează sistemul de cache'),
        array('auto_refresh', '300', 'number', 'performance', 'Auto-refresh (secunde)', 'Intervalul de auto-refresh în secunde')
    );
    
    foreach ($default_settings as $setting) {
        $wpdb->insert(
            $table_name,
            array(
                'setting_key' => $setting[0],
                'setting_value' => $setting[1],
                'setting_type' => $setting[2],
                'setting_group' => $setting[3],
                'setting_label' => $setting[4],
                'setting_description' => $setting[5],
                'is_public' => false
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
    }
    
    echo "✅ Setările implicite au fost adăugate!\n";
} else {
    echo "\n✅ Există setări în baza de date.\n";
}

// 4. Verifică din nou structura
echo "\n=== VERIFICARE FINALĂ ===\n";
$final_columns = $wpdb->get_results("DESCRIBE $table_name");
echo "Structura finală a tabelului:\n";
foreach ($final_columns as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}

echo "\n=== REPARARE COMPLETĂ ===\n";
echo "✅ Toate problemele au fost rezolvate!\n";
?> 