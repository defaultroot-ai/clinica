<?php
/**
 * Test Final Receptionist Dashboard
 */

// Include WordPress
require_once('../../../wp-load.php');

echo '<h2>Test Final Receptionist Dashboard</h2>';

// Test 1: Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    echo '<p style="color: red;">✗ Nu sunteți autentificat</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $current_user->roles) . '</p>';

// Test 2: Verifică permisiunile
if (!in_array('clinica_receptionist', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
    echo '<p style="color: red;">✗ Nu aveți permisiunea de a accesa dashboard-ul receptionist</p>';
    echo '<p>Roluri necesare: clinica_receptionist sau administrator</p>';
    exit;
}

echo '<p style="color: green;">✓ Permisiuni OK</p>';

// Test 3: Testează shortcode-ul
echo '<h3>Test Shortcode Receptionist Dashboard</h3>';
$shortcode_output = do_shortcode('[clinica_receptionist_dashboard]');

if (!empty($shortcode_output)) {
    echo '<p style="color: green;">✓ Shortcode-ul funcționează</p>';
    echo '<p><strong>Lungimea output:</strong> ' . strlen($shortcode_output) . ' caractere</p>';
    
    // Verifică dacă conține elementele necesare
    if (strpos($shortcode_output, 'clinica-receptionist-dashboard') !== false) {
        echo '<p style="color: green;">✓ Conține clasa CSS principală</p>';
    } else {
        echo '<p style="color: red;">✗ Nu conține clasa CSS principală</p>';
    }
    
    if (strpos($shortcode_output, 'clinica-receptionist-header') !== false) {
        echo '<p style="color: green;">✓ Conține header-ul</p>';
    } else {
        echo '<p style="color: red;">✗ Nu conține header-ul</p>';
    }
    
    if (strpos($shortcode_output, 'clinica-receptionist-tabs') !== false) {
        echo '<p style="color: green;">✓ Conține tab-urile</p>';
    } else {
        echo '<p style="color: red;">✗ Nu conține tab-urile</p>';
    }
    
    if (strpos($shortcode_output, 'clinica-receptionist-btn') !== false) {
        echo '<p style="color: green;">✓ Conține butoanele</p>';
    } else {
        echo '<p style="color: red;">✗ Nu conține butoanele</p>';
    }
    
} else {
    echo '<p style="color: red;">✗ Shortcode-ul nu returnează output</p>';
}

// Test 4: Verifică fișierele CSS și JS
echo '<h3>Test Fișiere CSS și JS</h3>';

$css_file = CLINICA_PLUGIN_PATH . 'assets/css/receptionist-dashboard.css';
$js_file = CLINICA_PLUGIN_PATH . 'assets/js/receptionist-dashboard.js';

if (file_exists($css_file)) {
    echo '<p style="color: green;">✓ CSS receptionist dashboard există</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($css_file) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ CSS receptionist dashboard nu există</p>';
}

if (file_exists($js_file)) {
    echo '<p style="color: green;">✓ JS receptionist dashboard există</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($js_file) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ JS receptionist dashboard nu există</p>';
}

// Test 5: Verifică AJAX handlers
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

// Test 6: Verifică pagina creată automat
echo '<h3>Test Pagină Creată Automat</h3>';

$receptionist_page = get_page_by_path('clinica-receptionist-dashboard');
if ($receptionist_page) {
    echo '<p style="color: green;">✓ Pagina receptionist dashboard există</p>';
    echo '<p><strong>ID:</strong> ' . $receptionist_page->ID . '</p>';
    echo '<p><strong>Status:</strong> ' . $receptionist_page->post_status . '</p>';
    echo '<p><strong>URL:</strong> <a href="' . get_permalink($receptionist_page->ID) . '" target="_blank">Vezi pagina</a></p>';
} else {
    echo '<p style="color: red;">✗ Pagina receptionist dashboard nu există</p>';
}

// Test 7: Preview dashboard
echo '<h3>Preview Dashboard Receptionist</h3>';
echo '<div style="border: 2px solid #ccc; padding: 20px; margin: 20px 0; background: #f9f9f9;">';
echo '<h4>Output Dashboard:</h4>';
echo '<div style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
echo $shortcode_output;
echo '</div>';
echo '</div>';

// Test 8: Verifică înregistrarea scripturilor
echo '<h3>Test Înregistrare Scripturi</h3>';

// Simulează încărcarea paginii pentru a verifica scripturile
wp_enqueue_scripts();
$enqueued_styles = wp_styles()->queue;
$enqueued_scripts = wp_scripts()->queue;

if (in_array('clinica-receptionist-dashboard', $enqueued_styles)) {
    echo '<p style="color: green;">✓ CSS-ul receptionist dashboard este înregistrat</p>';
} else {
    echo '<p style="color: orange;">⚠ CSS-ul receptionist dashboard nu este înregistrat (normal dacă nu sunt pe pagina corectă)</p>';
}

if (in_array('clinica-receptionist-dashboard', $enqueued_scripts)) {
    echo '<p style="color: green;">✓ JS-ul receptionist dashboard este înregistrat</p>';
} else {
    echo '<p style="color: orange;">⚠ JS-ul receptionist dashboard nu este înregistrat (normal dacă nu sunt pe pagina corectă)</p>';
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

echo '<h3>Instrucțiuni Testare Completă</h3>';
echo '<ol>';
echo '<li>Accesați pagina receptionist dashboard</li>';
echo '<li>Verificați că se încarcă cu design-ul modern</li>';
echo '<li>Testați navigarea între tab-uri</li>';
echo '<li>Apăsați butonul "Pacient Nou" și verificați formularul complet</li>';
echo '<li>Testați validarea CNP și autofill-ul</li>';
echo '<li>Verificați generarea parolei</li>';
echo '<li>Testați crearea unui pacient</li>';
echo '</ol>';

echo '<h3>Informații Debug</h3>';
echo '<p><strong>Plugin URL:</strong> ' . CLINICA_PLUGIN_URL . '</p>';
echo '<p><strong>Plugin Path:</strong> ' . CLINICA_PLUGIN_PATH . '</p>';
echo '<p><strong>Versiune Plugin:</strong> ' . CLINICA_VERSION . '</p>';
echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';

echo '<hr>';
echo '<p><em>Test finalizat la: ' . current_time('Y-m-d H:i:s') . '</em></p>';
?> 