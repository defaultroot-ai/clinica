<?php
/**
 * Test pentru Dashboard-urile Recreate
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a accesa acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo "<h1>Test Dashboard-uri Recreate</h1>";
echo "<p>Utilizator curent: " . esc_html($current_user->display_name) . "</p>";
echo "<p>Roluri: " . implode(', ', $user_roles) . "</p>";

// Testează dashboard-ul de doctor
echo "<h2>Test Dashboard Doctor</h2>";
if (class_exists('Clinica_Doctor_Dashboard')) {
    $doctor_dashboard = new Clinica_Doctor_Dashboard();
    $html = Clinica_Doctor_Dashboard::get_dashboard_html($current_user->ID);
    echo "<div style='border: 2px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 10px;'>";
    echo "<h3>Dashboard Doctor HTML:</h3>";
    echo "<div style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($html);
    echo "</div>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>Clasa Clinica_Doctor_Dashboard nu există!</p>";
}

// Testează dashboard-ul de asistent
echo "<h2>Test Dashboard Assistant</h2>";
if (class_exists('Clinica_Assistant_Dashboard')) {
    $assistant_dashboard = new Clinica_Assistant_Dashboard();
    $html = Clinica_Assistant_Dashboard::get_dashboard_html($current_user->ID);
    echo "<div style='border: 2px solid #ff6b6b; padding: 20px; margin: 20px 0; border-radius: 10px;'>";
    echo "<h3>Dashboard Assistant HTML:</h3>";
    echo "<div style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($html);
    echo "</div>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>Clasa Clinica_Assistant_Dashboard nu există!</p>";
}

// Testează shortcode-urile
echo "<h2>Test Shortcode-uri</h2>";

echo "<h3>Shortcode Doctor Dashboard:</h3>";
echo do_shortcode('[clinica_doctor_dashboard]');

echo "<h3>Shortcode Assistant Dashboard:</h3>";
echo do_shortcode('[clinica_assistant_dashboard]');

// Testează CSS-ul
echo "<h2>Test CSS</h2>";
echo "<p>Verifică dacă fișierele CSS sunt încărcate:</p>";

$css_files = array(
    'assets/css/doctor-dashboard.css',
    'assets/css/assistant-dashboard.css'
);

foreach ($css_files as $css_file) {
    $file_path = plugin_dir_path(__FILE__) . $css_file;
    if (file_exists($file_path)) {
        echo "<p style='color: green;'>✓ {$css_file} există</p>";
        $file_size = filesize($file_path);
        echo "<p>Dimensiune: " . number_format($file_size) . " bytes</p>";
    } else {
        echo "<p style='color: red;'>✗ {$css_file} nu există</p>";
    }
}

// Testează JavaScript-ul
echo "<h2>Test JavaScript</h2>";
echo "<p>Verifică dacă fișierele JS sunt încărcate:</p>";

$js_files = array(
    'assets/js/doctor-dashboard.js',
    'assets/js/assistant-dashboard.js'
);

foreach ($js_files as $js_file) {
    $file_path = plugin_dir_path(__FILE__) . $js_file;
    if (file_exists($file_path)) {
        echo "<p style='color: green;'>✓ {$js_file} există</p>";
        $file_size = filesize($file_path);
        echo "<p>Dimensiune: " . number_format($file_size) . " bytes</p>";
    } else {
        echo "<p style='color: red;'>✗ {$js_file} nu există</p>";
    }
}

// Testează AJAX handlers
echo "<h2>Test AJAX Handlers</h2>";
echo "<p>Verifică dacă handler-ele AJAX sunt înregistrate:</p>";

$ajax_actions = array(
    'clinica_doctor_overview',
    'clinica_doctor_appointments',
    'clinica_doctor_patients',
    'clinica_doctor_medical',
    'clinica_doctor_reports',
    'clinica_assistant_overview',
    'clinica_assistant_appointments',
    'clinica_assistant_patients',
    'clinica_assistant_calendar',
    'clinica_assistant_reports'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}")) {
        echo "<p style='color: green;'>✓ {$action} este înregistrat</p>";
    } else {
        echo "<p style='color: red;'>✗ {$action} nu este înregistrat</p>";
    }
}

echo "<h2>Recomandări</h2>";
echo "<ul>";
echo "<li>Verifică dacă dashboard-urile se afișează corect în frontend</li>";
echo "<li>Testează funcționalitatea de tab-uri</li>";
echo "<li>Verifică dacă butoanele funcționează</li>";
echo "<li>Testează responsivitatea pe mobile</li>";
echo "<li>Verifică dacă formularul de creare pacienți se încarcă în modal</li>";
echo "</ul>";

echo "<h2>Link-uri de Test</h2>";
echo "<p><a href='" . home_url('/dashboard-doctor/') . "' target='_blank'>Test Dashboard Doctor</a></p>";
echo "<p><a href='" . home_url('/dashboard-asistent/') . "' target='_blank'>Test Dashboard Assistant</a></p>";
?> 