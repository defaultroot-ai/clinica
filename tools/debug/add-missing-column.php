<?php
/**
 * Script pentru adăugarea coloanei lipsă setting_description
 */

// Încarcă WordPress
require_once('C:/xampp8.2.12/htdocs/plm/wp-load.php');

global $wpdb;

echo "=== ADĂUGARE COLOANĂ LIPSĂ ===\n\n";

$table_name = $wpdb->prefix . 'clinica_settings';

// Verifică dacă lipsește coloana setting_description
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

if (!in_array('setting_description', $column_names)) {
    echo "❌ Coloana 'setting_description' lipsește!\n";
    echo "Se adaugă coloana...\n";
    
    $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN setting_description TEXT AFTER setting_label");
    if ($result !== false) {
        echo "✅ Coloana 'setting_description' a fost adăugată!\n";
        
        // Actualizează setările existente cu descrieri
        $default_descriptions = array(
            'clinic_name' => 'Numele clinicii afișat în aplicație',
            'clinic_email' => 'Adresa de email a clinicii',
            'clinic_phone' => 'Numărul de telefon al clinicii',
            'clinic_website' => 'Website-ul clinicii',
            'clinic_address' => 'Adresa completă a clinicii',
            'clinic_logo' => 'Logo-ul clinicii',
            'working_hours' => 'Programul de lucru al clinicii',
            'email_from_name' => 'Numele care apare ca expeditor în emailuri',
            'email_from_address' => 'Adresa de email care apare ca expeditor',
            'email_smtp_host' => 'Adresa serverului SMTP',
            'email_smtp_port' => 'Portul serverului SMTP',
            'email_smtp_username' => 'Numele de utilizator pentru SMTP',
            'email_smtp_password' => 'Parola pentru SMTP',
            'email_smtp_encryption' => 'Tipul de criptare SMTP (tls, ssl, none)',
            'appointment_duration' => 'Durata standard a unei programări în minute',
            'appointment_interval' => 'Intervalul minim între programări',
            'appointment_advance_days' => 'Numărul de zile în avans pentru care se pot face programări',
            'notifications_enabled' => 'Activează sistemul de notificări',
            'reminder_days' => 'Numărul de zile înainte de programare pentru trimiterea reamintirii',
            'confirmation_required' => 'Necesită confirmarea programărilor',
            'session_timeout' => 'Durata sesiunii în secunde',
            'login_attempts' => 'Numărul maxim de încercări de login',
            'lockout_duration' => 'Durata blocării după prea multe încercări',
            'items_per_page' => 'Numărul de elemente afișate pe pagină',
            'cache_enabled' => 'Activează sistemul de cache',
            'auto_refresh' => 'Intervalul de auto-refresh în secunde'
        );
        
        foreach ($default_descriptions as $key => $description) {
            $wpdb->update(
                $table_name,
                array('setting_description' => $description),
                array('setting_key' => $key),
                array('%s'),
                array('%s')
            );
        }
        
        echo "✅ Descrierile au fost adăugate pentru setările existente!\n";
    } else {
        echo "❌ Eroare la adăugarea coloanei: " . $wpdb->last_error . "\n";
    }
} else {
    echo "✅ Coloana 'setting_description' există deja.\n";
}

// Verifică din nou structura
echo "\n=== VERIFICARE FINALĂ ===\n";
$final_columns = $wpdb->get_results("DESCRIBE $table_name");
echo "Structura finală a tabelului:\n";
foreach ($final_columns as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}

echo "\n=== REPARARE COMPLETĂ ===\n";
echo "✅ Coloana setting_description a fost adăugată!\n";
?> 