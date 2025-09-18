<?php
/**
 * Script pentru corectarea problemelor de encoding din clinica.php
 */

// Previne accesul direct
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Funcție pentru a corecta encoding-ul
function fix_encoding_issues() {
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
    
    // Detectează encoding-ul
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    echo "🔍 Encoding detectat: " . ($encoding ?: 'Necunoscut') . "\n";
    
    // Convertește la UTF-8 dacă nu este deja
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        echo "🔄 Convertis din $encoding la UTF-8\n";
    }
    
    // Corectează caracterele problematice
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
        'ĂŽncarcÄ' => 'Încarcă',
        'clasele principale' => 'clasele principale',
        'personalizate' => 'personalizate',
        'tabelelor' => 'tabelelor',
        'necesare' => 'necesare',
        'versiunea' => 'versiunea',
        'Dashboard Pacient' => 'Dashboard Pacient',
        'Dashboard Doctor' => 'Dashboard Doctor',
        'Dashboard Asistent' => 'Dashboard Asistent',
        'Dashboard Manager' => 'Dashboard Manager',
        'Dashboard Receptionist' => 'Dashboard Receptionist'
    ];
    
    $original_content = $content;
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    $changes = substr_count($original_content, 'Ä') + substr_count($original_content, 'Č') + substr_count($original_content, 'Ă');
    echo "🔧 Corectat $changes caractere problematice\n";
    
    // Adaugă BOM UTF-8 dacă nu există
    if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
        $content = "\xEF\xBB\xBF" . $content;
        echo "📝 Adăugat BOM UTF-8\n";
    }
    
    // Salvează fișierul
    $backup_file = __DIR__ . '/clinica.php.backup.' . date('Y-m-d-H-i-s');
    if (copy($clinica_file, $backup_file)) {
        echo "💾 Backup creat: " . basename($backup_file) . "\n";
    }
    
    if (file_put_contents($clinica_file, $content)) {
        echo "✅ Fișier salvat cu succes!\n";
        
        // Verifică rezultatul
        $new_content = file_get_contents($clinica_file);
        $new_encoding = mb_detect_encoding($new_content, ['UTF-8'], true);
        echo "🔍 Encoding final: " . ($new_encoding ?: 'Necunoscut') . "\n";
        
        // Testează diacriticele
        if (strpos($new_content, 'Medicală') !== false && strpos($new_content, 'pacienți') !== false) {
            echo "✅ Diacriticele românești se afișează corect!\n";
            return true;
        } else {
            echo "❌ Probleme persistă cu diacriticele!\n";
            return false;
        }
    } else {
        echo "❌ Nu s-a putut salva fișierul!\n";
        return false;
    }
}

// Funcție pentru a verifica encoding-ul
function check_encoding() {
    $clinica_file = __DIR__ . '/clinica.php';
    
    if (!file_exists($clinica_file)) {
        echo "❌ Fișierul clinica.php nu există!\n";
        return;
    }
    
    $content = file_get_contents($clinica_file);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    echo "=== VERIFICARE ENCODING ===\n";
    echo "Fișier: clinica.php\n";
    echo "Encoding detectat: " . ($encoding ?: 'Necunoscut') . "\n";
    echo "BOM UTF-8: " . (substr($content, 0, 3) === "\xEF\xBB\xBF" ? 'Da' : 'Nu') . "\n";
    echo "Dimensiune: " . strlen($content) . " bytes\n";
    
    // Verifică caracterele problematice
    $problem_chars = ['Ä', 'Č', 'Ă', '›', '™'];
    foreach ($problem_chars as $char) {
        $count = substr_count($content, $char);
        if ($count > 0) {
            echo "⚠️  Caracter problematic '$char': $count apariții\n";
        }
    }
    
    // Verifică diacriticele corecte
    $correct_chars = ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'];
    foreach ($correct_chars as $char) {
        $count = substr_count($content, $char);
        if ($count > 0) {
            echo "✅ Caracter corect '$char': $count apariții\n";
        }
    }
    
    // Afișează primele 200 de caractere
    echo "\n=== PRIMELE 200 DE CARACTERE ===\n";
    echo htmlspecialchars(substr($content, 0, 200)) . "\n";
}

// Execută scriptul
if (php_sapi_name() === 'cli') {
    echo "=== SCRIPT CORECTARE ENCODING ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (isset($argv[1]) && $argv[1] === 'check') {
        check_encoding();
    } else {
        if (fix_encoding_issues()) {
            echo "\n🎉 Corectarea encoding-ului a fost finalizată cu succes!\n";
            echo "Recomandări:\n";
            echo "1. Dezactivează și reactivează plugin-ul Clinica\n";
            echo "2. Verifică că diacriticele se afișează corect în WordPress\n";
            echo "3. Testează toate formularele și dashboard-urile\n";
        } else {
            echo "\n❌ Corectarea encoding-ului a eșuat!\n";
        }
    }
} else {
    // Execuție prin browser
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Corectare Encoding Clinica</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Corectare Encoding Clinica</h1>';
    
    ob_start();
    if (fix_encoding_issues()) {
        echo '<p class="success">✅ Corectarea encoding-ului a fost finalizată cu succes!</p>';
    } else {
        echo '<p class="error">❌ Corectarea encoding-ului a eșuat!</p>';
    }
    
    $output = ob_get_clean();
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    
    echo '<h2>Recomandări:</h2>
    <ul>
        <li>Dezactivează și reactivează plugin-ul Clinica</li>
        <li>Verifică că diacriticele se afișează corect în WordPress</li>
        <li>Testează toate formularele și dashboard-urile</li>
    </ul>
    </body>
    </html>';
}
?> 