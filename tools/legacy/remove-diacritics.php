<?php
/**
 * Script pentru eliminarea tuturor diacriticelor din clinica.php
 */

// Previne accesul direct
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

function remove_all_diacritics() {
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
    
    // Înlocuiri complete pentru toate diacriticele
    $replacements = [
        // Vocale cu diacritice
        'ă' => 'a', 'Ă' => 'A',
        'â' => 'a', 'Â' => 'A',
        'î' => 'i', 'Î' => 'I',
        'ș' => 's', 'Ș' => 'S',
        'ț' => 't', 'Ț' => 'T',
        
        // Caractere problematice din encoding
        'Ä' => 'a', 'Č' => 'C', 'Ă' => 'A',
        '›' => 'i', '™' => 'i', 'Ž' => 'Z',
        
        // Combinații specifice găsite în fișier
        'MedicalÄ' => 'Medicala',
        'medicalÄ' => 'medicala',
        'programÄri' => 'programari',
        'pacienČ›i' => 'pacienti',
        'medicale Č™i' => 'medicale si',
        'romĂ˘neČ™ti' => 'romanești',
        'strÄine' => 'straine',
        'DefineČ™te' => 'Defineste',
        'principalÄ' => 'principala',
        'InstanČ›a' => 'Instanta',
        'ReturneazÄ' => 'Returneaza',
        'IniČ›ializeazÄ' => 'Inițializeaza',
        'pacienČ›ilor' => 'pacientilor',
        'CNP Č™i' => 'CNP si',
        'parolÄ' => 'parola',
        'ĂŽncarcÄ' => 'Incarca',
        'CreeazÄ' => 'Creeaza',
        'ForČ›eazÄ' => 'Forteaza',
        'SeteazÄ' => 'Seteaza',
        'ObČ›ine' => 'Obtine',
        'recenČ›i' => 'recenti',
        'ĂŽnregistrÄ' => 'Inregistra',
        'programÄ' => 'programa',
        'IniČ›ializeazÄ' => 'Inițializeaza',
        
        // Alte combinații
        'Medicală' => 'Medicala',
        'medicală' => 'medicala',
        'programări' => 'programari',
        'pacienți' => 'pacienti',
        'medicale și' => 'medicale si',
        'românești' => 'romanești',
        'străine' => 'straine',
        'Definește' => 'Defineste',
        'principală' => 'principala',
        'Instanța' => 'Instanta',
        'Returnează' => 'Returneaza',
        'Inițializează' => 'Inițializeaza',
        'pacienților' => 'pacientilor',
        'CNP și' => 'CNP si',
        'parolă' => 'parola',
        'Încarcă' => 'Incarca',
        'Creează' => 'Creeaza',
        'Forțează' => 'Forteaza',
        'Setează' => 'Seteaza',
        'Obține' => 'Obtine',
        'recenți' => 'recenti',
        'Înregistră' => 'Inregistra',
        'programă' => 'programa',
        'Inițializează' => 'Inițializeaza',
        
        // Combinații cu caractere speciale
        'ă' => 'a', 'Ă' => 'A',
        'â' => 'a', 'Â' => 'A',
        'î' => 'i', 'Î' => 'I',
        'ș' => 's', 'Ș' => 'S',
        'ț' => 't', 'Ț' => 'T'
    ];
    
    $original_content = $content;
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    $changes = 0;
    foreach ($replacements as $old => $new) {
        $changes += substr_count($original_content, $old);
    }
    
    echo "🔧 Eliminat $changes diacritice\n";
    
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
    $backup_file = __DIR__ . '/clinica.php.backup-no-diacritics.' . date('Y-m-d-H-i-s');
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
        echo "   - Fără diacritice: " . (strpos($new_content, 'Medicala') !== false ? '✅ Da' : '❌ Nu') . "\n";
        
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
    echo "=== SCRIPT ELIMINARE DIAKRITICE ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (isset($argv[1]) && $argv[1] === 'check') {
        check_syntax();
    } else {
        if (remove_all_diacritics()) {
            echo "\n🎉 Eliminarea diacriticelor a fost finalizată cu succes!\n";
            echo "Recomandări:\n";
            echo "1. Verifică sintaxa: php remove-diacritics.php check\n";
            echo "2. Dezactivează și reactivează plugin-ul Clinica\n";
            echo "3. Verifică că nu mai apar probleme de encoding\n";
        } else {
            echo "\n❌ Eliminarea a eșuat!\n";
        }
    }
} else {
    // Execuție prin browser
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Eliminare Diacritice Clinica</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Eliminare Diacritice Clinica</h1>';
    
    ob_start();
    if (remove_all_diacritics()) {
        echo '<p class="success">✅ Eliminarea diacriticelor a fost finalizată cu succes!</p>';
    } else {
        echo '<p class="error">❌ Eliminarea a eșuat!</p>';
    }
    
    $output = ob_get_clean();
    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    
    echo '<h2>Recomandări:</h2>
    <ul>
        <li>Verifică sintaxa PHP</li>
        <li>Dezactivează și reactivează plugin-ul Clinica</li>
        <li>Verifică că nu mai apar probleme de encoding</li>
    </ul>
    </body>
    </html>';
}
?> 