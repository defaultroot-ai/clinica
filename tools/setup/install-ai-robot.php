<?php
/**
 * Script de instalare pentru Robotul Telefonic AI
 * Clinică Medicală
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiuni pentru a rula acest script.');
}

class ClinicaAIRobotInstaller {
    
    public function install() {
        echo "<h1>🤖 Instalare Robot Telefonic AI</h1>";
        echo "<p>Se instalează componentele necesare pentru robotul AI...</p>";
        
        try {
            // 1. Creează tabelele în baza de date
            $this->createTables();
            
            // 2. Creează directoarele necesare
            $this->createDirectories();
            
            // 3. Creează fișierele de configurare
            $this->createConfigFiles();
            
            // 4. Testează conectivitatea
            $this->testConnectivity();
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Instalare completă cu succes!</h3>";
            echo "<p>Robotul Telefonic AI a fost instalat cu succes.</p>";
            echo "</div>";
            
            echo "<h3>📋 Următorii pași:</h3>";
            echo "<ol>";
            echo "<li>Testați aparatura audio: <a href='/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html' target='_blank'>Test Aparatură</a></li>";
            echo "<li>Accesați robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
            echo "<li>Configurați setările în dashboard</li>";
            echo "</ol>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>❌ Eroare la instalare!</h3>";
            echo "<p>Eroare: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    private function createTables() {
        global $wpdb;
        
        echo "<h3>📊 Crearea tabelelor în baza de date...</h3>";
        
        // Citește fișierul SQL
        $sql_file = __DIR__ . '/create-ai-tables.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("Fișierul SQL nu a fost găsit: $sql_file");
        }
        
        $sql = file_get_contents($sql_file);
        
        // Împarte SQL-ul în comenzi separate
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($queries as $query) {
            if (empty($query)) continue;
            
            try {
                $result = $wpdb->query($query);
                if ($result !== false) {
                    $success_count++;
                } else {
                    $error_count++;
                    echo "<p style='color: #dc3545;'>❌ Eroare la executarea query: " . $wpdb->last_error . "</p>";
                }
            } catch (Exception $e) {
                $error_count++;
                echo "<p style='color: #dc3545;'>❌ Eroare: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p>✅ $success_count comenzi executate cu succes</p>";
        if ($error_count > 0) {
            echo "<p style='color: #dc3545;'>❌ $error_count erori</p>";
        }
    }
    
    private function createDirectories() {
        echo "<h3>📁 Crearea directoarelor...</h3>";
        
        $directories = [
            'public',
            'api',
            'includes',
            'assets/js',
            'assets/css',
            'logs'
        ];
        
        $base_path = __DIR__ . '/../../';
        
        foreach ($directories as $dir) {
            $full_path = $base_path . $dir;
            
            if (!file_exists($full_path)) {
                if (mkdir($full_path, 0755, true)) {
                    echo "<p>✅ Director creat: $dir</p>";
                } else {
                    echo "<p style='color: #dc3545;'>❌ Eroare la crearea directorului: $dir</p>";
                }
            } else {
                echo "<p>ℹ️ Director existent: $dir</p>";
            }
        }
    }
    
    private function createConfigFiles() {
        echo "<h3>⚙️ Crearea fișierelor de configurare...</h3>";
        
        // Creează fișierul .htaccess pentru API
        $htaccess_content = "RewriteEngine On\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess_content .= "RewriteRule ^(.*)$ index.php [L]\n\n";
        $htaccess_content .= "Header always set Access-Control-Allow-Origin \"*\"\n";
        $htaccess_content .= "Header always set Access-Control-Allow-Methods \"GET, POST, OPTIONS\"\n";
        $htaccess_content .= "Header always set Access-Control-Allow-Headers \"Content-Type\"\n";
        
        $htaccess_file = __DIR__ . '/../../api/.htaccess';
        file_put_contents($htaccess_file, $htaccess_content);
        echo "<p>✅ Fișier .htaccess creat pentru API</p>";
        
        // Creează fișierul de configurare pentru AI
        $config_content = "<?php\n";
        $config_content .= "/**\n";
        $config_content .= " * Configurare Robot Telefonic AI\n";
        $config_content .= " * Clinică Medicală\n";
        $config_content .= " */\n\n";
        $config_content .= "define('CLINICA_AI_ENABLED', true);\n";
        $config_content .= "define('CLINICA_AI_LANGUAGE', 'ro');\n";
        $config_content .= "define('CLINICA_AI_WORKING_HOURS_START', '08:30');\n";
        $config_content .= "define('CLINICA_AI_WORKING_HOURS_END', '19:30');\n";
        $config_content .= "define('CLINICA_AI_CONFIDENCE_THRESHOLD', 0.7);\n";
        $config_content .= "define('CLINICA_AI_MAX_CONVERSATION_LENGTH', 10);\n";
        
        $config_file = __DIR__ . '/../../includes/ai-config.php';
        file_put_contents($config_file, $config_content);
        echo "<p>✅ Fișier de configurare AI creat</p>";
    }
    
    private function testConnectivity() {
        echo "<h3>🌐 Testarea conectivității...</h3>";
        
        // Testează conexiunea la baza de date
        global $wpdb;
        $result = $wpdb->get_var("SELECT 1");
        if ($result) {
            echo "<p>✅ Conexiune la baza de date: OK</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ Eroare la conexiunea la baza de date</p>";
        }
        
        // Testează dacă tabelele au fost create
        $tables = [
            'wp_clinica_ai_identifications',
            'wp_clinica_webrtc_calls',
            'wp_clinica_webrtc_conversations',
            'wp_clinica_ai_conversations',
            'wp_clinica_ai_config'
        ];
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($exists) {
                echo "<p>✅ Tabel $table: OK</p>";
            } else {
                echo "<p style='color: #dc3545;'>❌ Tabel $table: NU EXISTĂ</p>";
            }
        }
        
        // Testează dacă fișierele există
        $files = [
            'public/phone-call.html',
            'api/identify-patient.php',
            'api/webrtc-offer.php',
            'tools/testing/test-audio-setup.html'
        ];
        
        foreach ($files as $file) {
            $full_path = __DIR__ . '/../../' . $file;
            if (file_exists($full_path)) {
                echo "<p>✅ Fișier $file: OK</p>";
            } else {
                echo "<p style='color: #dc3545;'>❌ Fișier $file: NU EXISTĂ</p>";
            }
        }
    }
    
    public function getStatus() {
        global $wpdb;
        
        $status = [];
        
        // Verifică tabelele
        $tables = [
            'wp_clinica_ai_identifications',
            'wp_clinica_webrtc_calls',
            'wp_clinica_webrtc_conversations',
            'wp_clinica_ai_conversations',
            'wp_clinica_ai_config'
        ];
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $status[$table] = $exists ? 'OK' : 'MISSING';
        }
        
        // Verifică fișierele
        $files = [
            'public/phone-call.html',
            'api/identify-patient.php',
            'api/webrtc-offer.php',
            'tools/testing/test-audio-setup.html'
        ];
        
        foreach ($files as $file) {
            $full_path = __DIR__ . '/../../' . $file;
            $status[$file] = file_exists($full_path) ? 'OK' : 'MISSING';
        }
        
        return $status;
    }
}

// Rulare instalare
if (isset($_GET['install']) && $_GET['install'] === 'true') {
    $installer = new ClinicaAIRobotInstaller();
    $installer->install();
} else {
    // Afișează status-ul
    $installer = new ClinicaAIRobotInstaller();
    $status = $installer->getStatus();
    
    echo "<h1>🤖 Status Robot Telefonic AI</h1>";
    echo "<p>Verifică starea instalării robotului AI:</p>";
    
    echo "<h3>📊 Tabele în baza de date:</h3>";
    foreach ($status as $component => $state) {
        if (strpos($component, 'wp_clinica_') === 0) {
            $color = $state === 'OK' ? '#28a745' : '#dc3545';
            $icon = $state === 'OK' ? '✅' : '❌';
            echo "<p style='color: $color;'>$icon $component: $state</p>";
        }
    }
    
    echo "<h3>📁 Fișiere:</h3>";
    foreach ($status as $component => $state) {
        if (strpos($component, 'wp_clinica_') !== 0) {
            $color = $state === 'OK' ? '#28a745' : '#dc3545';
            $icon = $state === 'OK' ? '✅' : '❌';
            echo "<p style='color: $color;'>$icon $component: $state</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='?install=true' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Instalează Robotul AI</a></p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
</style> 