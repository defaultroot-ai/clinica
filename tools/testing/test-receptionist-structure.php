<?php
/**
 * Test Structură Receptionist Dashboard
 */

// Include WordPress
require_once('../../../wp-load.php');

echo '<h2>Test Structură Receptionist Dashboard</h2>';

// Test 1: Verifică dacă clasa există
if (class_exists('Clinica_Receptionist_Dashboard')) {
    echo '<p style="color: green;">✓ Clasa Clinica_Receptionist_Dashboard există</p>';
} else {
    echo '<p style="color: red;">✗ Clasa Clinica_Receptionist_Dashboard nu există</p>';
    exit;
}

// Test 2: Verifică dacă shortcode-ul este înregistrat
if (shortcode_exists('clinica_receptionist_dashboard')) {
    echo '<p style="color: green;">✓ Shortcode-ul clinica_receptionist_dashboard este înregistrat</p>';
} else {
    echo '<p style="color: red;">✗ Shortcode-ul clinica_receptionist_dashboard nu este înregistrat</p>';
}

// Test 3: Verifică fișierele CSS și JS
echo '<h3>Test Fișiere CSS și JS</h3>';

$css_file = plugin_dir_path(__FILE__) . 'assets/css/receptionist-dashboard.css';
$js_file = plugin_dir_path(__FILE__) . 'assets/js/receptionist-dashboard.js';

if (file_exists($css_file)) {
    echo '<p style="color: green;">✓ CSS receptionist dashboard există</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($css_file) . ' bytes</p>';
    
    // Verifică conținutul CSS
    $css_content = file_get_contents($css_file);
    if (strpos($css_content, '.clinica-receptionist-dashboard') !== false) {
        echo '<p style="color: green;">✓ CSS conține stilurile principale</p>';
    } else {
        echo '<p style="color: red;">✗ CSS nu conține stilurile principale</p>';
    }
} else {
    echo '<p style="color: red;">✗ CSS receptionist dashboard nu există</p>';
}

if (file_exists($js_file)) {
    echo '<p style="color: green;">✓ JS receptionist dashboard există</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($js_file) . ' bytes</p>';
    
    // Verifică conținutul JS
    $js_content = file_get_contents($js_file);
    if (strpos($js_content, 'clinicaReceptionistAjax') !== false) {
        echo '<p style="color: green;">✓ JS conține variabilele AJAX</p>';
    } else {
        echo '<p style="color: red;">✗ JS nu conține variabilele AJAX</p>';
    }
} else {
    echo '<p style="color: red;">✗ JS receptionist dashboard nu există</p>';
}

// Test 4: Verifică AJAX handlers
echo '<h3>Test AJAX Handlers</h3>';

$ajax_handlers = array(
    'clinica_load_patient_form',
    'clinica_receptionist_overview',
    'clinica_receptionist_appointments',
    'clinica_receptionist_patients',
    'clinica_receptionist_calendar',
    'clinica_receptionist_reports'
);

foreach ($ajax_handlers as $handler) {
    if (has_action('wp_ajax_' . $handler)) {
        echo '<p style="color: green;">✓ AJAX handler există: ' . $handler . '</p>';
    } else {
        echo '<p style="color: orange;">⚠ AJAX handler nu există: ' . $handler . '</p>';
    }
}

// Test 5: Verifică pagina creată automat
echo '<h3>Test Pagină Creată Automat</h3>';

$receptionist_page = get_page_by_path('clinica-receptionist-dashboard');
if ($receptionist_page) {
    echo '<p style="color: green;">✓ Pagina receptionist dashboard există</p>';
    echo '<p><strong>ID:</strong> ' . $receptionist_page->ID . '</p>';
    echo '<p><strong>Status:</strong> ' . $receptionist_page->post_status . '</p>';
    echo '<p><strong>URL:</strong> <a href="' . get_permalink($receptionist_page->ID) . '" target="_blank">Vezi pagina</a></p>';
    
    // Verifică dacă conține shortcode-ul
    if (strpos($receptionist_page->post_content, '[clinica_receptionist_dashboard]') !== false) {
        echo '<p style="color: green;">✓ Pagina conține shortcode-ul corect</p>';
    } else {
        echo '<p style="color: red;">✗ Pagina nu conține shortcode-ul corect</p>';
        echo '<p><strong>Conținut:</strong> ' . esc_html($receptionist_page->post_content) . '</p>';
    }
} else {
    echo '<p style="color: red;">✗ Pagina receptionist dashboard nu există</p>';
}

// Test 6: Verifică înregistrarea în admin shortcodes
echo '<h3>Test Înregistrare în Admin Shortcodes</h3>';

// Verifică dacă shortcode-ul este în lista din admin
$admin_shortcodes_page = get_page_by_path('clinica-shortcodes');
if ($admin_shortcodes_page) {
    echo '<p style="color: green;">✓ Pagina admin shortcodes există</p>';
    if (strpos($admin_shortcodes_page->post_content, 'clinica_receptionist_dashboard') !== false) {
        echo '<p style="color: green;">✓ Shortcode-ul este documentat în admin</p>';
    } else {
        echo '<p style="color: orange;">⚠ Shortcode-ul nu este documentat în admin</p>';
    }
} else {
    echo '<p style="color: orange;">⚠ Pagina admin shortcodes nu există</p>';
}

// Test 7: Verifică înregistrarea în plugin principal
echo '<h3>Test Înregistrare în Plugin Principal</h3>';

// Verifică dacă clasa este încărcată în plugin principal
$plugin_file = plugin_dir_path(__FILE__) . 'clinica.php';
if (file_exists($plugin_file)) {
    $plugin_content = file_get_contents($plugin_file);
    if (strpos($plugin_content, 'Clinica_Receptionist_Dashboard') !== false) {
        echo '<p style="color: green;">✓ Clasa este înregistrată în plugin principal</p>';
    } else {
        echo '<p style="color: red;">✗ Clasa nu este înregistrată în plugin principal</p>';
    }
} else {
    echo '<p style="color: red;">✗ Fișierul plugin principal nu există</p>';
}

// Link-uri utile
echo '<h3>Link-uri Utile</h3>';
echo '<ul>';
if ($receptionist_page) {
    echo '<li><a href="' . get_permalink($receptionist_page->ID) . '" target="_blank">Pagina Receptionist Dashboard</a></li>';
}
echo '<li><a href="' . admin_url('admin.php?page=clinica-shortcodes') . '" target="_blank">Pagina Shortcode-uri în Admin</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica') . '" target="_blank">Dashboard Admin Clinica</a></li>';
echo '</ul>';

// Instrucțiuni pentru testare
echo '<h3>Instrucțiuni pentru Testare</h3>';
echo '<ol>';
echo '<li>Autentificați-vă ca administrator sau recepționist</li>';
echo '<li>Accesați pagina receptionist dashboard</li>';
echo '<li>Verificați că se încarcă cu design-ul modern</li>';
echo '<li>Testați navigarea între tab-uri</li>';
echo '<li>Apăsați butonul "Pacient Nou" și verificați formularul complet</li>';
echo '<li>Testați validarea CNP și autofill-ul</li>';
echo '<li>Verificați generarea parolei</li>';
echo '<li>Testați crearea unui pacient</li>';
echo '</ol>';

echo '<hr>';
echo '<p><em>Test finalizat la: ' . current_time('Y-m-d H:i:s') . '</em></p>';
?> 