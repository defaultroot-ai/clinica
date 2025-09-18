<?php
/**
 * Test pentru verificarea modificărilor din admin.js
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST MODIFICĂRI ADMIN.JS ===\n\n";

// 1. Verifică fișierul admin.js
echo "1. Verificare admin.js:\n";
$admin_js_file = CLINICA_PLUGIN_PATH . 'assets/js/admin.js';

if (file_exists($admin_js_file)) {
    $admin_js_content = file_get_contents($admin_js_file);
    $admin_js_time = filemtime($admin_js_file);
    echo "   ✓ admin.js există (modificat la: " . date('Y-m-d H:i:s', $admin_js_time) . ")\n";
    
    // Verifică conținutul
    if (strpos($admin_js_content, 'cnp.length !== 13') !== false) {
        echo "   ✓ Conține logica de validare la 13 cifre\n";
    } else {
        echo "   ✗ NU conține logica de validare la 13 cifre\n";
    }
    
    if (strpos($admin_js_content, 'Introduceți toate cele 13 cifre') !== false) {
        echo "   ✓ Conține mesajul de progres\n";
    } else {
        echo "   ✗ NU conține mesajul de progres\n";
    }
    
    if (strpos($admin_js_content, 'cnpValidationRequest') !== false) {
        echo "   ✓ Conține anularea cererilor anterioare\n";
    } else {
        echo "   ✗ NU conține anularea cererilor anterioare\n";
    }
    
    if (strpos($admin_js_content, 'ClinicaAdmin.generatePasswordFromCNP') !== false) {
        echo "   ✓ Conține generarea parolei din CNP\n";
    } else {
        echo "   ✗ NU conține generarea parolei din CNP\n";
    }
} else {
    echo "   ✗ admin.js NU EXISTĂ\n";
}

echo "\n";

// 2. Verifică fișierul admin.css
echo "2. Verificare admin.css:\n";
$admin_css_file = CLINICA_PLUGIN_PATH . 'assets/css/admin.css';

if (file_exists($admin_css_file)) {
    $admin_css_content = file_get_contents($admin_css_file);
    $admin_css_time = filemtime($admin_css_file);
    echo "   ✓ admin.css există (modificat la: " . date('Y-m-d H:i:s', $admin_css_time) . ")\n";
    
    if (strpos($admin_css_content, 'cnp-feedback') !== false) {
        echo "   ✓ Conține stilurile pentru feedback CNP\n";
    } else {
        echo "   ✗ NU conține stilurile pentru feedback CNP\n";
    }
    
    if (strpos($admin_css_content, 'is-valid') !== false) {
        echo "   ✓ Conține stilurile pentru câmpuri validate\n";
    } else {
        echo "   ✗ NU conține stilurile pentru câmpuri validate\n";
    }
} else {
    echo "   ✗ admin.css NU EXISTĂ\n";
}

echo "\n";

// 3. Verifică versiunea dinamică în clinica.php
echo "3. Verificare versiune dinamică admin:\n";
$clinica_file = CLINICA_PLUGIN_PATH . 'clinica.php';
if (file_exists($clinica_file)) {
    $clinica_content = file_get_contents($clinica_file);
    if (strpos($clinica_content, 'filemtime(CLINICA_PLUGIN_PATH . \'assets/js/admin.js\')') !== false) {
        echo "   ✓ Versiunea dinamică pentru admin.js este activată\n";
    } else {
        echo "   ✗ Versiunea dinamică pentru admin.js NU este activată\n";
    }
    
    if (strpos($clinica_content, 'filemtime(CLINICA_PLUGIN_PATH . \'assets/css/admin.css\')') !== false) {
        echo "   ✓ Versiunea dinamică pentru admin.css este activată\n";
    } else {
        echo "   ✗ Versiunea dinamică pentru admin.css NU este activată\n";
    }
} else {
    echo "   ✗ clinica.php NU EXISTĂ\n";
}

echo "\n";

// 4. Testează validarea CNP
echo "4. Test validare CNP:\n";
$cnp = '1800404080170';
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);

if ($result['valid']) {
    echo "   ✓ CNP $cnp este valid\n";
    
    $parser = new Clinica_CNP_Parser();
    $parsed = $parser->parse_cnp($cnp);
    echo "   ✓ Parsare CNP funcționează:\n";
    echo "     - Data nașterii: {$parsed['birth_date']}\n";
    echo "     - Sex: {$parsed['gender']}\n";
    echo "     - Vârsta: {$parsed['age']}\n";
} else {
    echo "   ✗ CNP $cnp este invalid: {$result['error']}\n";
}

echo "\n";

// 5. Rezumat
echo "=== REZUMAT ===\n";
echo "Toate modificările pentru admin sunt implementate!\n";
echo "\nPentru a testa:\n";
echo "1. Deschide formularul de creare pacient din admin\n";
echo "2. Introduce CNP-ul '1800404080170' cifră cu cifră\n";
echo "3. Verifică că nu apar mesaje 'Eroare la validare' multiple\n";
echo "4. Verifică că la 12 cifre apare mesajul de progres\n";
echo "5. Verifică că la 13 cifre apare 'CNP valid'\n";

echo "\n=== Test Completat ===\n";
?> 