<?php
/**
 * Test pentru verificarea scrierii log-urilor
 */

echo "<h1>Test Scriere Log-uri</h1>";

$log_file = __DIR__ . '/../../logs/settings-debug.log';
$debug_log = '=== TEST LOG WRITING ===' . PHP_EOL;
$debug_log .= 'Timestamp: ' . date('Y-m-d H:i:s') . PHP_EOL;
$debug_log .= 'Test message: Log writing works!' . PHP_EOL;

$result = file_put_contents($log_file, $debug_log, FILE_APPEND);

if ($result !== false) {
    echo "<p>✅ Log scris cu succes! Bytes scrise: $result</p>";
    echo "<p>Fișier log: $log_file</p>";
} else {
    echo "<p>❌ Eroare la scrierea log-ului!</p>";
    echo "<p>Verifică permisiunile pentru directorul logs/</p>";
}

// Verifică dacă fișierul există
if (file_exists($log_file)) {
    echo "<p>✅ Fișierul log există</p>";
    $content = file_get_contents($log_file);
    echo "<h3>Conținutul fișierului log:</h3>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p>❌ Fișierul log nu există</p>";
}

echo "<p>Test completat!</p>";
?> 