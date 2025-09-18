<?php
/**
 * Test pentru accesarea paginii de setări cu autentificare
 */

echo "<h1>Test Accesare Setări cu Autentificare</h1>";

// Simulează o sesiune WordPress
session_start();

// URL-uri pentru test
$login_url = "http://localhost/plm/wp-login.php";
$settings_url = "http://localhost/plm/wp-admin/admin.php?page=clinica-settings";

echo "<h2>1. Testare accesare pagina de login</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $login_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>Login URL: $login_url</p>";
echo "<p>HTTP Code: $http_code</p>";
if ($error) {
    echo "<p>❌ Eroare: $error</p>";
} else {
    echo "<p>✅ Pagina de login accesibilă</p>";
}

echo "<h2>2. Testare accesare pagina de setări</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $settings_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>Settings URL: $settings_url</p>";
echo "<p>HTTP Code: $http_code</p>";
if ($error) {
    echo "<p>❌ Eroare: $error</p>";
} else {
    echo "<p>✅ Pagina de setări accesibilă</p>";
    echo "<p>Lungime răspuns: " . strlen($response) . " bytes</p>";
}

echo "<h2>3. Verificare log-uri generate</h2>";

$log_file = __DIR__ . '/../../logs/settings-debug.log';
if (file_exists($log_file)) {
    $content = file_get_contents($log_file);
    echo "<p>✅ Fișierul log există</p>";
    echo "<h3>Conținutul log-ului:</h3>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p>❌ Fișierul log nu există</p>";
}

echo "<h2>4. Curățare fișiere temporare</h2>";
if (file_exists(__DIR__ . '/cookies.txt')) {
    unlink(__DIR__ . '/cookies.txt');
    echo "<p>✅ Fișierul cookies.txt șters</p>";
}

echo "<p>Test completat!</p>";
?> 