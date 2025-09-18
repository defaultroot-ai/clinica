<?php
/**
 * Script pentru monitorizarea log-urilor PHP în timp real
 */

echo "🔍 Monitorizare log-uri PHP pentru debug-ul extensiv...\n";
echo "Accesează pagina de setări și modifică orele pentru a vedea debug-ul\n\n";

// Calea către log-ul PHP
$log_file = 'C:/xampp8.2.12/php/logs/php_error_log';

if (!file_exists($log_file)) {
    echo "❌ Nu s-a găsit log-ul PHP la: $log_file\n";
    echo "Încearcă să găsești log-ul în:\n";
    echo "- C:/xampp8.2.12/apache/logs/error.log\n";
    echo "- C:/xampp8.2.12/apache/logs/php_error.log\n";
    exit;
}

echo "✅ Log-ul găsit la: $log_file\n";
echo "Monitorizare în timp real... (Ctrl+C pentru oprire)\n\n";

// Monitorizează log-ul în timp real
$handle = fopen($log_file, 'r');
if ($handle) {
    // Mergi la sfârșitul fișierului
    fseek($handle, 0, SEEK_END);
    
    while (true) {
        $line = fgets($handle);
        if ($line !== false) {
            // Verifică dacă linia conține debug-ul nostru
            if (strpos($line, 'DEBUG') !== false || 
                strpos($line, 'working_hours') !== false ||
                strpos($line, 'Clinica') !== false) {
                echo trim($line) . "\n";
            }
        }
        
        // Pauză scurtă
        usleep(100000); // 0.1 secunde
    }
    
    fclose($handle);
} else {
    echo "❌ Nu s-a putut deschide log-ul\n";
}
?> 