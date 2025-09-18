<?php
/**
 * Test AJAX pentru validarea CNP
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

// Încarcă clasele
require_once('includes/class-clinica-cnp-validator.php');
require_once('includes/class-clinica-cnp-parser.php');
require_once('includes/class-clinica-patient-creation-form.php');

$cnp = '1800404080170';
$nonce = wp_create_nonce('clinica_validate_cnp');

echo '<h1>Test AJAX CNP Validare</h1>';
echo '<p>CNP: ' . $cnp . '</p>';
echo '<p>Nonce: ' . $nonce . '</p>';

// Testează validarea directă
echo '<h2>Test Validare Directă:</h2>';
$validator = new Clinica_CNP_Validator();
$result = $validator->validate_cnp($cnp);
echo '<pre>' . print_r($result, true) . '</pre>';

// Testează parsarea directă
echo '<h2>Test Parsare Directă:</h2>';
$parser = new Clinica_CNP_Parser();
$parsed = $parser->parse_cnp($cnp);
echo '<pre>' . print_r($parsed, true) . '</pre>';

// Simulează cererea AJAX
echo '<h2>Simulare AJAX:</h2>';
$_POST['cnp'] = $cnp;
$_POST['nonce'] = $nonce;

// Creează o instanță a formularului
$form = new Clinica_Patient_Creation_Form();

echo '<p>Formular creat cu succes.</p>';

// Verifică dacă hook-urile sunt înregistrate
echo '<h2>Hook-uri AJAX:</h2>';
global $wp_filter;
if (isset($wp_filter['wp_ajax_clinica_validate_cnp'])) {
    echo '<p style="color: green;">✓ Hook wp_ajax_clinica_validate_cnp este înregistrat</p>';
} else {
    echo '<p style="color: red;">✗ Hook wp_ajax_clinica_validate_cnp NU este înregistrat</p>';
}

if (isset($wp_filter['wp_ajax_nopriv_clinica_validate_cnp'])) {
    echo '<p style="color: green;">✓ Hook wp_ajax_nopriv_clinica_validate_cnp este înregistrat</p>';
} else {
    echo '<p style="color: red;">✗ Hook wp_ajax_nopriv_clinica_validate_cnp NU este înregistrat</p>';
}

echo '<h2>Test JavaScript:</h2>';
echo '<p>Deschideți consola browser-ului și rulați următorul cod:</p>';
echo '<pre>';
echo "jQuery.ajax({
    url: '" . admin_url('admin-ajax.php') . "',
    type: 'POST',
    data: {
        action: 'clinica_validate_cnp',
        cnp: '$cnp',
        nonce: '$nonce'
    },
    success: function(response) {
        console.log('Success:', response);
    },
    error: function(xhr, status, error) {
        console.log('Error:', xhr.responseText);
    }
});";
echo '</pre>';

echo '<p><a href="' . admin_url() . '">← Înapoi la Admin</a></p>';
?> 