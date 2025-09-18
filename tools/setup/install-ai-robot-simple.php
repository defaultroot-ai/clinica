<?php
/**
 * Script de instalare simplificat pentru Robotul Telefonic AI
 * Clinică Medicală
 */

class ClinicaAIRobotSimpleInstaller {
    
    public function install() {
        echo "<h1>🤖 Instalare Robot Telefonic AI</h1>";
        echo "<p>Se instalează componentele necesare pentru robotul AI...</p>";
        
        try {
            // 1. Creează directoarele necesare
            $this->createDirectories();
            
            // 2. Creează fișierele de configurare
            $this->createConfigFiles();
            
            // 3. Testează conectivitatea
            $this->testConnectivity();
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Instalare completă cu succes!</h3>";
            echo "<p>Robotul Telefonic AI a fost instalat cu succes.</p>";
            echo "</div>";
            
            echo "<h3>📋 Următorii pași:</h3>";
            echo "<ol>";
            echo "<li>Testați aparatura audio: <a href='/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html' target='_blank'>Test Aparatură</a></li>";
            echo "<li>Accesați robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
            echo "<li>Rulați scriptul SQL pentru a crea tabelele în baza de date</li>";
            echo "</ol>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>❌ Eroare la instalare!</h3>";
            echo "<p>Eroare: " . $e->getMessage() . "</p>";
            echo "</div>";
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
        
        // Testează dacă directoarele există
        $directories = [
            'public',
            'api',
            'includes',
            'assets/js',
            'assets/css',
            'logs'
        ];
        
        foreach ($directories as $dir) {
            $full_path = __DIR__ . '/../../' . $dir;
            if (is_dir($full_path)) {
                echo "<p>✅ Director $dir: OK</p>";
            } else {
                echo "<p style='color: #dc3545;'>❌ Director $dir: NU EXISTĂ</p>";
            }
        }
    }
    
    public function getStatus() {
        $status = [];
        
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
        
        // Verifică directoarele
        $directories = [
            'public',
            'api',
            'includes',
            'assets/js',
            'assets/css',
            'logs'
        ];
        
        foreach ($directories as $dir) {
            $full_path = __DIR__ . '/../../' . $dir;
            $status[$dir] = is_dir($full_path) ? 'OK' : 'MISSING';
        }
        
        return $status;
    }
}

// Rulare instalare
if (isset($_GET['install']) && $_GET['install'] === 'true') {
    $installer = new ClinicaAIRobotSimpleInstaller();
    $installer->install();
} else {
    // Afișează status-ul
    $installer = new ClinicaAIRobotSimpleInstaller();
    $status = $installer->getStatus();
    
    echo "<h1>🤖 Status Robot Telefonic AI</h1>";
    echo "<p>Verifică starea instalării robotului AI:</p>";
    
    echo "<h3>📁 Fișiere și Directoare:</h3>";
    foreach ($status as $component => $state) {
        $color = $state === 'OK' ? '#28a745' : '#dc3545';
        $icon = $state === 'OK' ? '✅' : '❌';
        echo "<p style='color: $color;'>$icon $component: $state</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='?install=true' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Instalează Robotul AI</a></p>";
    
    echo "<h3>📋 Pași pentru completarea instalării:</h3>";
    echo "<ol>";
    echo "<li>Rulați scriptul SQL pentru a crea tabelele în baza de date</li>";
    echo "<li>Testați aparatura audio: <a href='/plm/wp-content/plugins/clinica/tools/testing/test-audio-setup.html' target='_blank'>Test Aparatură</a></li>";
    echo "<li>Accesați robotul AI: <a href='/plm/wp-content/plugins/clinica/public/phone-call.html' target='_blank'>Robot AI</a></li>";
    echo "</ol>";
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