<?php
/**
 * Script pentru crearea tabelului wp_clinica_settings
 * Clinică Medicală
 */

// Configurare baza de date
$db_host = 'localhost';
$db_name = 'plm';
$db_user = 'root';
$db_pass = '';

echo "<h1>⚙️ Creare Tabel Setări Clinică</h1>";

// Conectare la baza de date
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "<p style='color: #28a745;'>✅ Conectare la baza de date: OK</p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Eroare la conectare!</h3>";
    echo "<p>Eroare: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// Verifică dacă tabelul există deja
echo "<h3>🔍 Verificare tabel existent</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'wp_clinica_settings'");
    $result = $stmt->fetchAll();
    $table_exists = count($result) > 0;
    
    if ($table_exists) {
        echo "<p style='color: #ffc107;'>⚠️ Tabelul wp_clinica_settings există deja!</p>";
        echo "<p>Vrei să continui cu inserarea setărilor implicite?</p>";
    } else {
        echo "<p style='color: #28a745;'>✅ Tabelul wp_clinica_settings nu există - va fi creat</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificare: " . $e->getMessage() . "</p>";
    exit;
}

// SQL pentru crearea tabelului
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `wp_clinica_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `setting_group` varchar(100) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// SQL pentru inserarea setărilor implicite
$insert_settings_sql = "
INSERT IGNORE INTO `wp_clinica_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `is_public`) VALUES
-- Informații clinică
('clinic_name', 'Clinică Medicală', 'string', 'clinic', 1),
('clinic_address', 'Strada Exemplu, Nr. 123, București', 'string', 'clinic', 1),
('clinic_phone', '+40 21 123 4567', 'string', 'clinic', 1),
('clinic_email', 'contact@clinica.ro', 'string', 'clinic', 1),
('clinic_website', 'https://clinica.ro', 'string', 'clinic', 1),
('clinic_logo', '', 'string', 'clinic', 1),
('working_hours', 'Luni-Vineri: 8:00-18:00, Sâmbătă: 8:00-14:00', 'string', 'clinic', 1),

-- Configurare email
('email_from_name', 'Clinică Medicală', 'string', 'email', 0),
('email_from_address', 'noreply@clinica.ro', 'string', 'email', 0),
('email_smtp_host', 'smtp.gmail.com', 'string', 'email', 0),
('email_smtp_port', '587', 'string', 'email', 0),
('email_smtp_username', '', 'string', 'email', 0),
('email_smtp_password', '', 'string', 'email', 0),
('email_smtp_encryption', 'tls', 'string', 'email', 0),

-- Configurare programări
('appointment_duration', '30', 'integer', 'appointments', 0),
('appointment_interval', '15', 'integer', 'appointments', 0),
('appointment_advance_days', '30', 'integer', 'appointments', 0),

-- Configurare notificări
('notifications_enabled', '1', 'boolean', 'notifications', 0),
('reminder_days', '1,3', 'string', 'notifications', 0),
('confirmation_required', '1', 'boolean', 'notifications', 0),

-- Configurare securitate
('session_timeout', '3600', 'integer', 'security', 0),
('login_attempts', '5', 'integer', 'security', 0),
('lockout_duration', '900', 'integer', 'security', 0),

-- Configurare interfață
('items_per_page', '20', 'integer', 'interface', 0),
('cache_enabled', '1', 'boolean', 'interface', 0),
('auto_refresh', '0', 'boolean', 'interface', 0);
";

// Execută crearea tabelului
echo "<h3>🔨 Creare tabel</h3>";
try {
    $stmt = $pdo->prepare($create_table_sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "<p style='color: #28a745;'>✅ Tabelul wp_clinica_settings a fost creat cu succes!</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ Eroare la crearea tabelului</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la crearea tabelului: " . $e->getMessage() . "</p>";
}

// Execută inserarea setărilor
echo "<h3>📝 Inserare setări implicite</h3>";
try {
    $stmt = $pdo->prepare($insert_settings_sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "<p style='color: #28a745;'>✅ Setările implicite au fost inserate cu succes!</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ Eroare la inserarea setărilor</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la inserarea setărilor: " . $e->getMessage() . "</p>";
}

// Verifică rezultatul final
echo "<h3>✅ Verificare finală</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM wp_clinica_settings");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $settings_count = $result['count'];
    
    echo "<p style='color: #28a745;'>✅ Tabelul wp_clinica_settings conține $settings_count setări</p>";
    
    // Afișează primele 5 setări
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM wp_clinica_settings LIMIT 5");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($settings) > 0) {
        echo "<h4>📋 Primele setări:</h4>";
        echo "<ul>";
        foreach ($settings as $setting) {
            echo "<li><strong>{$setting['setting_key']}</strong>: {$setting['setting_value']}</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: #dc3545;'>❌ Eroare la verificarea finală: " . $e->getMessage() . "</p>";
}

echo "<h3>🎉 Instalare completă!</h3>";
echo "<p>Tabelul wp_clinica_settings a fost creat și configurat cu succes.</p>";
echo "<p>Acum pluginul clinicii ar trebui să funcționeze fără erori.</p>";

echo "<h3>📋 Următorii pași:</h3>";
echo "<ol>";
echo "<li>Reîncarcă pagina WordPress pentru a verifica că erorile au dispărut</li>";
echo "<li>Testează robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
echo "<li>Verifică că pluginul clinicii funcționează corect</li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h3, h4 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    margin: 10px 0;
    padding-left: 20px;
}
</style> 