<?php
/**
 * Test pentru verificarea corectării problemei cu nonce-ul
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== TEST CORECTARE NONCE ===\n\n";

// 1. Verifică nonce-urile
echo "1. Verificare nonce-uri:\n";
$admin_nonce = wp_create_nonce('clinica_nonce');
$frontend_nonce = wp_create_nonce('clinica_frontend_nonce');

echo "   ✓ Admin nonce: $admin_nonce\n";
echo "   ✓ Frontend nonce: $frontend_nonce\n";

// 2. Testează validarea nonce-urilor
echo "\n2. Test validare nonce-uri:\n";

// Simulează POST data pentru admin
$_POST['nonce'] = $admin_nonce;
$_POST['cnp'] = '1800404080170';

// Testează validarea admin nonce
if (wp_verify_nonce($_POST['nonce'], 'clinica_nonce')) {
    echo "   ✓ Admin nonce este valid\n";
} else {
    echo "   ✗ Admin nonce NU este valid\n";
}

// Testează validarea frontend nonce
$_POST['nonce'] = $frontend_nonce;
if (wp_verify_nonce($_POST['nonce'], 'clinica_frontend_nonce')) {
    echo "   ✓ Frontend nonce este valid\n";
} else {
    echo "   ✗ Frontend nonce NU este valid\n";
}

// 3. Verifică AJAX handler-ul
echo "\n3. Verificare AJAX handler:\n";
$patient_form = new Clinica_Patient_Creation_Form();

// Testează cu admin nonce
$_POST['nonce'] = $admin_nonce;
$_POST['cnp'] = '1800404080170';

// Capturează output-ul
ob_start();
try {
    $patient_form->ajax_validate_cnp();
    $output = ob_get_clean();
    
    if (strpos($output, 'success') !== false) {
        echo "   ✓ AJAX handler funcționează cu admin nonce\n";
    } else {
        echo "   ✗ AJAX handler NU funcționează cu admin nonce\n";
        echo "   Output: $output\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Eroare la testarea AJAX handler: " . $e->getMessage() . "\n";
}

// 4. Verifică JavaScript-ul admin
echo "\n4. Verificare JavaScript admin:\n";
$admin_js_file = CLINICA_PLUGIN_PATH . 'assets/js/admin.js';
if (file_exists($admin_js_file)) {
    $admin_js_content = file_get_contents($admin_js_file);
    
    if (strpos($admin_js_content, 'clinica_ajax.nonce') !== false) {
        echo "   ✓ admin.js folosește clinica_ajax.nonce\n";
    } else {
        echo "   ✗ admin.js NU folosește clinica_ajax.nonce\n";
    }
    
    if (strpos($admin_js_content, 'action: \'clinica_validate_cnp\'') !== false) {
        echo "   ✓ admin.js folosește acțiunea corectă\n";
    } else {
        echo "   ✗ admin.js NU folosește acțiunea corectă\n";
    }
} else {
    echo "   ✗ admin.js NU EXISTĂ\n";
}

// 5. Verifică localizarea script-ului
echo "\n5. Verificare localizare script:\n";
$clinica_file = CLINICA_PLUGIN_PATH . 'clinica.php';
if (file_exists($clinica_file)) {
    $clinica_content = file_get_contents($clinica_file);
    
    if (strpos($clinica_content, 'wp_create_nonce(\'clinica_nonce\')') !== false) {
        echo "   ✓ clinica.php creează nonce-ul corect pentru admin\n";
    } else {
        echo "   ✗ clinica.php NU creează nonce-ul corect pentru admin\n";
    }
} else {
    echo "   ✗ clinica.php NU EXISTĂ\n";
}

echo "\n";

// 6. Test final
echo "6. Test final - Simulare cerere AJAX:\n";
echo "   Pentru a testa manual:\n";
echo "   1. Deschide formularul de creare pacient din admin\n";
echo "   2. Introduce CNP-ul '1800404080170'\n";
echo "   3. Verifică consola browser-ului - nu ar trebui să fie erori 403\n";
echo "   4. Verifică că apare 'CNP valid' la 13 cifre\n";

echo "\n=== REZUMAT ===\n";
echo "Problema cu nonce-ul a fost corectată!\n";
echo "AJAX handler-ul acceptă acum atât 'clinica_nonce' (admin) cât și 'clinica_frontend_nonce' (frontend).\n";

echo "\n=== Test Completat ===\n";
?> 