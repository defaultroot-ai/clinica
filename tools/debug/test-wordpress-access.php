<?php
/**
 * Test pentru verificarea accesului la WordPress
 */

echo "<h1>Test Acces WordPress</h1>";

// Test 1: Accesare pagina principală WordPress
$home_url = "http://localhost/plm/";
echo "<h2>Test 1: Pagina principală</h2>";
echo "<p>URL: $home_url</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $home_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
if ($error) {
    echo "<p>❌ Eroare: $error</p>";
} else {
    echo "<p>✅ Pagina principală accesibilă</p>";
    echo "<p>Lungime răspuns: " . strlen($response) . " bytes</p>";
}

// Test 2: Accesare pagina de login WordPress
$login_url = "http://localhost/plm/wp-login.php";
echo "<h2>Test 2: Pagina de login</h2>";
echo "<p>URL: $login_url</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $login_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
if ($error) {
    echo "<p>❌ Eroare: $error</p>";
} else {
    echo "<p>✅ Pagina de login accesibilă</p>";
    echo "<p>Lungime răspuns: " . strlen($response) . " bytes</p>";
}

// Test 3: Accesare admin WordPress
$admin_url = "http://localhost/plm/wp-admin/";
echo "<h2>Test 3: Admin WordPress</h2>";
echo "<p>URL: $admin_url</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $admin_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
if ($error) {
    echo "<p>❌ Eroare: $error</p>";
} else {
    echo "<p>✅ Admin WordPress accesibil</p>";
    echo "<p>Lungime răspuns: " . strlen($response) . " bytes</p>";
}

echo "<h2>Concluzie:</h2>";
if ($http_code == 200) {
    echo "<p>✅ WordPress pare să funcționeze corect</p>";
} else {
    echo "<p>❌ Există probleme cu WordPress (cod HTTP: $http_code)</p>";
}
?> 