<?php
/**
 * Script pentru accesarea paginii de setări și generarea log-urilor
 */

echo "<h1>Accesare Pagină Setări</h1>";

// URL-ul paginii de setări
$settings_url = "http://localhost/plm/wp-admin/admin.php?page=clinica-settings";

echo "<p>Încerc să accesez: $settings_url</p>";

// Folosește cURL pentru a accesa pagina
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $settings_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Rezultat:</h2>";
echo "<p>HTTP Code: $http_code</p>";

if ($error) {
    echo "<p>❌ Eroare cURL: $error</p>";
} else {
    echo "<p>✅ Pagina accesată cu succes!</p>";
    echo "<p>Lungimea răspunsului: " . strlen($response) . " bytes</p>";
}

// Verifică dacă log-urile au fost generate
$log_file = __DIR__ . '/../../logs/settings-debug.log';
if (file_exists($log_file)) {
    $content = file_get_contents($log_file);
    echo "<h3>Conținutul log-ului după accesare:</h3>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p>❌ Fișierul log nu există</p>";
}

echo "<p>Test completat!</p>";
?> 