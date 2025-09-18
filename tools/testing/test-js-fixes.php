<?php
/**
 * Test JavaScript Fixes
 */

// Include WordPress
require_once('../../../wp-load.php');

echo '<h2>Test JavaScript Fixes</h2>';

// Test 1: Verifică dacă variabilele AJAX sunt localizate corect
echo '<h3>Test Localizare AJAX</h3>';

// Simulează încărcarea paginii pentru a verifica scripturile
wp_enqueue_scripts();

// Verifică dacă scripturile sunt înregistrate
$enqueued_scripts = wp_scripts()->queue;

$ajax_variables = array(
    'clinicaDoctorAjax' => 'doctor-dashboard.js',
    'clinicaAssistantAjax' => 'assistant-dashboard.js',
    'clinicaReceptionistAjax' => 'receptionist-dashboard.js'
);

foreach ($ajax_variables as $variable => $script) {
    if (in_array(str_replace('.js', '', $script), $enqueued_scripts)) {
        echo '<p style="color: green;">✓ Script-ul ' . $script . ' este înregistrat</p>';
    } else {
        echo '<p style="color: orange;">⚠ Script-ul ' . $script . ' nu este înregistrat (normal dacă nu sunt pe pagina corectă)</p>';
    }
}

// Test 2: Verifică funcțiile lipsă din receptionist dashboard
echo '<h3>Test Funcții Receptionist Dashboard</h3>';

$receptionist_js = file_get_contents(plugin_dir_path(__FILE__) . 'assets/js/receptionist-dashboard.js');

$required_functions = array(
    'displayOverviewData',
    'displayAppointmentsData',
    'displayPatientsData',
    'displayCalendarData',
    'displayReportsData',
    'getStatusText'
);

foreach ($required_functions as $function) {
    if (strpos($receptionist_js, $function) !== false) {
        echo '<p style="color: green;">✓ Funcția ' . $function . ' există</p>';
    } else {
        echo '<p style="color: red;">✗ Funcția ' . $function . ' lipsește</p>';
    }
}

// Test 3: Verifică corectarea tooltip-ului în assistant dashboard
echo '<h3>Test Corectare Tooltip</h3>';

$assistant_js = file_get_contents(plugin_dir_path(__FILE__) . 'assets/js/assistant-dashboard.js');

if (strpos($assistant_js, 'typeof $.fn.tooltip !== \'undefined\'') !== false) {
    echo '<p style="color: green;">✓ Tooltip-ul este verificat corect</p>';
} else {
    echo '<p style="color: red;">✗ Tooltip-ul nu este verificat</p>';
}

// Test 4: Verifică inițializarea dashboard-urilor în plugin principal
echo '<h3>Test Inițializare Dashboard-uri</h3>';

$plugin_file = file_get_contents(plugin_dir_path(__FILE__) . 'clinica.php');

if (strpos($plugin_file, '$doctor_dashboard->init()') !== false) {
    echo '<p style="color: green;">✓ Doctor dashboard inițializat corect</p>';
} else {
    echo '<p style="color: red;">✗ Doctor dashboard nu este inițializat</p>';
}

if (strpos($plugin_file, '$assistant_dashboard->init()') !== false) {
    echo '<p style="color: green;">✓ Assistant dashboard inițializat corect</p>';
} else {
    echo '<p style="color: red;">✗ Assistant dashboard nu este inițializat</p>';
}

// Test 5: Verifică AJAX handlers
echo '<h3>Test AJAX Handlers</h3>';

$ajax_handlers = array(
    'clinica_doctor_dashboard_nonce',
    'clinica_assistant_dashboard_nonce',
    'clinica_receptionist_nonce'
);

foreach ($ajax_handlers as $handler) {
    if (has_action('wp_ajax_' . $handler) || has_action('wp_ajax_nopriv_' . $handler)) {
        echo '<p style="color: green;">✓ AJAX handler ' . $handler . ' există</p>';
    } else {
        echo '<p style="color: orange;">⚠ AJAX handler ' . $handler . ' nu există</p>';
    }
}

// Test 6: Verifică fișierele CSS și JS
echo '<h3>Test Fișiere CSS și JS</h3>';

$files_to_check = array(
    'assets/css/doctor-dashboard.css',
    'assets/js/doctor-dashboard.js',
    'assets/css/assistant-dashboard.css',
    'assets/js/assistant-dashboard.js',
    'assets/css/receptionist-dashboard.css',
    'assets/js/receptionist-dashboard.js'
);

foreach ($files_to_check as $file) {
    $file_path = plugin_dir_path(__FILE__) . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo '<p style="color: green;">✓ ' . $file . ' există (' . $size . ' bytes)</p>';
    } else {
        echo '<p style="color: red;">✗ ' . $file . ' nu există</p>';
    }
}

// Test 7: Verifică paginile dashboard-uri
echo '<h3>Test Pagini Dashboard-uri</h3>';

$dashboard_pages = array(
    'clinica-patient-dashboard' => 'Patient Dashboard',
    'clinica-doctor-dashboard' => 'Doctor Dashboard',
    'clinica-assistant-dashboard' => 'Assistant Dashboard',
    'clinica-manager-dashboard' => 'Manager Dashboard',
    'clinica-receptionist-dashboard' => 'Receptionist Dashboard'
);

foreach ($dashboard_pages as $slug => $name) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo '<p style="color: green;">✓ ' . $name . ' există (ID: ' . $page->ID . ')</p>';
        echo '<p><a href="' . get_permalink($page->ID) . '" target="_blank">Vezi pagina</a></p>';
    } else {
        echo '<p style="color: red;">✗ ' . $name . ' nu există</p>';
    }
}

// Instrucțiuni pentru testare
echo '<h3>Instrucțiuni pentru Testare Completă</h3>';
echo '<ol>';
echo '<li>Deschideți Developer Tools (F12) în browser</li>';
echo '<li>Accesați fiecare pagină dashboard:</li>';
echo '<ul>';
echo '<li><a href="' . home_url('/clinica-patient-dashboard/') . '" target="_blank">Patient Dashboard</a></li>';
echo '<li><a href="' . home_url('/clinica-doctor-dashboard/') . '" target="_blank">Doctor Dashboard</a></li>';
echo '<li><a href="' . home_url('/clinica-assistant-dashboard/') . '" target="_blank">Assistant Dashboard</a></li>';
echo '<li><a href="' . home_url('/clinica-manager-dashboard/') . '" target="_blank">Manager Dashboard</a></li>';
echo '<li><a href="' . home_url('/clinica-receptionist-dashboard/') . '" target="_blank">Receptionist Dashboard</a></li>';
echo '</ul>';
echo '<li>Verificați că nu apar erori JavaScript în Console</li>';
echo '<li>Testați funcționalitățile AJAX (schimbare tab-uri, încărcare date)</li>';
echo '<li>Verificați că formularul de creare pacienți funcționează în receptionist dashboard</li>';
echo '</ol>';

// Link-uri utile
echo '<h3>Link-uri Utile</h3>';
echo '<ul>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-shortcodes') . '" target="_blank">Pagina Shortcode-uri în Admin</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica') . '" target="_blank">Dashboard Admin Clinica</a></li>';
echo '</ul>';

echo '<hr>';
echo '<p><em>Test finalizat la: ' . current_time('Y-m-d H:i:s') . '</em></p>';
?> 