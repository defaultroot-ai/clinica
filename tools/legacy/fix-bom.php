<?php
/**
 * Script pentru eliminarea BOM-ului și corectarea fișierului clinica.php
 */

// Previne accesul direct
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

function fix_bom_issue() {
    $clinica_file = __DIR__ . '/clinica.php';
    
    if (!file_exists($clinica_file)) {
        echo "❌ Fișierul clinica.php nu există!\n";
        return false;
    }
    
    // Citește conținutul fișierului ca bytes
    $content = file_get_contents($clinica_file);
    
    if ($content === false) {
        echo "❌ Nu s-a putut citi fișierul clinica.php!\n";
        return false;
    }
    
    echo "📖 Fișier citit cu succes. Dimensiune: " . strlen($content) . " bytes\n";
    
    // Verifică dacă începe cu BOM UTF-8
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "⚠️  Detectat BOM UTF-8 - se elimină...\n";
        $content = substr($content, 3);
    }
    
    // Verifică dacă începe cu <?php
    if (substr($content, 0, 5) !== '<?php') {
        echo "⚠️  Fișierul nu începe cu <?php - se corectează...\n";
        
        // Elimină orice caractere invizibile de la început
        $content = ltrim($content);
        
        // Verifică din nou
        if (substr($content, 0, 5) !== '<?php') {
            echo "❌ Nu s-a putut găsi <?php la început!\n";
            return false;
        }
    }
    
    // Verifică dacă există caractere invizibile după <?php
    $php_start = strpos($content, '<?php');
    if ($php_start !== 0) {
        echo "⚠️  Caractere invizibile înainte de <?php - se elimină...\n";
        $content = substr($content, $php_start);
    }
    
    // Verifică dacă există spații sau caractere invizibile după <?php
    $after_php = substr($content, 5);
    if (preg_match('/^\s+/', $after_php)) {
        echo "⚠️  Spații înainte de primul caracter - se elimină...\n";
        $content = '<?php' . ltrim($after_php);
    }
    
    // Creează backup
    $backup_file = __DIR__ . '/clinica.php.backup-bom.' . date('Y-m-d-H-i-s');
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
        echo "   - Nu are BOM: " . (substr($new_content, 0, 3) !== "\xEF\xBB\xBF" ? '✅ Da' : '❌ Nu') . "\n";
        echo "   - Nu are spații înainte: " . (substr($new_content, 0, 5) === '<?php' ? '✅ Da' : '❌ Nu') . "\n";
        
        return true;
    } else {
        echo "❌ Nu s-a putut salva fișierul!\n";
        return false;
    }
}

function check_file_structure() {
    $clinica_file = __DIR__ . '/clinica.php';
    
    if (!file_exists($clinica_file)) {
        echo "❌ Fișierul clinica.php nu există!\n";
        return;
    }
    
    $content = file_get_contents($clinica_file);
    
    echo "=== VERIFICARE STRUCTURĂ FIȘIER ===\n";
    echo "Fișier: clinica.php\n";
    echo "Dimensiune: " . strlen($content) . " bytes\n";
    
    // Verifică BOM
    $has_bom = substr($content, 0, 3) === "\xEF\xBB\xBF";
    echo "BOM UTF-8: " . ($has_bom ? '❌ DA (PROBLEMĂ!)' : '✅ Nu') . "\n";
    
    // Verifică începutul
    $starts_with_php = substr($content, 0, 5) === '<?php';
    echo "Începe cu <?php: " . ($starts_with_php ? '✅ Da' : '❌ Nu') . "\n";
    
    // Verifică caracterele primele 20 de bytes
    echo "Primele 20 de bytes (hex): ";
    for ($i = 0; $i < min(20, strlen($content)); $i++) {
        printf("%02X ", ord($content[$i]));
    }
    echo "\n";
    
    // Verifică caracterele primele 20 de caractere
    echo "Primele 20 de caractere: " . htmlspecialchars(substr($content, 0, 20)) . "\n";
    
    // Verifică dacă există caractere invizibile
    $invisible_chars = 0;
    for ($i = 0; $i < strlen($content); $i++) {
        $char = $content[$i];
        if (ord($char) < 32 && ord($char) !== 9 && ord($char) !== 10 && ord($char) !== 13) {
            $invisible_chars++;
        }
    }
    echo "Caractere invizibile: " . $invisible_chars . "\n";
}

// Execută scriptul
if (php_sapi_name() === 'cli') {
    echo "=== SCRIPT ELIMINARE BOM ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (isset($argv[1]) && $argv[1] === 'check') {
        check_file_structure();
    } else {
        if (fix_bom_issue()) {
            echo "\n🎉 Problema cu BOM-ul a fost rezolvată!\n";
            echo "Recomandări:\n";
            echo "1. Dezactivează și reactivează plugin-ul Clinica\n";
            echo "2. Verifică că nu mai apar erori de activare\n";
            echo "3. Testează funcționalitatea plugin-ului\n";
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
        <title>Eliminare BOM Clinica</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Eliminare BOM Clinica</h1>';
    
    ob_start();
    if (fix_bom_issue()) {
        echo '<p class="success">✅ Problema cu BOM-ul a fost rezolvată!</p>';
    } else {
        echo '<p class="error">❌ Corectarea a eșuat!</p>';
    }
    
    $output = ob_get_clean();
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    
    echo '<h2>Recomandări:</h2>
    <ul>
        <li>Dezactivează și reactivează plugin-ul Clinica</li>
        <li>Verifică că nu mai apar erori de activare</li>
        <li>Testează funcționalitatea plugin-ului</li>
    </ul>
    </body>
    </html>';
}
?> 