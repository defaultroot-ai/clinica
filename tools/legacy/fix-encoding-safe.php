<?php
/**
 * Script sigur pentru corectarea diacriticelor fără să stric structura
 */

// Previne accesul direct
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

function fix_diacritics_safe() {
    $clinica_file = __DIR__ . '/clinica.php';
    
    if (!file_exists($clinica_file)) {
        echo "❌ Fișierul clinica.php nu există!\n";
        return false;
    }
    
    // Citește conținutul fișierului
    $content = file_get_contents($clinica_file);
    
    if ($content === false) {
        echo "❌ Nu s-a putut citi fișierul clinica.php!\n";
        return false;
    }
    
    echo "📖 Fișier citit cu succes. Dimensiune: " . strlen($content) . " bytes\n";
    
    // Corectează doar diacriticele problematice, păstrând structura
    $replacements = [
        'MedicalÄ' => 'Medicală',
        'medicalÄ' => 'medicală',
        'programÄri' => 'programări',
        'pacienČ›i' => 'pacienți',
        'medicale Č™i' => 'medicale și',
        'romĂ˘neČ™ti' => 'românești',
        'strÄine' => 'străine',
        'DefineČ™te' => 'Definește',
        'principalÄ' => 'principală',
        'InstanČ›a' => 'Instanța',
        'ReturneazÄ' => 'Returnează',
        'IniČ›ializeazÄ' => 'Inițializează',
        'pacienČ›ilor' => 'pacienților',
        'CNP Č™i' => 'CNP și',
        'parolÄ' => 'parolă',
        'ĂŽncarcÄ' => 'Încarcă',
        'CreeazÄ' => 'Creează',
        'ForČ›eazÄ' => 'Forțează',
        'SeteazÄ' => 'Setează',
        'ObČ›ine' => 'Obține',
        'recenČ›i' => 'recenți',
        'ĂŽnregistrÄ' => 'Înregistră',
        'programÄ' => 'programă',
        'IniČ›ializeazÄ' => 'Inițializează'
    ];
    
    $original_content = $content;
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    $changes = 0;
    foreach ($replacements as $old => $new) {
        $changes += substr_count($original_content, $old);
    }
    
    echo "🔧 Corectat $changes caractere problematice\n";
    
    // Verifică că fișierul încă începe cu <?php
    if (substr($content, 0, 5) !== '<?php') {
        echo "❌ Fișierul nu mai începe cu <?php - se anulează modificarea!\n";
        return false;
    }
    
    // Verifică că fișierul se termină corect
    if (strpos($content, 'Clinica_Plugin::get_instance();') === false) {
        echo "❌ Fișierul nu se termină corect - se anulează modificarea!\n";
        return false;
    }
    
    // Creează backup
    $backup_file = __DIR__ . '/clinica.php.backup-safe.' . date('Y-m-d-H-i-s');
    if (copy($clinica_file, $backup_file)) {
        echo "💾 Backup creat: " . basename($backup_file) . "\n";
    }
    
    // Salvează fișierul
    if (file_put_contents($clinica_file, $content)) {
        echo "✅ Fișier salvat cu succes!\n";
        
        // Verifică rezultatul
        $new_content = file_get_contents($clinica_file);
        echo "🔍 Verificare finală:\n";
        echo "   - Începe cu <?php: " . (substr($new_content, 0, 5) === '<?php' ? '✅ Da' : '❌ Nu') . "\n";
        echo "   - Se termină corect: " . (strpos($new_content, 'Clinica_Plugin::get_instance();') !== false ? '✅ Da' : '❌ Nu') . "\n";
        echo "   - Diacritice corecte: " . (strpos($new_content, 'Medicală') !== false ? '✅ Da' : '❌ Nu') . "\n";
        
        return true;
    } else {
        echo "❌ Nu s-a putut salva fișierul!\n";
        return false;
    }
}

function check_syntax() {
    $clinica_file = __DIR__ . '/clinica.php';
    
    if (!file_exists($clinica_file)) {
        echo "❌ Fișierul clinica.php nu există!\n";
        return false;
    }
    
    // Verifică sintaxa PHP
    $output = shell_exec('php -l "' . $clinica_file . '" 2>&1');
    
    echo "=== VERIFICARE SINTAXĂ PHP ===\n";
    echo $output;
    
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Sintaxa PHP este corectă!\n";
        return true;
    } else {
        echo "❌ Erori de sintaxă PHP!\n";
        return false;
    }
}

// Execută scriptul
if (php_sapi_name() === 'cli') {
    echo "=== SCRIPT CORECTARE DIAKRITICE SIGUR ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (isset($argv[1]) && $argv[1] === 'check') {
        check_syntax();
    } else {
        if (fix_diacritics_safe()) {
            echo "\n🎉 Corectarea diacriticelor a fost finalizată cu succes!\n";
            echo "Recomandări:\n";
            echo "1. Verifică sintaxa: php fix-encoding-safe.php check\n";
            echo "2. Dezactivează și reactivează plugin-ul Clinica\n";
            echo "3. Verifică că diacriticele se afișează corect\n";
        } else {
            echo "\n❌ Corectarea a eșuat!\n";
        }
    }
} else {
    // Execuție prin browser
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Corectare Diacritice Sigur Clinica</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Corectare Diacritice Sigur Clinica</h1>';
    
    ob_start();
    if (fix_diacritics_safe()) {
        echo '<p class="success">✅ Corectarea diacriticelor a fost finalizată cu succes!</p>';
    } else {
        echo '<p class="error">❌ Corectarea a eșuat!</p>';
    }
    
    $output = ob_get_clean();
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    
    echo '<h2>Recomandări:</h2>
    <ul>
        <li>Verifică sintaxa PHP</li>
        <li>Dezactivează și reactivează plugin-ul Clinica</li>
        <li>Verifică că diacriticele se afișează corect</li>
    </ul>
    </body>
    </html>';
}
?> 