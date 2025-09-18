<?php
/**
 * Test Script pentru Dashboard-ul Doctor
 * 
 * Acest script testează funcționalitatea dashboard-ului pentru doctori
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    echo '<h2>Test Dashboard Doctor</h2>';
    echo '<p style="color: red;">Trebuie să fiți autentificat pentru a testa dashboard-ul.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h2>Test Dashboard Doctor</h2>';
echo '<h3>Informații Utilizator Curent</h3>';
echo '<ul>';
echo '<li><strong>ID:</strong> ' . $current_user->ID . '</li>';
echo '<li><strong>Username:</strong> ' . $current_user->user_login . '</li>';
echo '<li><strong>Email:</strong> ' . $current_user->user_email . '</li>';
echo '<li><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</li>';
echo '</ul>';

// Verifică dacă utilizatorul are rolul de doctor
if (!in_array('doctor', $user_roles)) {
    echo '<h3>Test Rol Doctor</h3>';
    echo '<p style="color: orange;">Utilizatorul curent nu are rolul de doctor.</p>';
    echo '<p>Pentru a testa dashboard-ul, trebuie să adăugați rolul "doctor" utilizatorului.</p>';
    
    // Adaugă rolul de doctor temporar pentru test
    $user = new WP_User($current_user->ID);
    $user->add_role('doctor');
    
    echo '<p style="color: green;">Rolul de doctor a fost adăugat temporar pentru test.</p>';
    echo '<p><a href="' . $_SERVER['REQUEST_URI'] . '">Reîncarcă pagina</a></p>';
    exit;
}

echo '<h3>Test Dashboard Doctor</h3>';
echo '<p style="color: green;">Utilizatorul are rolul de doctor. Dashboard-ul ar trebui să funcționeze.</p>';

// Testează shortcode-ul
echo '<h3>Test Shortcode Dashboard Doctor</h3>';
echo '<div style="border: 2px solid #0073AA; padding: 20px; margin: 20px 0; background: #f8f9fa;">';
echo do_shortcode('[clinica_doctor_dashboard]');
echo '</div>';

// Testează AJAX handlers
echo '<h3>Test AJAX Handlers</h3>';
echo '<div style="border: 2px solid #28a745; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

// Test pentru overview
echo '<h4>Test Overview</h4>';
$overview_url = admin_url('admin-ajax.php');
$overview_data = array(
    'action' => 'clinica_get_doctor_overview',
    'nonce' => wp_create_nonce('clinica_doctor_dashboard_nonce')
);

echo '<p><strong>URL:</strong> ' . $overview_url . '</p>';
echo '<p><strong>Action:</strong> ' . $overview_data['action'] . '</p>';
echo '<p><strong>Nonce:</strong> ' . $overview_data['nonce'] . '</p>';

// Test pentru appointments
echo '<h4>Test Appointments</h4>';
$appointments_data = array(
    'action' => 'clinica_get_doctor_appointments',
    'nonce' => wp_create_nonce('clinica_doctor_dashboard_nonce'),
    'date_filter' => 'today',
    'status_filter' => ''
);

echo '<p><strong>Action:</strong> ' . $appointments_data['action'] . '</p>';
echo '<p><strong>Date Filter:</strong> ' . $appointments_data['date_filter'] . '</p>';

// Test pentru patients
echo '<h4>Test Patients</h4>';
$patients_data = array(
    'action' => 'clinica_get_doctor_patients',
    'nonce' => wp_create_nonce('clinica_doctor_dashboard_nonce'),
    'search' => '',
    'sort' => 'name'
);

echo '<p><strong>Action:</strong> ' . $patients_data['action'] . '</p>';
echo '<p><strong>Sort:</strong> ' . $patients_data['sort'] . '</p>';

echo '</div>';

// Testează CSS și JS
echo '<h3>Test Assets</h3>';
echo '<div style="border: 2px solid #ffc107; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

$css_file = plugin_dir_url(__FILE__) . 'assets/css/doctor-dashboard.css';
$js_file = plugin_dir_url(__FILE__) . 'assets/js/doctor-dashboard.js';

echo '<h4>CSS File</h4>';
echo '<p><strong>URL:</strong> <a href="' . $css_file . '" target="_blank">' . $css_file . '</a></p>';

$css_path = plugin_dir_path(__FILE__) . 'assets/css/doctor-dashboard.css';
if (file_exists($css_path)) {
    echo '<p style="color: green;">✓ Fișierul CSS există</p>';
    echo '<p><strong>Size:</strong> ' . filesize($css_path) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul CSS nu există</p>';
}

echo '<h4>JS File</h4>';
echo '<p><strong>URL:</strong> <a href="' . $js_file . '" target="_blank">' . $js_file . '</a></p>';

$js_path = plugin_dir_path(__FILE__) . 'assets/js/doctor-dashboard.js';
if (file_exists($js_path)) {
    echo '<p style="color: green;">✓ Fișierul JS există</p>';
    echo '<p><strong>Size:</strong> ' . filesize($js_path) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul JS nu există</p>';
}

echo '</div>';

// Testează datele doctorului
echo '<h3>Test Date Doctor</h3>';
echo '<div style="border: 2px solid #6f42c1; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

$doctor_specialty = get_user_meta($current_user->ID, 'doctor_specialty', true);
$doctor_license = get_user_meta($current_user->ID, 'doctor_license', true);

echo '<p><strong>Specialitate:</strong> ' . ($doctor_specialty ?: 'N/A') . '</p>';
echo '<p><strong>Cod Parafa:</strong> ' . ($doctor_license ?: 'N/A') . '</p>';

if (!$doctor_specialty || !$doctor_license) {
    echo '<p style="color: orange;">Pentru un test complet, adăugați metadatele doctorului:</p>';
    echo '<pre>';
    echo "update_user_meta({$current_user->ID}, 'doctor_specialty', 'Cardiologie');\n";
    echo "update_user_meta({$current_user->ID}, 'doctor_license', '12345');";
    echo '</pre>';
}

echo '</div>';

// Instrucțiuni de testare
echo '<h3>Instrucțiuni de Testare</h3>';
echo '<div style="border: 2px solid #17a2b8; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

echo '<h4>1. Testare Shortcode</h4>';
echo '<p>Creați o pagină nouă și adăugați shortcode-ul: <code>[clinica_doctor_dashboard]</code></p>';

echo '<h4>2. Testare Funcționalități</h4>';
echo '<ul>';
echo '<li>Schimbarea între tab-uri (Prezentare Generală, Programări, Pacienți, Fișe Medicale)</li>';
echo '<li>Filtrarea programărilor după dată și status</li>';
echo '<li>Căutarea pacienților</li>';
echo '<li>Vizualizarea fișelor medicale</li>';
echo '</ul>';

echo '<h4>3. Testare AJAX</h4>';
echo '<p>Deschideți Developer Tools (F12) și verificați tab-ul Network pentru a vedea cererile AJAX.</p>';

echo '<h4>4. Testare Responsive</h4>';
echo '<p>Testați dashboard-ul pe diferite dimensiuni de ecran.</p>';

echo '</div>';

// Link-uri utile
echo '<h3>Link-uri Utile</h3>';
echo '<div style="border: 2px solid #6c757d; padding: 20px; margin: 20px 0; background: #f8f9fa;">';
echo '<ul>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-dashboard') . '">Dashboard Admin</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-patients') . '">Gestionare Pacienți</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-appointments') . '">Gestionare Programări</a></li>';
echo '<li><a href="' . home_url('clinica-patient-dashboard') . '">Dashboard Pacient</a></li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p><em>Test creat pentru verificarea dashboard-ului doctorilor - Clinica Plugin</em></p>';
?> 